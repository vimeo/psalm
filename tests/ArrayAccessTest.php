<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Config;
use Psalm\Context;
use Psalm\Exception\CodeException;
use Psalm\IssueBuffer;
use Psalm\Tests\Traits\InvalidCodeAnalysisCasesTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use function array_filter;
use function array_keys;
use function array_merge;
use function array_values;
use function explode;
use function ksort;
use function sort;
use function str_contains;

use const DIRECTORY_SEPARATOR;
use const PHP_VERSION_ID;

final class ArrayAccessTest extends TestCase
{
    // The taint tests call testValidCode(), which is not a test of this class: the cases are run by
    // ArrayAccessCases1Test to ArrayAccessCases<SHARDS>Test (see ProviderShard).
    use InvalidCodeAnalysisCasesTrait;
    use ValidCodeAnalysisTestTrait {
        testValidCode as private;
    }

    public const SHARDS = 2;

    public function testEmptyBranchOffsetMayBeUndefined(): void
    {
        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('PossiblyUndefinedStringArrayOffset - somefile.php:8');

        Config::getInstance()->ensure_array_string_offsets_exist = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @param array<string, int> $arr
                 * @psalm-suppress RiskyTruthyFalsyComparison
                 */
                function f(array $arr): void {
                    if (empty($arr["x"])) {
                        echo $arr["x"];
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testEnsureArrayOffsetsExist(): void
    {
        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('PossiblyUndefinedStringArrayOffset');

        Config::getInstance()->ensure_array_string_offsets_exist = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /** @psalm-mutation-free */
                function takesString(string $s): void {}

                /**
                 * @param array<string, string> $arr
                 * @psalm-mutation-free
                 */
                function takesArrayIteratorOfString(array $arr): void {
                    echo $arr["hello"];
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testEnsureArrayOffsetsExistWithIssetCheck(): void
    {
        Config::getInstance()->ensure_array_string_offsets_exist = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /** @psalm-mutation-free */
                function takesString(string $s): void {}

                /**
                 * @param array<string, string> $arr
                 */
                function takesArrayIteratorOfString(array $arr): void {
                    if (isset($arr["hello"])) {
                        echo $arr["hello"];
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testEnsureArrayOffsetsExistAfterNarrowingTheIssetKey(): void
    {
        Config::getInstance()->ensure_array_string_offsets_exist = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /** @param array<string, string> $arr */
                function takesArray(array $arr, string $key): void {
                    if (isset($arr[$key])) {
                        if ("foo" === $key) {
                            echo $arr[$key];
                        } elseif ("bar" === $key) {
                            echo $arr[$key];
                        } else {
                            echo $arr[$key];
                        }
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testEnsureArrayOffsetsExistAfterAssigningTheIssetKey(): void
    {
        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('PossiblyUndefinedStringArrayOffset');

        Config::getInstance()->ensure_array_string_offsets_exist = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /** @param array<string, string> $arr */
                function takesArray(array $arr, string $key): void {
                    if (isset($arr[$key])) {
                        $key = "foo";
                        echo $arr[$key];
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testEnsureArrayOffsetsExistForOffsetsPassedByReference(): void
    {
        Config::getInstance()->ensure_array_string_offsets_exist = true;
        Config::getInstance()->ensure_array_int_offsets_exist = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @param array<string, int>|null $dst
                 * @param-out array<string, int> $dst
                 */
                function fill(?array &$dst): void {
                    $dst = ["n" => 1];
                }

                /**
                 * @param array<string, array<string, int>> $loc
                 * @param list<array<string, int>> $list
                 */
                function f(array $loc, array $list, string $k): void {
                    fill($loc["a"]);
                    fill($loc[$k]);
                    fill($list[0]);
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testOffsetPassedByReferenceIsNullWhenItMayBeMissing(): void
    {
        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('PossiblyNullArgument');

        Config::getInstance()->ensure_array_string_offsets_exist = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /** @param array<string, int> $dst */
                function sortIt(array &$dst): void {}

                /** @param array<string, array<string, int>> $loc */
                function f(array $loc): void {
                    sortIt($loc["a"]);
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testOffsetPassedByReferenceOnlyByAStubIsStillChecked(): void
    {
        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('PossiblyUndefinedIntArrayOffset');

        Config::getInstance()->ensure_array_int_offsets_exist = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /** @param array<int, resource> $pipes */
                function f(array $pipes): void {
                    fclose($pipes[1]);
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testDontEnsureArrayOffsetsExist(): void
    {
        Config::getInstance()->ensure_array_string_offsets_exist = false;

        $this->addFile(
            'somefile.php',
            '<?php
                /** @psalm-mutation-free */
                function takesString(string $s): void {}

                /** @param array<string, string> $arr */
                function takesArrayIteratorOfString(array $arr): void {
                    echo $arr["hello"];
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testEnsureArrayOffsetsExistWithIssetCheckFollowedByIsArray(): void
    {
        Config::getInstance()->ensure_array_string_offsets_exist = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @param array<string, mixed> $s
                 * @psalm-mutation-free
                 */
                function foo(array $s) : void {
                    if (isset($s["a"]) && \is_array($s["a"])) {}
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testComplainAfterFirstIsset(): void
    {
        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('PossiblyUndefinedStringArrayOffset');

        Config::getInstance()->ensure_array_string_offsets_exist = true;

        $this->addFile(
            'somefile.php',
            '<?php
                    function foo(array $arr) : void {
                        if (isset($arr["a"]) && $arr["b"]) {}
                    }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testComplainAboutAStringOffsetThatMayBeAKeyNotCheckedYet(): void
    {
        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('PossiblyUndefinedStringArrayOffset');

        Config::getInstance()->ensure_array_string_offsets_exist = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @param array<string, string> $input
                 * @param "foo"|"bar" $key
                 */
                function show(array $input, string $key): void {
                    if (array_key_exists("foo", $input)) {
                        echo $input["foo"];
                        echo $input[$key];
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testComplainAboutAnIntOffsetThatMayBeAKeyNotCheckedYet(): void
    {
        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('PossiblyUndefinedIntArrayOffset');

        Config::getInstance()->ensure_array_int_offsets_exist = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @param array<int, string> $input
                 * @param 1|2 $key
                 */
                function show(array $input, int $key): void {
                    if (isset($input[1])) {
                        echo $input[$key];
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testNoIssueWhenEachKeyTheOffsetMayBeIsSet(): void
    {
        Config::getInstance()->ensure_array_string_offsets_exist = true;
        Config::getInstance()->ensure_array_int_offsets_exist = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @param array<array-key, string> $input
                 * @param "foo"|"bar" $key
                 * @param 1|2 $index
                 */
                function show(array $input, string $key, int $index): void {
                    if (isset($input["foo"], $input["bar"], $input[1], $input[2])) {
                        echo $input[$key];
                        echo $input[$index];
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testEnsureArrayIntOffsetsExist(): void
    {
        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('PossiblyUndefinedIntArrayOffset');

        Config::getInstance()->ensure_array_int_offsets_exist = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /** @psalm-mutation-free */
                function takesString(string $s): void {}

                /**
                 * @param array<int, string> $arr
                 * @psalm-mutation-free
                 */
                function takesArrayIteratorOfString(array $arr): void {
                    echo $arr[4];
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testNoIssueWhenUsingArrayValuesOnNonEmptyArray(): void
    {
        Config::getInstance()->ensure_array_int_offsets_exist = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @psalm-mutation-free
                 * @param string[][] $arr
                 */
                function foo(array $arr) : void {
                    if (count($arr) === 1 && count(array_values($arr)[0]) === 1) {}
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testNoIssueWhenUsingArrayValuesOnNonEmptyArrayCheckedWithSizeof(): void
    {
        Config::getInstance()->ensure_array_int_offsets_exist = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @param string[][] $arr
                 * @psalm-mutation-free
                 */
                function foo(array $arr) : void {
                    if (sizeof($arr) === 1 && sizeof(array_values($arr)[0]) === 1) {}
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testNoIssueAfterManyIssets(): void
    {
        Config::getInstance()->ensure_array_int_offsets_exist = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @return mixed
                 * @psalm-pure
                 */
                function f(array $a) {
                    if (isset($a[1])
                        && is_array($a[1])
                        && isset($a[1][2])
                    ) {
                        return $a[1][2];
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testEnsureListOffsetExistsNotEmpty(): void
    {
        Config::getInstance()->ensure_array_int_offsets_exist = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /** @param list<string> $arr */
                function takesList(array $arr) : void {
                    if ($arr) {
                        echo $arr[0];
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testEnsureListOffsetExistsAfterArrayPop(): void
    {
        Config::getInstance()->ensure_array_int_offsets_exist = true;

        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('PossiblyUndefinedIntArrayOffset');

        $this->addFile(
            'somefile.php',
            '<?php
                /** @param list<string> $arr */
                function takesList(array $arr) : void {
                    if ($arr) {
                        echo $arr[0];
                        array_pop($arr);
                        echo $arr[0];
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testEnsureOffsetExistsAfterArrayPush(): void
    {
        Config::getInstance()->ensure_array_int_offsets_exist = true;

        $this->addFile(
            'somefile.php',
            '<?php
                $a = [1, 2, 3];
                array_push($a, 4);
                echo $a[3];',
        );
        $this->analyzeFile('somefile.php', new Context());
    }

    public function testEnsureOffsetExistsAfterNestedIsset(): void
    {
        Config::getInstance()->ensure_array_string_offsets_exist = true;

        $this->addFile(
            'somefile.php',
            '<?php
                class A {
                    public int $foo = 0;
                }

                /**
                 * @psalm-mutation-free
                 * @param array<string, A> $value
                 */
                function test(array $value): int
                {
                    return isset($value["a"]->foo) ? $value["a"]->foo : 0;
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testEnsureListOffsetExistsAfterCountValueInRange(): void
    {
        Config::getInstance()->ensure_array_int_offsets_exist = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /** @param list<string> $arr */
                function takesList(array $arr) : void {
                    if (count($arr) >= 3) {
                        echo $arr[0];
                        echo $arr[1];
                        echo $arr[2];
                    }

                    if (count($arr) > 2) {
                        echo $arr[0];
                        echo $arr[1];
                        echo $arr[2];
                    }

                    if (count($arr) === 3) {
                        echo $arr[0];
                        echo $arr[1];
                        echo $arr[2];
                    }

                    if (3 === count($arr)) {
                        echo $arr[0];
                        echo $arr[1];
                        echo $arr[2];
                    }

                    if (3 <= count($arr)) {
                        echo $arr[0];
                        echo $arr[1];
                        echo $arr[2];
                    }

                    if (2 < count($arr)) {
                        echo $arr[0];
                        echo $arr[1];
                        echo $arr[2];
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testCountOnKeyedArrayInRange(): void
    {
        Config::getInstance()->ensure_array_int_offsets_exist = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /** @param non-empty-list<string> $list */
                function bar(array $list) : void {
                    if (rand(0, 1)) {
                        $list = ["a"];
                    }
                    if (count($list) > 1) {
                        echo $list[1];
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testCountOnKeyedArrayInRangeWithUpdate(): void
    {
        Config::getInstance()->ensure_array_int_offsets_exist = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /** @param non-empty-list<string> $list */
                function bar(array $list) : void {
                    if (rand(0, 1)) {
                        $list = ["a"];
                    }
                    if (count($list) > 1) {
                        if ($list[1][0] === "a") {
                            $list[1] = "foo";
                        }
                        echo $list[1];
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testCountOnKeyedArrayOutOfRange(): void
    {
        Config::getInstance()->ensure_array_int_offsets_exist = true;

        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('PossiblyUndefinedIntArrayOffset');

        $this->addFile(
            'somefile.php',
            '<?php
                /** @param non-empty-list<string> $list */
                function bar(array $list) : void {
                    if (rand(0, 1)) {
                        $list = ["a"];
                    }
                    if (count($list) > 1) {
                        echo $list[2];
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testEnsureListOffsetExistsAfterCountValueOutOfRange(): void
    {
        Config::getInstance()->ensure_array_int_offsets_exist = true;

        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('PossiblyUndefinedIntArrayOffset');

        $this->addFile(
            'somefile.php',
            '<?php
                /** @param list<string> $arr */
                function takesList(array $arr) : void {
                    if (count($arr) >= 2) {
                        echo $arr[0];
                        echo $arr[1];
                        echo $arr[2];
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testEnsureListOffsetExistsAfterCountValueOutOfRangeSmallerThan(): void
    {
        Config::getInstance()->ensure_array_int_offsets_exist = true;

        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('PossiblyUndefinedIntArrayOffset');

        $this->addFile(
            'somefile.php',
            '<?php
                /** @param list<string> $arr */
                function takesList(array $arr) : void {
                    if (2 <= count($arr)) {
                        echo $arr[0];
                        echo $arr[1];
                        echo $arr[2];
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testDontWorryWhenUnionedWithPositiveInt(): void
    {
        Config::getInstance()->ensure_array_int_offsets_exist = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @param list<string> $a
                 * @param 0|positive-int $b
                 */
                function foo(array $a, int $b): void {
                    echo $a[$b];
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testAFalseOffsetIsReportedOnceAndOnlyWhenItIsTheOnlyProblem(): void
    {
        $this->project_analyzer->setPhpVersion('8.3', 'tests');
        $this->project_analyzer->getConfig()->throw_exception = false;
        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                /** @param array<int, string> $a */
                function onlyFalse(array $a): string { return $a[false]; }

                /** @param array{a: int, b: int} $s */
                function shape(array $s, string|false $k): int { return $s[$k]; }

                /** @param array<int, string> $a */
                function floatFalse(array $a, float|false $k): void { $a[$k] = "y"; }

                /** @param array<int, string> $a */
                function coalesceAssign(array $a, int|false $k): void { $a[$k] ??= "x"; }

                /** @param list<string> $l */
                function search(array $l): string { return $l[array_search("a", $l)]; }

                /** @param array<int, string> $a */
                function narrowed(array $a): string {
                    $k = array_search("a", $a);
                    if ($k !== false) {
                        return $a[$k];
                    }

                    return "";
                }

                function byRef(array &$x): void {}

                /** @param array<int, array> $a */
                function refArg(array $a, int|false $k): void { byRef($a[$k]); }

                /** @param array<int, int> $a */
                function incr(array $a, int|false $k): void { $a[$k]++; }

                /** @param array<int, int> $a */
                function plusEq(array $a, int|false $k): void { $a[$k] += 1; }

                /** @param array<string, array> $a */
                function refArgStrFalse(array $a, string|false $k): void { byRef($a[$k]); }

                /** @param array<int, string> $a */
                function readInLoop(array $a, int|false $k): void {
                    for ($i = 0; $i < 3; $i++) {
                        echo $a[$k];
                    }
                }

                /** @param array<int, string> $a */
                function writeInLoop(array $a, int|false $k): void {
                    while (rand(0, 1)) {
                        $a[$k] = "x";
                    }
                }

                /** @param array<int, int> $a */
                function incrInLoop(array $a, int|false $k): void {
                    foreach ([1, 2] as $_) {
                        $a[$k]++;
                    }
                }',
        );
        $this->analyzeFile($file_path, new Context());

        $messages = [];
        foreach (IssueBuffer::getIssuesDataForFile($file_path) as $issue) {
            if (str_contains($issue->message, 'Cannot access')) {
                $messages[] = $issue->line_from . ' ' . $issue->type . ': ' . $issue->message;
            }
        }

        $this->assertSame(
            [
                '3 InvalidArrayOffset: Cannot access value on variable $a using a false offset, expecting array-key',
                '6 InvalidArrayOffset: Cannot access value on variable $s using a false|string offset, '
                    . 'expecting \'a\'|\'b\'',
                '9 InvalidArrayOffset: Cannot access value on variable $a[$k] using a false|float offset, '
                    . 'expecting array-key',
                '12 PossiblyInvalidArrayOffset: Cannot access value on variable $a[$k] using possibly false offset '
                    . 'false|int, which is 0',
                '15 PossiblyInvalidArrayOffset: Cannot access value on variable $l using possibly false offset '
                    . 'false|int<0, max>, which is 0',
                '30 PossiblyInvalidArrayOffset: Cannot access value on variable $a using possibly false offset '
                    . 'false|int, which is 0',
                '33 PossiblyInvalidArrayOffset: Cannot access value on variable $a using possibly false offset '
                    . 'false|int, which is 0',
                '36 PossiblyInvalidArrayOffset: Cannot access value on variable $a using possibly false offset '
                    . 'false|int, which is 0',
                '39 PossiblyInvalidArrayOffset: Cannot access value on variable $a using a false|string offset, '
                    . 'expecting string',
                '44 PossiblyInvalidArrayOffset: Cannot access value on variable $a using possibly false offset '
                    . 'false|int, which is 0',
                '51 PossiblyInvalidArrayOffset: Cannot access value on variable $a[$k] using possibly false offset '
                    . 'false|int, which is 0',
                '58 PossiblyInvalidArrayOffset: Cannot access value on variable $a using possibly false offset '
                    . 'false|int, which is 0',
            ],
            $messages,
        );
    }

    public function testAnOffsetReadAndWrittenIsReportedOnce(): void
    {
        $this->project_analyzer->getConfig()->throw_exception = false;
        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                /** @param array<string, int> $a */
                function incr(array $a, mixed $k): void { $a[$k]++; }

                /** @param array<string, int> $a */
                function plusEq(array $a, mixed $k): void { $a[$k] += 1; }

                function byRef(?int &$i): void { $i = 1; }

                /** @param array<string, int> $a */
                function refArg(array $a, mixed $k): void { byRef($a[$k]); }

                /** @param array<string, int> $a */
                function invalid(array $a, stdClass $k): void { $a[$k]++; }

                /** @param array<string, int> $a */
                function possiblyInvalid(array $a, stdClass|string $k): void { $a[$k] += 1; }

                /** @param array<string, array<string, int>> $a */
                function nested(array $a, mixed $k, mixed $j): void { $a[$k][$j]++; }

                /** @param array<string, int> $a */
                function incrInLoop(array $a, mixed $k): void {
                    foreach ([1, 2] as $_) {
                        $a[$k]++;
                    }
                }

                /** @param array<string, int> $a */
                function coalesceAssign(array $a, stdClass|string $k): void { $a[$k] ??= 1; }',
        );
        $this->analyzeFile($file_path, new Context());

        $messages = [];
        foreach (IssueBuffer::getIssuesDataForFile($file_path) as $issue) {
            if (str_contains($issue->message, 'Cannot access')) {
                $messages[] = $issue->line_from . ' ' . $issue->type . ': ' . $issue->message;
            }
        }

        $this->assertSame(
            [
                '3 MixedArrayOffset: Cannot access value on variable $a using mixed offset',
                '6 MixedArrayOffset: Cannot access value on variable $a using mixed offset',
                '11 MixedArrayOffset: Cannot access value on variable $a using mixed offset',
                '14 InvalidArrayOffset: Cannot access value on variable $a using a stdClass offset, '
                    . 'expecting array-key',
                '17 PossiblyInvalidArrayOffset: Cannot access value on variable $a using a stdClass|string offset, '
                    . 'expecting string',
                '20 MixedArrayOffset: Cannot access value on variable $a using mixed offset',
                '20 MixedArrayOffset: Cannot access value on variable $a[$k] using mixed offset',
                '25 MixedArrayOffset: Cannot access value on variable $a using mixed offset',
                '30 PossiblyInvalidArrayOffset: Cannot access value on variable $a[$k] using a stdClass|string offset, '
                    . 'expecting array-key',
            ],
            $messages,
        );
    }

    public function testMessagesNameAnArrayWithoutVariable(): void
    {
        $this->project_analyzer->getConfig()->throw_exception = false;
        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                /** @return array{11: string} */
                function shape(): array { return [11 => "foo"]; }
                /** @return ?array<string, string> */
                function nullableArr() { return []; }
                function mixedVal(): mixed { return 1; }
                function plainInt(): int { return 1; }
                /** @return array<string, string>|false */
                function falsable() { return []; }
                /** @return array{} */
                function emptyArr(): array { return []; }

                function t(?string $n, mixed $m): void {
                    shape()[null];
                    shape()[$n];
                    shape()["a"];
                    shape()[$m];
                    nullableArr()["a"];
                    mixedVal()["a"];
                    plainInt()["a"];
                    falsable()["a"];
                    emptyArr()["a"];
                    shape()[1.5];
                }',
        );
        $this->analyzeFile($file_path, new Context());

        $messages = [];
        foreach (IssueBuffer::getIssuesDataForFile($file_path) as $issue) {
            if (str_contains($issue->message, 'Cannot access')) {
                $messages[$issue->type][] = $issue->message;
            }
        }

        ksort($messages);
        $this->assertSame([], array_filter(
            array_merge(...array_values($messages)),
            static fn(string $message): bool => !str_contains($message, '(expression)'),
        ));
        $this->assertSame(
            [
                'EmptyArrayAccess',
                'InvalidArrayAccess',
                'InvalidArrayOffset',
                'MixedArrayOffset',
                'NullArrayOffset',
                'PossiblyInvalidArrayAccess',
                'PossiblyNullArrayAccess',
                'PossiblyNullArrayOffset',
            ],
            array_keys($messages),
        );
    }

    public function testOffsetOfANullableArrayIsReportedWhereItIsFirstRead(): void
    {
        $this->project_analyzer->getConfig()->throw_exception = false;
        $this->project_analyzer->setPhpVersion('8.1', 'tests');
        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                /** @param array<string, string>|null $a */
                function truthy(?array $a): void {
                    if ($a["k"] && strlen($a["k"]) > 1) { echo 1; }
                }

                /** @param array<string, string|null>|null $a */
                function notNull(?array $a): void {
                    if ($a["k"] !== null && strlen($a["k"]) > 1) { echo 1; }
                }

                /** @param array<string, list<string>|int>|null $a */
                function isArr(?array $a): void {
                    if (is_array($a["k"]) && count($a["k"]) > 0) { echo 1; }
                }

                /** @param array<string, list<string>>|null $lists */
                function unguarded(?array $lists): void {
                    if (isset($lists["codes"]) && is_array($lists["codes"])) { echo 1; }
                    if (is_array($lists["codes"])) { echo 2; }
                }',
        );
        $this->analyzeFile($file_path, new Context());

        $lines = [];
        foreach (IssueBuffer::getIssuesDataForFile($file_path) as $issue) {
            if ($issue->type === 'PossiblyNullArrayAccess') {
                $lines[] = $issue->line_from;
            }
        }

        $this->assertSame([4, 9, 14, 20], $lines);
    }

    public function testRepeatedReadOfAnOffsetOfANullableArrayIsReportedOnce(): void
    {
        $this->project_analyzer->getConfig()->throw_exception = false;
        $this->project_analyzer->setPhpVersion('8.1', 'tests');
        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                /** @param list<string>|null $m */
                function repeated(?array $m): void {
                    echo $m[0];
                    echo $m[0];
                }',
        );
        $this->analyzeFile($file_path, new Context());

        $lines = [];
        foreach (IssueBuffer::getIssuesDataForFile($file_path) as $issue) {
            if ($issue->type === 'PossiblyNullArrayAccess') {
                $lines[] = $issue->line_from;
            }
        }

        $this->assertSame([4], $lines);
    }

    public function testALoopThatDoesNotAssignTheArrayKeepsTheDeduplicationOfItsNull(): void
    {
        $this->assertPossiblyNullArrayAccessLines(
            '<?php
                /** @return list<int>|null */
                function get(): ?array {
                    return null;
                }

                /** @param list<int> $xs */
                function loopWithoutAssignment(array $xs): void {
                    $m = get();
                    echo $m[0]; // issue
                    foreach ($xs as $x) {
                        echo $x;
                    }
                    echo $m[0];
                }

                /** @param list<int> $xs */
                function loopAssigningAnotherVariable(array $xs): void {
                    $m = get();
                    echo $m[0]; // issue
                    while ($xs) {
                        $other = array_pop($xs);
                    }
                    echo $m[0];
                }

                /** @param list<int> $xs */
                function loopAssigningTheArray(array $xs): void {
                    $m = get();
                    echo $m[0]; // issue
                    foreach ($xs as $x) {
                        $m = get();
                    }
                    echo $m[0]; // issue
                }',
        );
    }

    public function testAnAssignmentOfTheArrayInASwitchCaseEndsTheDeduplicationOfItsNull(): void
    {
        $this->assertPossiblyNullArrayAccessLines(
            '<?php
                /**
                 * @param list<string>|null $m
                 * @param list<string>|null $n
                 */
                function assignedInACase(?array $m, ?array $n, int $k): void {
                    echo $m[0]; // issue
                    switch ($k) {
                        case 1:
                            $m = $n;
                            break;
                        default:
                    }
                    echo $m[0]; // issue
                }

                /**
                 * @param list<string>|null $m
                 * @param list<string>|null $n
                 */
                function assignedInTheDefaultOnly(?array $m, ?array $n, int $k): void {
                    echo $m[0]; // issue
                    switch ($k) {
                        case 1:
                            echo 1;
                            break;
                        default:
                            $m = $n;
                    }
                    echo $m[0]; // issue
                }

                /** @param list<string>|null $m */
                function notAssignedInTheSwitch(?array $m, int $k): void {
                    echo $m[0]; // issue
                    switch ($k) {
                        case 1:
                            echo 1;
                            break;
                        default:
                    }
                    echo $m[0];
                }',
        );
    }

    public function testAnAssignmentOfTheArrayInACatchEndsTheDeduplicationOfItsNull(): void
    {
        $this->assertPossiblyNullArrayAccessLines(
            '<?php
                /**
                 * @param list<string>|null $m
                 * @param list<string>|null $n
                 */
                function assignedInACatch(?array $m, ?array $n): void {
                    echo $m[0]; // issue
                    try {
                        echo 1;
                    } catch (Exception $e) {
                        $m = $n;
                    }
                    echo $m[0]; // issue
                }

                /** @param list<string>|null $m */
                function notAssignedInTheCatch(?array $m): void {
                    echo $m[0]; // issue
                    try {
                        echo 1;
                    } catch (Exception $e) {
                        echo 2;
                    }
                    echo $m[0];
                }',
        );
    }

    public function testAnAssignmentOfTheArrayInATernaryOrMatchEndsTheDeduplicationOfItsNull(): void
    {
        $this->assertPossiblyNullArrayAccessLines(
            '<?php
                /**
                 * @param list<string>|null $m
                 * @param list<string>|null $n
                 */
                function assignedInATernary(?array $m, ?array $n): void {
                    echo $m[0]; // issue
                    $x = rand(0, 1) ? ($m = $n) : null;
                    echo $m[0]; // issue
                }

                /**
                 * @param list<string>|null $m
                 * @param list<string>|null $n
                 */
                function assignedInAMatchArm(?array $m, ?array $n, int $k): void {
                    echo $m[0]; // issue
                    $x = match ($k) {
                        1 => ($m = $n),
                        default => null,
                    };
                    echo $m[0]; // issue
                }

                /** @param list<string>|null $m */
                function notAssignedInTheTernary(?array $m): void {
                    echo $m[0]; // issue
                    $x = rand(0, 1) ? 1 : 2;
                    echo $m[0];
                }',
            '8.1',
        );
    }

    public function testAnAssignmentNestedInABranchOfAnIfOrALoopEndsTheDeduplicationOfItsNull(): void
    {
        $this->assertPossiblyNullArrayAccessLines(
            '<?php
                /**
                 * @param list<string>|null $m
                 * @param list<string>|null $n
                 * @param list<int> $xs
                 */
                function switchInALoop(?array $m, ?array $n, array $xs): void {
                    echo $m[0]; // issue
                    foreach ($xs as $k) { switch ($k) { case 1: $m = $n; break; default: } }
                    if (isset($m[0]) && is_string($m[0])) { echo 1; }
                    echo $m[0]; // issue
                }

                /**
                 * @param list<string>|null $m
                 * @param list<string>|null $n
                 */
                function switchInAnIf(?array $m, ?array $n, int $k): void {
                    echo $m[0]; // issue
                    if (rand(0, 1)) { switch ($k) { case 1: $m = $n; break; default: } }
                    echo $m[0]; // issue
                }

                /**
                 * @param list<string>|null $m
                 * @param list<string>|null $n
                 */
                function ternaryInAnIf(?array $m, ?array $n): void {
                    echo $m[0]; // issue
                    if (rand(0, 1)) { $x = rand(0, 1) ? ($m = $n) : null; }
                    echo $m[0]; // issue
                }

                /**
                 * @param list<string>|null $m
                 * @param list<string>|null $n
                 * @param list<int> $xs
                 */
                function loopInAnIf(?array $m, ?array $n, array $xs): void {
                    echo $m[0]; // issue
                    if (rand(0, 1)) { foreach ($xs as $_) { $m = $n; } }
                    echo $m[0]; // issue
                }

                /**
                 * @param list<string>|null $m
                 * @param list<string>|null $n
                 * @param list<int> $xs
                 */
                function catchInALoop(?array $m, ?array $n, array $xs): void {
                    echo $m[0]; // issue
                    foreach ($xs as $_) { try { echo 1; } catch (Exception $e) { $m = $n; } }
                    echo $m[0]; // issue
                }

                /**
                 * @param list<string>|null $m
                 * @param list<string>|null $n
                 * @param list<int> $xs
                 */
                function assignedBeforeABreak(?array $m, ?array $n, array $xs): void {
                    echo $m[0]; // issue
                    foreach ($xs as $_) { if (rand(0, 1)) { $m = $n; break; } }
                    echo $m[0]; // issue
                }

                /**
                 * @param list<string>|null $m
                 * @param list<string>|null $n
                 */
                function ifInAFinally(?array $m, ?array $n): void {
                    echo $m[0]; // issue
                    try { echo 1; } finally { if (rand(0, 1)) { $m = $n; } }
                    echo $m[0]; // issue
                }

                /**
                 * @param list<string>|null $m
                 * @param list<int> $xs
                 */
                function notAssignedInTheNestedBranches(?array $m, array $xs, int $k): void {
                    echo $m[0]; // issue
                    foreach ($xs as $_) { switch ($k) { case 1: echo 1; break; default: } }
                    if (rand(0, 1)) { $x = rand(0, 1) ? 1 : 2; }
                    echo $m[0];
                }',
        );
    }

    public function testAReadInTheAndConditionOfATernaryIsKeptAfterTheTernary(): void
    {
        $this->assertPossiblyNullArrayAccessLines(
            '<?php
                /** @param array{generation: array{expression: string|null, type: string}|null}|null $column */
                function g(?array $column): void {
                    $a = ! is_null($column["generation"]) && $column["generation"]["type"] === "v" ? 1 : null; // issue
                    $b = ! is_null($column["generation"]) ? 1 : null;
                    echo $a, $b;
                }',
        );
    }

    public function testABreakThatOnlyLeavesASwitchKeepsTheReadAtTheEndOfTheLoopBody(): void
    {
        $this->assertPossiblyNullArrayAccessLines(
            '<?php
                /**
                 * @param list<string>|null $m
                 * @param list<string>|null $n
                 * @param list<int> $xs
                 */
                function f(?array $m, ?array $n, array $xs, int $k): void {
                    echo $m[0]; // issue
                    foreach ($xs as $_) {
                        switch ($k) { case 1: $m = $n; break; default: }
                        echo $m[0]; // issue
                    }
                    echo $m[0];
                }

                /**
                 * @param list<string>|null $m
                 * @param list<int> $xs
                 */
                function continueInASwitch(?array $m, array $xs, int $k): void {
                    foreach ($xs as $_) {
                        switch ($k) { case 1: continue; default: }
                        echo $m[0]; // issue
                    }
                    echo $m[0]; // issue
                }',
        );
    }

    public function testAReadInTheOrConditionOfAnIfOrATernaryIsKeptAfterIt(): void
    {
        $this->assertPossiblyNullArrayAccessLines(
            '<?php
                /** @param array{g: array{t: string}|null}|null $c */
                function inAnIf(?array $c): void {
                    if (is_null($c["g"]) || $c["g"]["t"] === "v") { echo 1; } // issue
                    echo $c["g"] === null ? 1 : 2;
                }

                /** @param array{g: array{t: string}|null}|null $c */
                function inATernary(?array $c): void {
                    $a = is_null($c["g"]) || $c["g"]["t"] === "v" ? 1 : null; // issue
                    echo $c["g"] === null ? 1 : 2;
                    echo $a;
                }

                /** @param array{g: array{t: string}|null}|null $c */
                function withFiveOperands(?array $c, bool $a, bool $b, bool $d): void {
                    if (is_null($c["g"]) || $a || $b || $d || $c["g"]["t"] === "v") { echo 1; } // issue
                    echo $c["g"] === null ? 1 : 2;
                }

                /** @param array{g: array{t: string}|null}|null $c */
                function onTheRight(?array $c, bool $b): void {
                    if ($b || $c["g"] === null) { echo 1; } // issue
                    echo $c["g"] === null ? 1 : 2; // issue
                }',
        );
    }

    public function testAnOffsetReadInEveryBranchIsReadAfterTheMerge(): void
    {
        $this->assertPossiblyNullArrayAccessLines(
            '<?php
                /** @param list<string>|null $m */
                function ifElse(?array $m, int $k): void {
                    if ($k > 0) {
                        echo $m[0]; // issue
                    } else {
                        echo $m[0]; // issue
                    }
                    echo $m[0];
                }

                /** @param list<string>|null $m */
                function ifElseifElse(?array $m, int $k): void {
                    if ($k > 0) {
                        echo $m[0]; // issue
                    } elseif ($k < 0) {
                        echo $m[0]; // issue
                    } else {
                        echo $m[0]; // issue
                    }
                    echo $m[0];
                }

                /** @param list<string>|null $m */
                function ifWithoutElse(?array $m, int $k): void {
                    if ($k > 0) { echo $m[0]; } // issue
                    echo $m[0]; // issue
                }

                /** @param list<string>|null $m */
                function switchWithADefault(?array $m, int $k): void {
                    switch ($k) {
                        case 1: echo $m[0]; break; // issue
                        default: echo $m[0]; // issue
                    }
                    echo $m[0];
                }

                /** @param list<string>|null $m */
                function switchWithoutADefault(?array $m, int $k): void {
                    switch ($k) {
                        case 1: echo $m[0]; break; // issue
                        case 2: echo $m[0]; break; // issue
                    }
                    echo $m[0]; // issue
                }

                /** @param list<string>|null $m */
                function breakBeforeTheRead(?array $m, int $k): void {
                    switch ($k) {
                        case 1:
                            if (rand(0, 1)) { break; }
                            echo $m[0]; // issue
                            break;
                        default: echo $m[0]; // issue
                    }
                    echo $m[0]; // issue
                }',
        );
    }

    public function testALoopThatChangesTheTypeOfTheArrayKeepsTheReadItDoesAfterTheAssignment(): void
    {
        $this->assertPossiblyNullArrayAccessLines(
            '<?php
                /**
                 * @param list<string>|null $m
                 * @param list<int> $xs
                 */
                function switchInTheBody(?array $m, array $xs, int $k): void {
                    echo $m[0]; // issue
                    foreach ($xs as $_) {
                        switch ($k) {
                            case 1:
                                $m = ["x"];
                                break;
                            default:
                        }
                        echo $m[0]; // issue
                    }
                    echo $m[0];
                }

                /**
                 * @param list<string>|null $m
                 * @param list<int> $xs
                 */
                function assignedAtTheEndOfTheBody(?array $m, array $xs): void {
                    echo $m[0]; // issue
                    foreach ($xs as $_) {
                        $m = ["x"];
                    }
                    echo $m[0]; // issue
                }',
            '8.1',
        );
    }

    /**
     * Asserts that PossiblyNullArrayAccess is reported on the lines of the code marked `// issue`, and nowhere else
     */
    private function assertPossiblyNullArrayAccessLines(string $code, string $php_version = '8.1'): void
    {
        $this->project_analyzer->getConfig()->throw_exception = false;
        $this->project_analyzer->setPhpVersion($php_version, 'tests');
        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile($file_path, $code);
        $this->analyzeFile($file_path, new Context());

        $expected = [];
        foreach (explode("\n", $code) as $index => $line) {
            if (str_contains($line, '// issue')) {
                $expected[] = $index + 1;
            }
        }

        $lines = [];
        foreach (IssueBuffer::getIssuesDataForFile($file_path) as $issue) {
            if ($issue->type === 'PossiblyNullArrayAccess') {
                $lines[] = $issue->line_from;
            }
        }

        $this->assertSame($expected, $lines);
    }

    public function testOffsetOfANullableArrayIsReportedAgainAfterTheArrayChanged(): void
    {
        $this->project_analyzer->getConfig()->throw_exception = false;
        $this->project_analyzer->setPhpVersion('8.1', 'tests');
        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                /**
                 * @param list<string>|null $m
                 * @param list<string>|null $n
                 */
                function branch(?array $m, ?array $n): void {
                    echo $m[0];
                    if (rand(0, 1)) { $m = $n; }
                    if (isset($m[0]) && is_string($m[0])) { echo 1; }
                    echo $m[0];
                }

                final class P {
                    /** @var list<string>|null */
                    public ?array $d = null;
                    public function mutate(): void { $this->d = null; }
                    public function f(): void {
                        echo $this->d[0];
                        $this->mutate();
                        if (isset($this->d[0]) && is_string($this->d[0])) { echo 1; }
                        echo $this->d[0];
                    }
                }

                /** @param list<string>|null $m */
                function loop(?array $m): void {
                    echo $m[0];
                    foreach ([1, 2] as $_) {
                        if (rand(0, 1)) { $m = null; }
                    }
                    if (isset($m[0]) && is_string($m[0])) { echo 1; }
                    echo $m[0];
                }

                /** @param list<string>|null $m */
                function unchangedByTheBranch(?array $m): void {
                    echo $m[0];
                    if (rand(0, 1)) { echo 2; }
                    echo $m[0];
                }',
        );
        $this->analyzeFile($file_path, new Context());

        $lines = [];
        foreach (IssueBuffer::getIssuesDataForFile($file_path) as $issue) {
            if ($issue->type === 'PossiblyNullArrayAccess') {
                $lines[] = $issue->line_from;
            }
        }

        sort($lines);

        $this->assertSame([7, 10, 18, 21, 27, 32, 37], $lines);
    }

    public function testTheFlagOfAReadOffsetFollowsTheFlowOfTheBranches(): void
    {
        $this->project_analyzer->getConfig()->throw_exception = false;
        $this->project_analyzer->setPhpVersion('8.1', 'tests');
        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                /**
                 * @param list<string>|null $m
                 * @param list<string>|null $n
                 */
                function f(?array $m, ?array $n): void {
                    echo $m[0];
                    if (rand(0, 1)) { $m = $n; } else { echo $m[0]; }
                }

                /**
                 * @param list<string>|null $m
                 * @param list<string>|null $n
                 */
                function g(?array $m, ?array $n): void {
                    echo $m[0];
                    if (rand(0, 1)) { $m = $n; return; }
                    echo $m[0];
                }

                final class G {
                    /** @var list<string>|null */
                    public ?array $p = null;
                    public int $n = 0;
                    public function wrap(): void { $this->n++; }
                    public function f(): void {
                        echo $this->p[0];
                        $this->wrap();
                        echo $this->p[0];
                    }
                }',
        );
        $this->analyzeFile($file_path, new Context());

        $lines = [];
        foreach (IssueBuffer::getIssuesDataForFile($file_path) as $issue) {
            if ($issue->type === 'PossiblyNullArrayAccess') {
                $lines[] = $issue->line_from;
            }
        }

        sort($lines);

        // each offset is reported where it is first read: the branch that assigns `$m` and returns does not end the
        // flag after the if, a call that only writes `$this->n` does not end it for `$this->p`
        $this->assertSame([7, 16, 27], $lines);
    }

    public function testAnAssertionDoesNotEndTheDeduplicationOfTheNullOfAnArray(): void
    {
        $this->project_analyzer->getConfig()->throw_exception = false;
        $this->project_analyzer->setPhpVersion('8.1', 'tests');
        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                /** @param array<string, string|int>|null $b */
                function asserted(?array $b): void {
                    echo $b["a"];
                    if (is_string($b["c"])) { echo 1; }
                    echo $b["a"];
                }

                final class S {
                    /** @var list<string>|null */
                    public static ?array $d = null;
                    public function f(): void {
                        echo self::$d[0];
                        echo self::$d[0];
                    }
                }',
        );
        $this->analyzeFile($file_path, new Context());

        $lines = [];
        foreach (IssueBuffer::getIssuesDataForFile($file_path) as $issue) {
            if ($issue->type === 'PossiblyNullArrayAccess') {
                $lines[] = $issue->line_from;
            }
        }

        sort($lines);

        // `$b["a"]` and `self::$d[0]` are reported where they are first read, `$b["c"]` where it is read
        $this->assertSame([4, 5, 13], $lines);
    }

    /**
     * With data flow tracked, the branches of the check redefine the list, and their merge gives its first item a
     * type that is not from the docblock, unlike its other items.
     */
    public function testListOffsetAfterIssetCheckWithTaintAnalysis(): void
    {
        $this->project_analyzer->trackTaintedInputs();
        $this->testValidCode(
            '<?php
                /**
                 * @param list<mixed> $l
                 * @return mixed
                 */
                function f(array $l) {
                    if (isset($l[0])) {}
                    return $l[0];
                }',
        );
    }

    public function testListOffsetFromVariableAfterIssetCheckWithTaintAnalysis(): void
    {
        $this->project_analyzer->trackTaintedInputs();
        $this->testValidCode(
            '<?php
                /**
                 * @param list<mixed> $l
                 * @return mixed
                 */
                function g(array $l) {
                    if (isset($l[0])) {}
                    $i = 0;
                    return $l[$i];
                }',
        );
    }

    public function testListOffsetAfterAssignmentInABranchWithTaintAnalysis(): void
    {
        $this->project_analyzer->trackTaintedInputs();
        $this->testValidCode(
            '<?php
                /**
                 * @param list<string> $l
                 * @param string $s
                 */
                function g(array $l, $s): string {
                    if (rand(0, 1)) {
                        $l[0] = $s;
                    }
                    $i = 0;
                    return $l[$i];
                }',
        );
    }

    public function testOffsetsOfDomNamespaceCollectionsAreReadByPositionOrName(): void
    {
        if (PHP_VERSION_ID < 8_04_00) {
            $this->markTestSkipped('The Dom namespace needs PHP 8.4');
        }

        $this->project_analyzer->setPhpVersion('8.4', 'tests');

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                function domAttributeAt(Dom\\NamedNodeMap $attributes): ?Dom\\Attr {
                    return $attributes[0];
                }

                function domAttributeNamed(Dom\\NamedNodeMap $attributes): ?Dom\\Attr {
                    return $attributes["href"];
                }

                function childAt(Dom\\NodeList $children): ?Dom\\Node {
                    return $children[0];
                }

                function elementAt(Dom\\HTMLCollection $elements): ?Dom\\Element {
                    return $elements[0];
                }

                function elementNamed(Dom\\HTMLCollection $elements): ?Dom\\Element {
                    return $elements["main"];
                }

                function entityAt(Dom\\DocumentType $doctype): ?Dom\\Node {
                    return $doctype->entities[0];
                }

                function entityNamed(Dom\\DocumentType $doctype): ?Dom\\Node {
                    return $doctype->entities["e"];
                }',
        );

        $this->analyzeFile($file_path, new Context());
        $this->assertTrue(true);
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'offsetOfADomNamedNodeMapOfEitherKindIsRead' => [
                'code' => '<?php
                    function either(DOMNamedNodeMap $attributes, int|string $key): ?DOMNode {
                        return $attributes[$key];
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'issetOfAnOffsetOfATemplateBoundedByLiteralsThatPartlyMatchTheKeys' => [
                'code' => '<?php
                    /**
                     * @template T of \'a\'|\'z\'
                     * @param array{a: int, b: int} $array
                     * @param T $o
                     */
                    function partialIsset(array $array, $o): bool {
                        return isset($array[$o]);
                    }

                    /**
                     * @template T of \'x\'|\'y\'
                     * @param array{a: true, b: true} $array
                     * @param T|\'a\' $o
                     */
                    function unionWithTemplate(array $array, $o): bool {
                        return isset($array[$o]);
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'offsetsOfADomNamedNodeMapAreReadByPositionOrName' => [
                'code' => '<?php
                    function attributeAt(DOMNamedNodeMap $attributes): ?DOMNode {
                        return $attributes[0];
                    }

                    function attributeNamed(DOMNamedNodeMap $attributes): ?DOMNode {
                        return $attributes["href"];
                    }',
            ],
            'issetOfASealedArrayWithATemplateOffsetBoundedByAUnion' => [
                'code' => '<?php
                    final class Types {
                        const SCALAR = ["int" => true, "bool" => true];

                        /**
                         * @template TObject of object
                         * @template TType of string|class-string<TObject>
                         * @param TType $type
                         */
                        public static function isScalar(string $type): bool {
                            return isset(self::SCALAR[$type]);
                        }
                    }',
            ],
            'issetOfASealedArrayWithATemplateOffsetBoundedByATemplate' => [
                'code' => '<?php
                    /**
                     * @template TKey of int|string
                     * @template TOffset of TKey
                     * @param array{a: true, b: true} $array
                     * @param TOffset $offset
                     */
                    function has(array $array, $offset): bool {
                        return isset($array[$offset]);
                    }',
            ],
            'issetOfAStringOffsetOfIntKeysLooksForANumericString' => [
                'code' => '<?php
                    function label(string $id): string {
                        $labels = ["1" => "foo", "2" => "bar"];

                        if (isset($labels[$id])) {
                            return $labels[$id];
                        }

                        return "";
                    }

                    function labelOrEmpty(string $id): string {
                        $labels = ["1" => "foo", "2" => "bar"];

                        return $labels[$id] ?? "";
                    }

                    /** @param array<int, string> $names */
                    function name(array $names, string $id): string {
                        if (isset($names[$id])) {
                            return $names[$id];
                        }

                        return "";
                    }',
            ],
            'appendToATypedArrayObjectOrArrayIterator' => [
                'code' => '<?php
                    final class Bag {
                        public ArrayObject $items;
                        /** @var ArrayObject<int, int> */
                        public ArrayObject $typed;
                        /** @var ArrayIterator<int, int> */
                        public ArrayIterator $iterator;

                        public function __construct() {
                            $this->items = new ArrayObject();
                            $this->typed = new ArrayObject();
                            $this->iterator = new ArrayIterator();
                        }
                    }

                    $bag = new Bag();
                    $bag->items[] = 1;
                    $bag->typed[] = 1;
                    $bag->iterator[] = 1;
                    $bag->typed->offsetSet(null, 2);',
            ],
            'writeUnderAKeyOfANewArrayObjectOrArrayIterator' => [
                'code' => '<?php
                    final class Storage {
                        /** @var ArrayObject */
                        private $config;

                        public function __construct(string $collection) {
                            $this->config = new ArrayObject();
                            $this->config[$collection] = [];
                        }
                    }

                    $object = new ArrayObject();
                    $object["x"] = 1;
                    $object[] = 2;
                    $iterator = new ArrayIterator();
                    $iterator["y"] = 3;',
                'assertions' => [],
                'ignored_issues' => ['UnusedProperty'],
            ],
            'issetOnArrayOrArrayAccessKeepsTheArrayValueType' => [
                'code' => '<?php
                    /** @var array<string, string>|ArrayAccess<string, string> $test */
                    $test = $_GET["x"];
                    $a = $test["a"] ?? "a";',
                'assertions' => [
                    '$a===' => 'string',
                ],
            ],
            'isArrayOfAnOffsetByAVariableIsKeptAfterAnIfThatNarrowsTheVariableAndHasALoop' => [
                'code' => '<?php
                    final class Parts {
                        /** @var array<string, mixed> */
                        private array $parts = ["select" => [], "join" => []];

                        public function add(string $name, array $part, bool $append = false): void {
                            $isMultiple = is_array($this->parts[$name]) && ! ($name === "join" && ! $append);

                            if ($name === "join") {
                                $new = [];

                                foreach ($part as $k => $v) {
                                    $new[$k] = $v;
                                }

                                $part = $new;
                            }

                            if ($append && $isMultiple) {
                                $this->parts[$name][] = 1;
                            }
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedAssignment'],
                'php_version' => '8.0',
            ],
            'offsetOfAShapeIsRecomputedWhenTheIndexIsNarrowed' => [
                'code' => '<?php
                    function takesString(string $s): void {}

                    /** @return array{foo: string, bar: int} */
                    function getShape(): array {
                        return ["foo" => "a", "bar" => 1];
                    }

                    /** @return "foo"|"bar" */
                    function getKey(): string {
                        return "foo";
                    }

                    $a = getShape();
                    $k = getKey();
                    $narrowed = "";
                    echo (string) $a[$k];
                    if ($k === "foo") {
                        takesString($a[$k]);
                        $narrowed = $a[$k];
                    }

                    $b = getShape();
                    $l = getKey();
                    $narrowedAfterIsset = "";
                    if (isset($b[$l]) && $l === "foo") {
                        takesString($b[$l]);
                        $narrowedAfterIsset = $b[$l];
                    }',
                'assertions' => [
                    '$narrowed===' => 'string',
                    '$narrowedAfterIsset===' => 'string',
                ],
            ],
            'offsetOfAShapeIsRecomputedWhateverTheOrderOfItsNameAndOfTheConditions' => [
                'code' => '<?php
                    /** @return array{foo: string, bar: int} */
                    function getShape(): array {
                        return ["foo" => "a", "bar" => 1];
                    }

                    /** @return "foo"|"bar" */
                    function getKey(): string {
                        return "foo";
                    }

                    $z = getShape();
                    $a = getKey();
                    $indexSortedFirst = "";
                    if (isset($z[$a]) && $a === "foo") {
                        $indexSortedFirst = $z[$a];
                    }

                    $b = getShape();
                    $l = getKey();
                    $indexCheckedFirst = "";
                    if ($l === "foo" && isset($b[$l])) {
                        $indexCheckedFirst = $b[$l];
                    }',
                'assertions' => [
                    '$indexSortedFirst===' => 'string',
                    '$indexCheckedFirst===' => 'string',
                ],
            ],
            'issetOnAnIndexIsKeptWhenTheIndexIsNarrowedInALoop' => [
                'code' => '<?php
                    /** @param string[] $in */
                    function strip(array $in): array {
                        $out = [];
                        foreach (["foo", "bar"] as $key) {
                            if (isset($in[$key])) {
                                if ("foo" === $key) {
                                    $out["baz"] = 1;
                                } else {
                                    $out[$key] = substr($in[$key], 0, -1);
                                }
                            }
                        }
                        return $out;
                    }',
                'assertions' => [],
            ],
            'issetOnANullableObjectOffsetKeepsTheOffsetUnknown' => [
                'code' => '<?php
                    function f(?SimpleXMLElement $a, bool $b): void {
                        if (!(isset($a["r"]) || $b)) {
                            return;
                        }
                        echo isset($a["r"]) && $a["r"] ? 1 : 0;
                    }',
                'assertions' => [],
                'ignored_issues' => ['RiskyTruthyFalsyComparison'],
            ],
            'issetOnAnObjectOrFalseOffsetKeepsTheOffsetUnknown' => [
                'code' => '<?php
                    /** @implements ArrayAccess<string, mixed> */
                    final class SomeObject implements ArrayAccess {
                        public function offsetExists(mixed $offset): bool {
                            return true;
                        }

                        public function offsetGet(mixed $offset): mixed {
                            return 1;
                        }

                        public function offsetSet(mixed $offset, mixed $value): void {}

                        public function offsetUnset(mixed $offset): void {}
                    }

                    function f(SomeObject|false $a, bool $b): void {
                        if (!(isset($a["r"]) || $b)) {
                            return;
                        }
                        echo isset($a["r"]) && $a["r"] ? 1 : 0;
                    }',
                'assertions' => [],
                'ignored_issues' => ['PossiblyFalseReference', 'MixedArgument'],
                'php_version' => '8.0',
            ],
            'issetOnArrayOrArrayAccessNarrowsTheArray' => [
                'code' => '<?php
                    /** @var array<string, string>|ArrayAccess<string, string> $test */
                    $test = $_GET["x"];
                    if (isset($test["a"])) {
                        $a = $test;
                    } else {
                        $a = null;
                    }',
                'assertions' => [
                    '$a===' => 'ArrayAccess<string, string>|array{a: string, ...<string, string>}|null',
                ],
            ],
            'issetOnArrayOrArrayAccessWithKnownOffset' => [
                'code' => '<?php
                    /** @var array{a: int}|ArrayAccess<string, int> $test */
                    $test = $_GET["x"];
                    if (isset($test["a"])) {
                        $a = $test;
                    } else {
                        $a = null;
                    }',
                'assertions' => [
                    '$a===' => 'ArrayAccess<string, int>|array{a: int}|null',
                ],
            ],
            'issetOnMixedArrayOrArrayAccessHasNoValueType' => [
                'code' => '<?php
                    /** @var array<string, mixed>|ArrayAccess<string, mixed> $test */
                    $test = $_GET["x"];
                    if (isset($test["a"])) {
                        $a = $test;
                    } else {
                        $a = null;
                    }',
                'assertions' => [
                    '$a===' => 'ArrayAccess<string, mixed>|array{a: mixed, ...<string, mixed>}|null',
                ],
            ],
            'issetOnNestedArrayOrArrayAccess' => [
                'code' => '<?php
                    /** @var array<string, array<string, int>|ArrayAccess<string, int>> $test */
                    $test = $_GET["x"];
                    if (isset($test["a"]["b"])) {
                        $a = $test["a"];
                    } else {
                        $a = null;
                    }',
                'assertions' => [
                    '$a===' => 'ArrayAccess<string, int>|array{b: int, ...<string, int>}|null',
                ],
            ],
            'issetOnANestedOffsetOfAnArrayOrArrayAccessDefinesTheOuterOffset' => [
                'code' => '<?php
                    /** @var array{a?: array{b?: int}}|ArrayAccess<string, int> $test */
                    $test = $_GET["x"];
                    if (isset($test["a"]["b"])) {
                        $a = $test["a"]["b"];
                    } else {
                        $a = 0;
                    }',
                'assertions' => [
                    '$a===' => 'int',
                ],
            ],
            'issetOnANestedOffsetOfAnArrayOrArrayAccessKeepsTheValueTypeOfTheObject' => [
                'code' => '<?php
                    /** @var array{a?: array{b?: int}}|ArrayAccess<string, array{b?: string}> $test */
                    $test = $_GET["x"];
                    if (isset($test["a"]["b"])) {
                        $a = $test["a"]["b"];
                    } else {
                        $a = 0;
                    }',
                'assertions' => [
                    '$a===' => 'int|string',
                ],
            ],
            'issetOnArrayOrArrayAccessKeepsTheValueTypeOfTheObject' => [
                'code' => '<?php
                    /** @var array{a?: int}|ArrayAccess<string, string> $test */
                    $test = $_GET["x"];
                    if (isset($test["a"])) {
                        $a = $test["a"];
                    } else {
                        $a = 0;
                    }',
                'assertions' => [
                    '$a===' => 'int|null|string',
                ],
            ],
            'destructuringAndForeachIntoAnArrayAccessItemHaveNoValueToCheck' => [
                'code' => '<?php
                    /** @implements ArrayAccess<string, list<int>> */
                    final class Bag implements ArrayAccess {
                        #[Override]
                        public function offsetExists(mixed $offset): bool {
                            return true;
                        }

                        /** @return list<int> */
                        #[Override]
                        public function offsetGet(mixed $offset): array {
                            return [];
                        }

                        /** @param list<int> $value */
                        #[Override]
                        public function offsetSet(mixed $offset, mixed $value): void {}

                        #[Override]
                        public function offsetUnset(mixed $offset): void {}
                    }

                    function fill(Bag $bag): void {
                        [$bag["a"]] = [[1]];

                        foreach ([[2]] as $bag["b"]) {
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.3',
            ],
            'nullOffsetOfAnObjectThatAcceptsItInArrayAccess' => [
                'code' => '<?php
                    /** @implements ArrayAccess<string|null, int> */
                    final class Bag implements ArrayAccess {
                        #[Override]
                        public function offsetExists(mixed $offset): bool {
                            return $offset === null;
                        }

                        #[Override]
                        public function offsetGet(mixed $offset): int {
                            return $offset === null ? 1 : 2;
                        }

                        #[Override]
                        public function offsetSet(mixed $offset, mixed $value): void {}

                        #[Override]
                        public function offsetUnset(mixed $offset): void {}
                    }

                    function f(Bag $bag, ?string $key): int {
                        $bag[null] = 3;
                        unset($bag[null]);

                        return $bag[null] + $bag[$key] + (isset($bag[null]) ? 1 : 0);
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.3',
            ],
            'nullOffsetOfAnArrayAccessInterfaceOrItsChild' => [
                'code' => '<?php
                    /** @extends ArrayAccess<string|null, int> */
                    interface Bag extends ArrayAccess {}

                    /** @param ArrayAccess<string|null, int> $a */
                    function f(ArrayAccess $a, Bag $bag, Countable&ArrayAccess $c): void {
                        echo $a[null];
                        echo $bag[null];
                        echo (string) $c[null];
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'emptyNestedOffsetIsForgottenWhenItsParentOffsetIsAssigned' => [
                'code' => '<?php
                    function g(array $m, string $l, string $p): string {
                        if (!empty($m[$l][$p])) {
                            return $p;
                        }

                        $m[$l] = array_merge($m[$l] ?? [], ["x" => 1]);

                        if (!empty($m[$l][$p])) {
                            return $p;
                        }

                        return $p;
                    }

                    final class B {
                        /** @var array */
                        protected $m = [];

                        public function h(string $l, string $p): string {
                            if (!empty($this->m[$l][$p])) {
                                return $p;
                            }

                            $this->m[$l] = ["x" => 1];

                            if (!empty($this->m[$l][$p])) {
                                return $p;
                            }

                            return $p;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedArgument', 'MixedAssignment', 'MixedArrayAccess', 'MixedArrayAssignment', 'MixedArrayOffset'],
                'php_version' => '8.0',
            ],
            'emptyOffsetOfArrayKeepsValueType' => [
                'code' => '<?php
                    /** @var array<string, int> $arr */
                    $arr = [];
                    /** @var array<int, string> $list */
                    $list = [];

                    $v = empty($arr["x"]) ? $arr["x"] : 0;
                    $w = empty($list[0]) ? $list[0] : "";
                    $x = !empty($arr["x"]) ? 0 : $arr["x"];',
                'assertions' => [
                    '$v' => 'int',
                    '$w' => 'string',
                    '$x' => 'int',
                ],
                'ignored_issues' => ['RiskyTruthyFalsyComparison'],
            ],
            'emptyNestedOffsetOfArrayKeepsValueType' => [
                'code' => '<?php
                    /** @var array<string, array<string, int>> $arr */
                    $arr = [];

                    $v = empty($arr["x"]["y"]) ? $arr["x"]["y"] : 0;',
                'assertions' => [
                    '$v' => 'int',
                ],
                'ignored_issues' => ['RiskyTruthyFalsyComparison'],
            ],
            'destructureNullableShapeByKey' => [
                'code' => '<?php
                    /** @return array{x: int}|null */
                    function f(): ?array {
                        return null;
                    }

                    ["x" => $a] = f();',
                'assertions' => ['$a' => 'int|null'],
            ],
            'destructureNullableShapeByKeyKeepsTheTypesOfTheOtherShapes' => [
                'code' => '<?php
                    /** @return array{x: int}|array{x: string}|null */
                    function f(): ?array {
                        return null;
                    }

                    ["x" => $a] = f();',
                'assertions' => ['$a' => 'int|null|string'],
            ],
            'directWriteToAnArrayAccessObjectDoesNotCallOffsetGet' => [
                'code' => '<?php
                    /** @implements ArrayAccess<string, int> */
                    final class Box implements ArrayAccess {
                        public function offsetExists(mixed $offset): bool { return true; }
                        /** @deprecated */
                        public function offsetGet(mixed $offset): int { return 1; }
                        public function offsetSet(mixed $offset, mixed $value): void {}
                        public function offsetUnset(mixed $offset): void {}
                    }

                    function f(Box $b): void {
                        $b["k"] = 1;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'itemOfAnObjectNotImplementingArrayAccessDoesNotLeakIntoTheItemOfAUnion' => [
                'code' => '<?php
                    final class Markup {}

                    /** @return ArrayObject<string, int>|Markup */
                    function u() {
                        return new Markup();
                    }

                    $u = u();
                    /** @psalm-suppress PossiblyInvalidArrayAccess */
                    $v = $u["k"];',
                'assertions' => [
                    '$v===' => 'int',
                ],
            ],
            'arrayAccessOnAnIntersectionWithArrayAccess' => [
                'code' => '<?php
                    interface I {}

                    /** @param I&ArrayAccess<string, int> $x */
                    function f($x): ?int {
                        return $x["k"];
                    }',
            ],
            'falseOffsetOfAnArrayAccessObjectIsNotReportedAsZero' => [
                'code' => '<?php
                    /** @implements ArrayAccess<int|false, string> */
                    final class Bag implements ArrayAccess {
                        #[Override]
                        public function offsetExists(mixed $offset): bool {
                            return true;
                        }

                        #[Override]
                        public function offsetGet(mixed $offset): string {
                            return "x";
                        }

                        #[Override]
                        public function offsetSet(mixed $offset, mixed $value): void {}

                        #[Override]
                        public function offsetUnset(mixed $offset): void {}
                    }

                    function read(Bag $bag): string {
                        $key = array_search("a", ["a"]);
                        $bag[$key] = "y";
                        unset($bag[$key]);

                        return $bag[$key];
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'offsetNarrowedInIfAndForgottenInElseIsNotNarrowedAfterTheIf' => [
                'code' => '<?php
                    /** @param array<string, mixed> $options */
                    function f(array $options): string {
                        if ($options["with_minutes"]) {
                            echo "m";
                        }
                        if ("single_text" === $options["widget"]) {
                            if (null !== $options["reference_date"]) {
                                echo "r";
                            }
                        } else {
                            if (isset($options["invalid_message_parameters"])) {
                                foreach ($options["hours"] as $_) {
                                    echo "h";
                                }
                            }
                            if ($options["with_minutes"]) {
                                echo "m";
                            }
                        }
                        return "text" === $options["widget"] ? "a" : "b";
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedAssignment'],
            ],
            'offsetNarrowedInIfAndForgottenInElseifIsNotNarrowedAfterTheIf' => [
                'code' => '<?php
                    /** @param array<string, mixed> $o */
                    function f(array $o, bool $b): string {
                        if ($o["m"]) {
                            echo "m";
                        }
                        if ("single_text" === $o["w"]) {
                            if (null !== $o["r"]) {
                                echo "r";
                            }
                        } elseif ($b) {
                            if (isset($o["i"])) {
                                foreach ($o["h"] as $_) {
                                    echo "h";
                                }
                            }
                            if ($o["m"]) {
                                echo "m";
                            }
                        } else {
                            if ($o["m"]) {
                                echo "m";
                            }
                        }
                        return "text" === $o["w"] ? "a" : "b";
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedAssignment'],
            ],
            'offsetNarrowedInIfAndForgottenInElseIsNotNarrowedAfterTheIfInLoop' => [
                'code' => '<?php
                    /** @param array<string, mixed> $o */
                    function f(array $o): void {
                        $i = 0;
                        while ($i < 3) {
                            $i++;
                            if ($o["m"]) {
                                echo "m";
                            }
                            if ("single_text" === $o["w"]) {
                                if (null !== $o["r"]) {
                                    echo "r";
                                }
                            } else {
                                if (isset($o["i"])) {
                                    foreach ($o["h"] as $_) {
                                        echo "h";
                                    }
                                }
                                if ($o["m"]) {
                                    echo "m";
                                }
                            }
                            echo "text" === $o["w"] ? "a" : "b";
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedAssignment'],
            ],
            'propertyOffsetNarrowedInIfAndForgottenInElseIsNotNarrowedAfterTheIf' => [
                'code' => '<?php
                    final class Q {
                        /** @var array<string, mixed> */
                        private array $o = [];

                        public function f(): string {
                            if ($this->o["m"]) {
                                echo "m";
                            }
                            if ("single_text" === $this->o["w"]) {
                                if (null !== $this->o["r"]) {
                                    echo "r";
                                }
                            } else {
                                if (isset($this->o["i"])) {
                                    foreach ($this->o["h"] as $_) {
                                        echo "h";
                                    }
                                }
                                if ($this->o["m"]) {
                                    echo "m";
                                }
                            }
                            return "text" === $this->o["w"] ? "a" : "b";
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedAssignment'],
            ],
            'offsetWithAVariableKeyKeepsWhatEveryBranchLeavesIt' => [
                'code' => '<?php
                    /** @param array<string, mixed> $config */
                    function implicitElse(array $config, string $key): void {
                        if (!is_array($config[$key])) {
                            $config[$key] = [$config[$key]];
                        }

                        $t = $config[$key];
                        /** @psalm-check-type-exact $t = array<array-key, mixed> */
                        $config[$key][] = 1;
                    }

                    /** @param array<string, mixed> $config */
                    function inElseif(array $config, string $key, bool $b): void {
                        if (!is_array($config[$key])) {
                            $config[$key] = [$config[$key]];
                        } elseif ($b) {
                            $config[$key] = [];
                        }

                        $t = $config[$key];
                        /** @psalm-check-type-exact $t = array<array-key, mixed> */
                    }

                    final class C {
                        /** @var array<string, mixed> */
                        public array $config = [];

                        public function f(string $key): void {
                            if (!is_array($this->config[$key])) {
                                $this->config[$key] = [$this->config[$key]];
                            }

                            $t = $this->config[$key];
                            /** @psalm-check-type-exact $t = array<array-key, mixed> */
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'offsetIsUnknownAfterABranchChangingWhatItDependsOn' => [
                'code' => '<?php
                    /** @param array<string, mixed> $a */
                    function keyChanged(array $a, string $k, bool $c): void {
                        if (is_int($a[$k])) {
                            if ($c) {
                                $k = "zz";
                            }
                            $t = $a[$k];
                            /** @psalm-check-type-exact $t = mixed */
                        }
                    }

                    /** @param array<string, mixed> $a */
                    function arrayReassigned(array $a, string $k, bool $c): void {
                        if (is_int($a[$k])) {
                            if ($c) {
                                $a = ["other" => "s"];
                            }
                            $t = $a[$k];
                            /** @psalm-check-type-exact $t = mixed */
                        }
                    }

                    /** @param array<string, int|string> $a */
                    function offsetUnset(array $a, string $k, bool $c): void {
                        if (!is_int($a[$k])) {
                            return;
                        }
                        if ($c) {
                            unset($a[$k]);
                        }
                        $t = $a[$k];
                        /** @psalm-check-type-exact $t = int|string */
                    }

                    final class O {
                        public ?int $p = null;
                    }

                    function objectReassigned(O $o, bool $c): void {
                        if ($o->p !== null) {
                            if ($c) {
                                $o = new O();
                            }
                            $t = $o->p;
                            /** @psalm-check-type-exact $t = int|null */
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedAssignment', 'PossiblyUndefinedArrayOffset'],
                'php_version' => '8.0',
            ],
            'propertyIsKeptAfterABranchOnlyNarrowingItsObject' => [
                'code' => '<?php
                    final class Foo {
                        public function bar(): void {}
                    }

                    class S {
                        public ?Foo $r = null;
                    }

                    final class M extends S {
                        public bool $h = false;
                    }

                    function f(S $s): void {
                        $s->r = new Foo();
                        if ($s instanceof M) {
                            $s->h = true;
                        }
                        $s->r->bar();
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'offsetWithAVariableKeyIsUnknownAfterABranchReassigningItsArray' => [
                'code' => '<?php
                    /** @param array<string, mixed> $config */
                    function f(array $config, string $key): void {
                        if (!is_array($config[$key])) {
                            $config[$key] = [$config[$key]];
                        } else {
                            $config = [$key => 5];
                        }

                        $t = $config[$key];
                        /** @psalm-check-type-exact $t = 5|list{mixed}|mixed */
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedAssignment'],
                'php_version' => '8.0',
            ],
            'offsetAssignedInElseKeepsItsTypeAfterTheIf' => [
                'code' => '<?php
                    /** @param array<string, mixed> $o */
                    function f(array $o): string {
                        if ($o["m"]) {
                            echo "m";
                        }
                        if (is_string($o["w"])) {
                            if (null !== $o["r"]) {
                                echo "r";
                            }
                        } else {
                            $o["w"] = "x";
                            if (isset($o["i"])) {
                                foreach ($o["h"] as $_) {
                                    echo "h";
                                }
                            }
                            if ($o["m"]) {
                                echo "m";
                            }
                        }
                        return $o["w"];
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedAssignment'],
            ],
            'offsetOfAnArrayUnionCombinedWithAShapeDeclaringItIsNotOptional' => [
                'code' => '<?php
                    function f(array $a, bool $c): int {
                        $x = $c ? $a + ["alpha" => 0] : ["red" => 1, "alpha" => 2];
                        return (int) $x["red"];
                    }',
            ],
            'offsetOfAnIntArrayUnionCombinedWithAShapeDeclaringItIsNotOptional' => [
                'code' => '<?php
                    /** @param array<array-key, int> $a */
                    function f(array $a, bool $c): int {
                        $x = $c ? $a + ["alpha" => 0] : ["red" => 1, "alpha" => 2];
                        return $x["red"];
                    }',
            ],
            'offsetOfUnsealedArrayNarrowedInIfWithoutElseIsNotOptional' => [
                'code' => '<?php
                    function f(array $a): string {
                        if ($a["c"] === null) {
                            if (is_string($a["k"])) {
                                echo "x";
                            }
                            return (string) $a["k"];
                        }
                        return "";
                    }',
            ],
            'offsetOfUnsealedArrayNarrowedInIfAndElseifIsNotOptional' => [
                'code' => '<?php
                    function f(array $a): string {
                        if ($a["c"] === null) {
                            if (is_string($a["k"])) {
                                echo "x";
                            } elseif (is_int($a["j"])) {
                                echo "y";
                            }
                            return (string) $a["k"] . (string) $a["j"];
                        }
                        return "";
                    }',
            ],
            'offsetOfUnsealedArrayNarrowedInLoopIsNotOptional' => [
                'code' => '<?php
                    function f(array $a): void {
                        if ($a["c"] === null) {
                            foreach ([1, 2] as $_) {
                                if (is_string($a["k"])) {
                                    echo "x";
                                }
                            }
                        }
                    }',
            ],
            'offsetOfGenericArrayAssignedItsValueTypeInIfWithoutElseIsNotOptional' => [
                'code' => '<?php
                    /** @param array<string, int> $a */
                    function f(array $a, bool $b): int {
                        $a["c"] = 1;
                        if ($b) {
                            $a["k"] = 5;
                        }
                        return $a["k"];
                    }',
            ],
            'offsetOfUnsealedArrayComparedInIfWithoutElseStaysDefined' => [
                'code' => '<?php
                    /** @param array{c: null, ...<string, int>} $a */
                    function f(array $a): int {
                        if ($a["k"] === 5) {
                            echo "x";
                        }
                        return $a["k"];
                    }',
            ],
            'nullCoalesceOnArrayAccessObjectIsNeverNull' => [
                'code' => <<<'PHP'
                    <?php
                    final class Foo {}

                    /** @implements ArrayAccess<string, Foo> */
                    final class FooMap implements ArrayAccess
                    {
                        public function offsetSet(mixed $offset, mixed $value): void {}
                        public function offsetUnset(mixed $offset): void {}
                        public function offsetExists(mixed $offset): bool { return false; }
                        public function offsetGet(mixed $offset): ?Foo { return null; }
                    }

                    $map = new FooMap();
                    $key = (string) rand();
                    $foo = $map[$key] ?? new Foo();
                    $int = $map[$key] ?? 5;
                    $null = $map[$key] ?? null;
                    PHP,
                'assertions' => [
                    '$foo===' => 'Foo',
                    '$int===' => '5|Foo',
                    '$null===' => 'Foo|null',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'offsetOfANullableArrayIsReadInsideTheGuardsThatNarrowIt' => [
                'code' => '<?php
                    /** @param array<string, list<string>>|null $lists */
                    function guarded(?array $lists): int {
                        if (isset($lists["codes"]) && is_array($lists["codes"])) {
                            return count($lists["codes"]);
                        }

                        if ($lists !== null && is_array($lists["codes"] ?? null)) {
                            return 1;
                        }

                        return 0;
                    }',
                'assertions' => [],
                'ignored_issues' => ['RedundantConditionGivenDocblockType', 'DocblockTypeContradiction'],
                'php_version' => '8.1',
            ],
            'coalesceOnAnIntKeyThatMayBeMissingKeepsTheFoundType' => [
                'code' => '<?php
                    $a = [1 => "Hello"];
                    $s = $a[rand(0, 1) ? 1 : 0] ?? "World";',
                'assertions' => [
                    '$s===' => "'Hello'|'World'",
                ],
            ],
            'coalesceOnAStringKeyThatMayBeMissingKeepsTheFoundType' => [
                'code' => '<?php
                    $a = ["x" => 5];
                    $s = $a[rand(0, 1) ? "x" : "y"] ?? 0;',
                'assertions' => [
                    '$s===' => '0|5',
                ],
            ],
            'issetOnAKeyThatMayBeMissingIsNotRedundant' => [
                'code' => '<?php
                    $a = [1 => "Hello"];
                    if (isset($a[rand(0, 1) ? 1 : 0])) {
                        $s = $a[1];
                    } else {
                        $s = null;
                    }',
                'assertions' => [
                    '$s===' => "'Hello'|null",
                ],
            ],
            'coalesceOnAKeyOfOneShapeOfAUnionKeepsItsType' => [
                'code' => '<?php
                    /** @param array{a: int}|array{b: string} $x */
                    function f(array $x): int {
                        return $x["a"] ?? 0;
                    }',
            ],
            'appendToASplDoublyLinkedList' => [
                'code' => '<?php
                    /** @var SplDoublyLinkedList<int> $dll */
                    $dll = new SplDoublyLinkedList();
                    $dll[] = 42;
                    $dll[0] = 43;

                    $queue = new SplQueue();
                    $queue[] = "a";',
            ],
            'coalesceOnOptionalKeysWithComputedOffset' => [
                'code' => '<?php
                    /** @return array{a?: int, b?: int} */
                    function getKeys(): array {
                        return [];
                    }

                    /** @return "a"|"b" */
                    function getKey(): string {
                        return "a";
                    }

                    $keys = getKeys();
                    $call = $keys[getKey()] ?? "none";
                    $concat = $keys[getKey() . ""] ?? 0;
                    $nullable = rand(0, 1) ? getKey() : null;
                    $nested = $keys[$nullable ?? "a"] ?? 0;
                ',
                'assertions' => [
                    '$call===' => "'none'|int",
                    '$concat' => 'int',
                    '$nested' => 'int',
                ],
            ],
            'coalesceOffsetAssignmentsStayInContext' => [
                'code' => '<?php
                    /** @var array<int, int> $arr */
                    $arr = [];
                    $value = $arr[($key = rand(0, 9)) + 1] ?? 1;
                    $used = $key;
                ',
                'assertions' => [
                    '$value' => 'int',
                    '$used' => 'int<0, 9>',
                ],
            ],
            'missingOffsetPassedByReferenceIsNull' => [
                'code' => '<?php
                    /**
                     * @param array<string, int>|null $dst
                     * @param-out array<string, int> $dst
                     */
                    function fill(?array &$dst): void {
                        $dst = ["n" => 1];
                    }

                    /** @param list<int> $dst */
                    function sortIt(array &$dst): void {}

                    function f(): void {
                        $empty = [];
                        fill($empty["x"]);

                        $nested = [];
                        fill($nested["a"]["b"]);

                        $shape = ["present" => [1]];
                        fill($shape["missing"]);
                        sortIt($shape["present"]);
                    }',
            ],
            'issetArrayAccessOffsetHasTheValueTypeOfTheObject' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @implements ArrayAccess<int, T>
                     */
                    abstract class Coll implements ArrayAccess {
                        /** @return T|null */
                        abstract public function offsetGet(mixed $offset): mixed;
                    }

                    /** @param ArrayAccess<string, stdClass> $a */
                    function f(ArrayAccess $a): bool {
                        return !empty($a["x"]);
                    }

                    /** @param ArrayObject<int, stdClass> $a */
                    function h(ArrayObject $a): bool {
                        return !empty($a[0]);
                    }

                    /** @param Coll<stdClass> $c */
                    function i(Coll $c): bool {
                        return !empty($c[0]);
                    }

                    function j(ArrayAccess $a): bool {
                        return !empty($a[0]);
                    }

                    /** @param mixed $owner */
                    function g($owner): bool {
                        return empty($owner["type"]) || empty($owner["id"]);
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'destructureArrayAccessObjectThatIsNotTraversable' => [
                'code' => '<?php
                    /** @implements ArrayAccess<int, string> */
                    final class Bag implements ArrayAccess {
                        /** @param int $offset */
                        public function offsetExists($offset): bool { return true; }
                        /** @param int $offset */
                        public function offsetGet($offset): string { return ""; }
                        /**
                         * @param ?int $offset
                         * @param string $value
                         */
                        public function offsetSet($offset, $value): void {}
                        /** @param int $offset */
                        public function offsetUnset($offset): void {}
                    }

                    [$first, $second] = new Bag();',
                'assertions' => [
                    '$first===' => 'string',
                    '$second===' => 'string',
                ],
            ],
            'destructureArrayAccessObjectOfSelf' => [
                'code' => '<?php
                    /** @implements ArrayAccess<int, self> */
                    final class Bag implements ArrayAccess {
                        /** @param int $offset */
                        public function offsetExists($offset): bool { return true; }
                        /** @param int $offset */
                        public function offsetGet($offset): self { return $this; }
                        /**
                         * @param ?int $offset
                         * @param self $value
                         */
                        public function offsetSet($offset, $value): void {}
                        /** @param int $offset */
                        public function offsetUnset($offset): void {}
                        public function value(): int { return 1; }
                    }

                    function f(Bag $b): int {
                        [$v] = $b;
                        return $v->value();
                    }

                    [$first] = new Bag();',
                'assertions' => [
                    '$first===' => 'Bag',
                ],
            ],
            'destructureArrayAccessObjectOfSelfDeclaredInParent' => [
                'code' => '<?php
                    /** @implements ArrayAccess<int, self> */
                    class Bag implements ArrayAccess {
                        /** @param int $offset */
                        public function offsetExists($offset): bool { return true; }
                        /** @param int $offset */
                        public function offsetGet($offset): self { return $this; }
                        /**
                         * @param ?int $offset
                         * @param self $value
                         */
                        public function offsetSet($offset, $value): void {}
                        /** @param int $offset */
                        public function offsetUnset($offset): void {}
                    }

                    final class SubBag extends Bag {}

                    [$first] = new SubBag();',
                'assertions' => [
                    '$first===' => 'Bag',
                ],
            ],
            'destructureArrayAccessObjectOfStatic' => [
                'code' => '<?php
                    /** @implements ArrayAccess<int, static> */
                    abstract class Bag implements ArrayAccess {
                        public function offsetExists(mixed $offset): bool { return true; }
                        public function offsetGet(mixed $offset): static { return $this; }
                        public function offsetSet(mixed $offset, mixed $value): void {}
                        public function offsetUnset(mixed $offset): void {}
                    }

                    final class SubBag extends Bag {}

                    [$first] = new SubBag();',
                'assertions' => [
                    '$first===' => 'SubBag',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'destructureTraversableArrayAccessInterfaceOfSelf' => [
                'code' => '<?php
                    /**
                     * @extends ArrayAccess<int, self>
                     * @extends Traversable<int, self>
                     */
                    interface Bag extends ArrayAccess, Traversable {
                        public function value(): int;
                    }

                    function f(Bag $b): int {
                        [$v] = $b;
                        return $v->value();
                    }',
            ],
            'allowEmptyList' => [
                'code' => '<?php
                    function test(): void {
                        $a = [];
                        /** @psalm-suppress RedundantFunctionCall */
                        $a = array_values($a);

                        /** @psalm-suppress RedundantConditionGivenDocblockType, NoValue, NullArrayOffset, PossiblyNullArrayOffset */
                        if (empty($a)
                            || count($a) > 1
                            || empty($a[array_key_first($a)])
                        ) {
                        }
                    }',
            ],
            'testBuildList' => [
                'code' => '<?php
                    $a = [];

                    if (random_int(0, 1)) {
                        $a []= 0;
                    }

                    if (random_int(0, 1)) {
                        $a []= 1;
                    }

                    $pre = $a;

                    $a []= 2;

                ',
                'assertions' => [
                    '$pre===' => 'list{0?: 0|1, 1?: 1}',
                    '$a===' => 'list{0: 0|1|2, 1?: 1|2, 2?: 2}',
                ],
            ],
            'testBuildListOther' => [
                'code' => '<?php
                    $list = [];
                    $entropy = random_int(0, 2);
                    if ($entropy === 0) {
                        $list[] = "A";
                    } elseif ($entropy === 1) {
                        $list[] = "B";
                    }

                    $list[] = "C";
                ',
                'assertions' => [
                    '$list===' => "list{0: 'A'|'B'|'C', 1?: 'C'}",
                ],
            ],
            'testBuildList3' => [
                'code' => '<?php
                    $a = [0];
                    if (random_int(0, 1)) {
                        $a []= 1;
                    }
                    if (random_int(0, 1)) {
                        $a []= 2;
                    }
                    $a []= 3;
                ',
                'assertions' => [
                    '$a===' => "list{0: 0, 1: 1|2|3, 2?: 2|3, 3?: 3}",
                ],
            ],
            'instanceOfStringOffset' => [
                'code' => '<?php
                    class A {
                        public function fooFoo(): void { }
                    }
                    function bar (array $a): void {
                        if ($a["a"] instanceof A) {
                            $a["a"]->fooFoo();
                        }
                    }',
            ],
            'instanceOfIntOffset' => [
                'code' => '<?php
                    class A {
                        public function fooFoo(): void { }
                    }
                    function bar (array $a): void {
                        if ($a[0] instanceof A) {
                            $a[0]->fooFoo();
                        }
                    }',
            ],
            'nonEmptyStringAccess' => [
                'code' => '<?php
                    /** @var non-empty-string $a */
                    $a = "blah";
                    $b = $a[0];',
                'assertions' => [
                    '$b===' => 'non-empty-string',
                ],
            ],
            'notEmptyStringOffset' => [
                'code' => '<?php
                    /**
                     * @param  array<string>  $a
                     */
                    function bar (array $a): string {
                        if ($a["bat"]) {
                            return $a["bat"];
                        }

                        return "blah";
                    }',
            ],
            'issetPropertyStringOffset' => [
                'code' => '<?php
                    class A {
                        /** @var array<string, string> */
                        public $arr = [];
                    }
                    $a = new A();
                    if (!isset($a->arr["bat"]) || strlen($a->arr["bat"])) { }',
            ],
            'issetPropertyStringOffsetUndefinedClass' => [
                'code' => '<?php
                    /** @psalm-suppress UndefinedClass */
                    $a = new A();
                    /**
                     * @psalm-suppress UndefinedClass
                     */
                    if (!isset($a->arr["bat"]) || strlen($a->arr["bat"])) { }',
                'assertions' => [],
                'ignored_issues' => ['MixedArgument', 'MixedArrayAccess'],
            ],
            'notEmptyIntOffset' => [
                'code' => '<?php
                    /**
                     * @param  array<string>  $a
                     */
                    function bar (array $a): string {
                        if ($a[0]) {
                            return $a[0];
                        }

                        return "blah";
                    }',
            ],
            'ignorePossiblyNullArrayAccess' => [
                'code' => '<?php
                    $a = rand(0, 1) ? [1, 2] : null;
                    echo $a[0];',
                'assertions' => [],
                'ignored_issues' => ['PossiblyNullArrayAccess'],
            ],
            'ignoreEmptyArrayAccess' => [
                'code' => '<?php
                    $arr = [];
                    $x = $arr[0];
                    if (isset($arr[0]) && $arr[0]) { }',
                'assertions' => [
                    '$x' => 'mixed',
                ],
                'ignored_issues' => ['EmptyArrayAccess', 'MixedAssignment'],
            ],
            'objectLikeWithoutKeys' => [
                'code' => '<?php
                    function takesInt(int $i): void {}
                    function takesString(string $s): void {}
                    function takesBool(bool $b): void {}

                    /**
                     * @param array{int, string, bool} $b
                     */
                    function a(array $b): void {
                        takesInt($b[0]);
                        takesString($b[1]);
                        takesBool($b[2]);
                    }',
            ],
            'stringKeysWithInts' => [
                'code' => '<?php
                    $array = ["01" => "01", "02" => "02"];

                    foreach ($array as $key => $value) {
                        $len = strlen($key);
                    }',
            ],
            'listAssignmentKeyOffset' => [
                'code' => '<?php
                    $a = [];
                    list($a["foo"]) = explode("+", "a+b");
                    echo $a["foo"];',
            ],
            'objectlikeOptionalNamespacedParam' => [
                'code' => '<?php
                    namespace N;

                    /**
                     * @psalm-param array{key?:string} $p
                     */
                    function f(array $p): void
                    {
                        echo isset($p["key"]) ? $p["key"] : "";
                    }',
            ],
            'unsetTKeyedArrayOffset' => [
                'code' => '<?php
                    $x1 = ["a" => "value"];
                    unset($x1["a"]);

                    $x2 = ["a" => "value", "b" => "value"];
                    unset($x2["a"]);

                    $x3 = ["a" => "value", "b" => "value"];
                    $k = "a";
                    unset($x3[$k]);',
                'assertions' => [
                    '$x1===' => 'array<never, never>',
                    '$x2===' => "array{b: 'value'}",
                    '$x3===' => "array{b: 'value'}",
                ],
            ],
            'possiblyUndefinedArrayOffsetKeyedArray' => [
                'code' => '<?php
                    $d = [];
                    if (!rand(0,1)) {
                        $d[0] = "a";
                    }

                    $x = $d[0];',
                'assertions' => [
                    '$x===' => '\'a\'',
                ],
                'ignored_issues' => ['PossiblyUndefinedArrayOffset'],
            ],
            'domNodeListAccessible' => [
                'code' => '<?php
                    $doc = new DOMDocument();
                    $doc->loadXML("<node key=\"value\"/>");
                    $e = $doc->getElementsByTagName("node")[0];',
                'assertions' => [
                    '$e' => 'DOMElement|null',
                ],
            ],
            'getOnArrayAccess' => [
                'code' => '<?php
                    /** @param ArrayAccess<int, string> $a */
                    function foo(ArrayAccess $a) : string {
                        return $a[0];
                    }',
            ],
            'mixedKeyMixedOffset' => [
                'code' => '<?php
                    function example(array $x, $y) : void {
                        echo $x[$y];
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedArgument', 'MixedArrayOffset', 'MissingParamType'],
            ],
            'suppressPossiblyUndefinedStringArrayOffset' => [
                'code' => '<?php
                    /** @var array{a?:string} */
                    $entry = ["a"];

                    ["a" => $elt] = $entry;
                    strlen($elt);
                    strlen($entry["a"]);',
                'assertions' => [],
                'ignored_issues' => ['PossiblyUndefinedArrayOffset'],
            ],
            'noRedundantConditionOnMixedArrayAccess' => [
                'code' => '<?php
                    /** @var array<int, int> */
                    $b = [];

                    /** @var array<int, int> */
                    $c = [];

                    /** @var array<int, mixed> */
                    $d = [];

                    if (!empty($d[0]) && !isset($c[$d[0]])) {
                        if (isset($b[$d[0]])) {}
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedArrayOffset'],
            ],
            'noEmptyArrayAccessInLoop' => [
                'code' => '<?php
                    /**
                     * @psalm-suppress MixedAssignment
                     * @psalm-suppress MixedOperand
                     * @psalm-suppress MixedArrayAssignment
                     * @param mixed[] $line
                     */
                    function _renderCells(array $line): void {
                      foreach ($line as $cell) {
                        $cellOptions = [];
                        if (is_array($cell)) {
                          $cellOptions = $cell[1];
                        }
                        if (isset($cellOptions[0])) {
                          $cellOptions[0] = $cellOptions[0] . "b";
                        } else {
                          $cellOptions[0] = "b";
                        }
                      }
                    }',
            ],
            'arrayAccessPropertyAssertion' => [
                'code' => '<?php
                    class A {}
                    class B extends A {
                        /** @var array<int, string> */
                        public $arr = [];
                    }

                    /** @var array<A> */
                    $as = [];

                    if (!$as
                        || !$as[0] instanceof B
                        || !$as[0]->arr
                    ) {
                        return null;
                    }

                    $b = $as[0]->arr;',
            ],
            'arrayAccessAfterPassByref' => [
                'code' => '<?php
                    class Arr {
                        /**
                         * @param mixed $c
                         * @return mixed
                         */
                        public static function pull(array &$a, string $b, $c = null) {
                            return $a[$b] ?? $c;
                        }
                    }

                    function _renderButton(array $settings): void {
                        Arr::pull($settings, "a", true);

                        if (isset($settings["b"])) {
                            Arr::pull($settings, "b");
                        }

                        if (isset($settings["c"])) {}
                    }',
            ],
            'arrayKeyChecks' => [
                'code' => '<?php
                    /**
                     * @param  string[] $arr
                     */
                    function foo(array $arr) : void {
                        if (!$arr) {
                            return;
                        }

                        foreach ($arr as $i => $_) {}

                        if ($i === "hello") {}
                        if ($i !== "hello") {}
                        if ($i === 5) {}
                        if ($i !== 5) {}
                        if (is_string($i)) {}
                        if (is_int($i)) {}

                        foreach ($arr as $i => $_) {}

                        if ($i === "hell") {
                            $i = "hellp";
                        }

                        if ($i === "hel") {}
                    }',
            ],
            'arrayKeyChecksAfterDefinition' => [
                'code' => '<?php
                    /**
                     * @param  string[] $arr
                     */
                    function foo(array $arr) : void {
                        if (!$arr) {
                            return;
                        }

                        foreach ($arr as $i => $_) {}

                        if ($i === "hell") {
                            $i = "hellp";
                        }

                        if ($i === "hel") {}
                    }',
            ],
            'allowMixedTypeCoercionArrayKeyAccess' => [
                'code' => '<?php
                    /**
                     * @param array<array-key, int> $i
                     * @param array<int, string> $arr
                     */
                    function foo(array $i, array $arr) : void {
                        foreach ($i as $j => $k) {
                            echo $arr[$j];
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedArrayTypeCoercion'],
            ],
            'allowNegativeStringOffset' => [
                'code' => '<?php
                    $a = "hello";
                    echo $a[-5];
                    echo $a[-4];
                    echo $a[-3];
                    echo $a[-2];
                    echo $a[-1];
                    echo $a[0];
                    echo $a[1];
                    echo $a[2];
                    echo $a[3];
                    echo $a[4];',
            ],
            'arrayAccessAfterPossibleGeneralisation' => [
                'code' => '<?php
                    function getArray() : array { return []; }
                    $params = array(
                        "a" => 1,
                        "b" => [
                            "c" => "a",
                        ]
                    );

                    if (rand(0, 1)) {
                        $params = getArray();
                    }

                    echo $params["b"]["c"];',
                'assertions' => [],
                'ignored_issues' => ['MixedArrayAccess', 'MixedArgument'],
            ],
            'arrayAccessOnObjectWithNullGet' => [
                'code' => '<?php
                    $array = new C([]);
                    $array["key"] = [];
                    /** @psalm-suppress PossiblyInvalidArrayAssignment */
                    $array["key"][] = "testing";

                    $c = isset($array["foo"]) ? $array["foo"] : null;

                    /**
                     * @psalm-suppress MissingTemplateParam
                     */
                    class C implements ArrayAccess
                    {
                        /**
                         * @var array<C|scalar>
                         */
                        protected $data = [];

                        /**
                         * @param array<scalar|array> $array
                         * @psalm-suppress MixedArgumentTypeCoercion
                         */
                        final public function __construct(array $array)
                        {
                            foreach ($array as $key => $value) {
                                if (is_array($value)) {
                                    $this->data[$key] = new static($value);
                                } else {
                                    $this->data[$key] = $value;
                                }
                            }
                        }

                        /**
                         * @param string $name
                         * @return C|scalar
                         */
                        public function offsetGet($name)
                        {
                            return $this->data[$name];
                        }

                        /**
                         * @param ?string $offset
                         * @param scalar|array $value
                         * @psalm-suppress MixedArgumentTypeCoercion
                         */
                        public function offsetSet($offset, $value) : void
                        {
                            if (is_array($value)) {
                                $value = new static($value);
                            }

                            if (null === $offset) {
                                $this->data[] = $value;
                            } else {
                                $this->data[$offset] = $value;
                            }
                        }

                        public function __isset(string $name) : bool
                        {
                            return isset($this->data[$name]);
                        }

                        public function __unset(string $name) : void
                        {
                            unset($this->data[$name]);
                        }

                        /**
                         * @psalm-suppress MixedArgument
                         */
                        public function offsetExists($offset) : bool
                        {
                            return $this->__isset($offset);
                        }

                        /**
                         * @psalm-suppress MixedArgument
                         */
                        public function offsetUnset($offset) : void
                        {
                            $this->__unset($offset);
                        }
                    }',
                'assertions' => [
                    '$c' => 'C|null|scalar',
                ],
            ],
            'singleLetterOffset' => [
                'code' => '<?php
                    ["s" => "str"]["str"[0]];',
            ],
            'arrayAccessAfterByRefArrayOffsetAssignment' => [
                'code' => '<?php
                    /**
                     * @param array{param1: array} $params
                     */
                    function dispatch(array $params) : void {
                        $params["param1"]["foo"] = "bar";
                    }

                    $ar = [];
                    dispatch(["param1" => &$ar]);
                    $value = "foo";
                    if (isset($ar[$value])) {
                        echo (string) $ar[$value];
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedArrayAccess'],
            ],
            'byRefArrayAccessWithoutKnownVarNoNotice' => [
                'code' => '<?php
                    $a = new stdClass();
                    /** @psalm-suppress MixedPropertyFetch */
                    print_r([&$a->foo->bar]);',
            ],
            'accessOffsetOnList' => [
                'code' => '<?php
                    /** @param list<int> $arr */
                    function foo(array $arr) : void {
                        echo $arr[3] ?? null;
                    }',
            ],
            'destructureMixed' => [
                'code' => '<?php
                    class S {
                        protected array $a = [];
                        protected array $b = [];
                        protected array $c = [];

                        /**
                         * @psalm-suppress MixedAssignment
                         */
                        public function pop(): void {
                            if (!$this->a) {
                                return;
                            }

                            $popped = array_pop($this->a);

                            /** @psalm-suppress MixedArrayAccess */
                            [$this->b, $this->c] = $popped;
                        }
                    }',
            ],
            'simpleXmlArrayFetch' => [
                'code' => '<?php
                    function foo(SimpleXMLElement $s) : ?SimpleXMLElement {
                        return $s["a"];
                    }',
            ],
            'simpleXmlArrayFetchChildren' => [
                'code' => '<?php
                    function iterator(SimpleXMLElement $xml): iterable {
                        $children = $xml->children();
                        assert($children !== null);
                        foreach ($children as $img) {
                            yield $img["src"] ?? "";
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'assertOnArrayAccess' => [
                'code' => '<?php
                    class A {
                        public function foo() : void {}
                    }

                    /**
                     * @psalm-suppress MissingTemplateParam
                     */
                    class C implements ArrayAccess
                    {
                        /**
                         * @var array
                         */
                        protected $data = [];

                        /**
                         * @param string $name
                         * @return mixed
                         */
                        public function offsetGet($name)
                        {
                            return $this->data[$name];
                        }

                        /**
                         * @param string $offset
                         * @param mixed $value
                         */
                        public function offsetSet($offset, $value) : void
                        {
                            $this->data[$offset] = $value;
                        }

                        public function __isset(string $name) : bool
                        {
                            return isset($this->data[$name]);
                        }

                        public function __unset(string $name) : void
                        {
                            unset($this->data[$name]);
                        }

                        /**
                         * @psalm-suppress MixedArgument
                         */
                        public function offsetExists($offset) : bool
                        {
                            return $this->__isset($offset);
                        }

                        /**
                         * @psalm-suppress MixedArgument
                         */
                        public function offsetUnset($offset) : void
                        {
                            $this->__unset($offset);
                        }
                    }

                    $container = new C();
                    if ($container["a"] instanceof A) {
                        $container["a"]->foo();
                    }',
            ],
            'assignmentListCheckForNull' => [
                'code' => '<?php
                    /**
                     * @return array{0: int, 1:string}|null
                     */
                    function bar(int $i) {
                        if ( $i < 0)
                            return [$i, "hello"];
                        else
                            return null;
                    }

                    [1 => $foo] = bar(0);

                    if ($foo !== null) {}',
            ],
            'accessKnownArrayWithPositiveInt' => [
                'code' => '<?php
                    /** @param list<int> $arr */
                    function foo(array $arr) : void {
                        $o = [4, 15, 18, 21, 51];
                        $i = 0;
                        foreach ($arr as $a) {
                            if ($i < 5 && $o[$i] === $a) {}
                            $i++;
                        }
                    }',
            ],
            'arrayAccessOnArraylikeObjectOrArray' => [
                'code' => '<?php
                    /**
                     * @param arraylike-object<int, string>|array<int, string> $arr
                     */
                    function test($arr): string {
                        return $arr[0];
                    }

                    test(["a", "b"]);
                    test(new ArrayObject(["a", "b"]));',
            ],
            'nullCoalesceArrayAccess' => [
                'code' => '<?php
                    /** @param ArrayAccess<int, string> $a */
                    function foo(?ArrayAccess $a) : void {
                        echo $a[0] ?? "default";
                    }',
            ],
            'allowUnsettingNested' => [
                'code' => '<?php
                    /** @psalm-immutable */
                    final class test {
                        public function __construct(public int $value) {}
                    }
                    $test = new test(1);
                    $a = [1 => $test];
                    unset($a[$test->value]);',
            ],
            'nestedUnsetAfterIssetOnMixedArray' => [
                'code' => '<?php
                    /**
                     * @param array<string, mixed> $data
                     * @return array<string, mixed>
                     * @psalm-suppress MixedArrayAccess
                     */
                    function clean(array $data): array {
                        if (isset($data["d"]["items"]["list"])) {
                            echo "has items";
                        }
                        unset($data["d"]["result"]["meta"]);
                        return $data;
                    }
                    echo count(clean([]));',
            ],
            'unsetOnPossiblyNullIntermediateArray' => [
                'code' => '<?php
                    /** @param array<string, array<string, int>|null> $data */
                    function clearNested(array $data): array {
                        unset($data["d"]["meta"]);
                        return $data;
                    }
                    clearNested(["d" => null]);',
            ],
            'unsetOnNullArray' => [
                'code' => '<?php
                    function clearNull(): void {
                        $data = null;
                        unset($data["d"]["meta"]);
                    }
                    clearNull();',
            ],
            'unsetThroughPropertyOfPossiblyNullArray' => [
                'code' => '<?php
                    final class Holder {
                        /** @var array<string, int> */
                        public array $items = [];
                    }
                    /** @param array<string, Holder>|null $holders */
                    function clearHeld(?array $holders): void {
                        unset($holders["h"]->items["x"]);
                    }',
                'assertions' => [],
                'ignored_issues' => ['PossiblyNullPropertyFetch'],
            ],
            'nestedUnsetUpdatesRootArray' => [
                'code' => '<?php
                    /** @param array{a: int, b: array{c: int}} $a */
                    function f(array $a): void {
                        unset($a["b"]["c"]);
                        /** @psalm-check-type-exact $a = array{a: int, b: array<never, never>} */;
                    }',
            ],
            'nestedUnsetInLoopReachesLoopCondition' => [
                'code' => '<?php
                    /** @param array{a: int, b: array{c: int}} $a */
                    function f(array $a): void {
                        while ($a["b"]) {
                            if (rand(0, 1)) {
                                unset($a["b"]["c"]);
                            }
                        }
                    }',
            ],
            'nestedUnsetInBranchUpdatesRootArray' => [
                'code' => '<?php
                    /** @param array{a: int, b: array{c: int}} $a */
                    function f(array $a): void {
                        if (rand(0, 1)) {
                            unset($a["b"]["c"]);
                        }
                        /** @psalm-check-type-exact $a = array{a: int, b: array{c?: int}} */;
                    }',
            ],
            'deeplyNestedUnsetUpdatesEveryHoldingArray' => [
                'code' => '<?php
                    /** @param array{x: array{y: array{z: int, w: int}}} $a */
                    function f(array $a): void {
                        unset($a["x"]["y"]["z"]);
                        /** @psalm-check-type-exact $a = array{x: array{y: array{w: int}}} */;
                        /** @psalm-check-type-exact $a[\'x\'] = array{y: array{w: int}} */;
                    }',
            ],
            'nestedUnsetUnderPossiblyUndefinedKeyKeepsItPossiblyUndefined' => [
                'code' => '<?php
                    /** @param array{a: int, b?: array{c: int, d: string}} $a */
                    function f(array $a): void {
                        unset($a["b"]["c"]);
                        /** @psalm-check-type-exact $a = array{a: int, b?: array{d: string}} */;
                    }',
            ],
            'nestedUnsetFromNullableArray' => [
                'code' => '<?php
                    /** @param array{b: array{c: int}}|null $a */
                    function f(?array $a): void {
                        unset($a["b"]["c"]);
                        /** @psalm-check-type-exact $a = array{b: array<never, never>}|null */;
                    }',
            ],
            'nestedUnsetUnderNonLiteralKeyOfGenericArray' => [
                'code' => '<?php
                    /** @param array<string, array{c: int, d: int}> $a */
                    function f(array $a, string $k): void {
                        unset($a[$k]["c"]);
                        /** @psalm-check-type-exact $a = array<string, array{c?: int, d: int}> */;
                    }',
            ],
            'nestedUnsetUnderOneOfSeveralLiteralKeys' => [
                'code' => '<?php
                    /**
                     * @param array{x: array{c: int, d: int}, y: array{c: int, d: int}, z: array{c: int}} $a
                     * @param "x"|"y" $k
                     */
                    function f(array $a, string $k): void {
                        unset($a[$k]["c"]);
                        /** @psalm-check-type-exact $a = array{x: array{c?: int, d: int}, y: array{c?: int, d: int}, z: array{c: int}} */;
                    }',
            ],
            'nestedUnsetUnderSeveralKeysKeepsTheShapeOfEachElement' => [
                'code' => '<?php
                    /**
                     * @param array{x: array{c: int, d: int}, y: array{c: int, e: string}} $a
                     * @param "x"|"y" $k
                     */
                    function f(array $a, string $k): int {
                        unset($a[$k]["c"]);
                        /** @psalm-check-type-exact $a = array{x: array{c?: int, d: int}, y: array{c?: int, e: string}} */;
                        return $a["x"]["d"];
                    }',
            ],
            'nestedUnsetUnderNonLiteralKeyKeepsTheShapeOfEachElement' => [
                'code' => '<?php
                    /** @param array{x: array{c: int, d: int}, z: array{c: int}} $a */
                    function f(array $a, string $k): void {
                        unset($a[$k]["c"]);
                        /** @psalm-check-type-exact $a = array{x: array{c?: int, d: int}, z: array{c?: int}} */;
                    }',
            ],
            'deeplyNestedUnsetUnderSeveralKeysKeepsTheShapeOfEachElement' => [
                'code' => '<?php
                    /**
                     * @param array{x: array{m: array{c: int, d: int}}, y: array{m: array{c: int, e: string}}} $a
                     * @param "x"|"y" $k
                     */
                    function f(array $a, string $k): void {
                        unset($a[$k]["m"]["c"]);
                        /** @psalm-check-type-exact $a = array{x: array{m: array{c?: int, d: int}}, y: array{m: array{c?: int, e: string}}} */;
                    }',
            ],
            'nestedUnsetKeepsACallableArrayCallable' => [
                'code' => '<?php
                    /** @param callable-list{ArrayAccess<string, int>, "run"} $cb */
                    function f(array $cb): callable {
                        unset($cb[0]["x"]);
                        return $cb;
                    }',
            ],
            'nestedUnsetUnderUndeclaredKeyOfUnsealedArray' => [
                'code' => '<?php
                    /** @param array{a: array{c: int, d: int}, ...<string, array{c: int, d: int}>} $a */
                    function f(array $a): void {
                        unset($a["other"]["c"]);
                        /** @psalm-check-type-exact $a = array{a: array{c: int, d: int}, ...<string, array{c?: int, d: int}>} */;
                    }',
            ],
            'nestedUnsetInListKeepsItAList' => [
                'code' => '<?php
                    /** @param list{array{c: int, d: int}, array{c: int}} $a */
                    function f(array $a): void {
                        unset($a[0]["c"]);
                        /** @psalm-check-type-exact $a = list{array{d: int}, array{c: int}} */;
                    }',
            ],
            'nestedUnsetUpdatesArrayProperty' => [
                'code' => '<?php
                    final class O {
                        /** @var array{b: array{c: int, d: int}} */
                        public array $p = ["b" => ["c" => 1, "d" => 2]];

                        public function m(): void {
                            unset($this->p["b"]["c"]);
                            /** @psalm-check-type-exact $this->p = array{b: array{d: int}} */;
                        }
                    }',
            ],
            'nestedUnsetOfAnOptionalKeyKeepsTheShapeOfAByRefParam' => [
                'code' => '<?php
                    /** @param array{b: array{c?: int}} $a */
                    function f(array &$a): void {
                        unset($a["b"]["c"]);
                    }',
            ],
            'nestedUnsetThroughArrayAccessKeepsTheObject' => [
                'code' => '<?php
                    /** @param ArrayAccess<string, array{c: int}>|array{b: array{c: int}} $a */
                    function f(ArrayAccess|array $a): void {
                        unset($a["b"]["c"]);
                        /** @psalm-check-type-exact $a = ArrayAccess<string, array{c: int}>|array{b: array<never, never>} */;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'arrayAssertionShouldNotBeNull' => [
                'code' => '<?php
                    function foo(?array $arr, string $s) : void {
                        /**
                         * @psalm-suppress PossiblyNullArrayAccess
                         * @psalm-suppress MixedArrayAccess
                         */
                        if ($arr[$s]["b"] !== true) {
                            return;
                        }

                        /**
                         * @psalm-suppress MixedArgument
                         * @psalm-suppress MixedArrayAccess
                         * @psalm-suppress PossiblyNullArrayAccess
                         */
                        echo $arr[$s]["c"];
                    }',
            ],
            'TransformBadOffsetToGoodOnes' => [
                'code' => '<?php
                    $index = 1.1;

                    /** @psalm-suppress InvalidArrayOffset */
                    $_arr1 = [$index => 5];

                    $_arr2 = [];
                    /** @psalm-suppress InvalidArrayOffset */
                    $_arr2[$index] = 5;',
                'assertions' => [
                    '$_arr1===' => 'non-empty-array<1, 5>',
                    '$_arr2===' => 'array{1: 5}',
                ],
            ],
            'accessArrayWithSingleStringLiteralOffset' => [
                'code' => '<?php
                    /** @param non-empty-array<"name", int> $p */
                    function f($p): int {
                        return $p["name"];
                    }',
            ],
            'unsetListKeyedArrayDisableListFlag' => [
                'code' => '<?php
                $a = ["a", "b"];
                unset($a[0]);
                ',
                'assertions' => ['$a===' => "array{1: 'b'}"],
            ],
            'noCrashOnUnknownClassArrayAccess' => [
                'code' => <<<'PHP'
                <?php

                namespace Psalmtest\Psalmtest;

                use SomeMissingClass;

                class Test
                {
                    public function f(): void {
                        /** @var SomeMissingClass */
                        $result = null;

                        if ($result['errors'] === true) {}
                    }
                }
                PHP,
                'assertions' => [],
                'ignored_issues' => ['UndefinedDocblockClass'],
            ],
            'canExtendArrayObjectOffsetSet' => [
                'code' => <<<'PHP'
                <?php
                // parameter names in PHP are messed up:
                // ArrayObject::offsetSet(mixed $key, mixed $value) : void;
                // ArrayAccess::offsetSet(mixed $offset, mixed $value) : void;
                // and yet ArrayObject implements ArrayAccess

                /**
                 * @psalm-purity-template TStorage(write-props) <= write-props
                 * @extends ArrayObject[TStorage]<int, int>
                 * @psalm-capabilities read-props|write-this-props
                 */
                class C extends ArrayObject {
                    /**
                     * @psalm-capabilities read-props|write-this-props
                     * @psalm-purity-from-template TStorage
                     */
                    public function offsetSet(mixed $key, mixed $value): void {
                        parent::offsetSet($key, $value);
                    }
                }
                PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'coalesceOnArrayFetchWithFunctionCallKeyDropsNullFromValue' => [
                'code' => '<?php
                    function bar(string $key): string {
                        return "aa" . $key;
                    }

                    /** @param array<string, ?string> $foo */
                    function foo(array $foo, string $key, string $default): string {
                        return $foo[bar($key)] ?? $default;
                    }',
            ],
            'falseOffsetOfInternalFunctionIsIgnoredWhenConfigured' => [
                'code' => '<?php
                    /** @param array<int, string> $a */
                    function f(array $a, string $file): string {
                        return $a[filesize($file)];
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'falseOffsetInIssetIsNotReported' => [
                'code' => '<?php
                    /** @param array<int, string> $a */
                    function f(array $a, int|false $k): string {
                        return $a[$k] ?? "";
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'listOffsetAfterIssetCheck' => [
                'code' => '<?php
                    /**
                     * @param list<mixed> $l
                     * @return mixed
                     */
                    function f(array $l) {
                        if (isset($l[0])) {}
                        return $l[0];
                    }',
            ],
            'listOffsetAfterCoalesce' => [
                'code' => '<?php
                    /**
                     * @param list<mixed> $l
                     * @return mixed
                     */
                    function f(array $l) {
                        $x = $l[0] ?? false;
                        return $l[0];
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedAssignment'],
            ],
            'stringArrayOffsetAfterIssetCheck' => [
                'code' => '<?php
                    /**
                     * @param array<string, mixed> $a
                     * @return mixed
                     */
                    function f(array $a) {
                        if (isset($a["x"])) {}
                        return $a["x"];
                    }',
            ],
            'intArrayOffsetAfterIssetCheck' => [
                'code' => '<?php
                    /**
                     * @param array<int, mixed> $a
                     * @return mixed
                     */
                    function f(array $a) {
                        if (isset($a[0])) {}
                        return $a[0];
                    }',
            ],
            'listOffsetAfterIssetCheckInACondition' => [
                'code' => '<?php
                    /**
                     * @param list<mixed> $l
                     * @return mixed
                     */
                    function f(array $l, bool $b) {
                        if (isset($l[0]) && $b) {}
                        return $l[0];
                    }',
            ],
            'listOffsetAfterIssetCheckInAnElseif' => [
                'code' => '<?php
                    /**
                     * @param list<mixed> $l
                     * @return mixed
                     */
                    function f(array $l, bool $b) {
                        if ($b) {} elseif (isset($l[0])) {}
                        return $l[0];
                    }',
            ],
            'listOffsetAfterForBreakingOnIsset' => [
                'code' => '<?php
                    /**
                     * @param list<mixed> $l
                     * @return mixed
                     */
                    function f(array $l) {
                        for ($i = 0; $i < 3; $i++) {
                            if (isset($l[0])) {
                                break;
                            }
                        }
                        return $l[0];
                    }',
            ],
            'listOffsetAfterDoBreakingOnIsset' => [
                'code' => '<?php
                    /**
                     * @param list<mixed> $l
                     * @return mixed
                     */
                    function f(array $l) {
                        do {
                            if (isset($l[0])) {
                                break;
                            }
                        } while (rand(0, 1));
                        return $l[0];
                    }',
            ],
            'listOffsetAfterForeachBreakingOnIsset' => [
                'code' => '<?php
                    /**
                     * @param list<mixed> $l
                     * @param non-empty-list<int> $items
                     * @return mixed
                     */
                    function f(array $l, array $items) {
                        foreach ($items as $_) {
                            if (isset($l[0])) {
                                break;
                            }
                        }
                        return $l[0];
                    }',
            ],
            'listOffsetAfterForBreakingOnNotIsset' => [
                'code' => '<?php
                    /**
                     * @param list<mixed> $l
                     * @return mixed
                     */
                    function f(array $l) {
                        for ($i = 0; $i < 3; $i++) {
                            if (!isset($l[0])) {
                                break;
                            }
                        }
                        return $l[0];
                    }',
            ],
            'listOffsetAfterForWithABreakBeforeTheIssetCheck' => [
                'code' => '<?php
                    /**
                     * @param list<mixed> $l
                     * @return mixed
                     */
                    function f(array $l) {
                        for ($i = 0; $i < 3; $i++) {
                            if (rand(0, 1)) {
                                break;
                            }
                            if (isset($l[0])) {
                                break;
                            }
                        }
                        return $l[0];
                    }',
            ],
            'listOffsetAfterForWithABreakThatDoesNotCheckIt' => [
                'code' => '<?php
                    /**
                     * @param list<mixed> $l
                     * @return mixed
                     */
                    function f(array $l) {
                        for ($i = 0; $i < 3; $i++) {
                            if ($i === 1) {
                                if (isset($l[0])) {
                                    break;
                                }
                            } else {
                                if (rand(0, 1)) {
                                    break;
                                }
                            }
                            if (isset($l[0])) {
                                return null;
                            }
                        }
                        return $l[0];
                    }',
            ],
            'nestedOffsetAfterDoBreakingOnIsset' => [
                'code' => '<?php
                    /**
                     * @param array<string, array<string, mixed>> $a
                     * @return mixed
                     */
                    function f(array $a) {
                        do {
                            if (isset($a["x"]["y"])) {
                                break;
                            }
                        } while (rand(0, 1));
                        return $a["x"]["y"];
                    }',
            ],
            'listOffsetAfterDoLeftByABreakOfANestedLoop' => [
                'code' => '<?php
                    /**
                     * @param list<mixed> $l
                     * @return mixed
                     */
                    function f(array $l) {
                        do {
                            foreach ([1, 2] as $_) {
                                if (isset($l[0])) {
                                    break 2;
                                }
                            }
                        } while (rand(0, 1));
                        return $l[0];
                    }',
            ],
            'listOffsetAfterWhileWithAContinueBeforeTheIssetCheck' => [
                'code' => '<?php
                    /**
                     * @param list<mixed> $l
                     * @return mixed
                     */
                    function f(array $l) {
                        $i = 0;
                        while ($i < 3) {
                            $i++;
                            if (rand(0, 1)) {
                                continue;
                            }
                            if (isset($l[0])) {
                                break;
                            }
                        }
                        return $l[0];
                    }',
            ],
            'listOffsetAfterForBreakingOnIssetInATryWithFinally' => [
                'code' => '<?php
                    /**
                     * @param list<mixed> $l
                     * @return mixed
                     */
                    function f(array $l) {
                        for ($i = 0; $i < 3; $i++) {
                            try {
                                if (isset($l[0])) {
                                    break;
                                }
                            } finally {
                                echo $i;
                            }
                        }
                        return $l[0];
                    }',
            ],
            'listPropertyOffsetAfterForBreakingOnIsset' => [
                'code' => '<?php
                    final class A {
                        /** @var list<mixed> */
                        public array $l = [];

                        /** @return mixed */
                        public function f() {
                            for ($i = 0; $i < 3; $i++) {
                                if (isset($this->l[0])) {
                                    break;
                                }
                            }
                            return $this->l[0];
                        }
                    }',
            ],
            'optionalOffsetSetOnEveryWayOutOfAFor' => [
                'code' => '<?php
                    /** @param array{a?: int} $a */
                    function f(array $a): int {
                        for ($i = 0; $i < 3; $i++) {
                            if (!isset($a["a"])) {
                                return 0;
                            }
                            if (rand(0, 1)) {
                                break;
                            }
                        }
                        return $a["a"];
                    }',
            ],
            'offsetCheckedAgainAfterNotEmptyCheck' => [
                'code' => '<?php
                    /** @param array{s?: mixed} $o */
                    function f(array $o): void {
                        if (!empty($o["s"])) {
                            echo 1;
                        }
                        if (!empty($o["s"])) {
                            echo 2;
                        }
                    }',
            ],
            'offsetCheckedAgainInALoopAfterNotEmptyCheck' => [
                'code' => '<?php
                    /** @param array{s?: mixed} $o */
                    function f(array $o): void {
                        if (!empty($o["s"])) {
                            echo 1;
                        }
                        foreach ([1, 2] as $_) {
                            if (!empty($o["s"])) {
                                echo 2;
                            }
                        }
                    }',
            ],
            'nestedOffsetInACoalesceAfterIssetCheck' => [
                'code' => '<?php
                    function f(array $arr, int $bank, int $code): string {
                        if (!isset($arr[$bank])) {
                            echo 1;
                        }
                        return (string) ($arr[$bank][$code] ?? "?");
                    }',
            ],
            'listOffsetAfterIssetCheckInASwitch' => [
                'code' => '<?php
                    /**
                     * @param list<mixed> $l
                     * @return mixed
                     */
                    function f(array $l) {
                        switch (true) {
                            case isset($l[0]):
                                break;
                            default:
                                break;
                        }
                        return $l[0];
                    }',
            ],
            'listOffsetAfterIssetCheckInASwitchCaseFallingToTheEnd' => [
                'code' => '<?php
                    /**
                     * @param list<mixed> $l
                     * @return mixed
                     */
                    function f(array $l) {
                        switch (true) {
                            case isset($l[0]):
                                echo 1;
                                break;
                            default:
                                echo 2;
                        }
                        return $l[0];
                    }',
            ],
            'arrayAccessOnCoalesceAssignmentEvaluatesItOnce' => [
                'code' => '<?php
                    /** @implements ArrayAccess<int, string> */
                    final class AA implements ArrayAccess {
                        /** @param int $o */
                        public function offsetExists($o): bool { return true; }
                        /** @param int $o */
                        public function offsetGet($o): string { return ""; }
                        /**
                         * @param ?int $o
                         * @param string $v
                         */
                        public function offsetSet($o, $v): void {}
                        /** @param int $o */
                        public function offsetUnset($o): void {}
                    }

                    final class C {
                        private ?AA $a = null;

                        public function get(): string {
                            return ($this->a ??= new AA())[0];
                        }

                        public function getOrNull(): ?string {
                            return ($this->a ??= new AA())[0] ?? null;
                        }
                    }',
            ],
            'domNodeListItemOnCoalesceAssignmentEvaluatesItOnce' => [
                'code' => '<?php
                    final class C {
                        private ?DOMNodeList $l = null;

                        public function first(DOMDocument $d): ?DOMNode {
                            return ($this->l ??= $d->getElementsByTagName("a"))[0];
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
            'writeToAnOffsetOfADomNamedNodeMapIsInvalid' => [
                'code' => '<?php
                    function a(DOMNamedNodeMap $m, DOMAttr $a): void { $m["x"] = $a; }',
                'error_message' => 'InvalidArrayAssignment',
                'error_levels' => [],
                'php_version' => '8.2',
            ],
            'appendToADomNodeListIsInvalid' => [
                'code' => '<?php
                    function b(DOMNodeList $l, DOMNode $n): void { $l[] = $n; }',
                'error_message' => 'InvalidArrayAssignment',
                'error_levels' => [],
                'php_version' => '8.2',
            ],
            'unsetAnOffsetOfADomNamedNodeMapIsInvalid' => [
                'code' => '<?php
                    function c(DOMNamedNodeMap $m): void { unset($m[0]); }',
                'error_message' => 'InvalidArrayAccess',
                'error_levels' => [],
                'php_version' => '8.2',
            ],
            'offsetOfATemplateBoundedByLiteralsOutsideIssetIsStillReported' => [
                'code' => '<?php
                    /**
                     * @template T of \'x\'|\'y\'
                     * @param array{a: int, b: int} $array
                     * @param T $o
                     */
                    function outside(array $array, $o): int {
                        return $array[$o];
                    }',
                'error_message' => 'InvalidArrayOffset',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'issetOfANonDecimalIntStringOffsetOfIntKeys' => [
                'code' => '<?php
                    /**
                     * @param array<int, string> $names
                     * @param non-decimal-int-string $id
                     */
                    function has(array $names, string $id): bool {
                        return isset($names[$id]);
                    }',
                'error_message' => 'InvalidArrayOffset',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'issetOfASealedArrayWithATemplateOffsetBoundedByATypeOfNoKey' => [
                'code' => '<?php
                    /**
                     * @template T of int
                     * @param array{a: true, b: true} $array
                     * @param T $offset
                     */
                    function has(array $array, $offset): bool {
                        return isset($array[$offset]);
                    }',
                'error_message' => 'InvalidArrayOffset',
            ],
            'issetOfAMixedOrStringOffsetIsStillACoercion' => [
                'code' => '<?php
                    /** @param array<array-key, string> $names */
                    function has(array $names, mixed $id, string $default, bool $useId): bool {
                        $key = $useId ? $id : $default;

                        return isset($names[$key]);
                    }',
                'error_message' => 'MixedArrayTypeCoercion',
                'error_levels' => ['MixedAssignment'],
                'php_version' => '8.0',
            ],
            'plainWriteOfAWrongValueIntoAnArrayAccessItemIsStillChecked' => [
                'code' => '<?php
                    /** @implements ArrayAccess<string, list<int>> */
                    final class Bag implements ArrayAccess {
                        #[Override]
                        public function offsetExists(mixed $offset): bool {
                            return true;
                        }

                        /** @return list<int> */
                        #[Override]
                        public function offsetGet(mixed $offset): array {
                            return [];
                        }

                        /** @param list<int> $value */
                        #[Override]
                        public function offsetSet(mixed $offset, mixed $value): void {}

                        #[Override]
                        public function offsetUnset(mixed $offset): void {}
                    }

                    function fill(Bag $bag): void {
                        $bag["a"] = "x";
                    }',
                'error_message' => 'InvalidArgument',
                'error_levels' => [],
                'php_version' => '8.3',
            ],
            'destructuringAWronglyTypedItemIntoAnArrayAccessItemIsChecked' => [
                'code' => '<?php
                    /** @implements ArrayAccess<string, list<int>> */
                    final class Bag implements ArrayAccess {
                        #[Override]
                        public function offsetExists(mixed $offset): bool {
                            return true;
                        }

                        /** @return list<int> */
                        #[Override]
                        public function offsetGet(mixed $offset): array {
                            return [];
                        }

                        /** @param list<int> $value */
                        #[Override]
                        public function offsetSet(mixed $offset, mixed $value): void {}

                        #[Override]
                        public function offsetUnset(mixed $offset): void {}
                    }

                    function fill(Bag $bag): void {
                        [$bag["a"]] = [["x"]];
                    }',
                'error_message' => 'InvalidArgument',
                'error_levels' => [],
                'php_version' => '8.3',
            ],
            'foreachWithAWronglyTypedValueIntoAnArrayAccessItemIsChecked' => [
                'code' => '<?php
                    /** @implements ArrayAccess<string, list<int>> */
                    final class Bag implements ArrayAccess {
                        #[Override]
                        public function offsetExists(mixed $offset): bool {
                            return true;
                        }

                        /** @return list<int> */
                        #[Override]
                        public function offsetGet(mixed $offset): array {
                            return [];
                        }

                        /** @param list<int> $value */
                        #[Override]
                        public function offsetSet(mixed $offset, mixed $value): void {}

                        #[Override]
                        public function offsetUnset(mixed $offset): void {}
                    }

                    function fill(Bag $bag): void {
                        foreach ([["x"]] as $bag["a"]) {
                        }
                    }',
                'error_message' => 'InvalidArgument',
                'error_levels' => [],
                'php_version' => '8.3',
            ],
            'stringOffsetOfAnIntKeyedArrayObject' => [
                'code' => '<?php
                    /** @var ArrayObject<int, int> */
                    $typed = new ArrayObject();
                    $typed["x"] = 1;',
                'error_message' => 'InvalidArgument',
            ],
            'offsetSetOfAnItemOfAnArrayChecksTheValue' => [
                'code' => '<?php
                    final class Dog {}
                    final class Cat {}

                    /** @implements ArrayAccess<int, Dog> */
                    final class Dogs implements ArrayAccess {
                        public function offsetExists(mixed $offset): bool { return false; }
                        public function offsetGet(mixed $offset): Dog { return new Dog(); }
                        public function offsetSet(mixed $offset, mixed $value): void { echo "set"; }
                        public function offsetUnset(mixed $offset): void { echo "unset"; }
                    }

                    /** @param array<string, Dogs> $packs */
                    function addCat(array $packs): void {
                        $packs["k"][] = new Cat();
                    }',
                'error_message' => 'InvalidArgument',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'isArrayClauseOfAVariableTheLoopChangesIsDroppedAfterTheLoop' => [
                'code' => '<?php
                    /** @param array<int, mixed> $items */
                    function f(array $items, mixed $m): void {
                        $isArray = is_array($m);

                        foreach ($items as $item) {
                            $m = $item;
                        }

                        if ($isArray) {
                            $m[] = 1;
                        }
                    }',
                'error_message' => 'MixedArrayAssignment - src' . DIRECTORY_SEPARATOR . 'somefile.php:11:29',
                'error_levels' => ['MixedAssignment'],
                'php_version' => '8.0',
            ],
            'nullOffsetOfAnObjectThatRejectsItInArrayAccess' => [
                'code' => '<?php
                    /** @implements ArrayAccess<string, int> */
                    final class Bag implements ArrayAccess {
                        #[Override]
                        public function offsetExists(string $offset): bool {
                            return true;
                        }

                        #[Override]
                        public function offsetGet(string $offset): int {
                            return strlen($offset);
                        }

                        #[Override]
                        public function offsetSet(?string $offset, mixed $value): void {}

                        #[Override]
                        public function offsetUnset(string $offset): void {}
                    }

                    function f(Bag $bag): int {
                        return $bag[null];
                    }',
                'error_message' => 'NullArgument',
                'error_levels' => [],
                'php_version' => '8.3',
            ],
            'nullOffsetOfAnArrayIsStillReported' => [
                'code' => '<?php
                    /** @param array<string, int> $values */
                    function f(array $values): void {
                        echo $values[null];
                    }',
                'error_message' => 'NullArrayOffset',
            ],
            'destructureNullableShapeAndGenericArrayByKey' => [
                'code' => '<?php
                    /** @return array{x: int}|array<string, string>|null */
                    function f(): ?array {
                        return null;
                    }

                    ["x" => $a] = f();',
                'error_message' => 'PossiblyNullArrayAccess',
            ],
            'destructureNullableUnionOfShapesWhereOneLacksTheKey' => [
                'code' => '<?php
                    /** @return array{x: int}|array{y: int}|null */
                    function f(): ?array {
                        return null;
                    }

                    ["x" => $a] = f();',
                'error_message' => 'PossiblyNullArrayAccess',
                'error_levels' => ['PossiblyUndefinedArrayOffset'],
            ],
            'destructureNullableShapeByAMissingKey' => [
                'code' => '<?php
                    /** @return array{x: int}|null */
                    function f(): ?array {
                        return null;
                    }

                    ["y" => $a] = f();',
                'error_message' => 'PossiblyNullArrayAccess',
            ],
            'destructureNullableShapeWithAnEmptyStringKeyByADynamicKey' => [
                'code' => '<?php
                    /** @param array{"": int}|null $l */
                    function f(?array $l, string $k): void {
                        [$k => $a] = $l;
                    }',
                'error_message' => 'PossiblyNullArrayAccess',
            ],
            'possiblyFalseOffsetOfAnArrayVariableNamesTheVariable' => [
                'code' => '<?php
                    function read(array $items): void {
                        $key = array_search("a", $items);

                        echo $items[$key];
                    }',
                'error_message' => 'PossiblyInvalidArrayOffset - src' . DIRECTORY_SEPARATOR . 'somefile.php:5:30 - Cannot access value on variable $items using possibly false offset array-key|false, which is 0',
            ],
            'offsetOfAnArrayUnionCombinedWithAShapeLackingItIsOptional' => [
                'code' => '<?php
                    function f(array $a, bool $c): int {
                        $x = $c ? $a + ["red" => 1, "alpha" => 0] : ["alpha" => 2];
                        return (int) $x["red"];
                    }',
                'error_message' => 'PossiblyUndefinedArrayOffset',
            ],
            'offsetOfAnArrayUnionCombinedWithAShapeDeclaringItOptionalIsOptional' => [
                'code' => '<?php
                    function f(array $a, bool $c, bool $d): int {
                        $x = $c ? $a + ["alpha" => 0] : ($d ? ["red" => 1, "alpha" => 2] : ["alpha" => 3]);
                        return (int) $x["red"];
                    }',
                'error_message' => 'PossiblyUndefinedArrayOffset',
            ],
            'declaredOptionalOffsetOfUnsealedArrayNarrowedInIfWithoutElseStaysOptional' => [
                'code' => '<?php
                    /** @param array{c: null, k?: string, ...<array-key, mixed>} $a */
                    function f(array $a): string {
                        if (is_string($a["k"] ?? null)) {
                            echo "x";
                        }
                        return $a["k"];
                    }',
                'error_message' => 'PossiblyUndefinedArrayOffset',
            ],
            'offsetOfUnsealedArrayAssignedOutsideItsFallbackInIfWithoutElseIsOptional' => [
                'code' => '<?php
                    /** @param array{c: null, ...<string, int>} $a */
                    function f(array $a, bool $b): void {
                        if ($b) {
                            $a["k"] = "str";
                        }
                        echo $a["k"];
                    }',
                'error_message' => 'PossiblyUndefinedArrayOffset',
            ],
            'nestedUnsetBreaksTheShapeOfAByRefParam' => [
                'code' => '<?php
                    /** @param array{b: array{c: int}} $a */
                    function f(array &$a): void {
                        unset($a["b"]["c"]);
                    }',
                'error_message' => 'ReferenceConstraintViolation',
            ],
            'offsetOfANullableArrayAfterAnIssetGuardIsPossiblyNull' => [
                'code' => '<?php
                    /** @param array<string, list<string>>|null $lists */
                    function unguarded(?array $lists): int {
                        if (isset($lists["codes"]) && is_array($lists["codes"])) {
                            echo count($lists["codes"]);
                        }

                        if (is_array($lists["codes"])) {
                            return 1;
                        }

                        return 0;
                    }',
                'error_message' => 'PossiblyNullArrayAccess',
                'error_levels' => ['RedundantConditionGivenDocblockType'],
                'php_version' => '8.1',
            ],
            'intRangeOffsetPartlyInsideTheKeysOfAList' => [
                'code' => '<?php
                    function f(int $i): void {
                        $list = [1, 21, 22];

                        if ($i > 0) {
                            echo $list[$i];
                        }
                    }',
                'error_message' => 'PossiblyInvalidArrayOffset',
            ],
            'intRangeOffsetPartlyInsideTheKeysOfSeveralLists' => [
                'code' => '<?php
                    /**
                     * @param list{1, 21, 22}|list{3, 33} $links
                     */
                    function f(array $links, int $i): void {
                        if ($i > 0) {
                            echo $links[$i];
                        }
                    }',
                'error_message' => 'PossiblyInvalidArrayOffset',
            ],
            'intRangeOffsetPartlyInsideTheLiteralKeysOfAnArray' => [
                'code' => '<?php
                    function f(int $i): void {
                        $array = [0 => 1, 2 => 3];

                        if ($i > 1 && $i < 4) {
                            echo $array[$i];
                        }
                    }',
                'error_message' => 'PossiblyInvalidArrayOffset',
            ],
            'intRangeOffsetOutsideTheKeysOfAList' => [
                'code' => '<?php
                    function f(int $i): void {
                        $list = [1, 21, 22];

                        if ($i > 5) {
                            echo $list[$i];
                        }
                    }',
                'error_message' => 'InvalidArrayOffset',
            ],
            'invalidOffsetOfConstantNamesTheConstant' => [
                'code' => '<?php
                    const ARR = [11 => "foo"];
                    echo ARR["a"];',
                'error_message' => 'InvalidArrayOffset - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:26 - Cannot access value on variable ARR using',
            ],
            'invalidOffsetOfClassConstantNamesTheConstant' => [
                'code' => '<?php
                    class A {
                        const ARR = [11 => "foo"];
                    }
                    echo A::ARR["a"];',
                'error_message' => 'InvalidArrayOffset - src' . DIRECTORY_SEPARATOR . 'somefile.php:5:26 - Cannot access value on variable A::ARR using',
            ],
            'invalidOffsetOfCallResultNamesNoVariable' => [
                'code' => '<?php
                    /** @return array{11: string} */
                    function f(): array { return [11 => "foo"]; }
                    echo f()["a"];',
                'error_message' => 'InvalidArrayOffset - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:26 - Cannot access value on variable (expression) using',
            ],
            'missingOffsetPassedByReferenceToANonNullableParam' => [
                'code' => '<?php
                    /** @param list<int> $dst */
                    function sortIt(array &$dst): void {}

                    $shape = ["present" => [1]];
                    sortIt($shape["missing"]);',
                'error_message' => 'NullArgument',
            ],
            'issetArrayAccessOffsetOfFalsyValueType' => [
                'code' => '<?php
                    /** @param ArrayAccess<string, int> $a */
                    function f(ArrayAccess $a): bool {
                        return !empty($a["x"]);
                    }',
                'error_message' => 'RiskyTruthyFalsyComparison - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:33 - Operand of type int|null contains type int',
            ],
            'issetArrayAccessOffsetOfFalsyValueTypeOfParentTemplate' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @implements ArrayAccess<int, T>
                     */
                    abstract class Coll implements ArrayAccess {
                        /** @return T|null */
                        abstract public function offsetGet(mixed $offset): mixed;
                    }

                    /** @extends Coll<int> */
                    abstract class IntColl extends Coll {}

                    function f(IntColl $c): bool {
                        return !empty($c[0]);
                    }',
                'error_message' => 'RiskyTruthyFalsyComparison - src' . DIRECTORY_SEPARATOR . 'somefile.php:15:33 - Operand of type int|null contains type int',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'invalidArrayAccess' => [
                'code' => '<?php
                    $a = 5;
                    echo $a[0];',
                'error_message' => 'InvalidArrayAccess',
            ],
            'invalidArrayOffset' => [
                'code' => '<?php
                    $x = ["a"];
                    $y = $x["b"];',
                'error_message' => 'InvalidArrayOffset',
            ],
            'possiblyInvalidArrayOffsetWithTernaryKeyOutsideIsset' => [
                'code' => '<?php
                    $a = [1 => "Hello"];
                    $s = $a[rand(0, 1) ? 1 : 0];',
                'error_message' => 'PossiblyInvalidArrayOffset',
            ],
            'possiblyInvalidArrayOffsetWithInt' => [
                'code' => '<?php
                    $x = rand(0, 5) > 2 ? ["a" => 5] : "hello";
                    $y = $x[0];',
                'error_message' => 'PossiblyInvalidArrayOffset',
            ],
            'possiblyInvalidArrayOffsetWithString' => [
                'code' => '<?php
                    $x = rand(0, 5) > 2 ? ["a" => 5] : "hello";
                    $y = $x["a"];',
                'error_message' => 'PossiblyInvalidArrayOffset',
            ],
            'possiblyInvalidArrayAccessWithNestedArray' => [
                'code' => '<?php
                    /**
                     * @return array<int,array<string,float>>|string
                     */
                    function return_array() {
                        return rand() % 5 > 3 ? [["key" => 3.5]] : "key:3.5";
                    }
                    $result = return_array();
                    $v = $result[0]["key"];',
                'error_message' => 'PossiblyInvalidArrayOffset',
            ],
            'arrayAccessOnAnObjectNotImplementingArrayAccess' => [
                'code' => '<?php
                    final class Markup {}

                    function f(Markup $m): void {
                        echo $m["k"];
                    }',
                'error_message' => 'InvalidArrayAccess - src' . DIRECTORY_SEPARATOR . 'somefile.php:5:30 - Cannot access array value on non-array variable $m of type Markup',
            ],
            'arrayAssignmentOnAnObjectNotImplementingArrayAccess' => [
                'code' => '<?php
                    final class Markup {}

                    function f(Markup $m): void {
                        $m["k"] = 1;
                    }',
                'error_message' => 'InvalidArrayAssignment - src' . DIRECTORY_SEPARATOR . 'somefile.php:5:25 - Cannot access array value on non-array variable $m[\'k\'] of type Markup',
            ],
            'arrayAppendOnAnObjectNotImplementingArrayAccess' => [
                'code' => '<?php
                    final class Markup {}

                    function f(Markup $m): void {
                        $m[] = 1;
                    }',
                'error_message' => 'InvalidArrayAssignment - src' . DIRECTORY_SEPARATOR . 'somefile.php:5:25 - Cannot access array value on non-array variable $m of type Markup',
            ],
            'issetOnAnObjectNotImplementingArrayAccess' => [
                'code' => '<?php
                    final class Markup {}

                    function f(Markup $m): bool {
                        return isset($m["k"]);
                    }',
                'error_message' => 'InvalidArrayAccess - src' . DIRECTORY_SEPARATOR . 'somefile.php:5:38 - Cannot access array value on non-array variable $m of type Markup',
            ],
            'unsetOnAnObjectNotImplementingArrayAccess' => [
                'code' => '<?php
                    final class Markup {}

                    function f(Markup $m): void {
                        unset($m["k"]);
                    }',
                'error_message' => 'InvalidArrayAccess - src' . DIRECTORY_SEPARATOR . 'somefile.php:5:31 - Cannot access array value on non-array variable $m of type Markup',
            ],
            'arrayAccessOnAnInterfaceNotExtendingArrayAccess' => [
                'code' => '<?php
                    interface I {}

                    function f(I $i): void {
                        echo $i["k"];
                    }',
                'error_message' => 'InvalidArrayAccess - src' . DIRECTORY_SEPARATOR . 'somefile.php:5:30 - Cannot access array value on non-array variable $i of type I',
            ],
            'arrayAccessOnAnObjectWithMagicCall' => [
                'code' => '<?php
                    final class Magic {
                        public function __call(string $name, array $args): mixed {
                            return 1;
                        }
                    }

                    function f(Magic $m): void {
                        echo $m["k"];
                    }',
                'error_message' => 'InvalidArrayAccess - src' . DIRECTORY_SEPARATOR . 'somefile.php:9:30 - Cannot access array value on non-array variable $m of type Magic',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'arrayAccessOnATemplatedObjectNotImplementingArrayAccess' => [
                'code' => '<?php
                    final class Markup {}

                    /**
                     * @template T of Markup
                     * @param T $t
                     */
                    function f($t): void {
                        echo $t["k"];
                    }',
                'error_message' => 'InvalidArrayAccess - src' . DIRECTORY_SEPARATOR . 'somefile.php:9:30 - Cannot access array value on non-array variable $t of type Markup',
            ],
            'arrayAccessOnAnArrayOrAnObjectNotImplementingArrayAccess' => [
                'code' => '<?php
                    final class Markup {}

                    /** @param Markup|array<string, int> $u */
                    function f($u): void {
                        echo $u["k"];
                    }',
                'error_message' => 'PossiblyInvalidArrayAccess - src' . DIRECTORY_SEPARATOR . 'somefile.php:6:30 - Cannot access array value on non-array variable $u of type Markup',
            ],
            'arrayAssignmentOnAnArrayOrAnObjectNotImplementingArrayAccess' => [
                'code' => '<?php
                    final class Markup {}

                    /** @param Markup|array<string, int> $u */
                    function f($u): void {
                        $u["k"] = 1;
                    }',
                'error_message' => 'PossiblyInvalidArrayAssignment - src' . DIRECTORY_SEPARATOR . 'somefile.php:6:25 - Cannot access array value on non-array variable $u[\'k\'] of type Markup',
            ],
            'arrayAccessOnAnArrayAccessObjectOrAnObjectNotImplementingIt' => [
                'code' => '<?php
                    final class Markup {}

                    /** @param ArrayObject<string, int>|Markup $u */
                    function f($u): void {
                        echo $u["k"];
                    }',
                'error_message' => 'PossiblyInvalidArrayAccess - src' . DIRECTORY_SEPARATOR . 'somefile.php:6:30 - Cannot access array value on non-array variable $u of type Markup',
            ],
            'arrayAssignmentOnAnArrayAccessObjectOrAnObjectNotImplementingIt' => [
                'code' => '<?php
                    final class Markup {}

                    /** @param ArrayObject<string, int>|Markup $u */
                    function f($u): void {
                        $u["k"] = 1;
                    }',
                'error_message' => 'PossiblyInvalidArrayAssignment - src' . DIRECTORY_SEPARATOR . 'somefile.php:6:25 - Cannot access array value on non-array variable $u[\'k\'] of type Markup',
            ],
            'issetOnAnArrayAccessObjectOrAnObjectNotImplementingIt' => [
                'code' => '<?php
                    final class Markup {}

                    /** @param ArrayObject<string, int>|Markup $u */
                    function f($u): bool {
                        return isset($u["k"]);
                    }',
                'error_message' => 'PossiblyInvalidArrayAccess - src' . DIRECTORY_SEPARATOR . 'somefile.php:6:38 - Cannot access array value on non-array variable $u of type Markup',
            ],
            'compoundWriteToAnArrayAccessObjectCallsOffsetGet' => [
                'code' => '<?php
                    /** @implements ArrayAccess<string, int> */
                    final class Box implements ArrayAccess {
                        public function offsetExists(mixed $offset): bool { return true; }
                        /** @deprecated */
                        public function offsetGet(mixed $offset): int { return 1; }
                        public function offsetSet(mixed $offset, mixed $value): void {}
                        public function offsetUnset(mixed $offset): void {}
                    }

                    function f(Box $b): void {
                        $b["k"] += 1;
                    }',
                'error_message' => 'DeprecatedMethod - src' . DIRECTORY_SEPARATOR . 'somefile.php:12:25 - The method Box::offsetGet has been marked as deprecated',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'itemOfAnArrayAccessObjectPassedByReferenceResolvesOffsetSet' => [
                'code' => '<?php
                    /** @implements ArrayAccess<int, list<int>> */
                    final class Box implements ArrayAccess {
                        public function offsetExists(mixed $offset): bool { return true; }
                        public function offsetGet(mixed $offset): array { return [2, 1]; }
                        /** @deprecated */
                        public function offsetSet(mixed $offset, mixed $value): void {}
                        public function offsetUnset(mixed $offset): void {}
                    }

                    function f(Box $b): void {
                        sort($b[0]);
                    }',
                'error_message' => 'DeprecatedMethod - src' . DIRECTORY_SEPARATOR . 'somefile.php:12:30 - The method Box::offsetSet has been marked as deprecated',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'possiblyInvalidArrayAccess' => [
                'code' => '<?php
                    $a = rand(0, 10) > 5 ? 5 : ["hello"];
                    echo $a[0];',
                'error_message' => 'PossiblyInvalidArrayAccess',
            ],
            'insideIssetDisabledForDim' => [
                'code' => '<?php
                    isset($a[$b]);',
                'error_message' => 'UndefinedGlobalVariable',
            ],
            'mixedArrayAccess' => [
                'code' => '<?php
                    /** @var mixed */
                    $a = [];
                    echo $a[0];',
                'error_message' => 'MixedArrayAccess',
            ],
            'mixedArrayOffset' => [
                'code' => '<?php
                    /** @var mixed */
                    $a = 5;
                    echo [1, 2, 3, 4][$a];',
                'error_message' => 'MixedArrayOffset',
            ],
            'nullArrayAccess' => [
                'code' => '<?php
                    $a = null;
                    echo $a[0];',
                'error_message' => 'NullArrayAccess',
            ],
            'possiblyNullArrayAccess' => [
                'code' => '<?php
                    $a = rand(0, 1) ? [1, 2] : null;
                    echo $a[0];',
                'error_message' => 'PossiblyNullArrayAccess',
            ],
            'nullableArrayReadAsUnsetOffset' => [
                'code' => '<?php
                    /**
                     * @param array<string, int> $data
                     * @param array{key: string}|null $keys
                     */
                    function removeByKey(array $data, ?array $keys): void {
                        unset($data[$keys["key"]]);
                    }',
                'error_message' => 'PossiblyNullArrayAccess',
            ],
            'nullableArrayReadInUnsetCallArgument' => [
                'code' => '<?php
                    /** @return array<string, int> */
                    function fetch(?string $key): array {
                        return ["entry" => 1];
                    }
                    /** @param array{key: string}|null $keys */
                    function remove(?array $keys): void {
                        unset(fetch($keys["key"])["entry"]);
                    }',
                'error_message' => 'PossiblyNullArrayAccess',
            ],
            'nullArrayReadInUnsetCallArgument' => [
                'code' => '<?php
                    /** @return array<string, int> */
                    function fetch(?string $key): array {
                        return ["entry" => 1];
                    }
                    function remove(): void {
                        $keys = null;
                        unset(fetch($keys["key"])["entry"]);
                    }',
                'error_message' => 'NullArrayAccess',
            ],
            'cannotUnsetOffsetOfInt' => [
                'code' => '<?php
                    function invalidUnset(int $data): void {
                        unset($data["key"]);
                    }',
                'error_message' => 'InvalidArrayAccess',
            ],
            'nullableArrayReadAsUnsetMethodReceiver' => [
                'code' => '<?php
                    final class Box {
                        /** @return array<string, int> */
                        public function items(): array {
                            return [];
                        }
                    }
                    /** @param array{box: Box}|null $boxes */
                    function remove(?array $boxes): void {
                        unset($boxes["box"]->items()["x"]);
                    }',
                'error_message' => 'PossiblyNullArrayAccess',
            ],
            'nullableArrayReadAsUnsetPropertyName' => [
                'code' => '<?php
                    final class Bag {
                        /** @var array<string, int> */
                        public array $items = [];
                    }
                    /** @param array{name: "items"}|null $names */
                    function remove(Bag $bag, ?array $names): void {
                        unset($bag->{$names["name"]}["x"]);
                    }',
                'error_message' => 'PossiblyNullArrayAccess',
            ],
            'nullArrayReadAsUnsetStaticPropertyClass' => [
                'code' => '<?php
                    final class Registry {
                        /** @var array<string, int> */
                        public static array $items = [];
                    }
                    function remove(): void {
                        $classes = null;
                        unset($classes["registry"]::$items["x"]);
                    }',
                'error_message' => 'NullArrayAccess',
            ],
            'specificErrorMessage' => [
                'code' => '<?php
                    $params = ["key" => "value"];
                    echo $params["fieldName"];',
                'error_message' => 'InvalidArrayOffset - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:26 - Cannot access '
                    . 'value on variable $params using offset value of',
            ],
            'missingArrayOffsetAfterUnset' => [
                'code' => '<?php
                    $x = ["a" => "value", "b" => "value"];
                    unset($x["a"]);
                    echo $x["a"];',
                'error_message' => 'InvalidArrayOffset',
            ],
            'noImpossibleStringAccess' => [
                'code' => '<?php
                    function foo(string $s) : void {
                        echo $s[0][1];
                    }',
                'error_message' => 'InvalidArrayOffset',
            ],
            'mixedKeyStdClassOffset' => [
                'code' => '<?php
                    function example(array $y) : void {
                        echo $y[new stdClass()];
                    }',
                'error_message' => 'InvalidArrayOffset',
            ],
            'toStringOffset' => [
                'code' => '<?php
                    class Foo {
                        public function __toString() {
                            return "Foo";
                        }
                    }

                    $a = ["Foo" => "bar"];
                    echo $a[new Foo];',
                'error_message' => 'InvalidArrayOffset',
            ],
            'possiblyUndefinedIntArrayOffset' => [
                'code' => '<?php
                    /** @var array{0?:string} */
                    $entry = ["a"];

                    [$elt] = $entry;',
                'error_message' => 'PossiblyUndefinedArrayOffset',
            ],
            'possiblyUndefinedStringArrayOffset' => [
                'code' => '<?php
                    /** @var array{a?:string} */
                    $entry = ["a"];

                    ["a" => $elt] = $entry;',
                'error_message' => 'PossiblyUndefinedArrayOffset',
            ],
            'possiblyInvalidMixedArrayOffset' => [
                'code' => '<?php
                    /**
                     * @param string|array $key
                     */
                    function foo(array $a, $key) : void {
                        echo $a[$key];
                    }',
                'error_message' => 'PossiblyInvalidArrayOffset',
            ],
            'arrayAccessOnIterable' => [
                'code' => '<?php
                    function foo(iterable $i) {
                        echo $i[0];
                    }',
                'error_message' => 'InvalidArrayAccess',
            ],
            'arrayKeyCannotBeBool' => [
                'code' => '<?php
                    /**
                     * @param string[] $arr
                     */
                    function foo(array $arr) : void {
                        if (!$arr) {
                            return;
                        }

                        foreach ($arr as $i => $_) {}

                        if ($i === false) {}
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'arrayKeyCannotBeFloat' => [
                'code' => '<?php
                    /**
                     * @param string[] $arr
                     */
                    function foo(array $arr) : void {
                        if (!$arr) {
                            return;
                        }

                        foreach ($arr as $i => $_) {}

                        if ($i === 4.0) {}
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'arrayKeyCannotBeObject' => [
                'code' => '<?php
                    /**
                     * @param string[] $arr
                     */
                    function foo(array $arr) : void {
                        if (!$arr) {
                            return;
                        }

                        foreach ($arr as $i => $_) {}

                        if ($i === new stdClass) {}
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'forbidNegativeStringOffsetOutOfRange' => [
                'code' => '<?php
                    $a = "hello";
                    echo $a[-6];',
                'error_message' => 'InvalidArrayOffset',
            ],
            'emptyStringAccess' => [
                'code' => '<?php
                    $a = "";
                    echo $a[0];',
                'error_message' => 'InvalidArrayOffset',
            ],
            'recogniseBadVar' => [
                'code' => '<?php
                    $array = $_GET["foo"] ?? [];

                    $array[$a] = "b";',
                'error_message' => 'UndefinedGlobalVariable',
            ],
            'unsetListElementShouldChangeToArray' => [
                'code' => '<?php
                    /**
                     * @param list<string> $arr
                     * @return list<string>
                     */
                    function takesList(array $arr) : array {
                        unset($arr[0]);

                        return $arr;
                    }',
                'error_message' => 'LessSpecificReturnStatement',
            ],
            'simpleXmlArrayFetchResultCannotEqualString' => [
                'code' => '<?php
                    function foo(SimpleXMLElement $s) : void {
                        $b = $s["a"];

                        if ($b === "hello" || $b === "1") {}
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'undefinedTKeyedArrayOffset' => [
                'code' => '<?php
                    class Example {
                        /**
                         * @param array{a: string, b: int} $context
                         */
                        function foo(array $context): void {
                            $context["c"];
                        }
                    }',
                'error_message' => 'InvalidArrayOffset',
            ],
            'destructureNullable' => [
                'code' => '<?php
                    /**
                     * @return null|array
                     */
                    function maybeReturnArray(): ?array {
                        return rand(0, 1) ? null : ["key" => 1];
                    }

                    ["key" => $a] = maybeReturnArray();',
                'error_message' => 'PossiblyNullArrayAccess',
            ],
            'destructureTuple' => [
                'code' => '<?php
                    /**
                     * @return array{int, int}
                     */
                    function size(): array {
                        return [10, 20];
                    }

                    [$width, $height, $depth] = size();',
                'error_message' => 'InvalidArrayOffset',
            ],
            'negativeListAccess' => [
                'code' => '<?php
                    class HelloWorld
                    {
                        public function sayHello(): void
                        {
                            $a = explode("/", "a/b/c");
                            $x = $a[-3];
                            echo $x;
                        }
                    }',
                'error_message' => 'InvalidArrayOffset',
            ],
            'arrayOpenByDefault' => [
                'code' => '<?php
                    /**
                     * @param array{a: float, b: float} $params
                     */
                    function avg(array $params): void {
                      takesArrayOfFloats($params);
                    }

                    /**
                     * @param array<array-key, float> $arr
                     */
                    function takesArrayOfFloats(array $arr): void {
                        foreach ($arr as $a) {
                            echo $a;
                        }
                    }

                    avg(["a" => 0.5, "b" => 1.5, "c" => new Exception()]);',
                'error_message' => 'InvalidArgument',
            ],
            'possiblyUndefinedArrayOffsetKeyedArray' => [
                'code' => '<?php
                    $d = [];
                    if (!rand(0,1)) {
                        $d[0] = "a";
                    }

                    $x = $d[0];

                    //  should not report TypeDoesNotContainNull
                    if ($x === null) {}',
                'error_message' => 'PossiblyUndefinedArrayOffset',
            ],
            'cannotUseNamedArgumentsForArrayAccess' => [
                'code' => <<<'PHP'
                <?php
                /** @param ArrayAccess<int, string> $a */
                function f(ArrayAccess $a): void {
                    echo $a->offsetGet(offset: 0);
                }
                PHP,
                'error_message' => 'NamedArgumentNotAllowed',
            ],
            'possiblyFalseOffsetOfArraySearch' => [
                'code' => '<?php
                    /** @param array<int, string> $a */
                    function f(array $a): void {
                        $found = array_search("d", $a);
                        unset($a[$found]);
                    }',
                'error_message' => 'PossiblyInvalidArrayOffset',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'possiblyFalseOffsetRead' => [
                'code' => '<?php
                    /** @param array<int, string> $a */
                    function f(array $a, int|false $k): string {
                        return $a[$k];
                    }',
                'error_message' => 'PossiblyInvalidArrayOffset',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'possiblyFalseOffsetAssignment' => [
                'code' => '<?php
                    /** @param array<int, string> $a */
                    function f(array $a, int|false $k): void {
                        $a[$k] = "x";
                    }',
                'error_message' => 'PossiblyInvalidArrayOffset',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'possiblyUndefinedKeyedArrayOffsetAfterIssetCheck' => [
                'code' => '<?php
                    /**
                     * @param array{a?: mixed} $a
                     * @return mixed
                     */
                    function f(array $a) {
                        if (isset($a["a"])) {}
                        return $a["a"];
                    }',
                'error_message' => 'PossiblyUndefinedArrayOffset',
            ],
            'listOffsetInsideNegatedIssetCheck' => [
                'code' => '<?php
                    /**
                     * @param list<mixed> $l
                     * @return mixed
                     */
                    function f(array $l) {
                        if (!isset($l[0])) {
                            return $l[0];
                        }
                        return null;
                    }',
                'error_message' => 'PossiblyUndefinedArrayOffset',
            ],
            'stringArrayOffsetInsideNegatedIssetCheck' => [
                'code' => '<?php
                    /**
                     * @param array<string, mixed> $a
                     * @return mixed
                     */
                    function f(array $a) {
                        if (!isset($a["x"])) {
                            return $a["x"];
                        }
                        return null;
                    }',
                'error_message' => 'PossiblyUndefinedArrayOffset',
            ],
            'nestedArrayOffsetInsideNegatedIssetCheck' => [
                'code' => '<?php
                    /**
                     * @param array<string, array> $a
                     * @return mixed
                     */
                    function f(array $a, string $k) {
                        if (!isset($a[$k]["x"])) {
                            return $a[$k]["x"];
                        }
                        return null;
                    }',
                'error_message' => 'PossiblyUndefinedArrayOffset',
            ],
            'listOffsetAfterLeavingIssetCheck' => [
                'code' => '<?php
                    /**
                     * @param list<mixed> $l
                     * @return mixed
                     */
                    function f(array $l) {
                        if (isset($l[0])) {
                            return null;
                        }
                        return $l[0];
                    }',
                'error_message' => 'PossiblyUndefinedArrayOffset',
            ],
            'listOffsetAfterLeavingIssetCheckInASwitch' => [
                'code' => '<?php
                    /**
                     * @param list<mixed> $l
                     * @return mixed
                     */
                    function f(array $l) {
                        switch (true) {
                            case isset($l[0]):
                                return null;
                            default:
                                break;
                        }
                        return $l[0];
                    }',
                'error_message' => 'PossiblyUndefinedArrayOffset',
            ],
            'optionalOffsetAfterForBreakingOnIsset' => [
                'code' => '<?php
                    /** @param array{a?: int} $a */
                    function f(array $a): ?int {
                        for ($i = 0; $i < 3; $i++) {
                            if (isset($a["a"])) {
                                break;
                            }
                        }
                        return $a["a"];
                    }',
                'error_message' => 'PossiblyUndefinedArrayOffset',
            ],
            'offsetCheckedBeforeOnlyOneBreakOfADo' => [
                'code' => '<?php
                    /** @param array<string, mixed> $a */
                    function f(array $a, string $k): int {
                        do {
                            if (rand(0, 1)) {
                                break;
                            }
                            if (!isset($a[$k]) || !is_int($a[$k])) {
                                return 0;
                            }
                            if (rand(0, 1)) {
                                break;
                            }
                        } while (rand(0, 1));
                        if (isset($a[$k])) {
                            return $a[$k];
                        }
                        return 0;
                    }',
                'error_message' => 'MixedReturnStatement',
            ],
            'variableDefinedBeforeOneBreakOfAFor' => [
                'code' => '<?php
                    function f(): int {
                        for ($i = 0; $i < 3; $i++) {
                            if (rand(0, 1)) {
                                $x = 1;
                                break;
                            }
                        }
                        return $x;
                    }',
                'error_message' => 'PossiblyUndefinedVariable',
            ],
        ];
    }
}
