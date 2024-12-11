<?php

namespace Psalm\Plugin\EventHandler\Event;

use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\StatementsSource;

final class AddRemoveTaintsEvent
{
    /** @var ArrayItem|Expr */
    private $expr;
    private Context $context;
    private StatementsSource $statements_source;
    private Codebase $codebase;

    /**
     * Called after an expression has been checked
     *
     * @param ArrayItem|Expr $expr
     * @internal
     */
    public function __construct(
        $expr,
        Context $context,
        StatementsSource $statements_source,
        Codebase $codebase
    ) {
        $this->expr = $expr;
        $this->context = $context;
        $this->statements_source = $statements_source;
        $this->codebase = $codebase;
    }

    /**
     * @return ArrayItem|Expr
     */
    public function getExpr()
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
