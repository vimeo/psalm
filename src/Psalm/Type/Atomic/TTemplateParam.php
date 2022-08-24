<?php

namespace Psalm\Type\Atomic;

use Psalm\Codebase;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Type\Atomic;
use Psalm\Type\Union;

use function array_map;
use function implode;

/**
 * denotes a template parameter that has been previously specified in a `@template` tag.
 */
final class TTemplateParam extends Atomic
{
    use HasIntersectionTrait;

    /**
     * @var string
     */
    public $param_name;

    /**
     * @var Union
     */
    public $as;

    /**
     * @var string
     */
    public $defining_class;

    /**
     * @param array<string, TNamedObject|TTemplateParam|TIterable|TObjectWithProperties>|null $extra_types
     */
    public function __construct(string $param_name, Union $extends, string $defining_class, ?array $extra_types = null)
    {
        $this->param_name = $param_name;
        $this->as = $extends;
        $this->defining_class = $defining_class;
        $this->extra_types = $extra_types;
    }

    public function replaceAs(Union $as): static
    {
        return new static(
            $this->param_name,
            $as,
            $this->defining_class,
            $this->extra_types
        );
    }

    public function getKey(bool $include_extra = true): string
    {
        if ($include_extra && $this->extra_types) {
            return $this->param_name . ':' . $this->defining_class . '&' . implode('&', $this->extra_types);
        }

        return $this->param_name . ':' . $this->defining_class;
    }

    public function getAssertionString(): string
    {
        return $this->as->getId();
    }

    public function getId(bool $exact = true, bool $nested = false): string
    {
        if (!$exact) {
            return $this->param_name;
        }

        if ($this->extra_types) {
            return '(' . $this->param_name . ':' . $this->defining_class . ' as ' . $this->as->getId($exact)
                . ')&' . implode('&', array_map(static fn(Atomic $type): string
                    => $type->getId($exact, true), $this->extra_types));
        }

        return ($nested ? '(' : '') . $this->param_name
            . ':' . $this->defining_class
            . ' as ' . $this->as->getId($exact) . ($nested ? ')' : '');
    }

    /**
     * @param  array<lowercase-string, string> $aliased_classes
     *
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
     *
     */
    public function toNamespacedString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        bool $use_phpdoc_format
    ): string {
        if ($use_phpdoc_format) {
            return $this->as->toNamespacedString(
                $namespace,
                $aliased_classes,
                $this_class,
                true
            );
        }

        $intersection_types = $this->getNamespacedIntersectionTypes(
            $namespace,
            $aliased_classes,
            $this_class,
            false
        );

        return $this->param_name . $intersection_types;
    }

    public function getChildNodes(): array
    {
        return [$this->as];
    }

    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }

    public function replaceClassLike(string $old, string $new): static
    {
        return new static(
            $this->param_name,
            $this->as->getBuilder()->replaceClassLike($old, $new)->freeze(),
            $this->defining_class,
            $this->replaceIntersectionClassLike($old, $new)
        );
    }

    public function replaceTemplateTypesWithArgTypes(
        TemplateResult $template_result,
        ?Codebase $codebase
    ): static {
        return new static(
            $this->param_name,
            $this->as,
            $this->defining_class,
            $this->replaceIntersectionTemplateTypesWithArgTypes($template_result, $codebase)
        );
    }
}
