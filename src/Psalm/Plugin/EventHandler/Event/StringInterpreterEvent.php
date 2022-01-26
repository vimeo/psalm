<?php

namespace Psalm\Plugin\EventHandler\Event;

final class StringInterpreterEvent
{
    /**
     * @var string
     */
    private $value;

    /**
     * Called after a statement has been checked
     *
     * @internal
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
