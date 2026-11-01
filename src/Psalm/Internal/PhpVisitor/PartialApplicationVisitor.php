<?php

declare(strict_types=1);

namespace Psalm\Internal\PhpVisitor;

use Override;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\VariadicPlaceholder;
use PhpParser\NodeVisitorAbstract;
use Psalm\Node\Expr\PartialApplication;

/**
 * Replaces the call node of each partial function application with a PartialApplication node, so that no call
 * Psalm analyzes holds a placeholder other than a first-class callable's `...`
 *
 * @internal
 */
final class PartialApplicationVisitor extends NodeVisitorAbstract
{
    #[Override]
    public function leaveNode(Node $node): ?Node
    {
        if (!($node instanceof FuncCall
                || $node instanceof MethodCall
                || $node instanceof NullsafeMethodCall
                || $node instanceof StaticCall
                || $node instanceof New_)
            || !$node->isPartialFunctionApplication()
            || $node->isFirstClassCallable()
        ) {
            return null;
        }

        $call = clone $node;
        $call->args = [new VariadicPlaceholder($node->getAttributes())];

        return new PartialApplication($call, $node->getRawArgs(), $node->getAttributes());
    }
}
