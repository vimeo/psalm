<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler\Event;

use PhpParser\Node\Stmt;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\FileManipulation;
use Psalm\StatementsSource;

final class BeforeStatementAnalysisEvent
{
    /**
     * Called after a statement has been checked
     *
     * @param list<FileManipulation> $file_replacements
     * @internal
     */
    public function __construct(
        public Stmt $stmt,
        public readonly Context $context,
        public readonly StatementsSource $statements_source,
        public readonly Codebase $codebase,
        public array $file_replacements = [],
    ) {
    }
}