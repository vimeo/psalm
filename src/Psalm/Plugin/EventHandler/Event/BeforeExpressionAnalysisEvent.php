<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler\Event;

use PhpParser\Node\Expr;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\FileManipulation;
use Psalm\StatementsSource;

final class BeforeExpressionAnalysisEvent
{
    /**
     * Called before an expression is checked
     *
     * @param  list<FileManipulation> $file_replacements
     * @internal
     */
    public function __construct(
        public readonly Expr $expr,
        public readonly Context $context,
        public readonly StatementsSource $statements_source,
        public readonly Codebase $codebase,
        public array $file_replacements = [],
    ) {
    }
}