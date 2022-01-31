<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler;

use Psalm\Plugin\EventHandler\Event\BeforeStatementAnalysisEvent;

interface BeforeStatementAnalysisInterface
{
    /**
     * Called before a statement has been checked
     *
     * @return null|false Whether to continue
     *  + `null` continues with next event handler
     *  + `false` stops analyzing current statement in StatementsAnalyzer
     */
    public static function beforeStatementAnalysis(BeforeStatementAnalysisEvent $event): ?bool;
}
