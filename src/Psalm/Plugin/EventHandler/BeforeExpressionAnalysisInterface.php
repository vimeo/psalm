<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler;

use Psalm\Plugin\EventHandler\Event\BeforeExpressionAnalysisEvent;

interface BeforeExpressionAnalysisInterface
{
    /**
     * Called before an expression is checked
     */
    public static function beforeExpressionAnalysis(BeforeExpressionAnalysisEvent $event): ?bool;
}
