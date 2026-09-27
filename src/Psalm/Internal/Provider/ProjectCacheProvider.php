<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider;

use Psalm\Config;

use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_array;
use function serialize;
use function touch;
use function unserialize;

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

    private const CUSTOM_TAINTS_NAME = 'custom_taints';

    /**
     * Load the custom taint mapping persisted by a previous run, so that the taint bits baked into the
     * reused file/classlike storage cache keep matching their taint names (otherwise cached sinks and
     * sources silently stop matching, and no taint issues are reported when running from cache).
     *
     * @return array{count: int, custom: array<int, string>, map: array<string, int>}|null
     */
    public function loadCustomTaints(): ?array
    {
        $cache_directory = Config::getInstance()->getCacheDirectory();

        if ($cache_directory === null) {
            return null;
        }

        $taints_location = $cache_directory . DIRECTORY_SEPARATOR . self::CUSTOM_TAINTS_NAME;

        if (!file_exists($taints_location)) {
            return null;
        }

        $contents = file_get_contents($taints_location);

        if ($contents === false || $contents === '') {
            return null;
        }

        /** @var mixed $data */
        $data = unserialize($contents, ['allowed_classes' => false]);

        if (!is_array($data)
            || !isset($data['count'], $data['custom'], $data['map'])
            || !is_array($data['custom'])
            || !is_array($data['map'])
        ) {
            return null;
        }

        /** @var array{count: int, custom: array<int, string>, map: array<string, int>} */
        return $data;
    }

    /**
     * @param array{count: int, custom: array<int, string>, map: array<string, int>} $data
     */
    public function saveCustomTaints(array $data): void
    {
        $cache_directory = Config::getInstance()->getCacheDirectory();

        if ($cache_directory === null) {
            return;
        }

        file_put_contents(
            $cache_directory . DIRECTORY_SEPARATOR . self::CUSTOM_TAINTS_NAME,
            serialize($data),
        );
    }

    public function canDiffFiles(): bool
    {
        $cache_directory = Config::getInstance()->getCacheDirectory();

        return $cache_directory !== null && file_exists($cache_directory . DIRECTORY_SEPARATOR . self::GOOD_RUN_NAME);
    }

    public function processSuccessfulRun(float $start_time, string $psalm_version): void
    {
        $cache_directory = Config::getInstance()->getCacheDirectory();

        if ($cache_directory === null) {
            return;
        }

        $run_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::GOOD_RUN_NAME;

        file_put_contents($run_cache_location, $psalm_version);

        touch($run_cache_location, (int)$start_time);
    }
}
