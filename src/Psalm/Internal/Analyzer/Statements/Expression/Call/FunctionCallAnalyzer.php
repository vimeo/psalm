<?php
namespace Psalm\Internal\Analyzer\Statements\Expression\Call;

use PhpParser;
use Psalm\Internal\Analyzer\FunctionAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\CallAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\AssertionFinder;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\ConstFetchAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\Comparator\CallableTypeComparator;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Internal\Codebase\InternalCallMapHandler;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;
use Psalm\Internal\Taint\Source;
use Psalm\Internal\Taint\TaintNode;
use Psalm\Issue\DeprecatedFunction;
use Psalm\Issue\ForbiddenCode;
use Psalm\Issue\MixedFunctionCall;
use Psalm\Issue\InvalidFunctionCall;
use Psalm\Issue\ImpureFunctionCall;
use Psalm\Issue\NullFunctionCall;
use Psalm\Issue\PossiblyInvalidFunctionCall;
use Psalm\Issue\PossiblyNullFunctionCall;
use Psalm\Issue\UnusedFunctionCall;
use Psalm\IssueBuffer;
use Psalm\Storage\Assertion;
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Type;
use Psalm\Type\Atomic\TCallableObject;
use Psalm\Type\Atomic\TCallableString;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Algebra;
use Psalm\Type\Reconciler;
use function count;
use function in_array;
use function reset;
use function implode;
use function strtolower;
use function array_merge;
use function is_string;
use function array_map;
use function extension_loaded;
use function strpos;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Storage\FunctionLikeParameter;
use function explode;

/**
 * @internal
 */
class FunctionCallAnalyzer extends CallAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\FuncCall $stmt,
        Context $context
    ) : bool {
        $function_name = $stmt->name;

        $function_id = null;
        $function_params = null;
        $in_call_map = false;

        $is_stubbed = false;

        $function_storage = null;

        $codebase = $statements_analyzer->getCodebase();

        $code_location = new CodeLocation($statements_analyzer->getSource(), $stmt);
        $codebase_functions = $codebase->functions;
        $config = $codebase->config;
        $defined_constants = [];
        $global_variables = [];

        $function_exists = false;

        $real_stmt = $stmt;

        if ($function_name instanceof PhpParser\Node\Name
            && isset($stmt->args[0])
            && !$stmt->args[0]->unpack
        ) {
            $original_function_id = implode('\\', $function_name->parts);

            if ($original_function_id === 'call_user_func') {
                $other_args = \array_slice($stmt->args, 1);

                $function_name = $stmt->args[0]->value;

                $stmt = new PhpParser\Node\Expr\FuncCall(
                    $function_name,
                    $other_args,
                    $stmt->getAttributes()
                );
            }

            if ($original_function_id === 'call_user_func_array' && isset($stmt->args[1])) {
                $function_name = $stmt->args[0]->value;

                $stmt = new PhpParser\Node\Expr\FuncCall(
                    $function_name,
                    [new PhpParser\Node\Arg($stmt->args[1]->value, false, true)],
                    $stmt->getAttributes()
                );
            }
        }

        $byref_uses = [];

        if ($function_name instanceof PhpParser\Node\Expr) {
            list($expr_function_exists, $expr_function_name, $expr_function_params, $byref_uses)
                = self::getAnalyzeNamedExpression(
                    $statements_analyzer,
                    $codebase,
                    $stmt,
                    $real_stmt,
                    $function_name,
                    $context
                );

            if ($expr_function_exists === false) {
                return true;
            }

            if ($expr_function_exists === true) {
                $function_exists = true;
            }

            if ($expr_function_name) {
                $function_name = $expr_function_name;
            }

            if ($expr_function_params) {
                $function_params = $expr_function_params;
            }
        } else {
            $original_function_id = implode('\\', $function_name->parts);

            if (!$function_name instanceof PhpParser\Node\Name\FullyQualified) {
                $function_id = $codebase_functions->getFullyQualifiedFunctionNameFromString(
                    $original_function_id,
                    $statements_analyzer
                );
            } else {
                $function_id = $original_function_id;
            }

            $namespaced_function_exists = $codebase_functions->functionExists(
                $statements_analyzer,
                strtolower($function_id)
            );

            if (!$namespaced_function_exists
                && !$function_name instanceof PhpParser\Node\Name\FullyQualified
            ) {
                $in_call_map = InternalCallMapHandler::inCallMap($original_function_id);
                $is_stubbed = $codebase_functions->hasStubbedFunction($original_function_id);

                if ($is_stubbed || $in_call_map) {
                    $function_id = $original_function_id;
                }
            } else {
                $in_call_map = InternalCallMapHandler::inCallMap($function_id);
                $is_stubbed = $codebase_functions->hasStubbedFunction($function_id);
            }

            if ($is_stubbed || $in_call_map || $namespaced_function_exists) {
                $function_exists = true;
            }

            if ($function_exists
                && $codebase->store_node_types
                && !$context->collect_initializations
                && !$context->collect_mutations
            ) {
                ArgumentMapPopulator::recordArgumentPositions(
                    $statements_analyzer,
                    $stmt,
                    $codebase,
                    $function_id
                );
            }

            $is_predefined = true;

            $is_maybe_root_function = !$function_name instanceof PhpParser\Node\Name\FullyQualified
                && count($function_name->parts) === 1;

            if (!$in_call_map) {
                $predefined_functions = $config->getPredefinedFunctions();
                $is_predefined = isset($predefined_functions[strtolower($original_function_id)])
                    || isset($predefined_functions[strtolower($function_id)]);

                if ($context->check_functions) {
                    if (self::checkFunctionExists(
                        $statements_analyzer,
                        $function_id,
                        $code_location,
                        $is_maybe_root_function
                    ) === false
                    ) {
                        if (ArgumentsAnalyzer::analyze(
                            $statements_analyzer,
                            $stmt->args,
                            null,
                            null,
                            $context
                        ) === false) {
                            // fall through
                        }

                        return true;
                    }
                }
            } else {
                $function_exists = true;
            }

            if ($function_exists) {
                $function_params = null;

                if ($codebase->functions->params_provider->has($function_id)) {
                    $function_params = $codebase->functions->params_provider->getFunctionParams(
                        $statements_analyzer,
                        $function_id,
                        $stmt->args
                    );
                }

                if ($function_params === null) {
                    if (!$in_call_map || $is_stubbed) {
                        try {
                            $function_storage = $codebase_functions->getStorage(
                                $statements_analyzer,
                                strtolower($function_id)
                            );

                            $function_params = $function_storage->params;

                            if (!$is_predefined) {
                                $defined_constants = $function_storage->defined_constants;
                                $global_variables = $function_storage->global_variables;
                            }
                        } catch (\UnexpectedValueException $e) {
                            $function_params = [
                                new FunctionLikeParameter('args', false, null, null, null, false, false, true)
                            ];
                        }
                    } else {
                        $function_callable = InternalCallMapHandler::getCallableFromCallMapById(
                            $codebase,
                            $function_id,
                            $stmt->args,
                            $statements_analyzer->node_data
                        );

                        $function_params = $function_callable->params;
                    }
                }

                if ($codebase->store_node_types
                    && !$context->collect_initializations
                    && !$context->collect_mutations
                ) {
                    $codebase->analyzer->addNodeReference(
                        $statements_analyzer->getFilePath(),
                        $function_name,
                        $function_id . '()'
                    );
                }
            }
        }

        $set_inside_conditional = false;

        if ($function_name instanceof PhpParser\Node\Name
            && $function_name->parts === ['assert']
            && !$context->inside_conditional
        ) {
            $context->inside_conditional = true;
            $set_inside_conditional = true;
        }

        if (ArgumentsAnalyzer::analyze(
            $statements_analyzer,
            $stmt->args,
            $function_params,
            $function_id,
            $context
        ) === false) {
            // fall through
        }

        if ($set_inside_conditional) {
            $context->inside_conditional = false;
        }

        $template_result = null;

        if ($function_exists) {
            if ($function_name instanceof PhpParser\Node\Name && $function_id) {
                if (!$is_stubbed && $in_call_map) {
                    $function_callable = \Psalm\Internal\Codebase\InternalCallMapHandler::getCallableFromCallMapById(
                        $codebase,
                        $function_id,
                        $stmt->args,
                        $statements_analyzer->node_data
                    );

                    $function_params = $function_callable->params;
                }
            }

            $template_result = new TemplateResult([], []);

            // do this here to allow closure param checks
            if ($function_params !== null
                && ArgumentsAnalyzer::checkArgumentsMatch(
                    $statements_analyzer,
                    $stmt->args,
                    $function_id,
                    $function_params,
                    $function_storage,
                    null,
                    $template_result,
                    $code_location,
                    $context
                ) === false
            ) {
                // fall through
            }

            CallAnalyzer::checkTemplateResult(
                $statements_analyzer,
                $template_result,
                $code_location,
                $function_id
            );

            if ($function_name instanceof PhpParser\Node\Name && $function_id) {
                $stmt_type = self::getFunctionCallReturnType(
                    $statements_analyzer,
                    $codebase,
                    $stmt,
                    $function_name,
                    $function_id,
                    $in_call_map,
                    $is_stubbed,
                    $function_storage,
                    $template_result,
                    $context
                );

                $statements_analyzer->node_data->setType($real_stmt, $stmt_type);

                if ($config->after_every_function_checks) {
                    foreach ($config->after_every_function_checks as $plugin_fq_class_name) {
                        $plugin_fq_class_name::afterEveryFunctionCallAnalysis(
                            $stmt,
                            $function_id,
                            $context,
                            $statements_analyzer->getSource(),
                            $codebase
                        );
                    }
                }
            }

            foreach ($defined_constants as $const_name => $const_type) {
                $context->constants[$const_name] = clone $const_type;
                $context->vars_in_scope[$const_name] = clone $const_type;
            }

            foreach ($global_variables as $var_id => $_) {
                $context->vars_in_scope[$var_id] = Type::getMixed();
                $context->vars_possibly_in_scope[$var_id] = true;
            }

            if ($config->use_assert_for_type &&
                $function_name instanceof PhpParser\Node\Name &&
                $function_name->parts === ['assert'] &&
                isset($stmt->args[0])
            ) {
                self::processAssertFunctionEffects(
                    $statements_analyzer,
                    $codebase,
                    $stmt,
                    $stmt->args[0],
                    $context
                );
            }
        }

        if ($codebase->store_node_types
            && !$context->collect_initializations
            && !$context->collect_mutations
            && ($stmt_type = $statements_analyzer->node_data->getType($real_stmt))
        ) {
            $codebase->analyzer->addNodeType(
                $statements_analyzer->getFilePath(),
                $stmt,
                $stmt_type->getId()
            );
        }

        self::checkFunctionCallPurity(
            $statements_analyzer,
            $codebase,
            $stmt,
            $function_name,
            $function_id,
            $in_call_map,
            $function_storage,
            $context
        );

        if ($function_storage) {
            $generic_params = $template_result ? $template_result->upper_bounds : [];

            if ($function_storage->assertions && $function_name instanceof PhpParser\Node\Name) {
                self::applyAssertionsToContext(
                    $function_name,
                    null,
                    $function_storage->assertions,
                    $stmt->args,
                    $generic_params,
                    $context,
                    $statements_analyzer
                );
            }

            if ($function_storage->if_true_assertions) {
                $statements_analyzer->node_data->setIfTrueAssertions(
                    $stmt,
                    array_map(
                        function (Assertion $assertion) use ($generic_params) : Assertion {
                            return $assertion->getUntemplatedCopy($generic_params ?: [], null);
                        },
                        $function_storage->if_true_assertions
                    )
                );
            }

            if ($function_storage->if_false_assertions) {
                $statements_analyzer->node_data->setIfFalseAssertions(
                    $stmt,
                    array_map(
                        function (Assertion $assertion) use ($generic_params) : Assertion {
                            return $assertion->getUntemplatedCopy($generic_params ?: [], null);
                        },
                        $function_storage->if_false_assertions
                    )
                );
            }

            if ($function_storage->deprecated && $function_id) {
                if (IssueBuffer::accepts(
                    new DeprecatedFunction(
                        'The function ' . $function_id . ' has been marked as deprecated',
                        $code_location,
                        $function_id
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // continue
                }
            }
        }

        if ($byref_uses) {
            foreach ($byref_uses as $byref_use_var => $_) {
                $context->vars_in_scope['$' . $byref_use_var] = Type::getMixed();
                $context->vars_possibly_in_scope['$' . $byref_use_var] = true;
            }
        }

        if ($function_name instanceof PhpParser\Node\Name) {
            self::handleNamedFunction(
                $statements_analyzer,
                $codebase,
                $stmt,
                $real_stmt,
                $function_name,
                $function_id,
                $context
            );
        }

        if (!$statements_analyzer->node_data->getType($real_stmt)) {
            $statements_analyzer->node_data->setType($real_stmt, Type::getMixed());
        }

        return true;
    }

    /**
     * @return  array{
     *     ?bool,
     *     ?PhpParser\Node\Expr|PhpParser\Node\Name,
     *     array<int, FunctionLikeParameter>|null,
     *     ?array<string, bool>
     * }
     */
    private static function getAnalyzeNamedExpression(
        StatementsAnalyzer $statements_analyzer,
        \Psalm\Codebase $codebase,
        PhpParser\Node\Expr\FuncCall $stmt,
        PhpParser\Node\Expr\FuncCall $real_stmt,
        PhpParser\Node\Expr $function_name,
        Context $context
    ) {
        $function_params = null;

        $explicit_function_name = null;
        $function_exists = null;
        $was_in_call = $context->inside_call;
        $context->inside_call = true;

        if (ExpressionAnalyzer::analyze($statements_analyzer, $function_name, $context) === false) {
            $context->inside_call = $was_in_call;

            return [false, null, null, null];
        }

        $context->inside_call = $was_in_call;

        $byref_uses = [];

        if ($stmt_name_type = $statements_analyzer->node_data->getType($function_name)) {
            if ($stmt_name_type->isNull()) {
                if (IssueBuffer::accepts(
                    new NullFunctionCall(
                        'Cannot call function on null value',
                        new CodeLocation($statements_analyzer->getSource(), $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }

                return [false, null, null, null];
            }

            if ($stmt_name_type->isNullable()) {
                if (IssueBuffer::accepts(
                    new PossiblyNullFunctionCall(
                        'Cannot call function on possibly null value',
                        new CodeLocation($statements_analyzer->getSource(), $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            $invalid_function_call_types = [];
            $has_valid_function_call_type = false;

            foreach ($stmt_name_type->getAtomicTypes() as $var_type_part) {
                if ($var_type_part instanceof Type\Atomic\TFn || $var_type_part instanceof Type\Atomic\TCallable) {
                    if (!$var_type_part->is_pure && $context->pure) {
                        if (IssueBuffer::accepts(
                            new ImpureFunctionCall(
                                'Cannot call an impure function from a mutation-free context',
                                new CodeLocation($statements_analyzer->getSource(), $stmt)
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    }

                    $function_params = $var_type_part->params;

                    if (($stmt_type = $statements_analyzer->node_data->getType($real_stmt))
                        && $var_type_part->return_type
                    ) {
                        $statements_analyzer->node_data->setType(
                            $real_stmt,
                            Type::combineUnionTypes(
                                $stmt_type,
                                $var_type_part->return_type
                            )
                        );
                    } else {
                        $statements_analyzer->node_data->setType(
                            $real_stmt,
                            $var_type_part->return_type ?: Type::getMixed()
                        );
                    }

                    if ($var_type_part instanceof Type\Atomic\TFn) {
                        $byref_uses += $var_type_part->byref_uses;
                    }

                    $function_exists = true;
                    $has_valid_function_call_type = true;
                } elseif ($var_type_part instanceof TTemplateParam && $var_type_part->as->hasCallableType()) {
                    $has_valid_function_call_type = true;
                } elseif ($var_type_part instanceof TMixed || $var_type_part instanceof TTemplateParam) {
                    $has_valid_function_call_type = true;

                    if (IssueBuffer::accepts(
                        new MixedFunctionCall(
                            'Cannot call function on ' . $var_type_part->getId(),
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } elseif ($var_type_part instanceof TCallableObject
                    || $var_type_part instanceof TCallableString
                ) {
                    // this is fine
                    $has_valid_function_call_type = true;
                } elseif (($var_type_part instanceof TNamedObject && $var_type_part->value === 'Closure')) {
                    // this is fine
                    $has_valid_function_call_type = true;
                } elseif ($var_type_part instanceof TString
                    || $var_type_part instanceof Type\Atomic\TArray
                    || $var_type_part instanceof Type\Atomic\TList
                    || ($var_type_part instanceof Type\Atomic\ObjectLike
                        && count($var_type_part->properties) === 2)
                ) {
                    $potential_method_id = null;

                    if ($var_type_part instanceof Type\Atomic\ObjectLike) {
                        $potential_method_id = CallableTypeComparator::getCallableMethodIdFromObjectLike(
                            $var_type_part,
                            $codebase,
                            $context->calling_method_id,
                            $statements_analyzer->getFilePath()
                        );

                        if ($potential_method_id === 'not-callable') {
                            $potential_method_id = null;
                        }
                    } elseif ($var_type_part instanceof Type\Atomic\TLiteralString) {
                        if (!$var_type_part->value) {
                            $invalid_function_call_types[] = '\'\'';
                            continue;
                        }

                        if (strpos($var_type_part->value, '::')) {
                            $parts = explode('::', strtolower($var_type_part->value));
                            $fq_class_name = $parts[0];
                            $fq_class_name = \preg_replace('/^\\\\/', '', $fq_class_name);
                            $potential_method_id = new \Psalm\Internal\MethodIdentifier($fq_class_name, $parts[1]);
                        } else {
                            $explicit_function_name = new PhpParser\Node\Name\FullyQualified(
                                $var_type_part->value,
                                $function_name->getAttributes()
                            );
                        }
                    }

                    if ($potential_method_id) {
                        $codebase->methods->methodExists(
                            $potential_method_id,
                            $context->calling_method_id,
                            null,
                            $statements_analyzer,
                            $statements_analyzer->getFilePath()
                        );
                    }

                    // this is also kind of fine
                    $has_valid_function_call_type = true;
                } elseif ($var_type_part instanceof TNull) {
                    // handled above
                } elseif (!$var_type_part instanceof TNamedObject
                    || !$codebase->classlikes->classOrInterfaceExists($var_type_part->value)
                    || !$codebase->methods->methodExists(
                        new \Psalm\Internal\MethodIdentifier(
                            $var_type_part->value,
                            '__invoke'
                        )
                    )
                ) {
                    $invalid_function_call_types[] = (string)$var_type_part;
                } else {
                    self::analyzeInvokeCall(
                        $statements_analyzer,
                        $stmt,
                        $real_stmt,
                        $function_name,
                        $context
                    );
                }
            }

            if ($invalid_function_call_types) {
                $var_type_part = reset($invalid_function_call_types);

                if ($has_valid_function_call_type) {
                    if (IssueBuffer::accepts(
                        new PossiblyInvalidFunctionCall(
                            'Cannot treat type ' . $var_type_part . ' as callable',
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new InvalidFunctionCall(
                            'Cannot treat type ' . $var_type_part . ' as callable',
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                return [false, null, null, null];
            }
        }

        if (!$statements_analyzer->node_data->getType($real_stmt)) {
            $statements_analyzer->node_data->setType($real_stmt, Type::getMixed());
        }

        return [
            $function_exists,
            $explicit_function_name ?: $function_name,
            $function_params,
            $byref_uses
        ];
    }

    private static function analyzeInvokeCall(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\FuncCall $stmt,
        PhpParser\Node\Expr\FuncCall $real_stmt,
        PhpParser\Node\Expr $function_name,
        Context $context
    ) : void {
        $old_data_provider = $statements_analyzer->node_data;

        $statements_analyzer->node_data = clone $statements_analyzer->node_data;

        $fake_method_call = new PhpParser\Node\Expr\MethodCall(
            $function_name,
            new PhpParser\Node\Identifier('__invoke', $function_name->getAttributes()),
            $stmt->args
        );

        $suppressed_issues = $statements_analyzer->getSuppressedIssues();

        if (!in_array('PossiblyNullReference', $suppressed_issues, true)) {
            $statements_analyzer->addSuppressedIssues(['PossiblyNullReference']);
        }

        if (!in_array('InternalMethod', $suppressed_issues, true)) {
            $statements_analyzer->addSuppressedIssues(['InternalMethod']);
        }

        if (!in_array('PossiblyInvalidMethodCall', $suppressed_issues, true)) {
            $statements_analyzer->addSuppressedIssues(['PossiblyInvalidMethodCall']);
        }

        \Psalm\Internal\Analyzer\Statements\Expression\Call\MethodCallAnalyzer::analyze(
            $statements_analyzer,
            $fake_method_call,
            $context,
            false
        );

        if (!in_array('PossiblyNullReference', $suppressed_issues, true)) {
            $statements_analyzer->removeSuppressedIssues(['PossiblyNullReference']);
        }

        if (!in_array('InternalMethod', $suppressed_issues, true)) {
            $statements_analyzer->removeSuppressedIssues(['InternalMethod']);
        }

        if (!in_array('PossiblyInvalidMethodCall', $suppressed_issues, true)) {
            $statements_analyzer->removeSuppressedIssues(['PossiblyInvalidMethodCall']);
        }

        $fake_method_call_type = $statements_analyzer->node_data->getType($fake_method_call);

        $statements_analyzer->node_data = $old_data_provider;

        if ($stmt_type = $statements_analyzer->node_data->getType($real_stmt)) {
            $statements_analyzer->node_data->setType(
                $real_stmt,
                Type::combineUnionTypes(
                    $fake_method_call_type ?: Type::getMixed(),
                    $stmt_type
                )
            );
        } else {
            $statements_analyzer->node_data->setType(
                $real_stmt,
                $fake_method_call_type ?: Type::getMixed()
            );
        }
    }

    private static function processAssertFunctionEffects(
        StatementsAnalyzer $statements_analyzer,
        \Psalm\Codebase $codebase,
        PhpParser\Node\Expr\FuncCall $stmt,
        PhpParser\Node\Arg $first_arg,
        Context $context
    ) : void {
        $assert_clauses = \Psalm\Type\Algebra::getFormula(
            \spl_object_id($first_arg->value),
            $first_arg->value,
            $context->self,
            $statements_analyzer,
            $codebase
        );

        $cond_assigned_var_ids = [];

        \Psalm\Internal\Analyzer\AlgebraAnalyzer::checkForParadox(
            $context->clauses,
            $assert_clauses,
            $statements_analyzer,
            $stmt,
            $cond_assigned_var_ids
        );

        $simplified_clauses = Algebra::simplifyCNF(array_merge($context->clauses, $assert_clauses));

        $assert_type_assertions = Algebra::getTruthsFromFormula($simplified_clauses);

        if ($assert_type_assertions) {
            $changed_var_ids = [];

            // while in an and, we allow scope to boil over to support
            // statements of the form if ($x && $x->foo())
            $op_vars_in_scope = Reconciler::reconcileKeyedTypes(
                $assert_type_assertions,
                $assert_type_assertions,
                $context->vars_in_scope,
                $changed_var_ids,
                array_map(
                    function ($v) {
                        return true;
                    },
                    $assert_type_assertions
                ),
                $statements_analyzer,
                $statements_analyzer->getTemplateTypeMap() ?: [],
                $context->inside_loop,
                new CodeLocation($statements_analyzer->getSource(), $stmt)
            );

            foreach ($changed_var_ids as $var_id => $_) {
                $first_appearance = $statements_analyzer->getFirstAppearance($var_id);

                if ($first_appearance
                    && isset($context->vars_in_scope[$var_id])
                    && $context->vars_in_scope[$var_id]->hasMixed()
                ) {
                    if (!$context->collect_initializations
                        && !$context->collect_mutations
                        && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
                        && (!(($parent_source = $statements_analyzer->getSource())
                                    instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer)
                                || !$parent_source->getSource() instanceof \Psalm\Internal\Analyzer\TraitAnalyzer)
                    ) {
                        $codebase->analyzer->decrementMixedCount($statements_analyzer->getFilePath());
                    }

                    IssueBuffer::remove(
                        $statements_analyzer->getFilePath(),
                        'MixedAssignment',
                        $first_appearance->raw_file_start
                    );
                }

                if (isset($op_vars_in_scope[$var_id])) {
                    $op_vars_in_scope[$var_id]->from_docblock = true;
                }
            }

            $context->vars_in_scope = $op_vars_in_scope;
        }
    }

    /**
     * @param non-empty-string $function_id
     */
    private static function getFunctionCallReturnType(
        StatementsAnalyzer $statements_analyzer,
        \Psalm\Codebase $codebase,
        PhpParser\Node\Expr\FuncCall $stmt,
        PhpParser\Node\Name $function_name,
        string $function_id,
        bool $in_call_map,
        bool $is_stubbed,
        ?FunctionLikeStorage $function_storage,
        TemplateResult $template_result,
        Context $context
    ) : Type\Union {
        $stmt_type = null;
        $config = $codebase->config;

        if ($codebase->functions->return_type_provider->has($function_id)) {
            $stmt_type = $codebase->functions->return_type_provider->getReturnType(
                $statements_analyzer,
                $function_id,
                $stmt->args,
                $context,
                new CodeLocation($statements_analyzer->getSource(), $function_name)
            );
        }

        if (!$stmt_type) {
            if (!$in_call_map || $is_stubbed) {
                if ($function_storage && $function_storage->template_types) {
                    foreach ($function_storage->template_types as $template_name => $_) {
                        if (!isset($template_result->upper_bounds[$template_name])) {
                            if ($template_name === 'TFunctionArgCount') {
                                $template_result->upper_bounds[$template_name] = [
                                    'fn-' . $function_id => [Type::getInt(false, count($stmt->args)), 0]
                                ];
                            } else {
                                $template_result->upper_bounds[$template_name] = [
                                    'fn-' . $function_id => [Type::getEmpty(), 0]
                                ];
                            }
                        }
                    }
                }

                if ($function_storage && !$context->isSuppressingExceptions($statements_analyzer)) {
                    $context->mergeFunctionExceptions(
                        $function_storage,
                        new CodeLocation($statements_analyzer->getSource(), $stmt)
                    );
                }

                try {
                    if ($function_storage && $function_storage->return_type) {
                        $return_type = clone $function_storage->return_type;

                        if ($template_result->upper_bounds && $function_storage->template_types) {
                            $return_type = \Psalm\Internal\Type\TypeExpander::expandUnion(
                                $codebase,
                                $return_type,
                                null,
                                null,
                                null
                            );

                            $return_type->replaceTemplateTypesWithArgTypes(
                                $template_result,
                                $codebase
                            );
                        }

                        $return_type = \Psalm\Internal\Type\TypeExpander::expandUnion(
                            $codebase,
                            $return_type,
                            null,
                            null,
                            null
                        );

                        $return_type_location = $function_storage->return_type_location;

                        if ($config->after_function_checks) {
                            $file_manipulations = [];

                            foreach ($config->after_function_checks as $plugin_fq_class_name) {
                                $plugin_fq_class_name::afterFunctionCallAnalysis(
                                    $stmt,
                                    $function_id,
                                    $context,
                                    $statements_analyzer->getSource(),
                                    $codebase,
                                    $file_manipulations,
                                    $return_type
                                );
                            }

                            if ($file_manipulations) {
                                FileManipulationBuffer::add(
                                    $statements_analyzer->getFilePath(),
                                    $file_manipulations
                                );
                            }
                        }

                        if ($return_type === null) {
                            throw new \UnexpectedValueException('$return_type shouldn’t be null here');
                        }

                        $stmt_type = $return_type;
                        $return_type->by_ref = $function_storage->returns_by_ref;

                        // only check the type locally if it's defined externally
                        if ($return_type_location &&
                            !$is_stubbed && // makes lookups or array_* functions quicker
                            !$config->isInProjectDirs($return_type_location->file_path)
                        ) {
                            $return_type->check(
                                $statements_analyzer,
                                new CodeLocation($statements_analyzer->getSource(), $stmt),
                                $statements_analyzer->getSuppressedIssues(),
                                $context->phantom_classes,
                                true,
                                false,
                                false,
                                $context->calling_method_id
                            );
                        }
                    }
                } catch (\InvalidArgumentException $e) {
                    // this can happen when the function was defined in the Config startup script
                    $stmt_type = Type::getMixed();
                }
            } else {
                $stmt_type = FunctionAnalyzer::getReturnTypeFromCallMapWithArgs(
                    $statements_analyzer,
                    $function_id,
                    $stmt->args,
                    $context
                );
            }
        }

        if (!$stmt_type) {
            $stmt_type = Type::getMixed();
        }

        if ($function_storage) {
            self::taintReturnType($statements_analyzer, $stmt, $function_id, $function_storage, $stmt_type);
        }


        return $stmt_type;
    }

    private static function taintReturnType(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\FuncCall $stmt,
        string $function_id,
        FunctionLikeStorage $function_storage,
        Type\Union $stmt_type
    ) : void {
        $codebase = $statements_analyzer->getCodebase();

        if (!$codebase->taint
            || !$codebase->config->trackTaintsInPath($statements_analyzer->getFilePath())
            || \in_array('TaintedInput', $statements_analyzer->getSuppressedIssues())
        ) {
            return;
        }

        $return_location = new CodeLocation($statements_analyzer->getSource(), $stmt);

        $function_return_sink = TaintNode::getForMethodReturn(
            $function_id,
            $function_id,
            $return_location,
            $function_storage->specialize_call ? $return_location : null
        );

        $codebase->taint->addTaintNode($function_return_sink);

        $stmt_type->parent_nodes[] = $function_return_sink;

        if ($function_storage->return_source_params) {
            $removed_taints = $function_storage->removed_taints;

            if ($function_id === 'preg_replace' && count($stmt->args) > 2) {
                $first_stmt_type = $statements_analyzer->node_data->getType($stmt->args[0]->value);
                $second_stmt_type = $statements_analyzer->node_data->getType($stmt->args[1]->value);

                if ($first_stmt_type
                    && $second_stmt_type
                    && $first_stmt_type->isSingleStringLiteral()
                    && $second_stmt_type->isSingleStringLiteral()
                ) {
                    $first_arg_value = $first_stmt_type->getSingleStringLiteral()->value;

                    $pattern = \substr($first_arg_value, 1, -1);

                    if ($pattern[0] === '['
                        && $pattern[1] === '^'
                        && \substr($pattern, -1) === ']'
                    ) {
                        $pattern = \substr($pattern, 2, -1);

                        if (self::simpleExclusion($pattern, $first_arg_value[0])) {
                            $removed_taints[] = 'html';
                            $removed_taints[] = 'sql';
                        }
                    }
                }
            }

            foreach ($function_storage->return_source_params as $i => $path_type) {
                if (!isset($stmt->args[$i])) {
                    continue;
                }

                $arg_location = new CodeLocation(
                    $statements_analyzer->getSource(),
                    $stmt->args[$i]->value
                );

                $function_param_sink = TaintNode::getForMethodArgument(
                    $function_id,
                    $function_id,
                    $i,
                    $arg_location,
                    $function_storage->specialize_call ? $return_location : null
                );

                $codebase->taint->addTaintNode($function_param_sink);

                $codebase->taint->addPath(
                    $function_param_sink,
                    $function_return_sink,
                    $path_type,
                    $function_storage->added_taints,
                    $removed_taints
                );
            }
        }

        if ($function_storage->taint_source_types) {
            $method_node = Source::getForMethodReturn(
                $function_id,
                $function_id,
                $return_location
            );

            $method_node->taints = $function_storage->taint_source_types;

            $codebase->taint->addSource($method_node);
        }
    }

    /**
     * @psalm-pure
     */
    private static function simpleExclusion(string $pattern, string $escape_char) : bool
    {
        $str_length = \strlen($pattern);

        for ($i = 0; $i < $str_length; $i++) {
            $current = $pattern[$i];
            $next = $pattern[$i + 1] ?? null;

            if ($current === '\\') {
                if ($next == null
                    || $next === 'x'
                    || $next === 'u'
                ) {
                    return false;
                }

                if ($next === '.'
                    || $next === '('
                    || $next === ')'
                    || $next === '['
                    || $next === ']'
                    || $next === 's'
                    || $next === 'w'
                    || $next === $escape_char
                ) {
                    $i++;
                    continue;
                }

                return false;
            }

            if ($next !== '-') {
                if ($current === '_'
                    || $current === '-'
                    || $current === '|'
                    || $current === ':'
                    || $current === '#'
                    || $current === '.'
                    || $current === ' '
                ) {
                    continue;
                }

                return false;
            }

            if ($current === ']') {
                return false;
            }

            if (!isset($pattern[$i + 2]) || $next !== '-') {
                return false;
            }

            if (($current === 'a' && $pattern[$i + 2] === 'z')
                || ($current === 'a' && $pattern[$i + 2] === 'Z')
                || ($current === 'A' && $pattern[$i + 2] === 'Z')
                || ($current === '0' && $pattern[$i + 2] === '9')
            ) {
                $i += 2;
                continue;
            }

            return false;
        }

        return true;
    }

    private static function checkFunctionCallPurity(
        StatementsAnalyzer $statements_analyzer,
        \Psalm\Codebase $codebase,
        PhpParser\Node\Expr\FuncCall $stmt,
        PhpParser\Node $function_name,
        ?string $function_id,
        bool $in_call_map,
        ?FunctionLikeStorage $function_storage,
        Context $context
    ) : void {
        $config = $codebase->config;

        if (!$context->collect_initializations
            && !$context->collect_mutations
            && ($context->mutation_free
                || $context->external_mutation_free
                || $codebase->find_unused_variables
                || !$config->remember_property_assignments_after_call
                || $codebase->alter_code)
        ) {
            $must_use = true;

            $callmap_function_pure = $function_id && $in_call_map
                ? $codebase->functions->isCallMapFunctionPure(
                    $codebase,
                    $statements_analyzer->node_data,
                    $function_id,
                    $stmt->args,
                    $must_use
                )
                : null;

            if ((!$in_call_map
                    && $function_storage
                    && !$function_storage->pure)
                || ($callmap_function_pure === false)
            ) {
                $project_analyzer = $statements_analyzer->getProjectAnalyzer();

                if ($context->mutation_free || $context->external_mutation_free) {
                    if (IssueBuffer::accepts(
                        new ImpureFunctionCall(
                            'Cannot call an impure function from a mutation-free context',
                            new CodeLocation($statements_analyzer, $function_name)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } elseif ($codebase->alter_code
                    && (isset($project_analyzer->getIssuesToFix()['MissingPureAnnotation'])
                        || isset($project_analyzer->getIssuesToFix()['MissingImmutableAnnotation']))
                    && $statements_analyzer->getSource()
                        instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer
                ) {
                    $statements_analyzer->getSource()->inferred_has_mutation = true;
                    $statements_analyzer->getSource()->inferred_impure = true;
                }

                if (!$config->remember_property_assignments_after_call) {
                    $context->removeAllObjectVars();
                }
            } elseif ($function_id
                && (($function_storage
                        && $function_storage->pure
                        && !$function_storage->assertions
                        && $must_use)
                    || ($callmap_function_pure === true && $must_use))
                && $codebase->find_unused_variables
                && !$context->inside_conditional
                && !$context->inside_unset
            ) {
                if (!$context->inside_assignment && !$context->inside_call) {
                    if (IssueBuffer::accepts(
                        new UnusedFunctionCall(
                            'The call to ' . $function_id . ' is not used',
                            new CodeLocation($statements_analyzer, $function_name),
                            $function_id
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } else {
                    /** @psalm-suppress UndefinedPropertyAssignment */
                    $stmt->pure = true;
                }
            }
        }
    }

    private static function handleNamedFunction(
        StatementsAnalyzer $statements_analyzer,
        \Psalm\Codebase $codebase,
        PhpParser\Node\Expr\FuncCall $stmt,
        PhpParser\Node\Expr\FuncCall $real_stmt,
        PhpParser\Node\Name $function_name,
        ?string $function_id,
        Context $context
    ) : void {
        $first_arg = isset($stmt->args[0]) ? $stmt->args[0] : null;

        if ($function_name->parts === ['get_class'] || $function_name->parts === ['gettype']) {
            if ($first_arg) {
                $var = $first_arg->value;

                if ($var instanceof PhpParser\Node\Expr\Variable
                    && is_string($var->name)
                ) {
                    $var_id = '$' . $var->name;

                    if (isset($context->vars_in_scope[$var_id])) {
                        $atomic_type = $function_name->parts === ['get_class']
                            ? new Type\Atomic\GetClassT(
                                $var_id,
                                $context->vars_in_scope[$var_id]->hasMixed()
                                    ? Type::getObject()
                                    : $context->vars_in_scope[$var_id]
                            )
                            : new Type\Atomic\GetTypeT($var_id);

                        $statements_analyzer->node_data->setType($real_stmt, new Type\Union([$atomic_type]));
                    }
                } elseif ($var_type = $statements_analyzer->node_data->getType($var)) {
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
                        } else {
                            $class_string_types[] = new Type\Atomic\TClassString();
                        }
                    }

                    if ($class_string_types) {
                        $statements_analyzer->node_data->setType($real_stmt, new Type\Union($class_string_types));
                    }
                }
            } elseif ($function_name->parts === ['get_class']
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

        if ($function_name->parts === ['method_exists']) {
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
        } elseif ($function_name->parts === ['class_exists']) {
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
        } elseif ($function_name->parts === ['interface_exists']) {
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
        } elseif ($function_name->parts === ['file_exists'] && $first_arg) {
            $var_id = ExpressionIdentifier::getArrayVarId($first_arg->value, null);

            if ($var_id) {
                $context->phantom_files[$var_id] = true;
            }
        } elseif ($function_name->parts === ['extension_loaded']) {
            if ($first_arg
                && $first_arg->value instanceof PhpParser\Node\Scalar\String_
            ) {
                if (@extension_loaded($first_arg->value->value)) {
                    // do nothing
                } else {
                    $context->check_classes = false;
                }
            }
        } elseif ($function_name->parts === ['function_exists']) {
            $context->check_functions = false;
        } elseif ($function_name->parts === ['is_callable']) {
            $context->check_methods = false;
            $context->check_functions = false;
        } elseif ($function_name->parts === ['defined']) {
            $context->check_consts = false;
        } elseif ($function_name->parts === ['extract']) {
            $context->check_variables = false;
        } elseif (strtolower($function_name->parts[0]) === 'var_dump'
            || strtolower($function_name->parts[0]) === 'shell_exec') {
            if (IssueBuffer::accepts(
                new ForbiddenCode(
                    'Unsafe ' . implode('', $function_name->parts),
                    new CodeLocation($statements_analyzer->getSource(), $stmt)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // continue
            }
        } elseif (isset($codebase->config->forbidden_functions[strtolower((string) $function_name)])) {
            if (IssueBuffer::accepts(
                new ForbiddenCode(
                    'You have forbidden the use of ' . $function_name,
                    new CodeLocation($statements_analyzer->getSource(), $stmt)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // continue
            }
        } elseif ($function_name->parts === ['define']) {
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
        } elseif ($function_name->parts === ['constant']) {
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
        } elseif ($first_arg
            && $function_id
            && strpos($function_id, 'is_') === 0
            && $function_id !== 'is_a'
        ) {
            $stmt_assertions = $statements_analyzer->node_data->getAssertions($stmt);

            if ($stmt_assertions !== null) {
                $assertions = $stmt_assertions;
            } else {
                $assertions = AssertionFinder::processFunctionCall(
                    $stmt,
                    $context->self,
                    $statements_analyzer,
                    $codebase,
                    $context->inside_negation
                );
            }

            $changed_vars = [];

            $referenced_var_ids = array_map(
                function (array $_) : bool {
                    return true;
                },
                $assertions
            );

            if ($assertions) {
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
        } elseif ($first_arg && $function_id === 'strtolower') {
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
                        new \Psalm\Issue\RedundantConditionGivenDocblockType(
                            'The call to strtolower is unnecessary given the docblock type',
                            new CodeLocation($statements_analyzer, $function_name)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new \Psalm\Issue\RedundantCondition(
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
    }
}
