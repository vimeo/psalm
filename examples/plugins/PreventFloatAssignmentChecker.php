<?php

namespace Psalm\Example\Plugin;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Issue\PluginIssue;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;

/**
 * Prevents any assignment to a float value
 */
class PreventFloatAssignmentChecker implements AfterExpressionAnalysisInterface
{
    /**
     * Called after an expression has been checked
     *
     * @return null
     */
    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
    {
        $expr = $event->getExpr();
        $statements_source = $event->getStatementsSource();
        if ($expr instanceof PhpParser\Node\Expr\Assign
            && ($expr_type = $statements_source->getNodeTypeProvider()->getType($expr->expr))
            && $expr_type->hasFloat()
        ) {
            IssueBuffer::maybeAdd(
                new NoFloatAssignment(
                    'Don’t assign to floats',
                    new CodeLocation($statements_source, $expr),
                ),
                $statements_source->getSuppressedIssues(),
            );
        }

        return null;
    }
}

class NoFloatAssignment extends PluginIssue
{
}
