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
}
