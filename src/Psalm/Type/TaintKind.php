<?php

namespace Psalm\Type;

/**
 * An Enum class holding all the taint types that Psalm recognises
 */
class TaintKind
{
    public const INPUT_TEXT = 'text';
    public const INPUT_UNSERIALIZE = 'unserialize';
    public const INPUT_INCLUDE = 'include';
    public const INPUT_EVAL = 'eval';
    public const INPUT_SQL = 'sql';
    public const INPUT_HTML = 'html';
    public const INPUT_SHELL = 'shell';
    public const INPUT_SSRF = 'ssrf';
    public const USER_SECRET = 'user_secret';
    public const SYSTEM_SECRET = 'system_secret';
}
