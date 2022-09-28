<?php

namespace Psalm\Type;

use InvalidArgumentException;
use Psalm\Codebase;
use Psalm\Exception\TypeParseTreeException;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TemplateStandinTypeReplacer;
use Psalm\Internal\Type\TypeAlias;
use Psalm\Internal\Type\TypeAlias\LinkableTypeAlias;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TArrayKey;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TCallableArray;
use Psalm\Type\Atomic\TCallableKeyedArray;
use Psalm\Type\Atomic\TCallableList;
use Psalm\Type\Atomic\TCallableObject;
use Psalm\Type\Atomic\TCallableString;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TClassStringMap;
use Psalm\Type\Atomic\TClosedResource;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Atomic\TDependentGetClass;
use Psalm\Type\Atomic\TEmptyMixed;
use Psalm\Type\Atomic\TEmptyNumeric;
use Psalm\Type\Atomic\TEmptyScalar;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIntRange;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TLowercaseString;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNever;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TNonEmptyList;
use Psalm\Type\Atomic\TNonEmptyLowercaseString;
use Psalm\Type\Atomic\TNonEmptyMixed;
use Psalm\Type\Atomic\TNonEmptyNonspecificLiteralString;
use Psalm\Type\Atomic\TNonEmptyScalar;
use Psalm\Type\Atomic\TNonEmptyString;
use Psalm\Type\Atomic\TNonFalsyString;
use Psalm\Type\Atomic\TNonspecificLiteralInt;
use Psalm\Type\Atomic\TNonspecificLiteralString;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Atomic\TNumericString;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TObjectWithProperties;
use Psalm\Type\Atomic\TResource;
use Psalm\Type\Atomic\TScalar;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTraitString;
use Psalm\Type\Atomic\TTrue;
use Psalm\Type\Atomic\TTypeAlias;
use Psalm\Type\Atomic\TVoid;

use function array_filter;
use function array_keys;
use function get_class;
use function is_numeric;
use function strpos;
use function strtolower;

/**
 * @psalm-immutable
 */
abstract class Atomic implements TypeNode
{
    public function __construct(bool $from_docblock = false)
    {
        $this->from_docblock = $from_docblock;
    }
    /**
     * Whether or not the type has been checked yet
     *
     * @var bool
     */
    public $checked = false;

    /**
     * Whether or not the type comes from a docblock
     *
     * @var bool
     */
    public $from_docblock = false;

    /**
     * @var ?int
     */
    public $offset_start;

    /**
     * @var ?int
     */
    public $offset_end;

    /**
     * @var ?string
     */
    public $text;

    /**
     * @return static
     */
    public function setFromDocblock(bool $from_docblock): self
    {
        if ($from_docblock === $this->from_docblock) {
            return $this;
        }
        $cloned = clone $this;
        $cloned->from_docblock = $from_docblock;
        return $cloned;
    }

    /**
     * @param int $analysis_php_version_id contains php version when the type comes from signature
     * @param array<string, array<string, Union>> $template_type_map
     * @param array<string, TypeAlias> $type_aliases
     */
    public static function create(
        string $value,
        ?int   $analysis_php_version_id = null,
        array  $template_type_map = [],
        array  $type_aliases = [],
        ?int   $offset_start = null,
        ?int   $offset_end = null,
        ?string $text = null,
        bool    $from_docblock = false
    ): Atomic {
        $result = self::createInner($value, $analysis_php_version_id, $template_type_map, $type_aliases);
        $result->offset_start = $offset_start;
        $result->offset_end = $offset_end;
        $result->text = $text;
        $result->from_docblock = $from_docblock;
        return $result;
    }
    /**
     * @param int $analysis_php_version_id contains php version when the type comes from signature
     * @param array<string, array<string, Union>> $template_type_map
     * @param array<string, TypeAlias> $type_aliases
     */
    private static function createInner(
        string $value,
        ?int   $analysis_php_version_id = null,
        array  $template_type_map = [],
        array  $type_aliases = [],
        bool   $from_docblock = false
    ): Atomic {
        switch ($value) {
            case 'int':
                return new TInt();

            case 'float':
                return new TFloat();

            case 'string':
                return new TString();

            case 'bool':
                return new TBool();

            case 'void':
                if ($analysis_php_version_id === null || $analysis_php_version_id >= 7_01_00) {
                    return new TVoid();
                }

                break;

            case 'array-key':
                return new TArrayKey();

            case 'iterable':
                if ($analysis_php_version_id === null || $analysis_php_version_id >= 7_01_00) {
                    return new TIterable();
                }

                break;

            case 'never':
                if ($analysis_php_version_id === null || $analysis_php_version_id >= 8_01_00) {
                    return new TNever();
                }

                break;

            case 'never-return':
            case 'never-returns':
            case 'no-return':
            case 'empty':
                return new TNever();

            case 'object':
                if ($analysis_php_version_id === null || $analysis_php_version_id >= 7_02_00) {
                    return new TObject();
                }

                break;

            case 'callable':
                return new TCallable();
            case 'pure-callable':
                $type = new TCallable();
                $type->is_pure = true;

                return $type;

            case 'array':
            case 'associative-array':
                return new TArray([new Union([new TArrayKey($from_docblock)]), new Union([new TMixed(false, $from_docblock)])]);

            case 'non-empty-array':
                return new TNonEmptyArray([new Union([new TArrayKey($from_docblock)]), new Union([new TMixed(false, $from_docblock)])]);

            case 'callable-array':
                return new TCallableArray([new Union([new TArrayKey($from_docblock)]), new Union([new TMixed(false, $from_docblock)])]);

            case 'list':
                return new TList(Type::getMixed(false, $from_docblock));

            case 'non-empty-list':
                return new TNonEmptyList(Type::getMixed(false, $from_docblock));

            case 'non-empty-string':
                return new TNonEmptyString();

            case 'non-falsy-string':
                return new TNonFalsyString();

            case 'lowercase-string':
                return new TLowercaseString();

            case 'non-empty-lowercase-string':
                return new TNonEmptyLowercaseString();

            case 'resource':
                return $analysis_php_version_id !== null ? new TNamedObject($value) : new TResource();

            case 'resource (closed)':
            case 'closed-resource':
                return new TClosedResource();

            case 'positive-int':
                return new TIntRange(1, null);

            case 'numeric':
                return $analysis_php_version_id !== null ? new TNamedObject($value) : new TNumeric();

            case 'true':
                return $analysis_php_version_id !== null ? new TNamedObject($value) : new TTrue();

            case 'false':
                if ($analysis_php_version_id === null || $analysis_php_version_id >= 8_00_00) {
                    return new TFalse();
                }

                return new TNamedObject($value);

            case 'scalar':
                return $analysis_php_version_id !== null ? new TNamedObject($value) : new TScalar();

            case 'null':
                if ($analysis_php_version_id === null || $analysis_php_version_id >= 8_00_00) {
                    return new TNull();
                }

                return new TNamedObject($value);

            case 'mixed':
                if ($analysis_php_version_id === null || $analysis_php_version_id >= 8_00_00) {
                    return new TMixed();
                }

                return new TNamedObject($value);

            case 'callable-object':
                return new TCallableObject();

            case 'stringable-object':
                return new TObjectWithProperties([], ['__tostring' => 'string']);

            case 'class-string':
                return new TClassString();

            case 'interface-string':
                $type = new TClassString();
                $type->is_interface = true;
                return $type;

            case 'enum-string':
                $type = new TClassString();
                $type->is_enum = true;
                return $type;

            case 'trait-string':
                return new TTraitString();

            case 'callable-string':
                return new TCallableString();

            case 'numeric-string':
                return new TNumericString();

            case 'literal-string':
                return new TNonspecificLiteralString();

            case 'non-empty-literal-string':
                return new TNonEmptyNonspecificLiteralString();

            case 'literal-int':
                return new TNonspecificLiteralInt();

            case '$this':
                return new TNamedObject('static');

            case 'non-empty-scalar':
                return new TNonEmptyScalar;

            case 'empty-scalar':
                return new TEmptyScalar;

            case 'non-empty-mixed':
                return new TNonEmptyMixed();
        }

        if (strpos($value, '-') && strpos($value, 'OCI-') !== 0) {
            throw new TypeParseTreeException('Unrecognized type ' . $value);
        }

        if (is_numeric($value[0])) {
            throw new TypeParseTreeException('First character of type cannot be numeric');
        }

        if (isset($template_type_map[$value])) {
            $first_class = array_keys($template_type_map[$value])[0];

            return new TTemplateParam(
                $value,
                $template_type_map[$value][$first_class],
                $first_class
            );
        }

        if (isset($type_aliases[$value])) {
            $type_alias = $type_aliases[$value];

            if ($type_alias instanceof LinkableTypeAlias) {
                return new TTypeAlias($type_alias->declaring_fq_classlike_name, $type_alias->alias_name);
            }

            throw new TypeParseTreeException('Invalid type alias ' . $value . ' provided');
        }

        return new TNamedObject($value);
    }

    /**
     * This is the string that will be used to represent the type in Union::$types. This means that two types sharing
     * the same getKey value will override themselves in an Union
     */
    abstract public function getKey(bool $include_extra = true): string;

    public function isNumericType(): bool
    {
        return $this instanceof TInt
            || $this instanceof TFloat
            || $this instanceof TNumericString
            || $this instanceof TNumeric
            || ($this instanceof TLiteralString && is_numeric($this->value));
    }

    public function isObjectType(): bool
    {
        return $this instanceof TObject
            || $this instanceof TNamedObject
            || ($this instanceof TTemplateParam
                && $this->as->hasObjectType());
    }

    public function isNamedObjectType(): bool
    {
        return $this instanceof TNamedObject
            || ($this instanceof TTemplateParam
                && ($this->as->hasNamedObjectType()
                    || array_filter(
                        $this->extra_types,
                        static fn($extra_type): bool => $extra_type->isNamedObjectType()
                    )
                )
            );
    }

    public function isCallableType(): bool
    {
        return $this instanceof TCallable
            || $this instanceof TCallableObject
            || $this instanceof TCallableString
            || $this instanceof TCallableArray
            || $this instanceof TCallableList
            || $this instanceof TCallableKeyedArray
            || $this instanceof TClosure;
    }

    public function isIterable(Codebase $codebase): bool
    {
        return $this instanceof TIterable
            || $this->hasTraversableInterface($codebase)
            || $this instanceof TArray
            || $this instanceof TKeyedArray
            || $this instanceof TList;
    }

    /**
     * @throws InvalidArgumentException if $this is not an iterable type.
     */
    public function getIterable(Codebase $codebase): TIterable
    {
        if ($this instanceof TIterable) {
            return $this;
        }
        if ($this instanceof TArray) {
            return new TIterable($this->type_params);
        }
        if ($this instanceof TList) {
            return new TIterable([new Union([new TIntRange(0, null)]), $this->type_param]);
        }
        if ($this instanceof TKeyedArray) {
            return new TIterable([$this->getGenericKeyType(), $this->getGenericValueType()]);
        }
        if ($this->hasTraversableInterface($codebase)) {
            if (strtolower($this->value) === "traversable") {
                if ($this instanceof TGenericObject) {
                    return new TIterable($this->type_params);
                }
                return new TIterable([Type::getMixed(), Type::getMixed()]);
            }

            $implemented_traversable_templates = TemplateStandinTypeReplacer::getMappedGenericTypeParams(
                $codebase,
                $this,
                new TGenericObject("Traversable", [Type::getMixed(), Type::getMixed()]),
            );
            return new TIterable($implemented_traversable_templates);
        }
        throw new InvalidArgumentException("{$this->getId()} is not an iterable");
    }

    public function isCountable(Codebase $codebase): bool
    {
        return $this->hasCountableInterface($codebase)
            || $this instanceof TArray
            || $this instanceof TKeyedArray
            || $this instanceof TList;
    }

    /**
     * @psalm-assert-if-true TNamedObject $this
     */
    public function hasTraversableInterface(Codebase $codebase): bool
    {
        return $this instanceof TNamedObject
            && (
                strtolower($this->value) === 'traversable'
                || ($codebase->classOrInterfaceExists($this->value)
                    && ($codebase->classExtendsOrImplements(
                        $this->value,
                        'Traversable'
                    ) || $codebase->interfaceExtends(
                        $this->value,
                        'Traversable'
                    )))
                || (
                    $this->extra_types
                    && array_filter(
                        $this->extra_types,
                        static fn(Atomic $a): bool => $a->hasTraversableInterface($codebase)
                    )
                )
            );
    }

    public function hasCountableInterface(Codebase $codebase): bool
    {
        return $this instanceof TNamedObject
            && (
                strtolower($this->value) === 'countable'
                || ($codebase->classOrInterfaceExists($this->value)
                    && ($codebase->classExtendsOrImplements(
                        $this->value,
                        'Countable'
                    ) || $codebase->interfaceExtends(
                        $this->value,
                        'Countable'
                    )))
                || (
                    $this->extra_types
                    && array_filter(
                        $this->extra_types,
                        static fn(Atomic $a): bool => $a->hasCountableInterface($codebase)
                    )
                )
            );
    }

    public function isArrayAccessibleWithStringKey(Codebase $codebase): bool
    {
        return $this instanceof TArray
            || $this instanceof TKeyedArray
            || $this instanceof TList
            || $this instanceof TClassStringMap
            || $this->hasArrayAccessInterface($codebase)
            || ($this instanceof TNamedObject && $this->value === 'SimpleXMLElement');
    }

    public function isArrayAccessibleWithIntOrStringKey(Codebase $codebase): bool
    {
        return $this instanceof TString
            || $this->isArrayAccessibleWithStringKey($codebase);
    }

    public function hasArrayAccessInterface(Codebase $codebase): bool
    {
        return $this instanceof TNamedObject
            && (
                strtolower($this->value) === 'arrayaccess'
                || ($codebase->classOrInterfaceExists($this->value)
                    && ($codebase->classExtendsOrImplements(
                        $this->value,
                        'ArrayAccess'
                    ) || $codebase->interfaceExtends(
                        $this->value,
                        'ArrayAccess'
                    )))
                || (
                    $this->extra_types
                    && array_filter(
                        $this->extra_types,
                        static fn(Atomic $a): bool => $a->hasArrayAccessInterface($codebase)
                    )
                )
            );
    }

    public function getChildNodeKeys(): array
    {
        return [];
    }

    final public function __toString(): string
    {
        return $this->getId();
    }

    public function __clone()
    {
        if ($this instanceof TNamedObject
            || $this instanceof TTemplateParam
            || $this instanceof TIterable
            || $this instanceof TObjectWithProperties
        ) {
            if ($this->extra_types) {
                foreach ($this->extra_types as &$type) {
                    $type = clone $type;
                }
            }
        }

        if ($this instanceof TTemplateParam) {
            $this->as = clone $this->as;
        }
    }

    /**
     * This is the true identifier for the type. It defaults to self::getKey() but can be overrided to be more precise
     */
    public function getId(bool $exact = true, bool $nested = false): string
    {
        return $this->getKey();
    }
    /**
     * This string is used in order to transform a type into an string assertion for the assertion module
     * Default to self::getId()
     */
    public function getAssertionString(): string
    {
        return $this->getId();
    }

    /**
     * Returns the detailed description of the type, either in phpdoc standard format or Psalm format depending on flag
     * Default to self::getKey()
     *
     * @param array<lowercase-string, string> $aliased_classes
     */
    public function toNamespacedString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        bool $use_phpdoc_format
    ): string {
        return $this->getKey();
    }

    /**
     * Returns a string representation of the type compatible with php signature or null if the type can't be expressed
     *  with the given php version
     *
     * @param  array<lowercase-string, string> $aliased_classes
     */
    abstract public function toPhpString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        int $analysis_php_version_id
    ): ?string;

    abstract public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool;

    /**
     * @return static
     */
    public function replaceTemplateTypesWithStandins(
        TemplateResult $template_result,
        Codebase $codebase,
        ?StatementsAnalyzer $statements_analyzer = null,
        Atomic $input_type = null,
        ?int $input_arg_offset = null,
        ?string $calling_class = null,
        ?string $calling_function = null,
        bool $replace = true,
        bool $add_lower_bound = false,
        int $depth = 0
    ): self {
        // do nothing
        return $this;
    }

    /**
     * @return static
     */
    public function replaceTemplateTypesWithArgTypes(
        TemplateResult $template_result,
        ?Codebase $codebase
    ): self {
        // do nothing
        return $this;
    }

    public function equals(Atomic $other_type, bool $ensure_source_equality): bool
    {
        return get_class($other_type) === get_class($this);
    }

    public function isTruthy(): bool
    {
        if ($this instanceof TTrue) {
            return true;
        }

        if ($this instanceof TLiteralInt && $this->value !== 0) {
            return true;
        }

        if ($this instanceof TLiteralFloat && $this->value !== 0.0) {
            return true;
        }

        if ($this instanceof TLiteralString &&
            ($this->value !== '' && $this->value !== '0')
        ) {
            return true;
        }

        if ($this instanceof TNonFalsyString) {
            return true;
        }

        if ($this instanceof TNonEmptyArray) {
            return true;
        }

        if ($this instanceof TNonEmptyScalar) {
            return true;
        }

        if ($this instanceof TNonEmptyList) {
            return true;
        }

        if ($this instanceof TNonEmptyMixed) {
            return true;
        }

        if ($this instanceof TObject) {
            return true;
        }

        if ($this instanceof TNamedObject
            && $this->value !== 'SimpleXMLElement'
            && $this->value !== 'SimpleXMLIterator') {
            return true;
        }

        if ($this instanceof TIntRange && !$this->contains(0)) {
            return true;
        }

        if ($this instanceof TLiteralClassString) {
            return true;
        }

        if ($this instanceof TClassString) {
            return true;
        }

        if ($this instanceof TDependentGetClass) {
            return true;
        }

        if ($this instanceof TTraitString) {
            return true;
        }

        if ($this instanceof TResource) {
            return true;
        }

        if ($this instanceof TKeyedArray) {
            foreach ($this->properties as $property) {
                if ($property->possibly_undefined === false) {
                    return true;
                }
            }
        }

        if ($this instanceof TTemplateParam && $this->as->isAlwaysTruthy()) {
            return true;
        }

        //we can't be sure the type is always truthy
        return false;
    }

    public function isFalsy(): bool
    {
        if ($this instanceof TFalse) {
            return true;
        }

        if ($this instanceof TLiteralInt && $this->value === 0) {
            return true;
        }

        if ($this instanceof TLiteralFloat && $this->value === 0.0) {
            return true;
        }

        if ($this instanceof TLiteralString &&
            ($this->value === '' || $this->value === '0')
        ) {
            return true;
        }

        if ($this instanceof TNull) {
            return true;
        }

        if ($this instanceof TEmptyMixed) {
            return true;
        }

        if ($this instanceof TEmptyNumeric) {
            return true;
        }

        if ($this instanceof TEmptyScalar) {
            return true;
        }

        if ($this instanceof TTemplateParam && $this->as->isAlwaysFalsy()) {
            return true;
        }

        if ($this instanceof TIntRange &&
            $this->min_bound === 0 &&
            $this->max_bound === 0
        ) {
            return true;
        }

        if ($this instanceof TArray && $this->isEmptyArray()) {
            return true;
        }

        //we can't be sure the type is always falsy
        return false;
    }
}
