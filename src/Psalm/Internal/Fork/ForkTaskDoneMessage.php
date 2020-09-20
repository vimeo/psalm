<?php

declare(strict_types=1);

namespace Psalm\Internal\Fork;

/**
 * @psalm-immutable
 */
class ForkTaskDoneMessage implements ForkMessage
{
    /** @var mixed */
    public $data;

    /**
     * @param mixed $data
     */
    public function __construct($data)
    {
        $this->data = $data;
    }
}
