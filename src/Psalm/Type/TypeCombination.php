<?php
namespace Psalm\Type;

class TypeCombination
{
    /** @var array<string, Atomic> */
    public $value_types = [];

    /** @var array<string, array<int, Union>> */
    public $type_params = [];

    /** @var array<string, bool>|null */
    public $array_counts = [];

    /** @var array<string|int, Union> */
    public $objectlike_entries = [];

    /** @var array<string, string> */
    public $class_string_types = [];

    /** @var array<string, bool>|null */
    public $strings = [];

    /** @var array<string, bool>|null */
    public $ints = [];

    /** @var array<string, bool>|null */
    public $floats = [];
}
