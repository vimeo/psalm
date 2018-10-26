<?php declare(strict_types=1);

namespace Psalm\Visitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * Visitor cloning all nodes and linking to the original nodes using an attribute.
 *
 * This visitor is required to perform format-preserving pretty prints.
 */
class CloningVisitor extends NodeVisitorAbstract
{
    public function enterNode(Node $origNode)
    {
        $node = clone $origNode;
        if ($c = $node->getDocComment()) {
            $node->setDocComment(clone $c);
        }
        return $node;
    }
}
