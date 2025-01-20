<?php

declare(strict_types=1);

namespace Psalm\Internal;

use InvalidArgumentException;
use Psalm\Storage\ImmutableNonCloneableTrait;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;
use Stringable;

use function explode;
use function is_string;
use function ltrim;
use function str_contains;
use function strtolower;

/**
 * @psalm-immutable
 * @internal
 */
final class MethodIdentifier implements Stringable
{
    use ImmutableNonCloneableTrait;
    use UnserializeMemoryUsageSuppressionTrait;

    /**
     * @param lowercase-string $method_name
     */
    public function __construct(public readonly string $fq_class_name, public readonly string $method_name)
    {
    }

    /**
     * Takes any valid reference to a method id and converts
     * it into a MethodIdentifier
     *
     * @psalm-pure
     */
    public static function wrap(string|MethodIdentifier $method_id): self
    {
        return is_string($method_id) ? static::fromMethodIdReference($method_id) : $method_id;
    }

    /**
     * @psalm-pure
     */
    public static function isValidMethodIdReference(string $method_id): bool
    {
        return str_contains($method_id, '::');
    }

    /**
     * @psalm-pure
     */
    public static function fromMethodIdReference(string $method_id): self
    {
        if (!static::isValidMethodIdReference($method_id)) {
            throw new InvalidArgumentException('Invalid method id reference provided: ' . $method_id);
        }
        // remove leading backslash if it exists
        $method_id = ltrim($method_id, '\\');
        $method_id_parts = explode('::', $method_id);
        return new self($method_id_parts[0], strtolower($method_id_parts[1]));
    }

    /** @return non-empty-string */
    public function __toString(): string
    {
        return $this->fq_class_name . '::' . $this->method_name;
    }
}
