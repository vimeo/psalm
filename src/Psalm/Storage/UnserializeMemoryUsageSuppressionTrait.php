<?php

namespace Psalm\Storage;

/**
 * Suppresses memory usage when unserializing objects.
 *
 * Workaround for the problem that objects retrieved with `\unserialize()`
 * build unnecessary dynamic property tables, resulting in larger memory
 * consumption.
 *
 * @see https://github.com/php/php-src/issues/10126
 * @psalm-immutable
 */
trait UnserializeMemoryUsageSuppressionTrait
{
    public function __unserialize(array $properties): void
    {
        /** @psalm-suppress MixedAssignment */
        foreach ($properties as $key => $value) {
            $this->$key = $value;
        }
    }
}
