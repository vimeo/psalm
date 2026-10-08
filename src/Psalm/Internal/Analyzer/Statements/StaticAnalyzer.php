<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\CommentAnalyzer;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\ReferenceConstraint;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Issue\ImpureStaticVariable;
use Psalm\Issue\ReferenceConstraintViolation;
use Psalm\IssueBuffer;
use Psalm\Storage\Capabilities;
use Psalm\Type;
use Psalm\Type\Union;

use function is_string;

/**
 * @internal
 */
final class StaticAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\Static_ $stmt,
        Context $context,
    ): void {
        $codebase = $statements_analyzer->getCodebase();

        $statements_analyzer->signalMutation(
            Capabilities::READ_GLOBALS | Capabilities::WRITE_GLOBALS,
            $context,
            'static variable',
            ImpureStaticVariable::class,
            $stmt,
        );

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

            FunctionLikeAnalyzer::unbindByRefParam($codebase, $context, $var_id);

            if ($context->check_variables) {
                $context->vars_in_scope[$var_id] = self::bindToStaticVariable(
                    $statements_analyzer,
                    $context,
                    $var,
                    $var_id,
                    $comment_type ?: Type::getMixed(),
                );
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

    /**
     * The static variable of the function-like analyzed holds what all its calls leave in it (see
     * DataFlowNode::getForStaticVariable()), as a static property does: the variable bound to it, of type
     * $type, reads it, and its default and what the variable holds after each statement flow into it (see
     * taintBoundStaticVariables()).
     */
    private static function bindToStaticVariable(
        StatementsAnalyzer $statements_analyzer,
        Context $context,
        PhpParser\Node\StaticVar $var,
        string $var_id,
        Union $type,
    ): Union {
        $source = $statements_analyzer->getSource();
        $taint_flow_graph = $statements_analyzer->getTaintFlowGraphWithSuppressed();

        if (!$taint_flow_graph || !$source instanceof FunctionLikeAnalyzer) {
            return $type;
        }

        $static_node = DataFlowNode::getForStaticVariable($source->getId(), $var_id);

        $taint_flow_graph->addNode($static_node);
        $taint_flow_graph->addSharedState($static_node);

        $default_type = $var->default ? $statements_analyzer->node_data->getType($var->default) : null;

        foreach ($default_type->parent_nodes ?? [] as $parent_node) {
            $taint_flow_graph->addPath($parent_node, $static_node, '=');
        }

        // what the variable holds when it stops referencing the static variable, as for a by-reference parameter
        $context->by_ref_param_out_nodes[$var_id] = $static_node;

        return $type->addParentNodes([$static_node->id => $static_node]);
    }

    /**
     * A variable bound to a static variable is it: what it holds after each statement flows into it, as the
     * function-like may run again before it returns, through a call it makes, and read it.
     */
    public static function taintBoundStaticVariables(StatementsAnalyzer $statements_analyzer, Context $context): void
    {
        if (!$context->by_ref_param_out_nodes
            || !$taint_flow_graph = $statements_analyzer->getTaintFlowGraphWithSuppressed()
        ) {
            return;
        }

        foreach ($context->by_ref_param_out_nodes as $var_id => $out_node) {
            if (!isset($context->vars_in_scope[$var_id]) || !$taint_flow_graph->isSharedState($out_node)) {
                continue;
            }

            foreach ($context->vars_in_scope[$var_id]->parent_nodes as $parent_node) {
                $taint_flow_graph->addPath($parent_node, $out_node, '=');
            }
        }
    }
}
