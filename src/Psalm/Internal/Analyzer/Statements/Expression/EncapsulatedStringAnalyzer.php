<?php
namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Type;

class EncapsulatedStringAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Scalar\Encapsed $stmt,
        Context $context
    ) : bool {
        $codebase = $statements_analyzer->getCodebase();

        $stmt_type = Type::getString();

        foreach ($stmt->parts as $part) {
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

                if ($codebase->taint
                    && $codebase->config->trackTaintsInPath($statements_analyzer->getFilePath())
                    && !\in_array('TaintedInput', $statements_analyzer->getSuppressedIssues())
                ) {
                    $var_location = new CodeLocation($statements_analyzer, $part);

                    $new_parent_node = \Psalm\Internal\Taint\TaintNode::getForAssignment('concat', $var_location);
                    $codebase->taint->addTaintNode($new_parent_node);

                    $stmt_type->parent_nodes[] = $new_parent_node;

                    if ($casted_part_type->parent_nodes) {
                        foreach ($casted_part_type->parent_nodes as $parent_node) {
                            $codebase->taint->addPath($parent_node, $new_parent_node, 'concat');
                        }
                    }
                }
            }
        }

        $statements_analyzer->node_data->setType($stmt, $stmt_type);

        return true;
    }
}
