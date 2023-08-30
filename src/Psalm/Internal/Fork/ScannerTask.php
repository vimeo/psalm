<?php

namespace Psalm\Internal\Fork;

use Amp\Cancellation;
use Amp\Parallel\Worker\Task;
use Amp\Sync\Channel;
use AssertionError;
use Psalm\Internal\Analyzer\ProjectAnalyzer;

/** @internal */
final class ScannerTask implements Task
{
    public function __construct(private string $file)
    {
    }
    public function run(Channel $channel, Cancellation $cancellation): mixed
    {
        if (!isset(ProjectAnalyzer::$instance)) {
            throw new AssertionError(InitTask::$ran ? 'ran' : 'did not run');
        }
        return ProjectAnalyzer::getInstance()->getCodebase()->scanner->scanAPath($this->file);
    }
}
