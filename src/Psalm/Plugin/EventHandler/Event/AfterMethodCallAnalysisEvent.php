<?php

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
     * @var MethodCall|StaticCall
     */
    private $expr;
    private string $method_id;
    private string $appearing_method_id;
    private string $declaring_method_id;
    private Context $context;
    private StatementsSource $statements_source;
    private Codebase $codebase;
    /**
     * @var FileManipulation[]
     */
    private array $file_replacements;
    private ?Union $return_type_candidate;

    /**
     * @param  MethodCall|StaticCall $expr
     * @param  FileManipulation[] $file_replacements
     * @internal
     */
    public function __construct(
        Expr $expr,
        string $method_id,
        string $appearing_method_id,
        string $declaring_method_id,
        Context $context,
        StatementsSource $statements_source,
        Codebase $codebase,
        array $file_replacements = [],
        Union $return_type_candidate = null
    ) {
        $this->expr = $expr;
        $this->method_id = $method_id;
        $this->appearing_method_id = $appearing_method_id;
        $this->declaring_method_id = $declaring_method_id;
        $this->context = $context;
        $this->statements_source = $statements_source;
        $this->codebase = $codebase;
        $this->file_replacements = $file_replacements;
        $this->return_type_candidate = $return_type_candidate;
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
