<?php

namespace Psalm\Internal\Fork;

use Composer\XdebugHandler\XdebugHandler;

use function array_filter;
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
use function preg_replace;
use function strlen;
use function strtolower;

use const PHP_VERSION_ID;

/**
 * @internal
 */
final class PsalmRestarter extends XdebugHandler
{
    private const REQUIRED_OPCACHE_SETTINGS = [
        'enable_cli' => true,
        'jit' => 1205,
        'jit_buffer_size' => 512 * 1024 * 1024,
        'optimization_level' => '0x7FFEBFFF',
        'preload' => '',
        'log_verbosity_level' => 0,
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
    protected function requiresRestart($default): bool
    {
        $this->required = (bool) array_filter(
            $this->disabled_extensions,
            static fn(string $extension): bool => extension_loaded($extension),
        );

        $opcache_loaded = extension_loaded('opcache') || extension_loaded('Zend OPcache');

        if (PHP_VERSION_ID >= 8_00_00 && $opcache_loaded) {
            // restart to enable JIT if it's not configured in the optimal way
            $opcache_settings = [
                'enable_cli' => in_array(ini_get('opcache.enable_cli'), ['1', 'true', true, 1]),
                'jit' => (int) ini_get('opcache.jit'),
                'log_verbosity_level' => (int) ini_get('opcache.log_verbosity_level'),
                'optimization_level' => (string) ini_get('opcache.optimization_level'),
                'preload' => (string) ini_get('opcache.preload'),
                'jit_buffer_size' => self::toBytes(ini_get('opcache.jit_buffer_size')),
            ];

            foreach (self::REQUIRED_OPCACHE_SETTINGS as $ini_name => $required_value) {
                if ($opcache_settings[$ini_name] !== $required_value) {
                    return true;
                }
            }
        }

        // opcache.save_comments is required for json mapper (used in language server) to work
        if ($opcache_loaded && in_array(ini_get('opcache.save_comments'), ['0', 'false', 0, false])) {
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
    protected function restart($command): void
    {
        if ($this->required && $this->tmpIni) {
            $regex = '/^\s*(extension\s*=.*(' . implode('|', $this->disabled_extensions) . ').*)$/mi';
            $content = file_get_contents($this->tmpIni);

            $content = preg_replace($regex, ';$1', $content);

            file_put_contents($this->tmpIni, $content);
        }

        $additional_options = [];
        $opcache_loaded = extension_loaded('opcache') || extension_loaded('Zend OPcache');

        // executed in the parent process (before restart)
        // if it wasn't loaded then we apparently don't have opcache installed and there's no point trying
        // to tweak it
        // If we're running on 7.4 there's no JIT available
        if (PHP_VERSION_ID >= 8_00_00 && $opcache_loaded) {
            $additional_options = [
                '-dopcache.enable_cli=true',
                '-dopcache.jit_buffer_size=512M',
                '-dopcache.jit=1205',
                '-dopcache.optimization_level=0x7FFEBFFF',
                '-dopcache.preload=',
                '-dopcache.log_verbosity_level=0',
            ];
        }

        if ($opcache_loaded) {
            $additional_options[] = '-dopcache.save_comments=1';
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
}
