<?php

declare(strict_types=1);

namespace Psalm\Internal\LanguageServer\Provider;

use Override;
use Psalm\Internal\Provider\ProjectCacheProvider as PsalmProjectCacheProvider;

/**
 * @internal
 */
final class ProjectCacheProvider extends PsalmProjectCacheProvider
{
    private int $last_run = 0;

    public function __construct()
    {
    }

    #[Override]
    public function getLastRun(string $psalm_version): int
    {
        return $this->last_run;
    }

    #[Override]
    public function processSuccessfulRun(float $start_time, string $psalm_version): void
    {
        $this->last_run = (int) $start_time;
    }

    #[Override]
    public function canDiffFiles(): bool
    {
        return $this->last_run > 0;
    }

    #[Override]
    public function hasLockfileChanged(): bool
    {
        return false;
    }

    #[Override]
    public function updateComposerLockHash(): void
    {
    }
}
