<?php

declare(strict_types=1);

namespace Psalm\Internal\Type;

use Psalm\Codebase;
use Psalm\Internal\Analyzer\Statements\Expression\Call\ClassTemplateParamCollector;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TCallableObject;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNever;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TObjectWithProperties;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTemplateParamClass;
use Psalm\Type\Union;
use Psalm\Type\Variance;

use function array_filter;
use function array_flip;
use function array_keys;
use function array_map;
use function array_slice;
use function array_values;
use function count;
use function reset;

use const ARRAY_FILTER_USE_BOTH;

/**
 * Member projection of a receiver with call-site variance (`Box<covariant B>`, `Box<contravariant B>`, `Box<*>`):
 * what the receiver's templates stand for in a member signature depends on where they occur in it.
 *
 * - read (output) positions see what the receiver can be read as: a covariant argument, else the template's bound
 * - written (input) positions see what the receiver can be given: a contravariant argument, else `never`
 * - invariant positions see the argument
 *
 * Templates of the receiver without call-site variance are left for the usual substitution. Receivers are
 * not projected by default: whatever is not projected treats `Box<covariant B>` as `Box<B>`.
 *
 * @internal
 */
final class CallSiteVarianceProjector
{
    private const INPUT = -1;
    private const INVARIANT = 0;
    private const OUTPUT = 1;

    /** @var array<string, int> */
    private readonly array $template_offsets;

    /**
     * @param list<string> $template_names
     * @psalm-capabilities read-props
     */
    private function __construct(
        private readonly Codebase $codebase,
        private readonly TGenericObject $receiver,
        private readonly ClassLikeStorage $storage,
        private readonly array $template_names,
    ) {
        $this->template_offsets = array_flip($template_names);
    }

    /**
     * @psalm-capabilities read-props
     */
    public static function create(Codebase $codebase, ?Atomic $receiver): ?self
    {
        if (!$receiver instanceof TGenericObject
            || !$receiver->type_param_variances
            || !$codebase->classlike_storage_provider->has($receiver->value)
        ) {
            return null;
        }

        $storage = $codebase->classlike_storage_provider->get($receiver->value);

        return new self($codebase, $receiver, $storage, array_keys($storage->template_types ?? []));
    }

    /**
     * The call-site variances of a generic object, but for a `covariant` the class declares for the template
     * anyway, which is no call-site variance.
     *
     * @return array<int, Variance>
     * @psalm-capabilities read-props
     */
    public static function getCallSiteVariances(Codebase $codebase, TGenericObject $type): array
    {
        $covariants = $type->type_param_variances && $codebase->classlike_storage_provider->has($type->value)
            ? $codebase->classlike_storage_provider->get($type->value)->template_covariants
            : [];

        return array_filter(
            $type->type_param_variances,
            static fn(Variance $variance, int $i): bool => $variance !== Variance::Covariant
                || empty($covariants[$i]),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    /**
     * The receiver as what it can be read as, without call-site variance.
     *
     * @psalm-capabilities read-props|write-this-props|write-props|write-refs
     */
    public function getReadView(): TGenericObject
    {
        $params = $this->receiver->type_params;

        foreach ($this->receiver->type_param_variances as $i => $variance) {
            if ($variance !== Variance::Covariant) {
                $params[$i] = $this->getBound($this->template_names[$i] ?? null);
            }
        }

        return $this->receiver->setTypeParamVariances([])->setTypeParams(array_values($params));
    }

    /**
     * @param list<FunctionLikeParameter> $params
     * @return list<FunctionLikeParameter>
     */
    public function projectParams(array $params): array
    {
        foreach ($params as $i => $param) {
            $type = $param->type === null ? null : $this->projectUnion($param->type, self::INPUT);

            if ($param->out_type !== null) {
                $out_type = $this->projectUnion($param->out_type, self::OUTPUT);
            } else {
                // what a by-reference parameter is left holding is read, whether or not it has an out type
                $read = $param->by_ref && $param->type !== null
                    ? $this->projectUnion($param->type, self::OUTPUT)
                    : null;
                $out_type = $read === $param->type ? null : $read;
            }

            if ($type === $param->type && $out_type === $param->out_type) {
                continue;
            }

            $projected = clone $param;
            $projected->type = $type;
            $projected->out_type = $out_type;
            $params[$i] = $projected;
        }

        return $params;
    }

    /**
     * The bounds of the templates of a member, which may mention the receiver's templates: what is given to a
     * template is given to its bound, so they are in an input position.
     *
     * A template whose bound nothing can be given to (`U of T` on a `Box<covariant B>`) never takes the arguments,
     * so it is bound to its bound, read, for the result.
     */
    public function projectTemplateResult(TemplateResult $template_result): void
    {
        foreach ($template_result->template_types as $template_name => $bounds) {
            foreach ($bounds as $defining_class => $bound) {
                $input = $this->projectUnion($bound, self::INPUT);

                // seeded before the arguments are analyzed, so a closure given for `callable(U): void` is typed by
                // it; no argument can be given to such a template, so none can contradict it
                if ($input->isNever() && !$bound->isNever()) {
                    $template_result->lower_bounds[$template_name][$defining_class][] = new TemplateBound(
                        $this->projectUnion($bound, self::OUTPUT),
                    );
                }

                $template_result->template_types[$template_name][$defining_class] = $input;
            }
        }
    }

    public function project(Union $type, bool $input): Union
    {
        return $this->projectUnion($type, $input ? self::INPUT : self::OUTPUT);
    }

    public function projectAtomicType(Atomic $atomic): Atomic
    {
        $projected = $this->projectAtomic($atomic, self::OUTPUT);

        return $projected instanceof Atomic ? $projected : $atomic;
    }

    private function projectUnion(Union $union, int $polarity): Union
    {
        $atomics = [];
        $changed = false;

        foreach ($union->getAtomicTypes() as $atomic) {
            $projected = $this->projectAtomic($atomic, $polarity);

            if ($projected instanceof Union) {
                $atomics = [...$atomics, ...array_values($projected->getAtomicTypes())];
                $changed = true;
            } else {
                $atomics[] = $projected;
                $changed = $changed || $projected !== $atomic;
            }
        }

        if (!$changed) {
            return $union;
        }

        // a template standing for `never` (nothing can be given) leaves the rest of the union
        $non_never = array_filter($atomics, static fn(Atomic $atomic): bool => !$atomic instanceof TNever);

        // atomics of one key (`array<int, Cat>` and `array<string, Animal>`) are combined, not overwritten
        $by_key = [];

        foreach ($non_never ?: [$atomics[0]] as $atomic) {
            $by_key[$atomic->getKey()][] = $atomic;
        }

        $merged = [];

        foreach ($by_key as $same_key) {
            $merged = [...$merged, ...(count($same_key) === 1
                ? $same_key
                : array_values(TypeCombiner::combine($same_key, $this->codebase)->getAtomicTypes()))];
        }

        return $union->setTypes($merged)->setProperties(['from_docblock' => $union->from_docblock]);
    }

    private function projectAtomic(Atomic $atomic, int $polarity): Atomic|Union
    {
        if ($atomic instanceof TTemplateParam) {
            $projected = $this->projectTemplate($atomic, $polarity);

            if (!$atomic->extra_types) {
                return $projected;
            }

            // `T&Countable` is what the argument is, and Countable; `U&Box<T>` projects Box<T> even if U stays
            $extras = $this->projectExtras($atomic->extra_types, $polarity);

            return match (true) {
                $extras === null => Type::getNever(),
                $projected instanceof TTemplateParam => $extras === $atomic->extra_types
                    ? $projected
                    : $projected->setIntersectionTypes($extras),
                default => $this->intersect(
                    $projected instanceof Union ? $projected : new Union([$projected]),
                    $extras,
                ),
            };
        }

        if ($atomic instanceof TNamedObject && $atomic->extra_types) {
            $extras = $this->projectExtras($atomic->extra_types, $polarity);

            if ($extras === null) {
                return Type::getNever();
            }

            $atomic = $atomic->setIntersectionTypes($extras);
        }

        if ($atomic instanceof TTemplateParamClass) {
            return $this->projectTemplateClass($atomic, $polarity);
        }

        if ($atomic instanceof TGenericObject) {
            return $this->projectGenericObject($atomic, $polarity);
        }

        if ($atomic instanceof TArray || $atomic instanceof TIterable) {
            return $atomic->setTypeParams(array_map(
                fn(Union $param): Union => $this->projectUnion($param, $polarity),
                $atomic->type_params,
            ));
        }

        if ($atomic instanceof TKeyedArray) {
            $properties = array_map(
                fn(Union $property): Union => $this->projectUnion($property, $polarity),
                $atomic->properties,
            );
            $fallback_params = $atomic->fallback_params === null ? null : [
                $this->projectUnion($atomic->fallback_params[0], $polarity),
                $this->projectUnion($atomic->fallback_params[1], $polarity),
            ];

            return $properties === $atomic->properties && $fallback_params === $atomic->fallback_params
                ? $atomic
                : TKeyedArray::make(
                    $properties,
                    $atomic->class_strings,
                    $fallback_params,
                    $atomic->is_list,
                    $atomic->from_docblock,
                );
        }

        if ($atomic instanceof TCallable || $atomic instanceof TClosure) {
            return $atomic->replace(
                $atomic->params === null ? null : array_map(
                    fn(FunctionLikeParameter $param): FunctionLikeParameter => $param->type === null
                        ? $param
                        : $param->setType($this->projectUnion($param->type, -$polarity)),
                    $atomic->params,
                ),
                $atomic->return_type === null ? null : $this->projectUnion($atomic->return_type, $polarity),
            );
        }

        return $atomic;
    }

    /**
     * @param array<string, TNamedObject|TTemplateParam|TIterable|TObjectWithProperties|TCallableObject> $extras
     * @return array<string, TNamedObject|TTemplateParam|TIterable|TObjectWithProperties|TCallableObject>|null
     *         null if one of them can be nothing
     */
    private function projectExtras(array $extras, int $polarity): ?array
    {
        $projected = [];

        foreach ($extras as $extra) {
            $part = $this->projectAtomic($extra, $polarity);
            $atomics = $part instanceof Union ? array_values($part->getAtomicTypes()) : [$part];

            if ($atomics[0] instanceof TNever) {
                return null;
            }

            // an intersection cannot hold a union (`Countable&T` with `T of A|B` read as its bound), so such a
            // member keeps its template unresolved, as it also does on an invariant receiver
            $new_extra = count($atomics) === 1
                && ($atomics[0] instanceof TNamedObject
                    || $atomics[0] instanceof TTemplateParam
                    || $atomics[0] instanceof TIterable
                    || $atomics[0] instanceof TObjectWithProperties
                    || $atomics[0] instanceof TCallableObject)
                ? $atomics[0]
                : $extra;
            $projected[$new_extra->getKey()] = $new_extra;
        }

        return $projected;
    }

    /**
     * @param array<string, TNamedObject|TTemplateParam|TIterable|TObjectWithProperties|TCallableObject> $extras
     * @psalm-capabilities read-props
     */
    private function intersect(Union $union, array $extras): Union
    {
        $atomics = [];
        $main = reset($extras);

        foreach ($union->getAtomicTypes() as $atomic) {
            if ($atomic instanceof TNamedObject
                || $atomic instanceof TTemplateParam
                || $atomic instanceof TIterable
                || $atomic instanceof TObjectWithProperties
            ) {
                $atomic = $atomic->setIntersectionTypes([...$atomic->extra_types, ...$extras]);
            } elseif (($atomic instanceof TMixed || $atomic instanceof TObject) && $main) {
                $atomic = $main->setIntersectionTypes([...$main->extra_types, ...array_slice($extras, 1)]);
            }

            // as with an invariant receiver, what cannot be intersected (`array<int>&Countable`) is kept as is
            $atomics[] = $atomic;
        }

        return $union->setTypes($atomics);
    }

    private function projectTemplate(TTemplateParam $template, int $polarity): Atomic|Union
    {
        if ($template->defining_class === $this->storage->name) {
            $argument = $this->getCallSiteArgument($template->param_name);

            if ($argument === null) {
                return $template;
            }

            [$variance, $type] = $argument;

            return match (true) {
                $polarity === self::OUTPUT && $variance !== Variance::Covariant
                    => $this->getBound($template->param_name),
                $polarity === self::INPUT && $variance !== Variance::Contravariant => Type::getNever(),
                default => $type,
            };
        }

        // a template of an ancestor stands for what the receiver's `@extends` gives it
        $expanded = $this->getAncestorArgument($template->param_name, $template->defining_class);

        if ($expanded !== null) {
            foreach ($expanded->getTemplateTypes() as $nested) {
                if ($nested->defining_class === $this->storage->name
                    && $this->getVariance($nested->param_name) !== null
                ) {
                    return $this->projectUnion($expanded, $polarity);
                }
            }

            return $template;
        }

        // nothing can be given to a template whose bound is `never`
        $as = $this->projectUnion($template->as, $polarity);

        return match (true) {
            $as === $template->as => $template,
            $polarity === self::INPUT && $as->isNever() => Type::getNever(),
            default => $template->replaceAs($as),
        };
    }

    private function projectTemplateClass(TTemplateParamClass $atomic, int $polarity): Atomic|Union
    {
        $template = $this->resolveReceiverTemplate($atomic->param_name, $atomic->defining_class);
        $variance = $template === null ? null : $this->getVariance($template);

        if ($template === null || $variance === null) {
            return $atomic;
        }

        if ($polarity === self::INPUT && $variance !== Variance::Contravariant) {
            return Type::getNever();
        }

        if ($polarity !== self::OUTPUT || $variance === Variance::Covariant) {
            return $atomic;
        }

        $class_strings = [];

        foreach ($this->getBound($template)->getAtomicTypes() as $bound) {
            if (!$bound instanceof TNamedObject) {
                return new Union([new TClassString()]);
            }

            $class_strings[] = new TClassString($bound->value, $bound);
        }

        return new Union($class_strings);
    }

    /**
     * What the receiver's `@extends` gives a template of an ancestor, in terms of the receiver's templates.
     */
    private function getAncestorArgument(string $template_name, string $defining_class): ?Union
    {
        $extended = $this->storage->template_extended_params[$defining_class][$template_name] ?? null;

        return $extended === null ? null : new Union(ClassTemplateParamCollector::expandType(
            $this->codebase,
            $extended,
            $this->storage->template_extended_params ?? [],
            $this->storage->name,
            $this->storage->template_types,
        ));
    }

    /**
     * The template of the receiver's class that a template stands for, if it is just that.
     */
    private function resolveReceiverTemplate(string $template_name, string $defining_class): ?string
    {
        if ($defining_class === $this->storage->name) {
            return $template_name;
        }

        $argument = $this->getAncestorArgument($template_name, $defining_class);
        $template = $argument?->isSingle() ? $argument->getSingleAtomic() : null;

        return $template instanceof TTemplateParam && $template->defining_class === $this->storage->name
            ? $template->param_name
            : null;
    }

    private function projectGenericObject(TGenericObject $atomic, int $polarity): TGenericObject
    {
        $declared_covariants = $this->codebase->classlike_storage_provider->has($atomic->value)
            ? $this->codebase->classlike_storage_provider->get($atomic->value)->template_covariants
            : [];
        $params = $atomic->type_params;
        $variances = $atomic->type_param_variances;

        foreach ($params as $i => $param) {
            $effective = $variances[$i] ?? (($declared_covariants[$i] ?? false) ? Variance::Covariant : null);

            $carried = null;

            if ($effective === null
                && $polarity === self::OUTPUT
                && $param->isSingle()
                && ($template = $param->getSingleAtomic()) instanceof TTemplateParam
                && ($receiver_template = $this->resolveReceiverTemplate(
                    $template->param_name,
                    $template->defining_class,
                )) !== null
            ) {
                $carried = $this->getCallSiteArgument($receiver_template);
            }

            if ($carried !== null) {
                // `Box<T>` read from `Box<covariant B>` is `Box<covariant B>`: it is not known to hold a B
                [$variances[$i], $params[$i]] = $carried;
            } else {
                $params[$i] = $this->projectUnion($param, match ($effective) {
                    Variance::Covariant => $polarity,
                    Variance::Contravariant => -$polarity,
                    default => self::INVARIANT,
                });
            }
        }

        return $atomic->setTypeParams($params)->setTypeParamVariances($variances);
    }

    /**
     * @psalm-capabilities read-props
     */
    private function getVariance(string $template_name): ?Variance
    {
        $offset = $this->template_offsets[$template_name] ?? null;

        return $offset === null ? null : $this->receiver->type_param_variances[$offset] ?? null;
    }

    /**
     * The call-site variance and argument the receiver gives a template of its class, if it gives it a variance.
     *
     * @return array{Variance, Union}|null
     * @psalm-capabilities read-props
     */
    private function getCallSiteArgument(string $template_name): ?array
    {
        $variance = $this->getVariance($template_name);
        $type = $this->receiver->type_params[$this->template_offsets[$template_name] ?? -1] ?? null;

        return $variance === null || $type === null ? null : [$variance, $type];
    }

    /**
     * The bound of a template of the receiver's class, in terms of the receiver's arguments.
     *
     * @psalm-capabilities read-props|write-this-props|write-props|write-refs
     */
    private function getBound(?string $template_name): Union
    {
        $bounds = $template_name === null ? null : $this->storage->template_types[$template_name] ?? null;

        if ($bounds === null) {
            return Type::getMixed();
        }

        $bound = reset($bounds);

        if (!$bound->hasTemplate()) {
            return $bound;
        }

        $arguments = [];

        foreach ($this->template_names as $i => $name) {
            if (isset($this->receiver->type_params[$i])) {
                $arguments[$name][$this->storage->name] = $this->receiver->type_params[$i];
            }
        }

        return TemplateInferredTypeReplacer::replace($bound, new TemplateResult([], $arguments), $this->codebase);
    }
}
