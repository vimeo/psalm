<?php
namespace Psalm\Internal\PhpVisitor;

use PhpParser;

/**
 * @internal
 */
class NodeCounterVisitor extends PhpParser\NodeVisitorAbstract implements PhpParser\NodeVisitor
{
    /** @var int */
    public $count = 0;

    /**
     * @return null|int
     */
    public function enterNode(PhpParser\Node $node)
    {
        $this->count++;
    }
}
