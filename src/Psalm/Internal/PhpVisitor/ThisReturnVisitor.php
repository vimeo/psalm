<?php

declare(strict_types=1);

namespace Psalm\Internal\PhpVisitor;

use Override;
use PhpParser;
use PhpParser\Node\Expr\Variable;

/**
 * Finds whether a function-like body always returns `$this`: it has a return, every return is
 * `return $this;` and it yields nothing. Closures and classes declared in the body are skipped.
 *
 * @internal
 */
final class ThisReturnVisitor extends PhpParser\NodeVisitorAbstract
{
    private bool $has_return = false;

    private bool $returns_only_this = true;

    /**
     * @param array<PhpParser\Node\Stmt> $stmts
     */
    public static function returnsOnlyThis(array $stmts): bool
    {
        $visitor = new self();

        $traverser = new PhpParser\NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($stmts);

        return $visitor->has_return && $visitor->returns_only_this;
    }

    /**
     * @psalm-external-mutation-free
     */
    #[Override]
    public function enterNode(PhpParser\Node $node): ?int
    {
        if ($node instanceof PhpParser\Node\FunctionLike || $node instanceof PhpParser\Node\Stmt\ClassLike) {
            return self::DONT_TRAVERSE_CHILDREN;
        }

        if ($node instanceof PhpParser\Node\Expr\Yield_ || $node instanceof PhpParser\Node\Expr\YieldFrom) {
            // a generator returns a new Generator, not `$this`
            $this->returns_only_this = false;

            return self::STOP_TRAVERSAL;
        }

        if ($node instanceof PhpParser\Node\Stmt\Return_) {
            $this->has_return = true;

            if (!$node->expr instanceof Variable || $node->expr->name !== 'this') {
                $this->returns_only_this = false;

                return self::STOP_TRAVERSAL;
            }
        }

        return null;
    }
}
