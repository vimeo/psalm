<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler\Event;

use PhpParser\Node\Stmt;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\StatementsSource;
use Psalm\Storage\FileStorage;

final class AfterFileAnalysisEvent
{
    /**
     * Called after a file has been checked
     *
     * @param array<Stmt> $stmts
     * @internal
     */
    public function __construct(
        public readonly StatementsSource $statements_source,
        public readonly Context $file_context,
        public readonly FileStorage $file_storage,
        public readonly Codebase $codebase,
        public readonly array $stmts,
    ) {
    }
}
