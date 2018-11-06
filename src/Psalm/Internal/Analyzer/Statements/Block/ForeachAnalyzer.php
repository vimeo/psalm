<?php
namespace Psalm\Internal\Analyzer\Statements\Block;

use PhpParser;
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

class ForeachAnalyzer
{
    /**
     * @param   StatementsAnalyzer               $statements_checker
     * @param   PhpParser\Node\Stmt\Foreach_    $stmt
     * @param   Context                         $context
     *
     * @return  false|null
     */
    public static function analyze(
        StatementsAnalyzer $statements_checker,
        PhpParser\Node\Stmt\Foreach_ $stmt,
        Context $context
    ) {
        if (ExpressionAnalyzer::analyze($statements_checker, $stmt->expr, $context) === false) {
            return false;
        }

        $codebase = $statements_checker->getCodebase();

        $key_type = null;
        $value_type = null;
        $always_non_empty_array = true;

        $var_id = ExpressionAnalyzer::getVarId(
            $stmt->expr,
            $statements_checker->getFQCLN(),
            $statements_checker
        );

        if (isset($stmt->expr->inferredType)) {
            $iterator_type = $stmt->expr->inferredType;
        } elseif ($var_id && $context->hasVariable($var_id, $statements_checker)) {
            $iterator_type = $context->vars_in_scope[$var_id];
        } else {
            $iterator_type = null;
        }

        if ($iterator_type) {
            if ($iterator_type->isNull()) {
                if (IssueBuffer::accepts(
                    new NullIterator(
                        'Cannot iterate over null',
                        new CodeLocation($statements_checker->getSource(), $stmt->expr)
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    return false;
                }
            } elseif ($iterator_type->isNullable() && !$iterator_type->ignore_nullable_issues) {
                if (IssueBuffer::accepts(
                    new PossiblyNullIterator(
                        'Cannot iterate over nullable var ' . $iterator_type,
                        new CodeLocation($statements_checker->getSource(), $stmt->expr)
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    return false;
                }
            } elseif ($iterator_type->isFalsable() && !$iterator_type->ignore_falsable_issues) {
                if (IssueBuffer::accepts(
                    new PossiblyFalseIterator(
                        'Cannot iterate over falsable var ' . $iterator_type,
                        new CodeLocation($statements_checker->getSource(), $stmt->expr)
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    return false;
                }
            }

            $has_valid_iterator = false;
            $invalid_iterator_types = [];

            foreach ($iterator_type->getTypes() as $iterator_type) {
                // if it's an empty array, we cannot iterate over it
                if ($iterator_type instanceof Type\Atomic\TArray
                    && $iterator_type->type_params[1]->isEmpty()
                ) {
                    $always_non_empty_array = false;
                    $has_valid_iterator = true;
                    continue;
                }

                if ($iterator_type instanceof Type\Atomic\TNull || $iterator_type instanceof Type\Atomic\TFalse) {
                    $always_non_empty_array = false;
                    continue;
                }

                if ($iterator_type instanceof Type\Atomic\TArray
                    || $iterator_type instanceof Type\Atomic\ObjectLike
                ) {
                    if ($iterator_type instanceof Type\Atomic\ObjectLike) {
                        if (!$iterator_type->sealed) {
                            $always_non_empty_array = false;
                        }
                        $iterator_type = $iterator_type->getGenericArrayType();
                    } elseif (!$iterator_type instanceof Type\Atomic\TNonEmptyArray) {
                        $always_non_empty_array = false;
                    }

                    if (!$value_type) {
                        $value_type = $iterator_type->type_params[1];
                    } else {
                        $value_type = Type::combineUnionTypes($value_type, $iterator_type->type_params[1]);
                    }

                    $key_type_part = $iterator_type->type_params[0];

                    if (!$key_type) {
                        $key_type = $key_type_part;
                    } else {
                        $key_type = Type::combineUnionTypes($key_type, $key_type_part);
                    }

                    $has_valid_iterator = true;
                    continue;
                }

                $always_non_empty_array = false;

                if ($iterator_type instanceof Type\Atomic\Scalar ||
                    $iterator_type instanceof Type\Atomic\TVoid
                ) {
                    $invalid_iterator_types[] = $iterator_type->getKey();

                    $value_type = Type::getMixed();
                } elseif ($iterator_type instanceof Type\Atomic\TObject ||
                    $iterator_type instanceof Type\Atomic\TMixed ||
                    $iterator_type instanceof Type\Atomic\TEmpty
                ) {
                    $has_valid_iterator = true;
                    $value_type = Type::getMixed();
                } elseif ($iterator_type instanceof Type\Atomic\TNamedObject) {
                    if ($iterator_type->value !== 'Traversable' &&
                        $iterator_type->value !== $statements_checker->getClassName()
                    ) {
                        if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                            $statements_checker,
                            $iterator_type->value,
                            new CodeLocation($statements_checker->getSource(), $stmt->expr),
                            $statements_checker->getSuppressedIssues()
                        ) === false) {
                            return false;
                        }
                    }

                    if (TypeAnalyzer::isAtomicContainedBy(
                        $codebase,
                        $iterator_type,
                        new Type\Atomic\TNamedObject('iterable')
                    )) {
                        if ($iterator_type->extra_types) {
                            $iterator_type_copy = clone $iterator_type;
                            $iterator_type_copy->extra_types = [];
                            $iterator_types = [$iterator_type_copy];
                            $iterator_types = array_merge($iterator_types, $iterator_type->extra_types);
                        } else {
                            $iterator_types = [$iterator_type];
                        }

                        foreach ($iterator_types as $iterator_type) {
                            if ($iterator_type instanceof Type\Atomic\TGenericParam) {
                                throw new \UnexpectedValueException('Shouldn’t get a generic param here');
                            }

                            $has_valid_iterator = true;

                            if ($iterator_type instanceof Type\Atomic\TGenericObject
                                && (strtolower($iterator_type->value) === 'iterable'
                                    || strtolower($iterator_type->value) === 'traversable'
                                    || $codebase->classImplements(
                                        $iterator_type->value,
                                        'Traversable'
                                    ))
                            ) {
                                $value_index = count($iterator_type->type_params) - 1;
                                $value_type_part = $iterator_type->type_params[$value_index];

                                if (!$value_type) {
                                    $value_type = $value_type_part;
                                } else {
                                    $value_type = Type::combineUnionTypes($value_type, $value_type_part);
                                }

                                if ($value_index) {
                                    $key_type_part = $iterator_type->type_params[0];

                                    if (!$key_type) {
                                        $key_type = $key_type_part;
                                    } else {
                                        $key_type = Type::combineUnionTypes($key_type, $key_type_part);
                                    }
                                }
                                continue 2;
                            }

                            if (!$codebase->classlikes->classOrInterfaceExists($iterator_type->value)) {
                                continue 2;
                            }

                            if ($codebase->classImplements(
                                $iterator_type->value,
                                'IteratorAggregate'
                            ) ||
                                (
                                    $codebase->interfaceExists($iterator_type->value)
                                    && $codebase->interfaceExtends(
                                        $iterator_type->value,
                                        'IteratorAggregate'
                                    )
                                )
                            ) {
                                $iterator_method = $iterator_type->value . '::getIterator';
                                $self_class = $iterator_type->value;
                                $iterator_class_type = $codebase->methods->getMethodReturnType(
                                    $iterator_method,
                                    $self_class
                                );

                                if ($iterator_class_type) {
                                    $array_type = ExpressionAnalyzer::fleshOutType(
                                        $codebase,
                                        $iterator_class_type,
                                        $self_class,
                                        $self_class
                                    );

                                    foreach ($array_type->getTypes() as $array_atomic_type) {
                                        if ($array_atomic_type instanceof Type\Atomic\TArray
                                            || $array_atomic_type instanceof Type\Atomic\ObjectLike
                                        ) {
                                            if ($array_atomic_type instanceof Type\Atomic\ObjectLike) {
                                                $array_atomic_type = $array_atomic_type->getGenericArrayType();
                                            }

                                            $key_type_part = $array_atomic_type->type_params[0];
                                            $value_type_part = $array_atomic_type->type_params[1];
                                        } elseif ($array_atomic_type instanceof Type\Atomic\TGenericObject) {
                                            $type_param_count = count($array_atomic_type->type_params);

                                            $value_type_part = $array_atomic_type->type_params[$type_param_count - 1];
                                            $key_type_part = $type_param_count > 1
                                                ? $array_atomic_type->type_params[0]
                                                : Type::getMixed();
                                        } else {
                                            $key_type = Type::getMixed();
                                            $value_type = Type::getMixed();
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
                                } else {
                                    $value_type = Type::getMixed();
                                }
                            } elseif ($codebase->classImplements(
                                $iterator_type->value,
                                'Iterator'
                            ) ||
                                (
                                    $codebase->interfaceExists($iterator_type->value)
                                    && $codebase->interfaceExtends(
                                        $iterator_type->value,
                                        'Iterator'
                                    )
                                )
                            ) {
                                $iterator_method = $iterator_type->value . '::current';
                                $self_class = $iterator_type->value;
                                $iterator_class_type = $codebase->methods->getMethodReturnType(
                                    $iterator_method,
                                    $self_class
                                );

                                if ($iterator_class_type) {
                                    $value_type_part = ExpressionAnalyzer::fleshOutType(
                                        $codebase,
                                        $iterator_class_type,
                                        $self_class,
                                        $self_class
                                    );

                                    if (!$value_type) {
                                        $value_type = $value_type_part;
                                    } else {
                                        $value_type = Type::combineUnionTypes($value_type, $value_type_part);
                                    }
                                } else {
                                    $value_type = Type::getMixed();
                                }
                            }
                        }
                    } else {
                        if (IssueBuffer::accepts(
                            new RawObjectIteration(
                                'Possibly undesired iteration over regular object ' . $iterator_type->value,
                                new CodeLocation($statements_checker->getSource(), $stmt->expr)
                            ),
                            $statements_checker->getSuppressedIssues()
                        )) {
                            return false;
                        }
                    }
                }
            }

            if ($invalid_iterator_types) {
                if ($has_valid_iterator) {
                    if (IssueBuffer::accepts(
                        new PossiblyInvalidIterator(
                            'Cannot iterate over ' . $invalid_iterator_types[0],
                            new CodeLocation($statements_checker->getSource(), $stmt->expr)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new InvalidIterator(
                            'Cannot iterate over ' . $invalid_iterator_types[0],
                            new CodeLocation($statements_checker->getSource(), $stmt->expr)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }
                }
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

            $location = new CodeLocation($statements_checker, $stmt->keyVar);

            if ($context->collect_references && !isset($foreach_context->byref_constraints[$key_var_id])) {
                $foreach_context->unreferenced_vars[$key_var_id] = [$location->getHash() => $location];
            }

            if (!$statements_checker->hasVariable($key_var_id)) {
                $statements_checker->registerVariable(
                    $key_var_id,
                    $location,
                    $foreach_context->branch_point
                );
            } else {
                $statements_checker->registerVariableAssignment(
                    $key_var_id,
                    $location
                );
            }

            if ($stmt->byRef && $context->collect_references) {
                $statements_checker->registerVariableUses([$location->getHash() => $location]);
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
            $statements_checker,
            $stmt->valueVar,
            null,
            $value_type ?: Type::getMixed(),
            $foreach_context,
            (string)$stmt->getDocComment()
        );

        $doc_comment_text = (string)$stmt->getDocComment();

        if ($doc_comment_text) {
            $var_comments = [];

            try {
                $var_comments = CommentAnalyzer::getTypeFromComment(
                    $doc_comment_text,
                    $statements_checker->getSource(),
                    $statements_checker->getSource()->getAliases()
                );
            } catch (DocblockParseException $e) {
                if (IssueBuffer::accepts(
                    new InvalidDocblock(
                        (string)$e->getMessage(),
                        new CodeLocation($statements_checker, $stmt)
                    )
                )) {
                    // fall through
                }
            }

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
        }

        $loop_scope = new LoopScope($foreach_context, $context);

        $protected_var_ids = $context->protected_var_ids;
        if ($var_id) {
            $protected_var_ids[$var_id] = true;
        }
        $loop_scope->protected_var_ids = $protected_var_ids;

        LoopAnalyzer::analyze($statements_checker, $stmt->stmts, [], [], $loop_scope, $inner_loop_context);

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
}
