<?php
namespace Psalm\Checker\Statements\Expression\Call;

use PhpParser;
use Psalm\Checker\ClassChecker;
use Psalm\Checker\ClassLikeChecker;
use Psalm\Checker\MethodChecker;
use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\Checker\StatementsChecker;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\AbstractInstantiation;
use Psalm\Issue\DeprecatedClass;
use Psalm\Issue\InterfaceInstantiation;
use Psalm\Issue\InvalidStringClass;
use Psalm\Issue\TooManyArguments;
use Psalm\Issue\UndefinedClass;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic\TNamedObject;

class NewChecker extends \Psalm\Checker\Statements\Expression\CallChecker
{
    /**
     * @param   StatementsChecker           $statements_checker
     * @param   PhpParser\Node\Expr\New_    $stmt
     * @param   Context                     $context
     *
     * @return  false|null
     */
    public static function analyze(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\New_ $stmt,
        Context $context
    ) {
        $fq_class_name = null;

        $project_checker = $statements_checker->getFileChecker()->project_checker;
        $codebase = $project_checker->codebase;
        $config = $project_checker->config;

        $late_static = false;

        if ($stmt->class instanceof PhpParser\Node\Name) {
            if (!in_array(strtolower($stmt->class->parts[0]), ['self', 'static', 'parent'], true)) {
                $fq_class_name = ClassLikeChecker::getFQCLNFromNameObject(
                    $stmt->class,
                    $statements_checker->getAliases()
                );

                if ($context->check_classes) {
                    if ($context->isPhantomClass($fq_class_name)) {
                        return null;
                    }

                    if (ClassLikeChecker::checkFullyQualifiedClassLikeName(
                        $statements_checker,
                        $fq_class_name,
                        new CodeLocation($statements_checker->getSource(), $stmt->class),
                        $statements_checker->getSuppressedIssues(),
                        false
                    ) === false) {
                        return false;
                    }

                    if ($codebase->interfaceExists($fq_class_name)) {
                        if (IssueBuffer::accepts(
                            new InterfaceInstantiation(
                                'Interface ' . $fq_class_name . ' cannot be instantiated',
                                new CodeLocation($statements_checker->getSource(), $stmt->class)
                            ),
                            $statements_checker->getSuppressedIssues()
                        )) {
                            return false;
                        }

                        return null;
                    }
                }
            } else {
                switch ($stmt->class->parts[0]) {
                    case 'self':
                        $fq_class_name = $context->self;
                        break;

                    case 'parent':
                        $fq_class_name = $context->parent;
                        break;

                    case 'static':
                        // @todo maybe we can do better here
                        $fq_class_name = $context->self;
                        $late_static = true;
                        break;
                }
            }

            if ($codebase->server_mode && $fq_class_name) {
                $codebase->analyzer->addNodeReference(
                    $statements_checker->getFilePath(),
                    $stmt->class,
                    $fq_class_name
                );
            }
        } elseif ($stmt->class instanceof PhpParser\Node\Stmt\Class_) {
            $statements_checker->analyze([$stmt->class], $context);
            $fq_class_name = ClassChecker::getAnonymousClassName($stmt->class, $statements_checker->getFilePath());
        } else {
            ExpressionChecker::analyze($statements_checker, $stmt->class, $context);

            $generic_params = null;

            if (self::checkMethodArgs(
                null,
                $stmt->args,
                $generic_params,
                $context,
                new CodeLocation($statements_checker->getSource(), $stmt),
                $statements_checker
            ) === false) {
                return false;
            }

            if (isset($stmt->class->inferredType)) {
                $new_type = null;

                foreach ($stmt->class->inferredType->getTypes() as $lhs_type_part) {
                    // this is always OK
                    if ($lhs_type_part instanceof Type\Atomic\TLiteralClassString
                        || $lhs_type_part instanceof Type\Atomic\TClassString
                    ) {
                        if (!isset($stmt->inferredType)) {
                            $class_name = $lhs_type_part instanceof Type\Atomic\TClassString
                                ? 'object'
                                : $lhs_type_part->value;

                            if ($new_type) {
                                $new_type = Type::combineUnionTypes(
                                    $new_type,
                                    Type::parseString($class_name)
                                );
                            } else {
                                $new_type = Type::parseString($class_name);
                            }
                        }

                        continue;
                    }

                    if ($lhs_type_part instanceof Type\Atomic\TString) {
                        if ($config->allow_string_standin_for_class
                            && !$lhs_type_part instanceof Type\Atomic\TNumericString
                        ) {
                            // do nothing
                        } elseif (IssueBuffer::accepts(
                            new InvalidStringClass(
                                'String cannot be used as a class',
                                new CodeLocation($statements_checker->getSource(), $stmt)
                            ),
                            $statements_checker->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    } elseif ($lhs_type_part instanceof Type\Atomic\TMixed
                        || $lhs_type_part instanceof Type\Atomic\TGenericParam
                    ) {
                        // do nothing
                    } elseif ($lhs_type_part instanceof Type\Atomic\TFalse
                        && $stmt->class->inferredType->ignore_falsable_issues
                    ) {
                        // do nothing
                    } elseif ($lhs_type_part instanceof Type\Atomic\TNull
                        && $stmt->class->inferredType->ignore_nullable_issues
                    ) {
                        // do nothing
                    } elseif (IssueBuffer::accepts(
                        new UndefinedClass(
                            'Type ' . $lhs_type_part . ' cannot be called as a class',
                            new CodeLocation($statements_checker->getSource(), $stmt),
                            (string)$lhs_type_part
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        // fall through
                    }

                    if ($new_type) {
                        $new_type = Type::combineUnionTypes(
                            $new_type,
                            Type::getObject()
                        );
                    } else {
                        $new_type = Type::getObject();
                    }
                }

                if ($new_type) {
                    $stmt->inferredType = $new_type;
                }
            }

            return null;
        }

        if ($fq_class_name) {
            $stmt->inferredType = new Type\Union([new TNamedObject($fq_class_name)]);

            if (strtolower($fq_class_name) !== 'stdclass' &&
                $context->check_classes &&
                $codebase->classlikes->classExists($fq_class_name)
            ) {
                $storage = $project_checker->classlike_storage_provider->get($fq_class_name);

                // if we're not calling this constructor via new static()
                if ($storage->abstract && !$late_static) {
                    if (IssueBuffer::accepts(
                        new AbstractInstantiation(
                            'Unable to instantiate a abstract class ' . $fq_class_name,
                            new CodeLocation($statements_checker->getSource(), $stmt)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }
                }

                if ($storage->deprecated) {
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

                if ($codebase->methods->methodExists(
                    $fq_class_name . '::__construct',
                    $context->calling_method_id,
                    $context->collect_references ? new CodeLocation($statements_checker->getSource(), $stmt) : null
                )) {
                    $method_id = $fq_class_name . '::__construct';

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

                    if (MethodChecker::checkMethodVisibility(
                        $method_id,
                        $context->self,
                        $statements_checker->getSource(),
                        new CodeLocation($statements_checker->getSource(), $stmt),
                        $statements_checker->getSuppressedIssues()
                    ) === false) {
                        return false;
                    }

                    $generic_params = null;

                    if ($storage->template_types) {
                        foreach ($storage->template_types as $template_name => $_) {
                            if (isset($found_generic_params[$template_name])) {
                                $generic_params[] = $found_generic_params[$template_name];
                            } else {
                                $generic_params[] = Type::getMixed();
                            }
                        }
                    }

                    if ($fq_class_name === 'ArrayIterator' && isset($stmt->args[0]->value->inferredType)) {
                        $first_arg_type = $stmt->args[0]->value->inferredType;

                        if ($first_arg_type->hasGeneric()) {
                            $key_type = null;
                            $value_type = null;

                            foreach ($first_arg_type->getTypes() as $type) {
                                if ($type instanceof Type\Atomic\TArray) {
                                    $first_type_param = count($type->type_params) ? $type->type_params[0] : null;
                                    $last_type_param = $type->type_params[count($type->type_params) - 1];

                                    if ($value_type === null) {
                                        $value_type = clone $last_type_param;
                                    } else {
                                        $value_type = Type::combineUnionTypes($value_type, $last_type_param);
                                    }

                                    if (!$key_type || !$first_type_param) {
                                        $key_type = $first_type_param ? clone $first_type_param : Type::getMixed();
                                    } else {
                                        $key_type = Type::combineUnionTypes($key_type, $first_type_param);
                                    }
                                }
                            }

                            if ($key_type === null) {
                                throw new \UnexpectedValueException('$key_type cannot be null');
                            }

                            if ($value_type === null) {
                                throw new \UnexpectedValueException('$value_type cannot be null');
                            }

                            $stmt->inferredType = new Type\Union([
                                new Type\Atomic\TGenericObject(
                                    $fq_class_name,
                                    [
                                        $key_type,
                                        $value_type,
                                    ]
                                ),
                            ]);
                        }
                    } elseif ($generic_params) {
                        $stmt->inferredType = new Type\Union([
                            new Type\Atomic\TGenericObject(
                                $fq_class_name,
                                $generic_params
                            ),
                        ]);
                    }
                } elseif ($stmt->args) {
                    if (IssueBuffer::accepts(
                        new TooManyArguments(
                            'Class ' . $fq_class_name . ' has no __construct, but arguments were passed',
                            new CodeLocation($statements_checker->getSource(), $stmt),
                            $fq_class_name . '::__construct'
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            }
        }

        if (!$config->remember_property_assignments_after_call && !$context->collect_initializations) {
            $context->removeAllObjectVars();
        }

        return null;
    }
}
