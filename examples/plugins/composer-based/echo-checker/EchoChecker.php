<?php
namespace Psalm\Example\Plugin\ComposerBased;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\FileManipulation;
use Psalm\IssueBuffer;
use Psalm\Issue\ArgumentTypeCoercion;
use Psalm\Plugin\Hook\AfterStatementAnalysisInterface;
use Psalm\Plugin\Hook\Event\AfterStatementAnalysisEvent;

class EchoChecker implements AfterStatementAnalysisInterface
{
    /**
     * Called after a statement has been checked
     *
     * @param  FileManipulation[]   $file_replacements
     *
     * @return null|false
     */
    public static function afterStatementAnalysis(AfterStatementAnalysisEvent $event): ?bool {
        $stmt = $event->getStmt();
        $statements_source = $event->getStatementsSource();
        if ($stmt instanceof PhpParser\Node\Stmt\Echo_) {
            foreach ($stmt->exprs as $expr) {
                $expr_type = $statements_source->getNodeTypeProvider()->getType($expr);

                if (!$expr_type || $expr_type->hasMixed()) {
                    if (IssueBuffer::accepts(
                        new ArgumentTypeCoercion(
                            'Echo requires an unescaped string, ' . $expr_type . ' provided',
                            new CodeLocation($statements_source, $expr),
                            'echo'
                        ),
                        $statements_source->getSuppressedIssues()
                    )) {
                        // keep soldiering on
                    }

                    continue;
                }

                $types = $expr_type->getAtomicTypes();

                foreach ($types as $type) {
                    if ($type instanceof \Psalm\Type\Atomic\TString
                        && !$type instanceof \Psalm\Type\Atomic\TLiteralString
                        && !$type instanceof \Psalm\Type\Atomic\THtmlEscapedString
                    ) {
                        if (IssueBuffer::accepts(
                            new ArgumentTypeCoercion(
                                'Echo requires an unescaped string, ' . $expr_type . ' provided',
                                new CodeLocation($statements_source, $expr),
                                'echo'
                            ),
                            $statements_source->getSuppressedIssues()
                        )) {
                            // keep soldiering on
                        }
                    }
                }
            }
        }

        return null;
    }
}
