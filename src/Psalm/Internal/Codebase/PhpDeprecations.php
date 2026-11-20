<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use function intdiv;
use function strtolower;

/**
 * The builtin functions, methods and constants a PHP version deprecates, with what replaces them: using one is
 * reported whatever PHP version is analysed, as code using it breaks once PHP removes it
 *
 * @internal
 * @psalm-immutable
 */
final class PhpDeprecations
{
    private const MB_REGEX = 'the preg_* functions with the u modifier';

    /** @var array<lowercase-string, array{int, ?string}> function id => [PHP version id, replacement] */
    private const FUNCTIONS = [
        'mb_ereg' => [8_06_00, self::MB_REGEX],
        'mb_ereg_match' => [8_06_00, self::MB_REGEX],
        'mb_ereg_replace' => [8_06_00, self::MB_REGEX],
        'mb_ereg_replace_callback' => [8_06_00, self::MB_REGEX],
        'mb_ereg_search' => [8_06_00, self::MB_REGEX],
        'mb_ereg_search_getpos' => [8_06_00, self::MB_REGEX],
        'mb_ereg_search_getregs' => [8_06_00, self::MB_REGEX],
        'mb_ereg_search_init' => [8_06_00, self::MB_REGEX],
        'mb_ereg_search_pos' => [8_06_00, self::MB_REGEX],
        'mb_ereg_search_regs' => [8_06_00, self::MB_REGEX],
        'mb_ereg_search_setpos' => [8_06_00, self::MB_REGEX],
        'mb_eregi' => [8_06_00, self::MB_REGEX],
        'mb_eregi_replace' => [8_06_00, self::MB_REGEX],
        'mb_regex_encoding' => [8_06_00, self::MB_REGEX],
        'mb_regex_set_options' => [8_06_00, self::MB_REGEX],
        'mb_split' => [8_06_00, 'preg_split() with the u modifier'],
        'is_double' => [8_06_00, 'is_float()'],
        'is_long' => [8_06_00, 'is_int()'],
        'is_integer' => [8_06_00, 'is_int()'],
        'doubleval' => [8_06_00, 'floatval()'],
        'spl_object_hash' => [8_06_00, 'spl_object_id()'],
        'spl_classes' => [8_06_00, 'ReflectionExtension::getClassNames()'],
        'strcoll' => [8_06_00, 'Collator::compare()'],
        'metaphone' => [8_06_00, null],
        'mysqli_get_charset' => [8_06_00, 'mysqli_character_set_name()'],
        'mysqli_stmt_init' => [8_06_00, 'mysqli_prepare()'],
    ];

    /** @var array<lowercase-string, array{int, ?string}> declaring method id => [PHP version id, replacement] */
    private const METHODS = [
        'mysqli::get_charset' => [8_06_00, 'mysqli::character_set_name()'],
        'mysqli::stmt_init' => [8_06_00, 'mysqli::prepare()'],
        'arrayiterator::getflags' => [8_06_00, null],
        'arrayiterator::setflags' => [8_06_00, null],
        'arrayiterator::asort' => [8_06_00, null],
        'arrayiterator::ksort' => [8_06_00, null],
        'arrayiterator::uasort' => [8_06_00, null],
        'arrayiterator::uksort' => [8_06_00, null],
        'arrayiterator::natsort' => [8_06_00, null],
        'arrayiterator::natcasesort' => [8_06_00, null],
        'arrayiterator::serialize' => [8_06_00, null],
        'arrayiterator::unserialize' => [8_06_00, null],
        'splfileobject::fgetcsv' => [8_06_00, 'fgetcsv() on a stream'],
        'splfileobject::fputcsv' => [8_06_00, 'fputcsv() on a stream'],
        'splfileobject::setcsvcontrol' => [8_06_00, 'fgetcsv() or fputcsv() on a stream'],
        'splfileobject::getcsvcontrol' => [8_06_00, 'fgetcsv() or fputcsv() on a stream'],
    ];

    /** @var array<string, array{int, ?string}> constant name => [PHP version id, replacement] */
    private const CONSTANTS = [
        'SORT_LOCALE_STRING' => [8_06_00, 'Collator::sort()'],
        'MB_ONIGURUMA_VERSION' => [8_06_00, null],
    ];

    /**
     * @var array<lowercase-string, int> the keywords PHP adds, by the PHP version that deprecates them as names of
     * classes, interfaces, traits, enums, functions, constants and aliases
     */
    private const KEYWORDS = [
        'let' => 8_06_00,
        'is' => 8_06_00,
    ];

    /**
     * Why the builtin function $function_id is deprecated, if it is
     *
     * @psalm-pure
     */
    public static function getFunctionMessage(string $function_id): ?string
    {
        return self::getMessage('The function ' . $function_id, self::FUNCTIONS[strtolower($function_id)] ?? null);
    }

    /**
     * Why the builtin method $cased_method_id, declared as $declaring_method_id, is deprecated, if it is
     *
     * @psalm-pure
     */
    public static function getMethodMessage(string $declaring_method_id, string $cased_method_id): ?string
    {
        return self::getMessage(
            'The method ' . $cased_method_id,
            self::METHODS[strtolower($declaring_method_id)] ?? null,
        );
    }

    /**
     * Why the builtin constant $name is deprecated, if it is
     *
     * @psalm-pure
     */
    public static function getConstantMessage(string $name): ?string
    {
        return self::getMessage('The constant ' . $name, self::CONSTANTS[$name] ?? null);
    }

    /**
     * Why naming a class-like or a class alias $name is deprecated, if it is: $kind is `a class`, `an interface`, ...
     *
     * @psalm-pure
     */
    public static function getClassLikeNameMessage(string $kind, string $name): ?string
    {
        return self::getKeywordNameMessage($kind, $name);
    }

    /**
     * Why naming a function $name is deprecated, if it is
     *
     * @psalm-pure
     */
    public static function getFunctionNameMessage(string $name): ?string
    {
        if (strtolower($name) === 'readonly') {
            return self::getDeprecatedSinceMessage('Naming a function readonly', 8_06_00, null);
        }

        return self::getKeywordNameMessage('a function', $name);
    }

    /**
     * Why naming a constant $name, with `const` or define(), is deprecated, if it is
     *
     * @psalm-pure
     */
    public static function getConstantNameMessage(string $name): ?string
    {
        if ($name === '_') {
            return self::getDeprecatedSinceMessage('Naming a constant _', 8_06_00, null);
        }

        return self::getKeywordNameMessage('a constant', $name);
    }

    /**
     * Why the alias $alias of a `use` declaration is deprecated, if it is
     *
     * @psalm-pure
     */
    public static function getUseAliasMessage(string $alias, bool $of_a_function): ?string
    {
        if ($alias === '_' && !$of_a_function) {
            return self::getDeprecatedSinceMessage('Naming an alias _', 8_06_00, null);
        }

        return self::getKeywordNameMessage('an alias', $alias);
    }

    /**
     * The message saying $subject is deprecated since a PHP version, and what replaces it
     *
     * @psalm-pure
     */
    public static function getDeprecatedSinceMessage(
        string $subject,
        int $since_php_version_id,
        ?string $replacement,
    ): string {
        return $subject . ' is deprecated since PHP ' . intdiv($since_php_version_id, 10_000) . '.'
            . intdiv($since_php_version_id % 10_000, 100)
            . ($replacement === null ? '' : ', use ' . $replacement . ' instead');
    }

    /**
     * @psalm-pure
     */
    private static function getKeywordNameMessage(string $kind, string $name): ?string
    {
        $name_lc = strtolower($name);
        $since = self::KEYWORDS[$name_lc] ?? null;

        return $since === null
            ? null
            : self::getDeprecatedSinceMessage('Naming ' . $kind . ' ' . $name, $since, null)
                . ', as ' . $name_lc . ' becomes a keyword';
    }

    /**
     * @param ?array{int, ?string} $deprecation
     * @psalm-pure
     */
    private static function getMessage(string $subject, ?array $deprecation): ?string
    {
        return $deprecation === null
            ? null
            : self::getDeprecatedSinceMessage($subject, $deprecation[0], $deprecation[1]);
    }
}
