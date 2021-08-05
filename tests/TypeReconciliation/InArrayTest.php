<?php
namespace Psalm\Tests\TypeReconciliation;

use const DIRECTORY_SEPARATOR;

class InArrayTest extends \Psalm\Tests\TestCase
{
    use \Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;
    use \Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'nullTypeRemovedAfterNegatedAssertionAgainstArrayOfInt' => [
                '<?php
                    /**
                     * @param int|null $x
                     * @return int
                     */
                    function assertInArray($x) {
                        if (!in_array($x, range(0, 5), true)) {
                            throw new \Exception();
                        }

                        return $x;
                    }',
            ],
            'nullTypeRemovedAfterAssertionAgainstArrayOfInt' => [
                '<?php
                    /**
                     * @param int|null $x
                     * @param non-empty-list<int> $y
                     * @return int|null
                     */
                    function assertInArray($x, $y) {
                        if (in_array($x, $y, true)) {
                            acceptInt($x);
                        }

                        return $x;
                    }
                    /**
                     * @param int $x
                     */
                    function acceptInt($x): void {
                    }',
            ],
            'typeNotChangedAfterAssertionAgainstArrayOfMixed' => [
                '<?php
                    /**
                     * @param int|null $x
                     * @param list<mixed> $y
                     * @return int|null
                     */
                    function assertInArray($x, $y) {
                        if (!in_array($x, $y, true)) {
                            throw new \Exception();
                        }

                        return $x;
                    }',
            ],
            'unionTypeReconciledToUnionTypeOfHaystackValueTypes' => [
                '<?php
                    /**
                     * @param int|string|bool $x
                     * @param non-empty-list<int|string> $y
                     * @return int|string|bool
                     */
                    function assertInArray($x, $y) {
                        if (in_array($x, $y, true)) {
                            acceptIntAndStr($x);
                        }

                        return $x;
                    }
                    /**
                     * @param int|string $x
                     */
                    function acceptIntAndStr($x): void {
                    }',
            ],
            'unionTypesReducedToIntersectionWithinAssertion' => [
                '<?php
                    /**
                     * @param int|bool $x
                     * @param non-empty-list<int|string> $y
                     * @return int
                     */
                    function assertInArray($x, $y) {
                        if (in_array($x, $y, true)) {
                            return $x;
                        }

                        throw new Exception();
                    }',
            ],
            'unionTypesReducedToIntersectionOutsideOfNegatedAssertion' => [
                '<?php
                    /**
                     * @param int|bool $x
                     * @param non-empty-list<int|string> $y
                     * @return int
                     */
                    function assertInArray($x, $y) {
                        if (!in_array($x, $y, true)) {
                            throw new Exception();
                        }
                        return $x;
                    }',
            ],
            'assertInArrayOfNotIntersectingTypeReturnsOriginalTypeOutsideOfAssertion' => [
                '<?php
                    /**
                     * @param int $x
                     * @param list<string> $y
                     * @return int
                     */
                    function assertInArray($x, $y) {
                        if (in_array($x, $y, true)) {
                            throw new \Exception();
                        }

                        return $x;
                    }',
                'assertions' => [],
                'error_level' => ['RedundantConditionGivenDocblockType'],
            ],
            'assertNegatedInArrayOfNotIntersectingTypeReturnsOriginalType' => [
                '<?php
                    /**
                     * @param int $x
                     * @param list<string> $y
                     * @return int
                     */
                    function assertInArray($x, $y) {
                        if (!in_array($x, $y, true)) {
                            return $x;
                        }

                        throw new \Exception();
                    }',
                'assertions' => [],
                'error_level' => ['RedundantConditionGivenDocblockType'],
            ],
            'assertAgainstListOfLiteralsAndScalarUnion' => [
                '<?php
                    /**
                     * @param string|bool $x
                     * @param non-empty-list<\'a\'|\'b\'|int> $y
                     * @return \'a\'|\'b\'
                     */
                    function assertInArray($x, $y) {
                        if (in_array($x, $y, true)) {
                            return $x;
                        }

                        throw new Exception();
                    }',
            ],
            'assertAgainstListOfLiteralsAndScalarUnionTypeHint' => [
                '<?php
                    /**
                     * @param non-empty-list<\'a\'|\'b\'|int> $y
                     * @return \'a\'|\'b\'
                     */
                    function assertInArray(string|bool $x, $y) {
                        if (in_array($x, $y, true)) {
                            return $x;
                        }

                        throw new Exception();
                    }',
                [],
                [],
                '8.0'
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,1?:string[],2?:bool,3?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'typeNotChangedAfterNegatedAssertionAgainstUnsealedArrayOfMixed' => [
                '<?php
                    /**
                     * @param int|null $x
                     * @param non-empty-list<mixed> $y
                     * @return int|null
                     */
                    function assertInArray($x, $y) {
                        if (!in_array($x, $y, true)) {
                            acceptInt($x);
                        }

                        return $x;
                    }
                    /**
                     * @param int $x
                     */
                    function acceptInt($x): void {
                    }',
                'error_message' => 'PossiblyNullArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:9:39 - Argument 1 of acceptInt cannot be null, possibly null value provided',
            ],
            'typeNotChangedAfterNegatedAssertionAgainstUnsealedArrayOfUnionType' => [
                '<?php
                    /**
                     * @param int|null $x
                     * @param non-empty-list<int|null> $y
                     * @return int|null
                     */
                    function assertInArray($x, $y) {
                        if (!in_array($x, $y, true)) {
                            acceptInt($x);
                        }

                        return $x;
                    }
                    /**
                     * @param int $x
                     */
                    function acceptInt($x): void {
                    }',
                'error_message' => 'PossiblyNullArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:9:39 - Argument 1 of acceptInt cannot be null, possibly null value provided',
            ],
            'initialTypeRemainsOutsideOfAssertion' => [
                '<?php
                    /**
                     * @param int|bool $x
                     * @param non-empty-list<int|string> $y
                     * @return int
                     */
                    function assertInArray($x, $y) {
                        if (in_array($x, $y, true)) {
                            throw new Exception();
                        }
                        return $x;
                    }',
                'error_message' => 'InvalidReturnStatement - src' . DIRECTORY_SEPARATOR . 'somefile.php:11:32 - The inferred type \'bool|int\' does not match the declared return type \'int\' for assertInArray',
            ],
            'initialTypeRemainsWithinTheNegatedAssertion' => [
                '<?php
                    /**
                     * @param int|bool $x
                     * @param non-empty-list<int|string> $y
                     * @return int
                     */
                    function assertInArray($x, $y) {
                        if (!in_array($x, $y, true)) {
                            return $x;
                        }
                        throw new Exception();
                    }',
                'error_message' => 'InvalidReturnStatement - src' . DIRECTORY_SEPARATOR . 'somefile.php:9:36 - The inferred type \'bool|int\' does not match the declared return type \'int\' for assertInArray',
            ],
            'assertInArrayOfNotIntersectingTypeTriggersTypeContradiction' => [
                '<?php
                    /**
                     * @param int $x
                     * @param list<string> $y
                     * @return int
                     */
                    function assertInArray($x, $y) {
                        if (in_array($x, $y, true)) {
                            throw new \Exception();
                        }

                        return $x;
                    }',
                'error_message' => 'RedundantConditionGivenDocblockType - src' . DIRECTORY_SEPARATOR . 'somefile.php:8:29 - Docblock-defined type int for $x is never string',
            ],
            'assertNegatedInArrayOfNotIntersectingTypeTriggersRedundantCondition' => [
                '<?php
                    /**
                     * @param int $x
                     * @param list<string> $y
                     * @return int
                     */
                    function assertInArray($x, $y) {
                        if (!in_array($x, $y, true)) {
                            return $x;
                        }

                        throw new \Exception();
                    }',
                'error_message' => 'RedundantConditionGivenDocblockType - src' . DIRECTORY_SEPARATOR . 'somefile.php:8:30 - Docblock-defined type int for $x is never string',
            ],
            'assertInArrayOfNotIntersectingTypeTriggersRedundantCondition' => [
                '<?php
                    /**
                     * @param int $x
                     * @param list<string> $y
                     * @return string
                     */
                    function assertInArray($x, $y) {
                        if (in_array($x, $y, true)) {
                            return $x;
                        }

                        throw new \Exception();
                    }',
                'error_message' => 'RedundantConditionGivenDocblockType - src' . DIRECTORY_SEPARATOR . 'somefile.php:8:29 - Docblock-defined type int for $x is never string',
            ],
            'assertInArrayOfNotIntersectingTypeReturnsTriggersMixedReturnStatement' => [
                '<?php
                    /**
                     * @param int $x
                     * @param list<string> $y
                     * @return string
                     */
                    function assertInArray($x, $y) {
                        if (in_array($x, $y, true)) {
                            return $x;
                        }

                        throw new \Exception();
                    }',
                'error_message' => 'MixedReturnStatement - src' . DIRECTORY_SEPARATOR . 'somefile.php:9:36 - Could not infer a return type',
                'error_levels' => ['RedundantConditionGivenDocblockType'],
            ],
            'assertNegatedInArrayOfNotIntersectingTypeTriggersTypeContradiction' => [
                '<?php
                    /**
                     * @param int $x
                     * @param list<string> $y
                     * @return string
                     */
                    function assertInArray($x, $y) {
                        if (!in_array($x, $y, true)) {
                            throw new \Exception();
                        }

                        return $x;
                    }',
                'error_message' => 'RedundantConditionGivenDocblockType - src' . DIRECTORY_SEPARATOR . 'somefile.php:8:30 - Docblock-defined type int for $x is never string',
            ],
            'assertNegatedInArrayOfNotIntersectingTypeTriggersMixedReturnStatement' => [
                '<?php
                    /**
                     * @param int $x
                     * @param list<string> $y
                     * @return string
                     */
                    function assertInArray($x, $y) {
                        if (!in_array($x, $y, true)) {
                            throw new \Exception();
                        }

                        return $x;
                    }',
                'error_message' => 'MixedReturnStatement - src' . DIRECTORY_SEPARATOR . 'somefile.php:12:32 - Could not infer a return type',
                'error_level' => ['RedundantConditionGivenDocblockType'],
            ],
        ];
    }
}
