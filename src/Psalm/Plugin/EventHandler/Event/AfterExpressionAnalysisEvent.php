<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler\Event;

use PhpParser\Node\Expr;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\FileManipulation;
use Psalm\StatementsSource;

final class AfterExpressionAnalysisEvent
{
    /**
     * Called after an expression has been checked
     *
     * @param  FileManipulation[]   $file_replacements
     * @internal
     */
    public function __construct(
        private readonly Expr $expr,
        private readonly Context $context,
        private readonly StatementsSource $statements_source,
        private readonly Codebase $codebase,
        private array $file_replacements = [],
    ) {
    }

    public function getExpr(): Expr
    {
        return $this->expr;
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
