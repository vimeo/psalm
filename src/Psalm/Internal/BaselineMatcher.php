<?php

declare(strict_types=1);

namespace Psalm\Internal;

use Psalm\Config;

use function array_search;
use function array_splice;
use function count;
use function htmlspecialchars;
use function htmlspecialchars_decode;
use function str_replace;
use function trim;

use const ENT_DISALLOWED;
use const ENT_QUOTES;
use const ENT_SUBSTITUTE;
use const ENT_XML1;

/**
 * How a baseline covers issues, one at a time: whatever an issue takes from the baseline is not there for the next
 *
 * @internal
 * @psalm-capabilities read-props|write-this-props|write-refs
 */
final class BaselineMatcher
{
    /**
     * Whether the baseline covers an issue of a type the configuration lets a baseline suppress; the entry it
     * takes is used up
     *
     * @param array<string, array<string, array{o: int, s: array<int, string>}>> $issue_baseline
     * @param string $file_name relative to the project root, with either separator
     * @psalm-capabilities read-props|write-refs
     */
    public static function consume(
        Config $config,
        array &$issue_baseline,
        string $file_name,
        string $type,
        string $selected_text,
    ): bool {
        if (!$config->isSuppressible($type)) {
            return false;
        }

        $file = str_replace('\\', '/', $file_name);

        if (!isset($issue_baseline[$file][$type]) || $issue_baseline[$file][$type]['o'] <= 0) {
            return false;
        }

        if ($issue_baseline[$file][$type]['o'] === count($issue_baseline[$file][$type]['s'])) {
            $position = array_search(
                str_replace("\r\n", "\n", self::normalizeSelectedText(trim($selected_text))),
                $issue_baseline[$file][$type]['s'],
                true,
            );

            if ($position === false) {
                return false;
            }

            array_splice($issue_baseline[$file][$type]['s'], $position, 1);
            $issue_baseline[$file][$type]['o']--;

            return true;
        }

        $issue_baseline[$file][$type]['s'] = [];
        $issue_baseline[$file][$type]['o']--;

        return true;
    }

    /**
     * The selected text of an issue as the baseline holds it: characters XML 1.0 forbids, even inside a CDATA
     * section, and invalid UTF-8 become U+FFFD. The text written to the baseline and the text of an issue are
     * compared in this form.
     *
     * @psalm-pure
     */
    public static function normalizeSelectedText(string $selected_text): string
    {
        return htmlspecialchars_decode(
            htmlspecialchars($selected_text, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE | ENT_DISALLOWED),
            ENT_XML1 | ENT_QUOTES,
        );
    }
}
