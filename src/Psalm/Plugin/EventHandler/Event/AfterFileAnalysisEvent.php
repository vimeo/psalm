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
        private readonly StatementsSource $statements_source,
        private readonly Context $file_context,
        private readonly FileStorage $file_storage,
        private readonly Codebase $codebase,
        private readonly array $stmts,
    ) {
    }

    public function getStatementsSource(): StatementsSource
    {
        return $this->statements_source;
    }

    public function getFileContext(): Context
    {
        return $this->file_context;
    }

    public function getFileStorage(): FileStorage
    {
        return $this->file_storage;
    }

    public function getCodebase(): Codebase
    {
        return $this->codebase;
    }

    /**
     * @return Stmt[]
     */
    public function getStmts(): array
    {
        return $this->stmts;
    }
}
