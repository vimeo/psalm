<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler\Event;

use PhpParser\Node\Expr;
use PhpParser\Node\InterpolatedStringPart;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\FileManipulation;
use Psalm\StatementsSource;

final class BeforeExpressionAnalysisEvent
{
    private Expr|InterpolatedStringPart $expr;
    private Context $context;
    private StatementsSource $statements_source;
    private Codebase $codebase;
    /**
     * @var list<FileManipulation>
     */
    private array $file_replacements;

    /**
     * Called before an expression is checked
     *
     * @param  list<FileManipulation> $file_replacements
     * @internal
     */
    public function __construct(
        Expr|InterpolatedStringPart $expr,
        Context $context,
        StatementsSource $statements_source,
        Codebase $codebase,
        array $file_replacements = [],
    ) {
        $this->expr = $expr;
        $this->context = $context;
        $this->statements_source = $statements_source;
        $this->codebase = $codebase;
        $this->file_replacements = $file_replacements;
    }

    public function getExpr(): Expr|InterpolatedStringPart
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
