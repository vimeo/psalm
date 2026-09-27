<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Interner;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Storage\Mutations;
use Psalm\StrId;
use Psalm\Type\Atomic;
use Psalm\Type\Union;

/**
 * Represents a closure where we know the return type and params
 *
 * @psalm-immutable
 * @api
 */
final class TClosure extends TNamedObject
{
    use CallableTrait;

    /**
     * @psalm-pure
     */
    #[Override]
    public function isCallableType(): bool
    {
        return true;
    }
    
    /**
     * @param list<FunctionLikeParameter> $params
     * @param array<string, bool> $byref_uses
     * @param Mutations::LEVEL_* $allowed_mutations
     * @param array<string, TNamedObject|TTemplateParam|TIterable|TObjectWithProperties|TCallableObject> $extra_types
     * @param ?int $callable_id The interned lowercase id of the underlying function/method, when
     *                                        known (e.g. for a first-class callable `foo(...)`). Metadata
     *                                        only - it does not affect the structural type - and is
     *                                        used to re-dispatch taint sinks/sources on invocation.
     */
    public function __construct(
        ?array $params = null,
        ?Union $return_type = null,
        int $allowed_mutations = Mutations::LEVEL_ALL,
        public array $byref_uses = [],
        array $extra_types = [],
        bool $from_docblock = false,
        public ?int $callable_id = null,
    ) {
        $this->params = $params;
        $this->return_type = $return_type;
        $this->allowed_mutations = $allowed_mutations;
        parent::__construct(
            StrId::Closure,
            false,
            false,
            $extra_types,
            $from_docblock,
        );
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
    ): string {
        return parent::toNamespacedString($namespace, $aliased_classes, $this_class, true);
    }

    #[Override]
    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        // it can, if it's just 'Closure'
        return $this->params === null
            && $this->return_type === null
            && $this->allowed_mutations === Mutations::LEVEL_ALL;
    }

    /**
     * @return static
     */
    #[Override]
    public function replaceTemplateTypesWithArgTypes(
        TemplateResult $template_result,
        ?Codebase $codebase,
    ): self {
        $replaced = $this->replaceCallableTemplateTypesWithArgTypes($template_result, $codebase);
        $intersection = $this->replaceIntersectionTemplateTypesWithArgTypes($template_result, $codebase);
        if (!$replaced && !$intersection) {
            return $this;
        }
        return new static(
            $replaced[0] ?? $this->params,
            $replaced[1] ?? $this->return_type,
            $this->allowed_mutations,
            $this->byref_uses,
            $intersection ?? $this->extra_types,
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
        ?int $calling_class = null,
        ?int $calling_function = null,
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
        if (!$replaced && !$intersection) {
            return $this;
        }
        return new static(
            $replaced[0] ?? $this->params,
            $replaced[1] ?? $this->return_type,
            $this->allowed_mutations,
            $this->byref_uses,
            $intersection ?? $this->extra_types,
            $this->from_docblock,
            $this->callable_id,
        );
    }

    #[Override]
    protected function getChildNodeKeys(): array
    {
        return [...parent::getChildNodeKeys(), ...$this->getCallableChildNodeKeys()];
    }

    /**
     * @psalm-mutation-free
     */
    #[Override]
    protected function getCallableBaseName(): string
    {
        return Interner::str($this->value);
    }
}
