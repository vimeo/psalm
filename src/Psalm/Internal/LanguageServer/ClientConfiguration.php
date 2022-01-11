<?php

declare(strict_types=1);

namespace Psalm\Internal\LanguageServer;

class ClientConfiguration
{

    /**
     * Hide Warnings or not
     *
     * @var boolean
     */
    protected $hideWarnings = false;



    /**
     * Should warnings be hidden or not
     */
    public function hideWarnings(): bool
    {
        return $this->hideWarnings;
    }

    /**
     * Set the value of hideWarnings
     */
    public function setHideWarnings(bool $hideWarnings): self
    {
        $this->hideWarnings = $hideWarnings;

        return $this;
    }
}
