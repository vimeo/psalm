<?php

use Psalm\Plugin\EventHandler\Event\AfterFunctionCallAnalysisEvent;

class BasePlugin implements Psalm\Plugin\EventHandler\AfterFunctionCallAnalysisInterface
{
    public static function afterFunctionCallAnalysis(AfterFunctionCallAnalysisEvent $event): void {
    }
}
