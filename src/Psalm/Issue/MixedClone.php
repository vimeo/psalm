<?php

declare(strict_types=1);

namespace Psalm\Issue;

final class MixedClone extends CodeIssue implements MixedIssue
{
    public const ERROR_LEVEL = 1;
    public const SHORTCODE = 227;

    use MixedIssueTrait;
}
