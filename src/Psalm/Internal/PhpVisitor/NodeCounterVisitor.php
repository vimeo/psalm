<?php

declare(strict_types=1);

namespace Psalm\Internal\PhpVisitor;

use PhpParser;

/**
 * @internal
 */
final class NodeCounterVisitor extends PhpParser\NodeVisitorAbstract
{
    public int $count = 0;

    public function enterNode(PhpParser\Node $node): ?int
    {
        $this->count++;

        return null;
    }
}
