<?php

declare(strict_types=1);

namespace Psalm\Internal\Fork;

use Amp\Cancellation;
use Amp\Parallel\Worker\Task;
use Amp\Sync\Channel;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Codebase\Scanner;

/** @psalm-import-type PoolData from Scanner */
final class InitAnalyzerTask implements Task
{
    /** @var PoolData */
    private readonly array $data;
    public function __construct()
    {
        $this->data = ShutdownScannerTask::getPoolData();
    }
    public function run(Channel $channel, Cancellation $cancellation): mixed
    {
        ProjectAnalyzer::getInstance()->getCodebase()->addThreadData($this->data);
        return null;
    }
}
