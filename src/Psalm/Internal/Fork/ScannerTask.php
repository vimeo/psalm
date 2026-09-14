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
 * @implements Task<null, array{id: int|null, count: int}, string>
 */
final class ScannerTask implements Task
{
    /**
     * @psalm-mutation-free
     */
    public function __construct(private string $file)
    {
    }
    #[Override]
    public function run(Channel $channel, Cancellation $cancellation): mixed
    {
        $codebase = ProjectAnalyzer::getInstance()->getCodebase();

        // Register any new custom taints discovered while scanning this file through the parent's single
        // registry, so every worker agrees on the bit assigned to a given taint name.
        $codebase->setTaintRegistrationChannel($channel);

        try {
            return $codebase->scanner->scanAPath($this->file);
        } finally {
            $codebase->setTaintRegistrationChannel(null);
        }
    }
}
