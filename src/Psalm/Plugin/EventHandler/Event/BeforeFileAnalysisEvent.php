<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler\Event;

use Psalm\Codebase;
use Psalm\Context;
use Psalm\StatementsSource;
use Psalm\Storage\FileStorage;

final class BeforeFileAnalysisEvent
{
    /**
     * Called before a file has been checked
     *
     * @internal
     */
    public function __construct(
        private readonly StatementsSource $statements_source,
        private readonly Context $file_context,
        private readonly FileStorage $file_storage,
        private readonly Codebase $codebase,
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
}
