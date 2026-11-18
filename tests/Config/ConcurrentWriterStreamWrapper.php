<?php

declare(strict_types=1);

// the method names of a stream wrapper are fixed by PHP
// phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps

namespace Psalm\Tests\Config;

use function closedir;
use function dirname;
use function fclose;
use function file_put_contents;
use function flock;
use function fopen;
use function is_resource;
use function opendir;
use function readdir;
use function rewinddir;
use function rmdir;
use function stat;
use function str_replace;
use function stream_wrapper_register;
use function stream_wrapper_unregister;
use function unlink;

use const STREAM_URL_STAT_QUIET;

/**
 * A `race://` stream wrapper that forwards to the real file system, and writes a file into a directory right after
 * a file was removed from it, as another process does that works on the same cache directory
 *
 * @psalm-api
 */
final class ConcurrentWriterStreamWrapper
{
    public const PROTOCOL = 'race';

    /** @var resource|null */
    public $context;

    /** @var resource|null */
    private $handle = null;

    public static function register(): void
    {
        stream_wrapper_register(self::PROTOCOL, self::class);
    }

    public static function unregister(): void
    {
        stream_wrapper_unregister(self::PROTOCOL);
    }

    public function dir_opendir(string $path, int $_options): bool
    {
        $handle = opendir(self::real($path));
        $this->handle = $handle === false ? null : $handle;

        return $handle !== false;
    }

    public function dir_readdir(): string|false
    {
        return $this->handle === null ? false : readdir($this->handle);
    }

    public function dir_closedir(): bool
    {
        if (is_resource($this->handle)) {
            closedir($this->handle);
        }

        return true;
    }

    public function dir_rewinddir(): bool
    {
        if (is_resource($this->handle)) {
            rewinddir($this->handle);
        }

        return true;
    }

    /**
     * @return array<array-key, int>|false
     */
    public function url_stat(string $path, int $flags): array|false
    {
        $real = self::real($path);

        return ($flags & STREAM_URL_STAT_QUIET) !== 0 ? @stat($real) : stat($real);
    }

    public function stream_open(string $path, string $mode, int $_options, ?string &$_opened_path): bool
    {
        $handle = fopen(self::real($path), $mode);
        $this->handle = $handle === false ? null : $handle;

        return $handle !== false;
    }

    public function stream_lock(int $operation): bool
    {
        return $this->handle !== null && flock($this->handle, $operation);
    }

    public function stream_close(): void
    {
        $handle = $this->handle;
        $this->handle = null;

        if (is_resource($handle)) {
            fclose($handle);
        }
    }

    public function unlink(string $path): bool
    {
        $real = self::real($path);
        $removed = unlink($real);

        file_put_contents(dirname($real) . '/written-by-another-process', 'x');

        return $removed;
    }

    public function rmdir(string $path, int $_options): bool
    {
        return rmdir(self::real($path));
    }

    /**
     * @psalm-pure
     */
    private static function real(string $path): string
    {
        return str_replace(self::PROTOCOL . '://', '', $path);
    }
}
