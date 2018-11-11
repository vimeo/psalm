<?php
namespace Psalm\Internal\Analyzer\Statements;

use PhpParser;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\CommentAnalyzer;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\TraitAnalyzer;
use Psalm\Internal\Analyzer\TypeAnalyzer;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Exception\DocblockParseException;
use Psalm\Issue\FalsableReturnStatement;
use Psalm\Issue\InvalidDocblock;
use Psalm\Issue\InvalidReturnStatement;
use Psalm\Issue\LessSpecificReturnStatement;
use Psalm\Issue\MixedReturnStatement;
use Psalm\Issue\MixedTypeCoercion;
use Psalm\Issue\NullableReturnStatement;
use Psalm\IssueBuffer;
use Psalm\Type;

class ReturnAnalyzer
{
    /**
     * @param  PhpParser\Node\Stmt\Return_ $stmt
     * @param  Context                     $context
     *
     * @return false|null
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\Return_ $stmt,
        Context $context
    ) {
        $doc_comment_text = (string)$stmt->getDocComment();

        $var_comments = [];
        $var_comment_type = null;

        $source = $statements_analyzer->getSource();

        $codebase = $statements_analyzer->getCodebase();

        if ($doc_comment_text) {
            try {
                $var_comments = CommentAnalyzer::getTypeFromComment(
                    $doc_comment_text,
                    $source,
                    $source->getAliases()
                );
            } catch (DocblockParseException $e) {
                if (IssueBuffer::accepts(
                    new InvalidDocblock(
                        (string)$e->getMessage(),
                        new CodeLocation($source, $stmt)
                    )
                )) {
                    // fall through
                }
            }

            foreach ($var_comments as $var_comment) {
                $comment_type = ExpressionAnalyzer::fleshOutType(
                    $codebase,
                    $var_comment->type,
                    $context->self,
                    $context->self
                );

                if (!$var_comment->var_id) {
                    $var_comment_type = $comment_type;
                    continue;
                }

                $context->vars_in_scope[$var_comment->var_id] = $comment_type;
            }
        }

        if ($stmt->expr) {
            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context) === false) {
                return false;
            }

            if ($var_comment_type) {
                $stmt->inferredType = $var_comment_type;
            } elseif (isset($stmt->expr->inferredType)) {
                $stmt->inferredType = $stmt->expr->inferredType;

                if ($stmt->inferredType->isVoid()) {
                    $stmt->inferredType = Type::getNull();
                }
            } else {
                $stmt->inferredType = Type::getMixed();
            }
        } else {
            $stmt->inferredType = Type::getVoid();
        }

        if ($source instanceof FunctionLikeAnalyzer
            && !($source->getSource() instanceof TraitAnalyzer)
        ) {
            $source->addReturnTypes($stmt->expr ? (string) $stmt->inferredType : '', $context);

            $storage = $source->getFunctionLikeStorage($statements_analyzer);

            $cased_method_id = $source->getCorrectlyCasedMethodId();

            if ($stmt->expr) {
                if ($storage->return_type && !$storage->return_type->isMixed()) {
                    $inferred_type = ExpressionAnalyzer::fleshOutType(
                        $codebase,
                        $stmt->inferredType,
                        $source->getFQCLN(),
                        $source->getFQCLN()
                    );

                    $local_return_type = $source->getLocalReturnType($storage->return_type);

                    if ($local_return_type->isGenerator() && $storage->has_yield) {
                        return null;
                    }

                    if ($stmt->inferredType->isMixed()) {
                        if ($local_return_type->isVoid()) {
                            if (IssueBuffer::accepts(
                                new InvalidReturnStatement(
                                    'No return values are expected for ' . $cased_method_id,
                                    new CodeLocation($source, $stmt)
                                ),
                                $statements_analyzer->getSuppressedIssues()
                            )) {
                                return false;
                            }
                        }

                        $codebase->analyzer->incrementMixedCount($statements_analyzer->getFilePath());

                        if (IssueBuffer::accepts(
                            new MixedReturnStatement(
                                'Could not infer a return type',
                                new CodeLocation($source, $stmt)
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            return false;
                        }

                        return null;
                    }

                    $codebase->analyzer->incrementNonMixedCount($statements_analyzer->getFilePath());

                    if ($local_return_type->isVoid()) {
                        if (IssueBuffer::accepts(
                            new InvalidReturnStatement(
                                'No return values are expected for ' . $cased_method_id,
                                new CodeLocation($source, $stmt)
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            return false;
                        }

                        return null;
                    }

                    if (!TypeAnalyzer::isContainedBy(
                        $codebase,
                        $inferred_type,
                        $local_return_type,
                        true,
                        true,
                        $has_scalar_match,
                        $type_coerced,
                        $type_coerced_from_mixed,
                        $to_string_cast,
                        $type_coerced_from_scalar
                    )
                    ) {
                        // is the declared return type more specific than the inferred one?
                        if ($type_coerced) {
                            if ($type_coerced_from_mixed) {
                                if (IssueBuffer::accepts(
                                    new MixedTypeCoercion(
                                        'The type \'' . $stmt->inferredType . '\' is more general than the declared '
                                            . 'return type \'' . $local_return_type . '\' for ' . $cased_method_id,
                                        new CodeLocation($source, $stmt)
                                    ),
                                    $statements_analyzer->getSuppressedIssues()
                                )) {
                                    return false;
                                }
                            } else {
                                if (IssueBuffer::accepts(
                                    new LessSpecificReturnStatement(
                                        'The type \'' . $stmt->inferredType . '\' is more general than the declared '
                                            . 'return type \'' . $local_return_type . '\' for ' . $cased_method_id,
                                        new CodeLocation($source, $stmt)
                                    ),
                                    $statements_analyzer->getSuppressedIssues()
                                )) {
                                    return false;
                                }
                            }

                            foreach ($local_return_type->getTypes() as $local_type_part) {
                                if ($local_type_part instanceof Type\Atomic\TClassString
                                    && $stmt->expr instanceof PhpParser\Node\Scalar\String_
                                ) {
                                    if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                                        $statements_analyzer,
                                        $stmt->expr->value,
                                        new CodeLocation($source, $stmt->expr),
                                        $statements_analyzer->getSuppressedIssues()
                                    ) === false
                                    ) {
                                        return false;
                                    }
                                } elseif ($local_type_part instanceof Type\Atomic\TArray
                                    && $stmt->expr instanceof PhpParser\Node\Expr\Array_
                                ) {
                                    foreach ($local_type_part->type_params[1]->getTypes() as $local_array_type_part) {
                                        if ($local_array_type_part instanceof Type\Atomic\TClassString) {
                                            foreach ($stmt->expr->items as $item) {
                                                if ($item && $item->value instanceof PhpParser\Node\Scalar\String_) {
                                                    if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                                                        $statements_analyzer,
                                                        $item->value->value,
                                                        new CodeLocation($source, $item->value),
                                                        $statements_analyzer->getSuppressedIssues()
                                                    ) === false
                                                    ) {
                                                        return false;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        } else {
                            if (IssueBuffer::accepts(
                                new InvalidReturnStatement(
                                    'The type \'' . $stmt->inferredType->getId()
                                        . '\' does not match the declared return '
                                        . 'type \'' . $local_return_type->getId() . '\' for ' . $cased_method_id,
                                    new CodeLocation($source, $stmt)
                                ),
                                $statements_analyzer->getSuppressedIssues()
                            )) {
                                return false;
                            }
                        }
                    }

                    if (!$stmt->inferredType->ignore_nullable_issues
                        && $inferred_type->isNullable()
                        && !$local_return_type->isNullable()
                    ) {
                        if (IssueBuffer::accepts(
                            new NullableReturnStatement(
                                'The declared return type \'' . $local_return_type . '\' for '
                                    . $cased_method_id . ' is not nullable, but the function returns \''
                                        . $inferred_type . '\'',
                                new CodeLocation($source, $stmt)
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            return false;
                        }
                    }

                    if (!$stmt->inferredType->ignore_falsable_issues
                        && $inferred_type->isFalsable()
                        && !$local_return_type->isFalsable()
                        && !$local_return_type->hasBool()
                    ) {
                        if (IssueBuffer::accepts(
                            new FalsableReturnStatement(
                                'The declared return type \'' . $local_return_type . '\' for '
                                    . $cased_method_id . ' does not allow false, but the function returns \''
                                        . $inferred_type . '\'',
                                new CodeLocation($source, $stmt)
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            return false;
                        }
                    }
                }
            } else {
                if ($storage->signature_return_type
                    && !$storage->signature_return_type->isVoid()
                    && (!$storage->signature_return_type->isGenerator() || !$storage->has_yield)
                ) {
                    if (IssueBuffer::accepts(
                        new InvalidReturnStatement(
                            'Empty return statement is not expected in ' . $cased_method_id,
                            new CodeLocation($source, $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        return false;
                    }

                    return null;
                }
            }
        }

        return null;
    }
}
