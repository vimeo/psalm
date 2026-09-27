<?php

declare(strict_types=1);

namespace Psalm\Issue;

use Psalm\Interner;

use function array_pop;
use function array_unique;
use function array_values;
use function count;
use function implode;
use function reset;

/**
 * @api
 */
final class InternalClass extends ClassIssue
{
    public const ERROR_LEVEL = 4;
    public const SHORTCODE = 174;

    /**
     * @param list<int> $ids interned names
     * @psalm-pure
     */
    public static function listToPhrase(array $ids): string
    {
        $words = array_values(array_unique(Interner::strAll($ids)));
        if (!$words) {
            return '';
        }
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
