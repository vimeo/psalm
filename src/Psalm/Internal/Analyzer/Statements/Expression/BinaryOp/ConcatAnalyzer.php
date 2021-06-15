<?php
namespace Psalm\Internal\Analyzer\Statements\Expression\BinaryOp;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Config;
use Psalm\Context;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\VariableUseGraph;
use Psalm\Internal\Type\Comparator\AtomicTypeComparator;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Issue\FalseOperand;
use Psalm\Issue\ImplicitToStringCast;
use Psalm\Issue\ImpureMethodCall;
use Psalm\Issue\InvalidOperand;
use Psalm\Issue\MixedOperand;
use Psalm\Issue\NullOperand;
use Psalm\Issue\PossiblyFalseOperand;
use Psalm\Issue\PossiblyInvalidOperand;
use Psalm\Issue\PossiblyNullOperand;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TNamedObject;

use function array_merge;
use function assert;
use function count;
use function reset;
use function strlen;

/**
 * @internal
 */
class ConcatAnalyzer
{
    /**
     * @param  Type\Union|null       &$result_type
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $left,
        PhpParser\Node\Expr $right,
        Context $context,
        Type\Union &$result_type = null
    ): void {
        $codebase = $statements_analyzer->getCodebase();

        $left_type = $statements_analyzer->node_data->getType($left);
        $right_type = $statements_analyzer->node_data->getType($right);
        $config = Config::getInstance();

        if ($left_type && $right_type) {
            $result_type = Type::getString();

            if ($left_type->hasMixed() || $right_type->hasMixed()) {
                if (!$context->collect_initializations
                    && !$context->collect_mutations
                    && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
                    && (!(($parent_source = $statements_analyzer->getSource())
                            instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer)
                        || !$parent_source->getSource() instanceof \Psalm\Internal\Analyzer\TraitAnalyzer)
                ) {
                    $codebase->analyzer->incrementMixedCount($statements_analyzer->getFilePath());
                }

                if ($left_type->hasMixed()) {
                    $arg_location = new CodeLocation($statements_analyzer->getSource(), $left);

                    $origin_locations = [];

                    if ($statements_analyzer->data_flow_graph instanceof VariableUseGraph) {
                        foreach ($left_type->parent_nodes as $parent_node) {
                            $origin_locations = array_merge(
                                $origin_locations,
                                $statements_analyzer->data_flow_graph->getOriginLocations($parent_node)
                            );
                        }
                    }

                    $origin_location = count($origin_locations) === 1 ? reset($origin_locations) : null;

                    if ($origin_location && $origin_location->getHash() === $arg_location->getHash()) {
                        $origin_location = null;
                    }

                    if (IssueBuffer::accepts(
                        new MixedOperand(
                            'Left operand cannot be mixed',
                            $arg_location,
                            $origin_location
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } else {
                    $arg_location = new CodeLocation($statements_analyzer->getSource(), $right);
                    $origin_locations = [];

                    if ($statements_analyzer->data_flow_graph instanceof VariableUseGraph) {
                        foreach ($right_type->parent_nodes as $parent_node) {
                            $origin_locations = array_merge(
                                $origin_locations,
                                $statements_analyzer->data_flow_graph->getOriginLocations($parent_node)
                            );
                        }
                    }

                    $origin_location = count($origin_locations) === 1 ? reset($origin_locations) : null;

                    if ($origin_location && $origin_location->getHash() === $arg_location->getHash()) {
                        $origin_location = null;
                    }

                    if (IssueBuffer::accepts(
                        new MixedOperand(
                            'Right operand cannot be mixed',
                            $arg_location,
                            $origin_location
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                return;
            }

            if (!$context->collect_initializations
                && !$context->collect_mutations
                && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
                && (!(($parent_source = $statements_analyzer->getSource())
                        instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer)
                    || !$parent_source->getSource() instanceof \Psalm\Internal\Analyzer\TraitAnalyzer)
            ) {
                $codebase->analyzer->incrementNonMixedCount($statements_analyzer->getFilePath());
            }

            self::analyzeOperand($statements_analyzer, $left, $left_type, 'Left', $context);
            self::analyzeOperand($statements_analyzer, $right, $right_type, 'Right', $context);

            // If one of the types is a single int or string literal, and the other
            // type is all string or int literals, combine them into new literal(s).
            $literal_concat = false;

            if (($left_type->allStringLiterals() || $left_type->allIntLiterals())
                && ($right_type->allStringLiterals() || $right_type->allIntLiterals())
            ) {
                $literal_concat = true;
                $result_type_parts = [];

                foreach ($left_type->getAtomicTypes() as $left_type_part) {
                    assert($left_type_part instanceof TLiteralString || $left_type_part instanceof TLiteralInt);
                    foreach ($right_type->getAtomicTypes() as $right_type_part) {
                        assert($right_type_part instanceof TLiteralString || $right_type_part instanceof TLiteralInt);
                        $literal = $left_type_part->value . $right_type_part->value;
                        if (strlen($literal) >= $config->max_string_length) {
                            // Literal too long, use non-literal type instead
                            $literal_concat = false;
                            break 2;
                        }

                        $result_type_parts[] = new Type\Atomic\TLiteralString($literal);
                    }
                }

                if (!empty($result_type_parts)) {
                    if ($literal_concat && count($result_type_parts) < 64) {
                        $result_type = new Type\Union($result_type_parts);
                    } else {
                        $result_type = new Type\Union([new Type\Atomic\TNonEmptyNonspecificLiteralString]);
                    }

                    return;
                }
            }

            if (!$literal_concat) {
                $numeric_type = Type::getNumericString();
                $numeric_type->addType(new Type\Atomic\TInt());
                $numeric_type->addType(new Type\Atomic\TFloat());
                $left_is_numeric = UnionTypeComparator::isContainedBy(
                    $codebase,
                    $left_type,
                    $numeric_type
                );

                if ($left_is_numeric) {
                    $right_uint = Type::getPositiveInt();
                    $right_uint->addType(new Type\Atomic\TLiteralInt(0));
                    $right_is_uint = UnionTypeComparator::isContainedBy(
                        $codebase,
                        $right_type,
                        $right_uint
                    );

                    if ($right_is_uint) {
                        $result_type = Type::getNumericString();
                        return;
                    }
                }

                $lowercase_type = clone $numeric_type;
                $lowercase_type->addType(new Type\Atomic\TLowercaseString());

                $all_lowercase = UnionTypeComparator::isContainedBy(
                    $codebase,
                    $left_type,
                    $lowercase_type
                ) && UnionTypeComparator::isContainedBy(
                    $codebase,
                    $right_type,
                    $lowercase_type
                );

                $non_empty_string = Type::getNonEmptyString();

                $has_non_empty = UnionTypeComparator::isContainedBy(
                    $codebase,
                    $left_type,
                    $non_empty_string
                ) || UnionTypeComparator::isContainedBy(
                    $codebase,
                    $right_type,
                    $non_empty_string
                );

                $all_literals = $left_type->allLiterals() && $right_type->allLiterals();

                if ($has_non_empty) {
                    if ($all_literals) {
                        $result_type = new Type\Union([new Type\Atomic\TNonEmptyNonspecificLiteralString]);
                    } elseif ($all_lowercase) {
                        $result_type = Type::getNonEmptyLowercaseString();
                    } else {
                        $result_type = Type::getNonEmptyString();
                    }
                } else {
                    if ($all_literals) {
                        $result_type = new Type\Union([new Type\Atomic\TNonspecificLiteralString]);
                    } elseif ($all_lowercase) {
                        $result_type = Type::getLowercaseString();
                    } else {
                        $result_type = Type::getString();
                    }
                }
            }
        }
    }

    private static function analyzeOperand(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $operand,
        Type\Union $operand_type,
        string $side,
        Context $context
    ): void {
        $codebase = $statements_analyzer->getCodebase();
        $config = Config::getInstance();

        if ($operand_type->isNull()) {
            if (IssueBuffer::accepts(
                new NullOperand(
                    'Cannot concatenate with a ' . $operand_type,
                    new CodeLocation($statements_analyzer->getSource(), $operand)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // fall through
            }

            return;
        }

        if ($operand_type->isFalse()) {
            if (IssueBuffer::accepts(
                new FalseOperand(
                    'Cannot concatenate with a ' . $operand_type,
                    new CodeLocation($statements_analyzer->getSource(), $operand)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // fall through
            }

            return;
        }

        if ($operand_type->isNullable() && !$operand_type->ignore_nullable_issues) {
            if (IssueBuffer::accepts(
                new PossiblyNullOperand(
                    'Cannot concatenate with a possibly null ' . $operand_type,
                    new CodeLocation($statements_analyzer->getSource(), $operand)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // fall through
            }
        }

        if ($operand_type->isFalsable() && !$operand_type->ignore_falsable_issues) {
            if (IssueBuffer::accepts(
                new PossiblyFalseOperand(
                    'Cannot concatenate with a possibly false ' . $operand_type,
                    new CodeLocation($statements_analyzer->getSource(), $operand)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // fall through
            }
        }

        $operand_type_match = true;
        $has_valid_operand = false;
        $comparison_result = new \Psalm\Internal\Type\Comparator\TypeComparisonResult();

        foreach ($operand_type->getAtomicTypes() as $operand_type_part) {
            if ($operand_type_part instanceof Type\Atomic\TTemplateParam && !$operand_type_part->as->isString()) {
                if (IssueBuffer::accepts(
                    new MixedOperand(
                        "$side operand cannot be a non-string template param",
                        new CodeLocation($statements_analyzer->getSource(), $operand)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }

                return;
            }

            if ($operand_type_part instanceof Type\Atomic\TNull || $operand_type_part instanceof Type\Atomic\TFalse) {
                continue;
            }

            $operand_type_part_match = AtomicTypeComparator::isContainedBy(
                $codebase,
                $operand_type_part,
                new Type\Atomic\TString,
                false,
                false,
                $comparison_result
            );

            $operand_type_match = $operand_type_match && $operand_type_part_match;

            $has_valid_operand = $has_valid_operand || $operand_type_part_match;

            if ($comparison_result->to_string_cast && $config->strict_binary_operands) {
                if (IssueBuffer::accepts(
                    new ImplicitToStringCast(
                        "$side side of concat op expects string, '$operand_type' provided with a __toString method",
                        new CodeLocation($statements_analyzer->getSource(), $operand)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            foreach ($operand_type->getAtomicTypes() as $atomic_type) {
                if ($atomic_type instanceof TNamedObject) {
                    $to_string_method_id = new \Psalm\Internal\MethodIdentifier(
                        $atomic_type->value,
                        '__tostring'
                    );

                    if ($codebase->methods->methodExists(
                        $to_string_method_id,
                        $context->calling_method_id,
                        $codebase->collect_locations
                            ? new CodeLocation($statements_analyzer->getSource(), $operand)
                            : null,
                        !$context->collect_initializations
                            && !$context->collect_mutations
                            ? $statements_analyzer
                            : null,
                        $statements_analyzer->getFilePath()
                    )) {
                        try {
                            $storage = $codebase->methods->getStorage($to_string_method_id);
                        } catch (\UnexpectedValueException $e) {
                            continue;
                        }

                        if ($context->mutation_free && !$storage->mutation_free) {
                            if (IssueBuffer::accepts(
                                new ImpureMethodCall(
                                    'Cannot call a possibly-mutating method '
                                        . $atomic_type->value . '::__toString from a pure context',
                                    new CodeLocation($statements_analyzer, $operand)
                                ),
                                $statements_analyzer->getSuppressedIssues()
                            )) {
                                // fall through
                            }
                        } elseif ($statements_analyzer->getSource()
                                instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer
                            && $statements_analyzer->getSource()->track_mutations
                        ) {
                            $statements_analyzer->getSource()->inferred_has_mutation = true;
                            $statements_analyzer->getSource()->inferred_impure = true;
                        }
                    }
                }
            }
        }

        if (!$operand_type_match
            && (!$comparison_result->scalar_type_match_found || $config->strict_binary_operands)
        ) {
            if ($has_valid_operand) {
                if (IssueBuffer::accepts(
                    new PossiblyInvalidOperand(
                        'Cannot concatenate with a ' . $operand_type,
                        new CodeLocation($statements_analyzer->getSource(), $operand)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            } else {
                if (IssueBuffer::accepts(
                    new InvalidOperand(
                        'Cannot concatenate with a ' . $operand_type,
                        new CodeLocation($statements_analyzer->getSource(), $operand)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            }
        }
    }
}
