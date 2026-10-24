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
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNever;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTemplateParamClass;
use Psalm\Type\Union;
use Psalm\Type\Variance;

use function array_filter;
use function array_keys;
use function array_map;
use function array_search;
use function array_values;
use function reset;

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

    /**
     * @param list<string> $template_names
     */
    private function __construct(
        private readonly Codebase $codebase,
        private readonly TGenericObject $receiver,
        private readonly ClassLikeStorage $storage,
        private readonly array $template_names,
    ) {
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
            if ($param->type === null && $param->out_type === null) {
                continue;
            }

            $projected = clone $param;
            $projected->type = $param->type === null ? null : $this->projectUnion($param->type, self::INPUT);
            // what a by-reference parameter is left holding is read, whether or not it has an out type
            $out_type = $param->out_type ?? ($param->by_ref ? $param->type : null);
            $projected_out_type = $out_type === null ? null : $this->projectUnion($out_type, self::OUTPUT);
            $projected->out_type = $projected_out_type === $param->type && $param->out_type === null
                ? null
                : $projected_out_type;
            $params[$i] = $projected;
        }

        return $params;
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

        return $union->setTypes($non_never ?: [$atomics[0]]);
    }

    private function projectAtomic(Atomic $atomic, int $polarity): Atomic|Union
    {
        if ($atomic instanceof TTemplateParam) {
            return $this->projectTemplate($atomic, $polarity);
        }

        if ($atomic instanceof TTemplateParamClass) {
            $template = $this->resolveReceiverTemplate($atomic->param_name, $atomic->defining_class);
            $variance = $template === null ? null : $this->getVariance($template);

            if ($variance === null) {
                return $atomic;
            }

            if ($polarity === self::INPUT && $variance !== Variance::Contravariant) {
                return Type::getNever();
            }

            if ($polarity === self::OUTPUT && $variance !== Variance::Covariant) {
                $class_strings = [];

                foreach ($this->getBound($template)->getAtomicTypes() as $bound) {
                    if (!$bound instanceof TNamedObject) {
                        return new Union([new TClassString()]);
                    }

                    $class_strings[] = new TClassString($bound->value, $bound);
                }

                return new Union($class_strings);
            }

            return $atomic;
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

    private function projectTemplate(TTemplateParam $template, int $polarity): Atomic|Union
    {
        if ($template->defining_class === $this->storage->name) {
            $variance = $this->getVariance($template->param_name);
            $offset = array_search($template->param_name, $this->template_names, true);

            if ($variance === null || $offset === false || !isset($this->receiver->type_params[$offset])) {
                return $template;
            }

            return match (true) {
                $polarity === self::OUTPUT && $variance !== Variance::Covariant
                    => $this->getBound($template->param_name),
                $polarity === self::INPUT && $variance !== Variance::Contravariant => Type::getNever(),
                default => $this->receiver->type_params[$offset],
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

        // the bound of a template of the member may mention the receiver's: nothing can be given to a template
        // whose bound is `never`
        $as = $this->projectUnion($template->as, $polarity);

        return match (true) {
            $as === $template->as => $template,
            $polarity === self::INPUT && $as->isNever() => Type::getNever(),
            default => $template->replaceAs($as),
        };
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

            $argument_polarity = match ($effective) {
                Variance::Covariant => $polarity,
                Variance::Contravariant => -$polarity,
                default => self::INVARIANT,
            };

            $carried = null;
            $offset = false;

            if ($effective === null
                && $polarity === self::OUTPUT
                && $param->isSingle()
                && ($template = $param->getSingleAtomic()) instanceof TTemplateParam
                && ($receiver_template = $this->resolveReceiverTemplate(
                    $template->param_name,
                    $template->defining_class,
                )) !== null
            ) {
                $carried = $this->getVariance($receiver_template);
                $offset = array_search($receiver_template, $this->template_names, true);
            }

            if ($carried !== null && $offset !== false && isset($this->receiver->type_params[$offset])) {
                // `Box<T>` read from `Box<covariant B>` is `Box<covariant B>`: it is not known to hold a B
                $variances[$i] = $carried;
                $params[$i] = $this->receiver->type_params[$offset];
            } else {
                $params[$i] = $this->projectUnion($param, $argument_polarity);
            }
        }

        return $atomic->setTypeParams($params)->setTypeParamVariances($variances);
    }

    /**
     * @psalm-capabilities read-props
     */
    private function getVariance(string $template_name): ?Variance
    {
        $offset = array_search($template_name, $this->template_names, true);

        return $offset === false ? null : $this->receiver->type_param_variances[$offset] ?? null;
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
