<?php

namespace Psalm\Internal\Type\TypeAlias;

use Psalm\Internal\Type\TypeAlias;
use Psalm\Type\Atomic;

/**
 * @internal
 */
final class ClassTypeAlias implements TypeAlias
{
    /**
     * @var list<Atomic>
     */
    public array $replacement_atomic_types;

    /**
     * @param list<Atomic> $replacement_atomic_types
     */
    public function __construct(array $replacement_atomic_types)
    {
        $this->replacement_atomic_types = $replacement_atomic_types;
    }
}
