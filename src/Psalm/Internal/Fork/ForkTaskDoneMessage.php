<?php
namespace Psalm\Internal\Fork;

class ForkTaskDoneMessage implements ForkMessage
{
    /** @var mixed */
    public $data;

    /**
     * @param mixed $data
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __construct($data)
    {
        $this->data = $data;
    }
}
