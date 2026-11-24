<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;

/**
 * Denotes a `scalar` type that is also non-empty.
 *
 * @psalm-immutable
 * @api
 */
final class TNonEmptyScalar extends TScalar
{
    /**
     * @psalm-pure
     */
    #[Override]
    public function getId(bool $exact = true): string
    {
        return 'non-empty-scalar';
    }
}
