<?php
namespace Psalm\Checker\Statements\Expression\Call;

use PhpParser;
use Psalm\Checker\AlgebraChecker;
use Psalm\Checker\FunctionChecker;
use Psalm\Checker\FunctionLikeChecker;
use Psalm\Checker\ProjectChecker;
use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\Checker\StatementsChecker;
use Psalm\Codebase\CallMap;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\ForbiddenCode;
use Psalm\Issue\InvalidFunctionCall;
use Psalm\Issue\NullFunctionCall;
use Psalm\Issue\PossiblyInvalidFunctionCall;
use Psalm\Issue\PossiblyNullFunctionCall;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Reconciler;

class FunctionCallChecker extends \Psalm\Checker\Statements\Expression\CallChecker
{
    /**
     * @param   StatementsChecker               $statements_checker
     * @param   PhpParser\Node\Expr\FuncCall    $stmt
     * @param   Context                         $context
     *
     * @return  false|null
     */
    public static function analyze(
        ProjectChecker $project_checker,
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\FuncCall $stmt,
        Context $context
    ) {
        $function = $stmt->name;

        if ($function instanceof PhpParser\Node\Name) {
            $first_arg = isset($stmt->args[0]) ? $stmt->args[0] : null;

            if ($function->parts === ['method_exists']) {
                $context->check_methods = false;
            } elseif ($function->parts === ['class_exists']) {
                if ($first_arg && $first_arg->value instanceof PhpParser\Node\Scalar\String_) {
                    $context->addPhantomClass($first_arg->value->value);
                } else {
                    $context->check_classes = false;
                }
            } elseif ($function->parts === ['extension_loaded']) {
                $context->check_classes = false;
            } elseif ($function->parts === ['function_exists']) {
                $context->check_functions = false;
            } elseif ($function->parts === ['is_callable']) {
                $context->check_methods = false;
                $context->check_functions = false;
            } elseif ($function->parts === ['defined']) {
                $context->check_consts = false;
            } elseif ($function->parts === ['extract']) {
                $context->check_variables = false;
            } elseif ($function->parts === ['var_dump'] || $function->parts === ['shell_exec']) {
                if (IssueBuffer::accepts(
                    new ForbiddenCode(
                        'Unsafe ' . implode('', $function->parts),
                        new CodeLocation($statements_checker->getSource(), $stmt)
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    return false;
                }
            } elseif ($function->parts === ['define']) {
                if ($first_arg && $first_arg->value instanceof PhpParser\Node\Scalar\String_) {
                    $second_arg = $stmt->args[1];
                    ExpressionChecker::analyze($statements_checker, $second_arg->value, $context);
                    $const_name = $first_arg->value->value;

                    $statements_checker->setConstType(
                        $const_name,
                        isset($second_arg->value->inferredType) ? $second_arg->value->inferredType : Type::getMixed(),
                        $context
                    );
                } else {
                    $context->check_consts = false;
                }
            }
        }

        $function_id = null;
        $function_params = null;
        $in_call_map = false;

        $is_stubbed = false;

        $function_storage = null;

        $code_location = new CodeLocation($statements_checker->getSource(), $stmt);
        $codebase = $project_checker->codebase;
        $codebase_functions = $codebase->functions;
        $config = $codebase->config;
        $defined_constants = [];

        $function_exists = false;

        if ($stmt->name instanceof PhpParser\Node\Expr) {
            if (ExpressionChecker::analyze($statements_checker, $stmt->name, $context) === false) {
                return false;
            }

            if (isset($stmt->name->inferredType)) {
                if ($stmt->name->inferredType->isNull()) {
                    if (IssueBuffer::accepts(
                        new NullFunctionCall(
                            'Cannot call function on null value',
                            new CodeLocation($statements_checker->getSource(), $stmt)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }

                    return;
                }

                if ($stmt->name->inferredType->isNullable()) {
                    if (IssueBuffer::accepts(
                        new PossiblyNullFunctionCall(
                            'Cannot call function on possibly null value',
                            new CodeLocation($statements_checker->getSource(), $stmt)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                $invalid_function_call_types = [];
                $has_valid_function_call_type = false;

                foreach ($stmt->name->inferredType->getTypes() as $var_type_part) {
                    if ($var_type_part instanceof Type\Atomic\Fn) {
                        $function_params = $var_type_part->params;

                        if (isset($stmt->inferredType) && $var_type_part->return_type) {
                            $stmt->inferredType = Type::combineUnionTypes(
                                $stmt->inferredType,
                                $var_type_part->return_type
                            );
                        } else {
                            $stmt->inferredType = $var_type_part->return_type ?: Type::getMixed();
                        }

                        $function_exists = true;
                        $has_valid_function_call_type = true;
                    } elseif ($var_type_part instanceof TMixed) {
                        $has_valid_function_call_type = true;
                    // @todo maybe emit issue here
                    } elseif (($var_type_part instanceof TNamedObject && $var_type_part->value === 'Closure') ||
                        $var_type_part instanceof TCallable
                    ) {
                        // this is fine
                        $has_valid_function_call_type = true;
                    } elseif ($var_type_part instanceof TString
                        || $var_type_part instanceof Type\Atomic\TArray
                        || ($var_type_part instanceof Type\Atomic\ObjectLike
                            && count($var_type_part->properties) === 2)
                    ) {
                        // this is also kind of fine
                        $has_valid_function_call_type = true;
                    } elseif ($var_type_part instanceof TNull) {
                        // handled above
                    } elseif (!$var_type_part instanceof TNamedObject
                        || !$codebase->classlikes->classOrInterfaceExists($var_type_part->value)
                        || !$codebase->methods->methodExists($var_type_part->value . '::__invoke')
                    ) {
                        $invalid_function_call_types[] = (string)$var_type_part;
                    }
                }

                if ($invalid_function_call_types) {
                    $var_type_part = reset($invalid_function_call_types);

                    if ($has_valid_function_call_type) {
                        if (IssueBuffer::accepts(
                            new PossiblyInvalidFunctionCall(
                                'Cannot treat type ' . $var_type_part . ' as callable',
                                new CodeLocation($statements_checker->getSource(), $stmt)
                            ),
                            $statements_checker->getSuppressedIssues()
                        )) {
                            return false;
                        }
                    } else {
                        if (IssueBuffer::accepts(
                            new InvalidFunctionCall(
                                'Cannot treat type ' . $var_type_part . ' as callable',
                                new CodeLocation($statements_checker->getSource(), $stmt)
                            ),
                            $statements_checker->getSuppressedIssues()
                        )) {
                            return false;
                        }
                    }
                }
            }

            if (!isset($stmt->inferredType)) {
                $stmt->inferredType = Type::getMixed();
            }
        } else {
            $function_id = implode('\\', $stmt->name->parts);

            $in_call_map = CallMap::inCallMap($function_id);
            $is_stubbed = $codebase_functions->hasStubbedFunction($function_id);

            $is_predefined = true;

            $is_maybe_root_function = !$stmt->name instanceof PhpParser\Node\Name\FullyQualified
                && count($stmt->name->parts) === 1;

            if (!$in_call_map) {
                $predefined_functions = $config->getPredefinedFunctions();
                $is_predefined = isset($predefined_functions[$function_id]);
            }

            if (!$in_call_map && !$stmt->name instanceof PhpParser\Node\Name\FullyQualified) {
                $function_id = $codebase_functions->getFullyQualifiedFunctionNameFromString(
                    $function_id,
                    $statements_checker
                );
            }

            if (!$in_call_map) {
                if ($context->check_functions) {
                    if (self::checkFunctionExists(
                        $statements_checker,
                        $function_id,
                        $code_location,
                        $is_maybe_root_function
                    ) === false
                    ) {
                        return false;
                    }
                } else {
                    $function_id = self::getExistingFunctionId(
                        $statements_checker,
                        $function_id,
                        $is_maybe_root_function
                    );
                }

                $function_exists = $is_stubbed || $codebase_functions->functionExists(
                    $statements_checker,
                    strtolower($function_id)
                );
            } else {
                $function_exists = true;
            }

            if ($function_exists) {
                if (!$in_call_map || $is_stubbed) {
                    $function_storage = $codebase_functions->getStorage(
                        $statements_checker,
                        strtolower($function_id)
                    );

                    $function_params = $function_storage->params;

                    if (!$is_predefined) {
                        $defined_constants = $function_storage->defined_constants;
                    }
                }

                if ($in_call_map && !$is_stubbed) {
                    $function_params = FunctionLikeChecker::getFunctionParamsFromCallMapById(
                        $statements_checker->getFileChecker()->project_checker,
                        $function_id,
                        $stmt->args
                    );
                }
            }
        }

        if (self::checkFunctionArguments(
            $statements_checker,
            $stmt->args,
            $function_params,
            $function_id,
            $context
        ) === false) {
            // fall through
        }

        if ($function_exists) {
            $generic_params = null;

            if ($stmt->name instanceof PhpParser\Node\Name && $function_id) {
                if (!$is_stubbed && $in_call_map) {
                    $function_params = FunctionLikeChecker::getFunctionParamsFromCallMapById(
                        $statements_checker->getFileChecker()->project_checker,
                        $function_id,
                        $stmt->args
                    );
                }
            }

            // do this here to allow closure param checks
            if (self::checkFunctionLikeArgumentsMatch(
                $statements_checker,
                $stmt->args,
                $function_id,
                $function_params ?: [],
                $function_storage,
                null,
                $generic_params,
                $code_location,
                $context
            ) === false) {
                // fall through
            }

            if ($stmt->name instanceof PhpParser\Node\Name && $function_id) {
                if (!$in_call_map || $is_stubbed) {
                    if ($function_storage && $function_storage->template_types) {
                        foreach ($function_storage->template_types as $template_name => $_) {
                            if (!isset($generic_params[$template_name])) {
                                $generic_params[$template_name] = Type::getMixed();
                            }
                        }
                    }

                    try {
                        if ($function_storage && $function_storage->return_type) {
                            $return_type = clone $function_storage->return_type;

                            if ($generic_params && $function_storage->template_types) {
                                $return_type->replaceTemplateTypesWithArgTypes(
                                    $generic_params
                                );
                            }

                            $return_type_location = $function_storage->return_type_location;

                            $stmt->inferredType = $return_type;
                            $return_type->by_ref = $function_storage->returns_by_ref;

                            // only check the type locally if it's defined externally
                            if ($return_type_location &&
                                !$is_stubbed && // makes lookups or array_* functions quicker
                                !$config->isInProjectDirs($return_type_location->file_path)
                            ) {
                                $return_type->check(
                                    $statements_checker,
                                    new CodeLocation($statements_checker->getSource(), $stmt),
                                    $statements_checker->getSuppressedIssues(),
                                    $context->getPhantomClasses()
                                );
                            }
                        }
                    } catch (\InvalidArgumentException $e) {
                        // this can happen when the function was defined in the Config startup script
                        $stmt->inferredType = Type::getMixed();
                    }
                } else {
                    $stmt->inferredType = FunctionChecker::getReturnTypeFromCallMapWithArgs(
                        $statements_checker,
                        $function_id,
                        $stmt->args,
                        $code_location,
                        $statements_checker->getSuppressedIssues()
                    );
                }
            }

            foreach ($defined_constants as $const_name => $const_type) {
                $context->constants[$const_name] = clone $const_type;
                $context->vars_in_scope[$const_name] = clone $const_type;
            }

            if ($config->use_assert_for_type &&
                $function instanceof PhpParser\Node\Name &&
                $function->parts === ['assert'] &&
                isset($stmt->args[0])
            ) {
                $assert_clauses = AlgebraChecker::getFormula(
                    $stmt->args[0]->value,
                    $statements_checker->getFQCLN(),
                    $statements_checker
                );

                $simplified_clauses = AlgebraChecker::simplifyCNF(array_merge($context->clauses, $assert_clauses));

                $assert_type_assertions = AlgebraChecker::getTruthsFromFormula($simplified_clauses);

                $changed_vars = [];

                // while in an and, we allow scope to boil over to support
                // statements of the form if ($x && $x->foo())
                $op_vars_in_scope = Reconciler::reconcileKeyedTypes(
                    $assert_type_assertions,
                    $context->vars_in_scope,
                    $changed_vars,
                    [],
                    $statements_checker,
                    new CodeLocation($statements_checker->getSource(), $stmt),
                    $statements_checker->getSuppressedIssues()
                );

                foreach ($changed_vars as $changed_var) {
                    if (isset($op_vars_in_scope[$changed_var])) {
                        $op_vars_in_scope[$changed_var]->from_docblock = true;
                    }
                }

                $context->vars_in_scope = $op_vars_in_scope;
            }
        }

        if (!$config->remember_property_assignments_after_call && !$context->collect_initializations) {
            $context->removeAllObjectVars();
        }

        if ($stmt->name instanceof PhpParser\Node\Name &&
            ($stmt->name->parts === ['get_class'] || $stmt->name->parts === ['gettype']) &&
            $stmt->args
        ) {
            $var = $stmt->args[0]->value;

            if ($var instanceof PhpParser\Node\Expr\Variable && is_string($var->name)) {
                $atomic_type = $stmt->name->parts === ['get_class']
                    ? new Type\Atomic\GetClassT('$' . $var->name)
                    : new Type\Atomic\GetTypeT('$' . $var->name);

                $stmt->inferredType = new Type\Union([$atomic_type]);
            }
        }

        if ($function_storage
            && strpos($function_storage->cased_name, 'assert') === 0
            && $function_storage->assertions
        ) {
            self::applyAssertionsToContext(
                $function_storage->assertions,
                $stmt->args,
                $context,
                $statements_checker
            );
        }

        return null;
    }
}
