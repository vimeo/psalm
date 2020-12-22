<?php
namespace Psalm\Plugin\Hook;

use Psalm\Plugin\Hook\Event\AfterEveryFunctionCallAnalysisEvent;

interface AfterEveryFunctionCallAnalysisInterface
{
    public static function afterEveryFunctionCallAnalysis(AfterEveryFunctionCallAnalysisEvent $event): void;
}
