<?php
namespace Psalm\Plugin\Hook;

use Psalm\Plugin\Hook\Event\AfterFunctionLikeAnalysisEvent;

interface AfterFunctionLikeAnalysisInterface
{
    /**
     * Called after a statement has been checked
     *
     * @return null|false
     */
    public static function afterStatementAnalysis(AfterFunctionLikeAnalysisEvent $event): ?bool;
}
