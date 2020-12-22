<?php
namespace Psalm\Plugin\Hook;

use Psalm\Plugin\Hook\Event\AfterExpressionAnalysisEvent;

interface AfterExpressionAnalysisInterface
{
    /**
     * Called after an expression has been checked
     *
     * @return null|false
     */
    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool;
}
