<?php
namespace Psalm\Internal\Analyzer\Statements\Block;

use PhpParser;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\CommentAnalyzer;
use Psalm\Internal\Analyzer\ScopeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\AssignmentAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\TypeAnalyzer;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Exception\DocblockParseException;
use Psalm\Issue\InvalidDocblock;
use Psalm\Issue\InvalidIterator;
use Psalm\Issue\NullIterator;
use Psalm\Issue\PossiblyFalseIterator;
use Psalm\Issue\PossiblyInvalidIterator;
use Psalm\Issue\PossiblyNullIterator;
use Psalm\Issue\RawObjectIteration;
use Psalm\IssueBuffer;
use Psalm\Internal\Scope\LoopScope;
use Psalm\Type;

/**
 * @internal
 */
class ForeachAnalyzer
{
    /**
     * @param   StatementsAnalyzer               $statements_analyzer
     * @param   PhpParser\Node\Stmt\Foreach_    $stmt
     * @param   Context                         $context
     *
     * @return  false|null
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\Foreach_ $stmt,
        Context $context
    ) {
        $var_comments = [];

        $doc_comment_text = (string)$stmt->getDocComment();

        $codebase = $statements_analyzer->getCodebase();

        if ($doc_comment_text) {
            try {
                $var_comments = CommentAnalyzer::getTypeFromComment(
                    $doc_comment_text,
                    $statements_analyzer->getSource(),
                    $statements_analyzer->getSource()->getAliases()
                );
            } catch (DocblockParseException $e) {
                if (IssueBuffer::accepts(
                    new InvalidDocblock(
                        (string)$e->getMessage(),
                        new CodeLocation($statements_analyzer, $stmt)
                    )
                )) {
                    // fall through
                }
            }
        }

        $safe_var_ids = [];

        if ($stmt->keyVar instanceof PhpParser\Node\Expr\Variable && is_string($stmt->keyVar->name)) {
            $safe_var_ids['$' . $stmt->keyVar->name] = true;
        }

        if ($stmt->valueVar instanceof PhpParser\Node\Expr\Variable && is_string($stmt->valueVar->name)) {
            $safe_var_ids['$' . $stmt->valueVar->name] = true;
        } elseif ($stmt->valueVar instanceof PhpParser\Node\Expr\List_) {
            foreach ($stmt->valueVar->items as $list_item) {
                if (!$list_item) {
                    continue;
                }

                $list_item_key = $list_item->key;
                $list_item_value = $list_item->value;

                if ($list_item_value instanceof PhpParser\Node\Expr\Variable && is_string($list_item_value->name)) {
                    $safe_var_ids['$' . $list_item_value->name] = true;
                }

                if ($list_item_key instanceof PhpParser\Node\Expr\Variable && is_string($list_item_key->name)) {
                    $safe_var_ids['$' . $list_item_key->name] = true;
                }
            }
        }

        foreach ($var_comments as $var_comment) {
            if (!$var_comment->var_id) {
                continue;
            }

            if (isset($safe_var_ids[$var_comment->var_id])) {
                continue;
            }

            $comment_type = ExpressionAnalyzer::fleshOutType(
                $codebase,
                $var_comment->type,
                $context->self,
                $context->self
            );

            if (isset($context->vars_in_scope[$var_comment->var_id])
                || in_array(
                    $var_comment->var_id,
                    [
                        '$GLOBALS',
                        '$_SERVER',
                        '$_GET',
                        '$_POST',
                        '$_FILES',
                        '$_COOKIE',
                        '$_SESSION',
                        '$_REQUEST',
                        '$_ENV',
                    ],
                    true
                )
            ) {
                $context->vars_in_scope[$var_comment->var_id] = $comment_type;
            }
        }

        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context) === false) {
            return false;
        }

        $key_type = null;
        $value_type = null;
        $always_non_empty_array = true;

        $var_id = ExpressionAnalyzer::getVarId(
            $stmt->expr,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer
        );

        if (isset($stmt->expr->inferredType)) {
            $iterator_type = $stmt->expr->inferredType;
        } elseif ($var_id && $context->hasVariable($var_id, $statements_analyzer)) {
            $iterator_type = $context->vars_in_scope[$var_id];
        } else {
            $iterator_type = null;
        }

        if ($iterator_type) {
            if (self::checkIteratorType(
                $statements_analyzer,
                $stmt,
                $iterator_type,
                $codebase,
                $context,
                $key_type,
                $value_type,
                $always_non_empty_array
            ) === false
            ) {
                return false;
            }
        }

        $foreach_context = clone $context;

        $foreach_context->inside_loop = true;
        $foreach_context->inside_case = false;

        if ($codebase->alter_code) {
            $foreach_context->branch_point =
                $foreach_context->branch_point ?: (int) $stmt->getAttribute('startFilePos');
        }

        if ($stmt->keyVar && $stmt->keyVar instanceof PhpParser\Node\Expr\Variable && is_string($stmt->keyVar->name)) {
            $key_var_id = '$' . $stmt->keyVar->name;
            $foreach_context->vars_in_scope[$key_var_id] = $key_type ?: Type::getMixed();
            $foreach_context->vars_possibly_in_scope[$key_var_id] = true;

            $location = new CodeLocation($statements_analyzer, $stmt->keyVar);

            if ($context->collect_references && !isset($foreach_context->byref_constraints[$key_var_id])) {
                $foreach_context->unreferenced_vars[$key_var_id] = [$location->getHash() => $location];
            }

            if (!$statements_analyzer->hasVariable($key_var_id)) {
                $statements_analyzer->registerVariable(
                    $key_var_id,
                    $location,
                    $foreach_context->branch_point
                );
            } else {
                $statements_analyzer->registerVariableAssignment(
                    $key_var_id,
                    $location
                );
            }

            if ($stmt->byRef && $context->collect_references) {
                $statements_analyzer->registerVariableUses([$location->getHash() => $location]);
            }
        }

        if ($context->collect_references
            && $stmt->byRef
            && $stmt->valueVar instanceof PhpParser\Node\Expr\Variable
            && is_string($stmt->valueVar->name)
        ) {
            $foreach_context->byref_constraints['$' . $stmt->valueVar->name]
                = new \Psalm\Internal\ReferenceConstraint($value_type);
        }

        AssignmentAnalyzer::analyze(
            $statements_analyzer,
            $stmt->valueVar,
            null,
            $value_type ?: Type::getMixed(),
            $foreach_context,
            $doc_comment_text
        );

        foreach ($var_comments as $var_comment) {
            if (!$var_comment->var_id) {
                continue;
            }

            $comment_type = ExpressionAnalyzer::fleshOutType(
                $codebase,
                $var_comment->type,
                $context->self,
                $context->self
            );

            $foreach_context->vars_in_scope[$var_comment->var_id] = $comment_type;
        }

        $loop_scope = new LoopScope($foreach_context, $context);

        $protected_var_ids = $context->protected_var_ids;
        if ($var_id) {
            $protected_var_ids[$var_id] = true;
        }
        $loop_scope->protected_var_ids = $protected_var_ids;

        LoopAnalyzer::analyze($statements_analyzer, $stmt->stmts, [], [], $loop_scope, $inner_loop_context);

        if (!$inner_loop_context) {
            throw new \UnexpectedValueException('There should be an inner loop context');
        }

        if ($always_non_empty_array) {
            foreach ($inner_loop_context->vars_in_scope as $var_id => $type) {
                // if there are break statements in the loop it's not certain
                // that the loop has finished executing, so the assertions at the end
                // the loop in the while conditional may not hold
                if (in_array(ScopeAnalyzer::ACTION_BREAK, $loop_scope->final_actions, true)
                    || in_array(ScopeAnalyzer::ACTION_CONTINUE, $loop_scope->final_actions, true)
                ) {
                    if (isset($loop_scope->possibly_defined_loop_parent_vars[$var_id])) {
                        $context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                            $type,
                            $loop_scope->possibly_defined_loop_parent_vars[$var_id]
                        );
                    }
                } else {
                    $context->vars_in_scope[$var_id] = $type;
                }
            }
        }

        $foreach_context->loop_scope = null;

        $context->vars_possibly_in_scope = array_merge(
            $foreach_context->vars_possibly_in_scope,
            $context->vars_possibly_in_scope
        );

        $context->referenced_var_ids = array_merge(
            $foreach_context->referenced_var_ids,
            $context->referenced_var_ids
        );

        if ($context->collect_exceptions) {
            $context->possibly_thrown_exceptions += $foreach_context->possibly_thrown_exceptions;
        }

        if ($context->collect_references) {
            foreach ($foreach_context->unreferenced_vars as $var_id => $locations) {
                if (isset($context->unreferenced_vars[$var_id])) {
                    $context->unreferenced_vars[$var_id] += $locations;
                } else {
                    $context->unreferenced_vars[$var_id] = $locations;
                }
            }
        }

        return null;
    }

    /**
     * @param  ?Type\Union  $key_type
     * @param  ?Type\Union  $value_type
     * @return false|null
     */
    public static function checkIteratorType(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\Foreach_ $stmt,
        Type\Union $iterator_type,
        Codebase $codebase,
        Context $context,
        &$key_type,
        &$value_type,
        bool &$always_non_empty_array
    ) {
        if ($iterator_type->isNull()) {
            if (IssueBuffer::accepts(
                new NullIterator(
                    'Cannot iterate over null',
                    new CodeLocation($statements_analyzer->getSource(), $stmt->expr)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                return false;
            }
        } elseif ($iterator_type->isNullable() && !$iterator_type->ignore_nullable_issues) {
            if (IssueBuffer::accepts(
                new PossiblyNullIterator(
                    'Cannot iterate over nullable var ' . $iterator_type,
                    new CodeLocation($statements_analyzer->getSource(), $stmt->expr)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                return false;
            }
        } elseif ($iterator_type->isFalsable() && !$iterator_type->ignore_falsable_issues) {
            if (IssueBuffer::accepts(
                new PossiblyFalseIterator(
                    'Cannot iterate over falsable var ' . $iterator_type,
                    new CodeLocation($statements_analyzer->getSource(), $stmt->expr)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                return false;
            }
        }

        $has_valid_iterator = false;
        $invalid_iterator_types = [];

        foreach ($iterator_type->getTypes() as $iterator_atomic_type) {
            if ($iterator_atomic_type instanceof Type\Atomic\TGenericParam) {
                $iterator_atomic_type = array_values($iterator_atomic_type->as->getTypes())[0];
            }

            // if it's an empty array, we cannot iterate over it
            if ($iterator_atomic_type instanceof Type\Atomic\TArray
                && $iterator_atomic_type->type_params[1]->isEmpty()
            ) {
                $always_non_empty_array = false;
                $has_valid_iterator = true;
                continue;
            }

            if ($iterator_atomic_type instanceof Type\Atomic\TNull
                || $iterator_atomic_type instanceof Type\Atomic\TFalse
            ) {
                $always_non_empty_array = false;
                continue;
            }

            if ($iterator_atomic_type instanceof Type\Atomic\TArray
                || $iterator_atomic_type instanceof Type\Atomic\ObjectLike
            ) {
                if ($iterator_atomic_type instanceof Type\Atomic\ObjectLike) {
                    if (!$iterator_atomic_type->sealed) {
                        $always_non_empty_array = false;
                    }
                    $iterator_atomic_type = $iterator_atomic_type->getGenericArrayType();
                } elseif (!$iterator_atomic_type instanceof Type\Atomic\TNonEmptyArray) {
                    $always_non_empty_array = false;
                }

                if (!$value_type) {
                    $value_type = $iterator_atomic_type->type_params[1];
                } else {
                    $value_type = Type::combineUnionTypes($value_type, $iterator_atomic_type->type_params[1]);
                }

                $key_type_part = $iterator_atomic_type->type_params[0];

                if (!$key_type) {
                    $key_type = $key_type_part;
                } else {
                    $key_type = Type::combineUnionTypes($key_type, $key_type_part);
                }

                $has_valid_iterator = true;
                continue;
            }

            $always_non_empty_array = false;

            if ($iterator_atomic_type instanceof Type\Atomic\Scalar ||
                $iterator_atomic_type instanceof Type\Atomic\TVoid
            ) {
                $invalid_iterator_types[] = $iterator_atomic_type->getKey();

                $value_type = Type::getMixed();
            } elseif ($iterator_atomic_type instanceof Type\Atomic\TObject ||
                $iterator_atomic_type instanceof Type\Atomic\TMixed ||
                $iterator_atomic_type instanceof Type\Atomic\TEmpty
            ) {
                $has_valid_iterator = true;
                $value_type = Type::getMixed();
            } elseif ($iterator_atomic_type instanceof Type\Atomic\TIterable) {
                $value_type_part = $iterator_atomic_type->type_params[1];
                $key_type_part = $iterator_atomic_type->type_params[0];

                if (!$value_type) {
                    $value_type = $value_type_part;
                } else {
                    $value_type = Type::combineUnionTypes($value_type, $value_type_part);
                }

                if (!$key_type) {
                    $key_type = $key_type_part;
                } else {
                    $key_type = Type::combineUnionTypes($key_type, $key_type_part);
                }

                $has_valid_iterator = true;
            } elseif ($iterator_atomic_type instanceof Type\Atomic\TNamedObject) {
                if ($iterator_atomic_type->value !== 'Traversable' &&
                    $iterator_atomic_type->value !== $statements_analyzer->getClassName()
                ) {
                    if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                        $statements_analyzer,
                        $iterator_atomic_type->value,
                        new CodeLocation($statements_analyzer->getSource(), $stmt->expr),
                        $statements_analyzer->getSuppressedIssues()
                    ) === false) {
                        return false;
                    }
                }

                if (TypeAnalyzer::isAtomicContainedBy(
                    $codebase,
                    $iterator_atomic_type,
                    new Type\Atomic\TIterable([Type::getMixed(), Type::getMixed()])
                )) {
                    self::handleIterable(
                        $statements_analyzer,
                        $iterator_atomic_type,
                        $stmt->expr,
                        $codebase,
                        $context,
                        $key_type,
                        $value_type,
                        $has_valid_iterator
                    );
                } else {
                    if (IssueBuffer::accepts(
                        new RawObjectIteration(
                            'Possibly undesired iteration over regular object ' . $iterator_atomic_type->value,
                            new CodeLocation($statements_analyzer->getSource(), $stmt->expr)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            }
        }

        if ($invalid_iterator_types) {
            if ($has_valid_iterator) {
                if (IssueBuffer::accepts(
                    new PossiblyInvalidIterator(
                        'Cannot iterate over ' . $invalid_iterator_types[0],
                        new CodeLocation($statements_analyzer->getSource(), $stmt->expr)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    return false;
                }
            } else {
                if (IssueBuffer::accepts(
                    new InvalidIterator(
                        'Cannot iterate over ' . $invalid_iterator_types[0],
                        new CodeLocation($statements_analyzer->getSource(), $stmt->expr)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    return false;
                }
            }
        }
    }

    /**
     * @param  ?Type\Union  $key_type
     * @param  ?Type\Union  $value_type
     * @return void
     */
    public static function handleIterable(
        StatementsAnalyzer $statements_analyzer,
        Type\Atomic\TNamedObject $iterator_atomic_type,
        PhpParser\Node\Expr $foreach_expr,
        Codebase $codebase,
        Context $context,
        &$key_type,
        &$value_type,
        bool &$has_valid_iterator
    ) {
        if ($iterator_atomic_type->extra_types) {
            $iterator_atomic_type_copy = clone $iterator_atomic_type;
            $iterator_atomic_type_copy->extra_types = [];
            $iterator_atomic_types = [$iterator_atomic_type_copy];
            $iterator_atomic_types = array_merge($iterator_atomic_types, $iterator_atomic_type->extra_types);
        } else {
            $iterator_atomic_types = [$iterator_atomic_type];
        }

        foreach ($iterator_atomic_types as $iterator_atomic_type) {
            if ($iterator_atomic_type instanceof Type\Atomic\TGenericParam) {
                throw new \UnexpectedValueException('Shouldn’t get a generic param here');
            }

            $has_valid_iterator = true;

            if ($iterator_atomic_type instanceof Type\Atomic\TIterable
                || (strtolower($iterator_atomic_type->value) === 'traversable'
                    || $codebase->classImplements(
                        $iterator_atomic_type->value,
                        'Traversable'
                    ) ||
                    (
                        $codebase->interfaceExists($iterator_atomic_type->value)
                        && $codebase->interfaceExtends(
                            $iterator_atomic_type->value,
                            'Traversable'
                        )
                    ))
            ) {
                if (strtolower($iterator_atomic_type->value) === 'iteratoraggregate'
                    || $codebase->classImplements(
                        $iterator_atomic_type->value,
                        'IteratorAggregate'
                    )
                    || ($codebase->interfaceExists($iterator_atomic_type->value)
                        && $codebase->interfaceExtends(
                            $iterator_atomic_type->value,
                            'IteratorAggregate'
                        )
                    )
                ) {
                    $fake_method_call = new PhpParser\Node\Expr\MethodCall(
                        $foreach_expr,
                        new PhpParser\Node\Identifier('getIterator', $foreach_expr->getAttributes())
                    );

                    $suppressed_issues = $statements_analyzer->getSuppressedIssues();

                    if (!in_array('PossiblyInvalidMethodCall', $suppressed_issues, true)) {
                        $statements_analyzer->addSuppressedIssues(['PossiblyInvalidMethodCall']);
                    }

                    \Psalm\Internal\Analyzer\Statements\Expression\Call\MethodCallAnalyzer::analyze(
                        $statements_analyzer,
                        $fake_method_call,
                        $context
                    );

                    if (!in_array('PossiblyInvalidMethodCall', $suppressed_issues, true)) {
                        $statements_analyzer->removeSuppressedIssues(['PossiblyInvalidMethodCall']);
                    }

                    $iterator_class_type = $fake_method_call->inferredType ?? null;

                    if ($iterator_class_type) {
                        foreach ($iterator_class_type->getTypes() as $array_atomic_type) {
                            $key_type_part = null;
                            $value_type_part = null;

                            if ($array_atomic_type instanceof Type\Atomic\TArray
                                || $array_atomic_type instanceof Type\Atomic\ObjectLike
                            ) {
                                if ($array_atomic_type instanceof Type\Atomic\ObjectLike) {
                                    $array_atomic_type = $array_atomic_type->getGenericArrayType();
                                }

                                $key_type_part = $array_atomic_type->type_params[0];
                                $value_type_part = $array_atomic_type->type_params[1];
                            } else {
                                if ($array_atomic_type instanceof Type\Atomic\TIterable
                                    || ($array_atomic_type instanceof Type\Atomic\TNamedObject
                                        && (strtolower($array_atomic_type->value) === 'traversable'
                                            || ($codebase->classOrInterfaceExists($array_atomic_type->value)
                                                && $codebase->classImplements(
                                                    $array_atomic_type->value,
                                                    'Traversable'
                                                ))))
                                ) {
                                    self::getKeyValueParamsForTraversableObject(
                                        $array_atomic_type,
                                        $codebase,
                                        $key_type_part,
                                        $value_type_part
                                    );
                                }
                            }

                            if (!$key_type_part || !$value_type_part) {
                                break;
                            }

                            if (!$key_type) {
                                $key_type = $key_type_part;
                            } else {
                                $key_type = Type::combineUnionTypes($key_type, $key_type_part);
                            }

                            if (!$value_type) {
                                $value_type = $value_type_part;
                            } else {
                                $value_type = Type::combineUnionTypes($value_type, $value_type_part);
                            }
                        }
                    }
                } elseif ($codebase->classImplements(
                    $iterator_atomic_type->value,
                    'Iterator'
                ) ||
                    (
                        $codebase->interfaceExists($iterator_atomic_type->value)
                        && $codebase->interfaceExtends(
                            $iterator_atomic_type->value,
                            'Iterator'
                        )
                    )
                ) {
                    $fake_method_call = new PhpParser\Node\Expr\MethodCall(
                        $foreach_expr,
                        new PhpParser\Node\Identifier('current', $foreach_expr->getAttributes())
                    );

                    $suppressed_issues = $statements_analyzer->getSuppressedIssues();

                    if (!in_array('PossiblyInvalidMethodCall', $suppressed_issues, true)) {
                        $statements_analyzer->addSuppressedIssues(['PossiblyInvalidMethodCall']);
                    }

                    \Psalm\Internal\Analyzer\Statements\Expression\Call\MethodCallAnalyzer::analyze(
                        $statements_analyzer,
                        $fake_method_call,
                        $context
                    );

                    if (!in_array('PossiblyInvalidMethodCall', $suppressed_issues, true)) {
                        $statements_analyzer->removeSuppressedIssues(['PossiblyInvalidMethodCall']);
                    }

                    $iterator_class_type = $fake_method_call->inferredType ?? null;

                    if ($iterator_class_type && !$iterator_class_type->isMixed()) {
                        if (!$value_type) {
                            $value_type = $iterator_class_type;
                        } else {
                            $value_type = Type::combineUnionTypes($value_type, $iterator_class_type);
                        }
                    }
                }

                if (!$key_type && !$value_type) {
                    self::getKeyValueParamsForTraversableObject(
                        $iterator_atomic_type,
                        $codebase,
                        $key_type,
                        $value_type
                    );
                }

                return;
            }

            if (!$codebase->classlikes->classOrInterfaceExists($iterator_atomic_type->value)) {
                return;
            }
        }
    }

    /**
     * @param  ?Type\Union  $key_type
     * @param  ?Type\Union  $value_type
     * @return void
     */
    public static function getKeyValueParamsForTraversableObject(
        Type\Atomic $iterator_atomic_type,
        Codebase $codebase,
        &$key_type,
        &$value_type
    ) {
        if ($iterator_atomic_type instanceof Type\Atomic\TIterable
            || ($iterator_atomic_type instanceof Type\Atomic\TGenericObject
                && strtolower($iterator_atomic_type->value) === 'traversable')
        ) {
            $value_type_part = $iterator_atomic_type->type_params[1];

            if (!$value_type) {
                $value_type = $value_type_part;
            } else {
                $value_type = Type::combineUnionTypes($value_type, $value_type_part);
            }

            $key_type_part = $iterator_atomic_type->type_params[0];

            if (!$key_type) {
                $key_type = $key_type_part;
            } else {
                $key_type = Type::combineUnionTypes($key_type, $key_type_part);
            }
            return;
        }

        if ($iterator_atomic_type instanceof Type\Atomic\TNamedObject
            && $codebase->classImplements(
                $iterator_atomic_type->value,
                'Traversable'
            )
        ) {
            $generic_storage = $codebase->classlike_storage_provider->get(
                $iterator_atomic_type->value
            );

            if (!isset($generic_storage->template_type_extends['traversable'])) {
                return;
            }

            if ($generic_storage->template_types
                || $iterator_atomic_type instanceof Type\Atomic\TGenericObject
            ) {
                // if we're just being passed the non-generic class itself, assume
                // that it's inside the calling class
                $passed_type_params = $iterator_atomic_type instanceof Type\Atomic\TGenericObject
                    ? $iterator_atomic_type->type_params
                    : array_values(
                        array_map(
                            /** @param array{0:Type\Union} $arr */
                            function (array $arr) : Type\Union {
                                return $arr[0];
                            },
                            $generic_storage->template_types
                        )
                    );
            } else {
                $passed_type_params = null;
            }

            $key_type = self::getExtendedType(
                'TKey',
                'traversable',
                strtolower($generic_storage->name),
                $generic_storage->template_type_extends,
                $generic_storage->template_types,
                $passed_type_params
            );

            $value_type = self::getExtendedType(
                'TValue',
                'traversable',
                strtolower($generic_storage->name),
                $generic_storage->template_type_extends,
                $generic_storage->template_types,
                $passed_type_params
            );

            return;
        }
    }

    /**
     * @param  string $template_name
     * @param  array<string, array<int|string, Type\Atomic>>  $template_type_extends
     * @param  array<string, array{Type\Union, ?string}>  $class_template_types
     * @param  array<int, Type\Union> $calling_type_params
     * @return Type\Union|null
     */
    private static function getExtendedType(
        string $template_name,
        string $template_class_lc,
        string $calling_class_lc,
        array $template_type_extends,
        array $class_template_types = null,
        array $calling_type_params = null
    ) {
        if ($calling_class_lc === $template_class_lc) {
            if (isset($class_template_types[$template_name]) && $calling_type_params) {
                $offset = array_search($template_name, array_keys($class_template_types));

                if ($offset !== false) {
                    return $calling_type_params[$offset];
                }
            }

            return null;
        }

        if (isset($template_type_extends[$template_class_lc][$template_name])) {
            $extended_type = $template_type_extends[$template_class_lc][$template_name];

            if (!$extended_type instanceof Type\Atomic\TGenericParam) {
                return new Type\Union([$extended_type]);
            }

            if ($extended_type->defining_class) {
                return self::getExtendedType(
                    $extended_type->param_name,
                    strtolower($extended_type->defining_class),
                    $calling_class_lc,
                    $template_type_extends,
                    $class_template_types,
                    $calling_type_params
                );
            }
        }

        return null;
    }
}
