<?php
namespace Psalm\Test\Config\Plugin\Hook;

use Psalm\Codebase;
use Psalm\Context;
use Psalm\Plugin\Hook\AfterFileAnalysisInterface;
use Psalm\Plugin\Hook\BeforeFileAnalysisInterface;
use Psalm\StatementsSource;
use Psalm\Storage\FileStorage;

class FileProvider implements
    AfterFileAnalysisInterface,
    BeforeFileAnalysisInterface
{
    /**
     * Called before a file has been checked
     *
     * @return void
     */
    public static function beforeAnalyzeFile(
        StatementsSource $statements_source,
        Context $file_context,
        FileStorage $file_storage,
        Codebase $codebase
    ) {
        $file_storage = $codebase->file_storage_provider->get($statements_source->getFilePath());
        $file_storage->custom_metadata['before-analysis'] = true;
    }

    /**
     * Called before a file has been checked
     *
     * @return void
     */
    public static function afterAnalyzeFile(
        StatementsSource $statements_source,
        Context $file_context,
        FileStorage $file_storage,
        Codebase $codebase
    ) {
        $file_storage = $codebase->file_storage_provider->get($statements_source->getFilePath());
        $file_storage->custom_metadata['after-analysis'] = true;
    }
}
