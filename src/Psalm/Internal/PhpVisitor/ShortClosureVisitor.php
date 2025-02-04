<?php

declare(strict_types=1);

namespace Psalm\Internal\PhpVisitor;

use PhpParser;

use function is_string;

/**
 * @internal
 */
final class ShortClosureVisitor extends PhpParser\NodeVisitorAbstract
{
    /**
     * @var array<string, bool>
     */
    private array $used_variables = [];

    public function enterNode(PhpParser\Node $node): ?int
    {
        if ($node instanceof PhpParser\Node\Expr\Variable && is_string($node->name)) {
            $this->used_variables['$' . $node->name] = true;
        }

        return null;
    }

    /**
     * @return array<string, bool>
     */
    public function getUsedVariables(): array
    {
        return $this->used_variables;
    }
}
