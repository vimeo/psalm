<?php

namespace Psalm\Report\PrettyPrintArray;

use function str_replace;

final class PrettyHelper
{
    public static function normalizeBracket(string $payload): string
    {
        return str_replace(
            ['<', '>'],
            ['{', '}'],
            $payload
        );
    }

    public static function normalizeTokens(string $payload): string
    {
        return str_replace('array-key', 'psalm-key', $payload);
    }

    public static function revertNormalizedTokens(string $payload): string
    {
        return str_replace('psalm-key', 'array-key', $payload);
    }
}
