<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler\Event;

use PhpParser\Node\Stmt;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\StatementsSource;
use Psalm\Storage\FileStorage;

/**
 * @psalm-external-mutation-free
 * @api
 */
final class BeforeFileAnalysisEvent
{
    /**
     * Called before a file has been checked
     *
     * @param list<Stmt> $stmts
     * @internal
     * @psalm-mutation-free
     */
    public function __construct(
        private readonly StatementsSource $statements_source,
        private readonly Context $file_context,
        private readonly FileStorage $file_storage,
        private readonly Codebase $codebase,
        private readonly array $stmts,
        private bool $cli_only = false,
    ) {
    }

    /**
     * @psalm-mutation-free
     */
    public function getStatementsSource(): StatementsSource
    {
        return $this->statements_source;
    }

    /**
     * @psalm-mutation-free
     */
    public function getFileContext(): Context
    {
        return $this->file_context;
    }

    /**
     * @psalm-mutation-free
     */
    public function getFileStorage(): FileStorage
    {
        return $this->file_storage;
    }

    /**
     * @psalm-mutation-free
     */
    public function getCodebase(): Codebase
    {
        return $this->codebase;
    }

    /**
     * @return list<Stmt>
     * @psalm-mutation-free
     */
    public function getStmts(): array
    {
        return $this->stmts;
    }

    /**
     * Whether the top-level code of the file, and the closures in it, only run from the command line: a plugin marked
     * the file, or it is included from such code
     *
     * @psalm-mutation-free
     */
    public function isFileCliOnly(): bool
    {
        return $this->cli_only;
    }

    /**
     * Says that the top-level code of the file, and the closures in it, only run from the command line (e.g. a
     * framework's migrations), like code marked `@psalm-cli-only`. The functions and classes it declares are not.
     *
     * @psalm-external-mutation-free
     */
    public function markFileAsCliOnly(): void
    {
        $this->cli_only = true;
    }
}
