<?php

namespace Psalm\Internal\Fork;

/**
 * @internal
 */
class PsalmRestarter extends \Composer\XdebugHandler\XdebugHandler
{
    /**
     * @var bool
     */
    private $required = false;

    /**
     * @var string[]
     */
    private $disabledExtensions = [];

    /**
     * @param string $disabledExtension
     *
     * @return void
     */
    public function disableExtension($disabledExtension)
    {
        $this->disabledExtensions[] = $disabledExtension;
    }

    /**
     * @psalm-suppress UnusedMethod
     */
    protected function requiresRestart($loaded)
    {
        $this->required = (bool) array_filter(
            $this->disabledExtensions,
            /**
             * @param string $extension
             *
             * @return bool
             */
            function ($extension) {
                return extension_loaded($extension);
            }
        );

        return $loaded || $this->required;
    }

    /**
     * @psalm-suppress UnusedMethod
     * @return void
     */
    protected function restart($command)
    {
        if ($this->required && $this->tmpIni) {
            $regex = '/^\s*(extension\s*=.*(' . implode('|', $this->disabledExtensions) . ').*)$/mi';
            $content = file_get_contents($this->tmpIni);

            $content = preg_replace($regex, ';$1', $content);

            file_put_contents($this->tmpIni, $content);
        }

        /** @psalm-suppress MixedArgument */
        parent::restart($command);
    }
}
