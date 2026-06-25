<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use PhpParser\Node\Expr;
use PhpParser\Node\InterpolatedStringPart;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\TaintFlowGraph;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\DataFlow\TaintSource;
use Psalm\Plugin\EventHandler\Event\AddRemoveTaintsEvent;
use Psalm\Type;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TNonEmptyNonspecificLiteralString;
use Psalm\Type\Atomic\TNonEmptyString;
use Psalm\Type\Atomic\TNonspecificLiteralString;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Union;

use function array_diff;
use function in_array;

/**
 * @internal
 */
final class EncapsulatedStringAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Scalar\InterpolatedString $stmt,
        Context $context,
    ): bool {
        $parent_nodes = [];
        $non_empty = false;
        $all_literals = true;
        $literal_string = "";
        $impossible = false;

        foreach ($stmt->parts as $part) {
            if ($part instanceof Expr) {
                $was_inside_general_use = $context->inside_general_use;
                $context->inside_general_use = true;
                if (ExpressionAnalyzer::analyze($statements_analyzer, $part, $context) === false) {
                    $context->inside_general_use = $was_inside_general_use;
                    return false;
                }

                $context->inside_general_use = $was_inside_general_use;
            }

            if ($part instanceof InterpolatedStringPart) {
                if ($literal_string !== null) {
                    $literal_string .= $part->value;
                }

                $non_empty = $non_empty || $part->value !== "";
            } elseif ($part_type = $statements_analyzer->node_data->getType($part)) {
                $casted_part_type = CastAnalyzer::castStringAttempt(
                    $statements_analyzer,
                    $context,
                    $part_type,
                    $part,
                );

                if ($casted_part_type->isNever()) {
                    $impossible = true;

                    continue;
                }


                $is_non_empty_part = true;
                $part_is_all_literals = true;
                $part_literal_string = null;
                $could_specify_literals = true;
                foreach ($casted_part_type->getAtomicTypes() as $casted_part_atomic) {
                    if (!$casted_part_atomic instanceof TString) {
                        $part_is_all_literals = false;
                        $could_specify_literals = false;
                        continue;
                    }

                    if (!$casted_part_atomic instanceof TNonEmptyString
                        && !$casted_part_atomic instanceof TNonEmptyNonspecificLiteralString
                        && !($casted_part_atomic instanceof TLiteralString && $casted_part_atomic->value !== '')
                    ) {
                        $is_non_empty_part = false;
                    }

                    if (!$part_is_all_literals || !$could_specify_literals) {
                        continue;
                    }

                    if ($casted_part_atomic instanceof TLiteralString) {
                        if ($part_literal_string === null) {
                            $part_literal_string = $casted_part_atomic->value;
                        } elseif ($part_literal_string !== $casted_part_atomic->value) {
                            $part_literal_string = null;
                            $could_specify_literals = false;
                        }

                        continue;
                    }

                    if ($casted_part_atomic instanceof TNonspecificLiteralString) {
                        $literal_string = null;
                        $part_literal_string = null;
                        $could_specify_literals = false;

                        continue;
                    }

                    $part_is_all_literals = false;
                }

                $non_empty = $non_empty || $is_non_empty_part;
                $all_literals = $all_literals && $part_is_all_literals;
                if (!$part_is_all_literals || !$could_specify_literals) {
                    $literal_string = null;
                } elseif ($part_literal_string !== null && $literal_string !== null) {
                    $literal_string .= $part_literal_string;
                } else {
                    $literal_string = null;
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

                    $taints = array_diff($added_taints, $removed_taints);
                    if ($taints !== [] && $statements_analyzer->data_flow_graph instanceof TaintFlowGraph) {
                        $taint_source = TaintSource::fromNode($new_parent_node);
                        $taint_source->taints = $taints;
                        $statements_analyzer->data_flow_graph->addSource($taint_source);
                    }

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
                $literal_string = null;
            }
        }

        if ($impossible) {
            $resulting_string = new Type\Atomic\TNever();
        } elseif ($literal_string !== null) {
            $resulting_string = Type::getAtomicStringFromLiteral($literal_string);
        } elseif ($non_empty && $all_literals) {
            $resulting_string = new TNonEmptyNonspecificLiteralString();
        } elseif ($non_empty) {
            $resulting_string = new TNonEmptyString();
        } elseif ($all_literals) {
            $resulting_string = new TNonspecificLiteralString();
        } else {
            $resulting_string = new TString();
        }

        $resulting_type = new Union(
            [$resulting_string],
            ['parent_nodes' => $parent_nodes],
        );

        $statements_analyzer->node_data->setType($stmt, $resulting_type);

        return true;
    }
}
