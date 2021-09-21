<?php
namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Plugin\EventHandler\Event\AddRemoveTaintsEvent;
use Psalm\Type;

class EncapsulatedStringAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Scalar\Encapsed $stmt,
        Context $context
    ) : bool {
        $stmt_type = Type::getString();

        $non_empty = false;

        $all_literals = true;

        foreach ($stmt->parts as $part) {
            if ($part instanceof PhpParser\Node\Scalar\EncapsedStringPart
                && $part->value
            ) {
                $non_empty = true;
            }

            if (ExpressionAnalyzer::analyze($statements_analyzer, $part, $context) === false) {
                return false;
            }

            $part_type = $statements_analyzer->node_data->getType($part);

            if ($part_type) {
                $casted_part_type = CastAnalyzer::castStringAttempt(
                    $statements_analyzer,
                    $context,
                    $part_type,
                    $part
                );

                if (!$casted_part_type->allLiterals()) {
                    $all_literals = false;
                }

                if ($statements_analyzer->data_flow_graph
                    && !\in_array('TaintedInput', $statements_analyzer->getSuppressedIssues())
                ) {
                    $var_location = new CodeLocation($statements_analyzer, $part);

                    $new_parent_node = DataFlowNode::getForAssignment('concat', $var_location);
                    $statements_analyzer->data_flow_graph->addNode($new_parent_node);

                    $stmt_type->parent_nodes[$new_parent_node->id] = $new_parent_node;

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
                                $removed_taints
                            );
                        }
                    }
                }
            }
        }

        if ($non_empty) {
            if ($all_literals) {
                $new_type = new Type\Union([new Type\Atomic\TNonEmptyNonspecificLiteralString()]);
            } else {
                $new_type = new Type\Union([new Type\Atomic\TNonEmptyString()]);
            }

            $new_type->parent_nodes = $stmt_type->parent_nodes;
            $stmt_type = $new_type;
        }

        $statements_analyzer->node_data->setType($stmt, $stmt_type);

        return true;
    }
}
