<?php

declare(strict_types=1);

namespace Psalm\Internal\Type\ParseTree;

/**
 * @internal
 */
class TemplateIsTree extends \Psalm\Internal\Type\ParseTree
{
    /**
     * @var string
     */
    public $param_name;

    public function __construct(string $param_name, ?\Psalm\Internal\Type\ParseTree $parent = null)
    {
        $this->param_name = $param_name;
        $this->parent = $parent;
    }
}
