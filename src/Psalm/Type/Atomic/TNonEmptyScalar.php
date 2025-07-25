<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;

/**
 * Denotes a `scalar` type that is also non-empty.
 *
 * @psalm-immutable
 */
final class TNonEmptyScalar extends TScalar
{
    #[Override]
    public function getId(bool $exact = true, bool $nested = false): string
    {
        return 'non-empty-scalar';
    }
}
