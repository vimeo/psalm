<?php

namespace Psalm\Fork;

class PsalmRestarter extends \Composer\XdebugHandler\XdebugHandler
{
    /**
     * @var bool
     */
    private $required = false;

    /**
     * @var bool
     */
    private $useThreads = false;

    /**
     * @return void
     */
    public function useThreads()
    {
        $this->useThreads = true;
    }

    /**
     * @psalm-suppress UnusedMethod
     */
    protected function requiresRestart($loaded)
    {
        $this->required = $this->useThreads && extension_loaded('grpc');

        return $loaded || $this->required;
    }

    /**
     * @psalm-suppress UnusedMethod
     * @return void
     */
    protected function restart($command)
    {
        if ($this->required && $this->tmpIni) {
            $regex = '/^\s*(extension\s*=.*grpc.*)$/mi';
            $content = file_get_contents($this->tmpIni);

            $content = preg_replace($regex, ';$1', $content);
            file_put_contents($this->tmpIni, $content);
        }

        /** @psalm-suppress MixedArgument */
        parent::restart($command);
    }
}
