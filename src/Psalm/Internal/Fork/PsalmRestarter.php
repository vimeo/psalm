<?php

namespace Psalm\Internal\Fork;

use Composer\XdebugHandler\XdebugHandler;

use function array_filter;
use function array_splice;
use function extension_loaded;
use function file_get_contents;
use function file_put_contents;
use function implode;
use function in_array;
use function ini_get;
use function preg_replace;

use const PHP_VERSION_ID;

/**
 * @internal
 */
class PsalmRestarter extends XdebugHandler
{
    private bool $required = false;

    /**
     * @var string[]
     */
    private array $disabledExtensions = [];

    public function disableExtension(string $disabledExtension): void
    {
        $this->disabledExtensions[] = $disabledExtension;
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
            $this->disabledExtensions,
            static fn(string $extension): bool => extension_loaded($extension)
        );

        if (PHP_VERSION_ID >= 8_00_00 && (extension_loaded('opcache') || extension_loaded('Zend OPcache'))) {
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
        }

        return $default || $this->required;
    }

    /**
     * No type hint to allow xdebug-handler v1 and v2 usage
     *
     * @param string[] $command
     */
    protected function restart(array $command): void
    {
        if ($this->required && $this->tmpIni) {
            $regex = '/^\s*(extension\s*=.*(' . implode('|', $this->disabledExtensions) . ').*)$/mi';
            $content = file_get_contents($this->tmpIni);

            $content = preg_replace($regex, ';$1', $content);

            file_put_contents($this->tmpIni, $content);
        }

        $additional_options = [];

        // executed in the parent process (before restart)
        // if it wasn't loaded then we apparently don't have opcache installed and there's no point trying
        // to tweak it
        // If we're running on 7.4 there's no JIT available
        if (PHP_VERSION_ID >= 8_00_00 && (extension_loaded('opcache') || extension_loaded('Zend OPcache'))) {
            $additional_options = [
                '-dopcache.enable_cli=true',
                '-dopcache.jit_buffer_size=512M',
                '-dopcache.jit=1205',
            ];
        }

        array_splice(
            $command,
            1,
            0,
            $additional_options,
        );

        parent::restart($command);
    }
}
