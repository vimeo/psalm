<?php
namespace Psalm\Issue;

/**
 * This is different from PossiblyNullReference, as PHP throws a notice (vs the possibility of a fatal error with a null
 * reference)
 */
class PossiblyNullPropertyFetch extends CodeIssue
{
    const ERROR_LEVEL = 3;
    const SHORTCODE = 82;
}
