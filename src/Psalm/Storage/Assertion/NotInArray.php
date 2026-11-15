<?php

declare(strict_types=1);

namespace Psalm\Storage\Assertion;

use Override;
use Psalm\Storage\Assertion;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;
use Psalm\Type\Union;

/**
 * @psalm-immutable
 * @api
 */
final class NotInArray extends Assertion
{
    use UnserializeMemoryUsageSuppressionTrait;
    /**
     * @param ?string $haystack the array the value is looked up in
     */
    public function __construct(
        public readonly Union $type,
        public readonly ?string $haystack = null,
    ) {
    }

    #[Override]
    public function getNegation(): Assertion
    {
        return new InArray($this->type, $this->haystack);
    }

    public function __toString(): string
    {
        return '!in-array-' . $this->type->getId()
            . ($this->haystack === null ? '' : '@' . $this->haystack);
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function isNegation(): bool
    {
        return true;
    }

    #[Override]
    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof InArray
            && $this->type->getId() === $assertion->type->getId()
            && $this->haystack === $assertion->haystack;
    }
}
