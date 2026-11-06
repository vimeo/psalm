<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler\Event;

use PhpParser\Node\Stmt;
use Psalm\Codebase;

/**
 * @psalm-external-mutation-free
 * @api
 */
final class AfterFileParsedEvent
{
    /**
     * The statements are the handler's to change: they are never those Psalm caches
     *
     * @param list<Stmt> $stmts
     * @internal
     * @psalm-mutation-free
     */
    public function __construct(
        private readonly string $file_path,
        private array $stmts,
        private readonly Codebase $codebase,
    ) {
    }

    /**
     * @psalm-mutation-free
     */
    public function getFilePath(): string
    {
        return $this->file_path;
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
     * @param list<Stmt> $stmts
     * @psalm-external-mutation-free
     */
    public function setStmts(array $stmts): void
    {
        $this->stmts = $stmts;
    }

    /**
     * @psalm-mutation-free
     */
    public function getCodebase(): Codebase
    {
        return $this->codebase;
    }
}
