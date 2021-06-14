<?php
namespace Psalm\Type\Atomic;

/**
 * Denotes the `literal-string` type, where the exact value is unknown but
 * we know that the string is not from user input
 */
class TNonspecificNonEmptyLiteralString extends TNonspecificLiteralString
{
}
