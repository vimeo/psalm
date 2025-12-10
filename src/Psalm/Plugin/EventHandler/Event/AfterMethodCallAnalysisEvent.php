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
        public readonly MethodCall|StaticCall $expr,
        public readonly string $method_id,
        public readonly string $appearing_method_id,
        public readonly string $declaring_method_id,
        public readonly Context $context,
        public readonly StatementsSource $statements_source,
        public readonly Codebase $codebase,
        public array $file_replacements = [],
        public ?Union $return_type_candidate = null,
    ) {
    }
}