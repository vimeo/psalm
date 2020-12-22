<?php
namespace Psalm\Plugin\Hook;

use Psalm\Plugin\Hook\Event\AfterStatementAnalysisEvent;

interface AfterStatementAnalysisInterface
{
    /**
     * Called after a statement has been checked
     *
     * @return null|false
     */
    public static function afterStatementAnalysis(AfterStatementAnalysisEvent $event): ?bool;
}
