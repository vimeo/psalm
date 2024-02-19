<?php

namespace Psalm\Plugin\EventHandler\Event;

use PhpParser\Node\Stmt;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\StatementsSource;
use Psalm\Storage\FileStorage;

final class BeforeFileAnalysisEvent
{
    private StatementsSource $statements_source;
    private Context $file_context;
    private FileStorage $file_storage;
    private Codebase $codebase;
    /**
     * @var list<Stmt>
     */
    private array $stmts;

    /**
     * Called before a file has been checked
     *
     * @param list<Stmt> $stmts
     * @internal
     */
    public function __construct(
        StatementsSource $statements_source,
        Context $file_context,
        FileStorage $file_storage,
        Codebase $codebase,
        array $stmts
    ) {
        $this->statements_source = $statements_source;
        $this->file_context = $file_context;
        $this->file_storage = $file_storage;
        $this->codebase = $codebase;
        $this->stmts = $stmts;
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
     * @return list<Stmt>
     */
    public function getStmts(): array
    {
        return $this->stmts;
    }
}
