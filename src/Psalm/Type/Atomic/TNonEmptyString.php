<?php
namespace Psalm\Type\Atomic;

/**
 * Denotes a string, that is also non-empty
 */
class TNonEmptyString extends TString
{
    public function getId(bool $nested = false): string
    {
        return 'non-empty-string';
    }
}
