<?php

namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\CodeLocation\DocblockTypeLocation;
use Psalm\Context;
use Psalm\Exception\DocblockParseException;
use Psalm\Internal\Analyzer\CommentAnalyzer;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\ClassTemplateParamCollector;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\AtomicPropertyFetchAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\TraitAnalyzer;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Issue\InvalidDocblock;
use Psalm\Issue\UnnecessaryVarAnnotation;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TNamedObject;

use function array_values;

/**
 * @internal
 */
final class YieldAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Yield_ $stmt,
        Context $context
    ): bool {
        $doc_comment = $stmt->getDocComment();

        $var_comments = [];
        $var_comment_type = null;

        $codebase = $statements_analyzer->getCodebase();

        if ($doc_comment) {
            try {
                $var_comments = CommentAnalyzer::getTypeFromComment(
                    $doc_comment,
                    $statements_analyzer,
                    $statements_analyzer->getAliases(),
                );
            } catch (DocblockParseException $e) {
                IssueBuffer::maybeAdd(
                    new InvalidDocblock(
                        $e->getMessage(),
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                    ),
                );
            }

            foreach ($var_comments as $var_comment) {
                if (!$var_comment->type) {
                    continue;
                }

                $comment_type = TypeExpander::expandUnion(
                    $codebase,
                    $var_comment->type,
                    $context->self,
                    $context->self ? new TNamedObject($context->self) : null,
                    $statements_analyzer->getParentFQCLN(),
                );

                $type_location = null;

                if ($var_comment->type_start
                    && $var_comment->type_end
                    && $var_comment->line_number
                ) {
                    $type_location = new DocblockTypeLocation(
                        $statements_analyzer,
                        $var_comment->type_start,
                        $var_comment->type_end,
                        $var_comment->line_number,
                    );
                }

                if (!$var_comment->var_id) {
                    $var_comment_type = $comment_type;
                    continue;
                }

                if ($codebase->find_unused_variables
                    && $type_location
                    && isset($context->vars_in_scope[$var_comment->var_id])
                    && $context->vars_in_scope[$var_comment->var_id]->getId() === $comment_type->getId()
                ) {
                    $project_analyzer = $statements_analyzer->getProjectAnalyzer();

                    if ($codebase->alter_code
                        && isset($project_analyzer->getIssuesToFix()['UnnecessaryVarAnnotation'])
                    ) {
                        FileManipulationBuffer::addVarAnnotationToRemove($type_location);
                    } else {
                        IssueBuffer::maybeAdd(
                            new UnnecessaryVarAnnotation(
                                'The @var annotation for ' . $var_comment->var_id . ' is unnecessary',
                                $type_location,
                            ),
                            $statements_analyzer->getSuppressedIssues(),
                            true,
                        );
                    }
                }

                if (isset($context->vars_in_scope[$var_comment->var_id])) {
                    $comment_type = $comment_type->setParentNodes(
                        $context->vars_in_scope[$var_comment->var_id]->parent_nodes,
                    );
                }

                $context->vars_in_scope[$var_comment->var_id] = $comment_type;
            }
        }

        if ($stmt->key) {
            $context->inside_call = true;
            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->key, $context) === false) {
                return false;
            }
            $context->inside_call = false;
        }

        if ($stmt->value) {
            $context->inside_call = true;
            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->value, $context) === false) {
                return false;
            }
            $context->inside_call = false;

            if ($var_comment_type) {
                $expression_type = $var_comment_type;
            } elseif ($stmt_var_type = $statements_analyzer->node_data->getType($stmt->value)) {
                $expression_type = $stmt_var_type;
            } else {
                $expression_type = Type::getMixed();
            }
        } else {
            $expression_type = Type::getNever();
        }

        $yield_type = null;

        foreach ($expression_type->getAtomicTypes() as $expression_atomic_type) {
            if (!$expression_atomic_type instanceof TNamedObject) {
                continue;
            }
            if (!$codebase->classlikes->classOrInterfaceExists($expression_atomic_type->value)) {
                continue;
            }

            $classlike_storage = $codebase->classlike_storage_provider->get($expression_atomic_type->value);

            if (!$classlike_storage->yield) {
                continue;
            }
            $declaring_classlike_storage = $classlike_storage->declaring_yield_fqcn
                ? $codebase->classlike_storage_provider->get($classlike_storage->declaring_yield_fqcn)
                : $classlike_storage;

            $yield_candidate_type = $classlike_storage->yield;
            $yield_candidate_type = !$yield_candidate_type->isMixed()
                ? TypeExpander::expandUnion(
                    $codebase,
                    $yield_candidate_type,
                    $expression_atomic_type->value,
                    $expression_atomic_type->value,
                    null,
                    true,
                    false,
                )
                : $yield_candidate_type;

            $class_template_params = ClassTemplateParamCollector::collect(
                $codebase,
                $declaring_classlike_storage,
                $classlike_storage,
                null,
                $expression_atomic_type,
                true,
            );

            if ($class_template_params) {
                if (!$expression_atomic_type instanceof TGenericObject) {
                    $type_params = [];

                    foreach ($class_template_params as $type_map) {
                        $type_params[] = array_values($type_map)[0];
                    }

                    $expression_atomic_type = new TGenericObject($expression_atomic_type->value, $type_params);
                }

                $yield_candidate_type = AtomicPropertyFetchAnalyzer::localizePropertyType(
                    $codebase,
                    $yield_candidate_type,
                    $expression_atomic_type,
                    $classlike_storage,
                    $declaring_classlike_storage,
                );
            }

            $yield_type = Type::combineUnionTypes(
                $yield_type,
                $yield_candidate_type,
                $codebase,
            );
        }

        if ($yield_type) {
            $expression_type = $expression_type->getBuilder()->substitute($expression_type, $yield_type)->freeze();
        }

        $statements_analyzer->node_data->setType($stmt, $expression_type);

        $source = $statements_analyzer->getSource();

        if ($source instanceof FunctionLikeAnalyzer
            && !($source->getSource() instanceof TraitAnalyzer)
        ) {
            $source->examineParamTypes($statements_analyzer, $context, $codebase, $stmt);

            $storage = $source->getFunctionLikeStorage($statements_analyzer);

            if ($storage->return_type && !$yield_type) {
                foreach ($storage->return_type->getAtomicTypes() as $atomic_return_type) {
                    if ($atomic_return_type instanceof TNamedObject
                        && $atomic_return_type->value === 'Generator'
                    ) {
                        if ($atomic_return_type instanceof TGenericObject) {
                            if (!$atomic_return_type->type_params[2]->isVoid()) {
                                $statements_analyzer->node_data->setType(
                                    $stmt,
                                    $atomic_return_type->type_params[2],
                                );
                            }
                        } else {
                            $statements_analyzer->node_data->setType(
                                $stmt,
                                Type::combineUnionTypes(
                                    Type::getMixed(),
                                    $expression_type,
                                ),
                            );
                        }
                    }
                }
            }
        }

        return true;
    }
}
