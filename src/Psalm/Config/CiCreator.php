<?php

declare(strict_types=1);

namespace Psalm\Config;

use JsonException;
use Psalm\Internal\Composer;

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
    private const DEFAULT_PHP_VERSION = '8.3';

    public static function getContents(string $current_dir): string
    {
        $php_version = self::detectPhpVersion($current_dir) ?? self::DEFAULT_PHP_VERSION;
        $template = self::loadTemplate();

        return str_replace('__PHP_VERSION__', $php_version, $template);
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

    private static function detectPhpVersion(string $current_dir): ?string
    {
        $composer_json_path = Composer::getJsonFilePath($current_dir);

        if (!file_exists($composer_json_path)) {
            return null;
        }

        $contents = file_get_contents($composer_json_path);
        if ($contents === false) {
            return null;
        }

        try {
            $composer_json = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (!is_array($composer_json)) {
            return null;
        }

        $php_constraint = $composer_json['require']['php'] ?? null;

        if (!is_string($php_constraint)) {
            return null;
        }

        // Extract the minimum version from common constraint patterns
        // e.g. ">=8.1", "^8.1", "~8.1", "8.1.*", ">=8.1 <8.4"
        if (preg_match('/(\d+\.\d+)/', $php_constraint, $matches) === 1
            && isset($matches[1])
        ) {
            return $matches[1];
        }

        return null;
    }
}
