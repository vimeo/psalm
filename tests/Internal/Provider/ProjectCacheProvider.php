<?php
namespace Psalm\Tests\Internal\Provider;

use function microtime;
use PhpParser;

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

    /**
     * @return void
     */
    public function processSuccessfulRun(float $start_time)
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
