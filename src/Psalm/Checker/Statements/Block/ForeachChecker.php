<?php
namespace Psalm\Checker\Statements\Block;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\IssueBuffer;
use Psalm\Checker\ClassChecker;
use Psalm\Checker\ClassLikeChecker;
use Psalm\Checker\CommentChecker;
use Psalm\Checker\MethodChecker;
use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\Checker\Statements\Expression\AssignmentChecker;
use Psalm\Checker\StatementsChecker;
use Psalm\Issue\InvalidIterator;
use Psalm\Issue\NullReference;
use Psalm\Type;

class ForeachChecker
{
    /**
     * @param   StatementsChecker               $statements_checker
     * @param   PhpParser\Node\Stmt\Foreach_    $stmt
     * @param   Context                         $context
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
        $foreach_context->in_loop = true;

        /** @var Type\Union|null */
        $key_type = null;

        /** @var Type\Union|null */
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
            foreach ($iterator_type->types as $return_type) {
                // if it's an empty array, we cannot iterate over it
                if ((string) $return_type === 'array<empty, empty>') {
                    continue;
                }

                if ($return_type instanceof Type\Atomic\TArray || $return_type instanceof Type\Atomic\TGenericObject) {
                    $value_index = count($return_type->type_params) - 1;
                    $value_type_part = $return_type->type_params[$value_index];

                    if (!$value_type) {
                        $value_type = $value_type_part;
                    } else {
                        $value_type = Type::combineUnionTypes($value_type, $value_type_part);
                    }

                    if ($value_index) {
                        $key_type_part = $return_type->type_params[0];

                        if (!$key_type) {
                            $key_type = $key_type_part;
                        } else {
                            $key_type = Type::combineUnionTypes($key_type, $key_type_part);
                        }
                    }
                    continue;
                }

                if ($return_type instanceof Type\Atomic\Scalar ||
                    $return_type instanceof Type\Atomic\TNull ||
                    $return_type instanceof Type\Atomic\TVoid
                ) {
                    if (IssueBuffer::accepts(
                        new InvalidIterator(
                            'Cannot iterate over ' . $return_type->getKey(),
                            new CodeLocation($statements_checker->getSource(), $stmt->expr)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }

                    $value_type = Type::getMixed();
                } elseif ($return_type instanceof Type\Atomic\TArray ||
                    $return_type instanceof Type\Atomic\TObject ||
                    $return_type instanceof Type\Atomic\TMixed ||
                    $return_type instanceof Type\Atomic\TEmpty ||
                    ($return_type instanceof Type\Atomic\TNamedObject && $return_type->value === 'Generator')
                ) {
                    $value_type = Type::getMixed();
                } elseif ($return_type instanceof Type\Atomic\TNamedObject) {
                    if ($return_type->value !== 'Traversable' &&
                        $return_type->value !== $statements_checker->getClassName()
                    ) {
                        if (ClassLikeChecker::checkFullyQualifiedClassLikeName(
                            $return_type->value,
                            $statements_checker->getFileChecker(),
                            new CodeLocation($statements_checker->getSource(), $stmt->expr),
                            $statements_checker->getSuppressedIssues()
                        ) === false) {
                            return false;
                        }
                    }

                    if (ClassChecker::classImplements(
                        $return_type->value,
                        'Iterator'
                    )) {
                        $iterator_method = $return_type->value . '::current';
                        $iterator_class_type = MethodChecker::getMethodReturnType($iterator_method);

                        if ($iterator_class_type) {
                            $value_type_part = ExpressionChecker::fleshOutTypes(
                                $iterator_class_type,
                                [],
                                $return_type->value,
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

        if ($stmt->keyVar && $stmt->keyVar instanceof PhpParser\Node\Expr\Variable && is_string($stmt->keyVar->name)) {
            $foreach_context->vars_in_scope['$' . $stmt->keyVar->name] = $key_type ?: Type::getMixed();
            $foreach_context->vars_possibly_in_scope['$' . $stmt->keyVar->name] = true;
            $statements_checker->registerVariable('$' . $stmt->keyVar->name, $stmt->getLine());
        }

        AssignmentChecker::analyze(
            $statements_checker,
            $stmt->valueVar,
            null,
            $value_type ?: Type::getMixed(),
            $foreach_context,
            (string)$stmt->getDocComment()
        );

        CommentChecker::getTypeFromComment(
            (string) $stmt->getDocComment(),
            $foreach_context,
            $statements_checker->getSource(),
            null
        );

        $statements_checker->analyze($stmt->stmts, $foreach_context, $context);

        foreach ($context->vars_in_scope as $var => $type) {
            if ($type->isMixed()) {
                continue;
            }

            if (!$foreach_context->hasVariable($var)) {
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

                $context->removeVarFromClauses($var);
            }
        }

        $context->vars_possibly_in_scope = array_merge(
            $foreach_context->vars_possibly_in_scope,
            $context->vars_possibly_in_scope
        );

        if ($context->count_references) {
            $context->referenced_vars = array_merge(
                $foreach_context->referenced_vars,
                $context->referenced_vars
            );
        }

        return null;
    }
}
