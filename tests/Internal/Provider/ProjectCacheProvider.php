<?php
namespace Psalm\Tests\Internal\Provider;

class ProjectCacheProvider extends \Psalm\Internal\Provider\ProjectCacheProvider
{
    /**
     * @var int
     */
    private $last_run = 0;

    public function __construct()
    {
    }

    public function getLastRun(): int
    {
        return $this->last_run;
    }

    public function processSuccessfulRun(float $start_time): void
    {
        $this->last_run = (int) $start_time;
    }

    public function canDiffFiles(): bool
    {
        return $this->last_run > 0;
    }

    public function hasLockfileChanged() : bool
    {
        return false;
    }

    public function updateComposerLockHash() : void
    {
    }
}
