<?php
namespace Psalm\Test\Config\Plugin\Hook;

use Psalm\Plugin\Hook\AfterFileAnalysisInterface;
use Psalm\Plugin\Hook\BeforeFileAnalysisInterface;
use Psalm\Plugin\Hook\Event\AfterFileAnalysisEvent;
use Psalm\Plugin\Hook\Event\BeforeFileAnalysisEvent;

class FileProvider implements
    AfterFileAnalysisInterface,
    BeforeFileAnalysisInterface
{
    /**
     * Called before a file has been checked
     */
    public static function beforeAnalyzeFile(BeforeFileAnalysisEvent $event): void {
        $codebase = $event->getCodebase();
        $statements_source = $event->getStatementsSource();
        $file_storage = $codebase->file_storage_provider->get($statements_source->getFilePath());
        $file_storage->custom_metadata['before-analysis'] = true;
    }

    /**
     * Called before a file has been checked
     */
    public static function afterAnalyzeFile(AfterFileAnalysisEvent $event): void {
        $codebase = $event->getCodebase();
        $statements_source = $event->getStatementsSource();
        $file_storage = $codebase->file_storage_provider->get($statements_source->getFilePath());
        $file_storage->custom_metadata['after-analysis'] = true;
    }
}
