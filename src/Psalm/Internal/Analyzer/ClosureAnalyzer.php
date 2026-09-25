<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer;

use Override;
use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\Expression\AssignmentAnalyzer;
use Psalm\Internal\Codebase\CodeUseGraph;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\PhpVisitor\ShortClosureVisitor;
use Psalm\Issue\DuplicateParam;
use Psalm\Issue\ImpureByReferenceAssignment;
use Psalm\Issue\PossiblyUndefinedVariable;
use Psalm\Issue\UndefinedVariable;
use Psalm\IssueBuffer;
use Psalm\Storage\Capabilities;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;
use Psalm\Type;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Union;

use function in_array;
use function is_string;
use function preg_match;
use function str_starts_with;
use function strtolower;

/**
 * @internal
 * @extends FunctionLikeAnalyzer<PhpParser\Node\Expr\Closure|PhpParser\Node\Expr\ArrowFunction>
 */
final class ClosureAnalyzer extends FunctionLikeAnalyzer
{
    use UnserializeMemoryUsageSuppressionTrait;
    /**
     * @param PhpParser\Node\Expr\Closure|PhpParser\Node\Expr\ArrowFunction $function
     */
    public function __construct(PhpParser\Node\FunctionLike $function, SourceAnalyzer $source)
    {
        $codebase = $source->getCodebase();

        $function_id = strtolower($source->getFilePath())
            . ':' . $function->getLine()
            . ':' . (int)$function->getAttribute('startFilePos')
            . ':-:closure';

        $this->closure_id = $function_id;

        $storage = $codebase->getClosureStorage($source->getFilePath(), $function_id);

        parent::__construct($function, $source, $storage);
    }

    /** @var lowercase-string */
    private readonly string $closure_id;

    /**
     * @psalm-mutation-free
     */
    #[Override]
    public function getMutationNodeId(): string
    {
        return CodeUseGraph::functionLikeNode($this->closure_id);
    }

    /**
     * The variable this closure is assigned to and captures by reference, if
     * any: calls through it from the closure body are recursive calls.
     */
    /**
     * The variables captured by reference that the closure body writes.
     *
     * @var array<string, true>
     */
    public array $captured_by_ref_writes = [];

    public function getRecursiveVarId(): ?string
    {
        /** @var mixed $var_id */
        $var_id = $this->function->getAttribute('recursive_var_id');

        return is_string($var_id) ? $var_id : null;
    }


    /** @psalm-mutation-free */
    #[Override]
    public function getTemplateTypeMap(): ?array
    {
        return $this->source->getTemplateTypeMap();
    }

    /**
     * @return non-empty-lowercase-string
     */
    public function getClosureId(): string
    {
        return strtolower($this->getFilePath())
            . ':' . $this->function->getLine()
            . ':' . (int)$this->function->getAttribute('startFilePos')
            . ':-:closure';
    }

    /**
     * @param PhpParser\Node\Expr\Closure|PhpParser\Node\Expr\ArrowFunction $stmt
     */
    public static function analyzeExpression(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\FunctionLike $stmt,
        Context $context,
    ): bool {
        $closure_analyzer = new ClosureAnalyzer($stmt, $statements_analyzer);

        if ($stmt instanceof PhpParser\Node\Expr\Closure
            && self::analyzeClosureUses($statements_analyzer, $stmt, $context) === false
        ) {
            return false;
        }

        $use_context = new Context($context->self);

        $codebase = $statements_analyzer->getCodebase();

        if (!$statements_analyzer->isStatic() && !$closure_analyzer->isStatic()) {
            if ($context->collect_mutations &&
                $context->self &&
                $codebase->classExtends(
                    $context->self,
                    (string)$statements_analyzer->getFQCLN(),
                )
            ) {
                /** @psalm-suppress PossiblyUndefinedStringArrayOffset */
                $use_context->vars_in_scope['$this'] = $context->vars_in_scope['$this'];
            } elseif ($context->self) {
                $this_atomic = new TNamedObject($context->self, true);

                $use_context->vars_in_scope['$this'] = new Union([$this_atomic]);
            }
        }

        foreach ($context->vars_in_scope as $var => $type) {
            if (str_starts_with($var, '$this->')) {
                $use_context->vars_in_scope[$var] = $type;
            }
        }

        if ($context->self) {
            $self_class_storage = $codebase->classlike_storage_provider->get($context->self);

            ClassAnalyzer::addContextProperties(
                $statements_analyzer,
                $self_class_storage,
                $use_context,
                $context->self,
                $statements_analyzer->getParentFQCLN(),
            );
        }

        foreach ($context->vars_possibly_in_scope as $var => $_) {
            if (str_starts_with($var, '$this->')) {
                $use_context->vars_possibly_in_scope[$var] = true;
            }
        }

        $was_by_ref = [];

        if ($stmt instanceof PhpParser\Node\Expr\Closure) {
            foreach ($stmt->uses as $use) {
                if (!is_string($use->var->name)) {
                    continue;
                }

                $use_var_id = '$' . $use->var->name;

                if ($statements_analyzer->variable_use_graph
                    && $context->hasVariable($use_var_id)
                ) {
                    $parent_nodes = $context->vars_in_scope[$use_var_id]->parent_nodes;

                    foreach ($parent_nodes as $parent_node) {
                        $statements_analyzer->variable_use_graph->addPath(
                            $parent_node,
                            DataFlowNode::getForClosureUse(),
                            'closure-use',
                        );
                    }
                }

                $use_context->vars_in_scope[$use_var_id] =
                    $context->hasVariable($use_var_id)
                    ? $context->vars_in_scope[$use_var_id]
                    : Type::getMixed();

                if ($use->byRef) {
                    $was_by_ref[$use_var_id] = $context->hasVariable($use_var_id)
                        && $context->vars_in_scope[$use_var_id]->by_ref;

                    $use_context->vars_in_scope[$use_var_id] =
                        $use_context->vars_in_scope[$use_var_id]->setProperties(['by_ref' => true]);
                    $use_context->references_to_external_scope[$use_var_id] = true;

                    // shared with the enclosing scope, plus what that scope needs to write it
                    $use_context->captured_by_ref[$use_var_id]
                        = AssignmentAnalyzer::getExternalWriteCapabilities($context, $use_var_id);
                }

                $use_context->vars_possibly_in_scope[$use_var_id] = true;

                foreach ($context->vars_in_scope as $var_id => $type) {
                    if (preg_match('/^\$' . $use->var->name . '[\[\-]/', $var_id)) {
                        $use_context->vars_in_scope[$var_id] = $type;
                        $use_context->vars_possibly_in_scope[$var_id] = true;
                    }
                }
            }
        } else {
            $traverser = new PhpParser\NodeTraverser;

            $short_closure_visitor = new ShortClosureVisitor();

            $traverser->addVisitor($short_closure_visitor);
            $traverser->traverse($stmt->getStmts());

            foreach ($short_closure_visitor->getUsedVariables() as $use_var_id => $_) {
                if ($context->hasVariable($use_var_id)) {
                    $use_context->vars_in_scope[$use_var_id] = $context->vars_in_scope[$use_var_id];

                    if ($statements_analyzer->variable_use_graph) {
                        $parent_nodes = $context->vars_in_scope[$use_var_id]->parent_nodes;

                        foreach ($parent_nodes as $parent_node) {
                            $statements_analyzer->variable_use_graph->addPath(
                                $parent_node,
                                DataFlowNode::getForClosureUse(),
                                'closure-use',
                            );
                        }
                    }
                }

                $use_context->vars_possibly_in_scope[$use_var_id] = true;
            }
        }

        $use_context->calling_method_id = $context->calling_method_id;
        $use_context->calling_function_id = $context->calling_function_id;
        $use_context->phantom_classes = $context->phantom_classes;

        $byref_vars = [];
        $closure_analyzer->analyze($use_context, $statements_analyzer->node_data, $context, false, $byref_vars);

        foreach ($byref_vars as $key => $value) {
            // the variable is shared with the closure, but is still this scope's own unless it
            // was a reference already
            $context->vars_in_scope[$key] = $value->setByRef($was_by_ref[$key] ?? false);
        }

        // the closure writes variables it shares with this scope: when such a variable is
        // itself shared with somewhere else, this scope is what lets the closure write there
        foreach ($closure_analyzer->captured_by_ref_writes as $var_id => $_) {
            $required = $use_context->captured_by_ref[$var_id] ?? Capabilities::NONE;

            if ($required === Capabilities::NONE) {
                continue;
            }

            $statements_analyzer->signalMutation(
                $required,
                $context,
                'variable ' . $var_id . ' captured by reference, written by the closure,',
                ImpureByReferenceAssignment::class,
                $stmt,
            );
        }
        
        // creating a closure is not an effect: its capabilities are carried by its type and are
        // required where it is called or passed. Only the purity inference of the enclosing
        // function-like still follows the closure's final level, as if it were called.
        $statements_analyzer->signalMutationOnlyInferred(
            Capabilities::NONE,
            $closure_analyzer->storage,
            false,
            $closure_analyzer->getMutationNodeId(),
        );

        if (!$statements_analyzer->node_data->getType($stmt)) {
            $statements_analyzer->node_data->setType($stmt, Type::getClosure());
        }

        return true;
    }

    /**
     * @return  false|null
     */
    private static function analyzeClosureUses(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Closure $stmt,
        Context $context,
    ): ?bool {
        $param_names = [];

        foreach ($stmt->params as $i => $param) {
            if ($param->var instanceof PhpParser\Node\Expr\Variable && is_string($param->var->name)) {
                $param_names[$i] = $param->var->name;
            } else {
                $param_names[$i] = '';
            }
        }

        foreach ($stmt->uses as $use) {
            if (!is_string($use->var->name)) {
                continue;
            }

            $use_var_id = '$' . $use->var->name;

            if (in_array($use->var->name, $param_names)) {
                if (IssueBuffer::accepts(
                    new DuplicateParam(
                        'Closure use duplicates param name ' . $use_var_id,
                        new CodeLocation($statements_analyzer->getSource(), $use->var),
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                )) {
                    return false;
                }
            }

            if (!$context->hasVariable($use_var_id)) {
                if ($use_var_id === '$argv' || $use_var_id === '$argc') {
                    continue;
                }

                if (!isset($context->vars_possibly_in_scope[$use_var_id])) {
                    if ($context->check_variables) {
                        if (IssueBuffer::accepts(
                            new UndefinedVariable(
                                'Cannot find referenced variable ' . $use_var_id,
                                new CodeLocation($statements_analyzer->getSource(), $use->var),
                            ),
                            $statements_analyzer->getSuppressedIssues(),
                        )) {
                            return false;
                        }

                        return null;
                    }
                }

                $first_appearance = $statements_analyzer->getFirstAppearance($use_var_id);

                if ($first_appearance) {
                    if (IssueBuffer::accepts(
                        new PossiblyUndefinedVariable(
                            'Possibly undefined variable ' . $use_var_id . ', first seen on line ' .
                                $first_appearance->getLineNumber(),
                            new CodeLocation($statements_analyzer->getSource(), $use->var),
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    )) {
                        return false;
                    }

                    continue;
                }

                if ($context->check_variables) {
                    if (IssueBuffer::accepts(
                        new UndefinedVariable(
                            'Cannot find referenced variable ' . $use_var_id,
                            new CodeLocation($statements_analyzer->getSource(), $use->var),
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    )) {
                        return false;
                    }

                    continue;
                }
            }
        }

        return null;
    }
}
