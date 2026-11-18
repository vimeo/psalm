<?php

declare(strict_types=1);

namespace Psalm\Internal\PhpVisitor;

use Override;
use PhpParser;

use function in_array;
use function strtolower;

/**
 * Finds whether a function-like body reads its argument list with `func_get_args()`, `func_get_arg()` or
 * `func_num_args()`, so that it accepts more arguments than it declares. Closures and classes declared in the
 * body are skipped: such a call there reads their own arguments.
 *
 * @internal
 */
final class ArgumentListReadVisitor extends PhpParser\NodeVisitorAbstract
{
    private bool $reads_argument_list = false;

    /**
     * @param array<PhpParser\Node\Stmt> $stmts
     */
    public static function readsArgumentList(array $stmts): bool
    {
        $visitor = new self();

        $traverser = new PhpParser\NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($stmts);

        return $visitor->reads_argument_list;
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

        if ($node instanceof PhpParser\Node\Expr\FuncCall
            && $node->name instanceof PhpParser\Node\Name
            && in_array(
                strtolower($node->name->name),
                ['func_get_args', 'func_get_arg', 'func_num_args'],
                true,
            )
        ) {
            $this->reads_argument_list = true;

            return self::STOP_TRAVERSAL;
        }

        return null;
    }
}
