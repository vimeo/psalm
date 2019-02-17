<?php
namespace Psalm\Internal\Type;

use Psalm\Codebase;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\HasClassString;
use Psalm\Type\Atomic\ObjectLike;
use Psalm\Type\Atomic\Scalar;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TArrayKey;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TEmpty;
use Psalm\Type\Atomic\TEmptyMixed;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TNonEmptyMixed;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TScalar;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTrue;
use Psalm\Internal\Type\TypeCombination;
use Psalm\Type\Union;

/**
 * @internal
 */
class TypeCombination
{
    /** @var array<string, Atomic> */
    private $value_types = [];

    /** @var array<string, TNamedObject>|null */
    private $named_object_types = [];

    /** @var array<string, array<int, Union>> */
    private $type_params = [];

    /** @var array<int, bool>|null */
    private $array_counts = [];

    /** @var bool */
    private $array_sometimes_filled = false;

    /** @var bool */
    private $array_always_filled = true;

    /** @var array<string|int, Union> */
    private $objectlike_entries = [];

    /** @var bool */
    private $objectlike_sealed = true;

    /** @var bool */
    private $has_mixed = false;

    /** @var bool */
    private $empty_mixed = false;

    /** @var bool */
    private $non_empty_mixed = false;

    /** @var ?bool */
    private $mixed_from_loop_isset = null;

    /** @var array<string, Atomic\TLiteralString>|null */
    private $strings = [];

    /** @var array<string, Atomic\TLiteralInt>|null */
    private $ints = [];

    /** @var array<string, Atomic\TLiteralFloat>|null */
    private $floats = [];

    /**
     * Combines types together
     *  - so `int + string = int|string`
     *  - so `array<int> + array<string> = array<int|string>`
     *  - and `array<int> + string = array<int>|string`
     *  - and `array<empty> + array<empty> = array<empty>`
     *  - and `array<string> + array<empty> = array<string>`
     *  - and `array + array<string> = array<mixed>`
     *
     * @param  array<Atomic>    $types
     * @param  int    $literal_limit any greater number of literal types than this
     *                               will be merged to a scalar
     *
     * @return Union
     * @psalm-suppress TypeCoercion
     */
    public static function combineTypes(
        array $types,
        Codebase $codebase = null,
        bool $overwrite_empty_array = false,
        bool $allow_mixed_union = true,
        int $literal_limit = 500
    ) {
        if (in_array(null, $types, true)) {
            return Type::getMixed();
        }

        if (count($types) === 1) {
            $union_type = new Union([$types[0]]);

            if ($types[0]->from_docblock) {
                $union_type->from_docblock = true;
            }

            return $union_type;
        }

        if (!$types) {
            throw new \InvalidArgumentException('You must pass at least one type to combineTypes');
        }

        $combination = new TypeCombination();

        $from_docblock = false;

        foreach ($types as $type) {
            $from_docblock = $from_docblock || $type->from_docblock;

            $result = self::scrapeTypeProperties(
                $type,
                $combination,
                $codebase,
                $overwrite_empty_array,
                $allow_mixed_union,
                $literal_limit
            );

            if ($result) {
                if ($from_docblock) {
                    $result->from_docblock = true;
                }

                return $result;
            }
        }

        if (count($combination->value_types) === 1
            && !count($combination->objectlike_entries)
            && !count($combination->type_params)
            && !$combination->named_object_types
            && !$combination->strings
            && !$combination->ints
            && !$combination->floats
        ) {
            if (isset($combination->value_types['false'])) {
                $union_type = Type::getFalse();

                if ($from_docblock) {
                    $union_type->from_docblock = true;
                }

                return $union_type;
            }

            if (isset($combination->value_types['true'])) {
                $union_type = Type::getTrue();

                if ($from_docblock) {
                    $union_type->from_docblock = true;
                }

                return $union_type;
            }
        } elseif (isset($combination->value_types['void'])) {
            unset($combination->value_types['void']);

            // if we're merging with another type, we cannot represent it in PHP
            $from_docblock = true;

            if (!isset($combination->value_types['null'])) {
                $combination->value_types['null'] = new TNull();
            }
        }

        if (isset($combination->value_types['true']) && isset($combination->value_types['false'])) {
            unset($combination->value_types['true'], $combination->value_types['false']);

            $combination->value_types['bool'] = new TBool();
        }

        if (isset($combination->type_params['array'])
            && (isset($combination->named_object_types['Traversable'])
                || isset($combination->type_params['Traversable']))
            && (($codebase && !$codebase->config->allow_phpstorm_generics)
                || isset($combination->type_params['Traversable'])
                || (isset($combination->named_object_types['Traversable'])
                    && $combination->named_object_types['Traversable']->from_docblock)
            )
        ) {
            $array_param_types = $combination->type_params['array'];
            $traversable_param_types = $combination->type_params['Traversable'] ?? [Type::getMixed(), Type::getMixed()];

            $combined_param_types = [];

            foreach ($array_param_types as $i => $array_param_type) {
                $combined_param_types[$i] = Type::combineUnionTypes($array_param_type, $traversable_param_types[$i]);
            }

            $combination->value_types['iterable'] = new TIterable($combined_param_types);

            /**
             * @psalm-suppress PossiblyNullArrayOffset
             * @psalm-suppress PossiblyNullArrayAccess
             */
            unset(
                $combination->value_types['array'],
                $combination->named_object_types['Traversable'],
                $combination->type_params['array'],
                $combination->type_params['Traversable']
            );
        }

        if ($combination->empty_mixed && $combination->non_empty_mixed) {
            $combination->value_types['mixed'] = new TMixed((bool) $combination->mixed_from_loop_isset);
        }

        $new_types = [];

        if (count($combination->objectlike_entries)) {
            if (!$combination->has_mixed || $combination->mixed_from_loop_isset) {
                if (isset($combination->type_params['array'])
                    && $combination->type_params['array'][0]->allStringLiterals()
                ) {
                    foreach ($combination->type_params['array'][0]->getTypes() as $atomic_key_type) {
                        if ($atomic_key_type instanceof TLiteralString) {
                            $combination->objectlike_entries[$atomic_key_type->value]
                                = $combination->type_params['array'][1];
                        }
                    }

                    unset($combination->type_params['array']);
                }

                if (!isset($combination->type_params['array'])
                    || $combination->type_params['array'][1]->isEmpty()
                ) {
                    if (!$overwrite_empty_array
                        && (isset($combination->type_params['array'])
                            && $combination->type_params['array'][1]->isEmpty())
                    ) {
                        foreach ($combination->objectlike_entries as $objectlike_entry) {
                            $objectlike_entry->possibly_undefined = true;
                        }
                    }

                    $objectlike = new ObjectLike($combination->objectlike_entries);

                    if ($combination->objectlike_sealed && !isset($combination->type_params['array'])) {
                        $objectlike->sealed = true;
                    }

                    $new_types[] = $objectlike;

                    // if we're merging an empty array with an object-like, clobber empty array
                    unset($combination->type_params['array']);
                }
            } else {
                $combination->type_params['array'] = [Type::getMixed(), Type::getMixed()];
            }
        }

        foreach ($combination->type_params as $generic_type => $generic_type_params) {
            if ($generic_type === 'array') {
                if ($combination->objectlike_entries) {
                    $objectlike_generic_type = null;

                    $objectlike_keys = [];

                    foreach ($combination->objectlike_entries as $property_name => $property_type) {
                        if ($objectlike_generic_type) {
                            $objectlike_generic_type = Type::combineUnionTypes(
                                $property_type,
                                $objectlike_generic_type,
                                $codebase,
                                $overwrite_empty_array
                            );
                        } else {
                            $objectlike_generic_type = clone $property_type;
                        }

                        if (is_int($property_name)) {
                            if (!isset($objectlike_keys['int'])) {
                                $objectlike_keys['int'] = new TInt;
                            }
                        } else {
                            if (!isset($objectlike_keys['string'])) {
                                $objectlike_keys['string'] = new TString;
                            }
                        }
                    }

                    $objectlike_generic_type->possibly_undefined = false;

                    $objectlike_key_type = new Type\Union(array_values($objectlike_keys));

                    $generic_type_params[0] = Type::combineUnionTypes(
                        $generic_type_params[0],
                        $objectlike_key_type,
                        $codebase,
                        $overwrite_empty_array,
                        $allow_mixed_union
                    );
                    $generic_type_params[1] = Type::combineUnionTypes(
                        $generic_type_params[1],
                        $objectlike_generic_type,
                        $codebase,
                        $overwrite_empty_array,
                        $allow_mixed_union
                    );
                }

                if ($combination->array_always_filled
                    || ($combination->array_sometimes_filled && $overwrite_empty_array)
                    || ($combination->objectlike_entries
                        && $combination->objectlike_sealed
                        && $overwrite_empty_array)
                ) {
                    if ($combination->array_counts && count($combination->array_counts) === 1) {
                        $array_type = new TNonEmptyArray($generic_type_params);
                        $array_type->count = array_keys($combination->array_counts)[0];
                    } else {
                        $array_type = new TNonEmptyArray($generic_type_params);
                    }
                } else {
                    $array_type = new TArray($generic_type_params);
                }

                $new_types[] = $array_type;
            } elseif (!isset($combination->value_types[$generic_type])) {
                if ($generic_type === 'iterable') {
                    $new_types[] = new TIterable($generic_type_params);
                } else {
                    $new_types[] = new TGenericObject($generic_type, $generic_type_params);
                }
            }
        }

        if ($combination->strings) {
            $new_types = array_merge($new_types, array_values($combination->strings));
        }

        if ($combination->ints) {
            $new_types = array_merge($new_types, array_values($combination->ints));
        }

        if ($combination->floats) {
            $new_types = array_merge($new_types, array_values($combination->floats));
        }

        if ($combination->named_object_types !== null) {
            $combination->value_types += $combination->named_object_types;
        }

        $has_empty = (int) isset($combination->value_types['empty']);

        foreach ($combination->value_types as $type) {
            if ($type instanceof TMixed
                && $combination->mixed_from_loop_isset
                && (count($combination->value_types) > (1 + $has_empty) || count($new_types) > $has_empty)
            ) {
                continue;
            }

            if ($type instanceof TEmpty
                && (count($combination->value_types) > 1 || count($new_types))
            ) {
                continue;
            }

            $new_types[] = $type;
        }

        if (!$new_types) {
            throw new \UnexpectedValueException('There should be types here');
        }

        $union_type = new Union($new_types);

        if ($from_docblock) {
            $union_type->from_docblock = true;
        }

        return $union_type;
    }

    /**
     * @param  Atomic  $type
     * @param  TypeCombination $combination
     * @param  Codebase|null   $codebase
     *
     * @return null|Union
     */
    private static function scrapeTypeProperties(
        Atomic $type,
        TypeCombination $combination,
        $codebase,
        bool $overwrite_empty_array,
        bool $allow_mixed_union,
        int $literal_limit
    ) {
        if ($type instanceof TMixed) {
            $combination->has_mixed = true;
            if ($type->from_loop_isset) {
                if ($combination->mixed_from_loop_isset === null) {
                    $combination->mixed_from_loop_isset = true;
                } else {
                    return null;
                }
            } else {
                $combination->mixed_from_loop_isset = false;
            }

            if ($type instanceof TNonEmptyMixed) {
                $combination->non_empty_mixed = true;

                if ($combination->empty_mixed) {
                    return null;
                }
            } elseif ($type instanceof TEmptyMixed) {
                $combination->empty_mixed = true;

                if ($combination->non_empty_mixed) {
                    return null;
                }
            } else {
                $combination->empty_mixed = true;
                $combination->non_empty_mixed = true;
            }

            if (!$allow_mixed_union) {
                return Type::getMixed((bool) $combination->mixed_from_loop_isset);
            }
        }

        // deal with false|bool => bool
        if (($type instanceof TFalse || $type instanceof TTrue) && isset($combination->value_types['bool'])) {
            return null;
        }

        if (get_class($type) === TBool::class && isset($combination->value_types['false'])) {
            unset($combination->value_types['false']);
        }

        if (get_class($type) === TBool::class && isset($combination->value_types['true'])) {
            unset($combination->value_types['true']);
        }

        if ($type instanceof TArray && isset($combination->type_params['iterable'])) {
            $type_key = 'iterable';
        } elseif ($type instanceof TArray
            && $type->type_params[1]->isMixed()
            && isset($combination->value_types['iterable'])
        ) {
            $type_key = 'iterable';
            $combination->type_params['iterable'] = [Type::getMixed(), Type::getMixed()];
        } elseif ($type instanceof TNamedObject
            && $type->value === 'Traversable'
            && (isset($combination->type_params['iterable']) || isset($combination->value_types['iterable']))
        ) {
            $type_key = 'iterable';

            if (!isset($combination->type_params['iterable'])) {
                $combination->type_params['iterable'] = [Type::getMixed(), Type::getMixed()];
            }

            if (!$type instanceof TGenericObject) {
                $type = new TGenericObject($type->value, [Type::getMixed(), Type::getMixed()]);
            }
        } else {
            $type_key = $type->getKey();
        }

        if ($type instanceof TIterable
            && isset($combination->type_params['array'])
            && ($type->has_docblock_params || $combination->type_params['array'][1]->isMixed())
        ) {
            if (!isset($combination->type_params['iterable'])) {
                $combination->type_params['iterable'] = $combination->type_params['array'];
            } else {
                foreach ($combination->type_params['array'] as $i => $array_type_param) {
                    $iterable_type_param = $combination->type_params['iterable'][$i];
                    $combination->type_params['iterable'][$i] = Type::combineUnionTypes(
                        $iterable_type_param,
                        $array_type_param
                    );
                }
            }

            unset($combination->type_params['array']);
        }

        if ($type instanceof TIterable
            && (isset($combination->named_object_types['Traversable'])
                || isset($combination->type_params['Traversable']))
        ) {
            if (!isset($combination->type_params['iterable'])) {
                $combination->type_params['iterable']
                    = $combination->type_params['Traversable'] ?? [Type::getMixed(), Type::getMixed()];
            } else {
                foreach ($combination->type_params['Traversable'] as $i => $array_type_param) {
                    $iterable_type_param = $combination->type_params['iterable'][$i];
                    $combination->type_params['iterable'][$i] = Type::combineUnionTypes(
                        $iterable_type_param,
                        $array_type_param
                    );
                }
            }

            /** @psalm-suppress PossiblyNullArrayAccess */
            unset(
                $combination->named_object_types['Traversable'],
                $combination->type_params['Traversable']
            );
        }

        if ($type instanceof TArray
            || $type instanceof TGenericObject
            || ($type instanceof TIterable && $type->has_docblock_params)
        ) {
            foreach ($type->type_params as $i => $type_param) {
                if (isset($combination->type_params[$type_key][$i])) {
                    $combination->type_params[$type_key][$i] = Type::combineUnionTypes(
                        $combination->type_params[$type_key][$i],
                        $type_param,
                        $codebase,
                        $overwrite_empty_array
                    );
                } else {
                    $combination->type_params[$type_key][$i] = $type_param;
                }
            }

            if ($type instanceof TArray) {
                if ($type instanceof TNonEmptyArray) {
                    if ($combination->array_counts !== null) {
                        if ($type->count === null) {
                            $combination->array_counts = null;
                        } else {
                            $combination->array_counts[$type->count] = true;
                        }
                    }

                    $combination->array_sometimes_filled = true;
                } else {
                    $combination->array_always_filled = false;
                }
            }
        } elseif ($type instanceof ObjectLike) {
            $existing_objectlike_entries = (bool) $combination->objectlike_entries;
            $possibly_undefined_entries = $combination->objectlike_entries;
            $combination->objectlike_sealed = $combination->objectlike_sealed && $type->sealed;

            foreach ($type->properties as $candidate_property_name => $candidate_property_type) {
                $value_type = isset($combination->objectlike_entries[$candidate_property_name])
                    ? $combination->objectlike_entries[$candidate_property_name]
                    : null;

                if (!$value_type) {
                    $combination->objectlike_entries[$candidate_property_name] = clone $candidate_property_type;
                    // it's possibly undefined if there are existing objectlike entries
                    $combination->objectlike_entries[$candidate_property_name]->possibly_undefined
                        = $existing_objectlike_entries || $candidate_property_type->possibly_undefined;
                } else {
                    $combination->objectlike_entries[$candidate_property_name] = Type::combineUnionTypes(
                        $value_type,
                        $candidate_property_type,
                        $codebase,
                        $overwrite_empty_array
                    );
                }

                unset($possibly_undefined_entries[$candidate_property_name]);
            }

            if ($combination->array_counts !== null) {
                $combination->array_counts[count($type->properties)] = true;
            }

            foreach ($possibly_undefined_entries as $type) {
                $type->possibly_undefined = true;
            }
        } else {
            if ($type instanceof TObject) {
                $combination->named_object_types = null;
                $combination->value_types[$type_key] = $type;
                return null;
            }

            if ($type instanceof TIterable) {
                $combination->value_types[$type_key] = $type;
                return null;
            }

            if ($type instanceof TNamedObject) {
                if ($combination->named_object_types === null) {
                    return null;
                }

                if (isset($combination->named_object_types[$type_key])) {
                    return null;
                }

                if (!$codebase) {
                    $combination->named_object_types[$type_key] = $type;

                    return null;
                }

                if (!$codebase->classlikes->classOrInterfaceExists($type_key)) {
                    // write this to the main list
                    $combination->value_types[$type_key] = $type;
                    return null;
                }

                $is_class = $codebase->classExists($type_key);

                foreach ($combination->named_object_types as $key => $_) {
                    if ($codebase->classExists($key)) {
                        if ($codebase->classExtendsOrImplements($key, $type_key)) {
                            unset($combination->named_object_types[$key]);
                            continue;
                        }

                        if ($is_class) {
                            if ($codebase->classExtends($type_key, $key)) {
                                return null;
                            }
                        }
                    } else {
                        if ($codebase->interfaceExtends($key, $type_key)) {
                            unset($combination->named_object_types[$key]);
                            continue;
                        }

                        if ($is_class) {
                            if ($codebase->classImplements($type_key, $key)) {
                                return null;
                            }
                        } else {
                            if ($codebase->interfaceExtends($type_key, $key)) {
                                return null;
                            }
                        }
                    }
                }

                $combination->named_object_types[$type_key] = $type;

                return null;
            }

            if ($type instanceof TScalar) {
                $combination->strings = null;
                $combination->ints = null;
                $combination->floats = null;
                unset(
                    $combination->value_types['string'],
                    $combination->value_types['int'],
                    $combination->value_types['bool'],
                    $combination->value_types['true'],
                    $combination->value_types['false'],
                    $combination->value_types['float']
                );
                $combination->value_types[$type_key] = $type;
            } elseif ($type instanceof Scalar) {
                if (isset($combination->value_types['scalar'])) {
                    return null;
                }

                if ($type instanceof TArrayKey) {
                    $combination->strings = null;
                    $combination->ints = null;
                    unset(
                        $combination->value_types['string'],
                        $combination->value_types['int']
                    );
                    $combination->value_types[$type_key] = $type;
                } elseif ($type instanceof TString) {
                    if (isset($combination->value_types['array-key'])) {
                        return null;
                    }

                    if ($type instanceof TLiteralString) {
                        if ($combination->strings !== null && count($combination->strings) < $literal_limit) {
                            $combination->strings[$type_key] = $type;
                        } else {
                            $combination->strings = null;

                            if (isset($combination->value_types['string'])
                                && $combination->value_types['string'] instanceof TClassString
                                && $type instanceof TLiteralClassString
                            ) {
                                // do nothing
                            } elseif ($type instanceof TLiteralClassString) {
                                $combination->value_types['string'] = new TClassString();
                            } else {
                                $combination->value_types['string'] = new TString();
                            }
                        }
                    } else {
                        $type_key = 'string';

                        if (!isset($combination->value_types['string'])) {
                            if ($combination->strings) {
                                $has_non_literal_class_string = false;
                                foreach ($combination->strings as $string_type) {
                                    if (!$string_type instanceof TLiteralClassString) {
                                        $has_non_literal_class_string = true;
                                    }
                                }

                                if ($has_non_literal_class_string || !$type instanceof TClassString) {
                                    $combination->value_types[$type_key] = new TString();
                                } else {
                                    $combination->value_types[$type_key] = new TClassString();
                                }
                            } else {
                                $combination->value_types[$type_key] = $type;
                            }
                        } elseif (get_class($combination->value_types['string']) !== TString::class) {
                            if (get_class($type) === TString::class) {
                                $combination->value_types[$type_key] = $type;
                            } elseif ($combination->value_types['string'] instanceof HasClassString
                                && $type instanceof HasClassString
                            ) {
                                $a_named_object = $combination->value_types['string']->hasSingleNamedObject();
                                $b_named_object = $type->hasSingleNamedObject();

                                if ($a_named_object && $b_named_object) {
                                    $a_object = $combination->value_types['string']->getSingleNamedObject();
                                    $b_object = $type->getSingleNamedObject();

                                    if ($a_object->value === $b_object->value) {
                                        $combination->value_types[$type_key] = new TClassString(
                                            $a_object->value,
                                            $a_object
                                        );
                                    } else {
                                        $combination->value_types[$type_key] = new TClassString();
                                    }
                                } else {
                                    $combination->value_types[$type_key] = new TClassString();
                                }
                            } elseif (get_class($combination->value_types['string']) !== get_class($type)) {
                                $combination->value_types[$type_key] = new TString();
                            }
                        }

                        $combination->strings = null;
                    }
                } elseif ($type instanceof TInt) {
                    if (isset($combination->value_types['array-key'])) {
                        return null;
                    }

                    if ($type instanceof TLiteralInt) {
                        if ($combination->ints !== null && count($combination->ints) < $literal_limit) {
                            $combination->ints[$type_key] = $type;
                        } else {
                            $combination->ints = null;
                            $combination->value_types['int'] = new TInt();
                        }
                    } else {
                        $combination->ints = null;
                        $combination->value_types['int'] = $type;
                    }
                } elseif ($type instanceof TFloat) {
                    if ($type instanceof TLiteralFloat) {
                        if ($combination->floats !== null && count($combination->floats) < $literal_limit) {
                            $combination->floats[$type_key] = $type;
                        } else {
                            $combination->floats = null;
                            $combination->value_types['float'] = new TFloat();
                        }
                    } else {
                        $combination->floats = null;
                        $combination->value_types['float'] = $type;
                    }
                } else {
                    $combination->value_types[$type_key] = $type;
                }
            } else {
                $combination->value_types[$type_key] = $type;
            }
        }
    }
}
