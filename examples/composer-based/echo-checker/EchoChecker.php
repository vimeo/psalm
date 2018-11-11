<?php
namespace Vimeo\CodeAnalysis\EchoChecker;

use PhpParser;
use Psalm\Checker;
use Psalm\Checker\StatementsChecker;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\FileManipulation\FileManipulation;
use Psalm\IssueBuffer;
use Psalm\Issue\TypeCoercion;

class EchoChecker extends \Psalm\Plugin
{
    /**
     * Called after an expression has been checked
     *
     * @param  StatementsChecker    $statements_checker
     * @param  PhpParser\Node       $stmt
     * @param  Context              $context
     * @param  CodeLocation         $code_location
     * @param  string[]             $suppressed_issues
     * @param  FileManipulation[]   $file_replacements
     *
     * @return null|false
     */
    public static function afterStatementCheck(
        StatementsChecker $statements_checker,
        PhpParser\Node $stmt,
        Context $context,
        CodeLocation $code_location,
        array $suppressed_issues,
        array &$file_replacements = []
    ) {
        if ($stmt instanceof PhpParser\Node\Stmt\Echo_) {
            foreach ($stmt->exprs as $expr) {
                if (!isset($expr->inferredType) || $expr->inferredType->isMixed()) {
                    if (IssueBuffer::accepts(
                        new TypeCoercion(
                            'Echo requires an unescaped string, ' . $expr->inferredType . ' provided',
                            new CodeLocation($statements_checker->getSource(), $expr)
                        ),
                        $statements_checker->getSuppressedIssues()
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
                                new CodeLocation($statements_checker->getSource(), $expr)
                            ),
                            $statements_checker->getSuppressedIssues()
                        )) {
                            // keep soldiering on
                        }
                    }
                }
            }
        }
    }
}
