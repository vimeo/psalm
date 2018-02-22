<?php
namespace Psalm\Checker\Statements\Expression\Call;

use PhpParser;
use Psalm\Checker\ClassLikeChecker;
use Psalm\Checker\FunctionLikeChecker;
use Psalm\Checker\MethodChecker;
use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\Checker\StatementsChecker;
use Psalm\Checker\TypeChecker;
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

class MethodCallChecker extends \Psalm\Checker\Statements\Expression\CallChecker
{
    /**
     * @param   StatementsChecker               $statements_checker
     * @param   PhpParser\Node\Expr\MethodCall  $stmt
     * @param   Context                         $context
     *
     * @return  false|null
     */
    public static function analyze(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\MethodCall $stmt,
        Context $context
    ) {
        if (ExpressionChecker::analyze($statements_checker, $stmt->var, $context) === false) {
            return false;
        }

        if (!is_string($stmt->name)) {
            if (ExpressionChecker::analyze($statements_checker, $stmt->name, $context) === false) {
                return false;
            }
        }

        $method_id = null;

        if ($stmt->var instanceof PhpParser\Node\Expr\Variable) {
            if (is_string($stmt->var->name) && $stmt->var->name === 'this' && !$statements_checker->getClassName()) {
                if (IssueBuffer::accepts(
                    new InvalidScope(
                        'Use of $this in non-class context',
                        new CodeLocation($statements_checker->getSource(), $stmt)
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    return false;
                }
            }
        }

        $var_id = ExpressionChecker::getVarId(
            $stmt->var,
            $statements_checker->getFQCLN(),
            $statements_checker
        );

        $class_type = $var_id && $context->hasVariable($var_id, $statements_checker)
            ? $context->vars_in_scope[$var_id]
            : null;

        if (isset($stmt->var->inferredType)) {
            /** @var Type\Union */
            $class_type = $stmt->var->inferredType;
        } elseif (!$class_type) {
            $stmt->inferredType = Type::getMixed();
        }

        $source = $statements_checker->getSource();

        if (!$context->check_methods || !$context->check_classes) {
            return null;
        }

        $has_mock = false;

        if ($class_type && is_string($stmt->name) && $class_type->isNull()) {
            if (IssueBuffer::accepts(
                new NullReference(
                    'Cannot call method ' . $stmt->name . ' on null variable ' . $var_id,
                    new CodeLocation($statements_checker->getSource(), $stmt->var)
                ),
                $statements_checker->getSuppressedIssues()
            )) {
                return false;
            }

            return null;
        }

        if ($class_type
            && is_string($stmt->name)
            && $class_type->isNullable()
            && !$class_type->ignore_nullable_issues
        ) {
            if (IssueBuffer::accepts(
                new PossiblyNullReference(
                    'Cannot call method ' . $stmt->name . ' on possibly null variable ' . $var_id,
                    new CodeLocation($statements_checker->getSource(), $stmt->var)
                ),
                $statements_checker->getSuppressedIssues()
            )) {
                return false;
            }
        }

        if ($class_type
            && is_string($stmt->name)
            && $class_type->isFalsable()
            && !$class_type->ignore_falsable_issues
        ) {
            if (IssueBuffer::accepts(
                new PossiblyFalseReference(
                    'Cannot call method ' . $stmt->name . ' on possibly false variable ' . $var_id,
                    new CodeLocation($statements_checker->getSource(), $stmt->var)
                ),
                $statements_checker->getSuppressedIssues()
            )) {
                return false;
            }
        }

        $config = Config::getInstance();
        $project_checker = $statements_checker->getFileChecker()->project_checker;
        $codebase = $project_checker->codebase;

        $non_existent_method_ids = [];
        $existent_method_ids = [];

        $invalid_method_call_types = [];
        $has_valid_method_call_type = false;

        $code_location = new CodeLocation($source, $stmt);

        $returns_by_ref = false;

        if ($class_type) {
            $return_type = null;

            foreach ($class_type->getTypes() as $class_type_part) {
                if (!$class_type_part instanceof TNamedObject) {
                    switch (get_class($class_type_part)) {
                        case 'Psalm\\Type\\Atomic\\TNull':
                        case 'Psalm\\Type\\Atomic\\TFalse':
                            // handled above
                            break;

                        case 'Psalm\\Type\\Atomic\\TInt':
                        case 'Psalm\\Type\\Atomic\\TBool':
                        case 'Psalm\\Type\\Atomic\\TTrue':
                        case 'Psalm\\Type\\Atomic\\TArray':
                        case 'Psalm\\Type\\Atomic\\TString':
                        case 'Psalm\\Type\\Atomic\\TNumericString':
                            $invalid_method_call_types[] = (string)$class_type_part;
                            break;

                        case 'Psalm\\Type\\Atomic\\TMixed':
                        case 'Psalm\\Type\\Atomic\\TObject':
                            $codebase->analyzer->incrementMixedCount($statements_checker->getCheckedFilePath());

                            if (IssueBuffer::accepts(
                                new MixedMethodCall(
                                    'Cannot call method on a mixed variable ' . $var_id,
                                    $code_location
                                ),
                                $statements_checker->getSuppressedIssues()
                            )) {
                                // fall through
                            }

                            $return_type = Type::getMixed();
                            break;
                    }

                    continue;
                }

                $codebase->analyzer->incrementNonMixedCount($statements_checker->getCheckedFilePath());

                $has_valid_method_call_type = true;

                $fq_class_name = $class_type_part->value;

                $intersection_types = $class_type_part->getIntersectionTypes();

                $is_mock = ExpressionChecker::isMock($fq_class_name);

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
                    $does_class_exist = ClassLikeChecker::checkFullyQualifiedClassLikeName(
                        $statements_checker,
                        $fq_class_name,
                        $code_location,
                        $statements_checker->getSuppressedIssues()
                    );
                }

                if (!$does_class_exist) {
                    return $does_class_exist;
                }

                if ($fq_class_name === 'iterable') {
                    if (IssueBuffer::accepts(
                        new UndefinedMethod(
                            $fq_class_name . ' has no defined methods',
                            $code_location
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }

                    return;
                }

                if (!is_string($stmt->name)) {
                    $return_type = Type::getMixed();
                    break;
                }

                $method_name_lc = strtolower($stmt->name);

                $method_id = $fq_class_name . '::' . $method_name_lc;

                if ($codebase->methodExists($fq_class_name . '::__call')) {
                    if (!$codebase->methodExists($method_id)
                        || !MethodChecker::isMethodVisible(
                            $method_id,
                            $context->self,
                            $statements_checker->getSource()
                        )
                    ) {
                        $return_type = Type::getMixed();
                        continue;
                    }
                }

                if ($var_id === '$this' &&
                    $context->self &&
                    $fq_class_name !== $context->self &&
                    $codebase->methodExists($context->self . '::' . $method_name_lc)
                ) {
                    $method_id = $context->self . '::' . $method_name_lc;
                    $fq_class_name = $context->self;
                }

                if ($intersection_types && !$codebase->methodExists($method_id)) {
                    foreach ($intersection_types as $intersection_type) {
                        $method_id = $intersection_type->value . '::' . $method_name_lc;
                        $fq_class_name = $intersection_type->value;

                        if ($codebase->methodExists($method_id)) {
                            break;
                        }
                    }
                }

                $cased_method_id = $fq_class_name . '::' . $stmt->name;

                if (!$codebase->methodExists($method_id, $code_location)) {
                    $non_existent_method_ids[] = $method_id;
                    continue;
                }

                $existent_method_ids[] = $method_id;

                $class_template_params = null;

                if ($stmt->var instanceof PhpParser\Node\Expr\Variable
                    && ($context->collect_initializations || $context->collect_mutations)
                    && $stmt->var->name === 'this'
                    && $source instanceof FunctionLikeChecker
                ) {
                    self::collectSpecialInformation($source, $stmt->name, $context);
                }

                $class_storage = $project_checker->classlike_storage_provider->get($fq_class_name);

                if ($class_storage->template_types) {
                    $class_template_params = [];

                    if ($class_type_part instanceof TGenericObject) {
                        $reversed_class_template_types = array_reverse(array_keys($class_storage->template_types));

                        $provided_type_param_count = count($class_type_part->type_params);

                        foreach ($reversed_class_template_types as $i => $type_name) {
                            if (isset($class_type_part->type_params[$provided_type_param_count - 1 - $i])) {
                                $class_template_params[$type_name] =
                                    $class_type_part->type_params[$provided_type_param_count - 1 - $i];
                            } else {
                                $class_template_params[$type_name] = Type::getMixed();
                            }
                        }
                    } else {
                        foreach ($class_storage->template_types as $type_name => $_) {
                            $class_template_params[$type_name] = Type::getMixed();
                        }
                    }
                }

                if (self::checkMethodArgs(
                    $method_id,
                    $stmt->args,
                    $class_template_params,
                    $context,
                    $code_location,
                    $statements_checker
                ) === false) {
                    return false;
                }

                switch (strtolower($stmt->name)) {
                    case '__tostring':
                        $return_type = Type::getString();
                        continue;
                }

                if ($method_name_lc === '__tostring') {
                    $return_type_candidate = Type::getString();
                } elseif (CallMap::inCallMap($cased_method_id)) {
                    if ($class_template_params
                        && isset($class_storage->methods[$method_name_lc])
                        && ($method_storage = $class_storage->methods[$method_name_lc])
                        && $method_storage->return_type
                    ) {
                        $return_type_candidate = clone $method_storage->return_type;

                        $return_type_candidate->replaceTemplateTypesWithArgTypes(
                            $class_template_params
                        );
                    } else {
                        $return_type_candidate = CallMap::getReturnTypeFromCallMap($method_id);
                    }

                    $return_type_candidate = ExpressionChecker::fleshOutType(
                        $project_checker,
                        $return_type_candidate,
                        $fq_class_name,
                        $fq_class_name
                    );
                } else {
                    if (MethodChecker::checkMethodVisibility(
                        $method_id,
                        $context->self,
                        $statements_checker->getSource(),
                        $code_location,
                        $statements_checker->getSuppressedIssues()
                    ) === false) {
                        return false;
                    }

                    if (MethodChecker::checkMethodNotDeprecated(
                        $project_checker,
                        $method_id,
                        $code_location,
                        $statements_checker->getSuppressedIssues()
                    ) === false) {
                        return false;
                    }

                    if (!self::checkMagicGetterOrSetterProperty(
                        $statements_checker,
                        $project_checker,
                        $stmt,
                        $fq_class_name
                    )) {
                        return false;
                    }

                    $self_fq_class_name = $fq_class_name;

                    $return_type_candidate = $codebase->methods->getMethodReturnType(
                        $method_id,
                        $self_fq_class_name
                    );

                    if ($return_type_candidate) {
                        $return_type_candidate = clone $return_type_candidate;

                        if ($class_template_params) {
                            $return_type_candidate->replaceTemplateTypesWithArgTypes(
                                $class_template_params
                            );
                        }

                        $return_type_candidate = ExpressionChecker::fleshOutType(
                            $project_checker,
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
                                $statements_checker,
                                new CodeLocation($source, $stmt),
                                $statements_checker->getSuppressedIssues(),
                                $context->getPhantomClasses()
                            );
                        }
                    } else {
                        $returns_by_ref =
                            $returns_by_ref
                                || $codebase->methods->getMethodReturnsByRef($method_id);
                    }
                }

                if ($config->after_method_checks) {
                    $file_manipulations = [];

                    $appearing_method_id = $codebase->methods->getAppearingMethodId($method_id);
                    $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);

                    foreach ($config->after_method_checks as $plugin_fq_class_name) {
                        $plugin_fq_class_name::afterMethodCallCheck(
                            $statements_checker,
                            $method_id,
                            $appearing_method_id,
                            $declaring_method_id,
                            $stmt->args,
                            $code_location,
                            $file_manipulations,
                            $return_type_candidate
                        );
                    }

                    if ($file_manipulations) {
                        /** @psalm-suppress MixedTypeCoercion */
                        FileManipulationBuffer::add($statements_checker->getFilePath(), $file_manipulations);
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
                            $code_location
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new InvalidMethodCall(
                            'Cannot call method on ' . $invalid_class_type . ' variable ' . $var_id,
                            $code_location
                        ),
                        $statements_checker->getSuppressedIssues()
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
                            $code_location
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new UndefinedMethod(
                            'Method ' . $non_existent_method_ids[0] . ' does not exist',
                            $code_location
                        ),
                        $statements_checker->getSuppressedIssues()
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
                new CodeLocation($statements_checker->getSource(), $stmt),
                $statements_checker
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

            $context->removeVarFromConflictingClauses($var_id, null, $statements_checker);

            $context->vars_in_scope[$var_id] = $class_type;
        }
    }

    /**
     * Check properties accessed with magic getters and setters.
     * If `@psalm-seal-properties` is set, they must be defined.
     * If an `@property` annotation is specified, the setter must set something with the correct
     * type.
     *
     * @param StatementsChecker $statements_checker
     * @param \Psalm\Checker\ProjectChecker $project_checker
     * @param PhpParser\Node\Expr\MethodCall $stmt
     * @param string $fq_class_name
     *
     * @return bool
     */
    private static function checkMagicGetterOrSetterProperty(
        StatementsChecker $statements_checker,
        \Psalm\Checker\ProjectChecker $project_checker,
        PhpParser\Node\Expr\MethodCall $stmt,
        $fq_class_name
    ) {
        if (!is_string($stmt->name)) {
            return true;
        }

        $method_name = strtolower($stmt->name);
        if (!in_array($method_name, ['__get', '__set'], true)) {
            return true;
        }

        $first_arg_value = $stmt->args[0]->value;
        if (!$first_arg_value instanceof PhpParser\Node\Scalar\String_) {
            return true;
        }

        $prop_name = $first_arg_value->value;
        $property_id = $fq_class_name . '::$' . $prop_name;
        $class_storage = $project_checker->classlike_storage_provider->get($fq_class_name);

        switch ($method_name) {
            case '__set':
                // If `@psalm-seal-properties` is set, the property must be defined with
                // a `@property` annotation
                if ($class_storage->sealed_properties
                    && !isset($class_storage->pseudo_property_set_types['$' . $prop_name])
                    && IssueBuffer::accepts(
                        new UndefinedThisPropertyAssignment(
                            'Instance property ' . $property_id . ' is not defined',
                            new CodeLocation($statements_checker->getSource(), $stmt)
                        ),
                        $statements_checker->getSuppressedIssues()
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
                    $pseudo_set_type = ExpressionChecker::fleshOutType(
                        $project_checker,
                        $class_storage->pseudo_property_set_types['$' . $prop_name],
                        $fq_class_name,
                        $fq_class_name
                    );

                    $type_match_found = TypeChecker::isContainedBy(
                        $project_checker->codebase,
                        $second_arg_type,
                        $pseudo_set_type,
                        false,
                        false,
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
                                    new CodeLocation($statements_checker->getSource(), $stmt)
                                ),
                                $statements_checker->getSuppressedIssues()
                            )) {
                                // keep soldiering on
                            }
                        } else {
                            if (IssueBuffer::accepts(
                                new TypeCoercion(
                                    $prop_name . ' expects \'' . $pseudo_set_type . '\', '
                                        . ' parent type `' . $second_arg_type . '` provided',
                                    new CodeLocation($statements_checker->getSource(), $stmt)
                                ),
                                $statements_checker->getSuppressedIssues()
                            )) {
                                // keep soldiering on
                            }
                        }
                    }

                    if (!$type_match_found && !$type_coerced_from_mixed) {
                        if (IssueBuffer::accepts(
                            new InvalidPropertyAssignmentValue(
                                $prop_name . ' with declared type \''
                                . $pseudo_set_type
                                . '\' cannot be assigned type \'' . $second_arg_type . '\'',
                                new CodeLocation($statements_checker->getSource(), $stmt)
                            ),
                            $statements_checker->getSuppressedIssues()
                        )) {
                            return false;
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
                            new CodeLocation($statements_checker->getSource(), $stmt)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )
                ) {
                    return false;
                }
                break;
        }

        return true;
    }
}
