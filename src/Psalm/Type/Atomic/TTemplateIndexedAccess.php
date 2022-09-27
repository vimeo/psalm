<?php

namespace Psalm\Type\Atomic;

use Psalm\Type\Atomic;

/**
 * @psalm-immutable
 */
final class TTemplateIndexedAccess extends Atomic
{
    /**
     * @var string
     */
    public $array_param_name;

    /**
     * @var string
     */
    public $offset_param_name;

    /**
     * @var string
     */
    public $defining_class;

    public function __construct(
        string $array_param_name,
        string $offset_param_name,
        string $defining_class,
        bool $from_docblock = false
    ) {
        $this->array_param_name = $array_param_name;
        $this->offset_param_name = $offset_param_name;
        $this->defining_class = $defining_class;
        $this->from_docblock = $from_docblock;
    }

    public function getKey(bool $include_extra = true): string
    {
        return $this->array_param_name . '[' . $this->offset_param_name . ']';
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
}
