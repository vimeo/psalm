<?php

namespace Psalm\Issue;

use function array_pop;
use function array_unique;
use function count;
use function implode;
use function reset;

final class InternalClass extends ClassIssue
{
    public const ERROR_LEVEL = 4;
    public const SHORTCODE = 174;

    /** @param non-empty-list<non-empty-string> $words */
    public static function listToPhrase(array $words): string
    {
        $words = array_unique($words);
        if (count($words) === 1) {
            return reset($words);
        }

        if (count($words) === 2) {
            return implode(" and ", $words);
        }

        $last_word = array_pop($words);
        $phrase = implode(", ", $words);
        $phrase = "$phrase, and $last_word";

        return $phrase;
    }
}
