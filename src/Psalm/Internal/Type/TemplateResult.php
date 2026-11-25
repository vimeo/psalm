<?php

declare(strict_types=1);

namespace Psalm\Internal\Type;

use Psalm\Type;
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

    /** Declared return type of the callable whose arguments are being inferred. */
    public ?Union $conditional_return_context = null;

    /**
     * The bindings are the type arguments of the receiver of a call, which replace the templates of the declared
     * parameter types, even in code of the receiver's class. The argument types stay rigid.
     */
    public bool $of_receiver = false;

    /**
     * The class a `new` expression constructs: its templates stand for the new object's type arguments, even in
     * code of that class, where they are otherwise the types of the object the code runs for
     */
    public ?string $constructed_class = null;

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
     * What a template of star_bindings that stands for a type wrapping a template bound to a `*` is where the types
     * are replaced as inputs (a parameter, an assigned value): that type with `never` for the `*`
     *
     * @var array<string, array<string, Union>>
     */
    public array $star_input_bounds = [];

    /**
     * Whether the types are replaced as inputs: a template bound to a `*` is `never` there, as no value is known to
     * be of the unknown type. It is flipped inside the parameters of a callable.
     */
    public bool $star_input = false;

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
     * Whether the bindings are those of another object than the one code of $class runs for: the receiver of a
     * call or the object a `new` of $class constructs
     *
     * @psalm-mutation-free
     */
    public function isForAnotherObject(string $class): bool
    {
        return $this->of_receiver || $this->constructed_class === $class;
    }

    /**
     * Marks the class templates the receiver bound to a `*`, for what is read from and written to the receiver
     *
     * @param array<string, array<string, true|Union>> $star_templates template name => defining class => mark
     * @param array<string, array<string, Union>> $input_bounds what the marks that are types are as inputs
     * @psalm-capabilities read-props|write-this-props
     */
    public function markStarBindings(array $star_templates, array $input_bounds = []): void
    {
        $this->star_bindings = array_replace_recursive($this->star_bindings, $star_templates);
        $this->star_input_bounds = array_replace_recursive($this->star_input_bounds, $input_bounds);
    }

    /**
     * The lower bounds for replacing the templates of what is read from the receiver: a template of a class it
     * extends that stands for a type wrapping a template bound to a `*` is that type with the `*` in it. For what is
     * written to the receiver, every template bound to a `*` is `never` (or the type wrapping it with `never` in it).
     *
     * @return array<string, array<string, non-empty-list<TemplateBound>>>
     * @psalm-mutation-free
     */
    public function getLowerBounds(): array
    {
        $lower_bounds = $this->lower_bounds;

        foreach ($this->star_bindings as $template_name => $defining_classes) {
            foreach ($defining_classes as $defining_class => $star_type) {
                if ($this->star_input) {
                    $lower_bounds[$template_name][$defining_class] = [new TemplateBound(
                        $this->getStarInputBound($template_name, $defining_class),
                    )];
                } elseif ($star_type instanceof Union) {
                    $lower_bounds[$template_name][$defining_class] = [new TemplateBound($star_type)];
                }
            }
        }

        return $lower_bounds;
    }

    /**
     * Whether a template is bound to a `*` where the types are replaced as inputs
     *
     * @psalm-mutation-free
     */
    public function isStarInput(string $template_name, string $defining_class): bool
    {
        return $this->star_input && isset($this->star_bindings[$template_name][$defining_class]);
    }

    /**
     * What a template bound to a `*` is where the types are replaced as inputs
     *
     * @psalm-mutation-free
     */
    public function getStarInputBound(string $template_name, string $defining_class): Union
    {
        return $this->star_input_bounds[$template_name][$defining_class] ?? Type::getNever();
    }

    /**
     * Only the star marks of this result, for replacing the templates of the types of a parameter
     *
     * @psalm-capabilities read-props
     */
    public function getStarInputs(): self
    {
        $instance = new self([], []);
        $instance->markStarBindings($this->star_bindings, $this->star_input_bounds);
        $instance->star_input = true;

        return $instance;
    }

    /**
     * This result for the other side of a type: the parameters of a callable are inputs of what is an output (the
     * closure of a getter takes what the getter returns as its argument), and the other way round
     *
     * @psalm-capabilities read-props
     */
    public function flipStarPosition(): self
    {
        return $this->withStarPosition(!$this->star_input);
    }

    /**
     * This result for the types of a parameter or of an assigned value (`$input`) or of what is read
     *
     * @psalm-capabilities read-props
     */
    public function withStarPosition(bool $input): self
    {
        if ($this->star_bindings === [] || $this->star_input === $input) {
            return $this;
        }

        $instance = clone $this;
        $instance->star_input = $input;

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
        $instance->star_input_bounds = array_replace_recursive(
            $instance->star_input_bounds,
            $result->star_input_bounds,
        );

        $instance->static_class_type = $result->static_class_type;
        $instance->static_class_is_final = $result->static_class_is_final;

        return $instance;
    }
}
