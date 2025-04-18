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
        private Stmt $stmt,
        private readonly Context $context,
        private readonly StatementsSource $statements_source,
        private readonly Codebase $codebase,
        private array $file_replacements = [],
    ) {
    }

    public function getStmt(): Stmt
    {
        return $this->stmt;
    }

    public function setStmt(Stmt $stmt): void
    {
        $this->stmt = $stmt;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getStatementsSource(): StatementsSource
    {
        return $this->statements_source;
    }

    public function getCodebase(): Codebase
    {
        return $this->codebase;
    }

    /**
     * @return list<FileManipulation>
     */
    public function getFileReplacements(): array
    {
        return $this->file_replacements;
    }

    /**
     * @param list<FileManipulation> $file_replacements
     */
    public function setFileReplacements(array $file_replacements): void
    {
        $this->file_replacements = $file_replacements;
    }
}
