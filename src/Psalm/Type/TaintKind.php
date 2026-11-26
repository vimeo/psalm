<?php

declare(strict_types=1);

namespace Psalm\Type;

/**
 * An Enum class holding all the taint types that Psalm recognises
 *
 * Not using an enum since real code usages will use only the integer value,
 * and extracting it with ->value every time is a pain.
 *
 * @psalm-immutable
 * @api
 */
final class TaintKind
{
    public const INPUT_CALLABLE = (1 << 0);
    public const INPUT_UNSERIALIZE = (1 << 1);
    public const INPUT_INCLUDE = (1 << 2);
    public const INPUT_EVAL = (1 << 3);
    public const INPUT_LDAP = (1 << 4);
    public const INPUT_SQL = (1 << 5);
    public const INPUT_HTML = (1 << 6);
    public const INPUT_HAS_QUOTES = (1 << 7);
    public const INPUT_SHELL = (1 << 8);
    public const INPUT_SSRF = (1 << 9);
    public const INPUT_FILE = (1 << 10);
    public const INPUT_COOKIE = (1 << 11);
    public const INPUT_HEADER = (1 << 12);
    public const INPUT_XPATH = (1 << 13);
    public const INPUT_SLEEP = (1 << 14);
    public const INPUT_EXTRACT = (1 << 15);
    public const INPUT_LLM_PROMPT = (1 << 16);
    public const INPUT_NOSQL = (1 << 17);
    public const INPUT_URL_COMPONENT = (1 << 18);
    public const INPUT_URL_PATH = (1 << 19);
    public const INPUT_REDIRECT = (1 << 20);
    /** SQL syntax outside of quotes, which escaping a value for SQL quotes doesn't remove */
    public const INPUT_SQL_UNQUOTED = (1 << 21);
    /** What ends an unquoted HTML attribute value, which escaping a value for HTML doesn't remove */
    public const INPUT_HTML_ATTRIBUTE = (1 << 22);
    /** A URL scheme running code (`javascript:`), which escaping a value for HTML doesn't remove */
    public const INPUT_URL_SCHEME = (1 << 23);
    /** What can end a string literal of an XPath query: a quote */
    public const INPUT_XPATH_LITERAL = (1 << 24);
    public const USER_SECRET = (1 << 25);
    public const SYSTEM_SECRET = (1 << 26);

    /**
     * Bitmask of all INPUT_* taint types. Used as the default taint for
     * user-controlled sources (e.g. $_GET, $_POST) so they propagate to any
     * input sink.
     * Excludes USER_SECRET and SYSTEM_SECRET, which represent
     * sensitive data leaking out rather than untrusted data flowing in.
     */
    public const ALL_INPUT = (1 << 25) - 1;

    /** @internal */
    public const NUMERIC_ONLY = self::INPUT_SLEEP;
    /** @internal */
    public const BOOL_ONLY = self::INPUT_SLEEP;
    /**
     * @internal Taints that can only apply to values able to hold an array/object
     * (e.g. a NoSQL query document). A plain string can never carry these taints,
     * so casting user input to string acts as an escape.
     */
    public const ARRAY_ONLY = self::INPUT_NOSQL;

    /**
     * @internal Every taint, builtin or custom: removing it on a data flow path means no
     * taint goes through it, and the taint graph leaves the path out.
     */
    public const ALL = -1;

    /** @internal Keep this synced with the above */
    public const BUILTIN_TAINT_COUNT = 27;


    // Map of taint kind names to their bitmask values, used in taint annotations
    public const TAINT_NAMES = [
        'callable' => self::INPUT_CALLABLE,
        'unserialize' => self::INPUT_UNSERIALIZE,
        'include' => self::INPUT_INCLUDE,
        'eval' => self::INPUT_EVAL,
        'ldap' => self::INPUT_LDAP,
        // SQL syntax wherever the value is: what breaks out of SQL quotes, and what is SQL syntax outside of them
        'sql' => self::INPUT_SQL | self::INPUT_SQL_UNQUOTED,
        'sql_in_quotes' => self::INPUT_SQL,
        'sql_unquoted' => self::INPUT_SQL_UNQUOTED,
        'nosql' => self::INPUT_NOSQL,
        'html' => self::INPUT_HTML,
        'has_quotes' => self::INPUT_HAS_QUOTES,
        'html_attribute' => self::INPUT_HTML_ATTRIBUTE,
        'url_scheme' => self::INPUT_URL_SCHEME,
        'shell' => self::INPUT_SHELL,
        'ssrf' => self::INPUT_SSRF,
        'url_component' => self::INPUT_URL_COMPONENT,
        'url_path' => self::INPUT_URL_PATH,
        'redirect' => self::INPUT_REDIRECT,
        'file' => self::INPUT_FILE,
        'cookie' => self::INPUT_COOKIE,
        'header' => self::INPUT_HEADER,
        // an XPath query is injected through a value outside a string literal, or one ending it
        'xpath' => self::INPUT_XPATH | self::INPUT_XPATH_LITERAL,
        'xpath_query' => self::INPUT_XPATH,
        'xpath_literal' => self::INPUT_XPATH_LITERAL,
        'sleep' => self::INPUT_SLEEP,
        'extract' => self::INPUT_EXTRACT,
        'user_secret' => self::USER_SECRET,
        'system_secret' => self::SYSTEM_SECRET,
        'llm_prompt' => self::INPUT_LLM_PROMPT,

        'input_except_sleep' => self::ALL_INPUT & ~self::INPUT_SLEEP,

        'input' => self::ALL_INPUT,
        'tainted' => self::ALL_INPUT,
    ];
}
