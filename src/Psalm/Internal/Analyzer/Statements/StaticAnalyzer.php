<?php

namespace Psalm\Internal\Analyzer\Statements;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\CommentAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\ReferenceConstraint;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Issue\ImpureStaticVariable;
use Psalm\Issue\ReferenceConstraintViolation;
use Psalm\IssueBuffer;
use Psalm\Type;

use function is_string;

/**
 * @internal
 */
final class StaticAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\Static_ $stmt,
        Context $context
    ): void {
        $codebase = $statements_analyzer->getCodebase();

        if ($context->mutation_free) {
            IssueBuffer::maybeAdd(
                new ImpureStaticVariable(
                    'Cannot use a static variable in a mutation-free context',
                    new CodeLocation($statements_analyzer, $stmt),
                ),
                $statements_analyzer->getSuppressedIssues(),
            );
        }

        foreach ($stmt->vars as $var) {
            if (!is_string($var->var->name)) {
                continue;
            }

            $var_id = '$' . $var->var->name;

            $doc_comment = $stmt->getDocComment();

            $comment_type = null;

            if ($doc_comment) {
                $var_comments = CommentAnalyzer::getVarComments($doc_comment, $statements_analyzer, $var->var);
                $comment_type = CommentAnalyzer::populateVarTypesFromDocblock(
                    $var_comments,
                    $var->var,
                    $context,
                    $statements_analyzer,
                );
            }

            if ($comment_type) {
                $context->byref_constraints[$var_id] = new ReferenceConstraint($comment_type);
            }

            if ($var->default) {
                if (ExpressionAnalyzer::analyze($statements_analyzer, $var->default, $context) === false) {
                    return;
                }

                if ($comment_type
                    && ($var_default_type = $statements_analyzer->node_data->getType($var->default))
                    && !UnionTypeComparator::isContainedBy(
                        $codebase,
                        $var_default_type,
                        $comment_type,
                    )
                ) {
                    IssueBuffer::maybeAdd(
                        new ReferenceConstraintViolation(
                            $var_id . ' of type ' . $comment_type->getId() . ' cannot be assigned type '
                                . $var_default_type->getId(),
                            new CodeLocation($statements_analyzer, $var),
                        ),
                    );
                }
            }

            if ($context->check_variables) {
                $context->vars_in_scope[$var_id] = $comment_type ? $comment_type : Type::getMixed();
                $context->vars_possibly_in_scope[$var_id] = true;
                $context->assigned_var_ids[$var_id] = (int) $stmt->getAttribute('startFilePos');
                $statements_analyzer->byref_uses[$var_id] = true;

                $location = new CodeLocation($statements_analyzer, $var);

                $statements_analyzer->registerVariable(
                    $var_id,
                    $location,
                    $context->branch_point,
                );
            }
        }
    }
}
