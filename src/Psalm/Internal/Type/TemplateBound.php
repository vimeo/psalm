<?php

declare(strict_types=1);

namespace Psalm\Internal\Type;

use Psalm\CodeLocation;
use Psalm\Type\Union;

/**
 * @internal
 */
final class TemplateBound
{
    /**
     * @psalm-mutation-free
     */
    public function __construct(
        public Union $type,
        /**
         * This is the depth at which the template appears in a given type.
         *
         * In the type Foo<T, Bar<T, array<T>>> the type T appears at three different depths.
         *
         * The shallowest-appearance of the template takes prominence when inferring the type of T.
         */
        public int $appearance_depth = 0,
        /**
         * The argument offset where this template was set
         *
         * In the type Foo<T, string, T> the type appears at argument offsets 0 and 2
         */
        public ?int $arg_offset = null,
        /**
         * When non-null, indicates an equality template bound (vs a lower or upper bound)
         */
        public ?string $equality_bound_classlike = null,
        /**
         * Where the bound was recorded, when it constrains a type variable
         */
        public ?CodeLocation $pos = null,
    ) {
    }

    /**
     * True for a lower bound that only mirrors an invariant argument's upper
     * bound (e.g. `Box<`_0>` passed to `Box<string>`): a requirement, not a
     * value the variable holds, so reconciliation ignores it as content.
     */
    public bool $from_invariant_argument_mirror = false;

    /**
     * True for an upper bound imposed by an argument position. Failures against
     * it are reported at the call site, not the construction site.
     */
    public bool $from_argument_requirement = false;

    /**
     * True for a lower bound that is only the template's own constraint,
     * standing in for an argument that did not bind the template (it was
     * rejected, and reported, as invalid): a placeholder shape, not a value
     * the template is known to hold, so reconciliation does not check it.
     */
    public bool $from_constraint_fallback = false;

    /**
     * True for an upper bound imposed by a declared return type on a returned
     * value.
     */
    public bool $from_return_requirement = false;

    /**
     * The id of the property (`Foo::$bar`) whose declared type imposed this
     * upper bound on an assigned value; null for bounds recorded elsewhere.
     */
    public ?string $property_requirement_id = null;

    /**
     * The issues suppressed where this bound was recorded, when it constrains
     * a type variable: an issue raised against the bound at reconciliation
     * honours them like the eagerly reported issue would have.
     *
     * @var array<string>
     */
    public array $suppressed_issues = [];
}
