<?php

declare(strict_types=1);

namespace Psalm\Issue;

/**
 * @psalm-immutable
 */
final class MismatchingDocblockParamType extends CodeIssue
{
    public const ERROR_LEVEL = 4;
    public const SHORTCODE = 141;
}
