<?php

namespace Psalm\Type\Atomic;

use Psalm\Codebase;
use Psalm\Internal\Type\TemplateInferredTypeReplacer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Type\Atomic;
use Psalm\Type\Union;

/**
 * Represents the type used when using TPropertiesOf when the type of the array is a template
 *
 * @psalm-immutable
 */
final class TTemplatePropertiesOf extends Atomic
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
     * @var TTemplateParam
     */
    public $as;
    /**
     * @var TPropertiesOf::VISIBILITY_*|null
     */
    public $visibility_filter;

    /**
     * @param TPropertiesOf::VISIBILITY_*|null $visibility_filter
     */
    public function __construct(
        string $param_name,
        string $defining_class,
        TTemplateParam $as,
        ?int $visibility_filter,
        bool $from_docblock = false
    ) {
        $this->param_name = $param_name;
        $this->defining_class = $defining_class;
        $this->as = $as;
        $this->visibility_filter = $visibility_filter;
        parent::__construct($from_docblock);
    }

    public function getKey(bool $include_extra = true): string
    {
        return TPropertiesOf::tokenNameForFilter($this->visibility_filter) . '<' . $this->param_name . '>';
    }

    public function getId(bool $exact = true, bool $nested = false): string
    {
        if (!$exact) {
            return $this->getKey();
        }

        return TPropertiesOf::tokenNameForFilter($this->visibility_filter) . '<' . $this->as->getId($exact) . '>';
    }

    /**
     * @param  array<lowercase-string, string> $aliased_classes
     */
    public function toPhpString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        int $analysis_php_version_id
    ): string {
        return $this->getKey();
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
        $param = new TTemplateParam(
            $this->as->param_name,
            TemplateInferredTypeReplacer::replace(
                new Union([$this->as]),
                $template_result,
                $codebase,
            ),
            $this->as->defining_class,
        );
        if ($param->as === $this->as->as) {
            return $this;
        }
        return new static(
            $this->param_name,
            $this->defining_class,
            $param,
            $this->visibility_filter,
        );
    }
}
