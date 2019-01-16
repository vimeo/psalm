<?php
namespace Psalm\Internal\Analyzer\Statements\Expression\Call;

use PhpParser;
use Psalm\Internal\Analyzer\ClassAnalyzer;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\MethodAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\AbstractInstantiation;
use Psalm\Issue\DeprecatedClass;
use Psalm\Issue\InterfaceInstantiation;
use Psalm\Issue\InternalClass;
use Psalm\Issue\InvalidStringClass;
use Psalm\Issue\MixedMethodCall;
use Psalm\Issue\TooManyArguments;
use Psalm\Issue\UndefinedClass;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic\TNamedObject;

/**
 * @internal
 */
class NewAnalyzer extends \Psalm\Internal\Analyzer\Statements\Expression\CallAnalyzer
{
    /**
     * @param   StatementsAnalyzer           $statements_analyzer
     * @param   PhpParser\Node\Expr\New_    $stmt
     * @param   Context                     $context
     *
     * @return  false|null
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\New_ $stmt,
        Context $context
    ) {
        $fq_class_name = null;

        $codebase = $statements_analyzer->getCodebase();
        $config = $codebase->config;

        $can_extend = false;

        if ($stmt->class instanceof PhpParser\Node\Name) {
            if (!in_array(strtolower($stmt->class->parts[0]), ['self', 'static', 'parent'], true)) {
                $aliases = $statements_analyzer->getAliases();

                if ($context->calling_method_id
                    && !$stmt->class instanceof PhpParser\Node\Name\FullyQualified
                ) {
                    $codebase->file_reference_provider->addReferenceToClassMethod(
                        $context->calling_method_id,
                        'use:' . $stmt->class->parts[0] . ':' . \md5($statements_analyzer->getFilePath())
                    );
                }

                $fq_class_name = ClassLikeAnalyzer::getFQCLNFromNameObject(
                    $stmt->class,
                    $aliases
                );

                $fq_class_name = $codebase->classlikes->getUnAliasedName($fq_class_name);
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
                        $can_extend = true;
                        break;
                }
            }

            if ($codebase->server_mode && $fq_class_name) {
                $codebase->analyzer->addNodeReference(
                    $statements_analyzer->getFilePath(),
                    $stmt->class,
                    $fq_class_name
                );
            }
        } elseif ($stmt->class instanceof PhpParser\Node\Stmt\Class_) {
            $statements_analyzer->analyze([$stmt->class], $context);
            $fq_class_name = ClassAnalyzer::getAnonymousClassName($stmt->class, $statements_analyzer->getFilePath());
        } else {
            ExpressionAnalyzer::analyze($statements_analyzer, $stmt->class, $context);

            if (isset($stmt->class->inferredType)) {
                $has_single_class = $stmt->class->inferredType->isSingleStringLiteral();

                if ($has_single_class) {
                    $fq_class_name = $stmt->class->inferredType->getSingleStringLiteral()->value;
                } else {
                    $generic_params = null;

                    if (self::checkMethodArgs(
                        null,
                        $stmt->args,
                        $generic_params,
                        $context,
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                        $statements_analyzer
                    ) === false) {
                        return false;
                    }
                }

                $new_type = null;

                foreach ($stmt->class->inferredType->getTypes() as $lhs_type_part) {
                    if ($lhs_type_part instanceof Type\Atomic\TGenericParamClass) {
                        if (!isset($stmt->inferredType)) {
                            $new_type_part = new Type\Atomic\TGenericParam(
                                $lhs_type_part->param_name,
                                $lhs_type_part->as_type ?: Type::parseString($lhs_type_part->as)
                            );

                            if ($new_type) {
                                $new_type = Type::combineUnionTypes(
                                    $new_type,
                                    new Type\Union([$new_type_part])
                                );
                            } else {
                                $new_type = new Type\Union([$new_type_part]);
                            }
                        }

                        continue;
                    }

                    if ($lhs_type_part instanceof Type\Atomic\TLiteralClassString
                        || $lhs_type_part instanceof Type\Atomic\TClassString
                    ) {
                        if (!isset($stmt->inferredType)) {
                            $class_name = $lhs_type_part instanceof Type\Atomic\TClassString
                                ? $lhs_type_part->as
                                : $lhs_type_part->value;

                            if ($lhs_type_part instanceof Type\Atomic\TClassString) {
                                $can_extend = true;
                            }

                            if ($class_name === 'object') {
                                if (IssueBuffer::accepts(
                                    new MixedMethodCall(
                                        'Cannot call constructor on an unknown class',
                                        new CodeLocation($statements_analyzer->getSource(), $stmt)
                                    ),
                                    $statements_analyzer->getSuppressedIssues()
                                )) {
                                    // fall through
                                }
                            }

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
                                new CodeLocation($statements_analyzer->getSource(), $stmt)
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    } elseif ($lhs_type_part instanceof Type\Atomic\TMixed
                        || $lhs_type_part instanceof Type\Atomic\TGenericParam
                    ) {
                        if (IssueBuffer::accepts(
                            new MixedMethodCall(
                                'Cannot call constructor on an unknown class',
                                new CodeLocation($statements_analyzer->getSource(), $stmt)
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            // fall through
                        }
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
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                            (string)$lhs_type_part
                        ),
                        $statements_analyzer->getSuppressedIssues()
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

                if (!$has_single_class) {
                    if ($new_type) {
                        $stmt->inferredType = $new_type;
                    }

                    return null;
                }
            } else {
                return null;
            }
        }

        if ($fq_class_name) {
            if ($context->check_classes) {
                if ($context->isPhantomClass($fq_class_name)) {
                    return null;
                }

                if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                    $statements_analyzer,
                    $fq_class_name,
                    new CodeLocation($statements_analyzer->getSource(), $stmt->class),
                    $statements_analyzer->getSuppressedIssues(),
                    false
                ) === false) {
                    return false;
                }

                if ($codebase->interfaceExists($fq_class_name)) {
                    if (IssueBuffer::accepts(
                        new InterfaceInstantiation(
                            'Interface ' . $fq_class_name . ' cannot be instantiated',
                            new CodeLocation($statements_analyzer->getSource(), $stmt->class)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        return false;
                    }

                    return null;
                }
            }

            $stmt->inferredType = new Type\Union([new TNamedObject($fq_class_name)]);

            if (strtolower($fq_class_name) !== 'stdclass' &&
                $context->check_classes &&
                $codebase->classlikes->classExists($fq_class_name)
            ) {
                $storage = $codebase->classlike_storage_provider->get($fq_class_name);

                // if we're not calling this constructor via new static()
                if ($storage->abstract && !$can_extend) {
                    if (IssueBuffer::accepts(
                        new AbstractInstantiation(
                            'Unable to instantiate a abstract class ' . $fq_class_name,
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        return false;
                    }
                }

                if ($storage->deprecated) {
                    if (IssueBuffer::accepts(
                        new DeprecatedClass(
                            $fq_class_name . ' is marked deprecated',
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                if ($storage->internal && $context->self) {
                    $self_root = preg_replace('/^([^\\\]+).*/', '$1', $context->self);
                    $declaring_root = preg_replace('/^([^\\\]+).*/', '$1', $fq_class_name);

                    if (strtolower($self_root) !== strtolower($declaring_root)) {
                        if (IssueBuffer::accepts(
                            new InternalClass(
                                $fq_class_name . ' is marked internal',
                                new CodeLocation($statements_analyzer->getSource(), $stmt)
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    }
                }

                if ($codebase->methods->methodExists(
                    $fq_class_name . '::__construct',
                    $context->calling_method_id,
                    $context->collect_references ? new CodeLocation($statements_analyzer->getSource(), $stmt) : null
                )) {
                    $method_id = $fq_class_name . '::__construct';

                    if (self::checkMethodArgs(
                        $method_id,
                        $stmt->args,
                        $found_generic_params,
                        $context,
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                        $statements_analyzer
                    ) === false) {
                        return false;
                    }

                    if (MethodAnalyzer::checkMethodVisibility(
                        $method_id,
                        $context->self,
                        $statements_analyzer->getSource(),
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                        $statements_analyzer->getSuppressedIssues()
                    ) === false) {
                        return false;
                    }

                    $generic_params = null;

                    if ($storage->template_types) {
                        foreach ($storage->template_types as $template_name => $_) {
                            if (isset($found_generic_params[$template_name])) {
                                $generic_params[] = $found_generic_params[$template_name];
                            } elseif ($storage->template_type_extends && $found_generic_params) {
                                $generic_params[] = self::getGenericParamForOffset(
                                    $template_name,
                                    $storage->template_type_extends,
                                    $found_generic_params
                                );
                            } else {
                                $generic_params[] = [Type::getMixed(), null];
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
                                array_map(
                                    /**
                                     * @param array{Type\Union, ?string} $i
                                     */
                                    function (array $i) : Type\Union {
                                        return $i[0];
                                    },
                                    $generic_params
                                )
                            ),
                        ]);
                    }
                } elseif ($stmt->args) {
                    if (IssueBuffer::accepts(
                        new TooManyArguments(
                            'Class ' . $fq_class_name . ' has no __construct, but arguments were passed',
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                            $fq_class_name . '::__construct'
                        ),
                        $statements_analyzer->getSuppressedIssues()
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

    /**
     * @param  string $template_name
     * @param  array<string, array<int|string, Type\Atomic>>  $template_type_extends
     * @param  array<string, array{Type\Union, ?string}>  $found_generic_params
     * @return array{Type\Union, ?string}
     */
    private static function getGenericParamForOffset(
        string $template_name,
        array $template_type_extends,
        array $found_generic_params
    ) {
        if (isset($found_generic_params[$template_name])) {
            return $found_generic_params[$template_name];
        }

        foreach ($template_type_extends as $type_map) {
            foreach ($type_map as $extended_template_name => $extended_type) {
                if (is_string($extended_template_name)
                    && $extended_type instanceof Type\Atomic\TGenericParam
                    && $extended_type->param_name === $template_name
                ) {
                    return self::getGenericParamForOffset(
                        $extended_template_name,
                        $template_type_extends,
                        $found_generic_params
                    );
                }
            }
        }

        return [Type::getMixed(), null];
    }
}
