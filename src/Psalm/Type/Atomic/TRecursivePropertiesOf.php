<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\TemplateInferredTypeReplacer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TemplateStandinTypeReplacer;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;
use Psalm\Type\Atomic;
use Psalm\Type\Union;

/**
 * Type that resolves to a keyed-array with properties of a class as keys and
 * their appropriate types as values. Recursively expands class-like properties
 * in the same way. Refer to `RecursivePropertiesOfExpander` for details on
 * expansion.
 *
 * @internal
 * @psalm-immutable
 */
final class TRecursivePropertiesOf extends Atomic
{
    use UnserializeMemoryUsageSuppressionTrait;

    public function __construct(
        public Union $types,
        bool $from_docblock = false,
    ) {
        parent::__construct($from_docblock);
    }

    #[Override]
    public function getKey(bool $include_extra = true): string
    {
        return 'recursive-properties-of<' . (string)$this->types . '>';
    }

    #[Override]
    protected function getChildNodeKeys(): array
    {
        return ['types'];
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
        return $this->getKey();
    }

    #[Override]
    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
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
        ?string $calling_class = null,
        ?string $calling_function = null,
        bool $replace = true,
        bool $add_lower_bound = false,
        int $depth = 0,
    ): self {
        $types_param = null;

        if ($input_type instanceof TRecursivePropertiesOf) {
            $types_param = $input_type->types;
        }

        $types = TemplateStandinTypeReplacer::replace(
            $this->types,
            $template_result,
            $codebase,
            $statements_analyzer,
            $types_param,
            $input_arg_offset,
            $calling_class,
            $calling_function,
            $replace,
            $add_lower_bound,
            null,
            $depth,
        );

        if ($types !== $this->types) {
            $cloned = clone $this;
            $cloned->types = $types;
            return $cloned;
        }

        return $this;
    }

    /**
     * @return static
     */
    #[Override]
    public function replaceTemplateTypesWithArgTypes(
        TemplateResult $template_result,
        ?Codebase $codebase,
    ): self {
        $types = TemplateInferredTypeReplacer::replace(
            $this->types,
            $template_result,
            $codebase,
        );

        if ($types !== $this->types) {
            $cloned = clone $this;
            $cloned->types = $types;
            return $cloned;
        }

        return $this;
    }

    #[Override]
    public function equals(Atomic $other_type, bool $ensure_source_equality): bool
    {
        if ($other_type::class !== static::class) {
            return false;
        }

        /** @var static $other_type */
        return $this->types->equals($other_type->types);
    }
}
