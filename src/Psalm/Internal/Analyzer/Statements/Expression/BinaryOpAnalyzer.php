<?php
namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\Internal\Analyzer\Statements\Expression\Assignment\ArrayAssignmentAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\Statements\Block\IfAnalyzer;
use Psalm\Internal\Analyzer\TypeAnalyzer;
use Psalm\CodeLocation;
use Psalm\Config;
use Psalm\Context;
use Psalm\Issue\FalseOperand;
use Psalm\Issue\ImplicitToStringCast;
use Psalm\Issue\ImpureMethodCall;
use Psalm\Issue\InvalidOperand;
use Psalm\Issue\MixedOperand;
use Psalm\Issue\NullOperand;
use Psalm\Issue\PossiblyFalseOperand;
use Psalm\Issue\PossiblyInvalidOperand;
use Psalm\Issue\PossiblyNullOperand;
use Psalm\Issue\StringIncrement;
use Psalm\IssueBuffer;
use Psalm\StatementsSource;
use Psalm\Type;
use Psalm\Type\Algebra;
use Psalm\Type\Atomic\ObjectLike;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Reconciler;
use Psalm\Internal\Type\AssertionReconciler;
use Psalm\Internal\Type\TypeCombination;
use function array_merge;
use function array_diff_key;
use function array_filter;
use function array_intersect_key;
use function array_values;
use function array_map;
use function array_keys;
use function preg_match;
use function preg_quote;
use function strtolower;
use function strlen;

/**
 * @internal
 */
class BinaryOpAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\BinaryOp $stmt,
        Context $context,
        int $nesting = 0,
        bool $from_stmt = false
    ) : bool {
        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Concat && $nesting > 20) {
            // ignore deeply-nested string concatenation
            return true;
        }

        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd ||
            $stmt instanceof PhpParser\Node\Expr\BinaryOp\LogicalAnd
        ) {
            return BinaryOp\AndAnalyzer::analyze(
                $statements_analyzer,
                $stmt,
                $context,
                $from_stmt
            );
        }

        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr ||
            $stmt instanceof PhpParser\Node\Expr\BinaryOp\LogicalOr
        ) {
            return BinaryOp\OrAnalyzer::analyze(
                $statements_analyzer,
                $stmt,
                $context,
                $from_stmt
            );
        }

        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Coalesce) {
            return BinaryOp\CoalesceAnalyzer::analyze(
                $statements_analyzer,
                $stmt,
                $context
            );
        }

        if ($stmt->left instanceof PhpParser\Node\Expr\BinaryOp) {
            if (self::analyze($statements_analyzer, $stmt->left, $context, ++$nesting) === false) {
                return false;
            }
        } else {
            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->left, $context) === false) {
                return false;
            }
        }

        if ($stmt->right instanceof PhpParser\Node\Expr\BinaryOp) {
            if (self::analyze($statements_analyzer, $stmt->right, $context, ++$nesting) === false) {
                return false;
            }
        } else {
            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->right, $context) === false) {
                return false;
            }
        }

        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Concat) {
            BinaryOp\ConcatAnalyzer::analyze(
                $statements_analyzer,
                $stmt->left,
                $stmt->right,
                $context
            );

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Spaceship) {
            $statements_analyzer->node_data->setType($stmt, Type::getInt());

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Equal
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\NotEqual
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Identical
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Greater
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\GreaterOrEqual
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Smaller
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\SmallerOrEqual
        ) {
            $statements_analyzer->node_data->setType($stmt, Type::getBool());

            $stmt_left_type = $statements_analyzer->node_data->getType($stmt->left);
            $stmt_right_type = $statements_analyzer->node_data->getType($stmt->right);

            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Equal
                && $stmt_left_type
                && $stmt_right_type
                && $context->mutation_free
            ) {
                self::checkForImpureEqualityComparison(
                    $statements_analyzer,
                    $stmt,
                    $stmt_left_type,
                    $stmt_right_type
                );
            }

            return true;
        }

        BinaryOp\NonComparisonOpAnalyzer::analyze(
            $statements_analyzer,
            $stmt,
            $context
        );

        return true;
    }

    private static function checkForImpureEqualityComparison(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\BinaryOp\Equal $stmt,
        Type\Union $stmt_left_type,
        Type\Union $stmt_right_type
    ) : void {
        $codebase = $statements_analyzer->getCodebase();

        if ($stmt_left_type->hasString() && $stmt_right_type->hasObjectType()) {
            foreach ($stmt_right_type->getAtomicTypes() as $atomic_type) {
                if ($atomic_type instanceof TNamedObject) {
                    try {
                        $storage = $codebase->methods->getStorage(
                            new \Psalm\Internal\MethodIdentifier(
                                $atomic_type->value,
                                '__tostring'
                            )
                        );
                    } catch (\UnexpectedValueException $e) {
                        continue;
                    }

                    if (!$storage->mutation_free) {
                        if (IssueBuffer::accepts(
                            new ImpureMethodCall(
                                'Cannot call a possibly-mutating method '
                                    . $atomic_type->value . '::__toString from a pure context',
                                new CodeLocation($statements_analyzer, $stmt)
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    }
                }
            }
        } elseif ($stmt_right_type->hasString() && $stmt_left_type->hasObjectType()) {
            foreach ($stmt_left_type->getAtomicTypes() as $atomic_type) {
                if ($atomic_type instanceof TNamedObject) {
                    try {
                        $storage = $codebase->methods->getStorage(
                            new \Psalm\Internal\MethodIdentifier(
                                $atomic_type->value,
                                '__tostring'
                            )
                        );
                    } catch (\UnexpectedValueException $e) {
                        continue;
                    }

                    if (!$storage->mutation_free) {
                        if (IssueBuffer::accepts(
                            new ImpureMethodCall(
                                'Cannot call a possibly-mutating method '
                                    . $atomic_type->value . '::__toString from a pure context',
                                new CodeLocation($statements_analyzer, $stmt)
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    }
                }
            }
        }
    }
}
