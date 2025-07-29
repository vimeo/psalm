<?php

declare(strict_types=1);

namespace Psalm\Internal\PhpVisitor;

use Override;
use PhpParser;
use Psalm\Internal\Provider\NodeDataProvider;

/**
 * @internal
 */
final class NodeCleanerVisitor extends PhpParser\NodeVisitorAbstract
{
    public function __construct(
        private readonly NodeDataProvider $type_provider,
    ) {
    }

    #[Override]
    public function enterNode(PhpParser\Node $node): ?int
    {
        if ($node instanceof PhpParser\Node\Expr) {
            $this->type_provider->clearNodeOfTypeAndAssertions($node);
        }

        return null;
    }
}
