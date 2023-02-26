<?php

namespace Psalm\Type\Atomic;

use Psalm\Codebase;
use Psalm\Internal\Type\TemplateInferredTypeReplacer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Type\Atomic;
use Psalm\Type\Union;

/**
 * Internal representation of a conditional return type in phpdoc. For example ($param1 is int ? int : string)
 *
 * @psalm-immutable
 */
final class TConditional extends Atomic
{
    /**
     * @var string
     */
    public $param_name;

    /**
     * @var string
     */
    public $defining_class;

    /**
     * @var Union
     */
    public $as_type;

    /**
     * @var Union
     */
    public $conditional_type;

    /**
     * @var Union
     */
    public $if_type;

    /**
     * @var Union
     */
    public $else_type;

    public function __construct(
        string $param_name,
        string $defining_class,
        Union $as_type,
        Union $conditional_type,
        Union $if_type,
        Union $else_type,
        bool $from_docblock = false
    ) {
        $this->param_name = $param_name;
        $this->defining_class = $defining_class;
        $this->as_type = $as_type;
        $this->conditional_type = $conditional_type;
        $this->if_type = $if_type;
        $this->else_type = $else_type;
        parent::__construct($from_docblock);
    }

    public function setTypes(
        ?Union $as_type,
        ?Union $conditional_type = null,
        ?Union $if_type = null,
        ?Union $else_type = null
    ): self {
        $as_type ??= $this->as_type;
        $conditional_type ??= $this->conditional_type;
        $if_type ??= $this->if_type;
        $else_type ??= $this->else_type;

        if ($as_type === $this->as_type
            && $conditional_type === $this->conditional_type
            && $if_type === $this->if_type
            && $else_type === $this->else_type
        ) {
            return $this;
        }
        $cloned = clone $this;
        $cloned->as_type = $as_type;
        $cloned->conditional_type = $conditional_type;
        $cloned->if_type = $if_type;
        $cloned->else_type = $else_type;
        return $cloned;
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'TConditional<' . $this->param_name . '>';
    }

    public function getAssertionString(): string
    {
        return '';
    }

    public function getId(bool $exact = true, bool $nested = false): string
    {
        return '('
            . $this->param_name
            . ' is ' . $this->conditional_type->getId($exact)
            . ' ? ' . $this->if_type->getId($exact)
            . ' : ' . $this->else_type->getId($exact)
            . ')';
    }

    /**
     * @param  array<lowercase-string, string> $aliased_classes
     * @return null
     */
    public function toPhpString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        int $analysis_php_version_id
    ): ?string {
        return null;
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
        return '';
    }

    protected function getChildNodeKeys(): array
    {
        return ['conditional_type', 'if_type', 'else_type'];
    }

    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }

    /**
     * @return static
     */
    public function replaceTemplateTypesWithArgTypes(
        TemplateResult $template_result,
        ?Codebase $codebase
    ): self {
        $conditional = TemplateInferredTypeReplacer::replace(
            $this->conditional_type,
            $template_result,
            $codebase,
        );
        if ($conditional === $this->conditional_type) {
            return $this;
        }
        return new static(
            $this->param_name,
            $this->defining_class,
            $this->as_type,
            $conditional,
            $this->if_type,
            $this->else_type,
        );
    }
}
