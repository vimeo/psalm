<?php

declare(strict_types=1);

namespace Psalm\Storage;

use Psalm\CodeLocation;

/**
 * Storage for property hooks ('get' & 'set') introduced in PHP 8.4
 *
 * @psalm-immutable
 */
final class PropertyHookStorage
{
    use UnserializeMemoryUsageSuppressionTrait;

    public function __construct(
        public bool $is_get,
        public bool $is_final,
        public bool $by_ref,
        public ?CodeLocation $location,
    ) {
    }
}
