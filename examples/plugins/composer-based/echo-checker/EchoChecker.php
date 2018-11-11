<?php
namespace Psalm\Example\Plugin\ComposerBased;

use PhpParser;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\FileManipulation\FileManipulation;
use Psalm\IssueBuffer;
use Psalm\Issue\TypeCoercion;
use Psalm\PluginApi\Hook\AfterStatementAnalysisInterface;
use Psalm\StatementsSource;

class EchoChecker implements AfterStatementAnalysisInterface
{
    /**
     * Called after a statement has been checked
     *
     * @param  string[]             $suppressed_issues
     * @param  FileManipulation[]   $file_replacements
     *
     * @return null|false
     */
    public static function afterStatementAnalysis(
        PhpParser\Node\Stmt $stmt,
        Context $context,
        StatementsSource $statements_source,
        Codebase $codebase,
        array &$file_replacements = []
    ) {
        if ($stmt instanceof PhpParser\Node\Stmt\Echo_) {
            foreach ($stmt->exprs as $expr) {
                if (!isset($expr->inferredType) || $expr->inferredType->isMixed()) {
                    if (IssueBuffer::accepts(
                        new TypeCoercion(
                            'Echo requires an unescaped string, ' . $expr->inferredType . ' provided',
                            new CodeLocation($statements_source, $expr)
                        ),
                        $statements_source->getSuppressedIssues()
                    )) {
                        // keep soldiering on
                    }

                    continue;
                }

                $types = $expr->inferredType->getTypes();

                foreach ($types as $type) {
                    if ($type instanceof \Psalm\Type\Atomic\TString
                        && !$type instanceof \Psalm\Type\Atomic\TLiteralString
                        && !$type instanceof \Psalm\Type\Atomic\THtmlEscapedString
                    ) {
                        if (IssueBuffer::accepts(
                            new TypeCoercion(
                                'Echo requires an unescaped string, ' . $expr->inferredType . ' provided',
                                new CodeLocation($statements_source, $expr)
                            ),
                            $statements_source->getSuppressedIssues()
                        )) {
                            // keep soldiering on
                        }
                    }
                }
            }
        }
    }
}
