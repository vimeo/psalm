<?php

namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use PhpParser\Node\Scalar\EncapsedStringPart;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Issue\MixedOperand;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\Event\AddRemoveTaintsEvent;
use Psalm\Type;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TNonEmptyNonspecificLiteralString;
use Psalm\Type\Atomic\TNonEmptyString;
use Psalm\Type\Atomic\TNonFalsyString;
use Psalm\Type\Atomic\TNonspecificLiteralInt;
use Psalm\Type\Atomic\TNonspecificLiteralString;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Union;

use function array_map;
use function array_merge;
use function array_unique;
use function count;
use function in_array;

/**
 * @internal
 */
final class EncapsulatedStringAnalyzer
{
    private const MAX_LITERALS = 500;

    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Scalar\Encapsed $stmt,
        Context $context
    ): bool {
        $parent_nodes = [];

        $non_falsy = false;
        $non_empty = false;

        $all_literals = true;

        $literal_strings = [];

        foreach ($stmt->parts as $part) {
            if (ExpressionAnalyzer::analyze($statements_analyzer, $part, $context) === false) {
                return false;
            }

            if ($part instanceof EncapsedStringPart) {
                if ($literal_strings !== null) {
                    $literal_strings = self::combineLiteral($literal_strings, $part->value);
                }
                $non_falsy = $non_falsy || $part->value;
                $non_empty = $non_empty || $part->value !== "";
            } elseif ($part_type = $statements_analyzer->node_data->getType($part)) {
                if ($part_type->hasMixed()) {
                    IssueBuffer::maybeAdd(
                        new MixedOperand(
                            'Operands cannot be mixed',
                            new CodeLocation($statements_analyzer, $part),
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                }

                $casted_part_type = CastAnalyzer::castStringAttempt(
                    $statements_analyzer,
                    $context,
                    $part_type,
                    $part,
                );

                if (!$casted_part_type->allLiterals()) {
                    $all_literals = false;
                }

                if (!$non_falsy) {
                    // Check if all literals are nonempty
                    $possibly_non_empty = true;
                    $non_falsy = true;
                    foreach ($casted_part_type->getAtomicTypes() as $atomic_literal) {
                        if (($atomic_literal instanceof TLiteralInt && $atomic_literal->value === 0)
                            || ($atomic_literal instanceof TLiteralFloat && $atomic_literal->value === (float) 0)
                            || ($atomic_literal instanceof TLiteralString && $atomic_literal->value === "0")
                        ) {
                            $non_falsy = false;

                            if ($non_empty) {
                                break;
                            }
                        }

                        if (!$atomic_literal instanceof TLiteralInt
                            && !$atomic_literal instanceof TNonspecificLiteralInt
                            && !$atomic_literal instanceof TLiteralFloat
                            && !$atomic_literal instanceof TNonEmptyNonspecificLiteralString
                            && !($atomic_literal instanceof TLiteralString && $atomic_literal->value !== "")
                        ) {
                            if (!$atomic_literal instanceof TNonFalsyString) {
                                $non_falsy = false;
                            }

                            if (!$atomic_literal instanceof TNonEmptyString) {
                                $possibly_non_empty = false;
                                $non_falsy = false;

                                break;
                            }
                        }
                    }

                    if ($possibly_non_empty) {
                        $non_empty = true;
                    }
                }

                if ($literal_strings !== null) {
                    if ($casted_part_type->allSpecificLiterals()) {
                        $new_literal_strings = [];
                        foreach ($casted_part_type->getLiteralStrings() as $literal_string_atomic) {
                            $new_literal_strings = array_merge(
                                $new_literal_strings,
                                self::combineLiteral($literal_strings, $literal_string_atomic->value),
                            );
                        }

                        $literal_strings = array_unique($new_literal_strings);
                        if (count($literal_strings) > self::MAX_LITERALS) {
                            $literal_strings = null;
                        }
                    } else {
                        $literal_strings = null;
                    }
                }

                if ($statements_analyzer->data_flow_graph
                    && !in_array('TaintedInput', $statements_analyzer->getSuppressedIssues())
                ) {
                    $var_location = new CodeLocation($statements_analyzer, $part);

                    $new_parent_node = DataFlowNode::getForAssignment('concat', $var_location);
                    $statements_analyzer->data_flow_graph->addNode($new_parent_node);

                    $parent_nodes[$new_parent_node->id] = $new_parent_node;

                    $codebase = $statements_analyzer->getCodebase();
                    $event = new AddRemoveTaintsEvent($stmt, $context, $statements_analyzer, $codebase);

                    $added_taints = $codebase->config->eventDispatcher->dispatchAddTaints($event);
                    $removed_taints = $codebase->config->eventDispatcher->dispatchRemoveTaints($event);

                    if ($casted_part_type->parent_nodes) {
                        foreach ($casted_part_type->parent_nodes as $parent_node) {
                            $statements_analyzer->data_flow_graph->addPath(
                                $parent_node,
                                $new_parent_node,
                                'concat',
                                $added_taints,
                                $removed_taints,
                            );
                        }
                    }
                }
            } else {
                $all_literals = false;
                $literal_strings = null;
            }
        }

        if ($non_empty || $non_falsy) {
            if ($literal_strings !== null && $literal_strings !== []) {
                $stmt_type = new Union(
                    array_map([Type::class, 'getAtomicStringFromLiteral'], $literal_strings),
                    ['parent_nodes' => $parent_nodes],
                );
            } elseif ($all_literals) {
                $stmt_type = new Union(
                    [new TNonEmptyNonspecificLiteralString()],
                    ['parent_nodes' => $parent_nodes],
                );
            } elseif ($non_falsy) {
                $stmt_type = new Union(
                    [new TNonFalsyString()],
                    ['parent_nodes' => $parent_nodes],
                );
            } else {
                $stmt_type = new Union(
                    [new TNonEmptyString()],
                    ['parent_nodes' => $parent_nodes],
                );
            }
        } elseif ($all_literals) {
            $stmt_type = new Union(
                [new TNonspecificLiteralString()],
                ['parent_nodes' => $parent_nodes],
            );
        } else {
            $stmt_type = new Union(
                [new TString()],
                ['parent_nodes' => $parent_nodes],
            );
        }

        $statements_analyzer->node_data->setType($stmt, $stmt_type);

        return true;
    }

    /**
     * @param string[] $literal_strings
     * @return non-empty-array<string>
     */
    private static function combineLiteral(
        array $literal_strings,
        string $append
    ): array {
        if ($literal_strings === []) {
            return [$append];
        }

        if ($append === '') {
            return $literal_strings;
        }

        $new_literal_strings = array();
        foreach ($literal_strings as $literal_string) {
            $new_literal_strings[] = "{$literal_string}{$append}";
        }

        return $new_literal_strings;
    }
}
