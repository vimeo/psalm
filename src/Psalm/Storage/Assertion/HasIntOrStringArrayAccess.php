<?php

declare(strict_types=1);

namespace Psalm\Storage\Assertion;

use Override;
use Psalm\Storage\Assertion;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;
use UnexpectedValueException;

/**
 * @psalm-immutable
 * @api
 */
final class HasIntOrStringArrayAccess extends Assertion
{
    use UnserializeMemoryUsageSuppressionTrait;

    /**
     * @param bool $int_key unused: kept so that plugins creating the assertion keep working
     */
    public function __construct(public readonly bool $int_key = false)
    {
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function getNegation(): Assertion
    {
        throw new UnexpectedValueException('This should never be called');
    }

    /**
     * @psalm-pure
     */
    public function __toString(): string
    {
        return 'has-string-or-int-array-access';
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
