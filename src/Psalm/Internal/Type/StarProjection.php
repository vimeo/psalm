<?php

declare(strict_types=1);

namespace Psalm\Internal\Type;

use Psalm\Codebase;
use Psalm\Internal\TypeVisitor\ContainsAtomicVisitor;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TObjectWithProperties;
use Psalm\Type\Atomic\TStarProjection;
use Psalm\Type\Atomic\TTemplateIndexedAccess;
use Psalm\Type\Atomic\TTemplateKeyOf;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTemplateParamClass;
use Psalm\Type\Atomic\TTemplatePropertiesOf;
use Psalm\Type\Atomic\TTemplateValueOf;
use Psalm\Type\Union;

use function array_keys;
use function array_values;
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
     * @psalm-pure
     */
    public static function create(bool $from_docblock, ?Union $as = null): Union
    {
        return new Union([new TStarProjection($as, $from_docblock)], ['from_docblock' => $from_docblock]);
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
        if ($atomic_type instanceof TTemplateParam
            && isset($template_result->lower_bounds[$atomic_type->param_name][$atomic_type->defining_class])
        ) {
            $bound = TemplateStandinTypeReplacer::getMostSpecificTypeFromBounds(
                $template_result->lower_bounds[$atomic_type->param_name][$atomic_type->defining_class],
                $codebase,
            );

            return self::readOrWrite($bound, $atomic_type->as, !$written);
        }

        // a generic object is read here too: an invariant argument mentioning such a template is `*` knowing its read
        if (!self::mentionsStarTemplate(new Union([$atomic_type]), $template_result)
            || !self::contains($atomic_type, [TClosure::class, TCallable::class, TGenericObject::class])
        ) {
            return self::replaceTemplates($atomic_type, $template_result, $written, $codebase);
        }

        // the parts below read no intersected types (`Box<T>&Holder<T>`)
        if (($atomic_type instanceof TNamedObject
                || $atomic_type instanceof TIterable
                || $atomic_type instanceof TObjectWithProperties)
            && $atomic_type->extra_types
        ) {
            return self::contains($atomic_type, [TClosure::class, TCallable::class])
                ? null
                : self::replaceTemplates($atomic_type, $template_result, $written, $codebase);
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
    private static function contains(Atomic|Union $type, array $atomic_classes): bool
    {
        $finder = new ContainsAtomicVisitor($atomic_classes);
        $finder->traverse($type);

        return $finder->matches();
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
    public static function readAs(Union $argument, ?Union $bound = null): Union
    {
        if (!$argument->isSingle()) {
            return $argument;
        }

        $star = $argument->getSingleAtomic();

        if (!$star instanceof TStarProjection) {
            return $argument;
        }

        return $star->as ?? $bound ?? Type::getMixed();
    }

    /**
     * What a template bound to $argument stands for where it is read ($read) or written: a `*` stands for what is
     * known of it where a value is handed out (the parameters of a callable parameter), and accepts nothing where
     * one is passed in
     *
     * @psalm-pure
     */
    public static function readOrWrite(Union $argument, Union $bound, bool $read): Union
    {
        if ($read) {
            return self::readAs($argument, $bound);
        }

        return self::isStar($argument) ? Type::getNever() : $argument;
    }

    /**
     * @psalm-mutation-free
     */
    public static function isTemplateBoundToStar(
        TTemplateParam|TTemplateParamClass $type,
        TemplateResult $template_result,
    ): bool {
        return self::getStarBinding($type->param_name, $type->defining_class, $template_result) !== null;
    }

    /**
     * Whether reading the template gives a `*` known to be nothing more than mixed
     *
     * @psalm-mutation-free
     */
    public static function readsUnknownMixed(TTemplateParam $type, TemplateResult $template_result): bool
    {
        $star = self::getStarBinding($type->param_name, $type->defining_class, $template_result);

        return $star !== null && self::readAs($star, $type->as)->isMixed();
    }

    /**
     * Whether the type is a template the template result binds to `*`, or is read from one (`key-of<TArr>`,
     * `TArray[TKey]`)
     *
     * @psalm-mutation-free
     */
    public static function readsTemplateBoundToStar(Atomic $type, TemplateResult $template_result): bool
    {
        if ($type instanceof TTemplateIndexedAccess) {
            return self::getStarBinding($type->array_param_name, $type->defining_class, $template_result) !== null
                || self::getStarBinding($type->offset_param_name, $type->defining_class, $template_result) !== null;
        }

        return ($type instanceof TTemplateParam
                || $type instanceof TTemplateParamClass
                || $type instanceof TTemplateKeyOf
                || $type instanceof TTemplateValueOf
                || $type instanceof TTemplatePropertiesOf)
            && self::getStarBinding($type->param_name, $type->defining_class, $template_result) !== null;
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
     * `Box<Box<TSecond>>` are `Box<*>`). Null when the argument is covariant, mentions no such template, or is a `*`
     * already (the template replacer reads what is known of it). Without a template result, the `*` are values of
     * the type already (a type a return type provider built from the receiver's arguments) and the argument
     * mentions one by containing it.
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
            || !self::mentionsStar($type_param, $template_result)
            || !self::isInvariantArgument($type, $offset, $codebase)
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
     * position) or another template in it is not instantiated yet.
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

        foreach ($type_param->getTemplateTypes() as $template) {
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
                $read = [...$read, ...array_values(self::readAs($instance, $atomic_type->as)->getAtomicTypes())];
            } else {
                $read[] = $atomic_type->replaceTemplateTypesWithArgTypes($instance_result, $codebase);
            }
        }

        $read_type = new Union($read);

        foreach ($read_type->getTemplateTypes() as $template) {
            if (isset($template_result->template_types[$template->param_name][$template->defining_class])
                || isset($template_result->lower_bounds[$template->param_name][$template->defining_class])
            ) {
                return $projected;
            }
        }

        return self::create($type_param->from_docblock, $read_type);
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
     * @psalm-mutation-free
     */
    private static function mentionsStar(Union $type, ?TemplateResult $template_result): bool
    {
        return $template_result === null
            ? self::contains($type, [TStarProjection::class])
            : self::mentionsStarTemplate($type, $template_result);
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

        foreach ($type->getTemplateTypes() as $template_type) {
            if (self::isTemplateBoundToStar($template_type, $template_result)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The type arguments of a use of the class with each `*` given what is known of its type: the bound of its
     * template, with the other arguments applied (`Dependent<DateTimeImmutable, *>` with `B of A` knows `B` is a
     * DateTimeImmutable)
     *
     * @param list<Union> $type_params
     * @return list<Union>
     */
    public static function boundStars(array $type_params, ClassLikeStorage $storage, Codebase $codebase): array
    {
        $template_names = array_keys($storage->template_types ?? []);
        $bindings = [];
        $has_unbound_star = false;

        foreach ($type_params as $offset => $type_param) {
            if (!isset($template_names[$offset])) {
                break;
            }

            $bindings[$template_names[$offset]][$storage->name] = $type_param;
            $has_unbound_star = $has_unbound_star || self::getUnboundStar($type_param) !== null;
        }

        if (!$has_unbound_star) {
            return $type_params;
        }

        foreach ($type_params as $offset => $type_param) {
            $template_name = $template_names[$offset] ?? null;
            $template_type_map = $template_name !== null ? $storage->template_types[$template_name] ?? null : null;

            if ($template_name === null || $template_type_map === null || self::getUnboundStar($type_param) === null) {
                continue;
            }

            $other_bindings = $bindings;
            unset($other_bindings[$template_name]);

            $type_params[$offset] = $bindings[$template_name][$storage->name] = self::create(
                $type_param->from_docblock,
                TemplateInferredTypeReplacer::replace(
                    reset($template_type_map),
                    new TemplateResult([], $other_bindings),
                    $codebase,
                ),
            );
        }

        return $type_params;
    }

    /**
     * @psalm-pure
     */
    private static function getUnboundStar(Union $type): ?TStarProjection
    {
        $star = $type->isSingle() ? $type->getSingleAtomic() : null;

        return $star instanceof TStarProjection && $star->as === null ? $star : null;
    }

    /**
     * Whether the type argument at $offset of the generic object binds an invariant template
     * (an unknown class counts as invariant)
     *
     * @psalm-mutation-free
     */
    public static function isInvariantArgument(TGenericObject $type, int $offset, ?Codebase $codebase): bool
    {
        if ($codebase === null || !$codebase->classlike_storage_provider->has($type->value)) {
            return true;
        }

        return !($codebase->classlike_storage_provider->get($type->value)->template_covariants[$offset] ?? false);
    }
}
