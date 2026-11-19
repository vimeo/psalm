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
final class NestedAssertions extends Assertion
{
    use UnserializeMemoryUsageSuppressionTrait;
    /** @param array<string, list<list<Assertion>>> $assertions */
    public function __construct(public readonly array $assertions)
    {
    }

    #[Override]
    public function getNegation(): Assertion
    {
        return new NotNestedAssertions($this->assertions);
    }

    public function __toString(): string
    {
        return '@' . self::getFormulaString($this->assertions);
    }

    /**
     * The nested assertions by their string form: encoding the objects would drop their class
     *
     * @internal
     * @param array<string, list<list<Assertion>>> $assertions
     */
    public static function getFormulaString(array $assertions): string
    {
        $strings = [];

        foreach ($assertions as $var_id => $anded_assertions) {
            foreach ($anded_assertions as $i => $orred_assertions) {
                foreach ($orred_assertions as $assertion) {
                    $strings[$var_id][$i][] = (string) $assertion;
                }
            }
        }

        try {
            return json_encode($strings, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            // Keep binary assertions lossless and printable in condition diagnostics.
            return bin2hex(var_export($strings, true));
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
