<?php
namespace Psalm\Internal\Analyzer\Statements\Expression\Call;

use PhpParser;
use Psalm\Internal\Analyzer\ClassAnalyzer;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\MethodAnalyzer;
use Psalm\Internal\Analyzer\NamespaceAnalyzer;
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
use function in_array;
use function strtolower;
use function implode;
use function explode;
use function array_values;
use function is_string;

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
                    $codebase->file_reference_provider->addMethodReferenceToClassMember(
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

            if ($codebase->store_node_types && $fq_class_name) {
                $codebase->analyzer->addNodeReference(
                    $statements_analyzer->getFilePath(),
                    $stmt->class,
                    $codebase->classlikes->classExists($fq_class_name)
                        ? $fq_class_name
                        : '*' . implode('\\', $stmt->class->parts)
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
                    if ($lhs_type_part instanceof Type\Atomic\TTemplateParamClass) {
                        if (!isset($stmt->inferredType)) {
                            $new_type_part = new Type\Atomic\TTemplateParam(
                                $lhs_type_part->param_name,
                                $lhs_type_part->as_type
                                    ? new Type\Union([$lhs_type_part->as_type])
                                    : Type::parseString($lhs_type_part->as),
                                $lhs_type_part->defining_class
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
                        || $lhs_type_part instanceof Type\Atomic\TTemplateParam
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
            if ($codebase->alter_code) {
                $codebase->classlikes->handleClassLikeReferenceInMigration(
                    $codebase,
                    $statements_analyzer,
                    $stmt->class,
                    $fq_class_name,
                    $context->calling_method_id
                );
            }

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
                    return;
                }

                if ($codebase->interfaceExists($fq_class_name)) {
                    if (IssueBuffer::accepts(
                        new InterfaceInstantiation(
                            'Interface ' . $fq_class_name . ' cannot be instantiated',
                            new CodeLocation($statements_analyzer->getSource(), $stmt->class)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                    }

                    return null;
                }
            }

            $stmt->inferredType = new Type\Union([new TNamedObject($fq_class_name)]);

            if (strtolower($fq_class_name) !== 'stdclass' &&
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
                        return;
                    }
                }

                if ($storage->deprecated) {
                    if (IssueBuffer::accepts(
                        new DeprecatedClass(
                            $fq_class_name . ' is marked deprecated',
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                            $fq_class_name
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }


                if ($storage->psalm_internal && $context->self) {
                    if (! NamespaceAnalyzer::isWithin($context->self, $storage->psalm_internal)) {
                        if (IssueBuffer::accepts(
                            new InternalClass(
                                $fq_class_name . ' is marked internal to ' . $storage->psalm_internal,
                                new CodeLocation($statements_analyzer->getSource(), $stmt),
                                $fq_class_name
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    }
                }

                if ($storage->internal && $context->self) {
                    if (! NamespaceAnalyzer::nameSpaceRootsMatch($context->self, $fq_class_name)) {
                        if (IssueBuffer::accepts(
                            new InternalClass(
                                $fq_class_name . ' is marked internal',
                                new CodeLocation($statements_analyzer->getSource(), $stmt),
                                $fq_class_name
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
                    $context->collect_references ? new CodeLocation($statements_analyzer->getSource(), $stmt) : null,
                    null,
                    $statements_analyzer->getFilePath()
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
                        $context,
                        $statements_analyzer->getSource(),
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                        $statements_analyzer->getSuppressedIssues()
                    ) === false) {
                        return false;
                    }

                    $generic_param_types = null;

                    if ($storage->template_types) {
                        $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);

                        $declaring_fq_class_name = $declaring_method_id
                            ? explode('::', $declaring_method_id)[0]
                            : $fq_class_name;

                        foreach ($storage->template_types as $template_name => $base_type) {
                            if (isset($found_generic_params[$template_name][$fq_class_name])) {
                                $generic_param_types[] = $found_generic_params[$template_name][$fq_class_name][0];
                            } elseif ($storage->template_type_extends && $found_generic_params) {
                                $generic_param_types[] = self::getGenericParamForOffset(
                                    $declaring_fq_class_name,
                                    $template_name,
                                    $storage->template_type_extends,
                                    $found_generic_params
                                );
                            } else {
                                $generic_param_types[] = array_values($base_type)[0][0];
                            }
                        }
                    }

                    if ($generic_param_types) {
                        $stmt->inferredType = new Type\Union([
                            new Type\Atomic\TGenericObject(
                                $fq_class_name,
                                $generic_param_types
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
     * @param  array<string, array<int|string, Type\Union>>  $template_type_extends
     * @param  array<string, array<string, array{Type\Union}>>  $found_generic_params
     * @return Type\Union
     */
    private static function getGenericParamForOffset(
        string $fq_class_name,
        string $template_name,
        array $template_type_extends,
        array $found_generic_params
    ) {
        if (isset($found_generic_params[$template_name][$fq_class_name])) {
            return $found_generic_params[$template_name][$fq_class_name][0];
        }

        foreach ($template_type_extends as $type_map) {
            foreach ($type_map as $extended_template_name => $extended_type) {
                foreach ($extended_type->getTypes() as $extended_atomic_type) {
                    if (is_string($extended_template_name)
                        && $extended_atomic_type instanceof Type\Atomic\TTemplateParam
                        && $extended_atomic_type->param_name === $template_name
                        && $extended_template_name !== $template_name
                    ) {
                        return self::getGenericParamForOffset(
                            $fq_class_name,
                            $extended_template_name,
                            $template_type_extends,
                            $found_generic_params
                        );
                    }
                }
            }
        }

        return Type::getMixed();
    }
}
