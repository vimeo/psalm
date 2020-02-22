<?php
namespace Psalm\Internal\Analyzer\Statements;

use PhpParser;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\TypeAnalyzer;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\InvalidThrow;
use Psalm\IssueBuffer;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Union;

/**
 * @internal
 */
class ThrowAnalyzer
{
    /**
     * @return  false|null
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\Throw_ $stmt,
        Context $context
    ) {
        $context->inside_throw = true;
        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context) === false) {
            return false;
        }
        $context->inside_throw = false;

        if ($context->check_classes
            && ($throw_type = $statements_analyzer->node_data->getType($stmt->expr))
            && !$throw_type->hasMixed()
        ) {
            $exception_type = new Union([new TNamedObject('Exception'), new TNamedObject('Throwable')]);

            $file_analyzer = $statements_analyzer->getFileAnalyzer();
            $codebase = $statements_analyzer->getCodebase();

            foreach ($throw_type->getAtomicTypes() as $throw_type_part) {
                $throw_type_candidate = new Union([$throw_type_part]);

                if (!TypeAnalyzer::isContainedBy($codebase, $throw_type_candidate, $exception_type)) {
                    if (IssueBuffer::accepts(
                        new InvalidThrow(
                            'Cannot throw ' . $throw_type_part
                                . ' as it does not extend Exception or implement Throwable',
                            new CodeLocation($file_analyzer, $stmt),
                            (string) $throw_type_part
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        return false;
                    }
                } elseif (!$context->isSuppressingExceptions($statements_analyzer)) {
                    $codelocation = new CodeLocation($file_analyzer, $stmt);
                    $hash = $codelocation->getHash();
                    foreach ($throw_type->getAtomicTypes() as $throw_atomic_type) {
                        if ($throw_atomic_type instanceof TNamedObject) {
                            $context->possibly_thrown_exceptions[$throw_atomic_type->value][$hash] = $codelocation;
                        }
                    }
                }
            }
        }
    }
}
