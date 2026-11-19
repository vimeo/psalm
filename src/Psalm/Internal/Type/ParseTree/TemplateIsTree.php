<?php

declare(strict_types=1);

namespace Psalm\Internal\Type\ParseTree;

use Psalm\Internal\Type\ParseTree;

/**
 * @internal
 */
final class TemplateIsTree extends ParseTree
{
    /** Whether the condition is negated, as in `T is not X` */
    public bool $negated = false;

    /** The type the condition is on when it isn't a template, as `value-of<T>` in `value-of<T> is string` */
    public ?ParseTree $subject = null;

    /**
     * @psalm-mutation-free
     */
    public function __construct(public string $param_name, ?ParseTree $parent = null)
    {
        $this->parent = $parent;
    }
}
