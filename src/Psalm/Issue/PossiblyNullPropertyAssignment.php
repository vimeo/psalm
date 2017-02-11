<?php
namespace Psalm\Issue;

/**
 * This is different from PossiblyNullReference, as PHP throws a notice (vs the possibility of a fatal error with a null
 * reference)
 *
 */
class PossiblyNullPropertyAssignment extends CodeError
{
}
