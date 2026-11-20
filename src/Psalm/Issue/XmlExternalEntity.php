<?php

declare(strict_types=1);

namespace Psalm\Issue;

/**
 * @api
 */
final class XmlExternalEntity extends CodeIssue implements SecurityIssue
{
    public const ERROR_LEVEL = 5;
    public const SHORTCODE = 385;
    public const CWE_IDS = [611];
}
