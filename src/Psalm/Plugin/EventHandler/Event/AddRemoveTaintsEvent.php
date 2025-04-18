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
        private readonly ArrayItem|Expr $expr,
        private readonly Context $context,
        private readonly StatementsSource $statements_source,
        private readonly Codebase $codebase,
    ) {
    }

    public function getExpr(): ArrayItem|Expr
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
}
