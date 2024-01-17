<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler\Event;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\FileManipulation;
use Psalm\StatementsSource;
use Psalm\Type\Union;

final class AfterMethodCallAnalysisEvent
{
    /**
     * @param  FileManipulation[] $file_replacements
     * @internal
     */
    public function __construct(
        private readonly MethodCall|StaticCall $expr,
        private readonly string $method_id,
        private readonly string $appearing_method_id,
        private readonly string $declaring_method_id,
        private readonly Context $context,
        private readonly StatementsSource $statements_source,
        private readonly Codebase $codebase,
        private array $file_replacements = [],
        private ?Union $return_type_candidate = null,
    ) {
    }

    /**
     * @return MethodCall|StaticCall
     */
    public function getExpr(): Expr
    {
        return $this->expr;
    }

    public function getMethodId(): string
    {
        return $this->method_id;
    }

    public function getAppearingMethodId(): string
    {
        return $this->appearing_method_id;
    }

    public function getDeclaringMethodId(): string
    {
        return $this->declaring_method_id;
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

    public function getReturnTypeCandidate(): ?Union
    {
        return $this->return_type_candidate;
    }

    /**
     * @param FileManipulation[] $file_replacements
     */
    public function setFileReplacements(array $file_replacements): void
    {
        $this->file_replacements = $file_replacements;
    }

    public function setReturnTypeCandidate(?Union $return_type_candidate): void
    {
        $this->return_type_candidate = $return_type_candidate;
    }
}
