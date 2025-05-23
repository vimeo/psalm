<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;
use Psalm\Type\Union;

use function array_merge;
use function array_values;

/**
 * Represents an offset of an array.
 *
 * @psalm-immutable
 */
final class TKeyOf extends TArrayKey
{
    public function __construct(public Union $type, bool $from_docblock = false)
    {
        parent::__construct($from_docblock);
    }

    #[Override]
    public function getKey(bool $include_extra = true): string
    {
        return 'key-of<' . $this->type . '>';
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

    #[Override]
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
                && !$type instanceof TPropertiesOf
            ) {
                return false;
            }
        }
        return true;
    }

    public static function getArrayKeyType(
        Union $type,
        bool $keep_template_params = false,
    ): ?Union {
        $key_types = [];

        foreach ($type->getAtomicTypes() as $atomic_type) {
            if ($atomic_type instanceof TArray) {
                $array_key_atomics = $atomic_type->type_params[0];
            } elseif ($atomic_type instanceof TKeyedArray) {
                $array_key_atomics = $atomic_type->getGenericKeyType();
            } elseif ($atomic_type instanceof TTemplateParam) {
                if ($keep_template_params) {
                    $array_key_atomics = new Union([$atomic_type]);
                } else {
                    $array_key_atomics = static::getArrayKeyType(
                        $atomic_type->as,
                        $keep_template_params,
                    );
                    if ($array_key_atomics === null) {
                        continue;
                    }
                }
            } else {
                continue;
            }

            $key_types = array_merge(
                $key_types,
                array_values($array_key_atomics->getAtomicTypes()),
            );
        }

        if ($key_types === []) {
            return null;
        }
        return new Union($key_types);
    }
}
