<?php

namespace Psalm\Example\Plugin;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Issue\InvalidClass;
use Psalm\Issue\UndefinedMethod;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;

use function in_array;
use function preg_match;
use function preg_split;
use function strpos;
use function strtolower;

class StringChecker implements AfterExpressionAnalysisInterface
{
    /**
     * Called after an expression has been checked
     *
     * @return null|false
     */
    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
    {
        $expr = $event->getExpr();
        $statements_source = $event->getStatementsSource();
        $codebase = $event->getCodebase();
        if ($expr instanceof PhpParser\Node\Scalar\String_) {
            $class_or_class_method = '/^\\\?Psalm(\\\[A-Z][A-Za-z0-9]+)+(::[A-Za-z0-9]+)?$/';

            if (strpos($statements_source->getFileName(), 'base/DefinitionManager.php') === false
                && strpos($expr->value, 'TestController') === false
                && preg_match($class_or_class_method, $expr->value)
            ) {
                /** @psalm-suppress PossiblyInvalidArrayAccess */
                $absolute_class = preg_split('/[:]/', $expr->value)[0];
                IssueBuffer::maybeAdd(
                    new InvalidClass(
                        'Use ::class constants when representing class names',
                        new CodeLocation($statements_source, $expr),
                        $absolute_class,
                    ),
                    $statements_source->getSuppressedIssues(),
                );
            }
        } elseif ($expr instanceof PhpParser\Node\Expr\BinaryOp\Concat
            && $expr->left instanceof PhpParser\Node\Expr\ClassConstFetch
            && $expr->left->class instanceof PhpParser\Node\Name
            && $expr->left->name instanceof PhpParser\Node\Identifier
            && strtolower($expr->left->name->name) === 'class'
            && !in_array(strtolower($expr->left->class->getFirst()), ['self', 'static', 'parent'])
            && $expr->right instanceof PhpParser\Node\Scalar\String_
            && preg_match('/^::[A-Za-z0-9]+$/', $expr->right->value)
        ) {
            $method_id = ((string) $expr->left->class->getAttribute('resolvedName')) . $expr->right->value;

            $appearing_method_id = $codebase->getAppearingMethodId($method_id);

            if (!$appearing_method_id) {
                if (IssueBuffer::accepts(
                    new UndefinedMethod(
                        'Method ' . $method_id . ' does not exist',
                        new CodeLocation($statements_source, $expr),
                        $method_id,
                    ),
                    $statements_source->getSuppressedIssues(),
                )) {
                    return false;
                }

                return null;
            }
        }

        return null;
    }
}
