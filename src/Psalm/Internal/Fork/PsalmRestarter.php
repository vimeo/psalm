<?php

namespace Psalm\Internal\Fork;

use Composer\XdebugHandler\Process;
use Composer\XdebugHandler\XdebugHandler;

use function array_filter;
use function array_merge;
use function array_splice;
use function defined;
use function extension_loaded;
use function fgets;
use function file_get_contents;
use function file_put_contents;
use function function_exists;
use function implode;
use function in_array;
use function ini_get;
use function is_resource;
use function preg_replace;
use function proc_close;
use function proc_open;
use function trim;

use const PHP_BINARY;
use const PHP_VERSION_ID;

/**
 * @internal
 */
class PsalmRestarter extends XdebugHandler
{
    private bool $required = false;
    private ?bool $needOPcache = null;

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
     * Returns true if the opcache extension is not currently loaded and *can* be loaded.
     */
    private function canAndNeedToLoadOpcache(): bool
    {
        if (function_exists('opcache_get_status')) {
            return false;
        }
        if ($this->needOPcache !== null) {
            return $this->needOPcache;
        }
        $cmd = [PHP_BINARY, '-n', '-dzend_extension=opcache', '-r', 'var_dump(function_exists("opcache_get_status"));'];

        if (PHP_VERSION_ID >= 70400) {
            $cmd = $cmd;
        } else {
            $cmd = Process::escapeShellCommand($cmd);
            if (defined('PHP_WINDOWS_VERSION_BUILD')) {
                // Outer quotes required on cmd string below PHP 8
                $cmd = '"'.$cmd.'"';
            }
        }

        $process = proc_open($cmd, [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes);
        $this->needOPcache =
            isset($pipes[1])
            && trim(fgets($pipes[1]) ?: '') === 'bool(true)';

        if (is_resource($process)) {
            proc_close($process);
        }

        return $this->needOPcache;
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
            static fn(string $extension): bool => extension_loaded($extension)
        );

        if ($this->canAndNeedToLoadOpcache()) {
            return true;
        }

        // restart to enable JIT if it's not configured in the optimal way
        if (!in_array(ini_get('opcache.enable_cli'), ['1', 'true', true, 1])) {
            return true;
        }

        if (((int) ini_get('opcache.jit')) !== 1205) {
            return true;
        }

        if (((int) ini_get('opcache.jit')) === 0) {
            return true;
        }

        if (ini_get('opcache.optimization_level') !== '0x7FFEBFFF') {
            return true;
        }

        return $default || $this->required;
    }

    /**
     * No type hint to allow xdebug-handler v1 and v2 usage
     *
     * @param string[] $command
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

        array_splice(
            $command,
            1,
            0,
            [
                '-dopcache.enable_cli=true',
                '-dopcache.jit_buffer_size=512M',
                '-dopcache.jit=1205',
                '-dopcache.optimization_level=0x7FFEBFFF',
            ],
        );

        if ($this->canAndNeedToLoadOpcache()) {
            array_splice(
                $command,
                1,
                0,
                ['-dzend_extension=opcache'],
            );
        }

        parent::restart($command);
    }
}
