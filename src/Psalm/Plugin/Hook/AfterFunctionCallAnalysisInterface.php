<?php
namespace Psalm\Plugin\Hook;

use Psalm\Plugin\Hook\Event\AfterFunctionCallAnalysisEvent;

interface AfterFunctionCallAnalysisInterface
{
    public static function afterFunctionCallAnalysis(AfterFunctionCallAnalysisEvent $event): void;
}
