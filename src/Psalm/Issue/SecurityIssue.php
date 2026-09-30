<?php

declare(strict_types=1);

namespace Psalm\Issue;

/**
 * An issue flagging a potential security vulnerability, such as the issues of
 * security analysis, rather than a type error or a code-quality problem.
 *
 * Human-readable reports show it under a SECURITY header instead of ERROR.
 *
 * @api
 * @psalm-mutable
 */
interface SecurityIssue
{
}
