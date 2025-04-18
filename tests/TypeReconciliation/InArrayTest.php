<?php

declare(strict_types=1);

namespace Psalm\Tests\TypeReconciliation;

use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

class InArrayTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        return [
            'nullTypeRemovedAfterNegatedAssertionAgainstArrayOfInt' => [
                'code' => '<?php
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
                'code' => '<?php
                    /**
                     * @param int|null $x
                     * @param non-empty-list<int> $y
                     * @return int
                     */
                    function assertInArray($x, $y) {
                        if (in_array($x, $y, true)) {
                            return $x;
                        }

                        throw new \Exception();
                    }
                ',
            ],
            'typeNotChangedAfterAssertionAgainstArrayOfMixed' => [
                'code' => '<?php
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
                'code' => '<?php
                    /**
                     * @param int|string|bool $x
                     * @param non-empty-list<int|string> $y
                     * @return int|string
                     */
                    function assertInArray($x, $y) {
                        if (in_array($x, $y, true)) {
                            return $x;
                        }
                        throw new \Exception();
                    }
                ',
            ],
            'unionTypesReducedToIntersectionWithinAssertion' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'ignored_issues' => ['RedundantConditionGivenDocblockType', 'DocblockTypeContradiction'],
            ],
            'assertNegatedInArrayOfNotIntersectingTypeReturnsOriginalType' => [
                'code' => '<?php
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
                'ignored_issues' => ['RedundantConditionGivenDocblockType'],
            ],
            'assertAgainstListOfLiteralsAndScalarUnion' => [
                'code' => '<?php
                    /**
                     * @param string|bool $x
                     * @param non-empty-list<"a"|"b"|int> $y
                     * @return "a"|"b"
                     */
                    function assertInArray($x, $y) {
                        if (in_array($x, $y, true)) {
                            return $x;
                        }

                        throw new Exception();
                    }',
            ],
            'assertAgainstListOfLiteralsAndScalarUnionTypeHint' => [
                'code' => '<?php
                    /**
                     * @param non-empty-list<"a"|"b"|int> $y
                     * @return "a"|"b"
                     */
                    function assertInArray(string|bool $x, $y) {
                        if (in_array($x, $y, true)) {
                            return $x;
                        }

                        throw new Exception();
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'in_arrayNullOrString' => [
                'code' => '<?php
                    function test(?string $x, string $y): void {
                        if (in_array($x, [null, $y], true)) {
                            if ($x === null) {
                                echo "Saw null\n";
                            }
                            echo "Saw $x\n";
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'in_array-mixed-twice' => [
                'code' => '<?php
                    function contains(array $list1, array $list2, mixed $element): void
                    {
                        if (in_array($element, $list1, true)) {
                        } elseif (in_array($element, $list2, true)) {
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'in_array-string-twice' => [
                'code' => '<?php
                    /**
                     * @param string[] $list1
                     * @param string[] $list2
                     */
                    function contains(array $list1, array $list2, string $element): void
                    {
                        if (in_array($element, $list1, true)) {
                        } elseif (in_array($element, $list2, true)) {
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'in_array-keyed-array-string-twice' => [
                'code' => '<?php
                    function contains(string $a, string $b, mixed $element): void
                    {
                        if (in_array($element, [$a], true)) {
                        } elseif (in_array($element, [$b], true)) {
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'typeNotChangedAfterNegatedAssertionAgainstUnsealedArrayOfMixed' => [
                'code' => '<?php
                    /**
                     * @param int|null $x
                     * @param non-empty-list<mixed> $y
                     * @return int
                     */
                    function assertInArray($x, $y) {
                        if (!in_array($x, $y, true)) {
                            return $x;
                        }
                        throw new \Exception();
                    }
                ',
                'error_message' => 'NullableReturnStatement',
            ],
            'typeNotChangedAfterNegatedAssertionAgainstUnsealedArrayOfUnionType' => [
                'code' => '<?php
                    /**
                     * @param int|null $x
                     * @param non-empty-list<int|null> $y
                     * @return int
                     */
                    function assertInArray($x, $y) {
                        if (!in_array($x, $y, true)) {
                            return $x;
                        }
                        throw new \Exception();
                    }
                ',
                'error_message' => 'NullableReturnStatement',
            ],
            'initialTypeRemainsOutsideOfAssertion' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'error_message' => 'DocblockTypeContradiction - src' . DIRECTORY_SEPARATOR . 'somefile.php:8:29 - Operand of type false is always falsy',
            ],
            'assertNegatedInArrayOfNotIntersectingTypeTriggersRedundantCondition' => [
                'code' => '<?php
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
                'error_message' => 'RedundantConditionGivenDocblockType - src' . DIRECTORY_SEPARATOR . 'somefile.php:8:29 - Operand of type true is always truthy',
            ],
            'assertInArrayOfNotIntersectingTypeTriggersDocblockTypeContradiction' => [
                'code' => '<?php
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
                'error_message' => 'DocblockTypeContradiction - src' . DIRECTORY_SEPARATOR . 'somefile.php:8:29 - Operand of type false is always falsy',
            ],
            'assertInArrayOfNotIntersectingTypeReturnsTriggersDocblockTypeContradiction' => [
                'code' => '<?php
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
                'error_message' => 'DocblockTypeContradiction - src' . DIRECTORY_SEPARATOR . 'somefile.php:8:29 - Operand of type false is always falsy',
                'ignored_issues' => ['RedundantConditionGivenDocblockType'],
            ],
            'inArrayDetectType' => [
                'code' => '<?php
                    function x($foo, string $bar): void {
                        if (!in_array($foo, [$bar], true)) {
                            throw new Exception();
                        }

                        if (is_string($foo)) {}
                    }',
                // foo is always string
                'error_message' => 'RedundantCondition',
            ],
            'inArrayRemoveInvalid' => [
                'code' => '<?php
                    function x(?string $foo, int $bar): void {
                        if (!in_array($foo, [$bar], true)) {
                            throw new Exception();
                        }
                    }',
                // Type null|string is never int
                'error_message' => 'RedundantCondition',
            ],
        ];
    }
}
