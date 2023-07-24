<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler;

use Psalm\Plugin\EventHandler\Event\AfterEveryFunctionCallAnalysisEvent;

interface AfterEveryFunctionCallAnalysisInterface
{
    public static function afterEveryFunctionCallAnalysis(AfterEveryFunctionCallAnalysisEvent $event): void;
}
