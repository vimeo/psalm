<?php

namespace Psalm\Type\Atomic;

use Psalm\Type\Atomic;

/**
 * Represents an offset of an array.
 *
 * @psalm-type ArrayLikeTemplateType = TClassConstant|TKeyedArray|TList|TArray
 */
class TKeyOfArray extends TArrayKey
{
    /** @var ArrayLikeTemplateType */
    public $type;

    /**
     * @param ArrayLikeTemplateType $type
     */
    public function __construct(Atomic $type)
    {
        $this->type = $type;
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'key-of<' . $this->type . '>';
    }

    /**
     * @param  array<lowercase-string, string> $aliased_classes
     */
    public function toPhpString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        int $analysis_php_version_id
    ): ?string {
        return null;
    }

    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }

    public function getAssertionString(): string
    {
        return 'mixed';
    }

    /**
     * @psalm-assert-if-true ArrayLikeTemplateType $template_type
     */
    public static function isViableTemplateType(Atomic $template_type): bool
    {
        return $template_type instanceof TArray
            || $template_type instanceof TClassConstant
            || $template_type instanceof TKeyedArray
            || $template_type instanceof TList;
    }
}
