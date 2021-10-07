<?php

namespace Psalm\Internal\Analyzer\Statements\Block\IfElse;

use Psalm\CodeLocation;
use Psalm\Config;
use Psalm\IssueBuffer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Issue\PossiblyInvalidCast;

trait StrictBoolConditionTrait
{
    private static function verifyStrictBoolCondition(Config $config, StatementsAnalyzer $statements_analyzer, \PhpParser\Node\Expr $cond): void
    {
        if (!$config->strict_bool_conditions) {
            return;
        }

        $type = $statements_analyzer->node_data->getType($cond);
        if ($type === null) {
            return;
        }

        if (!$type->isBool()) {
            if (IssueBuffer::accepts(
                new PossiblyInvalidCast(
                    'Type of if-condition should be bool, not ' . $type->getId(),
                    new CodeLocation($statements_analyzer, $cond)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // fall through
            }
        }
    }
}