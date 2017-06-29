<?php
namespace Psalm\Type;

class TypeCombination
{
    /** @var array<string, array<string, Union>> */
    public $key_types = [];

    /** @var array<string, array<string, Union|null>> */
    public $value_types = [];
}
