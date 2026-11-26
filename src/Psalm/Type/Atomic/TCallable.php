<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Storage\Capabilities;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;
use Psalm\Type\Atomic;
use Psalm\Type\Union;

/**
 * Denotes the `callable` type. Can result from an `is_callable` check.
 *
 * @psalm-immutable
 * @api
 * @psalm-import-type CapabilitySet from Capabilities
 */
final class TCallable extends Atomic
{
    use UnserializeMemoryUsageSuppressionTrait;
    use CallableTrait;

    public string $value = 'callable';

    /**
     * Constructs a new instance of a generic type
     *
     * @param list<FunctionLikeParameter> $params
     * @param CapabilitySet|Union $purity A capability set or a purity type (see {@see CallableTrait::$purity})
     * @param ?non-empty-lowercase-string $callable_id The id of the underlying function/method, when
     *                                        known. It does not affect the structural type, but keeps the
     *                                        callables of different functions apart in a union, and is used to
     *                                        re-dispatch taint sinks/sources on invocation.
     */
    public function __construct(
        ?array $params = null,
        ?Union $return_type = null,
        int|Union $purity = Capabilities::ALL,
        bool $from_docblock = false,
        public ?string $callable_id = null,
    ) {
        $this->params = $params;
        $this->return_type = $return_type;
        $this->purity = self::purityFrom($purity);
        parent::__construct($from_docblock);
    }

    /**
     * Callables of different functions or methods are kept apart, though their types read the same
     */
    #[Override]
    public function getKey(bool $include_extra = true): string
    {
        $key = $this->getPurityString() . $this->getParamString() . $this->getReturnTypeString();

        return $this->callable_id === null ? $key : $key . '#' . $this->callable_id;
    }

    #[Override]
    public function equals(Atomic $other_type, bool $ensure_source_equality): bool
    {
        return $other_type instanceof self
            && $this->callable_id === $other_type->callable_id
            && parent::equals($other_type, $ensure_source_equality);
    }

    /**
     * @param array<lowercase-string, string> $aliased_classes
     * @psalm-pure
     */
    #[Override]
    public function toPhpString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        int $analysis_php_version_id,
    ): string {
        return 'callable';
    }

    #[Override]
    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return $this->params === null
            && $this->return_type === null
            && $this->hasFixedPurity()
            && $this->getCapabilities() === Capabilities::ALL;
    }

    /**
     * @return static
     */
    #[Override]
    public function replaceTemplateTypesWithArgTypes(TemplateResult $template_result, ?Codebase $codebase): self
    {
        $replaced = $this->replaceCallableTemplateTypesWithArgTypes($template_result, $codebase);
        if (!$replaced) {
            return $this;
        }
        return new static(
            $replaced[0],
            $replaced[1],
            $replaced[2],
            $this->from_docblock,
            $this->callable_id,
        );
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
        $replaced = $this->replaceCallableTemplateTypesWithStandins(
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
        if (!$replaced) {
            return $this;
        }
        return new static(
            $replaced[0],
            $replaced[1],
            $replaced[2],
            $this->from_docblock,
            $this->callable_id,
        );
    }

    #[Override]
    protected function getChildNodeKeys(): array
    {
        return $this->getCallableChildNodeKeys();
    }

    /**
     * @return true
     * @psalm-pure
     */
    #[Override]
    public function isCallableType(): bool
    {
        return true;
    }
}
