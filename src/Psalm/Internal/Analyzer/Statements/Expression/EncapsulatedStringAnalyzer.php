<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use PhpParser\Node\Expr;
use PhpParser\Node\InterpolatedStringPart;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\Expression\BinaryOp\ConcatAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\VariableUseGraph;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Plugin\EventHandler\Event\AddRemoveTaintsEvent;
use Psalm\Type;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TNonEmptyNonspecificLiteralString;
use Psalm\Type\Atomic\TNonEmptyString;
use Psalm\Type\Atomic\TNonspecificLiteralString;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Union;

use function count;

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
        $has_never_part = false;

        $all_literals = true;

        $literal_string = "";

        // the literal strings the parts so far can start with (see ConcatAnalyzer::getLiteralPrefixes()), and whether
        // they are all literals, so what the next part starts with follows them
        $literal_prefixes = [''];
        $literal_prefixes_extendable = true;

        $single_part_type = null;

        foreach ($stmt->parts as $part) {
            if ($part instanceof Expr) {
                if (ExpressionAnalyzer::analyze($statements_analyzer, $part, $context) === false) {
                    return false;
                }
            }

            if ($part instanceof InterpolatedStringPart) {
                if ($literal_string !== null) {
                    $literal_string .= $part->value;
                }
                if ($literal_prefixes_extendable) {
                    $literal_prefixes = ConcatAnalyzer::concatLiterals($literal_prefixes, [$part->value])
                        ?? $literal_prefixes;
                }
                $non_empty = $non_empty || $part->value !== "";
            } elseif ($part_type = $statements_analyzer->node_data->getType($part)) {
                $casted_part_type = CastAnalyzer::castStringAttempt(
                    $statements_analyzer,
                    $context,
                    $part_type,
                    $part,
                );

                if (!$casted_part_type->allLiterals()) {
                    $all_literals = false;
                }

                $non_empty = $non_empty || self::isNonEmpty($casted_part_type);
                $has_never_part = $has_never_part || $casted_part_type->isNever();

                if ($literal_string !== null) {
                    if ($casted_part_type->isSingleLiteral()) {
                        $literal_string .= $casted_part_type->getSingleLiteral()->value;
                    } else {
                        $literal_string = null;
                    }
                }

                if ($graph = $statements_analyzer->getDataFlowGraphWithSuppressed()) {
                    $var_location = new CodeLocation($statements_analyzer, $part);

                    $new_parent_node = DataFlowNode::getForAssignment('concat', $var_location);
                    $graph->addNode($new_parent_node);

                    $parent_nodes[$new_parent_node->id] = $new_parent_node;

                    $codebase = $statements_analyzer->getCodebase();
                    $event = new AddRemoveTaintsEvent($stmt, $context, $statements_analyzer, $codebase);

                    $added_taints = $codebase->config->eventDispatcher->dispatchAddTaints($event);
                    $removed_taints = $codebase->config->eventDispatcher->dispatchRemoveTaints($event);

                    $taints = $added_taints & ~$removed_taints;
                    if ($taints !== 0 && !$graph instanceof VariableUseGraph) {
                        $taint_source = $new_parent_node->setTaints($taints);
                        $graph->addSource($taint_source);
                    }

                    if ($casted_part_type->parent_nodes) {
                        // after the start of a URL fixing its server, the part can't choose it
                        $removed_taints |= ConcatAnalyzer::getTaintsRemovedAfterUrlOrigins($literal_prefixes);

                        foreach ($casted_part_type->parent_nodes as $parent_node) {
                            $graph->addPath(
                                $parent_node,
                                $new_parent_node,
                                'concat',
                                $added_taints,
                                $removed_taints,
                            );
                        }
                    }
                }

                if ($literal_prefixes_extendable) {
                    $extended_prefixes = $casted_part_type->allStringLiterals()
                        ? ConcatAnalyzer::concatLiterals(
                            $literal_prefixes,
                            ConcatAnalyzer::getLiteralValues($casted_part_type),
                        )
                        : null;

                    $literal_prefixes_extendable = $extended_prefixes !== null;
                    $literal_prefixes = $extended_prefixes ?? $literal_prefixes;
                }

                $single_part_type = $casted_part_type;
            } else {
                $all_literals = false;
                $literal_string = null;
                $literal_prefixes_extendable = false;
            }
        }

        if ($statements_analyzer->getDataFlowGraphWithSuppressed()) {
            $statements_analyzer->node_data->setLiteralPrefixes($stmt, $literal_prefixes);
        }

        if ($has_never_part) {
            $stmt_type = Type::getNever();
        } elseif (count($stmt->parts) === 1 && $single_part_type !== null) {
            // e.g. "$int" is the same string as (string) $int
            $stmt_type = $single_part_type->setParentNodes($parent_nodes);
        } elseif ($literal_string !== null) {
            $stmt_type = new Union(
                [Type::getAtomicStringFromLiteral($literal_string)],
                ['parent_nodes' => $parent_nodes],
            );
        } elseif ($non_empty) {
            $stmt_type = new Union(
                [$all_literals ? new TNonEmptyNonspecificLiteralString() : new TNonEmptyString()],
                ['parent_nodes' => $parent_nodes],
            );
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
     * @psalm-pure
     */
    private static function isNonEmpty(Union $casted_part_type): bool
    {
        foreach ($casted_part_type->getAtomicTypes() as $atomic) {
            if (!$atomic instanceof TNonEmptyString
                && !$atomic instanceof TNonEmptyNonspecificLiteralString
                && !($atomic instanceof TLiteralString && $atomic->value !== '')
            ) {
                return false;
            }
        }

        return true;
    }
}
