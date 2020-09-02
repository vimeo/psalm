<?php
namespace Psalm\Type\Atomic;

/**
 * Represents a non-empty array
 */
class TNonEmptyString extends TString
{
    /**
     * @return string
     */
    public function getId(bool $nested = false): string
    {
        return 'non-empty-string';
    }
}
