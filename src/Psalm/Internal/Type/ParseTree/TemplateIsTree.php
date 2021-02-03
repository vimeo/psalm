<?php
namespace Psalm\Internal\Type\ParseTree;

use Psalm\Internal\Type\ParseTree;

/**
 * @internal
 */
class TemplateIsTree extends ParseTree
{
    /**
     * @var string
     */
    public $param_name;

    /**
     * @var bool
     */
    public $inverse;

    public function __construct(string $param_name, bool $inverse = false, ?ParseTree $parent = null)
    {
        $this->param_name = $param_name;
        $this->inverse = $inverse;
        $this->parent = $parent;
    }
}
