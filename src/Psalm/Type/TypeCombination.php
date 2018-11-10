<?php
namespace Psalm\Type;

use Psalm\Exception\TypeParseTreeException;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\ObjectLike;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TEmpty;
use Psalm\Type\Atomic\TEmptyMixed;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TResource;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTrue;
use Psalm\Type\Atomic\TVoid;
use Psalm\Type\ParseTree;
use Psalm\Type\TypeCombination;
use Psalm\Type\Union;

class TypeCombination
{
    /** @var array<string, Atomic> */
    private $value_types = [];

    /** @var array<string, array<int, Union>> */
    private $type_params = [];

    /** @var array<int, bool>|null */
    private $array_counts = [];

    /** @var bool */
    private $array_always_filled = true;

    /** @var array<string|int, Union> */
    private $objectlike_entries = [];

    /** @var bool */
    private $objectlike_sealed = true;

    /** @var array<int, Atomic\TLiteralString>|null */
    private $strings = [];

    /** @var array<int, Atomic\TLiteralInt>|null */
    private $ints = [];

    /** @var array<int, Atomic\TLiteralFloat>|null */
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
     *
     * @return Union
     * @psalm-suppress TypeCoercion
     */
    public static function combineTypes(array $types)
    {
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

        $has_null = false;
        $has_mixed = false;
        $has_non_mixed = false;

        foreach ($types as $type) {
            $from_docblock = $from_docblock || $type->from_docblock;

            $result = self::scrapeTypeProperties($type, $combination);

            if ($type instanceof TNull) {
                $has_null = true;
            }

            if ($type instanceof TMixed) {
                $has_mixed = true;
            } else {
                $has_non_mixed = true;
            }

            if ($result) {
                if ($from_docblock) {
                    $result->from_docblock = true;
                }

                return $result;
            }
        }

        if ($has_null && $has_mixed) {
            return Type::getMixed();
        }

        if (!$has_non_mixed) {
            return Type::getMixed(true);
        }

        if (count($combination->value_types) === 1
            && !count($combination->objectlike_entries)
            && !count($combination->type_params)
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

        $new_types = [];

        if (count($combination->objectlike_entries) &&
            (!isset($combination->type_params['array'])
                || $combination->type_params['array'][1]->isEmpty())
        ) {
            $objectlike = new ObjectLike($combination->objectlike_entries);

            if ($combination->objectlike_sealed && !isset($combination->type_params['array'])) {
                $objectlike->sealed = true;
            }

            $new_types[] = $objectlike;

            // if we're merging an empty array with an object-like, clobber empty array
            unset($combination->type_params['array']);
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
                                $objectlike_generic_type
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
                        $objectlike_key_type
                    );
                    $generic_type_params[1] = Type::combineUnionTypes(
                        $generic_type_params[1],
                        $objectlike_generic_type
                    );
                }

                if ($combination->array_always_filled) {
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
                $new_types[] = new TGenericObject($generic_type, $generic_type_params);
            }
        }

        if ($combination->strings) {
            $new_types = array_merge($new_types, $combination->strings);
        }

        if ($combination->ints) {
            $new_types = array_merge($new_types, $combination->ints);
        }

        if ($combination->floats) {
            $new_types = array_merge($new_types, $combination->floats);
        }

        foreach ($combination->value_types as $type) {
            if (!($type instanceof TEmpty)
                || (count($combination->value_types) === 1
                    && !count($new_types))
            ) {
                $new_types[] = $type;
            }
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
     *
     * @return null|Union
     */
    private static function scrapeTypeProperties(Atomic $type, TypeCombination $combination)
    {
        if ($type instanceof TMixed) {
            if ($type->from_isset || $type instanceof TEmptyMixed) {
                return null;
            }

            return Type::getMixed();
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

        $type_key = $type->getKey();

        if ($type instanceof TArray || $type instanceof TGenericObject) {
            foreach ($type->type_params as $i => $type_param) {
                if (isset($combination->type_params[$type_key][$i])) {
                    $combination->type_params[$type_key][$i] = Type::combineUnionTypes(
                        $combination->type_params[$type_key][$i],
                        $type_param
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
                        $candidate_property_type
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
            if ($type instanceof TString) {
                if ($type instanceof TLiteralString) {
                    if ($combination->strings !== null && count($combination->strings) < 30) {
                        $combination->strings[] = $type;
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
                    $combination->strings = null;

                    if (!isset($combination->value_types['string'])) {
                        $combination->value_types[$type_key] = $type;
                    } elseif (get_class($combination->value_types['string']) !== TString::class) {
                        if (get_class($type) === TString::class) {
                            $combination->value_types[$type_key] = $type;
                        } elseif (get_class($combination->value_types['string']) !== get_class($type)) {
                            $combination->value_types[$type_key] = new TString();
                        }
                    }
                }
            } elseif ($type instanceof TInt) {
                if ($type instanceof TLiteralInt) {
                    if ($combination->ints !== null && count($combination->ints) < 30) {
                        $combination->ints[] = $type;
                    } else {
                        $combination->ints = null;
                        $combination->value_types['int'] = new TInt();
                    }
                } else {
                    $combination->ints = null;
                    $combination->value_types[$type_key] = $type;
                }
            } elseif ($type instanceof TFloat) {
                if ($type instanceof TLiteralFloat) {
                    if ($combination->floats !== null && count($combination->floats) < 30) {
                        $combination->floats[] = $type;
                    } else {
                        $combination->floats = null;
                        $combination->value_types['float'] = new TFloat();
                    }
                } else {
                    $combination->floats = null;
                    $combination->value_types[$type_key] = $type;
                }
            } else {
                $combination->value_types[$type_key] = $type;
            }
        }
    }
}
