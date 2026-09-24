<?php

declare(strict_types=1);

namespace Psalm\Config;

use JsonException;

use function array_merge;
use function assert;
use function dirname;
use function file_exists;
use function file_get_contents;
use function is_array;
use function is_string;
use function json_decode;
use function preg_match;
use function str_replace;

use const DIRECTORY_SEPARATOR;
use const JSON_THROW_ON_ERROR;

/** @internal */
final class CiCreator
{
    private const DEFAULT_PSALM_VERSION = 'latest';

    public static function getContents(string $current_dir): string
    {
        $psalm_version = self::detectPsalmVersion($current_dir) ?? self::DEFAULT_PSALM_VERSION;
        $template = self::loadTemplate();

        return str_replace('__PSALM_VERSION__', $psalm_version, $template);
    }

    private static function loadTemplate(): string
    {
        $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . '.github'
            . DIRECTORY_SEPARATOR . 'stubs'
            . DIRECTORY_SEPARATOR . 'github-actions-psalm.yml';

        $contents = file_get_contents($path);
        assert($contents !== false);

        return $contents;
    }

    private static function detectPsalmVersion(string $current_dir): ?string
    {
        $composer_lock_path = $current_dir . DIRECTORY_SEPARATOR . 'composer.lock';

        if (!file_exists($composer_lock_path)) {
            return null;
        }

        $contents = file_get_contents($composer_lock_path);
        if ($contents === false) {
            return null;
        }

        try {
            $lock = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (!is_array($lock)) {
            return null;
        }

        $packages = isset($lock['packages']) && is_array($lock['packages']) ? $lock['packages'] : [];
        $packages_dev = isset($lock['packages-dev']) && is_array($lock['packages-dev']) ? $lock['packages-dev'] : [];
        /** @var list<mixed> $all_packages */
        $all_packages = array_merge($packages, $packages_dev);
        foreach ($all_packages as $package) {
            if (!is_array($package)) {
                continue;
            }
            if (($package['name'] ?? null) !== 'vimeo/psalm') {
                continue;
            }
            $version = $package['version'] ?? null;
            if (!is_string($version)) {
                return null;
            }
            // Extract major version from e.g. "6.8.4" or "v6.8.4"
            if (preg_match('/v?(\d+)/', $version, $matches) === 1
                && isset($matches[1])
            ) {
                return $matches[1];
            }

            return null;
        }

        return null;
    }
}
