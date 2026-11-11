<?php

declare(strict_types=1);

namespace Psalm\Tests\Config\Plugin;

use Override;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use Psalm\CodeLocation;
use Psalm\FileManipulation;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;

final class LegacyCallFixer implements AfterExpressionAnalysisInterface
{
    #[Override]
    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
    {
        $expr = $event->getExpr();

        if (!$expr instanceof FuncCall
            || !$expr->name instanceof Name
            || $expr->name->toLowerString() !== 'legacy_call'
        ) {
            return null;
        }

        $source = $event->getStatementsSource();
        $issue = new LegacyCall('Use modern_call()', new CodeLocation($source, $expr->name));

        if ($event->getCodebase()->shouldFixIssue($issue, $source->getSuppressedIssues())) {
            $event->setFileReplacements([
                new FileManipulation(
                    (int) $expr->name->getAttribute('startFilePos'),
                    (int) $expr->name->getAttribute('endFilePos') + 1,
                    'modern_call',
                ),
            ]);

            return null;
        }

        IssueBuffer::maybeAdd($issue, $source->getSuppressedIssues(), true);

        return null;
    }
}
