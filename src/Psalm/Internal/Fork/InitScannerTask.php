<?php

namespace Psalm\Internal\Fork;

use Amp\Cancellation;
use Amp\Parallel\Worker\Task;
use Amp\Sync\Channel;
use Psalm\Config;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\CliUtils;
use Psalm\Internal\ErrorHandler;
use Psalm\Internal\Provider\ClassLikeStorageProvider;
use Psalm\Internal\Provider\FileStorageProvider;
use Psalm\Internal\VersionUtils;
use Psalm\IssueBuffer;

use function cli_get_process_title;
use function cli_set_process_title;
use function define;
use function function_exists;
use function gc_collect_cycles;
use function gc_disable;
use function ini_get;
use function ini_set;

use const PHP_EOL;

final class InitScannerTask implements Task
{
    final public function run(Channel $channel, Cancellation $cancellation): mixed
    {
        $analyzer = ProjectAnalyzer::getInstance();
        $analyzer->progress->debug('Initialising forked process for scanning' . PHP_EOL);

        $codebase = $analyzer->getCodebase();
        $statements_provider = $codebase->statements_provider;

        $codebase->scanner->isForked();
        FileStorageProvider::deleteAll();
        ClassLikeStorageProvider::deleteAll();

        $statements_provider->resetDiffs();

        $analyzer->progress->debug('Have initialised forked process for scanning' . PHP_EOL);

        return null;
    }
}
