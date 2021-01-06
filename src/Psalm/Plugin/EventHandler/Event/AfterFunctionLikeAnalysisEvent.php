<?php


namespace Psalm\Plugin\EventHandler\Event;

use PhpParser\Node;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\FileManipulation;
use Psalm\NodeTypeProvider;
use Psalm\StatementsSource;
use Psalm\Storage\FunctionLikeStorage;

class AfterFunctionLikeAnalysisEvent
{
    /**
     * @var Node\FunctionLike
     */
    private $stmt;
    /**
     * @var FunctionLikeStorage
     */
    private $classlike_storage;
    /**
     * @var StatementsSource
     */
    private $statements_source;
    /**
     * @var Codebase
     */
    private $codebase;
    /**
     * @var FileManipulation[]
     */
    private $file_replacements;
    /**
     * @var NodeTypeProvider
     */
    private $node_type_provider;
    /**
     * @var Context
     */
    private $context;

    /**
     * Called after a statement has been checked
     *
     * @param  FileManipulation[]   $file_replacements
     */
    public function __construct(
        Node\FunctionLike $stmt,
        FunctionLikeStorage $classlike_storage,
        StatementsSource $statements_source,
        Codebase $codebase,
        array $file_replacements,
        NodeTypeProvider $node_type_provider,
        Context $context
    ) {
        $this->stmt = $stmt;
        $this->classlike_storage = $classlike_storage;
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

    public function getClasslikeStorage(): FunctionLikeStorage
    {
        return $this->classlike_storage;
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
