<?php

declare(strict_types=1);

namespace Psalm\Internal\Type;

use Psalm\Codebase;
use Psalm\Internal\TypeVisitor\ContainsAtomicVisitor;
use Psalm\Internal\TypeVisitor\TemplateTypeCollector;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TCallableObject;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Atomic\TConditional;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TKeyOf;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNever;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TObjectWithProperties;
use Psalm\Type\Atomic\TPropertiesOf;
use Psalm\Type\Atomic\TStarProjection;
use Psalm\Type\Atomic\TTemplateIndexedAccess;
use Psalm\Type\Atomic\TTemplateKeyOf;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTemplateParamClass;
use Psalm\Type\Atomic\TTemplatePropertiesOf;
use Psalm\Type\Atomic\TTemplateValueOf;
use Psalm\Type\Atomic\TValueOf;
use Psalm\Type\Union;

use function array_all;
use function array_any;
use function array_keys;
use function array_shift;
use function array_slice;
use function array_values;
use function assert;
use function count;
use function reset;

/**
 * The `*` type argument of a generic object (`Pair<int, *>`), see {@see TStarProjection}.
 *
 * @internal
 */
final class StarProjection
{
    public const NAME = '*';

    /**
     * The atomic types a class template's argument is read through. standIn() says what each stands for when the
     * template is bound to `*`; a test checks the list against every atomic type with a defining class.
     */
    public const TEMPLATE_ATOMICS = [
        TTemplateParam::class,
        TTemplateParamClass::class,
        TTemplateKeyOf::class,
        TTemplateValueOf::class,
        TTemplatePropertiesOf::class,
        TTemplateIndexedAccess::class,
        TConditional::class,
    ];

    /** @var array<string, true> the classes whose template bounds boundStars() is expanding */
    private static array $expanding = [];

    /**
     * @psalm-pure
     */
    public static function create(bool $from_docblock, ?Union $as = null): Union
    {
        return new Union([new TStarProjection($from_docblock, $as)], ['from_docblock' => $from_docblock]);
    }

    /**
     * @psalm-pure
     */
    public static function isStar(Union $type): bool
    {
        return $type->isSingle() && $type->getSingleAtomic() instanceof TStarProjection;
    }

    /**
     * What reading a template bound to $argument yields: for `*`, what is known of the unknown type (the bound it
     * carries, else $bound).
     *
     * @psalm-pure
     */
    public static function read(Union $argument, ?Union $bound = null): Union
    {
        $star = $argument->isSingle() ? $argument->getSingleAtomic() : null;

        return $star instanceof TStarProjection ? $star->as ?? $bound ?? Type::getMixed() : $argument;
    }

    /**
     * The generic object with each `*` argument read as what is known of it (see boundStars()), else as the bound of
     * its template
     *
     * @psalm-mutation-free
     */
    public static function readArguments(TGenericObject $type, Codebase $codebase): TGenericObject
    {
        if (!array_any($type->type_params, self::isStar(...))) {
            return $type;
        }

        $template_types = $codebase->classlike_storage_provider->has($type->value)
            ? array_values($codebase->classlike_storage_provider->get($type->value)->template_types ?? [])
            : [];
        $type_params = [];

        foreach ($type->type_params as $offset => $type_param) {
            $template_type_map = $template_types[$offset] ?? null;
            $type_params[] = self::read($type_param, $template_type_map === null ? null : reset($template_type_map));
        }

        return $type->setTypeParams($type_params);
    }

    /**
     * The `*` a type argument mentioning templates bound to `*` stands for, knowing it reads as the argument read
     * through the receiver like a member's type: `@extends Box<list<T>>` on an `LBox<*>` reads as `list<object>`,
     * `Box<T>` as `Box<*>` and `list<Closure(T): void>` as `list<Closure(never): void>`; as nothing more than the
     * template's bound when a callable in it is out of reach
     *
     * @psalm-external-mutation-free
     */
    public static function createForArgument(
        Union $type_param,
        TemplateResult $template_result,
        ?Codebase $codebase,
    ): Union {
        return self::create(
            $type_param->from_docblock,
            self::readByPosition($type_param, $template_result, false, $codebase),
        );
    }

    /**
     * The type with the templates the template result binds replaced, those bound to `*` by position: where the
     * value is read, as what is known of the `*` (an invariant type argument mentioning one is `*` itself); where it
     * is written (a parameter of a callable in it), as accepting nothing. Null for a callable it does not reach.
     *
     * @psalm-external-mutation-free
     */
    private static function readByPosition(
        Union $type,
        TemplateResult $template_result,
        bool $written,
        ?Codebase $codebase,
    ): ?Union {
        $atomic_types = [];

        foreach ($type->getAtomicTypes() as $atomic_type) {
            $read = self::readAtomicByPosition($atomic_type, $template_result, $written, $codebase);

            if ($read === null) {
                return null;
            }

            $atomic_types = [...$atomic_types, ...array_values($read->getAtomicTypes())];
        }

        return $type->getBuilder()->setTypes(TypeCombiner::combine($atomic_types, $codebase)->getAtomicTypes())
            ->freeze();
    }

    /**
     * @psalm-external-mutation-free
     */
    private static function readAtomicByPosition(
        Atomic $atomic_type,
        TemplateResult $template_result,
        bool $written,
        ?Codebase $codebase,
    ): ?Union {
        $stand_in = self::standIn($atomic_type, $template_result, !$written, $codebase);

        if ($stand_in !== null) {
            return new Union($stand_in);
        }

        if ($atomic_type instanceof TTemplateParam
            && isset($template_result->lower_bounds[$atomic_type->param_name][$atomic_type->defining_class])
        ) {
            return TemplateStandinTypeReplacer::getMostSpecificTypeFromBounds(
                $template_result->lower_bounds[$atomic_type->param_name][$atomic_type->defining_class],
                $codebase,
            );
        }

        // a generic object is read here too: an invariant argument mentioning such a template is `*` knowing its read
        if (!self::mentionsStarTemplate(new Union([$atomic_type]), $template_result)
            || !self::contains($atomic_type, [TClosure::class, TCallable::class, TGenericObject::class])
        ) {
            return self::replaceTemplates($atomic_type, $template_result, $written, $codebase);
        }

        // an intersection (`Countable&Box<Closure(T): void>`) is read part by part
        if (($atomic_type instanceof TNamedObject
                || $atomic_type instanceof TIterable
                || $atomic_type instanceof TObjectWithProperties)
            && $atomic_type->extra_types
        ) {
            $read_parts = [];

            foreach ([$atomic_type->setIntersectionTypes([]), ...$atomic_type->extra_types] as $part) {
                $read = self::readAtomicByPosition($part, $template_result, $written, $codebase);
                $read_part = $read !== null && $read->isSingle() ? $read->getSingleAtomic() : null;

                if (!$read_part instanceof TNamedObject
                    && !$read_part instanceof TIterable
                    && !$read_part instanceof TObjectWithProperties
                    && !$read_part instanceof TTemplateParam
                ) {
                    return null;
                }

                $read_parts[] = $read_part;
            }

            $intersected = $read_parts[0]->getIntersectionTypes();

            foreach (array_slice($read_parts, 1) as $read_part) {
                $intersected[$read_part->getKey()] = $read_part;
            }

            return new Union([$read_parts[0]->setIntersectionTypes($intersected)]);
        }

        if ($atomic_type instanceof TClosure || $atomic_type instanceof TCallable) {
            $params = null;

            foreach ($atomic_type->params ?? [] as $param) {
                $param_type = $param->type === null
                    ? null
                    : self::readByPosition($param->type, $template_result, !$written, $codebase);

                if ($param->type !== null && $param_type === null) {
                    return null;
                }

                $params[] = $param_type === null ? $param : $param->setType($param_type);
            }

            if ($atomic_type->params === []) {
                $params = [];
            }

            $return_type = $atomic_type->return_type === null
                ? null
                : self::readByPosition($atomic_type->return_type, $template_result, $written, $codebase);

            if ($atomic_type->return_type !== null && $return_type === null) {
                return null;
            }

            return new Union([$atomic_type->replace($params, $return_type)]);
        }

        if ($atomic_type instanceof TKeyedArray) {
            $properties = [];

            foreach ($atomic_type->properties as $key => $property) {
                $read = self::readByPosition($property, $template_result, $written, $codebase);

                if ($read === null) {
                    return null;
                }

                $properties[$key] = $read;
            }

            $fallback_params = $atomic_type->fallback_params === null
                ? null
                : self::readTypeParams($atomic_type->fallback_params, $template_result, $written, $codebase);

            if ($atomic_type->fallback_params !== null && $fallback_params === null) {
                return null;
            }

            return new Union([TKeyedArray::make(
                $properties,
                $atomic_type->class_strings,
                $fallback_params,
                $atomic_type->is_list,
                $atomic_type->from_docblock,
            )]);
        }

        if ($atomic_type instanceof TArray || $atomic_type instanceof TIterable) {
            $type_params = self::readTypeParams($atomic_type->type_params, $template_result, $written, $codebase);

            return $type_params === null ? null : new Union([$atomic_type->setTypeParams($type_params)]);
        }

        if ($atomic_type instanceof TObjectWithProperties) {
            $properties = [];

            foreach ($atomic_type->properties as $name => $property) {
                $read = self::readByPosition($property, $template_result, $written, $codebase);

                if ($read === null) {
                    return null;
                }

                $properties[$name] = $read;
            }

            return new Union([$atomic_type->setProperties($properties)]);
        }

        if ($atomic_type instanceof TGenericObject) {
            $type_params = [];

            foreach ($atomic_type->type_params as $offset => $type_param) {
                $read = self::readByPosition($type_param, $template_result, $written, $codebase);

                if ($read === null) {
                    return null;
                }

                $type_params[] = !$written
                    && self::projectInvariantArgument($atomic_type, $offset, $template_result, $codebase) !== null
                        ? self::create($type_param->from_docblock, $read)
                        : $read;
            }

            return new Union([$atomic_type->setTypeParams($type_params)]);
        }

        return self::contains($atomic_type, [TClosure::class, TCallable::class])
            ? null
            : self::replaceTemplates($atomic_type, $template_result, $written, $codebase);
    }

    /**
     * @psalm-mutation-free
     */
    private static function replaceTemplates(
        Atomic $atomic_type,
        TemplateResult $template_result,
        bool $written,
        ?Codebase $codebase,
    ): Union {
        return new Union([$atomic_type->replaceTemplateTypesWithArgTypes(
            $written ? self::withStarsAcceptingNothing($template_result) : $template_result,
            $codebase,
        )]);
    }

    /**
     * @param array{Union, Union} $type_params
     * @return array{Union, Union}|null
     * @psalm-external-mutation-free
     */
    private static function readTypeParams(
        array $type_params,
        TemplateResult $template_result,
        bool $written,
        ?Codebase $codebase,
    ): ?array {
        $key_type = self::readByPosition($type_params[0], $template_result, $written, $codebase);
        $value_type = self::readByPosition($type_params[1], $template_result, $written, $codebase);

        return $key_type === null || $value_type === null ? null : [$key_type, $value_type];
    }

    /**
     * @param list<class-string<Atomic>> $atomic_classes
     * @psalm-pure
     */
    public static function contains(Atomic|Union $type, array $atomic_classes): bool
    {
        $finder = new ContainsAtomicVisitor($atomic_classes);
        $finder->traverse($type);

        return $finder->matches();
    }

    /**
     * Whether writing to the type accepts nothing: it is one of TEMPLATE_ATOMICS reading a template the template
     * result binds to `*` (without a template result, a `*` value), or a keyed array with a required slot accepting
     * nothing. Such an array is not `array{value: never}`, which Psalm reads as the empty array.
     *
     * @psalm-mutation-free
     */
    public static function acceptsNothing(Atomic $type, ?TemplateResult $template_result): bool
    {
        if ($template_result === null
            ? $type instanceof TStarProjection
            : self::readsTemplateBoundToStar($type, $template_result)
        ) {
            return true;
        }

        if (!$type instanceof TKeyedArray) {
            return false;
        }

        foreach ($type->properties as $property) {
            if (!$property->possibly_undefined
                && array_all(
                    $property->getAtomicTypes(),
                    static fn(Atomic $atomic_type): bool => self::acceptsNothing($atomic_type, $template_result),
                )
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * The template result with the templates it binds to `*` bound to `never` instead: what writing to them accepts
     *
     * @psalm-mutation-free
     */
    private static function withStarsAcceptingNothing(TemplateResult $template_result): TemplateResult
    {
        $lower_bounds = $template_result->lower_bounds;

        foreach ($lower_bounds as $template_name => $type_map) {
            foreach ($type_map as $defining_class => $bounds) {
                if (count($bounds) === 1 && self::isStar($bounds[0]->type)) {
                    $lower_bounds[$template_name][$defining_class] = [new TemplateBound(Type::getNever())];
                }
            }
        }

        $result = new TemplateResult($template_result->template_types, []);
        $result->lower_bounds = $lower_bounds;

        return $result;
    }

    /**
     * What a template atomic reading a template bound to `*` stands for, decided here for each of TEMPLATE_ATOMICS
     * so that both template replacers agree: where a value is passed in, nothing (the unknown type accepts no value,
     * see acceptsNothing()); where one is handed out ($read), the atomic read through what is known of the `*`
     * (`key-of<TArr>` on a `Holder<*>` is the keys of TArr's bound). Null when the atomic reads no template bound to
     * `*`. A read known to be nothing more than mixed is any value, so the replacers read the whole union it is in as
     * mixed: `TValue|null` on a `Collection<int, *>` is mixed.
     *
     * @return non-empty-list<Atomic>|null
     * @psalm-external-mutation-free
     */
    public static function standIn(
        Atomic $type,
        TemplateResult $template_result,
        bool $read,
        ?Codebase $codebase,
    ): ?array {
        if (!$read) {
            return self::acceptsNothing($type, $template_result) ? [new TNever()] : null;
        }

        if (!self::readsTemplateBoundToStar($type, $template_result)) {
            return null;
        }

        if ($type instanceof TTemplateParam) {
            return self::withIntersectionTypes(
                self::readTemplate($type->param_name, $type->defining_class, $type->as, $template_result, $codebase),
                $type->extra_types,
            );
        }

        if ($type instanceof TTemplateParamClass) {
            $class = self::readTemplate(
                $type->param_name,
                $type->defining_class,
                new Union([$type->as_type ?? new TObject()]),
                $template_result,
                $codebase,
            );
            $class = $class->isSingle() ? $class->getSingleAtomic() : null;

            return [$class instanceof TNamedObject
                ? new TClassString($class->value, $class)
                : new TClassString($type->as, $type->as_type)];
        }

        if ($type instanceof TTemplateKeyOf || $type instanceof TTemplateValueOf) {
            $array = self::readTemplate(
                $type->param_name,
                $type->defining_class,
                $type->as,
                $template_result,
                $codebase,
            );
            $read_type = $type instanceof TTemplateKeyOf
                ? TKeyOf::getArrayKeyType($array)
                : ($codebase === null ? null : TValueOf::getValueType($array, $codebase));

            return array_values(($read_type ?? Type::getMixed())->getAtomicTypes());
        }

        if ($type instanceof TTemplatePropertiesOf) {
            $class = self::readTemplate(
                $type->param_name,
                $type->defining_class,
                $type->as->as,
                $template_result,
                $codebase,
            );
            $class = $class->isSingle() ? $class->getSingleAtomic() : null;

            return [$class instanceof TNamedObject
                ? new TPropertiesOf($class, $type->visibility_filter)
                : Type::getArrayAtomic()];
        }

        if ($type instanceof TTemplateIndexedAccess) {
            return array_values(self::readIndexedAccess($type, $template_result, $codebase)->getAtomicTypes());
        }

        // a conditional on an unknown type is either of its results
        assert($type instanceof TConditional);

        $results = Type::combineUnionTypes($type->if_type, $type->else_type, $codebase);

        return array_values(
            (self::readByPosition($results, $template_result, false, $codebase) ?? Type::getMixed())->getAtomicTypes(),
        );
    }

    /**
     * What a template is known to be: what is known of the `*` it is bound to, else what it is bound to, else $as
     *
     * @psalm-external-mutation-free
     */
    private static function readTemplate(
        string $param_name,
        string $defining_class,
        Union $as,
        TemplateResult $template_result,
        ?Codebase $codebase,
    ): Union {
        $star = self::getStarBinding($param_name, $defining_class, $template_result);

        if ($star !== null) {
            return self::read($star, $as);
        }

        if (isset($template_result->lower_bounds[$param_name][$defining_class])) {
            return TemplateStandinTypeReplacer::getMostSpecificTypeFromBounds(
                $template_result->lower_bounds[$param_name][$defining_class],
                $codebase,
            );
        }

        return $template_result->template_types[$param_name][$defining_class] ?? $as;
    }

    /**
     * `TArr[TKey]` with what is known of both: the value at a known offset, else any value of the array
     *
     * @psalm-external-mutation-free
     */
    private static function readIndexedAccess(
        TTemplateIndexedAccess $type,
        TemplateResult $template_result,
        ?Codebase $codebase,
    ): Union {
        $array = self::readTemplate(
            $type->array_param_name,
            $type->defining_class,
            Type::getMixed(),
            $template_result,
            $codebase,
        );
        $offset = self::readTemplate(
            $type->offset_param_name,
            $type->defining_class,
            Type::getArrayKey(),
            $template_result,
            $codebase,
        );
        $keyed_array = $array->isSingle() ? $array->getSingleAtomic() : null;
        $values = [];

        foreach ($offset->getAtomicTypes() as $offset_atomic) {
            if (!$keyed_array instanceof TKeyedArray
                || !($offset_atomic instanceof TLiteralString || $offset_atomic instanceof TLiteralInt)
                || !isset($keyed_array->properties[$offset_atomic->value])
            ) {
                return ($codebase === null ? null : TValueOf::getValueType($array, $codebase)) ?? Type::getMixed();
            }

            $values[] = $keyed_array->properties[$offset_atomic->value];
        }

        return Type::combineUnionTypeArray($values, $codebase);
    }

    /**
     * $type intersected with a template's intersection types (`T&Countable`)
     *
     * @param array<string, TNamedObject|TTemplateParam|TIterable|TObjectWithProperties|TCallableObject> $extra_types
     * @return non-empty-list<Atomic>
     * @psalm-mutation-free
     */
    private static function withIntersectionTypes(Union $type, array $extra_types): array
    {
        $atomic_types = [];

        foreach ($type->getAtomicTypes() as $atomic_type) {
            if (!$extra_types) {
                $atomic_types[] = $atomic_type;
            } elseif ($atomic_type instanceof TNamedObject
                || $atomic_type instanceof TTemplateParam
                || $atomic_type instanceof TIterable
                || $atomic_type instanceof TObjectWithProperties
            ) {
                $atomic_types[] = $atomic_type->setIntersectionTypes([
                    ...$atomic_type->getIntersectionTypes(),
                    ...$extra_types,
                ]);
            } elseif ($atomic_type instanceof TObject) {
                $intersected = $extra_types;
                $first = array_shift($intersected);
                $atomic_types[] = $first->setIntersectionTypes($intersected);
            } else {
                $atomic_types[] = $atomic_type;
            }
        }

        return $atomic_types;
    }

    /**
     * Whether the type is one of TEMPLATE_ATOMICS reading a template the template result binds to `*`
     *
     * @psalm-mutation-free
     */
    public static function readsTemplateBoundToStar(Atomic $type, TemplateResult $template_result): bool
    {
        [$defining_class, $param_names] = match (true) {
            $type instanceof TTemplateIndexedAccess
                => [$type->defining_class, [$type->array_param_name, $type->offset_param_name]],
            $type instanceof TTemplateParam,
            $type instanceof TTemplateParamClass,
            $type instanceof TTemplateKeyOf,
            $type instanceof TTemplateValueOf,
            $type instanceof TTemplatePropertiesOf,
            $type instanceof TConditional => [$type->defining_class, [$type->param_name]],
            default => ['', []],
        };

        foreach ($param_names as $param_name) {
            if (self::getStarBinding($param_name, $defining_class, $template_result) !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * The `*` the template result binds the template to, if any
     *
     * @psalm-mutation-free
     */
    private static function getStarBinding(
        string $param_name,
        string $defining_class,
        TemplateResult $template_result,
    ): ?Union {
        $template_type = $template_result->template_types[$param_name][$defining_class] ?? null;

        if ($template_type !== null && self::isStar($template_type)) {
            return $template_type;
        }

        $lower_bounds = $template_result->lower_bounds[$param_name][$defining_class] ?? null;

        return $lower_bounds !== null && count($lower_bounds) === 1 && self::isStar($lower_bounds[0]->type)
            ? $lower_bounds[0]->type
            : null;
    }

    /**
     * Whether the template result binds a template to `*`
     *
     * @psalm-mutation-free
     */
    public static function bindsStar(TemplateResult $template_result): bool
    {
        foreach ($template_result->template_types as $type_map) {
            foreach ($type_map as $type) {
                if (self::isStar($type)) {
                    return true;
                }
            }
        }

        foreach ($template_result->lower_bounds as $type_map) {
            foreach ($type_map as $lower_bounds) {
                if (count($lower_bounds) === 1 && self::isStar($lower_bounds[0]->type)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * A read-only copy of a template result binding only the templates it binds to something other than `*`
     *
     * @psalm-mutation-free
     */
    public static function withoutStarBindings(TemplateResult $template_result): TemplateResult
    {
        if (!self::bindsStar($template_result)) {
            return $template_result;
        }

        $template_types = [];

        foreach ($template_result->template_types as $template_name => $type_map) {
            foreach ($type_map as $defining_class => $type) {
                if (!self::isStar($type)) {
                    $template_types[$template_name][$defining_class] = $type;
                }
            }
        }

        $result = new TemplateResult($template_types, []);
        $result->readonly = true;

        return $result;
    }

    /**
     * The argument at $offset of a generic object read through a receiver like `Pair<int, *>`, when the argument is
     * invariant and mentions a template bound to `*`: it stands for an unknown type too, as it is one fixed type
     * built from the unknown one. It is the template's own `*` when it is the whole argument (`Pair<TFirst, TSecond>`
     * is `Pair<int, *>`), and `*` when the template is anywhere inside it (`Box<list<TSecond>>` and
     * `Box<Box<TSecond>>` are `Box<*>`). Null when the argument is covariant (an unknown class counts as invariant),
     * mentions no such template, or is a `*` already (the template replacer reads what is known of it). Without a
     * template result, the `*` are values of the type already (see StarProjectionVisitor) and the argument mentions
     * one by containing it.
     *
     * @psalm-mutation-free
     */
    public static function projectInvariantArgument(
        TGenericObject $type,
        int $offset,
        ?TemplateResult $template_result,
        ?Codebase $codebase,
    ): ?Union {
        $type_param = $type->type_params[$offset];

        if (self::isStar($type_param)
            || !($template_result === null
                ? self::contains($type_param, [TStarProjection::class])
                : self::mentionsStarTemplate($type_param, $template_result))
            || ($codebase !== null
                && $codebase->classlike_storage_provider->has($type->value)
                && ($codebase->classlike_storage_provider->get($type->value)->template_covariants[$offset] ?? false))
        ) {
            return null;
        }

        $atomic = $type_param->isSingle() ? $type_param->getSingleAtomic() : null;

        if ($atomic instanceof TTemplateParam && $template_result !== null) {
            return self::getStarBinding($atomic->param_name, $atomic->defining_class, $template_result);
        }

        return self::create($type_param->from_docblock);
    }

    /**
     * The argument at $offset projected like projectInvariantArgument() where it is read: a `*` standing for a type
     * built from the template knows the type with the template's `*` read and the other templates instantiated
     * (`Box<list<TSecond>>` is a `Box<*>` of `list<object>`, `Box<Box<TSecond>>` a `Box<*>` of `Box<*>`). It knows
     * nothing more than the template's bound when a callable in it takes the template (this reads it whatever its
     * position, see isReadByPosition()) or another template in it is not instantiated yet.
     *
     * @psalm-mutation-free
     */
    public static function readInvariantArgument(
        TGenericObject $type,
        int $offset,
        TemplateResult $template_result,
        ?Codebase $codebase,
    ): ?Union {
        $projected = self::projectInvariantArgument($type, $offset, $template_result, $codebase);
        $star = $projected?->getSingleAtomic();
        $type_param = $type->type_params[$offset];

        if (!$star instanceof TStarProjection
            || $star->as !== null
            || ($type_param->isSingle() && $type_param->getSingleAtomic() instanceof TTemplateParam)
            || self::contains($type_param, [TClosure::class, TCallable::class])
        ) {
            return $projected;
        }

        $instances = [];

        foreach (self::getReadTemplates($type_param) as $template) {
            $instance = self::getStarBinding($template->param_name, $template->defining_class, $template_result)
                ?? self::getInstance($template, $template_result);

            if ($instance === null) {
                return $projected;
            }

            $instances[$template->param_name][$template->defining_class] = $instance;
        }

        $instance_result = new TemplateResult([], $instances);
        $read = [];

        foreach ($type_param->getAtomicTypes() as $atomic_type) {
            if ($atomic_type instanceof TTemplateParam) {
                $instance = $instances[$atomic_type->param_name][$atomic_type->defining_class];
                $read = [...$read, ...array_values(self::read($instance, $atomic_type->as)->getAtomicTypes())];
            } else {
                $read[] = $atomic_type->replaceTemplateTypesWithArgTypes($instance_result, $codebase);
            }
        }

        $read_type = new Union($read);

        foreach (self::getReadTemplates($read_type) as $template) {
            if (isset($template_result->template_types[$template->param_name][$template->defining_class])
                || isset($template_result->lower_bounds[$template->param_name][$template->defining_class])
            ) {
                return $projected;
            }
        }

        return self::create($type_param->from_docblock, $read_type);
    }

    /**
     * Whether the argument at $offset, projected to `*` where it is read, contains a callable: what the `*` knows of
     * it is then the argument read by position (`Box<Closure(T): void>` is a `Box<*>` of `Closure(never): void`),
     * which the template replacer reads
     *
     * @psalm-pure
     */
    public static function isReadByPosition(Union $projected, TGenericObject $type, int $offset): bool
    {
        $star = $projected->getSingleAtomic();
        $type_param = $type->type_params[$offset];

        return $star instanceof TStarProjection
            && $star->as === null
            && !($type_param->isSingle() && $type_param->getSingleAtomic() instanceof TTemplateParam)
            && self::contains($type_param, [TClosure::class, TCallable::class]);
    }

    /**
     * The type the template result instantiates the template with: the one type inferred for it, else the type it
     * replaces it with when that is not merely the template's bound. Null when it is not instantiated (yet).
     *
     * @psalm-mutation-free
     */
    private static function getInstance(TTemplateParam $template, TemplateResult $template_result): ?Union
    {
        $instance = null;

        foreach ($template_result->lower_bounds[$template->param_name][$template->defining_class] ?? [] as $bound) {
            if (self::mentionsTemplate($bound->type, $template)) {
                continue;
            }

            // `*` arguments knowing different types print alike, so ids cannot tell inferred types apart
            if ($instance === null) {
                $instance = $bound->type;
            } elseif (!$instance->equals($bound->type, false, false)) {
                return null;
            }
        }

        if ($instance !== null) {
            return $instance;
        }

        $template_type = $template_result->template_types[$template->param_name][$template->defining_class] ?? null;

        return $template_type !== null
            && $template_type->getId() !== $template->as->getId()
            && !self::mentionsTemplate($template_type, $template)
                ? $template_type
                : null;
    }

    /**
     * @psalm-pure
     */
    private static function mentionsTemplate(Union $type, TTemplateParam $template): bool
    {
        foreach ($type->getTemplateTypes() as $mentioned) {
            if ($mentioned->param_name === $template->param_name
                && $mentioned->defining_class === $template->defining_class
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether the type mentions a template the template result binds to `*`
     *
     * @psalm-mutation-free
     */
    public static function mentionsStarTemplate(Union $type, TemplateResult $template_result): bool
    {
        if (!self::bindsStar($template_result)) {
            return false;
        }

        foreach (self::getReadTemplates($type) as $template_type) {
            if (self::getStarBinding($template_type->param_name, $template_type->defining_class, $template_result)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The templates the type reads, also through `key-of<T>`, `value-of<T>`, `properties-of<T>` and `T[K]`
     *
     * @return list<TTemplateParam>
     * @psalm-pure
     */
    private static function getReadTemplates(Union $type): array
    {
        $collector = new TemplateTypeCollector(true);
        $collector->traverseArray($type->getAtomicTypes());

        return $collector->getTemplateTypes();
    }

    /**
     * The generic object with each `*` argument knowing what it is known to be, see boundStars()
     */
    public static function boundArguments(TGenericObject $type, Codebase $codebase): TGenericObject
    {
        if (!array_any($type->type_params, self::isStar(...))
            || !$codebase->classlike_storage_provider->has($type->value)
        ) {
            return $type;
        }

        $type_params = self::boundStars(
            $type->type_params,
            $codebase->classlike_storage_provider->get($type->value),
            $codebase,
        );

        return $type_params === [] ? $type : $type->setTypeParams($type_params);
    }

    /**
     * The type arguments of a use of the class with each `*` given what is known of its type: the bound of its
     * template, expanded, with the other arguments applied (`Dependent<DateTimeImmutable, *>` with `B of A` knows `B`
     * is a DateTimeImmutable)
     *
     * @param list<Union> $type_params
     * @return list<Union>
     */
    public static function boundStars(array $type_params, ClassLikeStorage $storage, Codebase $codebase): array
    {
        $template_types = $storage->template_types ?? [];
        $template_names = array_keys($template_types);
        $bindings = [];
        $unbound_offsets = [];

        foreach ($type_params as $offset => $type_param) {
            if (!isset($template_names[$offset])) {
                break;
            }

            $bindings[$template_names[$offset]][$storage->name] = $type_param;
            $star = $type_param->isSingle() ? $type_param->getSingleAtomic() : null;

            if ($star instanceof TStarProjection && $star->as === null) {
                $unbound_offsets[] = $offset;
            }
        }

        // a bound naming its own class with a `*` (`T of Node<*>`) is expanded once
        if ($unbound_offsets === [] || isset(self::$expanding[$storage->name])) {
            return $type_params;
        }

        self::$expanding[$storage->name] = true;

        try {
            foreach ($unbound_offsets as $offset) {
                $template_name = $template_names[$offset];
                $template_type_map = $template_types[$template_name];
                $other_bindings = $bindings;
                unset($other_bindings[$template_name]);

                $type_params[$offset] = $bindings[$template_name][$storage->name] = self::create(
                    $type_params[$offset]->from_docblock,
                    TypeExpander::expandUnion(
                        $codebase,
                        TemplateInferredTypeReplacer::replace(
                            reset($template_type_map),
                            new TemplateResult([], $other_bindings),
                            $codebase,
                        ),
                        $storage->name,
                        $storage->name,
                        $storage->parent_class,
                    ),
                );
            }
        } finally {
            unset(self::$expanding[$storage->name]);
        }

        return $type_params;
    }
}
