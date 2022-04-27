<?php

namespace Psalm\Internal\Provider;

use function array_key_exists;
use function array_search;
use function array_splice;
use function count;
use function is_null;
use function key;
use function reset;

/**
 * @todo: track variables size instead
 */
class VolatileCacheProvider extends FileProvider
{
    /**
     * @var array
     */
    protected $cache = [];

    /**
     * @var list<string>
     */
    protected $access = [];

    /**
     * @var int
     */
    protected $max_size;

    /**
     * @var VolatileCacheProvider
     */
    protected static $instance;

    public function __construct(int $max_size = 4096)
    {
        $this->max_size = $max_size;
    }

    public static function getInstance(): VolatileCacheProvider
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->cache);
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get(string $key)
    {
        if (! $this->has($key)) {
            return null;
        }

        $access_index = array_search($key, $this->access);
        if (false !== $access_index) {
            array_splice($this->access, $access_index, 1);
        }
        $this->access[] = $key;

        return $this->cache[$key];
    }

    public function set(string $key, &$content): void
    {
        if (count($this->cache) > $this->max_size) {
            reset($this->access);

            $oldest_key_index = key($this->access);

            if (! is_null($oldest_key_index)) {
                $oldest_key = $this->access[$oldest_key_index];
                unset($this->cache[$oldest_key]);
                unset($this->access[$oldest_key_index]);
            }
        }

        $this->cache[$key] = & $content;
        $this->access[] = $key;
    }

    public function clearCache(): void
    {
        $this->cache = [];
        $this->access = [];
    }
}
