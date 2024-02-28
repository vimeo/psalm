<?php

namespace Psalm\Internal\Type;

use Psalm\Type\Union;

use function array_merge;
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
     * @var array<string, array<string, Union>>
     */
    public array $template_types;

    /**
     * @var array<string, array<string, non-empty-list<TemplateBound>>>
     */
    public array $lower_bounds;

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
     * @param  array<string, array<string, Union>> $template_types
     * @param  array<string, array<string, Union>> $lower_bounds
     */
    public function __construct(array $template_types, array $lower_bounds)
    {
        $this->template_types = $template_types;
        $this->lower_bounds = [];

        foreach ($lower_bounds as $key1 => $boundSet) {
            foreach ($boundSet as $key2 => $bound) {
                $this->lower_bounds[$key1][$key2] = [new TemplateBound($bound)];
            }
        }
    }

    public function merge(TemplateResult $result): TemplateResult
    {
        if ($result === $this) {
            return $this;
        }

        $instance = clone $this;
        /** @var array<string, array<string, non-empty-list<TemplateBound>>> $lower_bounds */
        $lower_bounds = array_replace_recursive($instance->lower_bounds, $result->lower_bounds);
        $instance->lower_bounds = $lower_bounds;
        $instance->template_types = array_merge($instance->template_types, $result->template_types);

        return $instance;
    }
}
