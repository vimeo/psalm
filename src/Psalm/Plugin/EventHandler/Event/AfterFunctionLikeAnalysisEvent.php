<?php

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
    private Node\FunctionLike $stmt;
    private FunctionLikeStorage $functionlike_storage;
    private StatementsSource $statements_source;
    private Codebase $codebase;
    /**
     * @var FileManipulation[]
     */
    private array $file_replacements;
    private NodeTypeProvider $node_type_provider;
    private Context $context;

    /**
     * Called after a statement has been checked
     *
     * @param  FileManipulation[]   $file_replacements
     * @internal
     */
    public function __construct(
        Node\FunctionLike $stmt,
        FunctionLikeStorage $functionlike_storage,
        StatementsSource $statements_source,
        Codebase $codebase,
        array $file_replacements,
        NodeTypeProvider $node_type_provider,
        Context $context
    ) {
        $this->stmt = $stmt;
        $this->functionlike_storage = $functionlike_storage;
        $this->statements_source = $statements_source;
        $this->codebase = $codebase;
        $this->file_replacements = $file_replacements;
        $this->node_type_provider = $node_type_provider;
        $this->context = $context;
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
