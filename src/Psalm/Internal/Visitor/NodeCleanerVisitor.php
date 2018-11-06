<?php
namespace Psalm\Internal\Visitor;

use PhpParser;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;

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
