<?php

declare(strict_types=1);

namespace Psalm\Storage;

use Psalm\Internal\SerializedProperties;

use function is_string;

/**
 * Suppresses memory usage when serializing and unserializing objects.
 *
 * Workaround for the problem that objects serialized with `igbinary_serialize()` or retrieved with `\unserialize()`
 * build unnecessary property tables, which they keep, resulting in larger memory consumption. `(array) $this` reads
 * the properties without building the table.
 *
 * @see https://github.com/php/php-src/issues/10126
 * @psalm-immutable
 * @api
 */
trait UnserializeMemoryUsageSuppressionTrait
{
    /**
     * @psalm-mutation-free
     */
    public function __serialize(): array
    {
        return (array) $this;
    }

    /**
     * @param array<string, mixed> $properties
     * @psalm-external-mutation-free
     */
    public function __unserialize(array $properties): void
    {
        // Read on every unserialized object, so not through a method call. A class's own __unserialize() wins over
        // its parents', so static::class always comes with the same self::class.
        /** @psalm-suppress ImpureStaticProperty Used for caching */
        $special_keys = SerializedProperties::$special_keys[static::class]
            ??= SerializedProperties::findSpecialKeys(static::class, self::class);

        if ($special_keys === []) {
            /** @psalm-suppress MixedAssignment */
            foreach ($properties as $key => $value) {
                $this->$key = $value;
            }

            return;
        }

        /** @psalm-suppress MixedAssignment */
        foreach ($properties as $key => $value) {
            $special_key = $special_keys[$key] ?? $key;

            if (is_string($special_key)) {
                $this->$special_key = $value;
            } else {
                /** @psalm-suppress ImpureMethodCall It initializes a property of $this */
                $special_key->setValue($this, $value);
            }
        }
    }
}
