<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;
use Psalm\Codebase;
use Psalm\Internal\Type\TemplateInferredTypeReplacer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;
use Psalm\Type\Atomic;
use Psalm\Type\Union;

/**
 * Represents the type used when using TValueOf when the type of the array or enum is a template
 *
 * @psalm-immutable
 */
final class TTemplateValueOf extends Atomic
{
    use UnserializeMemoryUsageSuppressionTrait;
    public function __construct(
        public string $param_name,
        public string $defining_class,
        public Union $as,
        bool $from_docblock = false,
    ) {
        parent::__construct($from_docblock);
    }

    #[Override]
    public function getKey(bool $include_extra = true): string
    {
        return 'value-of<' . $this->param_name . '>';
    }

    #[Override]
    public function getId(bool $exact = true, bool $nested = false): string
    {
        if (!$exact) {
            return 'value-of<' . $this->param_name . '>';
        }

        return 'value-of<' . $this->as->getId($exact) . '>';
    }

    /**
     * @param array<lowercase-string, string> $aliased_classes
     */
    #[Override]
    public function toNamespacedString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        bool $use_phpdoc_format,
    ): string {
        return 'value-of<' . $this->param_name . '>';
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
    ): ?string {
        return null;
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
    public function replaceTemplateTypesWithArgTypes(
        TemplateResult $template_result,
        ?Codebase $codebase,
    ): self {
        $as = TemplateInferredTypeReplacer::replace(
            $this->as,
            $template_result,
            $codebase,
        );
        if ($as === $this->as) {
            return $this;
        }
        return new static(
            $this->param_name,
            $this->defining_class,
            $as,
        );
    }
}
