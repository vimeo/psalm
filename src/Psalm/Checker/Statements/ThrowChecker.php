<?php
namespace Psalm\Checker\Statements;

use PhpParser;
use Psalm\Checker\StatementsChecker;
use Psalm\Checker\TypeChecker;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\InvalidThrow;
use Psalm\IssueBuffer;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Union;

class ThrowChecker
{
    /**
     * @return  false|null
     */
    public static function analyze(
        StatementsChecker $statements_checker,
        PhpParser\Node\Stmt\Throw_ $stmt,
        Context $context
    ) {
        if (ExpressionChecker::analyze($statements_checker, $stmt->expr, $context) === false) {
            return false;
        }

        if ($context->check_classes && isset($stmt->expr->inferredType) && !$stmt->expr->inferredType->isMixed()) {
            $throw_type = $stmt->expr->inferredType;

            $exception_type = new Union([new TNamedObject('Exception'), new TNamedObject('Throwable')]);

            $file_checker = $statements_checker->getFileChecker();
            $project_checker = $file_checker->project_checker;

            foreach ($throw_type->getTypes() as $throw_type_part) {
                $throw_type_candidate = new Union([$throw_type_part]);

                if (!TypeChecker::isContainedBy($project_checker->codebase, $throw_type_candidate, $exception_type)) {
                    if (IssueBuffer::accepts(
                        new InvalidThrow(
                            'Cannot throw ' . $throw_type_part
                                . ' as it does not extend Exception or implement Throwable',
                            new CodeLocation($file_checker, $stmt),
                            (string) $throw_type_part
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }
                } elseif ($context->collect_exceptions) {
                    foreach ($throw_type->getTypes() as $throw_atomic_type) {
                        if ($throw_atomic_type instanceof TNamedObject) {
                            $context->possibly_thrown_exceptions[$throw_atomic_type->value] = true;
                        }
                    }
                }
            }
        }
    }
}
