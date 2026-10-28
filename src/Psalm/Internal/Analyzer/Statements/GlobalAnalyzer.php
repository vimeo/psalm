<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements;

use InvalidArgumentException;
use PhpParser;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Internal\Analyzer\CommentAnalyzer;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\VariableFetchAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\TaintFlowGraph;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\ReferenceConstraint;
use Psalm\Issue\ImpureGlobalVariable;
use Psalm\Issue\InvalidGlobal;
use Psalm\IssueBuffer;
use Psalm\Storage\Capabilities;
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Type\Union;
use UnexpectedValueException;
use WeakMap;

use function array_pop;
use function explode;
use function is_string;
use function preg_match;
use function substr;

/**
 * @internal
 */
final class GlobalAnalyzer
{
    private const VARIABLE_ID_PATTERN = '/^\$[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/D';

    /**
     * @see getGlobalsWrittenBy()
     * @var ?WeakMap<FunctionLikeStorage, array<string, bool>>
     */
    private static ?WeakMap $globals_written_by = null;

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

                if ($var_id === '$argv') {
                    $taint_flow_graph->addPath(
                        VariableFetchAnalyzer::getArgvTaintSource($statements_analyzer, $taint_flow_graph, $var),
                        $assignment_node,
                        '=',
                    );
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
     * What the globals bound in this scope hold after a call of the function-like of $storage, which may have
     * written to them, or the function-likes it calls may have (see getGlobalsWrittenBy()): whatever the program
     * writes to them. A variable of the scope that isn't bound to a global is left alone.
     */
    public static function readGlobalsWrittenBy(
        StatementsAnalyzer $statements_analyzer,
        Context $context,
        FunctionLikeStorage $storage,
    ): void {
        if (!$context->is_global && !$context->references_to_external_scope) {
            return;
        }

        $analysis_php_version_id = $statements_analyzer->getCodebase()->analysis_php_version_id;

        foreach (self::getGlobalsWrittenBy($statements_analyzer, $storage) as $var_id => $_) {
            if ((!$context->is_global && !isset($context->references_to_external_scope[$var_id]))
                || preg_match(self::VARIABLE_ID_PATTERN, $var_id) !== 1
                || VariableFetchAnalyzer::isSuperGlobal($var_id)
            ) {
                continue;
            }

            $context->vars_in_scope[$var_id] = self::taintGlobalRead(
                $statements_analyzer,
                $context,
                $var_id,
                VariableFetchAnalyzer::getGlobalType($var_id, $analysis_php_version_id),
            );
            $context->vars_possibly_in_scope[$var_id] = true;
        }
    }

    /**
     * The globals the body of the function-like of $storage, or of those it calls by name, binds or writes
     * through $GLOBALS (see FunctionLikeStorage::$global_variables), by variable id
     *
     * @return array<string, bool>
     */
    private static function getGlobalsWrittenBy(
        StatementsAnalyzer $statements_analyzer,
        FunctionLikeStorage $storage,
    ): array {
        if (!$storage->called_function_like_ids) {
            return $storage->global_variables;
        }

        if (self::$globals_written_by === null) {
            /** @var WeakMap<FunctionLikeStorage, array<string, bool>> */
            self::$globals_written_by = new WeakMap();
        }

        $memoized = self::$globals_written_by[$storage] ?? null;

        if ($memoized !== null) {
            return $memoized;
        }

        $codebase = $statements_analyzer->getCodebase();
        $global_variables = [];
        $seen = new WeakMap();
        $seen[$storage] = true;
        $queue = [$storage];

        while ($queue) {
            $function_like_storage = array_pop($queue);
            $global_variables += $function_like_storage->global_variables;

            foreach ($function_like_storage->called_function_like_ids as $function_like_id => $_) {
                $callee_storage = self::getCalleeStorage(
                    $codebase,
                    $statements_analyzer,
                    $function_like_storage,
                    $function_like_id,
                );

                if ($callee_storage !== null && !isset($seen[$callee_storage])) {
                    $seen[$callee_storage] = true;
                    $queue[] = $callee_storage;
                }
            }
        }

        self::$globals_written_by[$storage] = $global_variables;

        return $global_variables;
    }

    /**
     * A function is resolved as the file declaring the function-like calling it sees it, not as the file being
     * analyzed does: what a function-like writes is memoized, so it can't depend on the file that asks first.
     *
     * @param lowercase-string $function_like_id
     */
    private static function getCalleeStorage(
        Codebase $codebase,
        StatementsAnalyzer $statements_analyzer,
        FunctionLikeStorage $caller_storage,
        string $function_like_id,
    ): ?FunctionLikeStorage {
        if ($function_like_id === '') {
            return null;
        }

        $parts = explode('::', $function_like_id, 2);

        if (!isset($parts[1])) {
            $caller_file_path = $caller_storage->location?->file_path;

            try {
                return $caller_file_path !== null
                    ? $codebase->functions->getStorage(null, $function_like_id, $caller_file_path, $caller_file_path)
                    : $codebase->functions->getStorage($statements_analyzer, $function_like_id);
            } catch (UnexpectedValueException | InvalidArgumentException) {
                return null;
            }
        }

        [$fq_class_name, $method_name] = $parts;

        if (!$codebase->classlike_storage_provider->has($fq_class_name)) {
            return null;
        }

        $declaring_method_id = $codebase->methods->getDeclaringMethodId(
            new MethodIdentifier($fq_class_name, $method_name),
        );

        return $declaring_method_id ? $codebase->methods->getStorage($declaring_method_id) : null;
    }

    /**
     * Whether $var_id is a global: bound by `global`, or any variable at file scope (but not $a['k'], $a->p, ...,
     * which are part of $a, nor the superglobals).
     *
     * @psalm-capabilities read-props
     */
    private static function isBoundToGlobal(Context $context, string $var_id): bool
    {
        if (isset($context->by_ref_param_out_nodes[$var_id])) {
            return $context->by_ref_param_out_nodes[$var_id]->id
                === DataFlowNode::getForGlobalVariable(substr($var_id, 1))->id;
        }

        return $context->is_global
            && preg_match(self::VARIABLE_ID_PATTERN, $var_id) === 1
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
