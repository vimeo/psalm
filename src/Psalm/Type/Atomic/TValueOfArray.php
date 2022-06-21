<?php

namespace Psalm\Type\Atomic;

use Psalm\Type\Atomic;
use Psalm\Type\Union;

use function array_merge;
use function array_values;

/**
 * Represents a value of an array.
 */
final class TValueOfArray extends Atomic
{
    /** @var Union */
    public $type;

    public function __construct(Union $type)
    {
        $this->type = $type;
    }

    public function __clone()
    {
        $this->type = clone $this->type;
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'value-of<' . $this->type . '>';
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

    public static function isViableTemplateType(Union $template_type): bool
    {
        foreach ($template_type->getAtomicTypes() as $type) {
            if (!$type instanceof TArray
                && !$type instanceof TClassConstant
                && !$type instanceof TKeyedArray
                && !$type instanceof TList
                && !$type instanceof TPropertiesOf
            ) {
                return false;
            }
        }
        return true;
    }

    public static function getArrayValueType(
        Union $type,
        bool $keep_template_params = false
    ): ?Union {
        $value_types = [];

        foreach ($type->getAtomicTypes() as $atomic_type) {
            if ($atomic_type instanceof TArray) {
                $array_value_atomics = $atomic_type->type_params[1];
            } elseif ($atomic_type instanceof TList) {
                $array_value_atomics = $atomic_type->type_param;
            } elseif ($atomic_type instanceof TKeyedArray) {
                $array_value_atomics = $atomic_type->getGenericValueType();
            } elseif ($atomic_type instanceof TTemplateParam) {
                if ($keep_template_params) {
                    $array_value_atomics = new Union([$atomic_type]);
                } else {
                    $array_value_atomics = static::getArrayValueType(
                        $atomic_type->as,
                        $keep_template_params
                    );
                    if ($array_value_atomics === null) {
                        continue;
                    }
                }
            } else {
                continue;
            }

            $value_types = array_merge(
                $value_types,
                array_values($array_value_atomics->getAtomicTypes())
            );
        }

        if ($value_types === []) {
            return null;
        }
        return new Union($value_types);
    }
}
