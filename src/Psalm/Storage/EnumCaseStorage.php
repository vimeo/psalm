<?php

declare(strict_types=1);

namespace Psalm\Storage;

use Psalm\CodeLocation;
use Psalm\Internal\Codebase\ClassLikes;
use Psalm\Internal\Codebase\ConstantTypeResolver;
use Psalm\Internal\Scanner\UnresolvedConstantComponent;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;

/**
 * @psalm-immutable
 * @api
 */
final class EnumCaseStorage
{
    use UnserializeMemoryUsageSuppressionTrait;

    /**
     * @psalm-mutation-free
     */
    public function __construct(
        public TLiteralString|TLiteralInt|UnresolvedConstantComponent|null $value,
        public CodeLocation $stmt_location,
        public bool $deprecated = false,
    ) {
    }

    public function getValue(ClassLikes $classlikes): TLiteralInt|TLiteralString|null
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
}
