<?php
namespace Psalm\Internal\Analyzer\Statements\Expression\Call;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\Expression\AssertionFinder;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\ConstFetchAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Issue\ForbiddenCode;
use Psalm\IssueBuffer;
use Psalm\Node\Expr\VirtualArray;
use Psalm\Node\Expr\VirtualArrayItem;
use Psalm\Node\Expr\VirtualVariable;
use Psalm\Node\Scalar\VirtualString;
use Psalm\Type;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Reconciler;

use function array_map;
use function extension_loaded;
use function implode;
use function is_string;
use function strpos;
use function strtolower;

/**
 * @internal
 */
class NamedFunctionCallHandler
{
    /**
     * @param  lowercase-string  $function_id
     */
    public static function handle(
        StatementsAnalyzer $statements_analyzer,
        \Psalm\Codebase $codebase,
        PhpParser\Node\Expr\FuncCall $stmt,
        PhpParser\Node\Expr\FuncCall $real_stmt,
        PhpParser\Node\Name $function_name,
        ?string $function_id,
        Context $context
    ) : void {
        if ($function_id === 'get_class'
            || $function_id === 'gettype'
            || $function_id === 'get_debug_type'
        ) {
            self::handleDependentTypeFunction(
                $statements_analyzer,
                $stmt,
                $real_stmt,
                $function_id,
                $context
            );

            return;
        }

        $first_arg = isset($stmt->args[0]) ? $stmt->args[0] : null;

        if ($function_id === 'method_exists') {
            $second_arg = isset($stmt->args[1]) ? $stmt->args[1] : null;

            if ($first_arg
                && $first_arg->value instanceof PhpParser\Node\Expr\Variable
                && $second_arg
                && $second_arg->value instanceof PhpParser\Node\Scalar\String_
            ) {
                // do nothing
            } else {
                $context->check_methods = false;
            }

            return;
        }

        if ($function_id === 'class_exists') {
            if ($first_arg) {
                if ($first_arg->value instanceof PhpParser\Node\Scalar\String_) {
                    if (!$codebase->classlikes->classExists($first_arg->value->value)) {
                        $context->phantom_classes[strtolower($first_arg->value->value)] = true;
                    }
                } elseif ($first_arg->value instanceof PhpParser\Node\Expr\ClassConstFetch
                    && $first_arg->value->class instanceof PhpParser\Node\Name
                    && $first_arg->value->name instanceof PhpParser\Node\Identifier
                    && $first_arg->value->name->name === 'class'
                ) {
                    $resolved_name = (string) $first_arg->value->class->getAttribute('resolvedName');

                    if (!$codebase->classlikes->classExists($resolved_name)) {
                        $context->phantom_classes[strtolower($resolved_name)] = true;
                    }
                }
            }

            return;
        }

        if ($function_id === 'interface_exists') {
            if ($first_arg) {
                if ($first_arg->value instanceof PhpParser\Node\Scalar\String_) {
                    $context->phantom_classes[strtolower($first_arg->value->value)] = true;
                } elseif ($first_arg->value instanceof PhpParser\Node\Expr\ClassConstFetch
                    && $first_arg->value->class instanceof PhpParser\Node\Name
                    && $first_arg->value->name instanceof PhpParser\Node\Identifier
                    && $first_arg->value->name->name === 'class'
                ) {
                    $resolved_name = (string) $first_arg->value->class->getAttribute('resolvedName');

                    if (!$codebase->classlikes->interfaceExists($resolved_name)) {
                        $context->phantom_classes[strtolower($resolved_name)] = true;
                    }
                }
            }

            return;
        }

        if (\in_array($function_id, ['is_file', 'file_exists']) && $first_arg) {
            $var_id = ExpressionIdentifier::getArrayVarId($first_arg->value, null);

            if ($var_id) {
                $context->phantom_files[$var_id] = true;
            }

            return;
        }

        if ($function_id === 'extension_loaded') {
            if ($first_arg
                && $first_arg->value instanceof PhpParser\Node\Scalar\String_
            ) {
                if (@extension_loaded($first_arg->value->value)) {
                    // do nothing
                } else {
                    $context->check_classes = false;
                }
            }

            return;
        }

        if ($function_id === 'function_exists') {
            $context->check_functions = false;
            return;
        }

        if ($function_id === 'is_callable') {
            $context->check_methods = false;
            $context->check_functions = false;
            return;
        }

        if ($function_id === 'defined') {
            $context->check_consts = false;
            return;
        }

        if ($function_id === 'extract') {
            $context->check_variables = false;

            foreach ($context->vars_in_scope as $var_id => $_) {
                if ($var_id === '$this' || strpos($var_id, '[') || strpos($var_id, '>')) {
                    continue;
                }

                $mixed_type = Type::getMixed();
                $mixed_type->parent_nodes = $context->vars_in_scope[$var_id]->parent_nodes;

                $context->vars_in_scope[$var_id] = $mixed_type;
                $context->assigned_var_ids[$var_id] = (int) $stmt->getAttribute('startFilePos');
                $context->possibly_assigned_var_ids[$var_id] = true;
            }

            return;
        }

        if ($function_id === 'compact') {
            $all_args_string_literals = true;
            $new_items = [];

            foreach ($stmt->args as $arg) {
                $arg_type = $statements_analyzer->node_data->getType($arg->value);

                if (!$arg_type || !$arg_type->isSingleStringLiteral()) {
                    $all_args_string_literals = false;
                    break;
                }

                $var_name = $arg_type->getSingleStringLiteral()->value;

                $new_items[] = new VirtualArrayItem(
                    new VirtualVariable($var_name, $arg->value->getAttributes()),
                    new VirtualString($var_name, $arg->value->getAttributes()),
                    false,
                    $arg->getAttributes()
                );
            }

            if ($all_args_string_literals) {
                $arr = new VirtualArray($new_items, $stmt->getAttributes());
                $old_node_data = $statements_analyzer->node_data;
                $statements_analyzer->node_data = clone $statements_analyzer->node_data;

                ExpressionAnalyzer::analyze($statements_analyzer, $arr, $context);

                $arr_type = $statements_analyzer->node_data->getType($arr);

                $statements_analyzer->node_data = $old_node_data;

                if ($arr_type) {
                    $statements_analyzer->node_data->setType($stmt, $arr_type);
                }
            }

            return;
        }

        if ($function_id === 'func_get_args') {
            $source = $statements_analyzer->getSource();

            if ($statements_analyzer->data_flow_graph
                && $source instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer
            ) {
                if ($statements_analyzer->data_flow_graph instanceof \Psalm\Internal\Codebase\VariableUseGraph) {
                    foreach ($source->param_nodes as $param_node) {
                        $statements_analyzer->data_flow_graph->addPath(
                            $param_node,
                            new DataFlowNode('variable-use', 'variable use', null),
                            'variable-use'
                        );
                    }
                }
            }

            return;
        }

        if ($function_id === 'var_dump'
            || $function_id === 'shell_exec'
        ) {
            if (IssueBuffer::accepts(
                new ForbiddenCode(
                    'Unsafe ' . implode('', $function_name->parts),
                    new CodeLocation($statements_analyzer->getSource(), $stmt)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // continue
            }
        }

        if (isset($codebase->config->forbidden_functions[strtolower((string) $function_name)])) {
            if (IssueBuffer::accepts(
                new ForbiddenCode(
                    'You have forbidden the use of ' . $function_name,
                    new CodeLocation($statements_analyzer->getSource(), $stmt)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // continue
            }

            return;
        }

        if ($function_id === 'define') {
            if ($first_arg) {
                $fq_const_name = ConstFetchAnalyzer::getConstName(
                    $first_arg->value,
                    $statements_analyzer->node_data,
                    $codebase,
                    $statements_analyzer->getAliases()
                );

                if ($fq_const_name !== null && isset($stmt->args[1])) {
                    $second_arg = $stmt->args[1];
                    $was_in_call = $context->inside_call;
                    $context->inside_call = true;
                    ExpressionAnalyzer::analyze($statements_analyzer, $second_arg->value, $context);
                    $context->inside_call = $was_in_call;

                    ConstFetchAnalyzer::setConstType(
                        $statements_analyzer,
                        $fq_const_name,
                        $statements_analyzer->node_data->getType($second_arg->value) ?: Type::getMixed(),
                        $context
                    );
                }
            } else {
                $context->check_consts = false;
            }

            return;
        }

        if ($function_id === 'constant') {
            if ($first_arg) {
                $fq_const_name = ConstFetchAnalyzer::getConstName(
                    $first_arg->value,
                    $statements_analyzer->node_data,
                    $codebase,
                    $statements_analyzer->getAliases()
                );

                if ($fq_const_name !== null) {
                    $const_type = ConstFetchAnalyzer::getConstType(
                        $statements_analyzer,
                        $fq_const_name,
                        true,
                        $context
                    );

                    if ($const_type) {
                        $statements_analyzer->node_data->setType($real_stmt, $const_type);
                    }
                }
            } else {
                $context->check_consts = false;
            }
        }

        if ($first_arg
            && $function_id
            && strpos($function_id, 'is_') === 0
            && $function_id !== 'is_a'
            && !$context->inside_negation
        ) {
            $stmt_assertions = $statements_analyzer->node_data->getAssertions($stmt);

            if ($stmt_assertions !== null) {
                $anded_assertions = $stmt_assertions;
            } else {
                $anded_assertions = AssertionFinder::processFunctionCall(
                    $stmt,
                    $context->self,
                    $statements_analyzer,
                    $codebase,
                    $context->inside_negation
                );
            }

            $changed_vars = [];

            foreach ($anded_assertions as $assertions) {
                $referenced_var_ids = array_map(
                    function (array $_) : bool {
                        return true;
                    },
                    $assertions
                );

                Reconciler::reconcileKeyedTypes(
                    $assertions,
                    $assertions,
                    $context->vars_in_scope,
                    $changed_vars,
                    $referenced_var_ids,
                    $statements_analyzer,
                    [],
                    $context->inside_loop,
                    new CodeLocation($statements_analyzer->getSource(), $stmt)
                );
            }

            return;
        }

        if ($first_arg && $function_id === 'strtolower') {
            $first_arg_type = $statements_analyzer->node_data->getType($first_arg->value);

            if ($first_arg_type
                && UnionTypeComparator::isContainedBy(
                    $codebase,
                    $first_arg_type,
                    new Type\Union([new Type\Atomic\TLowercaseString()])
                )
            ) {
                if ($first_arg_type->from_docblock) {
                    if (IssueBuffer::accepts(
                        new \Psalm\Issue\RedundantCastGivenDocblockType(
                            'The call to strtolower is unnecessary given the docblock type',
                            new CodeLocation($statements_analyzer, $function_name)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new \Psalm\Issue\RedundantCast(
                            'The call to strtolower is unnecessary',
                            new CodeLocation($statements_analyzer, $function_name)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            }
        }

        if ($first_arg
            && ($function_id === 'array_walk'
                || $function_id === 'array_walk_recursive'
            )
        ) {
            $first_arg_type = $statements_analyzer->node_data->getType($first_arg->value);

            if ($first_arg_type && $first_arg_type->hasObjectType()) {
                if ($first_arg_type->isSingle()) {
                    if (IssueBuffer::accepts(
                        new \Psalm\Issue\RawObjectIteration(
                            'Possibly undesired iteration over object properties',
                            new CodeLocation($statements_analyzer, $function_name)
                        )
                    )) {
                        // fall through
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new \Psalm\Issue\PossibleRawObjectIteration(
                            'Possibly undesired iteration over object properties',
                            new CodeLocation($statements_analyzer, $function_name)
                        )
                    )) {
                        // fall through
                    }
                }
            }
        }
    }

    private static function handleDependentTypeFunction(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\FuncCall $stmt,
        PhpParser\Node\Expr\FuncCall $real_stmt,
        ?string $function_id,
        Context $context
    ) : void {
        $first_arg = isset($stmt->args[0]) ? $stmt->args[0] : null;

        if ($first_arg) {
            $var = $first_arg->value;

            if ($var instanceof PhpParser\Node\Expr\Variable
                && is_string($var->name)
            ) {
                $var_id = '$' . $var->name;

                if (isset($context->vars_in_scope[$var_id])) {
                    if (!$context->vars_in_scope[$var_id]->hasTemplate()) {
                        if ($function_id === 'get_class') {
                            $atomic_type = new Type\Atomic\TDependentGetClass(
                                $var_id,
                                $context->vars_in_scope[$var_id]->hasMixed()
                                    ? Type::getObject()
                                    : $context->vars_in_scope[$var_id]
                            );
                        } elseif ($function_id === 'gettype') {
                            $atomic_type = new Type\Atomic\TDependentGetType($var_id);
                        } else {
                            $atomic_type = new Type\Atomic\TDependentGetDebugType($var_id);
                        }

                        $statements_analyzer->node_data->setType($real_stmt, new Type\Union([$atomic_type]));

                        return;
                    }
                }
            }

            if (($var_type = $statements_analyzer->node_data->getType($var))
                && ($function_id === 'get_class'
                    || $function_id === 'get_debug_type'
                )
            ) {
                $class_string_types = [];

                foreach ($var_type->getAtomicTypes() as $class_type) {
                    if ($class_type instanceof Type\Atomic\TNamedObject) {
                        $class_string_types[] = new Type\Atomic\TClassString($class_type->value, clone $class_type);
                    } elseif ($class_type instanceof Type\Atomic\TTemplateParam
                        && $class_type->as->isSingle()
                    ) {
                        $as_atomic_type = \array_values($class_type->as->getAtomicTypes())[0];

                        if ($as_atomic_type instanceof Type\Atomic\TObject) {
                            $class_string_types[] = new Type\Atomic\TTemplateParamClass(
                                $class_type->param_name,
                                'object',
                                null,
                                $class_type->defining_class
                            );
                        } elseif ($as_atomic_type instanceof TNamedObject) {
                            $class_string_types[] = new Type\Atomic\TTemplateParamClass(
                                $class_type->param_name,
                                $as_atomic_type->value,
                                $as_atomic_type,
                                $class_type->defining_class
                            );
                        }
                    } elseif ($function_id === 'get_class') {
                        $class_string_types[] = new Type\Atomic\TClassString();
                    } else {
                        if ($class_type instanceof Type\Atomic\TInt) {
                            $class_string_types[] = new Type\Atomic\TLiteralString('int');
                        } elseif ($class_type instanceof Type\Atomic\TString) {
                            $class_string_types[] = new Type\Atomic\TLiteralString('string');
                        } elseif ($class_type instanceof Type\Atomic\TFloat) {
                            $class_string_types[] = new Type\Atomic\TLiteralString('float');
                        } elseif ($class_type instanceof Type\Atomic\TBool) {
                            $class_string_types[] = new Type\Atomic\TLiteralString('bool');
                        } elseif ($class_type instanceof Type\Atomic\TClosedResource) {
                            $class_string_types[] = new Type\Atomic\TLiteralString('resource (closed)');
                        } elseif ($class_type instanceof Type\Atomic\TNull) {
                            $class_string_types[] = new Type\Atomic\TLiteralString('null');
                        } else {
                            $class_string_types[] = new Type\Atomic\TString();
                        }
                    }
                }

                if ($class_string_types) {
                    $statements_analyzer->node_data->setType($real_stmt, new Type\Union($class_string_types));
                }
            }
        } elseif ($function_id === 'get_class'
            && ($get_class_name = $statements_analyzer->getFQCLN())
        ) {
            $statements_analyzer->node_data->setType(
                $real_stmt,
                new Type\Union([
                    new Type\Atomic\TClassString(
                        $get_class_name,
                        new Type\Atomic\TNamedObject($get_class_name)
                    )
                ])
            );
        }
    }
}
