<?php
namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\ImpureMethodCall;
use Psalm\Issue\InvalidOperand;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic\TNamedObject;

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
        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Concat && $nesting > 100) {
            $statements_analyzer->node_data->setType($stmt, Type::getBool());

            // ignore deeply-nested string concatenation
            return true;
        }

        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd ||
            $stmt instanceof PhpParser\Node\Expr\BinaryOp\LogicalAnd
        ) {
            $expr_result = BinaryOp\AndAnalyzer::analyze(
                $statements_analyzer,
                $stmt,
                $context,
                $from_stmt
            );

            $statements_analyzer->node_data->setType($stmt, Type::getBool());

            return $expr_result;
        }

        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr ||
            $stmt instanceof PhpParser\Node\Expr\BinaryOp\LogicalOr
        ) {
            $expr_result = BinaryOp\OrAnalyzer::analyze(
                $statements_analyzer,
                $stmt,
                $context,
                $from_stmt
            );

            $statements_analyzer->node_data->setType($stmt, Type::getBool());

            return $expr_result;
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
            $stmt_type = Type::getString();

            BinaryOp\ConcatAnalyzer::analyze(
                $statements_analyzer,
                $stmt->left,
                $stmt->right,
                $context,
                $result_type
            );

            if ($result_type) {
                $stmt_type = $result_type;
            }

            $codebase = $statements_analyzer->getCodebase();

            if ($codebase->taint
                && $codebase->config->trackTaintsInPath($statements_analyzer->getFilePath())
                && !\in_array('TaintedInput', $statements_analyzer->getSuppressedIssues())
            ) {
                $stmt_left_type = $statements_analyzer->node_data->getType($stmt->left);
                $stmt_right_type = $statements_analyzer->node_data->getType($stmt->right);

                $var_location = new CodeLocation($statements_analyzer, $stmt);

                $new_parent_node = \Psalm\Internal\Taint\TaintNode::getForAssignment('concat', $var_location);
                $codebase->taint->addTaintNode($new_parent_node);

                $stmt_type->parent_nodes = [$new_parent_node];

                if ($stmt_left_type && $stmt_left_type->parent_nodes) {
                    foreach ($stmt_left_type->parent_nodes as $parent_node) {
                        $codebase->taint->addPath($parent_node, $new_parent_node, 'concat');
                    }
                }

                if ($stmt_right_type && $stmt_right_type->parent_nodes) {
                    foreach ($stmt_right_type->parent_nodes as $parent_node) {
                        $codebase->taint->addPath($parent_node, $new_parent_node, 'concat');
                    }
                }
            }

            $statements_analyzer->node_data->setType($stmt, $stmt_type);

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

            if (($stmt instanceof PhpParser\Node\Expr\BinaryOp\Greater
                    || $stmt instanceof PhpParser\Node\Expr\BinaryOp\GreaterOrEqual
                    || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Smaller
                    || $stmt instanceof PhpParser\Node\Expr\BinaryOp\SmallerOrEqual)
                && $statements_analyzer->getCodebase()->config->strict_binary_operands
                && $stmt_left_type
                && $stmt_right_type
                && (($stmt_left_type->isSingle() && $stmt_left_type->hasBool())
                    || ($stmt_right_type->isSingle() && $stmt_right_type->hasBool()))
            ) {
                if (IssueBuffer::accepts(
                    new InvalidOperand(
                        'Cannot compare ' . $stmt_left_type->getId() . ' to ' . $stmt_right_type->getId(),
                        new CodeLocation($statements_analyzer, $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            if (($stmt instanceof PhpParser\Node\Expr\BinaryOp\Equal
                    || $stmt instanceof PhpParser\Node\Expr\BinaryOp\NotEqual
                    || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Identical
                    || $stmt instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical)
                && $stmt->left instanceof PhpParser\Node\Expr\FuncCall
                && $stmt->left->name instanceof PhpParser\Node\Name
                && $stmt->left->name->parts === ['substr']
                && isset($stmt->left->args[1])
                && $stmt_right_type
                && $stmt_right_type->hasLiteralString()
            ) {
                $from_type = $statements_analyzer->node_data->getType($stmt->left->args[1]->value);

                $length_type = isset($stmt->left->args[2])
                    ? ($statements_analyzer->node_data->getType($stmt->left->args[2]->value) ?: Type::getMixed())
                    : null;

                $string_length = null;

                if ($from_type && $from_type->isSingleIntLiteral() && $length_type === null) {
                    $string_length = -$from_type->getSingleIntLiteral()->value;
                } elseif ($length_type && $length_type->isSingleIntLiteral()) {
                    $string_length = $length_type->getSingleIntLiteral()->value;
                }

                if ($string_length > 0) {
                    foreach ($stmt_right_type->getAtomicTypes() as $atomic_right_type) {
                        if ($atomic_right_type instanceof Type\Atomic\TLiteralString) {
                            if (\strlen($atomic_right_type->value) !== $string_length) {
                                if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Equal
                                    || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Identical
                                ) {
                                    if ($atomic_right_type->from_docblock) {
                                        if (IssueBuffer::accepts(
                                            new \Psalm\Issue\DocblockTypeContradiction(
                                                $atomic_right_type . ' string length is not ' . $string_length,
                                                new CodeLocation($statements_analyzer, $stmt)
                                            ),
                                            $statements_analyzer->getSuppressedIssues()
                                        )) {
                                            // fall through
                                        }
                                    } else {
                                        if (IssueBuffer::accepts(
                                            new \Psalm\Issue\TypeDoesNotContainType(
                                                $atomic_right_type . ' string length is not ' . $string_length,
                                                new CodeLocation($statements_analyzer, $stmt)
                                            ),
                                            $statements_analyzer->getSuppressedIssues()
                                        )) {
                                            // fall through
                                        }
                                    }
                                } else {
                                    if ($atomic_right_type->from_docblock) {
                                        if (IssueBuffer::accepts(
                                            new \Psalm\Issue\RedundantConditionGivenDocblockType(
                                                $atomic_right_type . ' string length is never ' . $string_length,
                                                new CodeLocation($statements_analyzer, $stmt)
                                            ),
                                            $statements_analyzer->getSuppressedIssues()
                                        )) {
                                            // fall through
                                        }
                                    } else {
                                        if (IssueBuffer::accepts(
                                            new \Psalm\Issue\RedundantCondition(
                                                $atomic_right_type . ' string length is never ' . $string_length,
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
            }

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
