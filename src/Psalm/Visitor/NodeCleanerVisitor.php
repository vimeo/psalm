<?php
namespace Psalm\Visitor;

use PhpParser;
use Psalm\Checker\Statements\ExpressionChecker;

class NodeCleanerVisitor extends PhpParser\NodeVisitorAbstract implements PhpParser\NodeVisitor
{
    /**
     * @param  PhpParser\Node $node
     *
     * @return null|int
     */
    public function enterNode(PhpParser\Node $node)
    {
        /** @psalm-suppress NoInterfaceProperties */
        unset($node->inferredType, $node->assertions);

        return null;
    }
}
