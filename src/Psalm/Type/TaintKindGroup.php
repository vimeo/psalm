<?php

declare(strict_types=1);

namespace Psalm\Type;

/**
 * An Enum class holding all the taint types that Psalm recognises
 */
final class TaintKindGroup
{
    public const ALL_INPUT = (1 << 17) - 1;

    /** @var array<int-mask-of<TaintKind::*>, string> */
    public const TAINT_TO_NAME = [
        TaintKind::INPUT_CALLABLE => 'callable',
        TaintKind::INPUT_UNSERIALIZE => 'unserialize',
        TaintKind::INPUT_INCLUDE => 'include',
        TaintKind::INPUT_EVAL => 'eval',
        TaintKind::INPUT_LDAP => 'ldap',
        TaintKind::INPUT_SQL => 'sql',
        TaintKind::INPUT_HTML => 'html',
        TaintKind::INPUT_HAS_QUOTES => 'has_quotes',
        TaintKind::INPUT_SHELL => 'shell',
        TaintKind::INPUT_SSRF => 'ssrf',
        TaintKind::INPUT_FILE => 'file',
        TaintKind::INPUT_COOKIE => 'cookie',
        TaintKind::INPUT_HEADER => 'header',
        TaintKind::INPUT_XPATH => 'xpath',
        TaintKind::INPUT_SLEEP => 'sleep',
        TaintKind::INPUT_EXTRACT => 'extract',
        TaintKind::USER_SECRET => 'user_secret',
        TaintKind::SYSTEM_SECRET => 'system_secret',
    ];
    /** @var array<string, int<0, max>> */
    public const NAME_TO_TAINT = [
        'callable' => TaintKind::INPUT_CALLABLE,
        'unserialize' => TaintKind::INPUT_UNSERIALIZE,
        'include' => TaintKind::INPUT_INCLUDE,
        'eval' => TaintKind::INPUT_EVAL,
        'ldap' => TaintKind::INPUT_LDAP,
        'sql' => TaintKind::INPUT_SQL,
        'html' => TaintKind::INPUT_HTML,
        'has_quotes' => TaintKind::INPUT_HAS_QUOTES,
        'shell' => TaintKind::INPUT_SHELL,
        'ssrf' => TaintKind::INPUT_SSRF,
        'file' => TaintKind::INPUT_FILE,
        'cookie' => TaintKind::INPUT_COOKIE,
        'header' => TaintKind::INPUT_HEADER,
        'xpath' => TaintKind::INPUT_XPATH,
        'sleep' => TaintKind::INPUT_SLEEP,
        'extract' => TaintKind::INPUT_EXTRACT,
        'user_secret' => TaintKind::USER_SECRET,
        'system_secret' => TaintKind::SYSTEM_SECRET,
        'input' => self::ALL_INPUT,
        'tainted' => self::ALL_INPUT,
    ];
}
