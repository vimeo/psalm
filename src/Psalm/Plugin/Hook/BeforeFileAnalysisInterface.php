<?php
namespace Psalm\Plugin\Hook;

use Psalm\Plugin\Hook\Event\BeforeFileAnalysisEvent;

interface BeforeFileAnalysisInterface
{
    /**
     * Called before a file has been checked
     */
    public static function beforeAnalyzeFile(BeforeFileAnalysisEvent $event): void;
}
