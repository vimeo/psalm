<?php
namespace Psalm\Internal\Type\ParseTree;

/**
 * @internal
 */
class ConstrainedTypeTree extends \Psalm\Internal\Type\ParseTree
{
    /**
     * @var string
     */
    public $type;

    public function __construct(
        string $type,
        ?\Psalm\Internal\Type\ParseTree $parent = null
    ) {
        $this->type = $type;
        $this->parent = $parent;
    }
}
