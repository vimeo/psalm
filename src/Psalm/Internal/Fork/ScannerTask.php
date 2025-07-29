<?php

declare(strict_types=1);

namespace Psalm\Internal\Fork;

use Amp\Cancellation;
use Amp\Parallel\Worker\Task;
use Amp\Sync\Channel;
use Override;
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
    #[Override]
    public function run(Channel $channel, Cancellation $cancellation): mixed
    {
        return ProjectAnalyzer::getInstance()->getCodebase()->scanner->scanAPath($this->file);
    }
}
