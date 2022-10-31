<?php

namespace Psalm\Issue;

/**
 * This is different from NullReference, as PHP throws a notice (vs the possibility of a fatal error with a null
 * reference)
 */
final class NullPropertyAssignment extends CodeIssue
{
    public const ERROR_LEVEL = -1;
    public const SHORTCODE = 36;
}
