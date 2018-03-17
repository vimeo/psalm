<?php
namespace Psalm\Checker\Statements\Expression\Call;

use PhpParser;
use Psalm\Checker\ClassLikeChecker;
use Psalm\Checker\MethodChecker;
use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\Checker\StatementsChecker;
use Psalm\CodeLocation;
use Psalm\Config;
use Psalm\Context;
use Psalm\FileManipulation\FileManipulationBuffer;
use Psalm\Issue\DeprecatedClass;
use Psalm\Issue\InvalidStringClass;
use Psalm\Issue\ParentNotFound;
use Psalm\Issue\UndefinedClass;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic\TNamedObject;

class StaticCallChecker extends \Psalm\Checker\Statements\Expression\CallChecker
{
    /**
     * @param   StatementsChecker               $statements_checker
     * @param   PhpParser\Node\Expr\StaticCall  $stmt
     * @param   Context                         $context
     *
     * @return  false|null
     */
    public static function analyze(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\StaticCall $stmt,
        Context $context
    ) {
        $method_id = null;

        $lhs_type = null;

        $file_checker = $statements_checker->getFileChecker();
        $project_checker = $file_checker->project_checker;
        $codebase = $project_checker->codebase;
        $source = $statements_checker->getSource();

        $stmt->inferredType = null;

        $config = $project_checker->config;

        if ($stmt->class instanceof PhpParser\Node\Name) {
            $fq_class_name = null;

            if (count($stmt->class->parts) === 1
                && in_array(strtolower($stmt->class->parts[0]), ['self', 'static', 'parent'], true)
            ) {
                if ($stmt->class->parts[0] === 'parent') {
                    $child_fq_class_name = $context->self;

                    $class_storage = $child_fq_class_name
                        ? $project_checker->classlike_storage_provider->get($child_fq_class_name)
                        : null;

                    if (!$class_storage || !$class_storage->parent_classes) {
                        if (IssueBuffer::accepts(
                            new ParentNotFound(
                                'Cannot call method on parent as this class does not extend another',
                                new CodeLocation($statements_checker->getSource(), $stmt)
                            ),
                            $statements_checker->getSuppressedIssues()
                        )) {
                            return false;
                        }

                        return;
                    }

                    $fq_class_name = reset($class_storage->parent_classes);

                    $class_storage = $project_checker->classlike_storage_provider->get($fq_class_name);

                    $fq_class_name = $class_storage->name;

                    if (is_string($stmt->name) && $class_storage->user_defined) {
                        $method_id = $fq_class_name . '::' . strtolower($stmt->name);

                        $old_context_include_location = $context->include_location;
                        $old_self = $context->self;
                        $context->include_location = new CodeLocation($statements_checker->getSource(), $stmt);
                        $context->self = $fq_class_name;

                        if ($context->collect_mutations) {
                            $file_checker->getMethodMutations($method_id, $context);
                        } elseif ($context->collect_initializations) {
                            $local_vars_in_scope = [];
                            $local_vars_possibly_in_scope = [];

                            foreach ($context->vars_in_scope as $var => $_) {
                                if (strpos($var, '$this->') !== 0 && $var !== '$this') {
                                    $local_vars_in_scope[$var] = $context->vars_in_scope[$var];
                                }
                            }

                            foreach ($context->vars_possibly_in_scope as $var => $_) {
                                if (strpos($var, '$this->') !== 0 && $var !== '$this') {
                                    $local_vars_possibly_in_scope[$var] = $context->vars_possibly_in_scope[$var];
                                }
                            }

                            if (!isset($context->initialized_methods[$method_id])) {
                                if ($context->initialized_methods === null) {
                                    $context->initialized_methods = [];
                                }

                                $context->initialized_methods[$method_id] = true;

                                $file_checker->getMethodMutations($method_id, $context);

                                foreach ($local_vars_in_scope as $var => $type) {
                                    $context->vars_in_scope[$var] = $type;
                                }

                                foreach ($local_vars_possibly_in_scope as $var => $type) {
                                    $context->vars_possibly_in_scope[$var] = $type;
                                }
                            }
                        }

                        $context->include_location = $old_context_include_location;
                        $context->self = $old_self;

                        if (isset($context->vars_in_scope['$this']) && $old_self) {
                            $context->vars_in_scope['$this'] = Type::parseString($old_self);
                        }
                    }
                } else {
                    $namespace = $statements_checker->getNamespace()
                        ? $statements_checker->getNamespace() . '\\'
                        : '';

                    $fq_class_name = $context->self ?: $namespace . $statements_checker->getClassName();
                }

                if ($context->isPhantomClass($fq_class_name)) {
                    return null;
                }
            } elseif ($context->check_classes) {
                $fq_class_name = ClassLikeChecker::getFQCLNFromNameObject(
                    $stmt->class,
                    $statements_checker->getAliases()
                );

                if ($context->isPhantomClass($fq_class_name)) {
                    return null;
                }

                $does_class_exist = false;

                if ($context->self) {
                    $self_storage = $project_checker->classlike_storage_provider->get($context->self);

                    if (isset($self_storage->used_traits[strtolower($fq_class_name)])) {
                        $fq_class_name = $context->self;
                        $does_class_exist = true;
                    }
                }

                if (!$does_class_exist) {
                    $does_class_exist = ClassLikeChecker::checkFullyQualifiedClassLikeName(
                        $statements_checker,
                        $fq_class_name,
                        new CodeLocation($source, $stmt->class),
                        $statements_checker->getSuppressedIssues(),
                        false
                    );
                }

                if (!$does_class_exist) {
                    return $does_class_exist;
                }
            }

            if ($fq_class_name) {
                $lhs_type = new Type\Union([new TNamedObject($fq_class_name)]);
            }
        } else {
            ExpressionChecker::analyze($statements_checker, $stmt->class, $context);
            $lhs_type = $stmt->class->inferredType;
        }

        if (!$context->check_methods || !$lhs_type) {
            return null;
        }

        $has_mock = false;

        foreach ($lhs_type->getTypes() as $lhs_type_part) {
            if (!$lhs_type_part instanceof TNamedObject) {
                // this is always OK
                if ($lhs_type_part instanceof Type\Atomic\TClassString) {
                    continue;
                }

                if ($lhs_type_part instanceof Type\Atomic\TString) {
                    if ($config->allow_string_standin_for_class
                        && !$lhs_type_part instanceof Type\Atomic\TNumericString
                    ) {
                        continue;
                    }

                    if (IssueBuffer::accepts(
                        new InvalidStringClass(
                            'String cannot be used as a class',
                            new CodeLocation($statements_checker->getSource(), $stmt)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        // fall through
                    }

                    continue;
                }

                if ($lhs_type_part instanceof Type\Atomic\TMixed) {
                    continue;
                }

                if ($lhs_type_part instanceof Type\Atomic\TNull
                    && $lhs_type->ignore_nullable_issues
                ) {
                    continue;
                }

                if (IssueBuffer::accepts(
                    new UndefinedClass(
                        'Type ' . $lhs_type_part . ' cannot be called as a class',
                        new CodeLocation($statements_checker->getSource(), $stmt)
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    // fall through
                }

                continue;
            }

            $fq_class_name = $lhs_type_part->value;

            $is_mock = ExpressionChecker::isMock($fq_class_name);

            $has_mock = $has_mock || $is_mock;

            $method_id = null;

            if (is_string($stmt->name) &&
                !$codebase->methodExists($fq_class_name . '::__callStatic') &&
                !$is_mock
            ) {
                $method_id = $fq_class_name . '::' . strtolower($stmt->name);
                $cased_method_id = $fq_class_name . '::' . $stmt->name;

                $does_method_exist = MethodChecker::checkMethodExists(
                    $project_checker,
                    $cased_method_id,
                    new CodeLocation($source, $stmt),
                    $statements_checker->getSuppressedIssues()
                );

                if (!$does_method_exist) {
                    return $does_method_exist;
                }

                $class_storage = $project_checker->classlike_storage_provider->get($fq_class_name);

                if ($class_storage->deprecated) {
                    if (IssueBuffer::accepts(
                        new DeprecatedClass(
                            $fq_class_name . ' is marked deprecated',
                            new CodeLocation($statements_checker->getSource(), $stmt)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                if (MethodChecker::checkMethodVisibility(
                    $method_id,
                    $context->self,
                    $statements_checker->getSource(),
                    new CodeLocation($source, $stmt),
                    $statements_checker->getSuppressedIssues()
                ) === false) {
                    return false;
                }

                if ($stmt->class instanceof PhpParser\Node\Name
                    && ($stmt->class->parts[0] !== 'parent' || $statements_checker->isStatic())
                    && (
                        !$context->self
                        || $statements_checker->isStatic()
                        || !$codebase->classExtends($context->self, $fq_class_name)
                    )
                ) {
                    if (MethodChecker::checkStatic(
                        $method_id,
                        strtolower($stmt->class->parts[0]) === 'self',
                        $project_checker,
                        new CodeLocation($source, $stmt),
                        $statements_checker->getSuppressedIssues()
                    ) === false) {
                        // fall through
                    }
                }

                if (MethodChecker::checkMethodNotDeprecated(
                    $project_checker,
                    $method_id,
                    new CodeLocation($statements_checker->getSource(), $stmt),
                    $statements_checker->getSuppressedIssues()
                ) === false) {
                    // fall through
                }

                if (self::checkMethodArgs(
                    $method_id,
                    $stmt->args,
                    $found_generic_params,
                    $context,
                    new CodeLocation($statements_checker->getSource(), $stmt),
                    $statements_checker
                ) === false) {
                    return false;
                }

                $fq_class_name = $stmt->class instanceof PhpParser\Node\Name && $stmt->class->parts === ['parent']
                    ? (string) $statements_checker->getFQCLN()
                    : $fq_class_name;

                $self_fq_class_name = $fq_class_name;

                $return_type_candidate = $codebase->methods->getMethodReturnType(
                    $method_id,
                    $self_fq_class_name
                );

                if ($return_type_candidate) {
                    $return_type_candidate = clone $return_type_candidate;

                    if ($found_generic_params) {
                        $return_type_candidate->replaceTemplateTypesWithArgTypes(
                            $found_generic_params
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
                }

                if ($config->after_method_checks) {
                    $file_manipulations = [];

                    $appearing_method_id = $codebase->methods->getAppearingMethodId($method_id);
                    $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);

                    $code_location = new CodeLocation($source, $stmt);

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
                    if (isset($stmt->inferredType)) {
                        $stmt->inferredType = Type::combineUnionTypes($stmt->inferredType, $return_type_candidate);
                    } else {
                        $stmt->inferredType = $return_type_candidate;
                    }
                }
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
    }
}
