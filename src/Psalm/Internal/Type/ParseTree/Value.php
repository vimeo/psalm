<?php
namespace Psalm\Internal\Type\ParseTree;

/**
 * @internal
 */
class Value extends \Psalm\Internal\Type\ParseTree
{
    /**
     * @var string
     */
    public $value;

    /**
     * @var int
     */
    public $offset;

    /**
     * @param string $value
     * @param \Psalm\Internal\Type\ParseTree|null $parent
     */
    public function __construct(string $value, int $offset, \Psalm\Internal\Type\ParseTree $parent = null)
    {
        $this->offset = $offset;
        $this->value = $value;
        $this->parent = $parent;
    }
}
