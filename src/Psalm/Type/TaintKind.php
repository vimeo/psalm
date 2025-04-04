<?php

declare(strict_types=1);

namespace Psalm\Type;

/**
 * An Enum class holding all the taint types that Psalm recognises
 *
 * Not using an enum since real code usages will use only the integer value,
 * and extracting it with ->value every time is a pain.
 */
final class TaintKind
{
    public const INPUT_CALLABLE = (1 << 0);
    public const INPUT_UNSERIALIZE = (1 << 2);
    public const INPUT_INCLUDE = (1 << 3);
    public const INPUT_EVAL = (1 << 4);
    public const INPUT_LDAP = (1 << 5);
    public const INPUT_SQL = (1 << 6);
    public const INPUT_HTML = (1 << 7);
    public const INPUT_HAS_QUOTES = (1 << 8);
    public const INPUT_SHELL = (1 << 9);
    public const INPUT_SSRF = (1 << 10);
    public const INPUT_FILE = (1 << 11);
    public const INPUT_COOKIE = (1 << 12);
    public const INPUT_HEADER = (1 << 13);
    public const INPUT_XPATH = (1 << 14);
    public const INPUT_SLEEP = (1 << 15);
    public const INPUT_EXTRACT = (1 << 16);
    public const USER_SECRET = (1 << 17);
    public const SYSTEM_SECRET = (1 << 18);

    public const ALL_INPUT = (1 << 17) - 1;

    /** @internal */
    public const NUMERIC_ONLY = self::INPUT_SLEEP;
    /** @internal */
    public const BOOL_ONLY = self::INPUT_SLEEP;

    /** @internal Keep this synced with the above */
    public const BUILTIN_TAINT_COUNT = 19;
}
