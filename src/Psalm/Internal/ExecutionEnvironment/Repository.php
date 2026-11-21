<?php

declare(strict_types=1);

namespace Psalm\Internal\ExecutionEnvironment;

use function count;
use function explode;

/**
 * A repository of a code hosting service, named like "owner/name".
 *
 * @psalm-immutable
 * @internal
 */
final class Repository
{
    public function __construct(
        public readonly string $owner,
        public readonly string $name,
    ) {
    }

    /**
     * The repository of an "owner/name" slug, or null when the slug is not exactly that.
     *
     * @psalm-pure
     */
    public static function fromSlug(string $slug): ?self
    {
        $parts = explode('/', $slug);

        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            return null;
        }

        return new self($parts[0], $parts[1]);
    }
}
