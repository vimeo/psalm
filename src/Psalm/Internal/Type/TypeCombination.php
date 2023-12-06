<?php

namespace Psalm\Internal\Type;

use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArrayKey;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIntRange;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;

use function is_int;
use function is_string;

/**
 * @internal
 */
final class TypeCombination
{
    /** @var array<string, Atomic> */
    public array $value_types = [];

    /** @var array<string, TNamedObject>|null */
    public ?array $named_object_types = [];

    /** @var list<Union> */
    public array $array_type_params = [];

    /** @var array<string, non-empty-list<Union>> */
    public array $builtin_type_params = [];

    /** @var array<string, non-empty-list<Union>> */
    public array $object_type_params = [];

    /** @var array<string, bool> */
    public array $object_static = [];

    /** @var array<int, bool>|null */
    public ?array $array_counts = [];

    /** @var array<int, bool>|null */
    public ?array $array_min_counts = [];

    public bool $array_sometimes_filled = false;

    public bool $array_always_filled = true;

    /** @var array<string|int, Union> */
    public array $objectlike_entries = [];

    /** @var array<string, bool> */
    public array $objectlike_class_string_keys = [];

    public bool $objectlike_sealed = true;

    public ?Union $objectlike_key_type = null;

    public ?Union $objectlike_value_type = null;

    public bool $empty_mixed = false;

    public bool $non_empty_mixed = false;

    public ?bool $mixed_from_loop_isset = null;

    /** @var array<string, TLiteralString>|null */
    public ?array $strings = [];

    /** @var array<string, TLiteralInt>|null */
    public ?array $ints = [];

    /** @var array<string, TLiteralFloat>|null */
    public ?array $floats = [];

    /** @var array<string, TNamedObject|TObject>|null */
    public ?array $class_string_types = [];

    /**
     * @var array<string, TNamedObject|TTemplateParam|TIterable|TObject>
     */
    public array $extra_types = [];

    public ?bool $all_arrays_lists = null;

    public ?bool $all_arrays_callable = null;

    public ?bool $all_arrays_class_string_maps = null;

    /** @var array<string, bool> */
    public array $class_string_map_names = [];

    /** @var array<string, ?TNamedObject> */
    public array $class_string_map_as_types = [];

    /**
     * @psalm-assert-if-true !null $this->objectlike_key_type
     * @psalm-assert-if-true !null $this->objectlike_value_type
     * @param array-key $k
     */
    public function fallbackKeyContains($k): bool
    {
        if (!$this->objectlike_key_type) {
            return false;
        }
        foreach ($this->objectlike_key_type->getAtomicTypes() as $t) {
            if ($t instanceof TArrayKey) {
                return true;
            } elseif ($t instanceof TLiteralInt || $t instanceof TLiteralString) {
                if ($t->value === $k) {
                    return true;
                }
            } elseif ($t instanceof TIntRange) {
                if (is_int($k) && $t->contains($k)) {
                    return true;
                }
            } elseif ($t instanceof TString && is_string($k)) {
                return true;
            } elseif ($t instanceof TInt && is_int($k)) {
                return true;
            }
        }
        return false;
    }
}
