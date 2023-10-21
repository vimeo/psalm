<?php

declare(strict_types=1);

namespace Psalm\Internal\Type;

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
    ) {
    }
}
