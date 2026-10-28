<?php

declare(strict_types=1);

namespace Psalm\Tests;

use DG\BypassFinals\MutatingWrapper;
use ReflectionClass;

use function method_exists;
use function stream_wrapper_register;
use function stream_wrapper_unregister;

/**
 * Lets BypassFinals' file wrapper read through another wrapper that registers itself again after each operation, like
 * the include interceptor of Infection's mutant runs: after each operation, it registers BypassFinals' wrapper again.
 * BypassFinals only forwards the operations its underlying wrapper declares, hence one method per operation, named
 * as PHP's stream wrapper protocol requires.
 *
 * @internal
 * @psalm-api
 */
// phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
final class BypassFinalsUnderlyingWrapper
{
    /** @var class-string */
    public static string $wrapperClass;

    /** @var resource|null */
    public $context;

    private ?object $wrapper = null;

    public function dir_closedir(mixed ...$args): mixed
    {
        return $this->call(__FUNCTION__, $args);
    }

    public function dir_opendir(mixed ...$args): mixed
    {
        return $this->call(__FUNCTION__, $args);
    }

    public function dir_readdir(mixed ...$args): mixed
    {
        return $this->call(__FUNCTION__, $args);
    }

    public function dir_rewinddir(mixed ...$args): mixed
    {
        return $this->call(__FUNCTION__, $args);
    }

    public function mkdir(mixed ...$args): mixed
    {
        return $this->call(__FUNCTION__, $args);
    }

    public function rename(mixed ...$args): mixed
    {
        return $this->call(__FUNCTION__, $args);
    }

    public function rmdir(mixed ...$args): mixed
    {
        return $this->call(__FUNCTION__, $args);
    }

    public function stream_cast(mixed ...$args): mixed
    {
        return $this->call(__FUNCTION__, $args);
    }

    public function stream_close(mixed ...$args): mixed
    {
        return $this->call(__FUNCTION__, $args);
    }

    public function stream_eof(mixed ...$args): mixed
    {
        return $this->call(__FUNCTION__, $args);
    }

    public function stream_flush(mixed ...$args): mixed
    {
        return $this->call(__FUNCTION__, $args);
    }

    public function stream_lock(mixed ...$args): mixed
    {
        return $this->call(__FUNCTION__, $args);
    }

    public function stream_metadata(mixed ...$args): mixed
    {
        return $this->call(__FUNCTION__, $args);
    }

    public function stream_open(mixed ...$args): mixed
    {
        return $this->call(__FUNCTION__, $args);
    }

    public function stream_read(mixed ...$args): mixed
    {
        return $this->call(__FUNCTION__, $args);
    }

    public function stream_seek(mixed ...$args): mixed
    {
        return $this->call(__FUNCTION__, $args);
    }

    public function stream_set_option(mixed ...$args): mixed
    {
        return $this->call(__FUNCTION__, $args);
    }

    public function stream_stat(mixed ...$args): mixed
    {
        return $this->call(__FUNCTION__, $args);
    }

    public function stream_tell(mixed ...$args): mixed
    {
        return $this->call(__FUNCTION__, $args);
    }

    public function stream_truncate(mixed ...$args): mixed
    {
        return $this->call(__FUNCTION__, $args);
    }

    public function stream_write(mixed ...$args): mixed
    {
        return $this->call(__FUNCTION__, $args);
    }

    public function unlink(mixed ...$args): mixed
    {
        return $this->call(__FUNCTION__, $args);
    }

    public function url_stat(mixed ...$args): mixed
    {
        return $this->call(__FUNCTION__, $args);
    }

    /**
     * @param array<mixed> $args
     */
    private function call(string $method, array $args): mixed
    {
        if ($this->wrapper === null) {
            $this->wrapper = (new ReflectionClass(self::$wrapperClass))->newInstance();
            $this->wrapper->context = $this->context;
        }

        try {
            return method_exists($this->wrapper, $method) ? $this->wrapper->$method(...$args) : false;
        } finally {
            stream_wrapper_unregister('file');
            stream_wrapper_register('file', MutatingWrapper::class);
        }
    }
}
