<?php
namespace Psalm\Type;

use InvalidArgumentException;
use Psalm\Codebase;
use Psalm\Exception\TypeParseTreeException;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\Comparator\TypeComparisonResult2;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TypeAlias;
use Psalm\Type;
use Psalm\Type\Atomic\Scalar;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TArrayKey;
use Psalm\Type\Atomic\TAssertionFalsy;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TCallableArray;
use Psalm\Type\Atomic\TCallableKeyedArray;
use Psalm\Type\Atomic\TCallableList;
use Psalm\Type\Atomic\TCallableObject;
use Psalm\Type\Atomic\TCallableString;
use Psalm\Type\Atomic\TClassConstant;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TEmpty;
use Psalm\Type\Atomic\TEmptyScalar;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\THtmlEscapedString;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNever;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TNonEmptyList;
use Psalm\Type\Atomic\TNonEmptyMixed;
use Psalm\Type\Atomic\TNonEmptyScalar;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Atomic\TNumericString;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TPositiveInt;
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

abstract class Atomic extends TypeNode
{
    /**
     * Set of types that always contain this type.
     *
     * Most types should have `CONTAINED_BY = parent::CONTAINED_BY + [self::class => true]`, to indicate that one
     * instance of that type is always contained by any other instance of that type. Exceptions to this are cases like
     * TLiteralInt, where int(1) is not contained by int(2), but both are TLiteralInt.
     *
     * @var array<class-string<Atomic>, true>
     */
    protected const CONTAINED_BY = [
        TMixed::class => true,
    ];

    /**
     * Indicates the scalar types that this type is able to be coerced to.
     *
     * @var array<class-string<Scalar>, true>
     */
    protected const COERCIBLE_TO = [
        TBool::class => true, // Everything can be coerced to bool
    ];

    /**
     * Set of types that this type intersects with, but that aren't already contained by or contain this type.
     *
     * Containing types always intersect with contained types and vice versa, but siblings or other types can be added
     * here as well. For instance TNonFalsyString and TNumericString are overlapping types, but neither contains the
     * other.
     * Only one side needs to be set for both types to be considered intersecting with each other.
     *
     * @var array<class-string<Atomic>, true>
     */
    protected const INTERSECTS = [];

    public const KEY = 'atomic';

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
     * @param array{int,int}|null $php_version contains php version when the type comes from signature
     * @param array<string, array<string, Union>> $template_type_map
     * @param array<string, TypeAlias> $type_aliases
     */
    public static function create(
        string $value,
        ?array $php_version = null,
        array $template_type_map = [],
        array $type_aliases = []
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
                if ($php_version === null
                    || ($php_version[0] > 7)
                    || ($php_version[0] === 7 && $php_version[1] >= 1)
                ) {
                    return new TVoid();
                }

                break;

            case 'array-key':
                return new TArrayKey();

            case 'iterable':
                if ($php_version === null
                    || ($php_version[0] > 7)
                    || ($php_version[0] === 7 && $php_version[1] >= 1)
                ) {
                    return new TIterable();
                }

                break;

            case 'never':
                if ($php_version === null
                    || ($php_version[0] > 8)
                    || ($php_version[0] === 8 && $php_version[1] >= 1)
                ) {
                    return new TNever();
                }

                break;

            case 'never-return':
            case 'never-returns':
            case 'no-return':
                return new TNever();

            case 'object':
                if ($php_version === null
                    || ($php_version[0] > 7)
                    || ($php_version[0] === 7 && $php_version[1] >= 2)
                ) {
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
                return new TArray([new Union([new TArrayKey]), new Union([new TMixed])]);

            case 'non-empty-array':
                return new TNonEmptyArray([new Union([new TArrayKey]), new Union([new TMixed])]);

            case 'callable-array':
                return new Type\Atomic\TCallableArray([new Union([new TArrayKey]), new Union([new TMixed])]);

            case 'list':
                return new TList(Type::getMixed());

            case 'non-empty-list':
                return new TNonEmptyList(Type::getMixed());

            case 'non-empty-string':
                return new Type\Atomic\TNonEmptyString();

            case 'non-falsy-string':
                return new Type\Atomic\TNonFalsyString();

            case 'lowercase-string':
                return new Type\Atomic\TLowercaseString();

            case 'non-empty-lowercase-string':
                return new Type\Atomic\TNonEmptyLowercaseString();

            case 'resource':
                return $php_version !== null ? new TNamedObject($value) : new TResource();

            case 'resource (closed)':
            case 'closed-resource':
                return new Type\Atomic\TClosedResource();

            case 'positive-int':
                return new TPositiveInt();

            case 'numeric':
                return $php_version !== null ? new TNamedObject($value) : new TNumeric();

            case 'true':
                return $php_version !== null ? new TNamedObject($value) : new TTrue();

            case 'false':
                if ($php_version === null || $php_version[0] >= 8) {
                    return new TFalse();
                }

                return new TNamedObject($value);

            case 'empty':
                return $php_version !== null ? new TNamedObject($value) : new TEmpty();

            case 'scalar':
                return $php_version !== null ? new TNamedObject($value) : new TScalar();

            case 'null':
                if ($php_version === null || $php_version[0] >= 8) {
                    return new TNull();
                }

                return new TNamedObject($value);

            case 'mixed':
                if ($php_version === null || $php_version[0] >= 8) {
                    return new TMixed();
                }

                return new TNamedObject($value);

            case 'callable-object':
                return new TCallableObject();

            case 'stringable-object':
                return new Type\Atomic\TObjectWithProperties([], ['__tostring' => 'string']);

            case 'class-string':
            case 'interface-string':
                return new TClassString();

            case 'trait-string':
                return new TTraitString();

            case 'callable-string':
                return new TCallableString();

            case 'numeric-string':
                return new TNumericString();

            case 'html-escaped-string':
                return new THtmlEscapedString();

            case 'literal-string':
                return new Type\Atomic\TNonspecificLiteralString();

            case 'non-empty-literal-string':
                return new Type\Atomic\TNonEmptyNonspecificLiteralString();

            case 'literal-int':
                return new Type\Atomic\TNonspecificLiteralInt();

            case 'false-y':
                return new TAssertionFalsy();

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

            if ($type_alias instanceof TypeAlias\LinkableTypeAlias) {
                return new TTypeAlias($type_alias->declaring_fq_classlike_name, $type_alias->alias_name);
            }

            throw new TypeParseTreeException('Invalid type alias ' . $value . ' provided');
        }

        return new TNamedObject($value);
    }

    abstract public function getKey(bool $include_extra = true): string;

    public function isNumericType(): bool
    {
        return $this instanceof TInt
            || $this instanceof TFloat
            || $this instanceof TNumericString
            || $this instanceof TNumeric
            || ($this instanceof TLiteralString && is_numeric($this->value));
    }

    public function hasObjectType(): bool
    {
        return $this->isObjectType();
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
                        $this->extra_types ?: [],
                        function ($extra_type) {
                            return $extra_type->isNamedObjectType();
                        }
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
            || $this instanceof TCallableKeyedArray;
    }

    public function isIterable(Codebase $codebase): bool
    {
        return $this instanceof TIterable
            || $this->hasTraversableInterface($codebase)
            || $this instanceof TArray
            || $this instanceof TKeyedArray
            || $this instanceof TList;
    }

    public function isCountable(Codebase $codebase): bool
    {
        return $this->hasCountableInterface($codebase)
            || $this instanceof TArray
            || $this instanceof TKeyedArray
            || $this instanceof TList;
    }

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
                        function (Atomic $a) use ($codebase): bool {
                            return $a->hasTraversableInterface($codebase);
                        }
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
                        function (Atomic $a) use ($codebase): bool {
                            return $a->hasCountableInterface($codebase);
                        }
                    )
                )
            );
    }

    public function isArrayAccessibleWithStringKey(Codebase $codebase): bool
    {
        return $this instanceof TArray
            || $this instanceof TKeyedArray
            || $this instanceof TList
            || $this instanceof Atomic\TClassStringMap
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
                        function (Atomic $a) use ($codebase): bool {
                            return $a->hasArrayAccessInterface($codebase);
                        }
                    )
                )
            );
    }

    public function getChildNodes(): array
    {
        return [];
    }

    public function replaceClassLike(string $old, string $new): void
    {
        if ($this instanceof TNamedObject) {
            if (strtolower($this->value) === $old) {
                $this->value = $new;
            }
        }

        if ($this instanceof TNamedObject
            || $this instanceof TIterable
            || $this instanceof TTemplateParam
        ) {
            if ($this->extra_types) {
                foreach ($this->extra_types as $extra_type) {
                    $extra_type->replaceClassLike($old, $new);
                }
            }
        }

        if ($this instanceof TClassConstant) {
            if (strtolower($this->fq_classlike_name) === $old) {
                $this->fq_classlike_name = $new;
            }
        }

        if ($this instanceof TClassString && $this->as !== 'object') {
            if (strtolower($this->as) === $old) {
                $this->as = $new;
            }
        }

        if ($this instanceof TTemplateParam) {
            $this->as->replaceClassLike($old, $new);
        }

        if ($this instanceof TLiteralClassString) {
            if (strtolower($this->value) === $old) {
                $this->value = $new;
            }
        }

        if ($this instanceof Type\Atomic\TArray
            || $this instanceof Type\Atomic\TGenericObject
            || $this instanceof Type\Atomic\TIterable
        ) {
            foreach ($this->type_params as $type_param) {
                $type_param->replaceClassLike($old, $new);
            }
        }

        if ($this instanceof Type\Atomic\TKeyedArray) {
            foreach ($this->properties as $property_type) {
                $property_type->replaceClassLike($old, $new);
            }
        }

        if ($this instanceof Type\Atomic\TClosure
            || $this instanceof Type\Atomic\TCallable
        ) {
            if ($this->params) {
                foreach ($this->params as $param) {
                    if ($param->type) {
                        $param->type->replaceClassLike($old, $new);
                    }
                }
            }

            if ($this->return_type) {
                $this->return_type->replaceClassLike($old, $new);
            }
        }
    }

    public function __toString(): string
    {
        return '';
    }

    public function __clone()
    {
        if ($this instanceof TNamedObject
            || $this instanceof TTemplateParam
            || $this instanceof TIterable
            || $this instanceof Type\Atomic\TObjectWithProperties
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

    public function getId(bool $nested = false): string
    {
        return $this->__toString();
    }

    public function getAssertionString(bool $exact = false): string
    {
        return $this->getId();
    }

    /**
     * @param  array<lowercase-string, string> $aliased_classes
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
     * @param  array<lowercase-string, string> $aliased_classes
     */
    abstract public function toPhpString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        int $php_major_version,
        int $php_minor_version
    ): ?string;

    abstract public function canBeFullyExpressedInPhp(int $php_major_version, int $php_minor_version): bool;

    public function replaceTemplateTypesWithStandins(
        TemplateResult $template_result,
        ?Codebase $codebase = null,
        ?StatementsAnalyzer $statements_analyzer = null,
        Type\Atomic $input_type = null,
        ?int $input_arg_offset = null,
        ?string $calling_class = null,
        ?string $calling_function = null,
        bool $replace = true,
        bool $add_lower_bound = false,
        int $depth = 0
    ): self {
        return $this;
    }

    public function replaceTemplateTypesWithArgTypes(
        TemplateResult $template_result,
        ?Codebase $codebase
    ): void {
        // do nothing
    }

    public function equals(TypeNode $other_type, bool $ensure_source_equality): bool
    {
        return get_class($other_type) === get_class($this);
    }

    /**
     * Should only be called where $this->negated === $other->negated === false.
     *
     * @psalm-mutation-free TODO enable on children
     */
    protected function containedByAtomic(
        Atomic $other,
        ?Codebase $codebase
        // bool $allow_interface_equality = false,
    ): TypeComparisonResult2 {
        if (isset(static::CONTAINED_BY[get_class($other)])) {
            return TypeComparisonResult2::true();
        }

        if (isset(static::COERCIBLE_TO[get_class($other)])
            || isset($other::COERCIBLE_TO[get_class($this)])
        ) {
            // If both are scalars, scalar type coercion is used, but if both have the same
            // PHP scalar type (eg non-empty-string and string) non-scalar coercion is used.
            if (($this instanceof Scalar || $other instanceof Scalar)
                && !($this instanceof TBool && $other instanceof TBool)
                && !($this instanceof TFloat && $other instanceof TFloat)
                && !($this instanceof TInt && $other instanceof TInt)
                && !($this instanceof TString && $other instanceof TString)
            ) {
                return TypeComparisonResult2::scalarCoerced();
            }
            return TypeComparisonResult2::coerced();
        }

        return TypeComparisonResult2::false();
    }

    private function possiblyNegatedContainedBy(
        Atomic $other,
        ?Codebase $codebase,
        bool $check_less_specific = true
    ): TypeComparisonResult2 {
        $a = $this;
        if ($this->negated) {
            $a = clone $a;
            $a->negated = false;
        }
        $b = $other;
        if ($other->negated) {
            $b = clone $b;
            $b->negated = false;
        }

        if ($this->negated && $other->negated) {
            $result = $b->containedByAtomic($a, $codebase);
        } elseif ($this->negated) {
            // TODO
            $result = TypeComparisonResult2::false();
        } elseif ($other->negated) {
            // TODO
            $result = TypeComparisonResult2::false();
        } else {
            $result = $a->containedByAtomic($b, $codebase);
        }

        if ($result->result) {
            return $result;
        }

        // Set $result->is_less_specific to false. It could be set to true if some contained type (eg in an array)
        // is less specific, but the entire type still might not be less specific.
        // TODO actually this might not be necessary, types containing other types should `and()` their results anyway.
        $result = $result->and(TypeComparisonResult2::true());

        if ($check_less_specific) {
            $less_specific_result = TypeComparisonResult2::lessSpecific(
                $other->possiblyNegatedContainedBy($this, $codebase, false)->result
            );
            if (!$result->result_with_coercion && $result->result_with_coercion_from_mixed) {
                // Mixed is always less specific, but less specific implies coercible, so if the type is already
                // coerced from mixed, we need to keep it that way.
                $less_specific_result = $less_specific_result->and(TypeComparisonResult2::coercedFromMixed());
            }
            $result = $result->or($less_specific_result);
        }

        return $result;
    }

    /**
     * Should only be called where $this->negated === $other->negated === false.
     */
    protected function intersectsAtomic(Atomic $other, ?Codebase $codebase): TypeComparisonResult2
    {
        if (isset(static::INTERSECTS[get_class($other)]) || isset($other::INTERSECTS[get_class($this)])) {
            return TypeComparisonResult2::true();
        }

        $subtype_result = $this->containedBy($other, $codebase);
        $supertype_result = $other->containedBy($this, $codebase);
        return $subtype_result->or($supertype_result);
    }

    private function possiblyNegatedIntersectsAtomic(Atomic $other, ?Codebase $codebase): TypeComparisonResult2
    {
        $a = $this;
        if ($this->negated) {
            $a = clone $a;
            $a->negated = false;
        }
        $b = $other;
        if ($other->negated) {
            $b = clone $b;
            $b->negated = false;
        }

        if ($this->negated && $other->negated) {
            $result = TypeComparisonResult2::true(); // TODO is this always true?
        } elseif ($this->negated) {
            $result = $b->containedBy($a, $codebase)->not();
        } elseif ($other->negated) {
            $result = $a->containedBy($b, $codebase)->not();
        } else {
            $result = $a->intersectsAtomic($b, $codebase);
        }

        // $result = $result->or(new TypeComparisonResult2(false, false, false, false, $this instanceof Scalar && $other instanceof Scalar));

        return $result;
    }

    /**
     * @psalm-mutation-free
     */
    final public function containedBy(TypeNode $other, ?Codebase $codebase = null): TypeComparisonResult2
    {
        // static $calls = 0;
        // if (++$calls % 1 === 0) {
        //     echo "containedBy " . ($calls) . "\n";
        // }
        // if ($calls > 10000000) {
        //     $e = new \Exception();
        //     $trace = $e->getTraceAsString();
        //     echo substr($trace, 0, 10000);
        //     exit;
        // }

        if ($other instanceof Atomic) {
            return $this->possiblyNegatedContainedBy($other, $codebase);
        }

        if ($other instanceof Union) {
            if (get_class($this) === TArrayKey::class) {
                // Special case, array-key needs to be expanded to string|int
                // TODO handle in a more generic way? Need to do something like this for Scalar as well.
                return Union::create([new TString(), new TInt()])->containedBy($other, $codebase);
            }

            $result = TypeComparisonResult2::false();
            foreach ($other->getChildNodes() as $other_child) {
                $result = $result->or($this->containedBy($other_child, $codebase));
            }
            return $result;
        }

        if ($other instanceof Intersection) {
            $result = TypeComparisonResult2::true();
            foreach ($other->getChildNodes() as $other_child) {
                $result = $result->and($this->containedBy($other_child, $codebase));
            }
            return $result;
        }

        throw new InvalidArgumentException('Only Atomic, Union, and Intersection are supported.');
    }

    final public function intersects(TypeNode $other, ?Codebase $codebase = null): TypeComparisonResult2
    {
        if ($other instanceof Atomic) {
            return $this->possiblyNegatedIntersectsAtomic($other, $codebase);
        }

        if ($other instanceof Union) {
            $result = TypeComparisonResult2::false();
            foreach ($other->getChildNodes() as $other_child) {
                $result = $result->or($this->intersects($other_child, $codebase));
            }
            return $result;
        }

        if ($other instanceof Intersection) {
            $result = TypeComparisonResult2::true();
            foreach ($other->getChildNodes() as $other_child) {
                $result = $result->and($this->intersects($other_child, $codebase));
            }
            return $result;
        }

        throw new InvalidArgumentException('Only Atomic, Union, and Intersection are supported.');
    }
}
