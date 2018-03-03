<?php
namespace Psalm\Checker\Statements\Block;

use PhpParser;
use Psalm\Checker\ClassLikeChecker;
use Psalm\Checker\CommentChecker;
use Psalm\Checker\Statements\Expression\AssignmentChecker;
use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\Checker\StatementsChecker;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Exception\DocblockParseException;
use Psalm\Issue\InvalidDocblock;
use Psalm\Issue\InvalidIterator;
use Psalm\Issue\NullIterator;
use Psalm\Issue\PossiblyNullIterator;
use Psalm\Issue\RawObjectIteration;
use Psalm\IssueBuffer;
use Psalm\Scope\LoopScope;
use Psalm\Type;

class ForeachChecker
{
    /**
     * @param   StatementsChecker               $statements_checker
     * @param   PhpParser\Node\Stmt\Foreach_    $stmt
     * @param   Context                         $context
     *
     * @return  false|null
     */
    public static function analyze(
        StatementsChecker $statements_checker,
        PhpParser\Node\Stmt\Foreach_ $stmt,
        Context $context
    ) {
        if (ExpressionChecker::analyze($statements_checker, $stmt->expr, $context) === false) {
            return false;
        }

        $foreach_context = clone $context;

        $project_checker = $statements_checker->getFileChecker()->project_checker;
        $codebase = $project_checker->codebase;

        if ($project_checker->alter_code) {
            $foreach_context->branch_point =
                $foreach_context->branch_point ?: (int) $stmt->getAttribute('startFilePos');
        }

        $key_type = null;
        $value_type = null;

        $var_id = ExpressionChecker::getVarId(
            $stmt->expr,
            $statements_checker->getFQCLN(),
            $statements_checker
        );

        if (isset($stmt->expr->inferredType)) {
            /** @var Type\Union */
            $iterator_type = $stmt->expr->inferredType;
        } elseif ($var_id && $foreach_context->hasVariable($var_id, $statements_checker)) {
            $iterator_type = $foreach_context->vars_in_scope[$var_id];
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
            }

            foreach ($iterator_type->getTypes() as $iterator_type) {
                // if it's an empty array, we cannot iterate over it
                if ($iterator_type instanceof Type\Atomic\TArray
                    && $iterator_type->type_params[1]->isEmpty()
                ) {
                    continue;
                }

                if ($iterator_type instanceof Type\Atomic\TNull) {
                    continue;
                }

                if ($iterator_type instanceof Type\Atomic\TArray
                    || $iterator_type instanceof Type\Atomic\ObjectLike
                ) {
                    if ($iterator_type instanceof Type\Atomic\ObjectLike) {
                        $iterator_type = $iterator_type->getGenericArrayType();
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
                    continue;
                }

                if ($iterator_type instanceof Type\Atomic\Scalar ||
                    $iterator_type instanceof Type\Atomic\TVoid
                ) {
                    if (IssueBuffer::accepts(
                        new InvalidIterator(
                            'Cannot iterate over ' . $iterator_type->getKey(),
                            new CodeLocation($statements_checker->getSource(), $stmt->expr)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }

                    $value_type = Type::getMixed();
                } elseif ($iterator_type instanceof Type\Atomic\TObject ||
                    $iterator_type instanceof Type\Atomic\TMixed ||
                    $iterator_type instanceof Type\Atomic\TEmpty
                ) {
                    $value_type = Type::getMixed();
                } elseif ($iterator_type instanceof Type\Atomic\TNamedObject) {
                    if ($iterator_type->value !== 'Traversable' &&
                        $iterator_type->value !== $statements_checker->getClassName()
                    ) {
                        if (ClassLikeChecker::checkFullyQualifiedClassLikeName(
                            $statements_checker,
                            $iterator_type->value,
                            new CodeLocation($statements_checker->getSource(), $stmt->expr),
                            $statements_checker->getSuppressedIssues()
                        ) === false) {
                            return false;
                        }
                    }

                    if ($iterator_type instanceof Type\Atomic\TGenericObject &&
                        (strtolower($iterator_type->value) === 'iterable' ||
                            strtolower($iterator_type->value) === 'traversable' ||
                            $codebase->classImplements(
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
                        continue;
                    }

                    if (!$codebase->classlikes->classOrInterfaceExists($iterator_type->value)) {
                        continue;
                    }

                    if ($codebase->classImplements(
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
                            $value_type_part = ExpressionChecker::fleshOutType(
                                $project_checker,
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
                    } elseif ($codebase->classImplements(
                        $iterator_type->value,
                        'Traversable'
                    ) ||
                        (
                            $codebase->interfaceExists($iterator_type->value)
                            && $codebase->interfaceExtends(
                                $iterator_type->value,
                                'Traversable'
                            )
                        )
                    ) {
                        // @todo try and get value type
                    } elseif (!in_array(
                        strtolower($iterator_type->value),
                        ['iterator', 'iterable', 'traversable'],
                        true
                    )) {
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
        }

        if ($stmt->keyVar && $stmt->keyVar instanceof PhpParser\Node\Expr\Variable && is_string($stmt->keyVar->name)) {
            $key_var_id = '$' . $stmt->keyVar->name;
            $foreach_context->vars_in_scope[$key_var_id] = $key_type ?: Type::getMixed();
            $foreach_context->vars_possibly_in_scope[$key_var_id] = true;

            $location = new CodeLocation($statements_checker, $stmt->keyVar);

            if ($context->collect_references && !isset($foreach_context->byref_constraints[$key_var_id])) {
                $foreach_context->unreferenced_vars[$key_var_id] = $location;
            }

            if (!$statements_checker->hasVariable($key_var_id)) {
                $statements_checker->registerVariable(
                    $key_var_id,
                    $location,
                    $foreach_context->branch_point
                );
            }

            if ($stmt->byRef) {
                $statements_checker->registerVariableUse($location);
            }
        }

        if ($context->collect_references
            && $stmt->byRef
            && $stmt->valueVar instanceof PhpParser\Node\Expr\Variable
            && is_string($stmt->valueVar->name)
        ) {
            $foreach_context->byref_constraints['$' . $stmt->valueVar->name]
                = new \Psalm\ReferenceConstraint($value_type);
        }

        AssignmentChecker::analyze(
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
                $var_comments = CommentChecker::getTypeFromComment(
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

                $comment_type = ExpressionChecker::fleshOutType(
                    $project_checker,
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

        LoopChecker::analyze($statements_checker, $stmt->stmts, [], [], $loop_scope);

        $context->vars_possibly_in_scope = array_merge(
            $foreach_context->vars_possibly_in_scope,
            $context->vars_possibly_in_scope
        );

        $context->referenced_var_ids = array_merge(
            $foreach_context->referenced_var_ids,
            $context->referenced_var_ids
        );

        if ($context->collect_references) {
            $context->unreferenced_vars = $foreach_context->unreferenced_vars;
        }

        return null;
    }
}
