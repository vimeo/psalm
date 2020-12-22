<?php

use Psalm\Plugin\Hook\Event\AfterFunctionCallAnalysisEvent;

class BasePlugin implements Psalm\Plugin\Hook\AfterFunctionCallAnalysisInterface
{
    public static function afterFunctionCallAnalysis(AfterFunctionCallAnalysisEvent $event): void {
    }
}
