<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler\Event;

use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\StatementsSource;

final class AddRemoveTaintsEvent
{
    /**
     * Called after an expression has been checked
     *
     * @internal
     */
    public function __construct(
        public readonly ArrayItem|Expr $expr,
        public readonly Context $context,
        public readonly StatementsSource $statements_source,
        public readonly Codebase $codebase,
    ) {
    }
}
