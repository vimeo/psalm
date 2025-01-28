<?php

namespace Psalm\Internal\Fork;

use Amp\Cancellation;
use Amp\Parallel\Worker\Task;
use Amp\Sync\Channel;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Codebase\Analyzer;

/** @internal */
final class AnalyzerTask implements Task
{
    public function __construct(private string $file)
    {
    }
    public function run(Channel $channel, Cancellation $cancellation): mixed
    {
        $codebase = ProjectAnalyzer::getInstance()->getCodebase();
        return Analyzer::analysisWorker($codebase->config, $codebase->getProgress(), $this->file);
    }
}
