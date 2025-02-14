<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;
use Psalm\Codebase;
use Psalm\Storage\EnumCaseStorage;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;
use Psalm\Type\Atomic;
use Psalm\Type\Union;

use function array_map;
use function array_values;
use function assert;

/**
 * Represents a value of an array or enum.
 *
 * @psalm-immutable
 */
final class TValueOf extends Atomic
{
    use UnserializeMemoryUsageSuppressionTrait;
    public function __construct(public Union $type, bool $from_docblock = false)
    {
        parent::__construct($from_docblock);
    }

    /**
     * @param non-empty-array<string,EnumCaseStorage> $cases
     */
    private static function getValueTypeForNamedObject(
        array $cases,
        TNamedObject $atomic_type,
        Codebase $codebase,
    ): Union {
        if ($atomic_type instanceof TEnumCase) {
            assert(isset($cases[$atomic_type->case_name]), 'Should\'ve been verified in TValueOf#getValueType');
            $value = $cases[$atomic_type->case_name]->getValue($codebase->classlikes);
            assert($value !== null, 'Backed enum must have a value.');

            return new Union([$value]);
        }

        return new Union(array_map(
            static function (EnumCaseStorage $case) use ($codebase): Atomic {
                $case_value = $case->getValue($codebase->classlikes);
                // Backed enum must have a value
                assert($case_value !== null);
                return $case_value;
            },
            array_values($cases),
        ));
    }

    #[Override]
    protected function getChildNodeKeys(): array
    {
        return ['type'];
    }


    #[Override]
    public function getKey(bool $include_extra = true): string
    {
        return 'value-of<' . $this->type . '>';
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
                && !$type instanceof TNamedObject
            ) {
                return false;
            }
        }
        return true;
    }

    public static function getValueType(
        Union $type,
        Codebase $codebase,
        bool $keep_template_params = false,
    ): ?Union {
        $value_types = [];

        foreach ($type->getAtomicTypes() as $atomic_type) {
            if ($atomic_type instanceof TArray) {
                $value_atomics = $atomic_type->type_params[1];
            } elseif ($atomic_type instanceof TKeyedArray) {
                $value_atomics = $atomic_type->getGenericValueType();
            } elseif ($atomic_type instanceof TTemplateParam) {
                if ($keep_template_params) {
                    $value_atomics = new Union([$atomic_type]);
                } else {
                    $value_atomics = static::getValueType(
                        $atomic_type->as,
                        $codebase,
                        $keep_template_params,
                    );
                    if ($value_atomics === null) {
                        continue;
                    }
                }
            } elseif ($atomic_type instanceof TNamedObject
                && $codebase->classlike_storage_provider->has($atomic_type->value)
            ) {
                $class_storage = $codebase->classlike_storage_provider->get($atomic_type->value);
                $cases = $class_storage->enum_cases;
                if (!$class_storage->is_enum
                    || $class_storage->enum_type === null
                    || $cases === []
                    || ($atomic_type instanceof TEnumCase && !isset($cases[$atomic_type->case_name]))
                ) {
                    // Invalid value-of, skip
                    continue;
                }

                $value_atomics = self::getValueTypeForNamedObject($cases, $atomic_type, $codebase);
            } else {
                continue;
            }

            $value_types = [...$value_types, ...array_values($value_atomics->getAtomicTypes())];
        }

        if ($value_types === []) {
            return null;
        }
        return new Union($value_types);
    }
}
