<?php
namespace Psalm\Internal\Visitor;

use PhpParser;

/**
 * @internal
 */
class CheckTrivialExprVisitor extends PhpParser\NodeVisitorAbstract implements PhpParser\NodeVisitor
{
    /**
     * @var array<int, PhpParser\Node\Expr>
     */
    protected $non_trivial_expr = [];

    /**
     * @param  PhpParser\Node\Expr $node
     * @return bool
     */
    private function checkNonTrivialExpr(PhpParser\Node\Expr $node)
    {
        /**
         * @psalm-suppress UndefinedClass
         * @psalm-suppress TypeDoesNotContainType
         */
        if ($node instanceof PhpParser\Node\Expr\ArrayDimFetch
            || $node instanceof PhpParser\Node\Expr\Closure
            || $node instanceof PhpParser\Node\Expr\ClosureUse
            || $node instanceof PhpParser\Node\Expr\Eval_
            || $node instanceof PhpParser\Node\Expr\Exit_
            || $node instanceof PhpParser\Node\Expr\Include_
            || $node instanceof PhpParser\Node\Expr\FuncCall
            || $node instanceof PhpParser\Node\Expr\MethodCall
            || $node instanceof PhpParser\Node\Expr\ArrowFunction
            || $node instanceof PhpParser\Node\Expr\ShellExec
            || $node instanceof PhpParser\Node\Expr\StaticCall
            || $node instanceof PhpParser\Node\Expr\Yield_
            || $node instanceof PhpParser\Node\Expr\YieldFrom
            || $node instanceof PhpParser\Node\Expr\New_
        ) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * @param  PhpParser\Node $node
     * @return null|int
     */
    public function enterNode(PhpParser\Node $node)
    {
        if ($node instanceof PhpParser\Node\Expr) {
            // Check for Non-Trivial Expression first
            if ($this->checkNonTrivialExpr($node)) {
                $this->non_trivial_expr[] = $node;
                return PhpParser\NodeTraverser::STOP_TRAVERSAL;
            } elseif ($node instanceof PhpParser\Node\Expr\ClassConstFetch
                || $node instanceof PhpParser\Node\Expr\ConstFetch
                || $node instanceof PhpParser\Node\Expr\Error
                || $node instanceof PhpParser\Node\Expr\PropertyFetch
                || $node instanceof PhpParser\Node\Expr\StaticPropertyFetch
            ) {
                return PhpParser\NodeTraverser::STOP_TRAVERSAL;
            }
        }
        return null;
    }

    /**
     * @return array<int, PhpParser\Node\Expr>
     */
    public function getNonTrivialExpr()
    {
        return $this->non_trivial_expr;
    }
}
