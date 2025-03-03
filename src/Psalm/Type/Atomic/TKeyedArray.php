<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\TemplateInferredTypeReplacer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TemplateStandinTypeReplacer;
use Psalm\Internal\Type\TypeCombiner;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Union;

use function addslashes;
use function array_key_first;
use function assert;
use function count;
use function implode;
use function is_int;
use function is_string;
use function ksort;
use function preg_match;
use function sort;
use function str_replace;

/**
 * Represents an 'object-like array' - an array with known keys.
 *
 * @psalm-api
 * @psalm-immutable
 */
final class TKeyedArray extends Atomic
{
    use UnserializeMemoryUsageSuppressionTrait;

    /**
     * Constructs a new instance of a generic type
     *
     * @param non-empty-array<string|int, Union> $properties
     * @param array{Union, Union}|null $fallback_params
     * @param array<string, bool> $class_strings
     */
    private function __construct(
        public array $properties,
        public ?array $class_strings = null,
        /**
         * If the shape has fallback params then they are here
         *
         * @var array{Union, Union}|null
         */
        public ?array $fallback_params = null,
        /**
         * @var bool - if this is a list of sequential elements
         */
        public bool $is_list = false,
        public bool $is_callable = false,
        bool $from_docblock = false,
    ) {
        parent::__construct($from_docblock);
    }

    #[Override]
    public function isCallableType(): bool
    {
        return $this->is_callable;
    }

    /**
     * @psalm-pure
     * @param non-empty-array<string|int, Union> $properties
     * @param array{Union, Union}|null $fallback_params
     * @param array<string, bool> $class_strings
     */
    public static function make(
        array $properties,
        ?array $class_strings = null,
        ?array $fallback_params = null,
        bool $is_list = false,
        bool $from_docblock = false,
    ): self|TArray {
        if ($is_list && $fallback_params) {
            $fallback_params[0] = Type::getListKey();
        }
        if (count($properties) === 1
            && $properties[array_key_first($properties)]->isNever()
            && ($fallback_params === null || $fallback_params[1]->isNever())
        ) {
            $never = $properties[array_key_first($properties)]->setPossiblyUndefined(false);
            return new TArray([
                $never, $never,
            ], $from_docblock);
        }
        if ($is_list) {
            $last_k = -1;
            $had_possibly_undefined = false;
            ksort($properties);
            foreach ($properties as $k => $v) {
                if (is_string($k) || $last_k !== ($k-1) || ($had_possibly_undefined && !$v->possibly_undefined)) {
                    $is_list = false;
                    break;
                }
                if ($v->possibly_undefined) {
                    $had_possibly_undefined = true;
                }
                $last_k = $k;
            }
        }

        return new self($properties, $class_strings, $fallback_params, $is_list, false, $from_docblock);
    }

    /**
     * @psalm-pure
     * @param non-empty-array<string|int, Union> $properties
     * @param array<string, bool> $class_strings
     */
    public static function makeCallable(
        array $properties,
        ?array $class_strings = null,
        bool $is_list = false,
        bool $from_docblock = false,
    ): self {
        return new self($properties, $class_strings, null, $is_list, true, $from_docblock);
    }

    public function setIsCallable(bool $is_callable): self
    {
        if ($is_callable === $this->is_callable) {
            return $this;
        }
        $cloned = clone $this;
        $cloned->is_callable = $is_callable;
        return $cloned;
    }

    /**
     * @param non-empty-array<string|int, Union> $properties
     */
    public function setProperties(array $properties): self|TArray
    {
        if ($properties === $this->properties) {
            return $this;
        }
        if (count($properties) === 1
            && $properties[array_key_first($properties)]->isNever()
            && ($this->fallback_params === null || $this->fallback_params[1]->isNever())
        ) {
            $never = $properties[array_key_first($properties)]->setPossiblyUndefined(false);
            return new TArray([
                $never, $never,
            ], $this->from_docblock);
        }
        $cloned = clone $this;
        $cloned->properties = $properties;
        if ($cloned->is_list) {
            $last_k = -1;
            $had_possibly_undefined = false;

            /** @psalm-suppress InaccessibleProperty */
            ksort($cloned->properties);
            foreach ($cloned->properties as $k => $v) {
                if (is_string($k) || $last_k !== ($k-1) || ($had_possibly_undefined && !$v->possibly_undefined)) {
                    $cloned->is_list = false;
                    break;
                }
                if ($v->possibly_undefined) {
                    $had_possibly_undefined = true;
                }
                $last_k = $k;
            }
        }
        return $cloned;
    }

    public function makeSealed(): self
    {
        if ($this->fallback_params === null) {
            return $this;
        }
        $cloned = clone $this;
        $cloned->fallback_params = null;
        return $cloned;
    }

    public function isSealed(): bool
    {
        return $this->fallback_params === null;
    }

    /**
     * @psalm-assert-if-true list{Union} $this->properties
     * @psalm-assert-if-true list{Union, Union} $this->fallback_params
     */
    public function isGenericList(): bool
    {
        return $this->is_list
            && count($this->properties) === 1
            && $this->fallback_params
            && $this->properties[0]->equals($this->fallback_params[1], true, true, false);
    }

    #[Override]
    public function getId(bool $exact = true, bool $nested = false): string
    {
        $property_strings = [];

        if ($this->is_list) {
            if ($this->isGenericList()) {
                $t = $this->properties[0]->possibly_undefined ? 'list' : 'non-empty-list';
                return "$t<".$this->fallback_params[1]->getId($exact).'>';
            }
            $use_list_syntax = true;
            foreach ($this->properties as $property) {
                if ($property->possibly_undefined) {
                    $use_list_syntax = false;
                    break;
                }
            }
        } else {
            $use_list_syntax = false;
        }

        foreach ($this->properties as $name => $type) {
            if ($use_list_syntax) {
                $property_strings[$name] = $type->getId($exact);
                continue;
            }

            $class_string_suffix = '';
            if (isset($this->class_strings[$name])) {
                $class_string_suffix = '::class';
            }

            $name = $this->escapeAndQuote($name);

            $property_strings[$name] = $name . $class_string_suffix . ($type->possibly_undefined ? '?' : '')
                . ': ' . $type->getId($exact);
        }

        if ($this->is_list) {
            $key = $this->is_callable ? 'callable-list' : 'list';
        } else {
            $key = $this->is_callable ? 'callable-array' : 'array';
            sort($property_strings);
        }

        $params_part = $this->fallback_params !== null
            ? ', ...<' . ($this->is_list
                ? $this->fallback_params[1]->getId($exact)
                : $this->fallback_params[0]->getId($exact) . ', '
                    . $this->fallback_params[1]->getId($exact)
            ) . '>'
            : '';

        return $key . '{' . implode(', ', $property_strings) . $params_part . '}';
    }

    /**
     * @param  array<lowercase-string, string> $aliased_classes
     */
    #[Override]
    public function toNamespacedString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        bool $use_phpdoc_format,
    ): string {
        if ($use_phpdoc_format) {
            return $this->getGenericArrayType()->toNamespacedString(
                $namespace,
                $aliased_classes,
                $this_class,
                true,
            );
        }

        $suffixed_properties = [];

        if ($this->is_list) {
            if (count($this->properties) === 1
                && $this->fallback_params
                && $this->properties[0]->equals($this->fallback_params[1], true, true, false)
            ) {
                $t = $this->properties[0]->possibly_undefined ? 'list' : 'non-empty-list';
                return "$t<".$this->fallback_params[1]->getId().'>';
            }
            $use_list_syntax = true;
            foreach ($this->properties as $property) {
                if ($property->possibly_undefined) {
                    $use_list_syntax = false;
                    break;
                }
            }
        } else {
            $use_list_syntax = false;
        }

        foreach ($this->properties as $name => $type) {
            if ($use_list_syntax) {
                $suffixed_properties[$name] = $type->toNamespacedString(
                    $namespace,
                    $aliased_classes,
                    $this_class,
                    false,
                );
                continue;
            }

            $class_string_suffix = '';
            if (isset($this->class_strings[$name])) {
                $class_string_suffix = '::class';
            }

            $name = $this->escapeAndQuote($name);

            $suffixed_properties[$name] = $name . $class_string_suffix . ($type->possibly_undefined ? '?' : '') . ': ' .
                $type->toNamespacedString(
                    $namespace,
                    $aliased_classes,
                    $this_class,
                    false,
                );
        }

        $params_part = $this->fallback_params !== null ? ',...' : '';

        return  ($this->is_list
            ? ($this->is_callable ? 'callable-list' : 'list')
            : ($this->is_callable ? 'callable-array' : 'array')
        ) . '{' . implode(', ', $suffixed_properties) . $params_part . '}';
    }

    /**
     * @param  array<lowercase-string, string> $aliased_classes
     */
    #[Override]
    public function toPhpString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        int $analysis_php_version_id,
    ): string {
        return 'array';
    }

    #[Override]
    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }

    public function getGenericKeyType(bool $possibly_undefined = false): Union
    {
        if ($this->is_list) {
            if ($this->fallback_params) {
                return Type::getListKey();
            }
            if (count($this->properties) === 1) {
                return new Union([new TLiteralInt(0)]);
            }
            return Type::getIntRange(0, count($this->properties)-1);
        }

        $key_types = [];

        foreach ($this->properties as $key => $_) {
            if (is_int($key)) {
                $key_types[] = new TLiteralInt($key);
            } elseif (isset($this->class_strings[$key])) {
                $key_types[] = new TLiteralClassString($key);
            } else {
                /** @psalm-suppress ImpureMethodCall let's assume string interpreters are pure */
                $key_types[] = Type::getAtomicStringFromLiteral($key);
            }
        }

        $key_type = TypeCombiner::combine($key_types);

        /** @psalm-suppress InaccessibleProperty We just created this type */
        $key_type->possibly_undefined = $possibly_undefined;

        if ($this->fallback_params === null) {
            return $key_type;
        }

        return Type::combineUnionTypes($this->fallback_params[0], $key_type);
    }

    public function getGenericValueType(bool $possibly_undefined = false): Union
    {
        $value_type = null;

        foreach ($this->properties as $property) {
            $value_type = Type::combineUnionTypes($property, $value_type);
        }

        return Type::combineUnionTypes(
            $this->fallback_params[1] ?? null,
            $value_type,
            null,
            false,
            true,
            500,
            $possibly_undefined,
        );
    }

    /**
     * @return TArray|TNonEmptyArray
     */
    public function getGenericArrayType(?string $list_var_id = null): TArray
    {
        $key_types = [];
        $value_type = null;

        $has_defined_keys = false;

        foreach ($this->properties as $key => $property) {
            if ($this->is_list) {
                // Do nothing
            } elseif (is_int($key)) {
                $key_types[] = new TLiteralInt($key);
            } elseif (isset($this->class_strings[$key])) {
                $key_types[] = new TLiteralClassString($key);
            } else {
                /** @psalm-suppress ImpureMethodCall let's assume string interpreters are pure */
                $key_types[] = Type::getAtomicStringFromLiteral($key);
            }

            $value_type = Type::combineUnionTypes($property, $value_type);

            if (!$property->possibly_undefined) {
                $has_defined_keys = true;
            }
        }

        if ($this->is_list) {
            if ($this->fallback_params !== null) {
                $value_type = Type::combineUnionTypes($this->fallback_params[1], $value_type);
            }

            $value_type = $value_type->setPossiblyUndefined(false);

            if ($this->fallback_params === null) {
                $key_type = new Union([new TIntRange(0, count($this->properties)-1, false, $list_var_id)]);
            } else {
                $key_type = new Union([new TIntRange(0, null, false, $list_var_id)]);
            }

            if ($has_defined_keys) {
                return new TNonEmptyArray([$key_type, $value_type]);
            }
            return new TArray([$key_type, $value_type]);
        }

        assert($key_types !== []);
        $key_type = TypeCombiner::combine($key_types);

        if ($this->fallback_params !== null) {
            $key_type = Type::combineUnionTypes($this->fallback_params[0], $key_type);
            $value_type = Type::combineUnionTypes($this->fallback_params[1], $value_type);
        }

        $value_type = $value_type->setPossiblyUndefined(false);

        if ($has_defined_keys) {
            return new TNonEmptyArray([$key_type, $value_type]);
        }
        return new TArray([$key_type, $value_type]);
    }

    public function isNonEmpty(): bool
    {
        if ($this->isGenericList()) {
            return !$this->properties[0]->possibly_undefined;
        }
        foreach ($this->properties as $property) {
            if (!$property->possibly_undefined) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return int<0, max>
     */
    public function getMinCount(): int
    {
        if ($this->is_list) {
            foreach ($this->properties as $k => $property) {
                if ($property->possibly_undefined || $property->isNever()) {
                    /** @var int<0, max> */
                    return $k;
                }
            }
            return count($this->properties);
        }
        $prop_min_count = 0;
        foreach ($this->properties as $property) {
            if (!($property->possibly_undefined || $property->isNever())) {
                $prop_min_count++;
            }
        }
        return $prop_min_count;
    }

    /**
     * Returns null if there is no upper limit.
     *
     * @return int<1, max>|null
     */
    public function getMaxCount(): ?int
    {
        if ($this->fallback_params) {
            return null;
        }
        $prop_max_count = 0;
        foreach ($this->properties as $property) {
            if (!$property->isNever()) {
                $prop_max_count++;
            }
        }
        assert($prop_max_count !== 0);
        return $prop_max_count;
    }
    /**
     * Whether all keys are always defined (ignores unsealedness).
     */
    public function allShapeKeysAlwaysDefined(): bool
    {
        foreach ($this->properties as $property) {
            if ($property->possibly_undefined) {
                return false;
            }
        }

        return true;
    }

    #[Override]
    public function getKey(bool $include_extra = true): string
    {
        return 'array';
    }

    #[Override]
    public function replaceTemplateTypesWithStandins(
        TemplateResult $template_result,
        Codebase $codebase,
        ?StatementsAnalyzer $statements_analyzer = null,
        ?Atomic $input_type = null,
        ?int $input_arg_offset = null,
        ?string $calling_class = null,
        ?string $calling_function = null,
        bool $replace = true,
        bool $add_lower_bound = false,
        int $depth = 0,
    ): self|TArray {
        if ($input_type instanceof TKeyedArray
            && $input_type->is_list
            && $input_type->isSealed()
            && $this->isGenericList()
        ) {
            $replaced_list_type = $this
                ->getGenericArrayType()
                ->replaceTemplateTypesWithStandins(
                    $template_result,
                    $codebase,
                    $statements_analyzer,
                    $input_type->getGenericArrayType(),
                    $input_arg_offset,
                    $calling_class,
                    $calling_function,
                    $replace,
                    $add_lower_bound,
                    $depth,
                )
                ->type_params[1]
                ->setPossiblyUndefined(!$this->isNonEmpty());

            if ($replaced_list_type->isNever()) {
                $replaced_list_type = $replaced_list_type->setPossiblyUndefined(false);
                return new TArray([
                    $replaced_list_type, $replaced_list_type,
                ], $this->from_docblock);
            }
            $cloned = clone $this;
            $cloned->properties = [$replaced_list_type];
            $cloned->fallback_params = [$this->fallback_params[1], $replaced_list_type];

            return $cloned;
        }

        $properties = $this->properties;

        foreach ($properties as $offset => $property) {
            $input_type_param = null;

            if ($input_type instanceof TKeyedArray
                && isset($input_type->properties[$offset])
            ) {
                $input_type_param = $input_type->properties[$offset];
            }

            $properties[$offset] = TemplateStandinTypeReplacer::replace(
                $property,
                $template_result,
                $codebase,
                $statements_analyzer,
                $input_type_param,
                $input_arg_offset,
                $calling_class,
                $calling_function,
                $replace,
                $add_lower_bound,
                null,
                $depth,
            );
        }

        $fallback_params = $this->fallback_params;

        foreach ($fallback_params ?? [] as $offset => $property) {
            $input_type_param = null;

            if ($input_type instanceof TKeyedArray
                && isset($input_type->fallback_params[$offset])
            ) {
                $input_type_param = $input_type->fallback_params[$offset];
            }

            $fallback_params[$offset] = TemplateStandinTypeReplacer::replace(
                $property,
                $template_result,
                $codebase,
                $statements_analyzer,
                $input_type_param,
                $input_arg_offset,
                $calling_class,
                $calling_function,
                $replace,
                $add_lower_bound,
                null,
                $depth,
            );
        }


        if ($properties === $this->properties && $fallback_params === $this->fallback_params) {
            return $this;
        }
        $cloned = clone $this;
        if (count($properties) === 1
            && $properties[array_key_first($properties)]->isNever()
            && ($fallback_params === null || $fallback_params[1]->isNever())
        ) {
            $never = $properties[array_key_first($properties)]->setPossiblyUndefined(false);
            return new TArray([
                $never, $never,
            ], $this->from_docblock);
        }
        $cloned->properties = $properties;
        /** @psalm-suppress PropertyTypeCoercion */
        $cloned->fallback_params = $fallback_params;
        return $cloned;
    }

    #[Override]
    public function replaceTemplateTypesWithArgTypes(
        TemplateResult $template_result,
        ?Codebase $codebase,
    ): self|TArray {
        $properties = $this->properties;
        foreach ($properties as $offset => $property) {
            $properties[$offset] = TemplateInferredTypeReplacer::replace(
                $property,
                $template_result,
                $codebase,
            );
        }
        $fallback_params = $this->fallback_params;
        foreach ($fallback_params ?? [] as $offset => $property) {
            $fallback_params[$offset] = TemplateInferredTypeReplacer::replace(
                $property,
                $template_result,
                $codebase,
            );
        }
        if ($properties !== $this->properties || $fallback_params !== $this->fallback_params) {
            $cloned = clone $this;
            if (count($properties) === 1
                && $properties[array_key_first($properties)]->isNever()
                && ($fallback_params === null || $fallback_params[1]->isNever())
            ) {
                $never = $properties[array_key_first($properties)]->setPossiblyUndefined(false);
                return new TArray([
                    $never, $never,
                ], $this->from_docblock);
            }
            $cloned->properties = $properties;
            /** @psalm-suppress PropertyTypeCoercion */
            $cloned->fallback_params = $fallback_params;
            return $cloned;
        }
        return $this;
    }

    #[Override]
    protected function getChildNodeKeys(): array
    {
        return ['properties', 'fallback_params'];
    }

    #[Override]
    public function equals(Atomic $other_type, bool $ensure_source_equality): bool
    {
        if ($other_type::class !== static::class) {
            return false;
        }

        if (count($this->properties) !== count($other_type->properties)) {
            return false;
        }

        if (($this->fallback_params === null) !== ($other_type->fallback_params === null)) {
            return false;
        }

        if ($this->fallback_params !== null && $other_type->fallback_params !== null) {
            if (!$this->fallback_params[0]->equals($other_type->fallback_params[0], false, false)) {
                return false;
            }

            if (!$this->fallback_params[1]->equals($other_type->fallback_params[1], false, false)) {
                return false;
            }
        }

        foreach ($this->properties as $property_name => $property_type) {
            if (!isset($other_type->properties[$property_name])) {
                return false;
            }

            if (!$property_type->equals($other_type->properties[$property_name], $ensure_source_equality, false)) {
                return false;
            }
        }

        return true;
    }

    #[Override]
    public function getAssertionString(): string
    {
        return $this->is_list ? 'list' : 'array';
    }

    private function escapeAndQuote(string|int $name): string|int
    {
        if (is_string($name)) {
            $quote = false;

            if ($name === '' || preg_match('/[^a-zA-Z0-9_]/', $name)) {
                $quote = true;
            }

            if (preg_match('/^-?[1-9][0-9]*$/', $name)
                && (string)(int) $name !== $name // overflow occurred
            ) {
                $quote = true;
            }

            if (preg_match('/^[1-9][0-9]*_([0-9]+_)*[0-9]+$/', $name)) {
                $quote = true;
            }

            // 08 should be quoted since it's numeric but it's handled as string and not cast to int
            if (preg_match('/^0[0-9]+$/', $name)) {
                $quote = true;
            }

            if (preg_match('/^[0-9]+e-?[0-9]+$/', $name)) {
                $quote = true;
            }

            if ($quote) {
                $name = '\'' . str_replace("\n", '\n', addslashes($name)) . '\'';
            }
        }

        return $name;
    }
}
