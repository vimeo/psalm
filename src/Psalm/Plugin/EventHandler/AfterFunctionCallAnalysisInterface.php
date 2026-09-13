<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler;

use Psalm\Plugin\EventHandler\Event\AfterFunctionCallAnalysisEvent;

/**
 * @api
 */
interface AfterFunctionCallAnalysisInterface
{
    public static function afterFunctionCallAnalysis(AfterFunctionCallAnalysisEvent $event): void;
}
