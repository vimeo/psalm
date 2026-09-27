<?php

declare(strict_types=1);

namespace Psalm\Internal;

use InvalidArgumentException;
use Override;
use Psalm\Interner;
use Psalm\Storage\ImmutableNonCloneableTrait;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;
use Stringable;

use function explode;
use function ltrim;
use function str_contains;

/**
 * @psalm-immutable
 * @internal
 */
final class MethodIdentifier implements Stringable
{
    use ImmutableNonCloneableTrait;
    use UnserializeMemoryUsageSuppressionTrait;

    /**
     * @param int $fq_class_name interned class name
     * @param int $method_name interned method name, as declared/written (case-sensitive)
     * @psalm-mutation-free
     */
    public function __construct(public readonly int $fq_class_name, public readonly int $method_name)
    {
    }

    /**
     * @psalm-pure
     */
    public static function isValidMethodIdReference(string $method_id): bool
    {
        return str_contains($method_id, '::');
    }

    /**
     * Parses a Foo::bar method reference string (e.g. from a callable string or from user input).
     *
     * @psalm-pure
     */
    public static function fromMethodIdReference(string $method_id): self
    {
        if (!self::isValidMethodIdReference($method_id)) {
            throw new InvalidArgumentException('Invalid method id reference provided: ' . $method_id);
        }
        // remove leading backslash if it exists
        $method_id = ltrim($method_id, '\\');
        $method_id_parts = explode('::', $method_id);
        return new self(Interner::intern($method_id_parts[0]), Interner::intern($method_id_parts[1]));
    }

    /**
     * @psalm-mutation-free
     */
    public function equals(self $other): bool
    {
        return $this->method_name === $other->method_name
            && $this->fq_class_name === $other->fq_class_name;
    }

    /** @var array<int, array<int, non-empty-string>> */
    private static array $strings = [];

    /**
     * @return non-empty-string
     * @psalm-suppress ImpureStaticProperty memoization only
     */
    #[Override]
    public function __toString(): string
    {
        return self::$strings[$this->fq_class_name][$this->method_name]
            ??= Interner::$strings[$this->fq_class_name] . '::' . Interner::$strings[$this->method_name];
    }
}
