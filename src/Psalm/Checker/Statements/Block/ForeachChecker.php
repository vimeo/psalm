<?php
namespace Psalm\Checker\Statements\Block;

use PhpParser;
use Psalm\Checker\ClassChecker;
use Psalm\Checker\ClassLikeChecker;
use Psalm\Checker\CommentChecker;
use Psalm\Checker\MethodChecker;
use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\Checker\StatementsChecker;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\InvalidIterator;
use Psalm\Issue\NullIterator;
use Psalm\Issue\PossiblyNullIterator;
use Psalm\IssueBuffer;
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
        $foreach_context->inside_loop = true;

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
        } elseif ($foreach_context->hasVariable($var_id)) {
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

            $project_checker = $statements_checker->getFileChecker()->project_checker;

            foreach ($iterator_type->types as $iterator_type) {
                // if it's an empty array, we cannot iterate over it
                if ((string) $iterator_type === 'array<empty, empty>') {
                    continue;
                }

                if ($iterator_type instanceof Type\Atomic\TNull) {
                    continue;
                }

                if ($iterator_type instanceof Type\Atomic\TArray) {
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
                            $project_checker,
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
                            ClassChecker::classImplements(
                                $project_checker,
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

                    if (ClassChecker::classImplements(
                        $project_checker,
                        $iterator_type->value,
                        'Iterator'
                    )) {
                        $iterator_method = $iterator_type->value . '::current';
                        $iterator_class_type = MethodChecker::getMethodReturnType($project_checker, $iterator_method);

                        if ($iterator_class_type) {
                            $value_type_part = ExpressionChecker::fleshOutType(
                                $project_checker,
                                $iterator_class_type,
                                $iterator_type->value,
                                $iterator_method
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
            }
        }

        $before_context = clone $foreach_context;

        if ($stmt->keyVar && $stmt->keyVar instanceof PhpParser\Node\Expr\Variable && is_string($stmt->keyVar->name)) {
            $key_var_id = '$' . $stmt->keyVar->name;
            $foreach_context->vars_in_scope[$key_var_id] = $key_type ?: Type::getMixed();
            $foreach_context->vars_possibly_in_scope[$key_var_id] = true;

            if (!$statements_checker->hasVariable($key_var_id)) {
                $statements_checker->registerVariable(
                    $key_var_id,
                    new CodeLocation($statements_checker, $stmt->keyVar)
                );
            }
        }

        if ($stmt->valueVar instanceof PhpParser\Node\Expr\List_) {
            foreach ($stmt->valueVar->vars as $var) {
                if ($var && $var instanceof PhpParser\Node\Expr\Variable && is_string($var->name)) {
                    $list_var_id = '$' . $var->name;
                    $foreach_context->vars_in_scope[$list_var_id] = Type::getMixed();
                    $foreach_context->vars_possibly_in_scope[$list_var_id] = true;

                    if (!$statements_checker->hasVariable($list_var_id)) {
                        $statements_checker->registerVariable(
                            $list_var_id,
                            new CodeLocation($statements_checker, $var)
                        );
                    }
                }
            }
        } elseif ($stmt->valueVar instanceof PhpParser\Node\Expr\Variable && is_string($stmt->valueVar->name)) {
            $value_var_id = '$' . $stmt->valueVar->name;
            $foreach_context->vars_in_scope[$value_var_id] = $value_type ? $value_type : Type::getMixed();
            $foreach_context->vars_possibly_in_scope[$value_var_id] = true;

            if (!$statements_checker->hasVariable($value_var_id)) {
                $statements_checker->registerVariable(
                    $value_var_id,
                    new CodeLocation($statements_checker, $stmt->valueVar)
                );
            }
        }

        $doc_comment_text = (string)$stmt->getDocComment();

        if ($doc_comment_text) {
            $var_comment = CommentChecker::getTypeFromComment(
                $doc_comment_text,
                $foreach_context,
                $statements_checker->getSource(),
                $statements_checker->getSource()->getAliases()
            );

            if ($var_comment && $var_comment->var_id) {
                $foreach_context->vars_in_scope[$var_comment->var_id] = Type::parseString($var_comment->type);
            }
        }

        $changed_vars = Context::getNewOrUpdatedVarIds($before_context, $foreach_context);

        $statements_checker->analyzeLoop($stmt->stmts, $changed_vars, $foreach_context, $context);

        foreach ($context->vars_in_scope as $var => $type) {
            if ($type->isMixed()) {
                continue;
            }

            if (!isset($foreach_context->vars_in_scope[$var])) {
                unset($context->vars_in_scope[$var]);
                continue;
            }

            if ($foreach_context->vars_in_scope[$var]->isMixed()) {
                $context->vars_in_scope[$var] = $foreach_context->vars_in_scope[$var];
            }

            if ((string) $foreach_context->vars_in_scope[$var] !== (string) $type) {
                $context->vars_in_scope[$var] = Type::combineUnionTypes(
                    $context->vars_in_scope[$var],
                    $foreach_context->vars_in_scope[$var]
                );

                $context->removeVarFromConflictingClauses($var);
            }
        }

        $context->vars_possibly_in_scope = array_merge(
            $foreach_context->vars_possibly_in_scope,
            $context->vars_possibly_in_scope
        );

        if ($context->collect_references) {
            $context->referenced_vars = array_merge(
                $foreach_context->referenced_vars,
                $context->referenced_vars
            );
        }

        return null;
    }
}
