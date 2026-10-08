<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\CommentAnalyzer;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\VariableFetchAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\TaintFlowGraph;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\ReferenceConstraint;
use Psalm\Issue\ImpureGlobalVariable;
use Psalm\Issue\InvalidGlobal;
use Psalm\IssueBuffer;
use Psalm\Storage\Capabilities;

use function is_string;

/**
 * @internal
 */
final class GlobalAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\Global_ $stmt,
        Context $context,
        ?Context $global_context,
    ): void {
        if (!$context->collect_initializations && !$global_context) {
            IssueBuffer::maybeAdd(
                new InvalidGlobal(
                    'Cannot use global scope here (unless this file is included from a non-global scope)',
                    new CodeLocation($statements_analyzer, $stmt),
                ),
                $statements_analyzer->getSource()->getSuppressedIssues(),
            );
        }

        $codebase = $statements_analyzer->getCodebase();
        $source = $statements_analyzer->getSource();
        $function_storage = $source instanceof FunctionLikeAnalyzer
            ? $source->getFunctionLikeStorage($statements_analyzer)
            : null;

        // binding a global reads global state; writing through it is charged at the write
        $statements_analyzer->signalMutation(
            Capabilities::READ_GLOBALS,
            $context,
            'global variable',
            ImpureGlobalVariable::class,
            $stmt,
        );

        foreach ($stmt->vars as $var) {
            if (!$var instanceof PhpParser\Node\Expr\Variable) {
                continue;
            }

            if (!is_string($var->name)) {
                continue;
            }

            $var_name = $var->name;
            $var_id = '$' . $var_name;

            FunctionLikeAnalyzer::unbindByRefParam($codebase, $context, $var_id);

            $doc_comment = $stmt->getDocComment();
            $comment_type = null;

            if ($doc_comment) {
                $var_comments = CommentAnalyzer::getVarComments($doc_comment, $statements_analyzer, $var);
                $comment_type = CommentAnalyzer::populateVarTypesFromDocblock(
                    $var_comments,
                    $var,
                    $context,
                    $statements_analyzer,
                );
            }

            if ($comment_type) {
                $context->vars_in_scope[$var_id] = $comment_type;
                $context->vars_possibly_in_scope[$var_id] = true;
                $context->byref_constraints[$var_id] = new ReferenceConstraint($comment_type);
            } else {
                if ($var->name === 'argv' || $var->name === 'argc') {
                    $context->vars_in_scope[$var_id] =
                        VariableFetchAnalyzer::getGlobalType($var_id, $codebase->analysis_php_version_id);
                } elseif (isset($function_storage->global_types[$var_id])) {
                    $context->vars_in_scope[$var_id] = $function_storage->global_types[$var_id];
                    $context->vars_possibly_in_scope[$var_id] = true;
                } else {
                    $context->vars_in_scope[$var_id] =
                        $global_context && $global_context->hasVariable($var_id)
                            ? $global_context->vars_in_scope[$var_id]
                            : VariableFetchAnalyzer::getGlobalType($var_id, $codebase->analysis_php_version_id);

                    $context->vars_possibly_in_scope[$var_id] = true;

                    $context->byref_constraints[$var_id] = new ReferenceConstraint();
                }
            }

            $assignment_node = DataFlowNode::getForAssignment(
                $var_id,
                new CodeLocation($statements_analyzer, $var),
            );
            $context->vars_in_scope[$var_id] = $context->vars_in_scope[$var_id]->setProperties([
                'parent_nodes' => [$assignment_node->id => $assignment_node],
                'from_global_state' => true,
            ]);

            if ($taint_flow_graph = $statements_analyzer->getTaintFlowGraphWithSuppressed()) {
                $taint_flow_graph->addNode($assignment_node);

                foreach (self::getGlobalReadNodes($taint_flow_graph, $var_name) as $global_node) {
                    $taint_flow_graph->addPath($global_node, $assignment_node, '=');
                }

                // what the variable holds when it stops referencing the global, as for a by-reference parameter
                $context->by_ref_param_out_nodes[$var_id] = self::getGlobalNode($taint_flow_graph, $var_name);
            }

            $context->references_to_external_scope[$var_id] = true;

            if (isset($context->references_in_scope[$var_id])) {
                // Global shadows existing reference
                $context->decrementReferenceCount($var_id);
                unset($context->references_in_scope[$var_id]);
            }
            $statements_analyzer->registerVariable(
                $var_id,
                new CodeLocation($statements_analyzer, $var),
                $context->branch_point,
            );
            $statements_analyzer->getCodebase()->analyzer->addNodeReference(
                $statements_analyzer->getFilePath(),
                $var,
                $var_id,
            );

            if ($global_context !== null && $global_context->hasVariable($var_id)) {
                $global_context->referenced_globals[$var_id] = true;
            }
        }
    }

    /**
     * The node of the global variable $name (null: of those written by a name not known statically), which
     * $GLOBALS holds under its name.
     */
    public static function getGlobalNode(TaintFlowGraph $graph, ?string $name): DataFlowNode
    {
        $global_node = DataFlowNode::getForGlobalVariable($name);
        $globals_node = DataFlowNode::getForGlobals();

        $graph->addNode($global_node);
        $graph->addNode($globals_node);
        $graph->addPath(
            $global_node,
            $globals_node,
            $name === null ? 'arrayvalue-assignment' : 'arrayvalue-assignment-\'' . $name . '\'',
        );

        return $global_node;
    }

    /**
     * What reading the global variable $name gets: what is written to it, by its name or by one not known
     * statically.
     *
     * @return array<string, DataFlowNode>
     */
    public static function getGlobalReadNodes(TaintFlowGraph $graph, string $name): array
    {
        $global_node = self::getGlobalNode($graph, $name);
        $unknown_global_node = self::getGlobalNode($graph, null);

        return [$global_node->id => $global_node, $unknown_global_node->id => $unknown_global_node];
    }
}
