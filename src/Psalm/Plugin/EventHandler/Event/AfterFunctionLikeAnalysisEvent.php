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
        public readonly Node\FunctionLike $stmt,
        public readonly FunctionLikeStorage $functionlike_storage,
        public readonly StatementsSource $statements_source,
        public readonly Codebase $codebase,
        public array $file_replacements,
        public readonly NodeTypeProvider $node_type_provider,
        public readonly Context $context,
    ) {
    }
}