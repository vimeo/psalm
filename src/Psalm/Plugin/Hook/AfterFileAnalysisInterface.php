<?php
namespace Psalm\Plugin\Hook;

use Psalm\Codebase;
use Psalm\Context;
use Psalm\StatementsSource;
use Psalm\Storage\FileStorage;

/** @deprecated going to be removed in Psalm 5 */
interface AfterFileAnalysisInterface
{
    /**
     * Called after a file has been checked
     */
    public static function afterAnalyzeFile(
        StatementsSource $statements_source,
        Context $file_context,
        FileStorage $file_storage,
        Codebase $codebase
    ): void;
}
