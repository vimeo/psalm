<?php

declare(strict_types=1);

namespace Psalm\Internal\Fork;

use Composer\XdebugHandler\XdebugHandler;
use Override;

use function array_merge;
use function array_splice;
use function assert;
use function count;
use function extension_loaded;
use function file_get_contents;
use function file_put_contents;
use function implode;
use function in_array;
use function ini_get;
use function is_int;
use function preg_replace;
use function strlen;
use function strtolower;

/**
 * @internal
 */
final class PsalmRestarter extends XdebugHandler
{
    private const REQUIRED_OPCACHE_SETTINGS = [
        'enable' => 1,
        'enable_cli' => 1,
        'jit' => 1205,
        'validate_timestamps' => 0,
        'file_update_protection' => 0,
        'jit_buffer_size' => 128 * 1024 * 1024,
        'max_accelerated_files' => 1_000_000,
        'interned_strings_buffer' => 64,
        'jit_max_root_traces' => 100_000,
        'jit_max_side_traces' => 100_000,
        'jit_max_exit_counters' => 100_000,
        'jit_hot_loop' => 1,
        'jit_hot_func' => 1,
        'jit_hot_return' => 1,
        'jit_hot_side_exit' => 1,
        'jit_blacklist_root_trace' => 255,
        'jit_blacklist_side_trace' => 255,
        'optimization_level' => '0x7FFEBFFF',
        'preload' => '',
        'log_verbosity_level' => 0,
        'save_comments' => 1,
    ];

    private bool $required = false;

    /**
     * @var string[]
     */
    private array $disabled_extensions = [];

    public function disableExtension(string $disabled_extension): void
    {
        $this->disabled_extensions[] = $disabled_extension;
    }

    /** @param list<non-empty-string> $disable_extensions */
    public function disableExtensions(array $disable_extensions): void
    {
        $this->disabled_extensions = array_merge($this->disabled_extensions, $disable_extensions);
    }

    /**
     * No type hint to allow xdebug-handler v1 and v2 usage
     *
     * @param bool $default
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    #[Override]
    protected function requiresRestart($default): bool
    {
        foreach ($this->disabled_extensions as $extension) {
            if (extension_loaded($extension)) {
                $this->required = true;
                break;
            }
        }

        if (!extension_loaded('opcache') && !extension_loaded('Zend OPcache')) {
            return true;
        }

            // restart to enable JIT if it's not configured in the optimal way
        foreach (self::REQUIRED_OPCACHE_SETTINGS as $ini_name => $required_value) {
            $value = (string) ini_get("opcache.$ini_name");
            if ($ini_name === 'jit_buffer_size') {
                $value = self::toBytes($value);
            } elseif ($ini_name === 'enable_cli') {
                $value = in_array($value, ['1', 'true', true, 1]) ? 1 : 0;
            } elseif (is_int($required_value)) {
                $value = (int) $value;
            }
            if ($value !== $required_value) {
                return true;
            }
        }

            $requiredMemoryConsumption = self::getRequiredMemoryConsumption();

        if ((int)ini_get('opcache.memory_consumption') < $requiredMemoryConsumption) {
            return true;
        }

        return $default || $this->required;
    }

    private static function toBytes(string $value): int
    {
        if (strlen($value) === 0) {
            return 0;
        }

        $unit = strtolower($value[strlen($value) - 1]);

        if (in_array($unit, ['g', 'm', 'k'], true)) {
            $value = (int) $value;
        } else {
            $unit = '';
            $value = (int) $value;
        }

        switch ($unit) {
            case 'g':
                $value *= 1024;
                // no break
            case 'm':
                $value *= 1024;
                // no break
            case 'k':
                $value *= 1024;
        }

        return $value;
    }


    /**
     * No type hint to allow xdebug-handler v1 and v2 usage
     *
     * @param non-empty-list<string> $command
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    #[Override]
    protected function restart($command): void
    {
        if ($this->required && $this->tmpIni) {
            $regex = '/^\s*((?:zend_)?extension\s*=.*(' . implode('|', $this->disabled_extensions) . ').*)$/mi';
            $content = file_get_contents($this->tmpIni);
            assert($content !== false);

            $content = (string) preg_replace($regex, ';$1', $content);

            file_put_contents($this->tmpIni, $content);
        }

        $opcache_loaded = extension_loaded('opcache') || extension_loaded('Zend OPcache');

        // executed in the parent process (before restart)
        // if it wasn't loaded then we apparently don't have opcache installed and there's no point trying
        // to tweak it
        $additional_options = $opcache_loaded ? [] : ['-dzend_extension=opcache'];
        foreach (self::REQUIRED_OPCACHE_SETTINGS as $key => $value) {
            $additional_options []= "-dopcache.{$key}={$value}";
        }

        $requiredMemoryConsumption = self::getRequiredMemoryConsumption();

        if ((int)ini_get('opcache.memory_consumption') < $requiredMemoryConsumption) {
            $additional_options []= "-dopcache.memory_consumption={$requiredMemoryConsumption}";
        }


        array_splice(
            $command,
            1,
            0,
            $additional_options,
        );
        assert(count($command) > 1);

        parent::restart($command);
    }

    /**
     * @return positive-int
     */
    private static function getRequiredMemoryConsumption(): int
    {
        // Reserve for byte-codes
        $result = 256;

        if (isset(self::REQUIRED_OPCACHE_SETTINGS['jit_buffer_size'])) {
            $result += self::REQUIRED_OPCACHE_SETTINGS['jit_buffer_size'] / 1024 / 1024;
        }

        if (isset(self::REQUIRED_OPCACHE_SETTINGS['interned_strings_buffer'])) {
            $result += self::REQUIRED_OPCACHE_SETTINGS['interned_strings_buffer'];
        }

        return $result;
    }
}
