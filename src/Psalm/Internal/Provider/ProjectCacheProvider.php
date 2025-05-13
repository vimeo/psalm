<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider;

use Psalm\Config;

use function file_exists;
use function file_put_contents;
use function filemtime;
use function touch;

use const DIRECTORY_SEPARATOR;

/**
 * Used to determine which files reference other files, necessary for using the --diff
 * option from the command line.
 *
 * @internal
 */
class ProjectCacheProvider
{
    private const GOOD_RUN_NAME = 'good_run';

    private ?int $last_run = null;

    public function canDiffFiles(): bool
    {
        $cache_directory = Config::getInstance()->getCacheDirectory();

        return $cache_directory && file_exists($cache_directory . DIRECTORY_SEPARATOR . self::GOOD_RUN_NAME);
    }

    public function processSuccessfulRun(float $start_time, string $psalm_version): void
    {
        $cache_directory = Config::getInstance()->getCacheDirectory();

        if (!$cache_directory) {
            return;
        }

        $run_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::GOOD_RUN_NAME;

        file_put_contents($run_cache_location, $psalm_version);

        touch($run_cache_location, (int)$start_time);
    }

    public function getLastRun(string $psalm_version): int
    {
        if ($this->last_run === null) {
            $cache_directory = Config::getInstance()->getCacheDirectory();

            $run_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::GOOD_RUN_NAME;

            if (file_exists($run_cache_location)
                && Providers::safeFileGetContents($run_cache_location) === $psalm_version) {
                $this->last_run = (int) filemtime($run_cache_location);
            } else {
                $this->last_run = 0;
            }
        }

        return $this->last_run;
    }
}
