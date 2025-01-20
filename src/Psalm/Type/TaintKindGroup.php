<?php

declare(strict_types=1);

namespace Psalm\Type;

/**
 * An Enum class holding all the taint types that Psalm recognises
 */
final class TaintKindGroup
{
    public const GROUP_INPUT = 'input';

    public const ALL_INPUT = [
        TaintKind::INPUT_HTML,
        TaintKind::INPUT_HAS_QUOTES,
        TaintKind::INPUT_SHELL,
        TaintKind::INPUT_SQL,
        TaintKind::INPUT_CALLABLE,
        TaintKind::INPUT_EVAL,
        TaintKind::INPUT_UNSERIALIZE,
        TaintKind::INPUT_INCLUDE,
        TaintKind::INPUT_SSRF,
        TaintKind::INPUT_LDAP,
        TaintKind::INPUT_FILE,
        TaintKind::INPUT_HEADER,
        TaintKind::INPUT_COOKIE,
        TaintKind::INPUT_XPATH,
        TaintKind::INPUT_SLEEP,
        TaintKind::INPUT_EXTRACT,
    ];
}
