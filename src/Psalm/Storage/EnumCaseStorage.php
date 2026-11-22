<?php

declare(strict_types=1);

namespace Psalm\Storage;

use Override;
use Psalm\CodeLocation;
use Psalm\Exception\CircularReferenceException;
use Psalm\Internal\Codebase\ClassLikes;
use Psalm\Internal\Codebase\ConstantTypeResolver;
use Psalm\Internal\Scanner\UnresolvedConstantComponent;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;

/**
 * @psalm-immutable
 * @api
 */
final class EnumCaseStorage implements HasAttributesInterface
{
    use UnserializeMemoryUsageSuppressionTrait;

    /**
     * @param list<AttributeStorage> $attributes
     * @psalm-mutation-free
     */
    public function __construct(
        public TLiteralString|TLiteralInt|UnresolvedConstantComponent|null $value,
        public CodeLocation $stmt_location,
        public bool $deprecated = false,
        public array $attributes = [],
    ) {
    }

    public function getValue(ClassLikes $classlikes): TLiteralInt|TLiteralString|null
    {
        try {
            return $this->resolveValue($classlikes);
        } catch (CircularReferenceException) {
            return null;
        }
    }

    /**
     * @throws CircularReferenceException when the value refers back to this case
     */
    public function resolveValue(ClassLikes $classlikes): TLiteralInt|TLiteralString|null
    {
        $case_value = $this->value;

        if ($case_value instanceof UnresolvedConstantComponent) {
            $case_value = ConstantTypeResolver::resolve(
                $classlikes,
                $case_value,
            );

            // a constant that is undefined, from a file that is not scanned, or not an int or a string has no value
            if (!$case_value instanceof TLiteralString
                && !$case_value instanceof TLiteralInt
            ) {
                return null;
            }
        }

        return $case_value;
    }

    /**
     * @return list<AttributeStorage>
     * @psalm-mutation-free
     */
    #[Override]
    public function getAttributeStorages(): array
    {
        return $this->attributes;
    }
}
