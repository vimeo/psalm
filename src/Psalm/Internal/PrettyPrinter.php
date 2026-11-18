<?php

declare(strict_types=1);

namespace Psalm\Internal;

use Override;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\NodeVisitorAbstract;
use PhpParser\PrettyPrinter\Standard;
use Psalm\Node\Expr\PartialApplication;

/**
 * Prints an expression of the AST Psalm parses, which has nodes php-parser's printer doesn't know
 *
 * @internal
 */
final class PrettyPrinter
{
    public static function printExpr(Expr $expr): string
    {
        $visitor = new class extends NodeVisitorAbstract {
            /**
             * @psalm-capabilities read-props
             */
            #[Override]
            public function leaveNode(Node $node): ?Node
            {
                if (!$node instanceof PartialApplication) {
                    return null;
                }

                // the call as written, with its placeholders
                $call = clone $node->call;
                $call->args = $node->args;

                return $call;
            }
        };

        $printable = (new NodeTraverser(new CloningVisitor(), $visitor))->traverse([$expr])[0] ?? null;

        return (new Standard())->prettyPrintExpr($printable instanceof Expr ? $printable : $expr);
    }
}
