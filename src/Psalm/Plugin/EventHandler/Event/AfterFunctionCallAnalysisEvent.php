<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler\Event;

use PhpParser\Node\Expr\FuncCall;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\FileManipulation;
use Psalm\StatementsSource;
use Psalm\Type\Union;

final class AfterFunctionCallAnalysisEvent
{
    /**
     * @param non-empty-string $function_id
     * @param FileManipulation[] $file_replacements
     * @internal
     */
    public function __construct(
        public readonly FuncCall $expr,
        public readonly string $function_id,
        public readonly Context $context,
        public readonly StatementsSource $statements_source,
        public readonly Codebase $codebase,
        public readonly Union $return_type_candidate,
        public array $file_replacements,
    ) {
    }
}
