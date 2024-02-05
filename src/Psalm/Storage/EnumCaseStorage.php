<?php

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

    /**
     * @var int|string|null|UnresolvedConstantComponent
     */
    public $value;

    /** @var CodeLocation */
    public $stmt_location;

    /**
     * @var bool
     */
    public $deprecated = false;

    /**
     * @param int|string|null|UnresolvedConstantComponent  $value
     */
    public function __construct(
        $value,
        CodeLocation $location
    ) {
        $this->value = $value;
        $this->stmt_location = $location;
    }

    /** @return int|string|null */
    public function getValue(ClassLikes $classlikes)
    {
        $case_value = $this->value;

        if ($case_value instanceof UnresolvedConstantComponent) {
            $case_value = ConstantTypeResolver::resolve(
                $classlikes,
                $case_value,
            );

            if ($case_value instanceof TLiteralString) {
                $case_value = $case_value->value;
            } elseif ($case_value instanceof TLiteralInt) {
                $case_value = $case_value->value;
            } else {
                throw new UnexpectedValueException('Failed to infer case value');
            }
        }

        return $case_value;
    }
}
