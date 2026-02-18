<?php

declare(strict_types=1);

namespace Psalm\Issue;

/**
 * @psalm-immutable
 */
final class MixedArrayAssignment extends CodeIssue implements MixedIssue
{
    public const ERROR_LEVEL = 1;
    public const SHORTCODE = 117;

    use MixedIssueTrait;
}
