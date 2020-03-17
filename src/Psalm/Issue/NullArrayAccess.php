<?php
namespace Psalm\Issue;

/**
 * This is different from NullReference, as PHP throws a notice (vs the possibility of a fatal error with a null
 * reference)
 */
class NullArrayAccess extends CodeIssue
{
    const ERROR_LEVEL = -1;
    const SHORTCODE = 52;
}
