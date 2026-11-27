<?php

declare(strict_types=1);

namespace Psalm\Tests\TypeReconciliation;

use Override;
use Psalm\Context;
use Psalm\IssueBuffer;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

final class InArrayTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    public function testEqualConditionsAreStillAlreadyAsserted(): void
    {
        $this->project_analyzer->getConfig()->throw_exception = false;
        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                function test(?string $s): void {
                    if (!$s && !$s) {
                        echo 1;
                    }
                }',
        );
        $this->analyzeFile($file_path, new Context());

        $messages = [];
        foreach (IssueBuffer::getIssuesDataForFile($file_path) as $issue) {
            if ($issue->type === 'RedundantCondition') {
                $messages[] = $issue->message;
            }
        }

        $this->assertContains('!$s has already been asserted', $messages);
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'stringCastOfAScalarFoundInAListOfWords' => [
                'code' => '<?php
                    /** @return string|int|null */
                    function value() {
                        return null;
                    }

                    $value = value();
                    $found = in_array((string) $value, ["ls", "cat"], true) ? $value : "none";',
                'assertions' => [
                    '$found===' => "'cat'|'ls'|'none'",
                ],
            ],
            'stringCastOfAScalarFoundInAListWithANumber' => [
                'code' => '<?php
                    /** @return string|int|null */
                    function value() {
                        return null;
                    }

                    $value = value();
                    $found = in_array((string) $value, ["ls", "1"], true) ? $value : "none";',
                'assertions' => [
                    '$found===' => 'int|null|string',
                ],
            ],
            'notInTwoDifferentArraysOfTheSameType' => [
                'code' => '<?php
                    class User {}

                    /**
                     * @param User[] $a
                     * @param User[] $b
                     */
                    function test(User $user, array $a, array $b): void {
                        if (!in_array($user, $a, true) && !in_array($user, $b, true)) {
                            throw new Exception();
                        }
                    }',
            ],
            'negatedInArrayOfLiteralListOrCall' => [
                'code' => '<?php
                    final class A {
                        public const NAMES = [\'a\', \'b\', \'c\', \'d\', \'e\', \'f\', \'g\', \'h\', \'i\', \'j\', \'k\', \'l\'];
                    }

                    function known(string $s): bool {
                        return $s !== \'\';
                    }

                    function getName(): string {
                        return \'\';
                    }

                    $name = getName();

                    if (!in_array($name, A::NAMES, true) || !known($name)) {
                        exit;
                    }',
                'assertions' => [
                    '$name===' => '\'a\'|\'b\'|\'c\'|\'d\'|\'e\'|\'f\'|\'g\'|\'h\'|\'i\'|\'j\'|\'k\'|\'l\'',
                ],
            ],
            'inTwoDifferentArraysOfTheSameType' => [
                'code' => '<?php
                    class User {}

                    /**
                     * @param User[] $a
                     * @param User[] $b
                     */
                    function test(User $user, array $a, array $b): bool {
                        return in_array($user, $a, true) || in_array($user, $b, true);
                    }',
            ],
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
            'inArrayNullNonStrictNarrowsArrayValueType' => [
                'code' => '<?php
                    /** @var array<string|null> $stringsAndNulls */
                    $stringsAndNulls = ["foo", "bar", null];

                    if (\in_array(null, $stringsAndNulls)) {
                        throw new \Exception("theres a null in the array");
                    }

                    foo($stringsAndNulls);

                    /** @param string[] $s */
                    function foo(array $s): void {
                        echo implode($s);
                    }',
                'assertions' => [
                    '$stringsAndNulls===' => 'array<array-key, non-empty-string>',
                ],
            ],
            'inArrayNullStrictNarrowsArrayValueType' => [
                'code' => '<?php
                    /**
                     * @param array<string|null> $arr
                     * @param list<string|null> $list
                     * @param non-empty-list<string|null> $nonEmptyList
                     * @param non-empty-array<int, string|null> $nonEmptyArr
                     */
                    function test(array $arr, array $list, array $nonEmptyList, array $nonEmptyArr): void {
                        if (in_array(null, $arr, true)) {
                            /** @psalm-check-type-exact $arr = non-empty-array<array-key, null|string> */
                            echo count($arr);
                            return;
                        }
                        /** @psalm-check-type-exact $arr = array<array-key, string> */

                        if (in_array(null, $list, true)) {
                            /** @psalm-check-type-exact $list = non-empty-list<null|string> */
                            echo count($list);
                            return;
                        }
                        /** @psalm-check-type-exact $list = list<string> */

                        if (!in_array(null, $nonEmptyList, true)) {
                            /** @psalm-check-type-exact $nonEmptyList = non-empty-list<string> */
                            echo count($nonEmptyList);
                        }

                        if (!in_array(null, $nonEmptyArr, true)) {
                            /** @psalm-check-type-exact $nonEmptyArr = non-empty-array<int, string> */
                            echo count($nonEmptyArr);
                        }
                    }',
            ],
            'inArrayBoolNeedlesNarrowArray' => [
                'code' => '<?php
                    /**
                     * @param array<string|false> $strictFalse
                     * @param array<string|false|null> $looseFalse
                     * @param list<bool> $strictTrue
                     * @param list<bool> $looseTrue
                     */
                    function test(array $strictFalse, array $looseFalse, array $strictTrue, array $looseTrue): void {
                        if (!in_array(false, $strictFalse, true)) {
                            /** @psalm-check-type-exact $strictFalse = array<array-key, string> */
                            echo count($strictFalse);
                        }

                        if (!in_array(false, $looseFalse)) {
                            /** @psalm-check-type-exact $looseFalse = array<array-key, non-falsy-string> */
                            echo count($looseFalse);
                        }

                        if (!in_array(true, $strictTrue, true)) {
                            /** @psalm-check-type-exact $strictTrue = list<false> */
                            echo count($strictTrue);
                        }

                        if (!in_array(true, $looseTrue)) {
                            /** @psalm-check-type-exact $looseTrue = list<false> */
                            echo count($looseTrue);
                        }
                    }',
            ],
            'inArrayTrueNonStrictKeepsEnumInArray' => [
                'code' => '<?php
                    enum Suit {
                        case Hearts;
                        case Spades;
                    }

                    /**
                     * @param list<Suit|bool> $items
                     * @param list<callable> $callbacks
                     * @param non-empty-list<callable|false> $nonEmptyCallbacks
                     * @param list<iterable> $iterables
                     */
                    function test(array $items, array $callbacks, array $nonEmptyCallbacks, array $iterables): void {
                        if (!in_array(true, $items)) {
                            /** @psalm-check-type-exact $items = list<false> */
                            echo count($items);
                        }

                        if (!in_array(true, $callbacks)) {
                            /** @psalm-check-type-exact $callbacks = array<never, never> */
                            echo count($callbacks);
                        }

                        if (!in_array(true, $nonEmptyCallbacks)) {
                            /** @psalm-check-type-exact $nonEmptyCallbacks = non-empty-list<false> */
                            echo count($nonEmptyCallbacks);
                        }

                        if (!in_array(true, $iterables)) {
                            /** @psalm-check-type-exact $iterables = list<iterable<mixed, mixed>> */
                            echo count($iterables);
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'inArrayNonStrictPositivePreservesLooseMatches' => [
                'code' => '<?php
                    /**
                     * @param list<0> $zeros
                     * @param list<1> $ones
                     * @param list<0.0> $zeroFloats
                     * @param list<SimpleXMLElement> $xmls
                     * @param list<object> $objects
                     * @param array{a: 0, b?: string} $shape
                     */
                    function test(
                        array $zeros,
                        array $ones,
                        array $zeroFloats,
                        array $xmls,
                        array $objects,
                        array $shape
                    ): void {
                        if (in_array("00", $zeros)) {
                            /** @psalm-check-type-exact $zeros = non-empty-list<0> */
                            echo count($zeros);
                        }

                        if (in_array("foo", $zeros)) {
                            /** @psalm-check-type-exact $zeros = non-empty-list<0> */
                            echo count($zeros);
                        }

                        if (in_array("1.0", $ones)) {
                            /** @psalm-check-type-exact $ones = non-empty-list<1> */
                            echo count($ones);
                        }

                        if (in_array("0.0", $zeroFloats)) {
                            /** @psalm-check-type-exact $zeroFloats = non-empty-list<0.0> */
                            echo count($zeroFloats);
                        }

                        if (in_array(0, $xmls)) {
                            /** @psalm-check-type-exact $xmls = non-empty-list<SimpleXMLElement> */
                            echo count($xmls);
                        }

                        if (in_array(0, $objects)) {
                            /** @psalm-check-type-exact $objects = non-empty-list<object> */
                            echo count($objects);
                        }

                        if (in_array("00", $shape)) {
                            /** @psalm-check-type-exact $shape = array{a: 0, b?: string} */
                            echo $shape["a"];
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
            'inArrayLiteralAndEnumCaseNarrowArray' => [
                'code' => '<?php
                    enum Suit {
                        case Hearts;
                        case Spades;
                    }

                    /**
                     * @param list<string> $strings
                     * @param list<"a"|"b"> $literals
                     * @param list<int<0, max>> $ints
                     * @param list<Suit> $suits
                     */
                    function test(array $strings, array $literals, array $ints, array $suits): void {
                        if (!in_array("", $strings, true)) {
                            /** @psalm-check-type-exact $strings = list<non-empty-string> */
                            echo count($strings);
                        }

                        if (!in_array("a", $literals, true)) {
                            /** @psalm-check-type-exact $literals = list<"b"> */
                            echo count($literals);
                        }

                        if (!in_array(0, $ints, true)) {
                            /** @psalm-check-type-exact $ints = list<int<1, max>> */
                            echo count($ints);
                        }

                        if (!in_array(Suit::Hearts, $suits, true)) {
                            /** @psalm-check-type-exact $suits = list<Suit::Spades> */
                            echo count($suits);
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'inArrayNarrowsArrayShapeAndEmptyResult' => [
                'code' => '<?php
                    /**
                     * @param array{a: string|null, b?: null} $shape
                     * @param array{a: string, b?: string|null} $shapePositive
                     * @param array<null> $onlyNulls
                     * @param list<null> $onlyNullList
                     * @param list<"0"|null> $zeroOrNull
                     */
                    function test(
                        array $shape,
                        array $shapePositive,
                        array $onlyNulls,
                        array $onlyNullList,
                        array $zeroOrNull,
                    ): void {
                        if (!in_array(null, $shape, true)) {
                            /** @psalm-check-type-exact $shape = array{a: string} */
                            echo $shape["a"];
                        }

                        if (in_array(null, $shapePositive, true)) {
                            /** @psalm-check-type-exact $shapePositive = array{a: string, b: null} */
                            echo $shapePositive["a"];
                        }

                        if (!in_array(null, $onlyNulls, true)) {
                            /** @psalm-check-type-exact $onlyNulls = array<never, never> */
                            echo count($onlyNulls);
                        }

                        if (!in_array(null, $onlyNullList, true)) {
                            /** @psalm-check-type-exact $onlyNullList = array<never, never> */
                            echo count($onlyNullList);
                        }

                        if (!in_array(null, $zeroOrNull)) {
                            /** @psalm-check-type-exact $zeroOrNull = list<"0"> */
                            echo count($zeroOrNull);
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'inArrayCompoundNamedArgsAndVariableNeedle' => [
                'code' => '<?php
                    /**
                     * @param list<string|false|null> $a
                     * @param list<string|null> $b
                     * @param list<string|null> $c
                     */
                    function test(array $a, array $b, array $c): void {
                        if (in_array(null, $a, true) || in_array(false, $a, true)) {
                            return;
                        }
                        /** @psalm-check-type-exact $a = list<string> */

                        if (!in_array(haystack: $b, needle: null, strict: true)) {
                            /** @psalm-check-type-exact $b = list<string> */
                            echo count($b);
                        }

                        $needle = null;
                        if (!in_array($needle, $c, true)) {
                            /** @psalm-check-type-exact $c = list<string> */
                            echo count($c);
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'inArrayDoesNotOverNarrowWhenNeedleNotSingletonOrStrictUnknown' => [
                'code' => '<?php
                    /**
                     * @param list<string|null> $a
                     * @param list<string|null> $b
                     * @param list<float> $c
                     */
                    function test(array $a, array $b, array $c, bool $strict, ?string $needle): void {
                        if (!in_array(null, $a, $strict)) {
                            /** @psalm-check-type-exact $a = list<null|string> */
                            echo count($a);
                        }

                        if (!in_array($needle, $b, true)) {
                            /** @psalm-check-type-exact $b = list<null|string> */
                            echo count($b);
                        }

                        if (!in_array(NAN, $c, true)) {
                            /** @psalm-check-type-exact $c = list<float> */
                            echo count($c);
                        }
                    }',
            ],
            'inArrayMultiLiteralNeedleDoesNotNarrowHaystack' => [
                'code' => '<?php
                    /**
                     * @param \'a\'|\'b\' $x
                     * @param list<\'a\'|\'b\'> $arr
                     */
                    function f(string $x, array $arr): void {
                        if (!in_array($x, $arr, true)) {
                            /** @psalm-check-type-exact $arr = list<\'a\'|\'b\'> */
                            echo count($arr);
                        }
                    }',
            ],
        ];
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerInvalidCodeParse(): iterable
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
            'inArrayNullStrictDoesNotExcludeEmptyString' => [
                'code' => '<?php
                    /** @param list<non-empty-string> $s */
                    function takesNonEmptyStrings(array $s): void {}

                    /** @param list<string|null> $arr */
                    function test(array $arr): void {
                        if (in_array(null, $arr, true)) {
                            throw new \Exception();
                        }

                        takesNonEmptyStrings($arr);
                    }',
                'error_message' => 'ArgumentTypeCoercion',
            ],
            'inArrayMultiLiteralIntNeedleChecksConstantList' => [
                'code' => '<?php
                    class LoadChecker {
                        private const AVAILABLE = [0, 1, 2];

                        /** @param 0|1|2 $load */
                        public function check(int $load): void {
                            if (!in_array($load, self::AVAILABLE, true)) {
                                throw new \InvalidArgumentException();
                            }
                        }
                    }',
                'error_message' => 'DocblockTypeContradiction',
            ],
        ];
    }
}
