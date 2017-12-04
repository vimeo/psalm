<?php
namespace Psalm\Checker\Statements\Block;

use PhpParser;
use Psalm\Checker\ClassChecker;
use Psalm\Checker\ClassLikeChecker;
use Psalm\Checker\CommentChecker;
use Psalm\Checker\InterfaceChecker;
use Psalm\Checker\MethodChecker;
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
use Psalm\VarDocblockComment;

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
        } elseif ($var_id && $foreach_context->hasVariable($var_id)) {
            $iterator_type = $foreach_context->vars_in_scope[$var_id];
        } else {
            $iterator_type = null;
        }

        $project_checker = $statements_checker->getFileChecker()->project_checker;

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
                    ) ||
                        (
                            InterfaceChecker::interfaceExists($project_checker, $iterator_type->value)
                            && InterfaceChecker::interfaceExtends(
                                $project_checker,
                                $iterator_type->value,
                                'Iterator'
                            )
                        )
                    ) {
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
                    } elseif (ClassChecker::classImplements(
                        $project_checker,
                        $iterator_type->value,
                        'Traversable'
                    ) ||
                        (
                            InterfaceChecker::interfaceExists($project_checker, $iterator_type->value)
                            && InterfaceChecker::interfaceExtends(
                                $project_checker,
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
            /** @var VarDocblockComment|null $var_comment */
            $var_comment = null;

            try {
                $var_comment = CommentChecker::getTypeFromComment(
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

            if ($var_comment && $var_comment->var_id) {
                $comment_type = ExpressionChecker::fleshOutType(
                    $project_checker,
                    Type::parseString($var_comment->type),
                    $context->self
                );

                $foreach_context->vars_in_scope[$var_comment->var_id] = $comment_type;
            }
        }

        $loop_scope = new LoopScope($foreach_context, $context);

        LoopChecker::analyze($statements_checker, $stmt->stmts, [], [], $loop_scope);

        $context->vars_possibly_in_scope = array_merge(
            $foreach_context->vars_possibly_in_scope,
            $context->vars_possibly_in_scope
        );

        $context->referenced_var_ids = array_merge(
            $foreach_context->referenced_var_ids,
            $context->referenced_var_ids
        );

        return null;
    }
}
