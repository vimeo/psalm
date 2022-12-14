<?php

namespace Psalm\Internal\Type;

use Psalm\Type\Union;

/**
 * @internal
 */
class TemplateBound
{
    /**
     * @var Union
     */
    public Union $type;

    /**
     * This is the depth at which the template appears in a given type.
     *
     * In the type Foo<T, Bar<T, array<T>>> the type T appears at three different depths.
     *
     * The shallowest-appearance of the template takes prominence when inferring the type of T.
     *
     * @var int
     */
    public int $appearance_depth;

    /**
     * The argument offset where this template was set
     *
     * In the type Foo<T, string, T> the type appears at argument offsets 0 and 2
     *
     * @var ?int
     */
    public ?int $arg_offset = null;

    /**
     * When non-null, indicates an equality template bound (vs a lower or upper bound)
     *
     * @var ?string
     */
    public ?string $equality_bound_classlike = null;

    public function __construct(
        Union $type,
        int $appearance_depth = 0,
        ?int $arg_offset = null,
        ?string $equality_bound_classlike = null
    ) {
        $this->type = $type;
        $this->appearance_depth = $appearance_depth;
        $this->arg_offset = $arg_offset;
        $this->equality_bound_classlike = $equality_bound_classlike;
    }
}
