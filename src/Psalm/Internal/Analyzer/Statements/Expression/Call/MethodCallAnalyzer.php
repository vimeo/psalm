<?php
namespace Psalm\Internal\Analyzer\Statements\Expression\Call;

use PhpParser;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\MethodAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\TypeAnalyzer;
use Psalm\Codebase\CallMap;
use Psalm\CodeLocation;
use Psalm\Config;
use Psalm\Context;
use Psalm\FileManipulation\FileManipulationBuffer;
use Psalm\Issue\InvalidMethodCall;
use Psalm\Issue\InvalidPropertyAssignmentValue;
use Psalm\Issue\InvalidScope;
use Psalm\Issue\MixedMethodCall;
use Psalm\Issue\MixedTypeCoercion;
use Psalm\Issue\NullReference;
use Psalm\Issue\PossiblyFalseReference;
use Psalm\Issue\PossiblyInvalidMethodCall;
use Psalm\Issue\PossiblyInvalidPropertyAssignmentValue;
use Psalm\Issue\PossiblyNullReference;
use Psalm\Issue\PossiblyUndefinedMethod;
use Psalm\Issue\TypeCoercion;
use Psalm\Issue\UndefinedMethod;
use Psalm\Issue\UndefinedThisPropertyAssignment;
use Psalm\Issue\UndefinedThisPropertyFetch;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TNamedObject;

class MethodCallAnalyzer extends \Psalm\Internal\Analyzer\Statements\Expression\CallAnalyzer
{
    /**
     * @param   StatementsAnalyzer               $statements_analyzer
     * @param   PhpParser\Node\Expr\MethodCall  $stmt
     * @param   Context                         $context
     *
     * @return  false|null
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\MethodCall $stmt,
        Context $context
    ) {
        $stmt->inferredType = null;

        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->var, $context) === false) {
            return false;
        }

        if (!$stmt->name instanceof PhpParser\Node\Identifier) {
            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->name, $context) === false) {
                return false;
            }
        }

        $method_id = null;

        if ($stmt->var instanceof PhpParser\Node\Expr\Variable) {
            if (is_string($stmt->var->name) && $stmt->var->name === 'this' && !$statements_analyzer->getFQCLN()) {
                if (IssueBuffer::accepts(
                    new InvalidScope(
                        'Use of $this in non-class context',
                        new CodeLocation($statements_analyzer->getSource(), $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    return false;
                }
            }
        }

        $var_id = ExpressionAnalyzer::getArrayVarId(
            $stmt->var,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer
        );

        $class_type = $var_id && $context->hasVariable($var_id, $statements_analyzer)
            ? $context->vars_in_scope[$var_id]
            : null;

        if (isset($stmt->var->inferredType)) {
            $class_type = $stmt->var->inferredType;
        } elseif (!$class_type) {
            $stmt->inferredType = Type::getMixed();
        }

        $source = $statements_analyzer->getSource();

        if (!$context->check_methods || !$context->check_classes) {
            if (self::checkFunctionArguments(
                $statements_analyzer,
                $stmt->args,
                null,
                null,
                $context
            ) === false) {
                return false;
            }

            return null;
        }

        $has_mock = false;

        if ($class_type && $stmt->name instanceof PhpParser\Node\Identifier && $class_type->isNull()) {
            if (IssueBuffer::accepts(
                new NullReference(
                    'Cannot call method ' . $stmt->name->name . ' on null variable ' . $var_id,
                    new CodeLocation($statements_analyzer->getSource(), $stmt->var)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                return false;
            }

            return null;
        }

        if ($class_type
            && $stmt->name instanceof PhpParser\Node\Identifier
            && $class_type->isNullable()
            && !$class_type->ignore_nullable_issues
        ) {
            if (IssueBuffer::accepts(
                new PossiblyNullReference(
                    'Cannot call method ' . $stmt->name->name . ' on possibly null variable ' . $var_id,
                    new CodeLocation($statements_analyzer->getSource(), $stmt->var)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                return false;
            }
        }

        if ($class_type
            && $stmt->name instanceof PhpParser\Node\Identifier
            && $class_type->isFalsable()
            && !$class_type->ignore_falsable_issues
        ) {
            if (IssueBuffer::accepts(
                new PossiblyFalseReference(
                    'Cannot call method ' . $stmt->name->name . ' on possibly false variable ' . $var_id,
                    new CodeLocation($statements_analyzer->getSource(), $stmt->var)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                return false;
            }
        }

        $config = Config::getInstance();
        $codebase = $statements_analyzer->getCodebase();

        $non_existent_method_ids = [];
        $existent_method_ids = [];

        $invalid_method_call_types = [];
        $has_valid_method_call_type = false;

        $code_location = new CodeLocation($source, $stmt);
        $name_code_location = new CodeLocation($source, $stmt->name);

        $returns_by_ref = false;

        if ($class_type) {
            $return_type = null;

            $lhs_types = $class_type->getTypes();

            foreach ($lhs_types as $lhs_type_part) {
                if (!$lhs_type_part instanceof TNamedObject) {
                    switch (get_class($lhs_type_part)) {
                        case Type\Atomic\TNull::class:
                        case Type\Atomic\TFalse::class:
                            // handled above
                            break;

                        case Type\Atomic\TInt::class:
                        case Type\Atomic\TLiteralInt::class:
                        case Type\Atomic\TFloat::class:
                        case Type\Atomic\TLiteralFloat::class:
                        case Type\Atomic\TBool::class:
                        case Type\Atomic\TTrue::class:
                        case Type\Atomic\TArray::class:
                        case Type\Atomic\TNonEmptyArray::class:
                        case Type\Atomic\ObjectLike::class:
                        case Type\Atomic\TString::class:
                        case Type\Atomic\TSingleLetter::class:
                        case Type\Atomic\TLiteralString::class:
                        case Type\Atomic\TLiteralClassString::class:
                        case Type\Atomic\TNumericString::class:
                        case Type\Atomic\THtmlEscapedString::class:
                        case Type\Atomic\TClassString::class:
                        case Type\Atomic\TEmptyMixed::class:
                            $invalid_method_call_types[] = (string)$lhs_type_part;
                            break;

                        case Type\Atomic\TMixed::class:
                        case Type\Atomic\TGenericParam::class:
                        case Type\Atomic\TObject::class:
                            $codebase->analyzer->incrementMixedCount($statements_analyzer->getFilePath());

                            if (IssueBuffer::accepts(
                                new MixedMethodCall(
                                    'Cannot call method on a mixed variable ' . $var_id,
                                    $name_code_location
                                ),
                                $statements_analyzer->getSuppressedIssues()
                            )) {
                                // fall through
                            }

                            if (self::checkFunctionArguments(
                                $statements_analyzer,
                                $stmt->args,
                                null,
                                null,
                                $context
                            ) === false) {
                                return false;
                            }

                            $return_type = Type::getMixed();
                            break;
                    }

                    continue;
                }

                $codebase->analyzer->incrementNonMixedCount($statements_analyzer->getFilePath());

                $has_valid_method_call_type = true;

                $fq_class_name = $lhs_type_part->value;

                $intersection_types = $lhs_type_part->getIntersectionTypes();

                $is_mock = ExpressionAnalyzer::isMock($fq_class_name);

                $has_mock = $has_mock || $is_mock;

                if ($fq_class_name === 'static') {
                    $fq_class_name = (string) $context->self;
                }

                if ($is_mock ||
                    $context->isPhantomClass($fq_class_name)
                ) {
                    $return_type = Type::getMixed();
                    continue;
                }

                if ($var_id === '$this') {
                    $does_class_exist = true;
                } else {
                    $does_class_exist = ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                        $statements_analyzer,
                        $fq_class_name,
                        new CodeLocation($source, $stmt->var),
                        $statements_analyzer->getSuppressedIssues()
                    );
                }

                if (!$does_class_exist) {
                    return $does_class_exist;
                }

                if ($fq_class_name === 'iterable') {
                    if (IssueBuffer::accepts(
                        new UndefinedMethod(
                            $fq_class_name . ' has no defined methods',
                            new CodeLocation($source, $stmt->var),
                            $fq_class_name . '::'
                                . (!$stmt->name instanceof PhpParser\Node\Identifier ? '$method' : $stmt->name->name)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        return false;
                    }

                    return;
                }

                if (!$stmt->name instanceof PhpParser\Node\Identifier) {
                    $return_type = Type::getMixed();
                    break;
                }

                $method_name_lc = strtolower($stmt->name->name);

                $method_id = $fq_class_name . '::' . $method_name_lc;

                $intersection_method_id = $intersection_types
                    ? '(' . $lhs_type_part . ')'  . '::' . $stmt->name->name
                    : null;

                $args = $stmt->args;

                if (!$codebase->methods->methodExists($method_id)
                    || !MethodAnalyzer::isMethodVisible(
                        $method_id,
                        $context->self,
                        $statements_analyzer->getSource()
                    )
                ) {
                    if ($codebase->methods->methodExists($fq_class_name . '::__call', $context->calling_method_id)) {
                        $class_storage = $codebase->classlike_storage_provider->get($fq_class_name);

                        if (isset($class_storage->pseudo_methods[$method_name_lc])) {
                            $has_valid_method_call_type = true;
                            $existent_method_ids[] = $method_id;

                            $pseudo_method_storage = $class_storage->pseudo_methods[$method_name_lc];

                            if (self::checkFunctionArguments(
                                $statements_analyzer,
                                $args,
                                $pseudo_method_storage->params,
                                $method_id,
                                $context
                            ) === false) {
                                return false;
                            }

                            $generic_params = [];

                            if (self::checkFunctionLikeArgumentsMatch(
                                $statements_analyzer,
                                $args,
                                null,
                                $pseudo_method_storage->params,
                                $pseudo_method_storage,
                                null,
                                $generic_params,
                                $code_location,
                                $context
                            ) === false) {
                                return false;
                            }

                            if ($pseudo_method_storage->return_type) {
                                $return_type_candidate = clone $pseudo_method_storage->return_type;

                                if (!$return_type) {
                                    $return_type = $return_type_candidate;
                                } else {
                                    $return_type = Type::combineUnionTypes($return_type_candidate, $return_type);
                                }

                                continue;
                            }
                        } else {
                            if (self::checkFunctionArguments(
                                $statements_analyzer,
                                $args,
                                null,
                                null,
                                $context
                            ) === false) {
                                return false;
                            }

                            if ($class_storage->sealed_methods) {
                                $non_existent_method_ids[] = $method_id;
                                continue;
                            }
                        }

                        $has_valid_method_call_type = true;
                        $existent_method_ids[] = $method_id;

                        $array_values = array_map(
                            /**
                             * @return PhpParser\Node\Expr\ArrayItem
                             */
                            function (PhpParser\Node\Arg $arg) {
                                return new PhpParser\Node\Expr\ArrayItem($arg->value);
                            },
                            $args
                        );

                        $args = [
                            new PhpParser\Node\Arg(new PhpParser\Node\Scalar\String_($method_name_lc)),
                            new PhpParser\Node\Arg(new PhpParser\Node\Expr\Array_($array_values)),
                        ];

                        $method_id = $fq_class_name . '::__call';
                    }
                }

                $source_source = $statements_analyzer->getSource();

                /**
                 * @var \Psalm\Internal\Analyzer\ClassLikeAnalyzer|null
                 */
                $classlike_source = $source_source->getSource();
                $classlike_source_fqcln = $classlike_source ? $classlike_source->getFQCLN() : null;

                if ($var_id === '$this'
                    && $context->self
                    && $classlike_source_fqcln
                    && $fq_class_name !== $context->self
                    && $codebase->methodExists($context->self . '::' . $method_name_lc)
                ) {
                    $method_id = $context->self . '::' . $method_name_lc;
                    $fq_class_name = $context->self;
                }

                if ($intersection_types && !$codebase->methodExists($method_id)) {
                    foreach ($intersection_types as $intersection_type) {
                        if ($intersection_type instanceof Type\Atomic\TGenericParam) {
                            throw new \UnexpectedValueException('Shouldn’t get a generic param here');
                        }

                        $method_id = $intersection_type->value . '::' . $method_name_lc;
                        $fq_class_name = $intersection_type->value;

                        $does_class_exist = ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                            $statements_analyzer,
                            $fq_class_name,
                            new CodeLocation($source, $stmt->var),
                            $statements_analyzer->getSuppressedIssues()
                        );

                        if (!$does_class_exist) {
                            return false;
                        }

                        if ($codebase->methodExists($method_id)) {
                            break;
                        }
                    }
                }

                $source_method_id = $source instanceof FunctionLikeAnalyzer
                    ? $source->getMethodId()
                    : null;

                if (!$codebase->methods->methodExists(
                    $method_id,
                    $context->calling_method_id,
                    $method_id !== $source_method_id ? $code_location : null
                )) {
                    if ($config->use_phpdoc_methods_without_call) {
                        $class_storage = $codebase->classlike_storage_provider->get($fq_class_name);

                        if (isset($class_storage->pseudo_methods[$method_name_lc])) {
                            $has_valid_method_call_type = true;
                            $existent_method_ids[] = $method_id;

                            $pseudo_method_storage = $class_storage->pseudo_methods[$method_name_lc];

                            if (self::checkFunctionArguments(
                                $statements_analyzer,
                                $args,
                                $pseudo_method_storage->params,
                                $method_id,
                                $context
                            ) === false) {
                                return false;
                            }

                            $generic_params = [];

                            if (self::checkFunctionLikeArgumentsMatch(
                                $statements_analyzer,
                                $args,
                                null,
                                $pseudo_method_storage->params,
                                $pseudo_method_storage,
                                null,
                                $generic_params,
                                $code_location,
                                $context
                            ) === false) {
                                return false;
                            }

                            if ($pseudo_method_storage->return_type) {
                                $return_type_candidate = clone $pseudo_method_storage->return_type;

                                if (!$return_type) {
                                    $return_type = $return_type_candidate;
                                } else {
                                    $return_type = Type::combineUnionTypes($return_type_candidate, $return_type);
                                }

                                continue;
                            }

                            $return_type = Type::getMixed();

                            continue;
                        }
                    }

                    $non_existent_method_ids[] = $intersection_method_id ?: $method_id;
                    continue;
                }

                if ($context->collect_initializations && $context->calling_method_id) {
                    list($calling_method_class) = explode('::', $context->calling_method_id);
                    $codebase->file_reference_provider->addReferenceToClassMethod(
                        $calling_method_class . '::__construct',
                        strtolower($method_id)
                    );
                }

                $existent_method_ids[] = $method_id;

                $class_template_params = null;

                if ($stmt->var instanceof PhpParser\Node\Expr\Variable
                    && ($context->collect_initializations || $context->collect_mutations)
                    && $stmt->var->name === 'this'
                    && $source instanceof FunctionLikeAnalyzer
                ) {
                    self::collectSpecialInformation($source, $stmt->name->name, $context);
                }

                $class_storage = $codebase->classlike_storage_provider->get($fq_class_name);

                if ($class_storage->template_types) {
                    $class_template_params = [];

                    if ($lhs_type_part instanceof TGenericObject) {
                        $reversed_class_template_types = array_reverse(array_keys($class_storage->template_types));

                        $provided_type_param_count = count($lhs_type_part->type_params);

                        foreach ($reversed_class_template_types as $i => $type_name) {
                            if (isset($lhs_type_part->type_params[$provided_type_param_count - 1 - $i])) {
                                $class_template_params[$type_name] =
                                    $lhs_type_part->type_params[$provided_type_param_count - 1 - $i];
                            } else {
                                $class_template_params[$type_name] = Type::getMixed();
                            }
                        }
                    } else {
                        foreach ($class_storage->template_types as $type_name => $_) {
                            if (!$stmt->var instanceof PhpParser\Node\Expr\Variable
                                || $stmt->var->name !== 'this'
                            ) {
                                $class_template_params[$type_name] = Type::getMixed();
                            }
                        }
                    }
                }

                if (self::checkMethodArgs(
                    $method_id,
                    $args,
                    $class_template_params,
                    $context,
                    $code_location,
                    $statements_analyzer
                ) === false) {
                    return false;
                }

                switch (strtolower($stmt->name->name)) {
                    case '__tostring':
                        $return_type = Type::getString();
                        continue 2;
                }

                $call_map_id = strtolower(
                    $codebase->methods->getDeclaringMethodId($method_id) ?: $method_id
                );

                if ($method_name_lc === '__tostring') {
                    $return_type_candidate = Type::getString();
                } elseif ($call_map_id && CallMap::inCallMap($call_map_id)) {
                    if (($class_template_params || $class_storage->stubbed)
                        && isset($class_storage->methods[$method_name_lc])
                        && ($method_storage = $class_storage->methods[$method_name_lc])
                        && $method_storage->return_type
                    ) {
                        $return_type_candidate = clone $method_storage->return_type;

                        if ($class_template_params) {
                            $return_type_candidate->replaceTemplateTypesWithArgTypes(
                                $class_template_params
                            );
                        }
                    } else {
                        if ($call_map_id === 'domnode::appendchild'
                            && isset($args[0]->value->inferredType)
                            && $args[0]->value->inferredType->hasObjectType()
                        ) {
                            $return_type_candidate = clone $args[0]->value->inferredType;
                        } elseif ($call_map_id === 'simplexmlelement::asxml' && !count($args)) {
                            $return_type_candidate = Type::parseString('string|false');
                        } else {
                            $return_type_candidate = CallMap::getReturnTypeFromCallMap($call_map_id);
                            if ($return_type_candidate->isFalsable()) {
                                $return_type_candidate->ignore_falsable_issues = true;
                            }
                        }
                    }

                    $return_type_candidate = ExpressionAnalyzer::fleshOutType(
                        $codebase,
                        $return_type_candidate,
                        $fq_class_name,
                        $fq_class_name
                    );
                } else {
                    if (MethodAnalyzer::checkMethodVisibility(
                        $method_id,
                        $context->self,
                        $statements_analyzer->getSource(),
                        $name_code_location,
                        $statements_analyzer->getSuppressedIssues()
                    ) === false) {
                        return false;
                    }

                    if (MethodAnalyzer::checkMethodNotDeprecated(
                        $codebase,
                        $method_id,
                        $name_code_location,
                        $statements_analyzer->getSuppressedIssues()
                    ) === false) {
                        return false;
                    }

                    if (!self::checkMagicGetterOrSetterProperty(
                        $statements_analyzer,
                        $stmt,
                        $fq_class_name
                    )) {
                        return false;
                    }

                    $self_fq_class_name = $fq_class_name;

                    $return_type_candidate = $codebase->methods->getMethodReturnType(
                        $method_id,
                        $self_fq_class_name,
                        $args
                    );

                    if ($codebase->server_mode && $method_id) {
                        $codebase->analyzer->addNodeReference(
                            $statements_analyzer->getFilePath(),
                            $stmt->name,
                            $method_id . '()'
                        );
                    }

                    if (isset($stmt->inferredType)) {
                        $return_type_candidate = $stmt->inferredType;
                    }

                    if ($return_type_candidate) {
                        $return_type_candidate = clone $return_type_candidate;

                        if ($class_template_params) {
                            $return_type_candidate->replaceTemplateTypesWithArgTypes(
                                $class_template_params
                            );
                        }

                        $return_type_candidate = ExpressionAnalyzer::fleshOutType(
                            $codebase,
                            $return_type_candidate,
                            $self_fq_class_name,
                            $fq_class_name
                        );

                        $return_type_location = $codebase->methods->getMethodReturnTypeLocation(
                            $method_id,
                            $secondary_return_type_location
                        );

                        if ($secondary_return_type_location) {
                            $return_type_location = $secondary_return_type_location;
                        }

                        // only check the type locally if it's defined externally
                        if ($return_type_location && !$config->isInProjectDirs($return_type_location->file_path)) {
                            $return_type_candidate->check(
                                $statements_analyzer,
                                new CodeLocation($source, $stmt),
                                $statements_analyzer->getSuppressedIssues(),
                                $context->phantom_classes
                            );
                        }
                    } else {
                        $returns_by_ref =
                            $returns_by_ref
                                || $codebase->methods->getMethodReturnsByRef($method_id);
                    }

                    if (count($lhs_types) === 1) {
                        $method_storage = $codebase->methods->getUserMethodStorage($method_id);

                        if ($method_storage) {
                            if ($method_storage->assertions) {
                                self::applyAssertionsToContext(
                                    $method_storage->assertions,
                                    $args,
                                    $method_storage->template_typeof_params ?: [],
                                    $context,
                                    $statements_analyzer
                                );
                            }

                            if ($method_storage->if_true_assertions) {
                                $stmt->ifTrueAssertions = $method_storage->if_true_assertions;
                            }

                            if ($method_storage->if_false_assertions) {
                                $stmt->ifFalseAssertions = $method_storage->if_false_assertions;
                            }
                        }
                    }
                }

                if (!$args && $var_id) {
                    if ($config->memoize_method_calls) {
                        $method_var_id = $var_id . '->' . $method_name_lc . '()';

                        if (isset($context->vars_in_scope[$method_var_id])) {
                            $return_type_candidate = clone $context->vars_in_scope[$method_var_id];
                        } elseif ($return_type_candidate) {
                            $context->vars_in_scope[$method_var_id] = $return_type_candidate;
                        }
                    }
                }

                if ($config->after_method_checks) {
                    $file_manipulations = [];

                    $appearing_method_id = $codebase->methods->getAppearingMethodId($method_id);
                    $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);

                    foreach ($config->after_method_checks as $plugin_fq_class_name) {
                        $plugin_fq_class_name::afterMethodCallAnalysis(
                            $stmt,
                            $method_id,
                            $appearing_method_id,
                            $declaring_method_id,
                            $context,
                            $source,
                            $file_manipulations,
                            $return_type_candidate
                        );
                    }

                    if ($file_manipulations) {
                        /** @psalm-suppress MixedTypeCoercion */
                        FileManipulationBuffer::add($statements_analyzer->getFilePath(), $file_manipulations);
                    }
                }

                if ($return_type_candidate) {
                    if (!$return_type) {
                        $return_type = $return_type_candidate;
                    } else {
                        $return_type = Type::combineUnionTypes($return_type_candidate, $return_type);
                    }
                } else {
                    $return_type = Type::getMixed();
                }
            }

            if ($invalid_method_call_types) {
                $invalid_class_type = $invalid_method_call_types[0];

                if ($has_valid_method_call_type) {
                    if (IssueBuffer::accepts(
                        new PossiblyInvalidMethodCall(
                            'Cannot call method on possible ' . $invalid_class_type . ' variable ' . $var_id,
                            $name_code_location
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        return false;
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new InvalidMethodCall(
                            'Cannot call method on ' . $invalid_class_type . ' variable ' . $var_id,
                            $name_code_location
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        return false;
                    }
                }
            }

            if ($non_existent_method_ids) {
                if ($existent_method_ids) {
                    if (IssueBuffer::accepts(
                        new PossiblyUndefinedMethod(
                            'Method ' . $non_existent_method_ids[0] . ' does not exist',
                            $name_code_location,
                            $non_existent_method_ids[0]
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        return false;
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new UndefinedMethod(
                            'Method ' . $non_existent_method_ids[0] . ' does not exist',
                            $name_code_location,
                            $non_existent_method_ids[0]
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        return false;
                    }
                }

                return null;
            }

            $stmt->inferredType = $return_type;

            if ($returns_by_ref) {
                if (!$stmt->inferredType) {
                    $stmt->inferredType = Type::getMixed();
                }

                $stmt->inferredType->by_ref = $returns_by_ref;
            }
        }


        if ($method_id === null) {
            return self::checkMethodArgs(
                $method_id,
                $stmt->args,
                $found_generic_params,
                $context,
                new CodeLocation($statements_analyzer->getSource(), $stmt),
                $statements_analyzer
            );
        }

        if ($codebase->server_mode
            && (!$context->collect_initializations
                && !$context->collect_mutations)
            && isset($stmt->inferredType)
        ) {
            $codebase->analyzer->addNodeType(
                $statements_analyzer->getFilePath(),
                $stmt->name,
                (string) $stmt->inferredType
            );
        }

        if (!$config->remember_property_assignments_after_call && !$context->collect_initializations) {
            $context->removeAllObjectVars();
        }

        // if we called a method on this nullable variable, remove the nullable status here
        // because any further calls must have worked
        if ($var_id
            && $class_type
            && $has_valid_method_call_type
            && !$invalid_method_call_types
            && $existent_method_ids
            && ($class_type->from_docblock || $class_type->isNullable())
        ) {
            $keys_to_remove = [];

            foreach ($class_type->getTypes() as $key => $type) {
                if (!$type instanceof TNamedObject) {
                    $keys_to_remove[] = $key;
                } else {
                    $type->from_docblock = false;
                }
            }

            foreach ($keys_to_remove as $key) {
                $class_type->removeType($key);
            }

            $class_type->from_docblock = false;

            $context->removeVarFromConflictingClauses($var_id, null, $statements_analyzer);

            $context->vars_in_scope[$var_id] = $class_type;
        }
    }

    /**
     * Check properties accessed with magic getters and setters.
     * If `@psalm-seal-properties` is set, they must be defined.
     * If an `@property` annotation is specified, the setter must set something with the correct
     * type.
     *
     * @param StatementsAnalyzer $statements_analyzer
     * @param PhpParser\Node\Expr\MethodCall $stmt
     * @param string $fq_class_name
     *
     * @return bool
     */
    private static function checkMagicGetterOrSetterProperty(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\MethodCall $stmt,
        $fq_class_name
    ) {
        if (!$stmt->name instanceof PhpParser\Node\Identifier) {
            return true;
        }

        $method_name = strtolower($stmt->name->name);
        if (!in_array($method_name, ['__get', '__set'], true)) {
            return true;
        }

        $first_arg_value = $stmt->args[0]->value;
        if (!$first_arg_value instanceof PhpParser\Node\Scalar\String_) {
            return true;
        }

        $prop_name = $first_arg_value->value;
        $property_id = $fq_class_name . '::$' . $prop_name;
        $codebase = $statements_analyzer->getCodebase();
        $class_storage = $codebase->classlike_storage_provider->get($fq_class_name);

        switch ($method_name) {
            case '__set':
                // If `@psalm-seal-properties` is set, the property must be defined with
                // a `@property` annotation
                if ($class_storage->sealed_properties
                    && !isset($class_storage->pseudo_property_set_types['$' . $prop_name])
                    && IssueBuffer::accepts(
                        new UndefinedThisPropertyAssignment(
                            'Instance property ' . $property_id . ' is not defined',
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                            $property_id
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )
                ) {
                    return false;
                }

                // If a `@property` annotation is set, the type of the value passed to the
                // magic setter must match the annotation.
                $second_arg_type = isset($stmt->args[1]->value->inferredType)
                    ? $stmt->args[1]->value->inferredType
                    : null;

                if (isset($class_storage->pseudo_property_set_types['$' . $prop_name]) && $second_arg_type) {
                    $pseudo_set_type = ExpressionAnalyzer::fleshOutType(
                        $codebase,
                        $class_storage->pseudo_property_set_types['$' . $prop_name],
                        $fq_class_name,
                        $fq_class_name
                    );

                    $type_match_found = TypeAnalyzer::isContainedBy(
                        $codebase,
                        $second_arg_type,
                        $pseudo_set_type,
                        $second_arg_type->ignore_nullable_issues,
                        $second_arg_type->ignore_falsable_issues,
                        $has_scalar_match,
                        $type_coerced,
                        $type_coerced_from_mixed,
                        $to_string_cast
                    );

                    if ($type_coerced) {
                        if ($type_coerced_from_mixed) {
                            if (IssueBuffer::accepts(
                                new MixedTypeCoercion(
                                    $prop_name . ' expects \'' . $pseudo_set_type . '\', '
                                        . ' parent type `' . $second_arg_type . '` provided',
                                    new CodeLocation($statements_analyzer->getSource(), $stmt)
                                ),
                                $statements_analyzer->getSuppressedIssues()
                            )) {
                                // keep soldiering on
                            }
                        } else {
                            if (IssueBuffer::accepts(
                                new TypeCoercion(
                                    $prop_name . ' expects \'' . $pseudo_set_type . '\', '
                                        . ' parent type `' . $second_arg_type . '` provided',
                                    new CodeLocation($statements_analyzer->getSource(), $stmt)
                                ),
                                $statements_analyzer->getSuppressedIssues()
                            )) {
                                // keep soldiering on
                            }
                        }
                    }

                    if (!$type_match_found && !$type_coerced_from_mixed) {
                        if (TypeAnalyzer::canBeContainedBy(
                            $codebase,
                            $second_arg_type,
                            $pseudo_set_type
                        )) {
                            if (IssueBuffer::accepts(
                                new PossiblyInvalidPropertyAssignmentValue(
                                    $prop_name . ' with declared type \''
                                    . $pseudo_set_type
                                    . '\' cannot be assigned possibly different type \'' . $second_arg_type . '\'',
                                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                                    $property_id
                                ),
                                $statements_analyzer->getSuppressedIssues()
                            )) {
                                return false;
                            }
                        } else {
                            if (IssueBuffer::accepts(
                                new InvalidPropertyAssignmentValue(
                                    $prop_name . ' with declared type \''
                                    . $pseudo_set_type
                                    . '\' cannot be assigned type \'' . $second_arg_type . '\'',
                                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                                    $property_id
                                ),
                                $statements_analyzer->getSuppressedIssues()
                            )) {
                                return false;
                            }
                        }
                    }
                }
                break;

            case '__get':
                // If `@psalm-seal-properties` is set, the property must be defined with
                // a `@property` annotation
                if ($class_storage->sealed_properties
                    && !isset($class_storage->pseudo_property_get_types['$' . $prop_name])
                    && IssueBuffer::accepts(
                        new UndefinedThisPropertyFetch(
                            'Instance property ' . $property_id . ' is not defined',
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                            $property_id
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )
                ) {
                    return false;
                }

                if (isset($class_storage->pseudo_property_get_types['$' . $prop_name])) {
                    $stmt->inferredType = clone $class_storage->pseudo_property_get_types['$' . $prop_name];
                }

                break;
        }

        return true;
    }
}
