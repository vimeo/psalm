<?php

declare(strict_types=1);

namespace Psalm\Internal;

use function array_map;
use function explode;
use function get_cfg_var;
use function implode;
use function ini_get;
use function is_string;
use function preg_match;
use function rawurlencode;
use function strtr;
use function substr;

/**
 * Builds clickable terminal links (OSC 8) to a file location.
 *
 * @internal
 */
final class FileLink
{
    private const DEFAULT_FORMAT = 'file://%f#L%l';

    /**
     * Wraps $text in an OSC 8 hyperlink pointing at $file_path:$line.
     *
     * @psalm-external-mutation-free
     */
    public static function hyperlink(string $text, string $file_path, int $line): string
    {
        return "\e]8;;" . self::create($file_path, $line) . "\e\\" . $text . "\e]8;;\e\\";
    }

    /**
     * Uses `xdebug.file_link_format` when set, read with `get_cfg_var` as well,
     * so that it works when the xdebug extension is not loaded.
     *
     * @psalm-external-mutation-free
     * @psalm-suppress ImpureFunctionCall reads php.ini configuration, which does not change during a run
     */
    public static function create(string $file_path, int $line): string
    {
        $format = ini_get('xdebug.file_link_format');
        if (!is_string($format) || $format === '') {
            $format = get_cfg_var('xdebug.file_link_format');
        }

        return self::build(is_string($format) ? $format : '', $file_path, $line);
    }

    /**
     * An empty $format selects the default `file://` URI.
     *
     * @psalm-pure
     */
    public static function build(string $format, string $file_path, int $line): string
    {
        if ($format !== '') {
            // Xdebug substitutes the raw file name into a custom format, so do the same
            return strtr($format, ['%f' => $file_path, '%l' => (string) $line]);
        }

        return strtr(self::DEFAULT_FORMAT, ['%f' => self::toFileUriPath($file_path), '%l' => (string) $line]);
    }

    /**
     * @psalm-pure
     */
    private static function toFileUriPath(string $file_path): string
    {
        $path = strtr($file_path, '\\', '/');
        $prefix = '';

        if (preg_match('#^[A-Za-z]:(?:/|$)#', $path) === 1) {
            // `C:/dir` becomes `file:///C:/dir`
            $prefix = '/' . substr($path, 0, 2);
            $path = substr($path, 2);
        }

        return $prefix . implode('/', array_map(rawurlencode(...), explode('/', $path)));
    }
}
