<?php

declare(strict_types=1);

namespace Psalm\Tests\Config\Plugin;

use Override;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use Psalm\CodeLocation;
use Psalm\FileManipulation;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\AfterMethodCallAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterMethodCallAnalysisEvent;

use function strtolower;

/**
 * Reports LegacyCall on LegacyReceiver::legacy(). The rewrite to modern() is only safe when the receiver is exactly
 * a LegacyReceiver, so the call is fixed then, and left to the report for any wider type
 */
final class LegacyReceiverFixer implements AfterMethodCallAnalysisInterface
{
    #[Override]
    public static function afterMethodCallAnalysis(AfterMethodCallAnalysisEvent $event): void
    {
        $expr = $event->getExpr();

        if (!$expr instanceof MethodCall
            || !$expr->name instanceof Identifier
            || strtolower($event->getDeclaringMethodId()) !== 'legacyreceiver::legacy'
        ) {
            return;
        }

        $source = $event->getStatementsSource();
        $receiver_type = $source->getNodeTypeProvider()->getType($expr->var);
        $issue = new LegacyCall('Use modern()', new CodeLocation($source, $expr->name));

        if ($receiver_type?->getId() === 'LegacyReceiver'
            && $event->getCodebase()->shouldFixIssue($issue, $source->getSuppressedIssues())
        ) {
            $event->setFileReplacements([
                new FileManipulation(
                    (int) $expr->name->getAttribute('startFilePos'),
                    (int) $expr->name->getAttribute('endFilePos') + 1,
                    'modern',
                ),
            ]);

            return;
        }

        IssueBuffer::maybeAdd($issue, $source->getSuppressedIssues(), true);
    }
}
