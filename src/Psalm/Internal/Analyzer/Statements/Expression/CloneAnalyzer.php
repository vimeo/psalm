<?php

namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\MethodAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\Method\MethodCallProhibitionAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\MethodIdentifier;
use Psalm\Issue\InvalidClone;
use Psalm\Issue\MixedClone;
use Psalm\Issue\PossiblyInvalidClone;
use Psalm\IssueBuffer;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TTemplateParam;

use function array_merge;
use function array_pop;

/**
 * @internal
 */
class CloneAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Clone_ $stmt,
        Context $context
    ): bool {
        $codebase = $statements_analyzer->getCodebase();
        $codebase_methods = $codebase->methods;
        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context) === false) {
            return false;
        }

        $location = new CodeLocation($statements_analyzer->getSource(), $stmt);
        $stmt_expr_type = $statements_analyzer->node_data->getType($stmt->expr);

        if ($stmt_expr_type) {
            $clone_type = $stmt_expr_type;

            $immutable_cloned = false;

            $invalid_clones = [];
            $mixed_clone = false;

            $possibly_valid = false;
            $atomic_types = $clone_type->getAtomicTypes();

            while ($atomic_types) {
                $clone_type_part = array_pop($atomic_types);

                if ($clone_type_part instanceof TMixed) {
                    $mixed_clone = true;
                } elseif ($clone_type_part instanceof TObject) {
                    $possibly_valid = true;
                } elseif ($clone_type_part instanceof TNamedObject) {
                    if (!$codebase->classlikes->classOrInterfaceExists($clone_type_part->value)) {
                        $invalid_clones[] = $clone_type_part->getId();
                    } else {
                        $clone_method_id = new MethodIdentifier(
                            $clone_type_part->value,
                            '__clone',
                        );

                        $does_method_exist = $codebase_methods->methodExists(
                            $clone_method_id,
                            $context->calling_method_id,
                            $location,
                        );
                        $is_method_visible = MethodAnalyzer::isMethodVisible(
                            $clone_method_id,
                            $context,
                            $statements_analyzer->getSource(),
                        );
                        if ($does_method_exist && !$is_method_visible) {
                            $invalid_clones[] = $clone_type_part->getId();
                        } else {
                            MethodCallProhibitionAnalyzer::analyze(
                                $codebase,
                                $context,
                                $clone_method_id,
                                $statements_analyzer->getNamespace(),
                                $location,
                                $statements_analyzer->getSuppressedIssues(),
                            );
                            $possibly_valid = true;
                            $immutable_cloned = true;
                        }
                    }
                } elseif ($clone_type_part instanceof TTemplateParam) {
                    $atomic_types = array_merge($atomic_types, $clone_type_part->as->getAtomicTypes());
                } else {
                    if ($clone_type_part instanceof TFalse
                        && $clone_type->ignore_falsable_issues
                    ) {
                        continue;
                    }

                    if ($clone_type_part instanceof TNull
                        && $clone_type->ignore_nullable_issues
                    ) {
                        continue;
                    }

                    $invalid_clones[] = $clone_type_part->getId();
                }
            }

            if ($mixed_clone) {
                IssueBuffer::maybeAdd(
                    new MixedClone(
                        'Cannot clone mixed',
                        $location,
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            }

            if ($invalid_clones) {
                if ($possibly_valid) {
                    IssueBuffer::maybeAdd(
                        new PossiblyInvalidClone(
                            'Cannot clone ' . $invalid_clones[0],
                            $location,
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                } else {
                    IssueBuffer::maybeAdd(
                        new InvalidClone(
                            'Cannot clone ' . $invalid_clones[0],
                            $location,
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                }

                return true;
            }

            if ($immutable_cloned) {
                $stmt_expr_type = $stmt_expr_type->setProperties([
                    'reference_free' => true,
                    'allow_mutations' => true,
                ]);
            }
            $statements_analyzer->node_data->setType($stmt, $stmt_expr_type);
        }

        return true;
    }
}
