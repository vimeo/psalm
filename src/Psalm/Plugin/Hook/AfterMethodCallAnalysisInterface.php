<?php
namespace Psalm\Plugin\Hook;

use Psalm\Plugin\Hook\Event\AfterMethodCallAnalysisEvent;

interface AfterMethodCallAnalysisInterface
{
    public static function afterMethodCallAnalysis(AfterMethodCallAnalysisEvent $event): void;
}
