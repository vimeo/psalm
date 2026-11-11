<?php

declare(strict_types=1);

namespace Psalm\Storage\Assertion;

use JsonException;
use Override;
use Psalm\Storage\Assertion;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;

use function bin2hex;
use function json_encode;
use function var_export;

use const JSON_THROW_ON_ERROR;

/**
 * @psalm-immutable
 * @api
 */
final class NotNestedAssertions extends Assertion
{
    use UnserializeMemoryUsageSuppressionTrait;
    /** @param array<string, list<list<Assertion>>> $assertions */
    public function __construct(public readonly array $assertions)
    {
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
    public function getNegation(): Assertion
    {
        return new NestedAssertions($this->assertions);
    }

    public function __toString(): string
    {
        try {
            return '!@' . json_encode($this->assertions, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            // Keep binary assertions lossless and printable in condition diagnostics.
            return '!@' . bin2hex(var_export($this->assertions, true));
        }
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function isNegationOf(Assertion $assertion): bool
    {
        return false;
    }
}
