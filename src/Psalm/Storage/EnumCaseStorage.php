<?php

declare(strict_types=1);

namespace Psalm\Storage;

use Psalm\CodeLocation;
use Psalm\Internal\Codebase\ClassLikes;
use Psalm\Internal\Codebase\ConstantTypeResolver;
use Psalm\Internal\Scanner\UnresolvedConstantComponent;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use UnexpectedValueException;

final class EnumCaseStorage
{
    use UnserializeMemoryUsageSuppressionTrait;

    public bool $deprecated = false;

    public function __construct(
        public TLiteralString|TLiteralInt|UnresolvedConstantComponent|null $value,
        public CodeLocation $stmt_location,
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

            if (!$case_value instanceof TLiteralString
                && !$case_value instanceof TLiteralInt
            ) {
                throw new UnexpectedValueException('Failed to infer case value');
            }
        }

        return $case_value;
    }
}
