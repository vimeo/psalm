<?php

namespace Psalm\Internal\Fork;

use Amp\Cancellation;
use Amp\Parallel\Worker\Task;
use Amp\Sync\Channel;
use Assert\Assertion;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\CliUtils;
use Psalm\Internal\ErrorHandler;
use Psalm\Internal\Provider\ClassLikeStorageProvider;
use Psalm\Internal\Provider\FileStorageProvider;

use const PHP_EOL;

final class InitScannerTask extends InitTask
{
    public function init(): void
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
    }
}
