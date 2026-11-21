<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use PhpParser\Node\Expr;
use Psalm\Internal\Analyzer\Statements\Expression\BinaryOp\ConcatAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Type\TaintKind;

use function ctype_alnum;
use function ctype_alpha;
use function ord;
use function preg_match;
use function preg_match_all;
use function preg_split;
use function strlen;
use function strpbrk;
use function strspn;
use function strtolower;
use function substr;
use function substr_compare;

use const PREG_OFFSET_CAPTURE;
use const PREG_SPLIT_DELIM_CAPTURE;

/**
 * Where the text known of a string ends, for the values embedded after it: inside or outside the quotes of SQL, and
 * where in HTML markup. An escaping function only makes a value safe for some positions: what real_escape_string()
 * returns is only safe in SQL quotes, what htmlspecialchars() returns can still end an unquoted attribute value, or
 * be a `javascript:` URL.
 *
 * A string whose start is literal text starts outside SQL quotes, in HTML text. A value embedded in it is taken to
 * change neither (an escaped value has no quote or `<`), except that it ends the scheme of a URL. Where the text is not
 * known, nothing is: a start tag in the text after it tells where in HTML markup the string is again.
 *
 * @internal
 * @psalm-immutable
 */
final class StringContext
{
    private const SQL_OUTSIDE = 0;
    private const SQL_SINGLE_QUOTED = 1;
    private const SQL_DOUBLE_QUOTED = 2;
    private const SQL_BACKTICKED = 3;

    private const HTML_TEXT = 0;
    private const HTML_COMMENT = 1;
    private const HTML_END_TAG = 2;
    private const HTML_TAG = 3;
    private const HTML_ATTRIBUTE_NAME = 4;
    private const HTML_AFTER_ATTRIBUTE_NAME = 5;
    private const HTML_BEFORE_VALUE = 6;
    private const HTML_UNQUOTED_VALUE = 7;
    private const HTML_SINGLE_QUOTED_VALUE = 8;
    private const HTML_DOUBLE_QUOTED_VALUE = 9;

    /** the scheme of a URL value: decided, not started, or started by the letters known so far */
    private const SCHEME_DECIDED = 0;
    private const SCHEME_NOT_STARTED = 1;
    private const SCHEME_STARTED = 2;

    private const HTML_WHITESPACE = " \t\n\r\x0C";

    /**
     * The attributes of each tag whose URL runs code with the `javascript:` scheme: when a link is followed or a form
     * is sent, or when a frame loads it. In an image, a script or a stylesheet, it doesn't.
     */
    private const SCRIPT_URL_ATTRIBUTES = [
        'a' => ['href' => true, 'xlink:href' => true],
        'area' => ['href' => true],
        'form' => ['action' => true],
        'button' => ['formaction' => true],
        'input' => ['formaction' => true],
        'iframe' => ['src' => true],
        'frame' => ['src' => true],
    ];

    /**
     * @psalm-mutation-free
     */
    private function __construct(
        private readonly ?int $sql,
        private readonly ?int $html,
        private readonly string $tag = '',
        private readonly string $attribute = '',
        private readonly int $scheme = self::SCHEME_DECIDED,
    ) {
    }

    /**
     * @psalm-pure
     */
    public static function getUnknown(): self
    {
        return new self(null, null);
    }

    /**
     * After each of the literal strings $values, which start a string
     *
     * @param list<string> $values
     * @psalm-pure
     */
    public static function getForLiterals(array $values): self
    {
        return (new self(self::SQL_OUTSIDE, self::HTML_TEXT))->afterLiterals($values);
    }

    /**
     * Where $expr, already analyzed, ends
     *
     * @psalm-mutation-free
     */
    public static function getForExpression(StatementsAnalyzer $statements_analyzer, Expr $expr): self
    {
        $type = $statements_analyzer->node_data->getType($expr);

        if ($type && $type->allStringLiterals()) {
            return self::getForLiterals(ConcatAnalyzer::getLiteralValues($type));
        }

        // the end of a concatenation or of an interpolated string, recorded when it was analyzed
        return $statements_analyzer->node_data->getStringContext($expr) ?? self::getUnknown();
    }

    /**
     * Where the values a sprintf() format formats are embedded, by index of their argument (1 for the first value),
     * with any of $formats; with $values_in_array (vsprintf()), they are all in argument 1. A value without a
     * position known in all the formats has none.
     *
     * @param list<string> $formats
     * @return array<int, self>
     * @psalm-pure
     */
    public static function getForFormatArguments(array $formats, bool $values_in_array): array
    {
        $contexts = [];
        $unknown = false;

        foreach ($formats as $format) {
            $parts = preg_split(
                '~(%(?:\d+\$)?(?:[-+ 0]|\'.)*\d*(?:\.\d*)?[bcdeEfFgGhHosuxX%]?)~s',
                $format,
                -1,
                PREG_SPLIT_DELIM_CAPTURE,
            ) ?: [];

            $context = new self(self::SQL_OUTSIDE, self::HTML_TEXT);
            $next_arg = 1;
            $format_contexts = [];

            foreach ($parts as $offset => $part) {
                if ($offset % 2 === 0 || $part === '%%') {
                    $context = $context->afterLiteral($offset % 2 === 0 ? $part : '%');

                    continue;
                }

                // `*` width or precision, taken from an argument, or an invalid conversion
                if (!preg_match('~^%(?:(\d+)\$)?.*[bcdeEfFgGhHosuxX]$~s', $part, $matches)) {
                    $unknown = true;

                    break;
                }

                $position = $matches[1] ?? '';
                $arg = $values_in_array ? 1 : ($position !== '' ? (int) $position : $next_arg++);
                $format_contexts[$arg] = isset($format_contexts[$arg])
                    ? $format_contexts[$arg]->merge($context)
                    : $context;
                $context = $context->afterValue();
            }

            foreach ($format_contexts as $arg => $format_context) {
                $contexts[$arg] = isset($contexts[$arg]) ? $contexts[$arg]->merge($format_context) : $format_context;
            }
        }

        return $unknown ? [] : $contexts;
    }

    /**
     * Where the string ends once $expr, already analyzed, is embedded here
     *
     * @psalm-mutation-free
     */
    public function afterExpression(StatementsAnalyzer $statements_analyzer, Expr $expr): self
    {
        $type = $statements_analyzer->node_data->getType($expr);

        if ($type && $type->allStringLiterals()) {
            return $this->afterLiterals(ConcatAnalyzer::getLiteralValues($type));
        }

        return $this->afterValue();
    }

    /**
     * Where the string ends once any of $values is embedded here: where they all end, as far as they agree
     *
     * @param list<string> $values
     * @psalm-mutation-free
     */
    public function afterLiterals(array $values): self
    {
        $context = null;

        foreach ($values as $value) {
            $after = $this->afterLiteral($value);
            $context = $context === null ? $after : $context->merge($after);
        }

        return $context ?? $this;
    }

    /**
     * Where the string ends once a value that isn't literal is embedded here
     *
     * @psalm-mutation-free
     */
    public function afterValue(): self
    {
        return match ($this->html) {
            self::HTML_BEFORE_VALUE => new self($this->sql, self::HTML_UNQUOTED_VALUE, $this->tag, $this->attribute),
            self::HTML_UNQUOTED_VALUE, self::HTML_SINGLE_QUOTED_VALUE, self::HTML_DOUBLE_QUOTED_VALUE
                => new self($this->sql, $this->html, $this->tag, $this->attribute),
            default => $this,
        };
    }

    /**
     * The taints a value embedded here loses: SQL syntax outside quotes, unless the value is known to be outside
     * SQL quotes. Where that isn't known, the escaping of the value is trusted.
     *
     * @psalm-mutation-free
     */
    public function getRemovedTaints(): int
    {
        return $this->sql === self::SQL_OUTSIDE || $this->sql === self::SQL_BACKTICKED
            ? 0
            : TaintKind::INPUT_SQL_UNQUOTED;
    }

    /**
     * The taints a value embedded here is a sink for: what ends an unquoted attribute value, and in a URL running
     * code, a scheme such as `javascript:`
     *
     * @psalm-mutation-free
     */
    public function getSinkTaints(): int
    {
        $taints = 0;

        if ($this->html === self::HTML_BEFORE_VALUE || $this->html === self::HTML_UNQUOTED_VALUE) {
            $taints |= TaintKind::INPUT_HTML_ATTRIBUTE;
        }

        if (($this->html === self::HTML_BEFORE_VALUE || $this->scheme !== self::SCHEME_DECIDED)
            && isset(self::SCRIPT_URL_ATTRIBUTES[$this->tag][$this->attribute])
        ) {
            $taints |= TaintKind::INPUT_URL_SCHEME;
        }

        return $taints;
    }

    /**
     * The attribute whose value the string ends in, if any
     *
     * @psalm-mutation-free
     */
    public function getAttribute(): string
    {
        return $this->attribute;
    }

    /**
     * @psalm-mutation-free
     */
    public function merge(self $other): self
    {
        $same_html = $this->html === $other->html
            && $this->tag === $other->tag
            && $this->attribute === $other->attribute
            && $this->scheme === $other->scheme;

        return new self(
            $this->sql === $other->sql ? $this->sql : null,
            $same_html ? $this->html : null,
            $same_html ? $this->tag : '',
            $same_html ? $this->attribute : '',
            $same_html ? $this->scheme : self::SCHEME_DECIDED,
        );
    }

    /**
     * @psalm-mutation-free
     */
    private function afterLiteral(string $text): self
    {
        return $this->afterHtml($text, $this->afterSql($text));
    }

    /**
     * @psalm-mutation-free
     */
    private function afterSql(string $text): ?int
    {
        $state = $this->sql;

        if ($state === null) {
            return null;
        }

        for ($i = 0, $length = strlen($text); $i < $length; $i++) {
            $char = $text[$i];

            if ($state === self::SQL_OUTSIDE) {
                $state = match ($char) {
                    "'" => self::SQL_SINGLE_QUOTED,
                    '"' => self::SQL_DOUBLE_QUOTED,
                    '`' => self::SQL_BACKTICKED,
                    default => self::SQL_OUTSIDE,
                };
            } elseif ($state === self::SQL_BACKTICKED) {
                if ($char === '`') {
                    $state = self::SQL_OUTSIDE;
                }
            } elseif ($char === '\\') {
                $i++;
            } elseif ($char === ($state === self::SQL_SINGLE_QUOTED ? "'" : '"')) {
                $state = self::SQL_OUTSIDE;
            }
        }

        return $state;
    }

    /**
     * @psalm-mutation-free
     */
    private function afterHtml(string $text, ?int $sql): self
    {
        $state = $this->html;
        $tag = $this->tag;
        $attribute = $this->attribute;
        $scheme = $this->scheme;
        $i = 0;

        // where the markup is isn't known: from the last start tag on, it is
        if ($state === null) {
            $count = (int) preg_match_all('/<[a-zA-Z]/', $text, $matches, PREG_OFFSET_CAPTURE);
            $last_tag_start = $matches[0][$count - 1][1] ?? null;

            if ($last_tag_start === null) {
                return new self($sql, null);
            }

            $state = self::HTML_TEXT;
            $i = $last_tag_start;
        }

        for ($length = strlen($text); $i < $length; $i++) {
            $char = $text[$i];
            $is_whitespace = strpbrk($char, self::HTML_WHITESPACE) !== false;

            switch ($state) {
                case self::HTML_TEXT:
                    if ($char !== '<') {
                        break;
                    }

                    if (substr_compare($text, '<!--', $i, 4) === 0) {
                        $state = self::HTML_COMMENT;
                        $i += 3;
                    } elseif (($text[$i + 1] ?? '') === '/') {
                        $state = self::HTML_END_TAG;
                    } elseif (ctype_alpha($text[$i + 1] ?? '')) {
                        $name_length = strspn(
                            $text,
                            'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789:-',
                            $i + 1,
                        );
                        $tag = strtolower(substr($text, $i + 1, $name_length));
                        $state = self::HTML_TAG;
                        $i += $name_length;
                    }

                    break;

                case self::HTML_COMMENT:
                    if (substr_compare($text, '-->', $i, 3) === 0) {
                        $state = self::HTML_TEXT;
                        $i += 2;
                    }

                    break;

                case self::HTML_END_TAG:
                    if ($char === '>') {
                        $state = self::HTML_TEXT;
                    }

                    break;

                case self::HTML_TAG:
                case self::HTML_AFTER_ATTRIBUTE_NAME:
                    if ($char === '>') {
                        $state = self::HTML_TEXT;
                    } elseif ($char === '/') {
                        $state = self::HTML_TAG;
                    } elseif ($char === '=' && $state === self::HTML_AFTER_ATTRIBUTE_NAME) {
                        $state = self::HTML_BEFORE_VALUE;
                    } elseif (!$is_whitespace) {
                        $state = self::HTML_ATTRIBUTE_NAME;
                        $attribute = strtolower($char);
                    }

                    break;

                case self::HTML_ATTRIBUTE_NAME:
                    if ($char === '=') {
                        $state = self::HTML_BEFORE_VALUE;
                    } elseif ($is_whitespace) {
                        $state = self::HTML_AFTER_ATTRIBUTE_NAME;
                    } elseif ($char === '/') {
                        $state = self::HTML_TAG;
                    } elseif ($char === '>') {
                        $state = self::HTML_TEXT;
                    } else {
                        $attribute .= strtolower($char);
                    }

                    break;

                case self::HTML_BEFORE_VALUE:
                    if ($char === '"' || $char === "'") {
                        $state = $char === '"' ? self::HTML_DOUBLE_QUOTED_VALUE : self::HTML_SINGLE_QUOTED_VALUE;
                        $scheme = self::SCHEME_NOT_STARTED;
                    } elseif ($char === '>') {
                        $state = self::HTML_TEXT;
                    } elseif (!$is_whitespace) {
                        $state = self::HTML_UNQUOTED_VALUE;
                        $scheme = self::afterSchemeCharacter(self::SCHEME_NOT_STARTED, $char);
                    }

                    break;

                case self::HTML_UNQUOTED_VALUE:
                    if ($is_whitespace) {
                        $state = self::HTML_TAG;
                    } elseif ($char === '>') {
                        $state = self::HTML_TEXT;
                    } else {
                        $scheme = self::afterSchemeCharacter($scheme, $char);
                    }

                    break;

                default:
                    if ($char === ($state === self::HTML_DOUBLE_QUOTED_VALUE ? '"' : "'")) {
                        $state = self::HTML_TAG;
                    } else {
                        $scheme = self::afterSchemeCharacter($scheme, $char);
                    }
            }

            if ($state !== self::HTML_UNQUOTED_VALUE
                && $state !== self::HTML_SINGLE_QUOTED_VALUE
                && $state !== self::HTML_DOUBLE_QUOTED_VALUE
            ) {
                $scheme = self::SCHEME_DECIDED;
            }
        }

        return new self($sql, $state, $tag, $attribute, $scheme);
    }

    /**
     * Whether a URL starting with $start has a scheme the value after it can't choose: `https://`, a path, ...
     *
     * @psalm-pure
     */
    public static function fixesUrlScheme(string $start): bool
    {
        $scheme = self::SCHEME_NOT_STARTED;

        for ($i = 0, $length = strlen($start); $i < $length; $i++) {
            $scheme = self::afterSchemeCharacter($scheme, $start[$i]);
        }

        return $scheme === self::SCHEME_DECIDED;
    }

    /**
     * Browsers drop the whitespace and control characters a URL starts with, and the tabs and line breaks in it: a
     * scheme is letters, then letters, digits, `+`, `-` or `.`, up to a `:`
     *
     * @psalm-pure
     */
    private static function afterSchemeCharacter(int $scheme, string $char): int
    {
        if ($scheme === self::SCHEME_DECIDED || $char === "\t" || $char === "\n" || $char === "\r") {
            return $scheme;
        }

        if ($scheme === self::SCHEME_NOT_STARTED) {
            if (ord($char) <= 0x20) {
                return $scheme;
            }

            return ctype_alpha($char) ? self::SCHEME_STARTED : self::SCHEME_DECIDED;
        }

        return ctype_alnum($char) || $char === '+' || $char === '-' || $char === '.'
            ? self::SCHEME_STARTED
            : self::SCHEME_DECIDED;
    }
}
