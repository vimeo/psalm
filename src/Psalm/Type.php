<?php

namespace Psalm;

use InvalidArgumentException;
use LogicException;
use Psalm\Internal\Type\Comparator\AtomicTypeComparator;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Internal\Type\TypeCombiner;
use Psalm\Internal\Type\TypeParser;
use Psalm\Internal\Type\TypeTokenizer;
use Psalm\Plugin\EventHandler\Event\StringInterpreterEvent;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TArrayKey;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIntRange;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TLowercaseString;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNever;
use Psalm\Type\Atomic\TNonEmptyList;
use Psalm\Type\Atomic\TNonEmptyLowercaseString;
use Psalm\Type\Atomic\TNonEmptyString;
use Psalm\Type\Atomic\TNonFalsyString;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Atomic\TNumericString;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TObjectWithProperties;
use Psalm\Type\Atomic\TResource;
use Psalm\Type\Atomic\TScalar;
use Psalm\Type\Atomic\TSingleLetter;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTrue;
use Psalm\Type\Atomic\TVoid;
use Psalm\Type\MutableUnion;
use Psalm\Type\Union;
use UnexpectedValueException;

use function array_merge;
use function array_pop;
use function array_shift;
use function array_values;
use function explode;
use function get_class;
use function implode;
use function preg_quote;
use function preg_replace;
use function stripos;
use function strlen;
use function strpos;
use function strtolower;
use function substr;

abstract class Type
{
    /**
     * Parses a string type representation
     *
     * @param  array{int,int}|null   $php_version
     * @param  array<string, array<string, Union>> $template_type_map
     */
    public static function parseString(
        string $type_string,
        ?int $analysis_php_version_id = null,
        array $template_type_map = []
    ): Union {
        return TypeParser::parseTokens(
            TypeTokenizer::tokenize(
                $type_string
            ),
            $analysis_php_version_id,
            $template_type_map
        );
    }

    public static function getFQCLNFromString(
        string $class,
        Aliases $aliases
    ): string {
        if ($class === '') {
            throw new InvalidArgumentException('$class cannot be empty');
        }

        if ($class[0] === '\\') {
            return substr($class, 1);
        }

        $imported_namespaces = $aliases->uses;

        if (strpos($class, '\\') !== false) {
            $class_parts = explode('\\', $class);
            $first_namespace = array_shift($class_parts);

            if (isset($imported_namespaces[strtolower($first_namespace)])) {
                return $imported_namespaces[strtolower($first_namespace)] . '\\' . implode('\\', $class_parts);
            }
        } elseif (isset($imported_namespaces[strtolower($class)])) {
            return $imported_namespaces[strtolower($class)];
        }

        $namespace = $aliases->namespace;

        return ($namespace ? $namespace . '\\' : '') . $class;
    }

    /**
     * @param array<lowercase-string, string> $aliased_classes
     *
     * @psalm-pure
     */
    public static function getStringFromFQCLN(
        string $value,
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        bool $allow_self = false,
        bool $is_static = false
    ): string {
        if ($allow_self && $value === $this_class) {
            if ($is_static) {
                return 'static';
            }
            return 'self';
        }

        if (isset($aliased_classes[strtolower($value)])) {
            return $aliased_classes[strtolower($value)];
        }

        if ($namespace && stripos($value, $namespace . '\\') === 0) {
            $candidate = preg_replace(
                '/^' . preg_quote($namespace . '\\') . '/i',
                '',
                $value
            );

            $candidate_parts = explode('\\', $candidate);

            if (!isset($aliased_classes[strtolower($candidate_parts[0])])) {
                return $candidate;
            }
        } elseif (!$namespace && strpos($value, '\\') === false) {
            return $value;
        }

        if (strpos($value, '\\')) {
            $parts = explode('\\', $value);

            $suffix = array_pop($parts);

            while ($parts) {
                $left = implode('\\', $parts);

                if (isset($aliased_classes[strtolower($left)])) {
                    return $aliased_classes[strtolower($left)] . '\\' . $suffix;
                }

                $suffix = array_pop($parts) . '\\' . $suffix;
            }
        }

        return '\\' . $value;
    }

    /**
     * @psalm-pure
     */
    public static function getInt(bool $from_calculation = false, ?int $value = null): Union
    {
        if ($value !== null) {
            return new Union([new TLiteralInt($value)], [
                'from_calculation' => $from_calculation
            ]);
        }
        return new Union([new TInt()], [
            'from_calculation' => $from_calculation
        ]);
    }

    /**
     * @psalm-pure
     */
    public static function getLowercaseString(): Union
    {
        $type = new TLowercaseString();

        return new Union([$type]);
    }

    /**
     * @psalm-pure
     */
    public static function getNonEmptyLowercaseString(): Union
    {
        $type = new TNonEmptyLowercaseString();

        return new Union([$type]);
    }

    /**
     * @psalm-pure
     */
    public static function getNonEmptyString(): Union
    {
        $type = new TNonEmptyString();

        return new Union([$type]);
    }

    /**
     * @psalm-pure
     */
    public static function getNonFalsyString(): Union
    {
        $type = new TNonFalsyString();

        return new Union([$type]);
    }

    /**
     * @psalm-pure
     */
    public static function getNumeric(): Union
    {
        $type = new TNumeric;

        return new Union([$type]);
    }

    /**
     * @psalm-pure
     */
    public static function getNumericString(): Union
    {
        $type = new TNumericString;

        return new Union([$type]);
    }

    public static function getString(?string $value = null): Union
    {
        $type = null;

        if ($value !== null) {
            $config = Config::getInstance();

            $event = new StringInterpreterEvent($value);

            $type = $config->eventDispatcher->dispatchStringInterpreter($event);

            if (!$type) {
                if (strlen($value) < $config->max_string_length) {
                    $type = new TLiteralString($value);
                } else {
                    $type = new TNonEmptyString();
                }
            }
        }

        if (!$type) {
            $type = new TString();
        }

        return new Union([$type]);
    }

    /**
     * @psalm-pure
     */
    public static function getSingleLetter(): Union
    {
        $type = new TSingleLetter;

        return new Union([$type]);
    }

    /**
     * @psalm-pure
     */
    public static function getClassString(string $extends = 'object'): Union
    {
        return new Union([
            new TClassString(
                $extends,
                $extends === 'object'
                    ? null
                    : new TNamedObject($extends)
            ),
        ]);
    }

    /**
     * @psalm-pure
     */
    public static function getLiteralClassString(string $class_type, bool $definite_class = false): Union
    {
        $type = new TLiteralClassString($class_type, $definite_class);

        return new Union([$type]);
    }

    /**
     * @psalm-pure
     */
    public static function getNull(bool $from_docblock = false): Union
    {
        $type = new TNull($from_docblock);

        return new Union([$type]);
    }

    /**
     * @psalm-pure
     */
    public static function getMixed(bool $from_loop_isset = false, bool $from_docblock = false): Union
    {
        $type = new TMixed($from_loop_isset, $from_docblock);

        return new Union([$type]);
    }

    /**
     * @psalm-pure
     */
    public static function getScalar(bool $from_docblock = false): Union
    {
        $type = new TScalar($from_docblock);

        return new Union([$type]);
    }

    /**
     * @psalm-pure
     */
    public static function getNever(bool $from_docblock = false): Union
    {
        $type = new TNever($from_docblock);

        return new Union([$type]);
    }

    /**
     * @psalm-pure
     */
    public static function getBool(bool $from_docblock = false): Union
    {
        $type = new TBool($from_docblock);

        return new Union([$type]);
    }

    /**
     * @psalm-pure
     */
    public static function getFloat(?float $value = null, bool $from_docblock = false): Union
    {
        if ($value !== null) {
            $type = new TLiteralFloat($value, $from_docblock);
        } else {
            $type = new TFloat($from_docblock);
        }

        return new Union([$type]);
    }

    /**
     * @psalm-pure
     */
    public static function getObject(): Union
    {
        $type = new TObject;

        return new Union([$type]);
    }

    /**
     * @psalm-pure
     */
    public static function getClosure(): Union
    {
        $type = new TClosure('Closure');

        return new Union([$type]);
    }

    /**
     * @psalm-pure
     */
    public static function getArrayKey(bool $from_docblock = false): Union
    {
        $type = new TArrayKey($from_docblock);

        return new Union([$type]);
    }

    /**
     * @psalm-pure
     */
    public static function getArray(): Union
    {
        $type = new TArray(
            [
                new Union([new TArrayKey]),
                new Union([new TMixed]),
            ]
        );

        return new Union([$type]);
    }

    /**
     * @psalm-pure
     */
    public static function getEmptyArray(): Union
    {
        $array_type = new TArray(
            [
                new Union([new TNever()]),
                new Union([new TNever()]),
            ]
        );

        return new Union([
            $array_type,
        ]);
    }

    /**
     * @psalm-pure
     */
    public static function getList(): Union
    {
        $type = new TList(new Union([new TMixed]));

        return new Union([$type]);
    }

    /**
     * @psalm-pure
     */
    public static function getNonEmptyList(): Union
    {
        $type = new TNonEmptyList(new Union([new TMixed]));

        return new Union([$type]);
    }

    /**
     * @psalm-pure
     */
    public static function getVoid(bool $from_docblock = false): Union
    {
        $type = new TVoid($from_docblock);

        return new Union([$type]);
    }

    /**
     * @psalm-pure
     */
    public static function getFalse(bool $from_docblock = false): Union
    {
        $type = new TFalse($from_docblock);

        return new Union([$type]);
    }

    /**
     * @psalm-pure
     */
    public static function getTrue(bool $from_docblock = false): Union
    {
        $type = new TTrue($from_docblock);

        return new Union([$type]);
    }

    /**
     * @psalm-pure
     */
    public static function getResource(bool $from_docblock = false): Union
    {
        return new Union([new TResource($from_docblock)]);
    }

    /**
     * @psalm-external-mutation-free
     * @param non-empty-list<Union> $union_types
     */
    public static function combineUnionTypeArray(array $union_types, ?Codebase $codebase): Union
    {
        $first_type = array_pop($union_types);

        foreach ($union_types as $type) {
            $first_type = self::combineUnionTypes($first_type, $type, $codebase);
        }

        return $first_type;
    }

    /**
     * Combines two union types into one
     *
     * @param  int    $literal_limit any greater number of literal types than this
     *                               will be merged to a scalar
     *
     * @psalm-external-mutation-free
     *
     * @psalm-suppress ImpurePropertyAssignment We're not mutating external instances
     * @psalm-suppress InaccessibleProperty We're not mutating external instances
     */
    public static function combineUnionTypes(
        ?Union $type_1,
        ?Union $type_2,
        ?Codebase $codebase = null,
        bool $overwrite_empty_array = false,
        bool $allow_mixed_union = true,
        int $literal_limit = 500,
        ?bool $possibly_undefined = null
    ): Union {
        if ($type_2 === null && $type_1 === null) {
            throw new UnexpectedValueException('At least one type must be provided to combine');
        }

        if ($type_1 === null) {
            if ($possibly_undefined !== null) {
                return $type_2->setPossiblyUndefined($possibly_undefined);
            }
            return $type_2;
        }

        if ($type_2 === null) {
            if ($possibly_undefined !== null) {
                return $type_1->setPossiblyUndefined($possibly_undefined);
            }
            return $type_1;
        }

        if ($type_1 === $type_2) {
            if ($possibly_undefined !== null) {
                return $type_1->setPossiblyUndefined($possibly_undefined);
            }
            return $type_1;
        }

        if ($type_1->isVanillaMixed() && $type_2->isVanillaMixed()) {
            $combined_type = self::getMixed();
        } else {
            $both_failed_reconciliation = false;

            if ($type_1->failed_reconciliation) {
                if ($type_2->failed_reconciliation) {
                    $both_failed_reconciliation = true;
                } else {
                    return $type_2->setProperties([
                        'parent_nodes' => array_merge($type_2->parent_nodes, $type_1->parent_nodes),
                        'possibly_undefined' => $possibly_undefined ?? $type_2->possibly_undefined
                    ]);
                }
            } elseif ($type_2->failed_reconciliation) {
                return $type_1->setProperties([
                    'parent_nodes' => array_merge($type_1->parent_nodes, $type_2->parent_nodes),
                    'possibly_undefined' => $possibly_undefined ?? $type_1->possibly_undefined
                ]);
            }

            $combined_type = TypeCombiner::combine(
                array_merge(
                    array_values($type_1->getAtomicTypes()),
                    array_values($type_2->getAtomicTypes())
                ),
                $codebase,
                $overwrite_empty_array,
                $allow_mixed_union,
                $literal_limit
            );

            if (!$type_1->initialized || !$type_2->initialized) {
                $combined_type->initialized = false;
            }

            if ($type_1->from_docblock || $type_2->from_docblock) {
                $combined_type->from_docblock = true;
            }

            if ($type_1->from_calculation || $type_2->from_calculation) {
                $combined_type->from_calculation = true;
            }

            if ($type_1->ignore_nullable_issues || $type_2->ignore_nullable_issues) {
                $combined_type->ignore_nullable_issues = true;
            }

            if ($type_1->ignore_falsable_issues || $type_2->ignore_falsable_issues) {
                $combined_type->ignore_falsable_issues = true;
            }

            if ($type_1->had_template && $type_2->had_template) {
                $combined_type->had_template = true;
            }

            if ($type_1->reference_free && $type_2->reference_free) {
                $combined_type->reference_free = true;
            }

            if ($both_failed_reconciliation) {
                $combined_type->failed_reconciliation = true;
            }
        }

        if ($possibly_undefined !== null) {
            $combined_type->possibly_undefined = $possibly_undefined;
        } elseif ($type_1->possibly_undefined || $type_2->possibly_undefined) {
            $combined_type->possibly_undefined = true;
        }

        if ($type_1->possibly_undefined_from_try || $type_2->possibly_undefined_from_try) {
            $combined_type->possibly_undefined_from_try = true;
        }

        if ($type_1->parent_nodes || $type_2->parent_nodes) {
            $combined_type->parent_nodes = $type_1->parent_nodes + $type_2->parent_nodes;
        }

        if ($type_1->by_ref || $type_2->by_ref) {
            $combined_type->by_ref = true;
        }

        return $combined_type;
    }

    /**
     * Combines two union types into one via an intersection
     *
     *
     */
    public static function intersectUnionTypes(
        ?Union $type_1,
        ?Union $type_2,
        Codebase $codebase
    ): ?Union {
        if ($type_2 === null && $type_1 === null) {
            throw new UnexpectedValueException('At least one type must be provided to combine');
        }

        if ($type_1 === null) {
            return $type_2;
        }

        if ($type_2 === null) {
            return $type_1;
        }

        if ($type_1 === $type_2) {
            return $type_1;
        }

        $intersection_performed = false;
        $type_1_mixed = $type_1->isMixed();
        $type_2_mixed = $type_2->isMixed();

        $possibly_undefined = $type_1->possibly_undefined && $type_2->possibly_undefined;

        if ($type_1_mixed && $type_2_mixed) {
            $combined_type = new Union([new TMixed()], ['possibly_undefined' => $possibly_undefined]);
        } else {
            $both_failed_reconciliation = false;

            if ($type_1->failed_reconciliation) {
                if ($type_2->failed_reconciliation) {
                    $both_failed_reconciliation = true;
                } else {
                    return $type_2;
                }
            } elseif ($type_2->failed_reconciliation) {
                return $type_1;
            }

            if ($type_1_mixed) {
                $combined_type = $type_2->getBuilder();
                $intersection_performed = true;
            } elseif ($type_2_mixed) {
                $combined_type = $type_1->getBuilder();
                $intersection_performed = true;
            } else {
                $combined_type = null;
                foreach ($type_1->getAtomicTypes() as $type_1_atomic) {
                    foreach ($type_2->getAtomicTypes() as $type_2_atomic) {
                        $intersection_atomic = self::intersectAtomicTypes(
                            $type_1_atomic,
                            $type_2_atomic,
                            $codebase,
                            $intersection_performed
                        );

                        if (null !== $intersection_atomic) {
                            if (null === $combined_type) {
                                $combined_type = new MutableUnion([$intersection_atomic]);
                            } else {
                                $combined_type->addType($intersection_atomic);
                            }
                        }
                    }
                }
            }

            //if a type is contained by the other, the intersection is the narrowest type
            if (!$intersection_performed) {
                $type_1_in_2 = UnionTypeComparator::isContainedBy($codebase, $type_1, $type_2);
                $type_2_in_1 = UnionTypeComparator::isContainedBy($codebase, $type_2, $type_1);
                if ($type_1_in_2) {
                    $intersection_performed = true;
                    $combined_type = $type_1->getBuilder();
                } elseif ($type_2_in_1) {
                    $intersection_performed = true;
                    $combined_type = $type_2->getBuilder();
                }
            }

            if ($combined_type !== null) {
                if (!$type_1->initialized && !$type_2->initialized) {
                    $combined_type->initialized = false;
                }

                if ($type_1->possibly_undefined_from_try && $type_2->possibly_undefined_from_try) {
                    $combined_type->possibly_undefined_from_try = true;
                }

                if ($type_1->from_docblock && $type_2->from_docblock) {
                    $combined_type->from_docblock = true;
                }

                if ($type_1->from_calculation && $type_2->from_calculation) {
                    $combined_type->from_calculation = true;
                }

                if ($type_1->ignore_nullable_issues && $type_2->ignore_nullable_issues) {
                    $combined_type->ignore_nullable_issues = true;
                }

                if ($type_1->ignore_falsable_issues && $type_2->ignore_falsable_issues) {
                    $combined_type->ignore_falsable_issues = true;
                }

                if ($both_failed_reconciliation) {
                    $combined_type->failed_reconciliation = true;
                }

                $combined_type->possibly_undefined = $possibly_undefined;

                $combined_type = $combined_type->freeze();
            }
        }

        if (!$intersection_performed && $type_1->getId() !== $type_2->getId()) {
            return null;
        }

        return $combined_type;
    }

    private static function intersectAtomicTypes(
        Atomic $type_1_atomic,
        Atomic $type_2_atomic,
        Codebase $codebase,
        bool &$intersection_performed
    ): ?Atomic {
        $intersection_atomic = null;
        $wider_type = null;
        if ($type_1_atomic instanceof TNamedObject
            && $type_2_atomic instanceof TNamedObject
        ) {
            if (($type_1_atomic->value === $type_2_atomic->value
                && get_class($type_1_atomic) === TNamedObject::class
                && get_class($type_2_atomic) !== TNamedObject::class)
            ) {
                $intersection_atomic = $type_2_atomic;
                $wider_type = $type_1_atomic;
                $intersection_performed = true;
            } elseif (($type_1_atomic->value === $type_2_atomic->value
                && get_class($type_2_atomic) === TNamedObject::class
                && get_class($type_1_atomic) !== TNamedObject::class)
            ) {
                $intersection_atomic = $type_1_atomic;
                $wider_type = $type_2_atomic;
                $intersection_performed = true;
            }
        }
        if ($type_1_atomic instanceof TInt && $type_2_atomic instanceof TInt) {
            $int_intersection = TIntRange::intersectIntRanges(
                TIntRange::convertToIntRange($type_1_atomic),
                TIntRange::convertToIntRange($type_2_atomic)
            );
            if ($int_intersection
                && ($int_intersection->min_bound !== null || $int_intersection->max_bound !== null)
            ) {
                $intersection_performed = true;
                if ($int_intersection->min_bound !== null
                    && $int_intersection->min_bound === $int_intersection->max_bound
                ) {
                    return new TLiteralInt($int_intersection->min_bound);
                }
                return $int_intersection;
            }
        }

        if (null === $intersection_atomic) {
            if (AtomicTypeComparator::isContainedBy(
                $codebase,
                $type_2_atomic,
                $type_1_atomic
            )) {
                $intersection_atomic = $type_2_atomic;
                $wider_type = $type_1_atomic;
                $intersection_performed = true;
            } elseif (AtomicTypeComparator::isContainedBy(
                $codebase,
                $type_1_atomic,
                $type_2_atomic
            )) {
                $intersection_atomic = $type_1_atomic;
                $wider_type = $type_2_atomic;
                $intersection_performed = true;
            }

            if ($intersection_atomic
                && !self::hasIntersection($type_1_atomic)
                && !self::hasIntersection($type_2_atomic)
            ) {
                return $intersection_atomic;
            }
        }

        if (self::mayHaveIntersection($type_1_atomic, $codebase)
            && self::mayHaveIntersection($type_2_atomic, $codebase)
        ) {
            /** @psalm-suppress TypeDoesNotContainType */
            if ($type_1_atomic instanceof TNamedObject && $type_2_atomic instanceof TNamedObject) {
                $first = $codebase->classlike_storage_provider->get($type_1_atomic->value);
                $second = $codebase->classlike_storage_provider->get($type_2_atomic->value);
                $first_is_class = !$first->is_interface && !$first->is_trait;
                $second_is_class = !$second->is_interface && !$second->is_trait;
                if ($first_is_class && $second_is_class) {
                    return $intersection_atomic;
                }
            }
            if ($intersection_atomic === null && $wider_type === null) {
                $intersection_atomic = $type_1_atomic;
                $wider_type = $type_2_atomic;
            }
            if ($intersection_atomic === null || $wider_type === null) {
                throw new LogicException(
                    '$intersection_atomic and $wider_type should be both set or null.'
                    .' Check the preceding code for errors.'
                    .' Did you forget to assign one of the variables?'
                );
            }
            if (!self::mayHaveIntersection($intersection_atomic, $codebase)
                || !self::mayHaveIntersection($wider_type, $codebase)
            ) {
                throw new LogicException(
                    '$intersection_atomic and $wider_type should be both support intersection.'
                    .' Check the preceding code for errors.'
                );
            }

            $intersection_performed = true;

            $wider_type_clone = $wider_type->setIntersectionTypes([]);

            $final_intersection = array_merge(
                [$wider_type_clone->getKey() => $wider_type_clone],
                $intersection_atomic->getIntersectionTypes()
            );

            $wider_type_intersection_types = $wider_type->getIntersectionTypes();

            foreach ($wider_type_intersection_types as $wider_type_intersection_type) {
                $final_intersection[$wider_type_intersection_type->getKey()]
                    = $wider_type_intersection_type;
            }

            return $intersection_atomic->setIntersectionTypes($final_intersection);
        }

        return $intersection_atomic;
    }

    /**
     * @psalm-assert-if-true TIterable|TNamedObject|TTemplateParam|TObjectWithProperties $type
     */
    private static function mayHaveIntersection(Atomic $type, Codebase $codebase): bool
    {
        if ($type instanceof TIterable
            || $type instanceof TTemplateParam
            || $type instanceof TObjectWithProperties
        ) {
            return true;
        }
        if (!$type instanceof TNamedObject) {
            return false;
        }
        $storage = $codebase->classlike_storage_provider->get($type->value);
        return !$storage->final;
    }

    private static function hasIntersection(Atomic $type): bool
    {
        return ($type instanceof TIterable
                || $type instanceof TNamedObject
                || $type instanceof TTemplateParam
                || $type instanceof TObjectWithProperties
            ) && $type->extra_types;
    }
}
