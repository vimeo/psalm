<?php

declare(strict_types=1);

namespace Psalm\Internal\PhpVisitor;

use Override;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * Visitor cloning all nodes and linking to the original nodes using an attribute.
 *
 * This visitor is required to perform format-preserving pretty prints.
 *
 * @internal
 */
final class CloningVisitor extends NodeVisitorAbstract
{
    #[Override]
    public function enterNode(Node $node): Node
    {
        $node = clone $node;

        if (($cs = $node->getComments()) !== []) {
            $comments = [];
            foreach ($cs as $i => $comment) {
                $comments[$i] = clone $comment;
            }

            $node->setAttribute('comments', $comments);
        }

        return $node;
    }
}
