<?php
namespace Psalm\Internal\Analyzer\Statements\Expression\Call;

use PhpParser;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\MethodAnalyzer;
use Psalm\Internal\Analyzer\NamespaceAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;
use Psalm\Issue\DeprecatedClass;
use Psalm\Issue\ImpureMethodCall;
use Psalm\Issue\InvalidStringClass;
use Psalm\Issue\InternalClass;
use Psalm\Issue\MixedMethodCall;
use Psalm\Issue\ParentNotFound;
use Psalm\Issue\UndefinedClass;
use Psalm\Issue\UndefinedMethod;
use Psalm\IssueBuffer;
use Psalm\Storage\Assertion;
use Psalm\Type;
use Psalm\Type\Atomic\TNamedObject;
use function count;
use function in_array;
use function strtolower;
use function array_map;
use function explode;
use function strpos;
use function is_string;
use function strlen;
use function substr;
use Psalm\Internal\Taint\Source;

/**
 * @internal
 */
class StaticCallAnalyzer extends \Psalm\Internal\Analyzer\Statements\Expression\CallAnalyzer
{
    /**
     * @param   StatementsAnalyzer               $statements_analyzer
     * @param   PhpParser\Node\Expr\StaticCall  $stmt
     * @param   Context                         $context
     *
     * @return  false|null
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\StaticCall $stmt,
        Context $context
    ) {
        $method_id = null;

        $lhs_type = null;

        $file_analyzer = $statements_analyzer->getFileAnalyzer();
        $codebase = $statements_analyzer->getCodebase();
        $source = $statements_analyzer->getSource();

        $stmt->inferredType = null;

        $config = $codebase->config;

        if ($stmt->class instanceof PhpParser\Node\Name) {
            $fq_class_name = null;

            if (count($stmt->class->parts) === 1
                && in_array(strtolower($stmt->class->parts[0]), ['self', 'static', 'parent'], true)
            ) {
                if ($stmt->class->parts[0] === 'parent') {
                    $child_fq_class_name = $context->self;

                    $class_storage = $child_fq_class_name
                        ? $codebase->classlike_storage_provider->get($child_fq_class_name)
                        : null;

                    if (!$class_storage || !$class_storage->parent_class) {
                        if (IssueBuffer::accepts(
                            new ParentNotFound(
                                'Cannot call method on parent as this class does not extend another',
                                new CodeLocation($statements_analyzer->getSource(), $stmt)
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            return false;
                        }

                        return;
                    }

                    $fq_class_name = $class_storage->parent_class;

                    $class_storage = $codebase->classlike_storage_provider->get($fq_class_name);

                    $fq_class_name = $class_storage->name;
                } elseif ($context->self) {
                    if ($stmt->class->parts[0] === 'static' && isset($context->vars_in_scope['$this'])) {
                        $fq_class_name = (string) $context->vars_in_scope['$this'];
                        $lhs_type = clone $context->vars_in_scope['$this'];
                    } else {
                        $fq_class_name = $context->self;
                    }
                } else {
                    $namespace = $statements_analyzer->getNamespace()
                        ? $statements_analyzer->getNamespace() . '\\'
                        : '';

                    $fq_class_name = $namespace . $statements_analyzer->getClassName();
                }

                if ($context->isPhantomClass($fq_class_name)) {
                    return null;
                }
            } elseif ($context->check_classes) {
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

                if ($context->isPhantomClass($fq_class_name)) {
                    return null;
                }

                $does_class_exist = false;

                if ($context->self) {
                    $self_storage = $codebase->classlike_storage_provider->get($context->self);

                    if (isset($self_storage->used_traits[strtolower($fq_class_name)])) {
                        $fq_class_name = $context->self;
                        $does_class_exist = true;
                    }
                }

                if (!$does_class_exist) {
                    $does_class_exist = ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                        $statements_analyzer,
                        $fq_class_name,
                        new CodeLocation($source, $stmt->class),
                        $statements_analyzer->getSuppressedIssues(),
                        false,
                        false,
                        false
                    );
                }

                if (!$does_class_exist) {
                    return $does_class_exist;
                }
            }

            if ($codebase->store_node_types
                && $fq_class_name
                && !$context->collect_initializations
                && !$context->collect_mutations
            ) {
                $codebase->analyzer->addNodeReference(
                    $statements_analyzer->getFilePath(),
                    $stmt->class,
                    $fq_class_name
                );
            }

            if ($fq_class_name && !$lhs_type) {
                $lhs_type = new Type\Union([new TNamedObject($fq_class_name)]);
            }
        } else {
            ExpressionAnalyzer::analyze($statements_analyzer, $stmt->class, $context);
            $lhs_type = $stmt->class->inferredType ?? Type::getMixed();
        }

        if (!$lhs_type) {
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
        $moved_call = false;

        foreach ($lhs_type->getTypes() as $lhs_type_part) {
            $intersection_types = [];

            if ($lhs_type_part instanceof TNamedObject) {
                $fq_class_name = $lhs_type_part->value;

                if (!ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                    $statements_analyzer,
                    $fq_class_name,
                    new CodeLocation($source, $stmt->class),
                    $statements_analyzer->getSuppressedIssues(),
                    false
                )) {
                    return false;
                }

                $intersection_types = $lhs_type_part->extra_types;
            } elseif ($lhs_type_part instanceof Type\Atomic\TClassString
                && $lhs_type_part->as_type
            ) {
                $fq_class_name = $lhs_type_part->as_type->value;

                if (!ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                    $statements_analyzer,
                    $fq_class_name,
                    new CodeLocation($source, $stmt->class),
                    $statements_analyzer->getSuppressedIssues(),
                    false
                )) {
                    return false;
                }

                $intersection_types = $lhs_type_part->as_type->extra_types;
            } elseif ($lhs_type_part instanceof Type\Atomic\TLiteralClassString) {
                $fq_class_name = $lhs_type_part->value;

                if (!ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                    $statements_analyzer,
                    $fq_class_name,
                    new CodeLocation($source, $stmt->class),
                    $statements_analyzer->getSuppressedIssues(),
                    false
                )) {
                    return false;
                }
            } elseif ($lhs_type_part instanceof Type\Atomic\TTemplateParam
                && !$lhs_type_part->as->isMixed()
                && !$lhs_type_part->as->hasObject()
            ) {
                $fq_class_name = null;

                foreach ($lhs_type_part->as->getTypes() as $generic_param_type) {
                    if (!$generic_param_type instanceof TNamedObject) {
                        continue 2;
                    }

                    $fq_class_name = $generic_param_type->value;
                    break;
                }

                if (!$fq_class_name) {
                    if (IssueBuffer::accepts(
                        new UndefinedClass(
                            'Type ' . $lhs_type_part->as . ' cannot be called as a class',
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                            (string) $lhs_type_part
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }

                    continue;
                }
            } else {
                if ($lhs_type_part instanceof Type\Atomic\TMixed
                    || $lhs_type_part instanceof Type\Atomic\TTemplateParam
                    || $lhs_type_part instanceof Type\Atomic\TClassString
                ) {
                    if ($stmt->name instanceof PhpParser\Node\Identifier) {
                        $codebase->analyzer->addMixedMemberName(
                            strtolower($stmt->name->name),
                            $context->calling_method_id ?: $statements_analyzer->getFileName()
                        );
                    }

                    if (IssueBuffer::accepts(
                        new MixedMethodCall(
                            'Cannot call method on an unknown class',
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }

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
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }

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
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                        (string) $lhs_type_part
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }

                continue;
            }

            $fq_class_name = $codebase->classlikes->getUnAliasedName($fq_class_name);

            $is_mock = ExpressionAnalyzer::isMock($fq_class_name);

            $has_mock = $has_mock || $is_mock;

            if ($stmt->name instanceof PhpParser\Node\Identifier && !$is_mock) {
                $method_name_lc = strtolower($stmt->name->name);
                $method_id = $fq_class_name . '::' . $method_name_lc;

                if (!$context->collect_initializations
                    && !$context->collect_mutations
                ) {
                    ArgumentMapPopulator::recordArgumentPositions(
                        $statements_analyzer,
                        $stmt,
                        $codebase,
                        $method_id
                    );
                }

                $args = $stmt->args;

                if ($intersection_types
                    && !$codebase->methods->methodExists($method_id)
                ) {
                    foreach ($intersection_types as $intersection_type) {
                        if (!$intersection_type instanceof TNamedObject) {
                            continue;
                        }

                        $intersection_method_id = $intersection_type->value . '::' . $method_name_lc;

                        if ($codebase->methods->methodExists($intersection_method_id)) {
                            $method_id = $intersection_method_id;
                            $fq_class_name = $intersection_type->value;
                            break;
                        }
                    }
                }

                if (!$codebase->methods->methodExists(
                    $method_id,
                    $context->calling_method_id,
                    $codebase->collect_references ? new CodeLocation($source, $stmt->name) : null,
                    null,
                    $statements_analyzer->getFilePath()
                )
                    || !MethodAnalyzer::isMethodVisible(
                        $method_id,
                        $context,
                        $statements_analyzer->getSource()
                    )
                ) {
                    if ($codebase->methods->methodExists(
                        $fq_class_name . '::__callStatic',
                        $context->calling_method_id,
                        $codebase->collect_references ? new CodeLocation($source, $stmt->name) : null,
                        null,
                        $statements_analyzer->getFilePath()
                    )) {
                        $class_storage = $codebase->classlike_storage_provider->get($fq_class_name);

                        if (isset($class_storage->pseudo_static_methods[$method_name_lc])) {
                            $pseudo_method_storage = $class_storage->pseudo_static_methods[$method_name_lc];

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
                                new CodeLocation($source, $stmt),
                                $context
                            ) === false) {
                                return false;
                            }

                            if ($pseudo_method_storage->return_type) {
                                $return_type_candidate = clone $pseudo_method_storage->return_type;

                                $return_type_candidate = ExpressionAnalyzer::fleshOutType(
                                    $codebase,
                                    $return_type_candidate,
                                    $fq_class_name,
                                    $fq_class_name,
                                    $class_storage->parent_class
                                );

                                if (!isset($stmt->inferredType)) {
                                    $stmt->inferredType = $return_type_candidate;
                                } else {
                                    $stmt->inferredType = Type::combineUnionTypes(
                                        $return_type_candidate,
                                        $stmt->inferredType
                                    );
                                }

                                return;
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
                        }

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
                            new PhpParser\Node\Arg(new PhpParser\Node\Scalar\String_($method_id)),
                            new PhpParser\Node\Arg(new PhpParser\Node\Expr\Array_($array_values)),
                        ];

                        $method_id = $fq_class_name . '::__callstatic';
                    }

                    if (!$context->check_methods) {
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
                }

                $does_method_exist = MethodAnalyzer::checkMethodExists(
                    $codebase,
                    $method_id,
                    new CodeLocation($source, $stmt),
                    $statements_analyzer->getSuppressedIssues(),
                    $context->calling_method_id
                );

                if (!$does_method_exist) {
                    if (self::checkFunctionArguments(
                        $statements_analyzer,
                        $stmt->args,
                        null,
                        null,
                        $context
                    ) === false) {
                        return false;
                    }

                    if ($codebase->alter_code && $fq_class_name && !$moved_call) {
                        $codebase->classlikes->handleClassLikeReferenceInMigration(
                            $codebase,
                            $statements_analyzer,
                            $stmt->class,
                            $fq_class_name,
                            $context->calling_method_id
                        );
                    }

                    return;
                }

                $class_storage = $codebase->classlike_storage_provider->get($fq_class_name);

                if ($class_storage->user_defined
                    && $context->self
                    && ($context->collect_mutations || $context->collect_initializations)
                ) {
                    $appearing_method_id = $codebase->getAppearingMethodId($method_id);

                    if (!$appearing_method_id) {
                        if (IssueBuffer::accepts(
                            new UndefinedMethod(
                                'Method ' . $method_id . ' does not exist',
                                new CodeLocation($statements_analyzer->getSource(), $stmt),
                                $method_id
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            //
                        }

                        return;
                    }

                    list($appearing_method_class_name) = explode('::', $appearing_method_id);

                    if ($codebase->classExtends($context->self, $appearing_method_class_name)) {
                        $old_context_include_location = $context->include_location;
                        $old_self = $context->self;
                        $context->include_location = new CodeLocation($statements_analyzer->getSource(), $stmt);
                        $context->self = $appearing_method_class_name;

                        if ($context->collect_mutations) {
                            $file_analyzer->getMethodMutations($method_id, $context);
                        } else {
                            // collecting initializations
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

                                $file_analyzer->getMethodMutations($method_id, $context);

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
                }

                if ($class_storage->deprecated && $fq_class_name !== $context->self) {
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

                if ($class_storage->psalm_internal
                    && $context->self
                    && ! NamespaceAnalyzer::isWithin($context->self, $class_storage->psalm_internal)
                ) {
                    if (IssueBuffer::accepts(
                        new InternalClass(
                            $fq_class_name . ' is marked internal to ' . $class_storage->psalm_internal,
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                            $fq_class_name
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                if ($class_storage->internal
                    && $context->self
                    && !$context->collect_initializations
                    && !$context->collect_mutations
                ) {
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

                if (MethodAnalyzer::checkMethodVisibility(
                    $method_id,
                    $context,
                    $statements_analyzer->getSource(),
                    new CodeLocation($source, $stmt),
                    $statements_analyzer->getSuppressedIssues()
                ) === false) {
                    return false;
                }

                if ((!$stmt->class instanceof PhpParser\Node\Name
                        || $stmt->class->parts[0] !== 'parent'
                        || $statements_analyzer->isStatic())
                    && (
                        !$context->self
                        || $statements_analyzer->isStatic()
                        || !$codebase->classExtends($context->self, $fq_class_name)
                    )
                ) {
                    if (MethodAnalyzer::checkStatic(
                        $method_id,
                        ($stmt->class instanceof PhpParser\Node\Name
                            && strtolower($stmt->class->parts[0]) === 'self')
                            || $context->self === $fq_class_name,
                        !$statements_analyzer->isStatic(),
                        $codebase,
                        new CodeLocation($source, $stmt),
                        $statements_analyzer->getSuppressedIssues(),
                        $is_dynamic_this_method
                    ) === false) {
                        // fall through
                    }

                    if ($is_dynamic_this_method) {
                        $fake_method_call_expr = new PhpParser\Node\Expr\MethodCall(
                            new PhpParser\Node\Expr\Variable(
                                'this',
                                $stmt->class->getAttributes()
                            ),
                            $stmt->name,
                            $stmt->args,
                            $stmt->getAttributes()
                        );

                        if (MethodCallAnalyzer::analyze(
                            $statements_analyzer,
                            $fake_method_call_expr,
                            $context
                        ) === false) {
                            return false;
                        }

                        if (isset($fake_method_call_expr->inferredType)) {
                            $stmt->inferredType = $fake_method_call_expr->inferredType;
                        }

                        return null;
                    }
                }

                if (MethodAnalyzer::checkMethodNotDeprecatedOrInternal(
                    $codebase,
                    $context,
                    $method_id,
                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                    $statements_analyzer->getSuppressedIssues()
                ) === false) {
                    // fall through
                }

                $found_generic_params = MethodCallAnalyzer::getClassTemplateParams(
                    $codebase,
                    $class_storage,
                    $fq_class_name,
                    $method_name_lc,
                    $lhs_type_part,
                    null
                );

                if ($found_generic_params
                    && $stmt->class instanceof PhpParser\Node\Name
                    && $stmt->class->parts === ['parent']
                    && $context->self
                    && ($self_class_storage = $codebase->classlike_storage_provider->get($context->self))
                    && $self_class_storage->template_type_extends
                ) {
                    foreach ($self_class_storage->template_type_extends as $template_fq_class_name => $extended_types) {
                        foreach ($extended_types as $type_key => $extended_type) {
                            if (!is_string($type_key)) {
                                continue;
                            }

                            if (isset($found_generic_params[$type_key][$template_fq_class_name])) {
                                $found_generic_params[$type_key][$template_fq_class_name][0] = clone $extended_type;
                                continue;
                            }

                            foreach ($extended_type->getTypes() as $t) {
                                if ($t instanceof Type\Atomic\TTemplateParam
                                    && isset($found_generic_params[$t->param_name][$t->defining_class ?: ''])
                                ) {
                                    $found_generic_params[$type_key][$template_fq_class_name] = [
                                        $found_generic_params[$t->param_name][$t->defining_class ?: ''][0]
                                    ];
                                } else {
                                    $found_generic_params[$type_key][$template_fq_class_name] = [
                                        clone $extended_type
                                    ];
                                    break;
                                }
                            }
                        }
                    }
                }

                if (self::checkMethodArgs(
                    $method_id,
                    $args,
                    $found_generic_params,
                    $context,
                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                    $statements_analyzer
                ) === false) {
                    return false;
                }

                $fq_class_name = $stmt->class instanceof PhpParser\Node\Name && $stmt->class->parts === ['parent']
                    ? (string) $statements_analyzer->getFQCLN()
                    : $fq_class_name;

                $self_fq_class_name = $fq_class_name;

                $return_type_candidate = null;

                if ($codebase->methods->return_type_provider->has($fq_class_name)) {
                    $return_type_candidate = $codebase->methods->return_type_provider->getReturnType(
                        $statements_analyzer,
                        $fq_class_name,
                        $stmt->name->name,
                        $stmt->args,
                        $context,
                        new CodeLocation($statements_analyzer->getSource(), $stmt->name)
                    );
                }

                $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);

                if (!$return_type_candidate && $declaring_method_id && $declaring_method_id !== $method_id) {
                    list($declaring_fq_class_name, $declaring_method_name) = explode('::', $declaring_method_id);

                    if ($codebase->methods->return_type_provider->has($declaring_fq_class_name)) {
                        $return_type_candidate = $codebase->methods->return_type_provider->getReturnType(
                            $statements_analyzer,
                            $declaring_fq_class_name,
                            $declaring_method_name,
                            $stmt->args,
                            $context,
                            new CodeLocation($statements_analyzer->getSource(), $stmt->name),
                            null,
                            $fq_class_name,
                            $stmt->name->name
                        );
                    }
                }

                if (!$return_type_candidate) {
                    $return_type_candidate = $codebase->methods->getMethodReturnType(
                        $method_id,
                        $self_fq_class_name,
                        $args
                    );

                    if ($return_type_candidate) {
                        $return_type_candidate = clone $return_type_candidate;

                        if ($found_generic_params !== null) {
                            $return_type_candidate->replaceTemplateTypesWithArgTypes(
                                $found_generic_params
                            );
                        }

                        if ($lhs_type_part instanceof Type\Atomic\TTemplateParam) {
                            $static_type = $lhs_type_part;
                        } elseif ($lhs_type_part instanceof Type\Atomic\TTemplateParamClass) {
                            $static_type = new Type\Atomic\TTemplateParam(
                                $lhs_type_part->param_name,
                                $lhs_type_part->as_type
                                    ? new Type\Union([$lhs_type_part->as_type])
                                    : Type::getObject()
                            );
                        } else {
                            $static_type = $fq_class_name;
                        }

                        $return_type_candidate = ExpressionAnalyzer::fleshOutType(
                            $codebase,
                            $return_type_candidate,
                            $self_fq_class_name,
                            $static_type,
                            $class_storage->parent_class
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
                    }
                }

                $method_storage = $codebase->methods->getUserMethodStorage($method_id);

                if ($method_storage) {
                    if ($context->pure && !$method_storage->pure) {
                        if (IssueBuffer::accepts(
                            new ImpureMethodCall(
                                'Cannot call an impure method from a pure context',
                                new CodeLocation($source, $stmt->name)
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    }

                    if ($method_storage->assertions) {
                        self::applyAssertionsToContext(
                            $stmt->name,
                            $method_storage->assertions,
                            $stmt->args,
                            $found_generic_params ?: [],
                            $context,
                            $statements_analyzer
                        );
                    }

                    if ($method_storage->if_true_assertions) {
                        $stmt->ifTrueAssertions = array_map(
                            function (Assertion $assertion) use ($found_generic_params) : Assertion {
                                return $assertion->getUntemplatedCopy($found_generic_params ?: []);
                            },
                            $method_storage->if_true_assertions
                        );
                    }

                    if ($method_storage->if_false_assertions) {
                        $stmt->ifFalseAssertions = array_map(
                            function (Assertion $assertion) use ($found_generic_params) : Assertion {
                                return $assertion->getUntemplatedCopy($found_generic_params ?: []);
                            },
                            $method_storage->if_false_assertions
                        );
                    }
                }

                if ($codebase->alter_code) {
                    foreach ($codebase->call_transforms as $original_pattern => $transformation) {
                        if ($declaring_method_id
                            && strtolower($declaring_method_id) . '\((.*\))' === $original_pattern
                        ) {
                            if (strpos($transformation, '($1)') === strlen($transformation) - 4
                                && $stmt->class instanceof PhpParser\Node\Name
                            ) {
                                $new_method_id = substr($transformation, 0, -4);
                                list($old_declaring_fq_class_name) = explode('::', $declaring_method_id);
                                list($new_fq_class_name, $new_method_name) = explode('::', $new_method_id);

                                if ($codebase->classlikes->handleClassLikeReferenceInMigration(
                                    $codebase,
                                    $statements_analyzer,
                                    $stmt->class,
                                    $new_fq_class_name,
                                    $context->calling_method_id,
                                    strtolower($old_declaring_fq_class_name) !== strtolower($new_fq_class_name)
                                )) {
                                    $moved_call = true;
                                }

                                $file_manipulations = [];

                                $file_manipulations[] = new \Psalm\FileManipulation(
                                    (int) $stmt->name->getAttribute('startFilePos'),
                                    (int) $stmt->name->getAttribute('endFilePos') + 1,
                                    $new_method_name
                                );

                                FileManipulationBuffer::add(
                                    $statements_analyzer->getFilePath(),
                                    $file_manipulations
                                );
                            }
                        }
                    }
                }

                if ($config->after_method_checks) {
                    $file_manipulations = [];

                    $appearing_method_id = $codebase->methods->getAppearingMethodId($method_id);

                    if ($appearing_method_id && $declaring_method_id) {
                        foreach ($config->after_method_checks as $plugin_fq_class_name) {
                            $plugin_fq_class_name::afterMethodCallAnalysis(
                                $stmt,
                                $method_id,
                                $appearing_method_id,
                                $declaring_method_id,
                                $context,
                                $source,
                                $codebase,
                                $file_manipulations,
                                $return_type_candidate
                            );
                        }
                    }

                    if ($file_manipulations) {
                        /** @psalm-suppress MixedTypeCoercion */
                        FileManipulationBuffer::add($statements_analyzer->getFilePath(), $file_manipulations);
                    }
                }

                if ($return_type_candidate) {
                    if ($codebase->taint && $method_id) {
                        if ($method_storage && $method_storage->pure) {
                            $code_location = new CodeLocation($statements_analyzer->getSource(), $stmt);

                            $method_source = new Source(
                                strtolower(
                                    $method_id
                                        . '-' . $code_location->file_name
                                        . ':' . $code_location->raw_file_start
                                ),
                                new CodeLocation($source, $stmt->name)
                            );
                        } else {
                            $method_source = new Source(
                                strtolower($method_id),
                                new CodeLocation($source, $stmt->name)
                            );
                        }

                        if ($tainted_source = $codebase->taint->hasPreviousSource($method_source, $suffixes)) {
                            $return_type_candidate->tainted = $tainted_source->taint;

                            if ($suffixes !== null) {
                                $specialized_sources = [];

                                foreach ($suffixes as $suffix) {
                                    $specialized_sources[] = new Source(
                                        $method_source->id . '-' . $suffix,
                                        $method_source->code_location,
                                        $tainted_source->taint
                                    );
                                }

                                $return_type_candidate->sources = $specialized_sources;
                            } else {
                                $return_type_candidate->sources = [$method_source];
                                $method_source->taint = $tainted_source->taint;
                            }
                        }
                    }

                    if (isset($stmt->inferredType)) {
                        $stmt->inferredType = Type::combineUnionTypes($stmt->inferredType, $return_type_candidate);
                    } else {
                        $stmt->inferredType = $return_type_candidate;
                    }
                }
            } else {
                if ($stmt->name instanceof PhpParser\Node\Expr) {
                    ExpressionAnalyzer::analyze($statements_analyzer, $stmt->name, $context);
                }

                if (!$context->ignore_variable_method) {
                    $codebase->analyzer->addMixedMemberName(
                        strtolower($fq_class_name) . '::',
                        $context->calling_method_id ?: $statements_analyzer->getFileName()
                    );
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
            }

            if ($codebase->alter_code && $fq_class_name && !$moved_call) {
                $codebase->classlikes->handleClassLikeReferenceInMigration(
                    $codebase,
                    $statements_analyzer,
                    $stmt->class,
                    $fq_class_name,
                    $context->calling_method_id
                );
            }

            if ($codebase->store_node_types
                && $method_id
                && !$context->collect_initializations
                && !$context->collect_mutations
            ) {
                /** @psalm-suppress PossiblyInvalidArgument never a string, PHP Parser bug */
                $codebase->analyzer->addNodeReference(
                    $statements_analyzer->getFilePath(),
                    $stmt->name,
                    $method_id . '()'
                );
            }

            if ($codebase->store_node_types
                && !$context->collect_initializations
                && !$context->collect_mutations
                && isset($stmt->inferredType)
            ) {
                /** @psalm-suppress PossiblyInvalidArgument never a string, PHP Parser bug */
                $codebase->analyzer->addNodeType(
                    $statements_analyzer->getFilePath(),
                    $stmt->name,
                    (string) $stmt->inferredType,
                    $stmt
                );
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

        if (!$config->remember_property_assignments_after_call && !$context->collect_initializations) {
            $context->removeAllObjectVars();
        }
    }
}
