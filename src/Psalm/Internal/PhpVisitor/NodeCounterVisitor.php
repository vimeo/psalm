<?php

declare(strict_types=1);

namespace Psalm\Internal\PhpVisitor;

use Override;
use PhpParser;

/**
 * @internal
 */
final class NodeCounterVisitor extends PhpParser\NodeVisitorAbstract
{
    public int $count = 0;

    #[Override]
    public function enterNode(PhpParser\Node $node): ?int
    {
        $this->count++;

        return null;
    }
}
