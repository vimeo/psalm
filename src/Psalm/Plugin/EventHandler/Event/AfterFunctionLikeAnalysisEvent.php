<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler\Event;

use PhpParser\Node;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\FileManipulation;
use Psalm\NodeTypeProvider;
use Psalm\StatementsSource;
use Psalm\Storage\FunctionLikeStorage;

final class AfterFunctionLikeAnalysisEvent
{
    /**
     * Called after a statement has been checked
     *
     * @param  FileManipulation[]   $file_replacements
     * @internal
     */
    public function __construct(
        private readonly Node\FunctionLike $stmt,
        private readonly FunctionLikeStorage $functionlike_storage,
        private readonly StatementsSource $statements_source,
        private readonly Codebase $codebase,
        private array $file_replacements,
        private readonly NodeTypeProvider $node_type_provider,
        private readonly Context $context,
    ) {
    }

    public function getStmt(): Node\FunctionLike
    {
        return $this->stmt;
    }

    public function getFunctionlikeStorage(): FunctionLikeStorage
    {
        return $this->functionlike_storage;
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
     * @return FileManipulation[]
     */
    public function getFileReplacements(): array
    {
        return $this->file_replacements;
    }

    /**
     * @param FileManipulation[] $file_replacements
     */
    public function setFileReplacements(array $file_replacements): void
    {
        $this->file_replacements = $file_replacements;
    }

    public function getNodeTypeProvider(): NodeTypeProvider
    {
        return $this->node_type_provider;
    }

    public function getContext(): Context
    {
        return $this->context;
    }
}
