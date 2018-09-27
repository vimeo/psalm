<?php declare(strict_types=1);

namespace Psalm\Diff;

/**
 * @internal
 */
class DiffElem
{
    const TYPE_KEEP = 0;
    const TYPE_REMOVE = 1;
    const TYPE_ADD = 2;
    const TYPE_REPLACE = 3;
    const TYPE_KEEP_SIGNATURE = 4;

    /** @var int One of the TYPE_* constants */
    public $type;
    /** @var mixed Is null for add operations */
    public $old;
    /** @var mixed Is null for remove operations */
    public $new;

    /**
     * @param int    $type
     * @param mixed  $old
     * @param mixed  $new
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __construct($type, $old, $new)
    {
        $this->type = $type;
        $this->old = $old;
        $this->new = $new;
    }
}
