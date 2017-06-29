<?php
namespace Psalm\Type;

class TypeCombination
{
    /** @var array<string, Atomic> */
    public $value_types = [];

    /** @var array<string, array<int, Union>> */
    public $type_params = [];

    /** @var array<string, Union> */
    public $objectlike_entries = [];
}
