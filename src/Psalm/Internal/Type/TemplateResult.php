<?php

declare(strict_types=1);

namespace Psalm\Internal\Type;

use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Union;

use function array_replace_recursive;

/**
 * This class captures the result of running Psalm's argument analysis with
 * regard to generic parameters.
 *
 * It captures upper and lower bounds for parameters. Mostly we just care about
 * lower bounds — those are captured when calling a function that expects a
 * non-callable templated argument.
 *
 * Upper bounds are found in callable parameter types. Given a parameter type
 * `callable(T1): void` and an argument typed as `callable(int): void`, `int` will
 * be added as an _upper_ bound for the template param `T1`. This only applies to
 * parameters — given a parameter type `callable(): T2` and an argument typed as
 * `callable(): string`, `string` will be added as a _lower_ bound for the template
 * param `T2`.
 *
 * @internal
 */
final class TemplateResult
{
    /**
     * @var array<string, array<string, non-empty-list<TemplateBound>>>
     */
    public array $lower_bounds = [];

    /**
     * @var array<string, array<string, TemplateBound>>
     */
    public array $upper_bounds = [];

    /**
     * If set to true then we shouldn't update the template bounds
     */
    public bool $readonly = false;

    /**
     * @var list<Union>
     */
    public array $upper_bounds_unintersectable_types = [];

    /**
     * The class templates the receiver bound to a `*` (template name => defining class => `true`), see
     * markStarBindings(). A template of a class the receiver extends that stands for a type wrapping them
     * (`Getter<Box<T>>`) has that type as it is read from the receiver, with the `*`, as the value.
     *
     * @var array<string, array<string, true|Union>>
     */
    public array $star_bindings = [];

    /**
     * `static` of the call when it is known before the arguments are analyzed (a template receiver or a forwarding call
     * has none) and whether it is final: the arguments are checked, and the return type is expanded, with them
     */
    public string|TNamedObject|null $static_class_type = null;

    public bool $static_class_is_final = false;

    /**
     * @param array<string, array<string, Union>> $template_types
     * @param array<string, array<string, Union>> $lower_bounds
     * @psalm-mutation-free
     */
    public function __construct(public array $template_types, array $lower_bounds)
    {
        foreach ($lower_bounds as $key1 => $boundSet) {
            foreach ($boundSet as $key2 => $bound) {
                $this->lower_bounds[$key1][$key2] = [new TemplateBound($bound)];
            }
        }
    }

    /**
     * Marks the class templates the receiver bound to a `*`, for what is read from the receiver
     *
     * @param array<string, array<string, true|Union>> $star_templates template name => defining class => mark
     * @psalm-capabilities read-props|write-this-props
     */
    public function markStarBindings(array $star_templates): void
    {
        $this->star_bindings = array_replace_recursive($this->star_bindings, $star_templates);
    }

    /**
     * The lower bounds for replacing the templates of what is read from the receiver: a template of a class it
     * extends that stands for a type wrapping a template bound to a `*` is that type with the `*` in it, where the
     * parameters (which have no star marks, see withoutStarBindings()) read the bound
     *
     * @return array<string, array<string, non-empty-list<TemplateBound>>>
     * @psalm-mutation-free
     */
    public function getLowerBoundsOfReads(): array
    {
        $lower_bounds = $this->lower_bounds;

        foreach ($this->star_bindings as $template_name => $defining_classes) {
            foreach ($defining_classes as $defining_class => $star_type) {
                if ($star_type instanceof Union) {
                    $lower_bounds[$template_name][$defining_class] = [new TemplateBound($star_type)];
                }
            }
        }

        return $lower_bounds;
    }

    /**
     * This result without the star marks, for the parameter types of a callable: they hold the bound of the template
     * (not a `*`, which would accept any argument). Calling such a callable with a value of another type is a write
     * through the `*` that is not restricted yet.
     *
     * @psalm-capabilities read-props
     */
    public function withoutStarBindings(): self
    {
        if ($this->star_bindings === []) {
            return $this;
        }

        $instance = clone $this;
        $instance->star_bindings = [];

        return $instance;
    }

    /**
     * @psalm-capabilities read-props
     */
    public function merge(TemplateResult $result): TemplateResult
    {
        if ($result === $this) {
            return $this;
        }

        $instance = clone $this;
        /** @var array<string, array<string, non-empty-list<TemplateBound>>> $lower_bounds */
        $lower_bounds = array_replace_recursive($instance->lower_bounds, $result->lower_bounds);
        $instance->lower_bounds = $lower_bounds;
        $instance->template_types = [...$instance->template_types, ...$result->template_types];
        $instance->star_bindings = array_replace_recursive($instance->star_bindings, $result->star_bindings);

        $instance->static_class_type = $result->static_class_type;
        $instance->static_class_is_final = $result->static_class_is_final;

        return $instance;
    }
}
