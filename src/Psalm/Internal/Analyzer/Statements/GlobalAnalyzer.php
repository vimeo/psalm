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
use Psalm\Type\Union;

use function is_string;
use function preg_match;
use function substr;

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
     * A variable bound to a global is the global: what it holds after each statement flows into it, as any
     * function-like called before the variable stops referencing it may read it (see
     * FunctionLikeAnalyzer::unbindByRefParam() for the last value). At file scope, every variable is a global.
     */
    public static function taintBoundGlobals(StatementsAnalyzer $statements_analyzer, Context $context): void
    {
        if ((!$context->by_ref_param_out_nodes && !$context->is_global)
            || !$taint_flow_graph = $statements_analyzer->getTaintFlowGraphWithSuppressed()
        ) {
            return;
        }

        $var_ids = $context->is_global ? $context->vars_in_scope : $context->by_ref_param_out_nodes;

        foreach ($var_ids as $var_id => $_) {
            if (!self::isBoundToGlobal($context, $var_id)
                || !isset($context->vars_in_scope[$var_id])
                || !$context->vars_in_scope[$var_id]->parent_nodes
            ) {
                continue;
            }

            $global_node = self::getGlobalNode($taint_flow_graph, substr($var_id, 1));

            foreach ($context->vars_in_scope[$var_id]->parent_nodes as $parent_node) {
                $taint_flow_graph->addPath($parent_node, $global_node, '=');
            }
        }
    }

    /**
     * What the variable $var_id holds, of type $type, once the rest of the program may have written to it, if it
     * is a global: when it isn't assigned yet at file scope, or after a call of a function-like binding it.
     */
    public static function taintGlobalRead(
        StatementsAnalyzer $statements_analyzer,
        Context $context,
        string $var_id,
        Union $type,
    ): Union {
        if (!self::isBoundToGlobal($context, $var_id)
            || !$taint_flow_graph = $statements_analyzer->getTaintFlowGraphWithSuppressed()
        ) {
            return $type;
        }

        return $type->addParentNodes(self::getGlobalReadNodes($taint_flow_graph, substr($var_id, 1)));
    }

    /**
     * Whether $var_id is a global: bound by `global`, or any variable at file scope (but not $a['k'], $a->p, ...,
     * which are part of $a, nor the superglobals).
     */
    private static function isBoundToGlobal(Context $context, string $var_id): bool
    {
        if (isset($context->by_ref_param_out_nodes[$var_id])) {
            return $context->by_ref_param_out_nodes[$var_id]->id
                === DataFlowNode::getForGlobalVariable(substr($var_id, 1))->id;
        }

        return $context->is_global
            && preg_match('/^\$[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/D', $var_id) === 1
            && !VariableFetchAnalyzer::isSuperGlobal($var_id);
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
