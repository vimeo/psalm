<?php

namespace Psalm\Example\Plugin\ComposerBased;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Issue\ArgumentTypeCoercion;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\AfterStatementAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterStatementAnalysisEvent;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TString;

class EchoChecker implements AfterStatementAnalysisInterface
{
    /**
     * Called after a statement has been checked
     *
     * @return null|false
     */
    public static function afterStatementAnalysis(AfterStatementAnalysisEvent $event): ?bool
    {
        $stmt = $event->getStmt();
        $statements_source = $event->getStatementsSource();
        if ($stmt instanceof PhpParser\Node\Stmt\Echo_) {
            foreach ($stmt->exprs as $expr) {
                $expr_type = $statements_source->getNodeTypeProvider()->getType($expr);

                if (!$expr_type || $expr_type->hasMixed()) {
                    IssueBuffer::maybeAdd(
                        new ArgumentTypeCoercion(
                            'Echo requires an unescaped string, ' . $expr_type . ' provided',
                            new CodeLocation($statements_source, $expr),
                            'echo',
                        ),
                        $statements_source->getSuppressedIssues(),
                    );
                    continue;
                }

                $types = $expr_type->getAtomicTypes();

                foreach ($types as $type) {
                    if ($type instanceof TString
                        && !$type instanceof TLiteralString
                    ) {
                        IssueBuffer::maybeAdd(
                            new ArgumentTypeCoercion(
                                'Echo requires an unescaped string, ' . $expr_type . ' provided',
                                new CodeLocation($statements_source, $expr),
                                'echo',
                            ),
                            $statements_source->getSuppressedIssues(),
                        );
                    }
                }
            }
        }

        return null;
    }
}
