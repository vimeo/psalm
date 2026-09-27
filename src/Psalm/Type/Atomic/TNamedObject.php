<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Interner;
use Psalm\Storage\Mutations;
use Psalm\StrId;
use Psalm\Type;
use Psalm\Type\Atomic;

use function array_map;
use function assert;
use function implode;
use function str_starts_with;
use function strrpos;
use function substr;

/**
 * Denotes an object type where the type of the object is known e.g. `Exception`, `Throwable`, `Foo\Bar`
 *
 * @psalm-immutable
 * @api
 */
class TNamedObject extends Atomic
{
    use HasIntersectionTrait;

    /** Interned class name */
    public int $value;

    public bool $is_static_resolved = false;

    /**
     * @param int $value the interned name of the object
     * @param array<string, TNamedObject|TTemplateParam|TIterable|TObjectWithProperties|TCallableObject> $extra_types
     */
    public function __construct(
        int $value,
        public bool $is_static = false,
        /**
         * Whether or not this type can represent a child of the class named in $value
         */
        public bool $definite_class = false,
        array $extra_types = [],
        bool $from_docblock = false,
    ) {
        assert(
            !str_starts_with(Interner::$strings[$value], '\\'),
            'Class names must be interned without a leading backslash',
        );
        $this->value = $value;
        $this->extra_types = $extra_types;
        parent::__construct($from_docblock);
    }

    /**
     * @return static
     */
    public function setIsStatic(bool $is_static, ?bool $is_static_resolved = null): self
    {
        $is_static_resolved ??= $this->is_static_resolved;
        if ($this->is_static === $is_static && $this->is_static_resolved === $is_static_resolved) {
            return $this;
        }
        $cloned = clone $this;
        $cloned->is_static = $is_static;
        $cloned->is_static_resolved = $is_static_resolved;
        return $cloned;
    }

    /**
     * @return static
     */
    public function setValue(int $value): self
    {
        if ($value === $this->value) {
            return $this;
        }
        $cloned = clone $this;
        $cloned->value = $value;
        return $cloned;
    }
    /**
     * @return static
     */
    public function setValueIsStatic(int $value, bool $is_static, ?bool $is_static_resolved = null): self
    {
        $is_static_resolved ??= $this->is_static_resolved;
        if ($value === $this->value
            && $this->is_static === $is_static
            && $this->is_static_resolved === $is_static_resolved
        ) {
            return $this;
        }
        $cloned = clone $this;
        $cloned->value = $value;
        $cloned->is_static = $is_static;
        $cloned->is_static_resolved = $is_static;
        return $cloned;
    }
    /** @psalm-suppress ImpureStaticProperty read-only access to the interned strings table */
    #[Override]
    public function getKey(bool $include_extra = true): string
    {
        if ($include_extra && $this->extra_types) {
            return Interner::$strings[$this->value] . '&' . implode('&', $this->extra_types);
        }

        return Interner::$strings[$this->value];
    }

    /** @psalm-suppress ImpureStaticProperty read-only access to the interned strings table */
    #[Override]
    public function getId(bool $exact = true, bool $nested = false): string
    {
        if ($this->extra_types) {
            return Interner::$strings[$this->value] . '&' . implode(
                '&',
                array_map(
                    static fn(Atomic $type): string => $type->getId($exact, true),
                    $this->extra_types,
                ),
            );
        }

        return $this->is_static && $exact
            ? Interner::$strings[$this->value] . '&static'
            : Interner::$strings[$this->value];
    }

    /**
     * @param  array<int, int> $aliased_classes
     */
    #[Override]
    public function toNamespacedString(
        ?int $namespace,
        array $aliased_classes,
        ?int $this_class,
        bool $use_phpdoc_format,
    ): string {
        if ($this->value === StrId::static) {
            return 'static';
        }

        $intersection_types = $this->getNamespacedIntersectionTypes(
            $namespace,
            $aliased_classes,
            $this_class,
            $use_phpdoc_format,
        );

        return Type::getStringFromFQCLN(
            $this->value,
            $namespace,
            $aliased_classes,
            $this_class,
            true,
            $this->is_static,
        ) . $intersection_types;
    }

    /**
     * @param  array<int, int> $aliased_classes
     */
    #[Override]
    public function toPhpString(
        ?int $namespace,
        array $aliased_classes,
        ?int $this_class,
        int $analysis_php_version_id,
    ): ?string {
        if ($this->value === StrId::static) {
            return $analysis_php_version_id >= 8_00_00 ? 'static' : null;
        }

        if ($this->is_static && $this->value === $this_class) {
            return $analysis_php_version_id >= 8_00_00 ? 'static' : 'self';
        }

        $result = $this->toNamespacedString($namespace, $aliased_classes, $this_class, false);
        $intersection = strrpos($result, '&');
        if ($intersection === false || $analysis_php_version_id >= 8_01_00) {
            return $result;
        }
        return substr($result, $intersection+1);
    }

    #[Override]
    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return ($this->value !== StrId::static && $this->is_static === false) || $analysis_php_version_id >= 8_00_00;
    }

    /**
     * @return static
     */
    #[Override]
    public function replaceTemplateTypesWithArgTypes(
        TemplateResult $template_result,
        ?Codebase $codebase,
    ): self {
        $intersection = $this->replaceIntersectionTemplateTypesWithArgTypes($template_result, $codebase);
        if (!$intersection) {
            return $this;
        }
        $cloned = clone $this;
        $cloned->extra_types = $intersection;
        return $cloned;
    }

    /**
     * @return static
     */
    #[Override]
    public function replaceTemplateTypesWithStandins(
        TemplateResult $template_result,
        Codebase $codebase,
        ?StatementsAnalyzer $statements_analyzer = null,
        ?Atomic $input_type = null,
        ?int $input_arg_offset = null,
        ?int $calling_class = null,
        ?int $calling_function = null,
        bool $replace = true,
        bool $add_lower_bound = false,
        int $depth = 0,
    ): self {
        $intersection = $this->replaceIntersectionTemplateTypesWithStandins(
            $template_result,
            $codebase,
            $statements_analyzer,
            $input_type,
            $input_arg_offset,
            $calling_class,
            $calling_function,
            $replace,
            $add_lower_bound,
            $depth,
        );
        if ($intersection) {
            $cloned = clone $this;
            $cloned->extra_types = $intersection;
            return $cloned;
        }
        return $this;
    }
    /**
     * @psalm-pure
     */
    #[Override]
    protected function getChildNodeKeys(): array
    {
        return ['extra_types'];
    }

    /**
     * @param array<string, TNamedObject|TTemplateParam|TIterable|TObjectWithProperties> $extra_types
     * @psalm-pure
     */
    public static function createFromName(
        int $value,
        bool $is_static = false,
        bool $definite_class = false,
        array $extra_types = [],
        bool $from_docblock = false,
    ): TNamedObject {
        if ($value === StrId::Closure) {
            return new TClosure(null, null, Mutations::LEVEL_ALL, [], $extra_types, $from_docblock);
        }

        return new TNamedObject($value, $is_static, $definite_class, $extra_types, $from_docblock);
    }
}
