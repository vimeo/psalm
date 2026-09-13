<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Provider;

use Override;
use Psalm\Internal\Provider\ProjectCacheProvider as PsalmProjectCacheProvider;

final class ProjectCacheProvider extends PsalmProjectCacheProvider
{
    private int $last_run = 0;

    /**
     * @psalm-mutation-free
     */
    public function __construct()
    {
    }

    /**
     * @psalm-external-mutation-free
     */
    #[Override]
    public function processSuccessfulRun(float $start_time, string $psalm_version): void
    {
        $this->last_run = (int) $start_time;
    }

    /**
     * @psalm-mutation-free
     */
    #[Override]
    public function canDiffFiles(): bool
    {
        return $this->last_run > 0;
    }
}
