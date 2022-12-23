<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler;

use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;

interface AfterExpressionAnalysisInterface
{
    /**
     * Called after an expression has been checked
     *
     * @return null|false
     */
    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool;
}
