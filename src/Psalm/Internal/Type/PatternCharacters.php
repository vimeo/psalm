<?php

declare(strict_types=1);

namespace Psalm\Internal\Type;

use Psalm\Internal\Codebase\StringContext;
use Psalm\Type\TaintKind;

use function array_fill;
use function chr;
use function count;
use function ctype_alnum;
use function ctype_digit;
use function hexdec;
use function in_array;
use function ord;
use function preg_match;
use function str_contains;
use function str_split;
use function strlen;
use function strpbrk;
use function strpos;
use function strrpos;
use function strspn;
use function strtolower;
use function strtoupper;
use function substr;

/**
 * The characters a string can hold once it matches a regular expression anchored at both ends, such as
 * `/^[a-z0-9_-]+$/`, and the taints such a string can't carry. Only a simple subset of the PCRE syntax is understood:
 * literals, classes, `.`, the `\d`, `\w` and `\s` escapes, groups of alternatives and quantifiers. Any other pattern
 * says nothing.
 *
 * @internal
 * @psalm-capabilities read-props|write-this-props|write-refs
 */
final class PatternCharacters
{
    /**
     * The characters each taint needs: a string holding none of them can't carry it. A taint missing here isn't
     * removed by any pattern (the callable name `system` or the host `evil.example` need only letters and dots).
     */
    private const TAINT_CHARACTERS = [
        TaintKind::INPUT_HTML => '<>',
        TaintKind::INPUT_HAS_QUOTES => '\'"`',
        TaintKind::INPUT_LDAP => "*()\\,+\"<>;=\0",
        // whitespace makes an unquoted predicate `1 or 1`
        TaintKind::INPUT_XPATH => "'\"[]()=|*@/:<>!$, \t\n\r\x0B\x0C",
        TaintKind::INPUT_XPATH_LITERAL => '\'"',
        // `..` is the parent directory wherever the path puts it
        TaintKind::INPUT_FILE => "/\\.\0",
        TaintKind::INPUT_INCLUDE => "/\\.\0",
        TaintKind::INPUT_UNSERIALIZE => ':',
        TaintKind::INPUT_URL_COMPONENT => "/?&#%=;+\\ ",
        TaintKind::INPUT_URL_PATH => '.',
        TaintKind::INPUT_HEADER => "\r\n",
        TaintKind::INPUT_COOKIE => ";,= \r\n",
        TaintKind::INPUT_HTML_ATTRIBUTE => " \t\n\r\x0C>",
        TaintKind::INPUT_URL_SCHEME => ':',
    ];

    /**
     * The taints a newline can carry even as the last character, which `$` lets a string end with
     */
    private const TAINTS_OF_A_TRAILING_NEWLINE = TaintKind::INPUT_HEADER | TaintKind::INPUT_COOKIE;

    /**
     * The characters a value can hold and still be no SQL or shell syntax: a word, a number, a dotted name; for a
     * shell argument, a path too. A trailing newline ends a shell command.
     */
    private const SQL_SAFE_CHARACTERS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_.';
    private const SHELL_SAFE_CHARACTERS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_.,:/=+@%';

    /** @var array<int, bool> the bytes the pattern matches so far */
    private array $chars;

    private int $offset = 0;

    /**
     * @psalm-capabilities read-props
     */
    private function __construct(
        private readonly string $body,
        private readonly bool $case_insensitive,
        private readonly bool $dot_all,
        private readonly bool $unicode,
    ) {
        $this->chars = array_fill(0, 256, false);
    }

    /**
     * The taints a string matching $pattern can't carry: none if the analysis can't tell which characters it holds
     *
     * @psalm-pure
     */
    public static function getTaintsRemoved(string $pattern): int
    {
        $characters = self::getCharacters($pattern);

        if ($characters === null) {
            return 0;
        }

        [$chars, $trailing_newline] = $characters;

        $removed_taints = 0;

        foreach (self::TAINT_CHARACTERS as $taint => $needed) {
            if (!self::holdsAnyOf($chars, $needed)
                && !($trailing_newline && ($taint & self::TAINTS_OF_A_TRAILING_NEWLINE) !== 0)
            ) {
                $removed_taints |= $taint;
            }
        }

        if (self::holdsOnly($chars, self::SQL_SAFE_CHARACTERS)) {
            $removed_taints |= TaintKind::INPUT_SQL | TaintKind::INPUT_SQL_UNQUOTED;
        }

        if (!$trailing_newline && self::holdsOnly($chars, self::SHELL_SAFE_CHARACTERS)) {
            $removed_taints |= TaintKind::INPUT_SHELL;
        }

        return $removed_taints;
    }

    /**
     * The taints a string matching $pattern can't carry because of how the pattern makes it start: a pattern such as
     * `~^https?://~` or `~^/~` fixes the scheme of a URL. Only literals, `x?` and a first group of alternatives
     * (`^(?:https|mailto):`) are understood.
     *
     * @psalm-pure
     */
    public static function getTaintsRemovedByStart(string $pattern): int
    {
        if (strlen($pattern) < 3 || ctype_alnum($pattern[0]) || $pattern[0] === '\\' || $pattern[0] === ' ') {
            return 0;
        }

        $closing_delimiter = ['(' => ')', '[' => ']', '{' => '}', '<' => '>'][$pattern[0]] ?? $pattern[0];
        $end = strrpos($pattern, $closing_delimiter);

        if ($end === false || $end === 0) {
            return 0;
        }

        $modifiers = substr($pattern, $end + 1);
        $body = substr($pattern, 1, $end - 1);

        // m anchors every line, x changes what whitespace means
        if (strspn($modifiers, 'iusDUSA') !== strlen($modifiers)) {
            return 0;
        }

        if (str_contains($modifiers, 'A')) {
            $offset = 0;
        } elseif (($body[0] ?? '') === '^') {
            $offset = 1;
        } elseif (substr($body, 0, 2) === '\\A') {
            $offset = 2;
        } else {
            return 0;
        }

        $starts = [''];

        if (($body[$offset] ?? '') === '(') {
            $offset += substr($body, $offset, 3) === '(?:' ? 3 : 1;
            $alternatives = [];

            do {
                $alternative = self::getLiteralStarts($body, $offset);

                if ($alternative === null) {
                    return 0;
                }

                [$alternative_starts, $offset] = $alternative;
                $alternatives = [...$alternatives, ...$alternative_starts];
                $separator = $body[$offset++] ?? '';
            } while ($separator === '|');

            // a group that can be left out, or repeated, starts with anything
            if ($separator !== ')' || in_array($body[$offset] ?? '', ['?', '*', '+', '{'], true)) {
                return 0;
            }

            $starts = $alternatives;
        }

        $rest = self::getLiteralStarts($body, $offset);

        if ($rest === null) {
            return 0;
        }

        foreach ($starts as $start) {
            foreach ($rest[0] as $end_of_start) {
                if (!StringContext::fixesUrlScheme($start . $end_of_start)) {
                    return 0;
                }
            }
        }

        return TaintKind::INPUT_URL_SCHEME;
    }

    /**
     * The literal strings the part of $body from $offset up to the first character that isn't a literal or `?` can
     * match, and the offset of that character; null for too many strings
     *
     * @return array{non-empty-list<string>, int}|null
     * @psalm-pure
     */
    private static function getLiteralStarts(string $body, int $offset): ?array
    {
        $starts = [''];

        for ($length = strlen($body); $offset < $length; $offset++) {
            $char = $body[$offset];

            if ($char === '\\') {
                $escaped = $body[$offset + 1] ?? '';

                // \d, \w, \s, ... are classes
                if ($escaped === '' || ctype_alnum($escaped)) {
                    break;
                }

                $char = $escaped;
                $offset++;
            } elseif (strpbrk($char, '.[]()|^$*+{}?') !== false) {
                break;
            }

            $quantifier = $body[$offset + 1] ?? '';

            // repeated any number of times, it may not be there
            if ($quantifier === '*' || $quantifier === '{') {
                break;
            }

            $optional = $quantifier === '?';
            $next_starts = [];

            foreach ($starts as $start) {
                $next_starts[] = $start . $char;

                if ($optional) {
                    $next_starts[] = $start;
                }
            }

            if (count($next_starts) > 16) {
                return null;
            }

            $starts = $next_starts;
            $offset += $optional ? 1 : 0;
        }

        return [$starts, $offset];
    }

    /**
     * The bytes a string matching $pattern can hold, and whether it can end with a newline the pattern doesn't
     * match (`$` matches before one), or null if the analysis can't tell
     *
     * @return array{array<int, bool>, bool}|null
     * @psalm-pure
     */
    public static function getCharacters(string $pattern): ?array
    {
        if (strlen($pattern) < 3) {
            return null;
        }

        $delimiter = $pattern[0];

        if (ctype_alnum($delimiter) || $delimiter === '\\' || $delimiter === ' ') {
            return null;
        }

        $closing_delimiter = ['(' => ')', '[' => ']', '{' => '}', '<' => '>'][$delimiter] ?? $delimiter;
        $end = strrpos($pattern, $closing_delimiter);

        if ($end === false || $end === 0) {
            return null;
        }

        $modifiers = substr($pattern, $end + 1);

        for ($i = 0, $length = strlen($modifiers); $i < $length; $i++) {
            // m anchors every line, x changes what whitespace means
            if (!in_array($modifiers[$i], ['i', 'u', 's', 'D', 'U', 'S', 'A'], true)) {
                return null;
            }
        }

        $body = substr($pattern, 1, $end - 1);

        // anchored at the start
        if (str_contains($modifiers, 'A')) {
            $start = 0;
        } elseif (($body[0] ?? '') === '^') {
            $start = 1;
        } elseif (substr($body, 0, 2) === '\\A') {
            $start = 2;
        } else {
            return null;
        }

        // anchored at the end, with or without a trailing newline
        if (substr($body, -2) === '\\z' && !self::isEscaped($body, strlen($body) - 2)) {
            $body_end = strlen($body) - 2;
            $trailing_newline = false;
        } elseif (substr($body, -2) === '\\Z' && !self::isEscaped($body, strlen($body) - 2)) {
            $body_end = strlen($body) - 2;
            $trailing_newline = true;
        } elseif (substr($body, -1) === '$' && !self::isEscaped($body, strlen($body) - 1)) {
            $body_end = strlen($body) - 1;
            $trailing_newline = !str_contains($modifiers, 'D');
        } else {
            return null;
        }

        $matcher = new self(
            substr($body, $start, $body_end - $start),
            str_contains($modifiers, 'i'),
            str_contains($modifiers, 's'),
            str_contains($modifiers, 'u'),
        );

        if (!$matcher->parseAlternatives(false) || $matcher->offset !== strlen($matcher->body)) {
            return null;
        }

        return [$matcher->chars, $trailing_newline];
    }

    /**
     * @param array<int, bool> $chars
     * @psalm-pure
     */
    private static function holdsAnyOf(array $chars, string $needed): bool
    {
        for ($i = 0, $length = strlen($needed); $i < $length; $i++) {
            if ($chars[ord($needed[$i])]) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, bool> $chars
     * @psalm-pure
     */
    private static function holdsOnly(array $chars, string $allowed): bool
    {
        foreach ($chars as $byte => $held) {
            if ($held && strpos($allowed, chr($byte)) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * @psalm-pure
     */
    private static function isEscaped(string $body, int $offset): bool
    {
        $backslashes = 0;

        while ($offset - $backslashes - 1 >= 0 && $body[$offset - $backslashes - 1] === '\\') {
            $backslashes++;
        }

        return $backslashes % 2 === 1;
    }

    /**
     * Alternatives separated by `|`, up to the end of the body or, in a group, its `)`; anchors inside are not
     * understood. At the top level `^a|b$` is `(^a)|(b$)`, which isn't anchored.
     *
     * @psalm-external-mutation-free
     */
    private function parseAlternatives(bool $in_group): bool
    {
        while ($this->offset < strlen($this->body)) {
            $char = $this->body[$this->offset];

            if ($char === ')') {
                return $in_group;
            }

            if ($char === '|') {
                if (!$in_group) {
                    return false;
                }

                $this->offset++;
                continue;
            }

            if (!$this->parseAtom() || !$this->parseQuantifier()) {
                return false;
            }
        }

        return !$in_group;
    }

    /**
     * @psalm-external-mutation-free
     */
    private function parseAtom(): bool
    {
        $char = $this->body[$this->offset];

        if ($char === '(') {
            $this->offset++;

            if (($this->body[$this->offset] ?? '') === '?') {
                // only non-capturing and named groups: no lookaround, no inline modifier
                if (($this->body[$this->offset + 1] ?? '') === ':') {
                    $this->offset += 2;
                } elseif (substr($this->body, $this->offset, 2) === '?<'
                    || substr($this->body, $this->offset, 3) === '?P<'
                ) {
                    $name_end = strpos($this->body, '>', $this->offset);

                    if ($name_end === false || in_array($this->body[$this->offset + 2] ?? '', ['=', '!'], true)) {
                        return false;
                    }

                    $this->offset = $name_end + 1;
                } else {
                    return false;
                }
            }

            if (!$this->parseAlternatives(true)) {
                return false;
            }

            $this->offset++;

            return true;
        }

        if ($char === '[') {
            return $this->parseClass();
        }

        if ($char === '.') {
            $this->offset++;

            for ($byte = 0; $byte < 256; $byte++) {
                if ($byte !== 10 || $this->dot_all) {
                    $this->chars[$byte] = true;
                }
            }

            return true;
        }

        if ($char === '\\') {
            return $this->parseEscape();
        }

        if (in_array($char, ['^', '$', '*', '+', '?', '{', '}', ']'], true)) {
            return false;
        }

        $this->offset++;
        $this->addChar($char);

        return true;
    }

    /**
     * `*`, `+`, `?`, `{n}`, `{n,}` or `{n,m}`, maybe lazy or possessive: they repeat what the atom matches, so the
     * characters stay the same
     *
     * @psalm-external-mutation-free
     */
    private function parseQuantifier(): bool
    {
        $char = $this->body[$this->offset] ?? '';

        if ($char === '*' || $char === '+' || $char === '?') {
            $this->offset++;
        } elseif ($char === '{') {
            // a brace that doesn't start a quantifier is a literal
            if (preg_match('/\G\{(\d+(,\d*)?|,\d+)\}/', $this->body, $matches, 0, $this->offset) !== 1) {
                return false;
            }

            $this->offset += strlen($matches[0] ?? '');
        } else {
            return true;
        }

        $next = $this->body[$this->offset] ?? '';

        if ($next === '?' || $next === '+') {
            $this->offset++;
        }

        return true;
    }

    /**
     * @psalm-external-mutation-free
     */
    private function parseClass(): bool
    {
        $this->offset++;

        $negated = ($this->body[$this->offset] ?? '') === '^';

        if ($negated) {
            $this->offset++;
        }

        // the characters of the class, collected in $this->chars
        $chars = $this->chars;
        $this->chars = array_fill(0, 256, false);
        $first = true;

        while (true) {
            if ($this->offset >= strlen($this->body)) {
                return false;
            }

            $char = $this->body[$this->offset];


            if ($char === ']' && !$first) {
                $this->offset++;
                break;
            }

            $first = false;

            if ($char === '[') {
                // POSIX classes ([:alpha:]) aren't understood
                return false;
            }

            if ($char === '\\') {
                $escape = $this->body[$this->offset + 1] ?? '';

                if (in_array($escape, ['d', 'w', 's', 'v', 'D', 'W', 'S'], true)) {
                    // the complement of almost every character isn't worked out
                    if ($negated && in_array($escape, ['D', 'W', 'S'], true)) {
                        return false;
                    }

                    if (!$this->parseEscape()) {
                        return false;
                    }

                    continue;
                }

                $from = $this->parseEscapedLiteral();
            } else {
                $this->offset++;
                $from = $char;
            }

            if ($from === null) {
                return false;
            }

            if (($this->body[$this->offset] ?? '') === '-' && ($this->body[$this->offset + 1] ?? ']') !== ']') {
                $this->offset++;
                $to_char = $this->body[$this->offset];

                if ($to_char === '\\') {
                    $to = $this->parseEscapedLiteral();
                } else {
                    $this->offset++;
                    $to = $to_char;
                }

                if ($to === null || ord($to) < ord($from)) {
                    return false;
                }

                for ($byte = ord($from); $byte <= ord($to); $byte++) {
                    $this->addChar(chr($byte));
                }
            } else {
                $this->addChar($from);
            }
        }

        if ($negated && $this->unicode) {
            // a character that isn't one of those listed can be any multibyte one
            for ($byte = 128; $byte < 256; $byte++) {
                $this->chars[$byte] = false;
            }
        }

        foreach ($this->chars as $byte => $held) {
            if ($held !== $negated) {
                $chars[$byte] = true;
            }
        }

        $this->chars = $chars;

        return true;
    }

    /**
     * An escape: `\d`, `\w`, `\s` and their negations, `\b` and `\B`, or an escaped literal
     *
     * @psalm-external-mutation-free
     */
    private function parseEscape(): bool
    {
        $escape = $this->body[$this->offset + 1] ?? '';

        $set = match ($escape) {
            'd' => '0123456789',
            'w' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_',
            's' => " \t\n\r\x0B\x0C",
            // vertical whitespace, also the multibyte NEL and line and paragraph separators
            'v' => "\n\r\x0B\x0C\x85",
            default => null,
        };

        if ($set !== null) {
            $this->offset += 2;

            foreach (str_split($set) as $char) {
                $this->chars[ord($char)] = true;
            }

            if (($escape === 'w' || $escape === 'v') && $this->unicode) {
                // the letters of other scripts, the multibyte separators
                for ($byte = 128; $byte < 256; $byte++) {
                    $this->chars[$byte] = true;
                }
            }

            return true;
        }

        if ($escape === 'D' || $escape === 'W' || $escape === 'S') {
            // everything but a few characters
            $this->offset += 2;

            for ($byte = 0; $byte < 256; $byte++) {
                $this->chars[$byte] = true;
            }

            return true;
        }

        if ($escape === 'b' || $escape === 'B') {
            $this->offset += 2;

            return true;
        }

        $literal = $this->parseEscapedLiteral();

        if ($literal === null) {
            return false;
        }

        $this->addChar($literal);

        return true;
    }

    /**
     * An escaped character: punctuation, `\n`, `\r`, `\t`, `\f`, `\v`, `\e`, `\0` or `\xHH`. Null for any other
     * escape, such as a back reference or a Unicode property.
     *
     * @psalm-external-mutation-free
     */
    private function parseEscapedLiteral(): ?string
    {
        $escape = $this->body[$this->offset + 1] ?? '';

        if ($escape === '') {
            return null;
        }

        if ($escape === 'x') {
            $hex = substr($this->body, $this->offset + 2, 2);

            if (strlen($hex) !== 2 || !ctype_alnum($hex) || hexdec($hex) > 255) {
                return null;
            }

            $this->offset += 4;

            return chr((int) hexdec($hex));
        }

        // `\v` is a class, `\0` followed by digits an octal escape
        $special = ['n' => "\n", 'r' => "\r", 't' => "\t", 'f' => "\f", 'e' => "\e", '0' => "\0"];

        if ($escape === '0' && ctype_digit($this->body[$this->offset + 2] ?? '')) {
            return null;
        }

        if (isset($special[$escape])) {
            $this->offset += 2;

            return $special[$escape];
        }

        if (ctype_alnum($escape)) {
            return null;
        }

        $this->offset += 2;

        return $escape;
    }

    /**
     * @psalm-external-mutation-free
     */
    private function addChar(string $char): void
    {
        $this->chars[ord($char)] = true;

        if ($this->case_insensitive) {
            $this->chars[ord(strtolower($char))] = true;
            $this->chars[ord(strtoupper($char))] = true;
        }
    }
}
