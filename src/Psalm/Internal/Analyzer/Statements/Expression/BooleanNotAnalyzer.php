<?php
namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Context;
use Psalm\Type;

class BooleanNotAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\BooleanNot $stmt,
        Context $context
    ) : bool {
        $statements_analyzer->node_data->setType($stmt, Type::getBool());

        $inside_negation = $context->inside_negation;

        $context->inside_negation = !$inside_negation;

        $result = ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context);

        $context->inside_negation = $inside_negation;

        return $result;
    }
}
