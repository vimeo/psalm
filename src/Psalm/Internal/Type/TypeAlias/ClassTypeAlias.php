<?php

declare(strict_types=1);

namespace Psalm\Internal\Type\TypeAlias;

use Psalm\Internal\Type\TypeAlias;
use Psalm\Type\Atomic;

/**
 * @internal
 */
final class ClassTypeAlias implements TypeAlias
{
    /**
     * @param list<Atomic> $replacement_atomic_types
     */
    public function __construct(public array $replacement_atomic_types)
    {
    }
}
