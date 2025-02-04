<?php

declare(strict_types=1);

namespace Psalm\Internal\Fork;

use Amp\Cancellation;
use Amp\Parallel\Worker\Task;
use Amp\Sync\Channel;
use Psalm\Internal\Analyzer\ProjectAnalyzer;

/**
 * @internal
 * @implements Task<null, void, void>
 */
final class ScannerTask implements Task
{
    public function __construct(private string $file)
    {
    }
    public function run(Channel $channel, Cancellation $cancellation): mixed
    {
        return ProjectAnalyzer::getInstance()->getCodebase()->scanner->scanAPath($this->file);
    }
}
