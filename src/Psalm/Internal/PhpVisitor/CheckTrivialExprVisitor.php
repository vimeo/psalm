<?php

declare(strict_types=1);

namespace Psalm\Internal\PhpVisitor;

use Closure;
use Override;
use PhpParser;

use function is_string;

/**
 * @internal
 */
final class CheckTrivialExprVisitor extends PhpParser\NodeVisitorAbstract
{
    private bool $has_non_trivial_expr = false;

    /**
     * @param Closure(PhpParser\Node\Expr\Variable):bool $is_used_write whether the value a write gives the variable
     *                                                                  is used afterwards
     */
    public function __construct(
        private readonly Closure $is_used_write,
    ) {
    }

    private function checkNonTrivialExpr(PhpParser\Node\Expr $node): bool
    {
        if ($node instanceof PhpParser\Node\Expr\Assign
            || $node instanceof PhpParser\Node\Expr\AssignOp
            || $node instanceof PhpParser\Node\Expr\AssignRef
            || $node instanceof PhpParser\Node\Expr\PreInc
            || $node instanceof PhpParser\Node\Expr\PreDec
            || $node instanceof PhpParser\Node\Expr\PostInc
            || $node instanceof PhpParser\Node\Expr\PostDec
        ) {
            // a write to a local variable matters only if what it writes is read later
            return $node instanceof PhpParser\Node\Expr\AssignRef
                || !$node->var instanceof PhpParser\Node\Expr\Variable
                || !is_string($node->var->name)
                || ($this->is_used_write)($node->var);
        }

        if ($node instanceof PhpParser\Node\Expr\ArrayDimFetch
            || $node instanceof PhpParser\Node\Expr\Closure
            || $node instanceof PhpParser\Node\Expr\Eval_
            || $node instanceof PhpParser\Node\Expr\Exit_
            || $node instanceof PhpParser\Node\Expr\Include_
            || $node instanceof PhpParser\Node\Expr\FuncCall
            || $node instanceof PhpParser\Node\Expr\MethodCall
            || $node instanceof PhpParser\Node\Expr\NullsafeMethodCall
            || $node instanceof PhpParser\Node\Expr\ArrowFunction
            || $node instanceof PhpParser\Node\Expr\ShellExec
            || $node instanceof PhpParser\Node\Expr\StaticCall
            || $node instanceof PhpParser\Node\Expr\Yield_
            || $node instanceof PhpParser\Node\Expr\YieldFrom
            || $node instanceof PhpParser\Node\Expr\New_
            || $node instanceof PhpParser\Node\Expr\Cast\String_
            || $node instanceof PhpParser\Node\Expr\BinaryOp\Pipe
            || $node instanceof PhpParser\Node\Expr\Print_
            || $node instanceof PhpParser\Node\Expr\Throw_
        ) {
            if (($node instanceof PhpParser\Node\Expr\FuncCall
                    || $node instanceof PhpParser\Node\Expr\MethodCall
                    || $node instanceof PhpParser\Node\Expr\StaticCall)
                && $node->getAttribute('pure', false)
            ) {
                return false;
            }

            if ($node instanceof PhpParser\Node\Expr\New_ && $node->getAttribute('external_mutation_free', false)) {
                return false;
            }

            return true;
        }

        return false;
    }

    #[Override]
    public function enterNode(PhpParser\Node $node): ?int
    {
        if ($node instanceof PhpParser\Node\Expr) {
            // Check for Non-Trivial Expression first
            if ($this->checkNonTrivialExpr($node)) {
                $this->has_non_trivial_expr = true;
                return self::STOP_TRAVERSAL;
            }
        } elseif ($node instanceof PhpParser\Node\ClosureUse) {
            $this->has_non_trivial_expr = true;
            return self::STOP_TRAVERSAL;
        }
        return null;
    }

    public function hasNonTrivialExpr(): bool
    {
        return $this->has_non_trivial_expr;
    }
}
