<?php

declare(strict_types=1);

namespace Psalm\Plugin;

use PhpParser;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Type;
use Psalm\Type\Union;

final class ArgTypeInferer
{
    /**
     * @internal
     */
    public function __construct(
        private readonly Context $context,
        private readonly StatementsAnalyzer $statements_analyzer,
    ) {
    }

    public function infer(PhpParser\Node\Arg $arg): null|Union
    {
        $already_inferred_type = $this->statements_analyzer->node_data->getType($arg->value);

        if ($already_inferred_type) {
            return $already_inferred_type;
        }

        if (ExpressionAnalyzer::analyze($this->statements_analyzer, $arg->value, $this->context) === false) {
            return null;
        }

        return $this->statements_analyzer->node_data->getType($arg->value) ?? Type::getMixed();
    }
}
