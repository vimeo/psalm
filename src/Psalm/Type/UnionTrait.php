<?php

namespace Psalm\Type;

use InvalidArgumentException;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Internal\TypeVisitor\CanContainObjectTypeVisitor;
use Psalm\Internal\TypeVisitor\ClasslikeReplacer;
use Psalm\Internal\TypeVisitor\ContainsClassLikeVisitor;
use Psalm\Internal\TypeVisitor\ContainsLiteralVisitor;
use Psalm\Internal\TypeVisitor\TemplateTypeCollector;
use Psalm\Internal\TypeVisitor\TypeChecker;
use Psalm\Internal\TypeVisitor\TypeScanner;
use Psalm\StatementsSource;
use Psalm\Storage\FileStorage;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TClassStringMap;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Atomic\TConditional;
use Psalm\Type\Atomic\TEmptyMixed;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIntRange;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TLowercaseString;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNever;
use Psalm\Type\Atomic\TNonEmptyLowercaseString;
use Psalm\Type\Atomic\TNonEmptyString;
use Psalm\Type\Atomic\TNonspecificLiteralInt;
use Psalm\Type\Atomic\TNonspecificLiteralString;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTemplateParamClass;
use Psalm\Type\Atomic\TTrue;

use function array_filter;
use function array_unique;
use function count;
use function get_class;
use function implode;
use function ksort;
use function reset;
use function sort;
use function strpos;

use const ARRAY_FILTER_USE_BOTH;

/**
 * @psalm-immutable
 * @psalm-import-type TProperties from Union
 */
trait UnionTrait
{
    /**
     * Constructs an Union instance
     *
     * @psalm-external-mutation-free
     * @param non-empty-array<Atomic>     $types
     * @param TProperties $properties
     */
    public function __construct(array $types, array $properties = [])
    {
        foreach ($properties as $key => $value) {
            $this->{$key} = $value;
        }
        $this->literal_int_types = [];
        $this->literal_string_types = [];
        $this->literal_float_types = [];
        $this->typed_class_strings = [];
        $this->checked = false;
        $this->id = null;
        $this->exact_id = null;

        $keyed_types = [];

        $from_docblock = $this->from_docblock;
        foreach ($types as $type) {
            $key = $type->getKey();
            $keyed_types[$key] = $type;

            if ($type instanceof TLiteralInt) {
                $this->literal_int_types[$key] = $type;
            } elseif ($type instanceof TLiteralString) {
                $this->literal_string_types[$key] = $type;
            } elseif ($type instanceof TLiteralFloat) {
                $this->literal_float_types[$key] = $type;
            } elseif ($type instanceof TClassString
                && ($type->as_type || $type instanceof TTemplateParamClass)
            ) {
                $this->typed_class_strings[$key] = $type;
            } elseif ($type instanceof TNever) {
                $this->explicit_never = true;
            }

            $from_docblock = $from_docblock || $type->from_docblock;
        }

        $this->from_docblock = $from_docblock;
        $this->types = $keyed_types;
    }

    /**
     * @psalm-mutation-free
     * @return non-empty-array<string, Atomic>
     */
    public function getAtomicTypes(): array
    {
        return $this->types;
    }

    /**
     * @psalm-mutation-free
     */
    public function __toString(): string
    {
        $types = [];

        $printed_int = false;
        $printed_float = false;
        $printed_string = false;

        foreach ($this->types as $type) {
            if ($type instanceof TLiteralFloat) {
                if ($printed_float) {
                    continue;
                }

                $printed_float = true;
            } elseif ($type instanceof TLiteralString) {
                if ($printed_string) {
                    continue;
                }

                $printed_string = true;
            } elseif ($type instanceof TLiteralInt) {
                if ($printed_int) {
                    continue;
                }

                $printed_int = true;
            }

            $types[] = $type->getId(false);
        }

        sort($types);
        return implode('|', $types);
    }

    /**
     * @psalm-mutation-free
     */
    public function getKey(): string
    {
        $types = [];

        $printed_int = false;
        $printed_float = false;
        $printed_string = false;

        foreach ($this->types as $type) {
            if ($type instanceof TLiteralFloat) {
                if ($printed_float) {
                    continue;
                }

                $types[] = 'float';
                $printed_float = true;
            } elseif ($type instanceof TLiteralString) {
                if ($printed_string) {
                    continue;
                }

                $types[] = 'string';
                $printed_string = true;
            } elseif ($type instanceof TLiteralInt) {
                if ($printed_int) {
                    continue;
                }

                $types[] = 'int';
                $printed_int = true;
            } else {
                $types[] = $type->getKey();
            }
        }

        sort($types);
        return implode('|', $types);
    }

    /**
     * @psalm-mutation-free
     */
    public function getId(bool $exact = true): string
    {
        if ($exact && $this->exact_id) {
            return $this->exact_id;
        } elseif (!$exact && $this->id) {
            return $this->id;
        }

        $types = [];
        foreach ($this->types as $type) {
            $types[] = $type->getId($exact);
        }
        $types = array_unique($types);
        sort($types);

        if (count($types) > 1) {
            foreach ($types as $i => $type) {
                if (strpos($type, ' as ') && strpos($type, '(') === false) {
                    $types[$i] = '(' . $type . ')';
                }
            }
        }

        $id = implode('|', $types);

        if ($exact) {
            /** @psalm-suppress ImpurePropertyAssignment, InaccessibleProperty Cache */
            $this->exact_id = $id;
        } else {
            /** @psalm-suppress ImpurePropertyAssignment, InaccessibleProperty Cache */
            $this->id = $id;
        }

        return $id;
    }

    /**
     * @param  array<lowercase-string, string> $aliased_classes
     * @psalm-mutation-free
     */
    public function toNamespacedString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        bool $use_phpdoc_format
    ): string {
        $other_types = [];

        $literal_ints = [];
        $literal_strings = [];

        $has_non_literal_int = false;
        $has_non_literal_string = false;

        foreach ($this->types as $type) {
            $type_string = $type->toNamespacedString($namespace, $aliased_classes, $this_class, $use_phpdoc_format);
            if ($type instanceof TLiteralInt) {
                $literal_ints[] = $type_string;
            } elseif ($type instanceof TLiteralString) {
                $literal_strings[] = $type_string;
            } else {
                if (get_class($type) === TString::class) {
                    $has_non_literal_string = true;
                } elseif (get_class($type) === TInt::class) {
                    $has_non_literal_int = true;
                }
                $other_types[] = $type_string;
            }
        }

        if (count($literal_ints) <= 3 && !$has_non_literal_int) {
            $other_types = [...$other_types, ...$literal_ints];
        } else {
            $other_types[] = 'int';
        }

        if (count($literal_strings) <= 3 && !$has_non_literal_string) {
            $other_types = [...$other_types, ...$literal_strings];
        } else {
            $other_types[] = 'string';
        }

        sort($other_types);
        return implode('|', array_unique($other_types));
    }

    /**
     * @psalm-mutation-free
     * @param  array<lowercase-string, string> $aliased_classes
     */
    public function toPhpString(
        ?string $namespace,
        array   $aliased_classes,
        ?string $this_class,
        int     $analysis_php_version_id
    ): ?string {
        if (!$this->isSingleAndMaybeNullable()) {
            if ($analysis_php_version_id < 8_00_00) {
                return null;
            }
        } elseif ($analysis_php_version_id < 7_00_00
            || (isset($this->types['null']) && $analysis_php_version_id < 7_01_00)
        ) {
            return null;
        }

        $types = $this->types;

        $nullable = false;

        if (isset($types['null']) && count($types) > 1) {
            unset($types['null']);

            $nullable = true;
        }

        $falsable = false;

        if (isset($types['false']) && count($types) > 1) {
            unset($types['false']);

            $falsable = true;
        }

        $php_types = [];

        foreach ($types as $atomic_type) {
            $php_type = $atomic_type->toPhpString(
                $namespace,
                $aliased_classes,
                $this_class,
                $analysis_php_version_id,
            );

            if (!$php_type) {
                return null;
            }

            $php_types[] = $php_type;
        }

        if ($falsable) {
            if ($nullable) {
                $php_types['null'] = 'null';
            }
            $php_types['false'] = 'false';
            ksort($php_types);
            return implode('|', array_unique($php_types));
        }

        if ($analysis_php_version_id < 8_00_00) {
            return ($nullable ? '?' : '') . implode('|', array_unique($php_types));
        }
        if ($nullable) {
            $php_types['null'] = 'null';
        }
        return implode('|', array_unique($php_types));
    }

    /**
     * @psalm-mutation-free
     */
    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        if (!$this->isSingleAndMaybeNullable() && $analysis_php_version_id < 8_00_00) {
            return false;
        }

        $types = $this->types;

        if (isset($types['null'])) {
            if (count($types) > 1) {
                unset($types['null']);
            } else {
                return false;
            }
        }

        return !array_filter(
            $types,
            static fn($atomic_type): bool => !$atomic_type->canBeFullyExpressedInPhp($analysis_php_version_id),
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function hasType(string $type_string): bool
    {
        return isset($this->types[$type_string]);
    }

    /**
     * @psalm-mutation-free
     */
    public function hasArray(): bool
    {
        return isset($this->types['array']);
    }

    /**
     * @return TArray|TKeyedArray|TClassStringMap
     */
    public function getArray(): Atomic
    {
        if ($this->types['array'] instanceof TList) {
            return $this->types['array']->getKeyedArray();
        }
        return $this->types['array'];
    }

    /**
     * @psalm-mutation-free
     */
    public function hasIterable(): bool
    {
        return isset($this->types['iterable']);
    }

    /**
     * @psalm-mutation-free
     */
    public function hasList(): bool
    {
        return isset($this->types['array'])
            && $this->types['array'] instanceof TKeyedArray
            && $this->types['array']->is_list;
    }

    /**
     * @psalm-mutation-free
     */
    public function hasClassStringMap(): bool
    {
        return isset($this->types['array']) && $this->types['array'] instanceof TClassStringMap;
    }

    /**
     * @psalm-mutation-free
     */
    public function isTemplatedClassString(): bool
    {
        return $this->isSingle()
            && count(
                array_filter(
                    $this->types,
                    static fn($type): bool => $type instanceof TTemplateParamClass,
                ),
            ) === 1;
    }

    /**
     * @psalm-mutation-free
     */
    public function hasArrayAccessInterface(Codebase $codebase): bool
    {
        return (bool)array_filter(
            $this->types,
            static fn($type): bool => $type->hasArrayAccessInterface($codebase),
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function hasCallableType(): bool
    {
        return $this->getCallableTypes() || $this->getClosureTypes();
    }

    /**
     * @psalm-mutation-free
     * @return array<string, TCallable>
     */
    public function getCallableTypes(): array
    {
        return array_filter(
            $this->types,
            static fn($type): bool => $type instanceof TCallable,
        );
    }

    /**
     * @psalm-mutation-free
     * @return array<string, TClosure>
     */
    public function getClosureTypes(): array
    {
        return array_filter(
            $this->types,
            static fn($type): bool => $type instanceof TClosure,
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function hasObject(): bool
    {
        return isset($this->types['object']);
    }

    /**
     * @psalm-mutation-free
     */
    public function hasObjectType(): bool
    {
        foreach ($this->types as $type) {
            if ($type->isObjectType()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @psalm-mutation-free
     */
    public function canContainObjectType(Codebase $codebase): bool
    {
        $object_type_visitor = new CanContainObjectTypeVisitor($codebase);

        $object_type_visitor->traverseArray($this->types);

        return $object_type_visitor->matches();
    }

    /**
     * @psalm-mutation-free
     */
    public function isObjectType(): bool
    {
        foreach ($this->types as $type) {
            if (!$type->isObjectType()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @psalm-mutation-free
     */
    public function hasNamedObjectType(): bool
    {
        foreach ($this->types as $type) {
            if ($type->isNamedObjectType()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @psalm-mutation-free
     */
    public function isStaticObject(): bool
    {
        foreach ($this->types as $type) {
            if (!$type instanceof TNamedObject
                || !$type->is_static
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @psalm-mutation-free
     */
    public function hasStaticObject(): bool
    {
        foreach ($this->types as $type) {
            if ($type instanceof TNamedObject
                && $type->is_static
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @psalm-mutation-free
     */
    public function isNullable(): bool
    {
        if (isset($this->types['null'])) {
            return true;
        }

        foreach ($this->types as $type) {
            if ($type instanceof TTemplateParam && $type->as->isNullable()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @psalm-mutation-free
     */
    public function isFalsable(): bool
    {
        if (isset($this->types['false'])) {
            return true;
        }

        foreach ($this->types as $type) {
            if ($type instanceof TTemplateParam && $type->as->isFalsable()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @psalm-mutation-free
     */
    public function hasBool(): bool
    {
        return isset($this->types['bool']) || isset($this->types['false']) || isset($this->types['true']);
    }

    /**
     * @psalm-mutation-free
     */
    public function hasNull(): bool
    {
        return isset($this->types['null']);
    }

    /**
     * @psalm-mutation-free
     */
    public function hasString(): bool
    {
        return isset($this->types['string'])
            || isset($this->types['class-string'])
            || isset($this->types['trait-string'])
            || isset($this->types['numeric-string'])
            || isset($this->types['callable-string'])
            || isset($this->types['array-key'])
            || $this->literal_string_types
            || $this->typed_class_strings;
    }

    /**
     * @psalm-mutation-free
     */
    public function hasLowercaseString(): bool
    {
        return isset($this->types['string'])
            && ($this->types['string'] instanceof TLowercaseString
                || $this->types['string'] instanceof TNonEmptyLowercaseString);
    }

    /**
     * @psalm-mutation-free
     */
    public function hasLiteralClassString(): bool
    {
        return count($this->typed_class_strings) > 0;
    }

    /**
     * @psalm-mutation-free
     */
    public function hasInt(): bool
    {
        return isset($this->types['int']) || isset($this->types['array-key']) || $this->literal_int_types
            || array_filter($this->types, static fn(Atomic $type): bool => $type instanceof TIntRange);
    }

    /**
     * @psalm-mutation-free
     */
    public function hasArrayKey(): bool
    {
        return isset($this->types['array-key']);
    }

    /**
     * @psalm-mutation-free
     */
    public function hasFloat(): bool
    {
        return isset($this->types['float']) || $this->literal_float_types;
    }

    /**
     * @psalm-mutation-free
     */
    public function hasScalar(): bool
    {
        return isset($this->types['scalar']);
    }

    /**
     * @psalm-mutation-free
     */
    public function hasNumeric(): bool
    {
        return isset($this->types['numeric']);
    }

    /**
     * @psalm-mutation-free
     */
    public function hasScalarType(): bool
    {
        return isset($this->types['int'])
            || isset($this->types['float'])
            || isset($this->types['string'])
            || isset($this->types['class-string'])
            || isset($this->types['trait-string'])
            || isset($this->types['bool'])
            || isset($this->types['false'])
            || isset($this->types['true'])
            || isset($this->types['numeric'])
            || isset($this->types['numeric-string'])
            || $this->literal_int_types
            || $this->literal_float_types
            || $this->literal_string_types
            || $this->typed_class_strings;
    }

    /**
     * @psalm-mutation-free
     */
    public function hasTemplate(): bool
    {
        return (bool) array_filter(
            $this->types,
            static fn(Atomic $type): bool => $type instanceof TTemplateParam
                || ($type instanceof TNamedObject
                    && $type->extra_types
                    && array_filter(
                        $type->extra_types,
                        static fn($t): bool => $t instanceof TTemplateParam,
                    )
                ),
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function hasConditional(): bool
    {
        return (bool) array_filter(
            $this->types,
            static fn(Atomic $type): bool => $type instanceof TConditional,
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function hasTemplateOrStatic(): bool
    {
        return (bool) array_filter(
            $this->types,
            static fn(Atomic $type): bool => $type instanceof TTemplateParam
                || ($type instanceof TNamedObject
                    && ($type->is_static
                        || ($type->extra_types
                            && array_filter(
                                $type->extra_types,
                                static fn($t): bool => $t instanceof TTemplateParam,
                            )
                        )
                    )
                ),
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function hasMixed(): bool
    {
        return isset($this->types['mixed']);
    }

    /**
     * @psalm-mutation-free
     */
    public function isMixed(bool $check_templates = false): bool
    {
        return count(
            array_filter(
                $this->types,
                static fn($type, $key): bool => $key === 'mixed'
                    || $type instanceof TMixed
                    || ($check_templates
                        && $type instanceof TTemplateParam
                        && $type->as->isMixed()
                    ),
                ARRAY_FILTER_USE_BOTH,
            ),
        ) === count($this->types);
    }

    /**
     * @psalm-mutation-free
     */
    public function isEmptyMixed(): bool
    {
        return isset($this->types['mixed'])
            && $this->types['mixed'] instanceof TEmptyMixed
            && count($this->types) === 1;
    }

    /**
     * @psalm-mutation-free
     */
    public function isVanillaMixed(): bool
    {
        return isset($this->types['mixed'])
            && get_class($this->types['mixed']) === TMixed::class
            && !$this->types['mixed']->from_loop_isset
            && count($this->types) === 1;
    }

    /**
     * @psalm-mutation-free
     */
    public function isArrayKey(): bool
    {
        return isset($this->types['array-key']) && count($this->types) === 1;
    }

    /**
     * @psalm-mutation-free
     */
    public function isNull(): bool
    {
        return count($this->types) === 1 && isset($this->types['null']);
    }

    /**
     * @psalm-mutation-free
     */
    public function isFalse(): bool
    {
        return count($this->types) === 1 && isset($this->types['false']);
    }

    /**
     * @psalm-mutation-free
     */
    public function isAlwaysFalsy(): bool
    {
        foreach ($this->getAtomicTypes() as $atomic_type) {
            if (!$atomic_type->isFalsy()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @psalm-mutation-free
     */
    public function isTrue(): bool
    {
        return count($this->types) === 1 && isset($this->types['true']);
    }

    /**
     * @psalm-mutation-free
     */
    public function isAlwaysTruthy(): bool
    {
        if ($this->possibly_undefined || $this->possibly_undefined_from_try) {
            return false;
        }

        foreach ($this->getAtomicTypes() as $atomic_type) {
            if (!$atomic_type->isTruthy()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @psalm-mutation-free
     */
    public function isVoid(): bool
    {
        return isset($this->types['void']) && count($this->types) === 1;
    }

    /**
     * @psalm-mutation-free
     */
    public function isNever(): bool
    {
        return isset($this->types['never']) && count($this->types) === 1;
    }

    /**
     * @psalm-mutation-free
     */
    public function isGenerator(): bool
    {
        return count($this->types) === 1
            && (($single_type = reset($this->types)) instanceof TNamedObject)
            && ($single_type->value === 'Generator');
    }

    /**
     * @psalm-mutation-free
     */
    public function isSingle(): bool
    {
        $type_count = count($this->types);

        $int_literal_count = count($this->literal_int_types);
        $string_literal_count = count($this->literal_string_types);
        $float_literal_count = count($this->literal_float_types);

        if (($int_literal_count && $string_literal_count)
            || ($int_literal_count && $float_literal_count)
            || ($string_literal_count && $float_literal_count)
        ) {
            return false;
        }

        if ($int_literal_count || $string_literal_count || $float_literal_count) {
            $type_count -= $int_literal_count + $string_literal_count + $float_literal_count - 1;
        }

        return $type_count === 1;
    }

    /**
     * @psalm-mutation-free
     */
    public function isSingleAndMaybeNullable(): bool
    {
        $is_nullable = isset($this->types['null']);

        $type_count = count($this->types);

        if ($type_count === 1 && $is_nullable) {
            return false;
        }

        $int_literal_count = count($this->literal_int_types);
        $string_literal_count = count($this->literal_string_types);
        $float_literal_count = count($this->literal_float_types);

        if (($int_literal_count && $string_literal_count)
            || ($int_literal_count && $float_literal_count)
            || ($string_literal_count && $float_literal_count)
        ) {
            return false;
        }

        if ($int_literal_count || $string_literal_count || $float_literal_count) {
            $type_count -= $int_literal_count + $string_literal_count + $float_literal_count - 1;
        }

        return ($type_count - (int) $is_nullable) === 1;
    }

    /**
     * @psalm-mutation-free
     * @return bool true if this is an int
     */
    public function isInt(bool $check_templates = false): bool
    {
        return count(
            array_filter(
                $this->types,
                static fn($type): bool => $type instanceof TInt
                    || ($check_templates
                        && $type instanceof TTemplateParam
                        && $type->as->isInt()
                    ),
            ),
        ) === count($this->types);
    }

    /**
     * @psalm-mutation-free
     * @return bool true if this is a float
     */
    public function isFloat(): bool
    {
        if (!$this->isSingle()) {
            return false;
        }

        return isset($this->types['float']) || $this->literal_float_types;
    }

    /**
     * @psalm-mutation-free
     * @return bool true if this is a string
     */
    public function isString(bool $check_templates = false): bool
    {
        return count(
            array_filter(
                $this->types,
                static fn($type): bool => $type instanceof TString
                    || ($check_templates
                        && $type instanceof TTemplateParam
                        && $type->as->isString()
                    ),
            ),
        ) === count($this->types);
    }

    /**
     * @psalm-mutation-free
     * @return bool true if this is a string
     */
    public function isNonEmptyString(bool $check_templates = false): bool
    {
        return count(
            array_filter(
                $this->types,
                static fn($type): bool => $type instanceof TNonEmptyString
                    || ($type instanceof TLiteralString && $type->value !== '')
                    || ($check_templates
                        && $type instanceof TTemplateParam
                        && $type->as->isNonEmptyString()
                    ),
            ),
        ) === count($this->types);
    }

    /**
     * @psalm-mutation-free
     * @return bool true if this is a boolean
     */
    public function isBool(): bool
    {
        if (!$this->isSingle()) {
            return false;
        }

        return isset($this->types['bool']);
    }

    /**
     * @psalm-mutation-free
     * @return bool true if this is an array
     */
    public function isArray(): bool
    {
        if (!$this->isSingle()) {
            return false;
        }

        return isset($this->types['array']);
    }

    /**
     * @psalm-mutation-free
     * @return bool true if this is a string literal with only one possible value
     */
    public function isSingleStringLiteral(): bool
    {
        return count($this->types) === 1 && count($this->literal_string_types) === 1;
    }

    /**
     * @throws InvalidArgumentException if isSingleStringLiteral is false
     * @psalm-mutation-free
     * @return TLiteralString the only string literal represented by this union type
     */
    public function getSingleStringLiteral(): TLiteralString
    {
        if (count($this->types) !== 1 || count($this->literal_string_types) !== 1) {
            throw new InvalidArgumentException('Not a string literal');
        }

        return reset($this->literal_string_types);
    }

    /**
     * @psalm-mutation-free
     */
    public function allStringLiterals(): bool
    {
        foreach ($this->types as $atomic_key_type) {
            if (!$atomic_key_type instanceof TLiteralString) {
                return false;
            }
        }

        return true;
    }

    /**
     * @psalm-mutation-free
     */
    public function allIntLiterals(): bool
    {
        foreach ($this->types as $atomic_key_type) {
            if (!$atomic_key_type instanceof TLiteralInt) {
                return false;
            }
        }

        return true;
    }

    /**
     * @psalm-mutation-free
     */
    public function allFloatLiterals(): bool
    {
        foreach ($this->types as $atomic_key_type) {
            if (!$atomic_key_type instanceof TLiteralFloat) {
                return false;
            }
        }

        return true;
    }

    /**
     * @psalm-mutation-free
     * @psalm-assert-if-true array<
     *     array-key,
     *     TLiteralString|TLiteralInt|TLiteralFloat|TFalse|TTrue
     * > $this->getAtomicTypes()
     */
    public function allSpecificLiterals(): bool
    {
        foreach ($this->types as $atomic_key_type) {
            if (!$atomic_key_type instanceof TLiteralString
                && !$atomic_key_type instanceof TLiteralInt
                && !$atomic_key_type instanceof TLiteralFloat
                && !$atomic_key_type instanceof TFalse
                && !$atomic_key_type instanceof TTrue
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @psalm-mutation-free
     * @psalm-assert-if-true array<
     *     array-key,
     *     TLiteralString|TLiteralInt|TLiteralFloat|TNonspecificLiteralString|TNonSpecificLiteralInt|TFalse|TTrue
     * > $this->getAtomicTypes()
     */
    public function allLiterals(): bool
    {
        foreach ($this->types as $atomic_key_type) {
            if (!$atomic_key_type instanceof TLiteralString
                && !$atomic_key_type instanceof TLiteralInt
                && !$atomic_key_type instanceof TLiteralFloat
                && !$atomic_key_type instanceof TNonspecificLiteralString
                && !$atomic_key_type instanceof TNonspecificLiteralInt
                && !$atomic_key_type instanceof TFalse
                && !$atomic_key_type instanceof TTrue
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @psalm-mutation-free
     */
    public function hasLiteralValue(): bool
    {
        return $this->literal_int_types
            || $this->literal_string_types
            || $this->literal_float_types
            || isset($this->types['false'])
            || isset($this->types['true']);
    }

    /**
     * @psalm-mutation-free
     */
    public function isSingleLiteral(): bool
    {
        return count($this->types) === 1
            && count($this->literal_int_types)
                + count($this->literal_string_types)
                + count($this->literal_float_types) === 1
        ;
    }

    /**
     * @psalm-mutation-free
     * @return TLiteralInt|TLiteralString|TLiteralFloat
     */
    public function getSingleLiteral()
    {
        if (!$this->isSingleLiteral()) {
            throw new InvalidArgumentException("Not a single literal");
        }

        return ($literal = reset($this->literal_int_types)) !== false
            ? $literal
            : (($literal = reset($this->literal_string_types)) !== false
                ? $literal
                : reset($this->literal_float_types))
        ;
    }

    /**
     * @psalm-mutation-free
     */
    public function hasLiteralString(): bool
    {
        return count($this->literal_string_types) > 0;
    }

    /**
     * @psalm-mutation-free
     */
    public function hasLiteralInt(): bool
    {
        return count($this->literal_int_types) > 0;
    }

    /**
     * @psalm-mutation-free
     * @return bool true if this is a int literal with only one possible value
     */
    public function isSingleIntLiteral(): bool
    {
        return count($this->types) === 1 && count($this->literal_int_types) === 1;
    }

    /**
     * @throws InvalidArgumentException if isSingleIntLiteral is false
     * @psalm-mutation-free
     * @return TLiteralInt the only int literal represented by this union type
     */
    public function getSingleIntLiteral(): TLiteralInt
    {
        if (count($this->types) !== 1 || count($this->literal_int_types) !== 1) {
            throw new InvalidArgumentException('Not an int literal');
        }

        return reset($this->literal_int_types);
    }

    /**
     * @param  array<string>    $suppressed_issues
     * @param  array<string, bool> $phantom_classes
     */
    public function check(
        StatementsSource $source,
        CodeLocation $code_location,
        array $suppressed_issues,
        array $phantom_classes = [],
        bool $inferred = true,
        bool $inherited = false,
        bool $prevent_template_covariance = false,
        ?string $calling_method_id = null
    ): bool {
        if ($this->checked) {
            return true;
        }

        $checker = new TypeChecker(
            $source,
            $code_location,
            $suppressed_issues,
            $phantom_classes,
            $inferred,
            $inherited,
            $prevent_template_covariance,
            $calling_method_id,
        );

        $checker->traverseArray($this->types);

        /** @psalm-suppress InaccessibleProperty, ImpurePropertyAssignment Does not affect anything else */
        $this->checked = true;

        return !$checker->hasErrors();
    }

    /**
     * @param  array<string, mixed> $phantom_classes
     */
    public function queueClassLikesForScanning(
        Codebase $codebase,
        ?FileStorage $file_storage = null,
        array $phantom_classes = []
    ): void {
        $scanner_visitor = new TypeScanner(
            $codebase->scanner,
            $file_storage,
            $phantom_classes,
        );

        /** @psalm-suppress ImpureMethodCall */
        $scanner_visitor->traverseArray($this->types);
    }

    /**
     * @param  lowercase-string $fq_class_like_name
     * @psalm-mutation-free
     */
    public function containsClassLike(string $fq_class_like_name): bool
    {
        $classlike_visitor = new ContainsClassLikeVisitor($fq_class_like_name);

        /** @psalm-suppress ImpureMethodCall Actually mutation-free */
        $classlike_visitor->traverseArray($this->types);

        return $classlike_visitor->matches();
    }

    /**
     * @return static
     */
    public function replaceClassLike(string $old, string $new): self
    {
        $type = $this;
        (new ClasslikeReplacer(
            $old,
            $new,
        ))->traverse($type);
        return $type;
    }

    /** @psalm-mutation-free */
    public function containsAnyLiteral(): bool
    {
        $literal_visitor = new ContainsLiteralVisitor();

        /** @psalm-suppress ImpureMethodCall Actually mutation-free */
        $literal_visitor->traverseArray($this->types);

        return $literal_visitor->matches();
    }

    /**
     * @psalm-mutation-free
     * @return list<TTemplateParam>
     */
    public function getTemplateTypes(): array
    {
        $template_type_collector = new TemplateTypeCollector();

        /** @psalm-suppress ImpureMethodCall Actually mutation-free */
        $template_type_collector->traverseArray($this->types);

        return $template_type_collector->getTemplateTypes();
    }

    /**
     * @psalm-mutation-free
     */
    public function equals(
        self $other_type,
        bool $ensure_source_equality = true,
        bool $ensure_parent_node_equality = true,
        bool $ensure_possibly_undefined_equality = true
    ): bool {
        if ($other_type === $this) {
            return true;
        }

        if ($other_type->id && $this->id && $other_type->id !== $this->id) {
            return false;
        }

        if ($other_type->exact_id && $this->exact_id && $other_type->exact_id !== $this->exact_id) {
            return false;
        }

        if ($this->possibly_undefined !== $other_type->possibly_undefined && $ensure_possibly_undefined_equality) {
            return false;
        }

        if ($this->had_template !== $other_type->had_template) {
            return false;
        }

        if ($this->possibly_undefined_from_try !== $other_type->possibly_undefined_from_try) {
            return false;
        }

        if ($this->from_calculation !== $other_type->from_calculation) {
            return false;
        }

        if ($this->initialized !== $other_type->initialized) {
            return false;
        }

        if ($ensure_source_equality && $this->from_docblock !== $other_type->from_docblock) {
            return false;
        }

        if (count($this->types) !== count($other_type->types)) {
            return false;
        }

        if ($ensure_parent_node_equality && $this->parent_nodes !== $other_type->parent_nodes) {
            return false;
        }

        if ($this->different || $other_type->different) {
            return false;
        }

        $other_atomic_types = $other_type->types;

        foreach ($this->types as $key => $atomic_type) {
            if (!isset($other_atomic_types[$key])) {
                return false;
            }

            if (!$atomic_type->equals($other_atomic_types[$key], $ensure_source_equality)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @psalm-mutation-free
     * @return array<string, TLiteralString>
     */
    public function getLiteralStrings(): array
    {
        return $this->literal_string_types;
    }

    /**
     * @psalm-mutation-free
     * @return array<string, TLiteralInt>
     */
    public function getLiteralInts(): array
    {
        return $this->literal_int_types;
    }

    /**
     * @psalm-mutation-free
     * @return array<string, TIntRange>
     */
    public function getRangeInts(): array
    {
        $ranges = [];
        foreach ($this->getAtomicTypes() as $atomic) {
            if ($atomic instanceof TIntRange) {
                $ranges[$atomic->getKey()] = $atomic;
            }
        }

        return $ranges;
    }

    /**
     * @psalm-mutation-free
     * @return array<string, TLiteralFloat>
     */
    public function getLiteralFloats(): array
    {
        return $this->literal_float_types;
    }

    /**
     * @psalm-mutation-free
     * @return bool true if this is a float literal with only one possible value
     */
    public function isSingleFloatLiteral(): bool
    {
        return count($this->types) === 1 && count($this->literal_float_types) === 1;
    }

    /**
     * @psalm-mutation-free
     * @throws InvalidArgumentException if isSingleFloatLiteral is false
     * @return TLiteralFloat the only float literal represented by this union type
     */
    public function getSingleFloatLiteral(): TLiteralFloat
    {
        if (count($this->types) !== 1 || count($this->literal_float_types) !== 1) {
            throw new InvalidArgumentException('Not a float literal');
        }

        return reset($this->literal_float_types);
    }

    /**
     * @psalm-mutation-free
     */
    public function hasLiteralFloat(): bool
    {
        return count($this->literal_float_types) > 0;
    }

    /**
     * @psalm-mutation-free
     */
    public function getSingleAtomic(): Atomic
    {
        return reset($this->types);
    }

    /**
     * @psalm-mutation-free
     */
    public function isEmptyArray(): bool
    {
        return count($this->types) === 1
            && isset($this->types['array'])
            && $this->types['array'] instanceof TArray
            && $this->types['array']->isEmptyArray();
    }

    /**
     * @psalm-mutation-free
     */
    public function isUnionEmpty(): bool
    {
        return $this->types === [];
    }

    public function visit(TypeVisitor $visitor): bool
    {
        foreach ($this->types as $type) {
            if ($visitor->traverse($type) === false) {
                return false;
            }
        }

        return true;
    }
}
