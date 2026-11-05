<?php

declare(strict_types=1);

namespace Psalm\Internal\PhpVisitor;

use Override;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Attribute;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\VariadicPlaceholder;
use PhpParser\NodeVisitorAbstract;
use Psalm\Node\Expr\PartialApplication;

use function array_filter;
use function array_values;
use function count;

/**
 * Replaces the call node of each partial function application with a PartialApplication node, and removes the
 * placeholders of attribute arguments (a compile error, see AttributesAnalyzer), so that no argument list Psalm
 * analyzes holds a placeholder other than a first-class callable's `...`
 *
 * @internal
 */
final class PartialApplicationVisitor extends NodeVisitorAbstract
{
    public const PLACEHOLDER_IN_ATTRIBUTE = 'psalmPlaceholderInAttribute';

    #[Override]
    public function leaveNode(Node $node): ?Node
    {
        if ($node instanceof Attribute) {
            $args = array_values(array_filter($node->args, static fn(Node $arg): bool => $arg instanceof Arg));

            if (count($args) !== count($node->args)) {
                $node->args = $args;
                $node->setAttribute(self::PLACEHOLDER_IN_ATTRIBUTE, true);
            }

            return null;
        }

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
