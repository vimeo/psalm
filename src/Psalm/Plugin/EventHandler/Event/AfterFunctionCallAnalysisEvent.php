<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler\Event;

use PhpParser\Node\Expr\FuncCall;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\FileManipulation;
use Psalm\StatementsSource;
use Psalm\Type\Union;

final class AfterFunctionCallAnalysisEvent
{
    /**
     * @param non-empty-string $function_id
     * @param FileManipulation[] $file_replacements
     * @internal
     */
    public function __construct(
        private readonly FuncCall $expr,
        private readonly string $function_id,
        private readonly Context $context,
        private readonly StatementsSource $statements_source,
        private readonly Codebase $codebase,
        private readonly Union $return_type_candidate,
        private array $file_replacements,
    ) {
    }

    public function getExpr(): FuncCall
    {
        return $this->expr;
    }

    /**
     * @return non-empty-string
     */
    public function getFunctionId(): string
    {
        return $this->function_id;
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

    public function getReturnTypeCandidate(): Union
    {
        return $this->return_type_candidate;
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
}
