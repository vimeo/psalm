<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Context;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\IssueBuffer;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use function array_filter;
use function array_map;
use function array_values;

use const DIRECTORY_SEPARATOR;

final class ReferenceTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'arrayItemPassedByReferenceHoldsWhatTheCallLeavesInIt' => [
                'code' => '<?php
                    function read_value(string &$value): void {
                        $value = (string) rand(0, 1);
                    }

                    $values = ["read" => ""];
                    read_value($values["read"]);
                    $read = $values["read"];

                    $nested = ["a" => ["b" => "1"]];
                    read_value($nested["a"]["b"]);

                    $key = "z";
                    $dynamic = ["z" => "1"];
                    read_value($dynamic[$key]);',
                'assertions' => [
                    '$values===' => 'array{read: string}',
                    '$read===' => 'string',
                    '$nested===' => 'array{a: array{b: string}}',
                    '$dynamic===' => 'array{z: string}',
                ],
            ],
            'arrayItemPassedByReferenceIsCheckedWithTheTypeItIsPassedWith' => [
                'code' => '<?php
                    function run(string $command): void {
                        $process = proc_open($command, [0 => ["pipe", "r"], 1 => ["pipe", "w"]], $pipes);
                        if (!is_resource($process)) {
                            return;
                        }

                        fclose($pipes[0]);

                        if (isset($pipes[1])) {
                            echo (string) stream_get_contents($pipes[1]);
                            fclose($pipes[1]);
                        }

                        proc_close($process);
                    }',
            ],
            'arrayItemPassedByReferenceHasItsKeyEvaluatedOnce' => [
                'code' => '<?php
                    $a = [[3, 1], [2, 4]];
                    $i = 0;
                    sort($a[$i++]);
                    $j = $i;',
                'assertions' => [
                    '$j===' => '1',
                    '$a===' => 'list{non-empty-list<1|3>, list{2, 4}}',
                ],
            ],
            'arrayItemPassedByReferenceHasItsNestedKeysEvaluatedOnce' => [
                'code' => '<?php
                    $a = [[[3, 1]], [[2, 4]]];
                    $i = 0;
                    $j = 0;
                    sort($a[$i++][$j++]);
                    $k = $i;
                    $l = $j;',
                'assertions' => [
                    '$k===' => '1',
                    '$l===' => '1',
                ],
            ],
            'propertyItemPassedByReferenceHasItsKeyEvaluatedOnce' => [
                'code' => '<?php
                    final class Box {
                        /** @var list<list<int>> */
                        public array $items = [[2, 1]];

                        /** @var list<list<int>> */
                        public static array $all = [[2, 1]];
                    }

                    $b = new Box();
                    $i = 0;
                    sort($b->items[$i++]);
                    $j = $i;

                    $k = 0;
                    sort(Box::$all[$k++]);
                    $l = $k;',
                'assertions' => [
                    '$j===' => '1',
                    '$l===' => '1',
                ],
            ],
            'keyAssignedInAnArrayItemPassedByReferenceIsAssignedOnce' => [
                'code' => '<?php
                    $a = [[3, 1], [2, 4], [6, 5]];
                    $x = 0;
                    sort($a[$x = $x + 1]);',
                'assertions' => [
                    '$x===' => '1',
                ],
            ],
            'arrayItemPassedByReferenceIsWrittenUnderTheKeyItWasPassedWith' => [
                'code' => '<?php
                    /**
                     * @param mixed $value
                     * @param-out int $value
                     */
                    function set_int(&$value, int $next): void {
                        $value = $next;
                    }

                    $a = ["a", "b"];
                    $k = 0;
                    set_int($a[$k], $k = 1);',
                'assertions' => [
                    '$a===' => "list{int, 'b'}",
                ],
            ],
            'arrayItemPushedToHasItsKeyEvaluatedOnce' => [
                'code' => '<?php
                    $a = [[1], [2]];
                    $i = 0;
                    array_push($a[$i++], 5, 6);
                    $j = $i;',
                'assertions' => [
                    '$j===' => '1',
                ],
            ],
            'arrayItemPassedByReferenceToAnArrayFunctionHasItsKeyEvaluatedOnce' => [
                'code' => '<?php
                    $a = [[3, 1], [2, 4]];
                    $i = 0;
                    natsort($a[$i++]);
                    $k = 0;
                    array_splice($a[$k++], 0, 1);
                    $m = 0;
                    usort($a[$m++], fn(int $x, int $y): int => $x <=> $y);',
                'assertions' => [
                    '$i===' => '1',
                    '$k===' => '1',
                    '$m===' => '1',
                ],
            ],
            'arrayItemPassedByReferenceUnderAnIntOrNumericStringKeyIsReplaced' => [
                'code' => '<?php
                    function f(): array {
                        $a = ["old"];
                        $k = rand(0, 1) ? 0 : "0";
                        preg_match("/x/", "x", $a[$k]);
                        $copy = $a;
                        return $copy[0];
                    }',
            ],
            'arrayItemAppendedByReference' => [
                'code' => '<?php
                    $a = [];
                    preg_match("/x/", "x", $a[]);
                    $b = $a;

                    $n = [[]];
                    preg_match("/x/", "x", $n[0][]);',
                'assertions' => [
                    '$a===' => 'list{array<array-key, string>}',
                    '$b===' => 'list{array<array-key, string>}',
                    '$n===' => 'list{non-empty-list<array<array-key, string>>}',
                ],
            ],
            'arrayItemsPassedByReferenceToOneCall' => [
                'code' => '<?php
                    $o = [];
                    exec("ls", $o["l"], $o["c"]);',
                'assertions' => [
                    '$o===' => 'array{c: int, l: list<string>}',
                ],
            ],
            'listItemPassedByReferenceUnderItsKeyKeepsTheList' => [
                'code' => '<?php
                    /** @param list<list<int>> $lists */
                    function sort_each(array $lists): void {
                        foreach ($lists as $i => $_) {
                            sort($lists[$i]);
                        }

                        takes_lists($lists);
                    }

                    /** @param list<list<int>> $lists */
                    function sort_each_counted(array $lists): void {
                        for ($i = 0; $i < count($lists); $i++) {
                            sort($lists[$i]);
                        }

                        takes_lists($lists);
                    }

                    /** @param list<list<int>> $lists */
                    function takes_lists(array $lists): void {}',
            ],
            'newItemPassedByReferenceKeepsTheOtherItemsDefined' => [
                'code' => '<?php
                    function first_match(string $s): string {
                        $o = ["x" => "y"];
                        preg_match("/a/", $s, $o["m"]);
                        return $o["x"];
                    }

                    function nested_match(string $s): string {
                        $o = ["x" => ["y" => "z"]];
                        preg_match("/a/", $s, $o["x"]["m"]);
                        return $o["x"]["y"];
                    }',
            ],
            'itemsPassedByReferenceInALoopOverTheirKeysAreDefined' => [
                'code' => '<?php
                    $m = [];
                    foreach (["a", "b"] as $k) {
                        preg_match("/a/", "s", $m[$k]);
                    }',
                'assertions' => [
                    '$m===' => 'array{a: array<array-key, string>, b: array<array-key, string>}',
                ],
            ],
            'itemPassedByReferenceIntoANullableArrayKeepsItsItems' => [
                'code' => '<?php
                    /** @param array{y: int}|null $a */
                    function keyed(?array $a): int {
                        settype($a["x"], "int");
                        return $a["y"] ?? 0;
                    }

                    /** @param array<string, int>|null $a */
                    function generic(?array $a): int {
                        settype($a["x"], "int");
                        return $a["z"] ?? 0;
                    }',
            ],
            'itemOfArrayAccessPassedByReferenceIsNotSet' => [
                'code' => '<?php
                    /** @param ArrayObject<int, list<int>> $o */
                    function sort_first(ArrayObject $o): void {
                        sort($o[0]);
                    }',
            ],
            'itemAppendedByReferenceToAnArrayObject' => [
                'code' => '<?php
                    /** @param ArrayObject<int, string> $o */
                    function matchInto(ArrayObject $o): void {
                        preg_match("/x/", "s", $o[]);
                    }',
            ],
            'itemOfAnArrayAccessReadAsMixedPassedByReference' => [
                'code' => '<?php
                    final class Box implements ArrayAccess {
                        public function offsetExists(mixed $offset): bool {
                            return true;
                        }

                        public function offsetGet(mixed $offset): mixed {
                            return "";
                        }

                        /** @param string $value */
                        public function offsetSet(mixed $offset, mixed $value): void {}

                        public function offsetUnset(mixed $offset): void {}
                    }

                    function cast(Box $b): void {
                        settype($b[0], "string");
                    }',
                'assertions' => [],
                'ignored_issues' => ['MissingOverrideAttribute', 'MissingTemplateParam'],
                'php_version' => '8.0',
            ],
            'itemOfAUnionOfArrayAccessesPassedByReference' => [
                'code' => '<?php
                    /** @param ArrayAccess<int, list<int>|null>|array<int, list<string>> $o */
                    function sortObjectOrArray($o): void {
                        sort($o[0]);
                    }

                    /** @param ArrayObject<int, list<int>>|ArrayIterator<int, list<string>> $o */
                    function sortEitherObject($o): void {
                        sort($o[0]);
                    }',
            ],
            'nullItemOfAnArrayAccessPassedByReference' => [
                'code' => '<?php
                    /** @param ArrayAccess<int, array<array-key, string>> $o */
                    function fillMissing(ArrayAccess $o): void {
                        if ($o[0] !== null) {
                            return;
                        }

                        preg_match("/x/", "x", $o[0]);
                    }',
            ],
            'itemOfAMixinArrayObjectPassedByReference' => [
                'code' => '<?php
                    /** @mixin ArrayObject<int, list<int>> */
                    final class Lists {
                        public function __call(string $name, array $args): mixed {
                            return [];
                        }
                    }

                    function sortFirst(Lists $o): bool {
                        return sort($o[0]);
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'itemOfAnArrayObjectOfNeverPassedByReference' => [
                'code' => '<?php
                    /** @param ArrayObject<int, never> $o */
                    function fill(ArrayObject $o): void {
                        preg_match("/x/", "x", $o[0]);
                    }',
            ],
            'itemOfATemplatedMixinArrayObjectPassedByReference' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @mixin ArrayObject<int, T>
                     */
                    final class Lists {
                        public function __call(string $name, array $args): mixed {
                            return null;
                        }
                    }

                    /** @param Lists<list<int>> $o */
                    function sortFirst(Lists $o): void {
                        sort($o[0]);
                    }

                    /**
                     * @template T
                     * @mixin ArrayObject<int, T>
                     */
                    abstract class Wrapper {
                        public function __call(string $name, array $args): mixed {
                            return [];
                        }
                    }

                    /** @param Wrapper<list<int>>&ArrayAccess<int, mixed> $w */
                    function sortWrapped(Wrapper $w): void {
                        sort($w[0]);
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'itemOfAnObjectWithAPseudoOffsetSetPassedByReference' => [
                'code' => '<?php
                    /** @method void offsetSet(int $key, list<int> $value) */
                    final class Box {
                        /** @return list<int> */
                        public function offsetGet(int $key): array {
                            return [];
                        }

                        public function __call(string $name, array $args): mixed {
                            return null;
                        }
                    }

                    function sortFirst(Box $b): void {
                        sort($b[0]);
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'itemOfAnArrayObjectPushedTo' => [
                'code' => '<?php
                    /** @param ArrayObject<int, list<int>> $o */
                    function push(ArrayObject $o): void {
                        array_push($o[0], 3);
                    }',
            ],
            'itemOfAnArrayObjectAssignedInto' => [
                'code' => '<?php
                    /** @param ArrayObject<int, list<int>> $o */
                    function append(ArrayObject $o): void {
                        $o[0][] = 3;
                    }',
            ],
            'itemOfMixedPassedByReferenceIsNotAssigned' => [
                'code' => '<?php
                    /** @param mixed $m */
                    function sort_item($m): void {
                        /** @psalm-suppress MixedArrayAccess, MixedArgument */
                        sort($m["k"]);
                    }',
            ],
            'referenceAssignmentToNonReferenceCountsAsUse' => [
                'code' => '<?php
                    $b = &$a;
                    $b = 2;
                    echo $a;
                ',
                'assertions' => [
                    '$b===' => '2',
                    '$a===' => '2',
                ],
            ],
            'updateReferencedTypes' => [
                'code' => '<?php
                    $a = 1;
                    $b = &$a;
                    $b = 2;
                    $c = 3;
                    $b = &$c;
                    $b = 4;
                ',
                'assertions' => [
                    '$a===' => '2',
                    '$b===' => '4',
                    '$c===' => '4',
                ],
            ],
            'changingReferencedVariableChangesReference' => [
                'code' => '<?php
                    $a = 1;
                    $b = &$a;
                    $a = 2;
                ',
                'assertions' => [
                    '$a===' => '2',
                    '$b===' => '2',
                ],
            ],
            'unsetReference' => [
                'code' => '<?php
                    $a = 1;
                    $b = &$a;
                    $b = 2;
                    unset($b);
                    $b = 3;
                ',
                'assertions' => [
                    '$a===' => '2',
                    '$b===' => '3',
                ],
            ],
            'recursiveReference' => [
                'code' => '<?php
                    $a = 1;
                    $b = &$a;
                    $a = &$b;
                    $b = 2;
                ',
                'assertions' => [
                    '$a===' => '2',
                    '$b===' => '2',
                ],
            ],
            'SKIPPED-referenceToArrayItemChangesArrayType' => [
                'code' => '<?php
                    /** @var list<int> */
                    $arr = [];

                    assert(isset($arr[0]));
                    $int = &$arr[0];
                    $int = (string) $int;
                ',
                'assertions' => [
                    '$arr' => 'list<int|string>',
                ],
            ],
            'referenceToReference' => [
                'code' => '<?php
                    $a = 1;
                    $b = &$a;
                    $c = &$b;
                    $c = 2;
                    $d = 3;
                    $b = &$d;
                ',
                'assertions' => [
                    '$a===' => '2',
                    '$b===' => '3',
                    '$c===' => '2',
                    '$d===' => '3',
                ],
            ],
            'referenceToSubsequentCatch' => [
                'code' => '<?php
                    $a = null;
                    $b = &$a;

                    try {
                        throw new \Exception();
                    } catch (\Exception $a) {
                        takesException($b);
                    }
                    function takesException(\Exception $e): void {}
                ',
            ],
            'referenceAsSubsequentCatch' => [
                'code' => '<?php
                    $a = null;
                    $b = &$a;

                    try {
                        throw new \Exception();
                    } catch (\Exception $b) {
                        takesException($a);
                    }
                    function takesException(\Exception $e): void {}
                ',
            ],
            'referenceToNewVariableInitializesNull' => [
                'code' => '<?php
                    $b = &$a;
                ',
                'assertions' => [
                    '$a===' => 'null',
                    '$b===' => 'null',
                ],
            ],
            'referenceShadowedByGlobal' => [
                'code' => '<?php
                    /** @var string */
                    $a = 0;
                    function foo(): void
                    {
                        $b = 1;
                        $a = &$b;
                        global $a;
                        takesString($a);
                    }

                    function takesString(string $str): void {}
                ',
            ],
            'unsetPreventsReferenceConfusion' => [
                'code' => '<?php
                    $arr = [1, 2, 3];
                    foreach ($arr as &$i) {
                        ++$i;
                    }
                    unset($i);

                    for ($i = 0; $i < 10; ++$i) {
                        echo $i;
                    }
                ',
            ],
            'assignmentAsReferencePreventsReferenceConfusion' => [
                'code' => '<?php
                    $arr = [1, 2, 3];
                    foreach ($arr as &$i) {
                        ++$i;
                    }

                    $i = &$foo;

                    for ($i = 0; $i < 10; ++$i) {
                        echo $i;
                    }
                ',
            ],
            'assignmentAsReferenceInForeachPreventsReferenceConfusion' => [
                'code' => '<?php
                    $arr = [1, 2, 3];
                    foreach ($arr as &$i) {
                        ++$i;
                    }
                    foreach ($arr as &$i) {
                        ++$i;
                    }
                ',
            ],
            'referenceToProperty' => [
                'code' => '<?php
                    class Foo
                    {
                        public string $bar = "";
                    }

                    $foo = new Foo();
                    $bar = &$foo->bar;

                    $foo->bar = "bar";
                ',
                'assertions' => [
                    '$bar===' => "'bar'",
                ],
                'ignored_issues' => ['UnsupportedPropertyReferenceUsage'],
            ],
            'referenceReassignedInLoop' => [
                'code' => '<?php
                    /** @psalm-param list<string> $keys */
                    function &ensure_array(array &$what, array $keys): array
                    {
                        $arr = & $what;
                        while ($key = array_shift($keys)) {
                            if (!isset($arr[$key]) || !is_array($arr[$key])) {
                                $arr[$key] = array();
                            }
                            $arr = & $arr[$key];
                        }
                        return $arr;
                    }
                ',
            ],
            'referenceReboundToANestedItemInALoopDoesNotWriteToTheArrayItWasBoundTo' => [
                'code' => '<?php
                    /**
                     * @param array<mixed> $data
                     * @param array<mixed> $path
                     * @psalm-suppress MixedAssignment, MixedArgument, PossiblyInvalidArgument, MixedArrayOffset
                     */
                    function setPath(array &$data, array $path, string $value): void {
                        $cursor = &$data;
                        $last = array_key_last($path);
                        foreach ($path as $k => $key) {
                            if ($last === $k) {
                                if (array_key_exists($key, $cursor) && !is_string($cursor[$key])) {
                                    throw new RuntimeException("not a string");
                                } else {
                                    $cursor[$key] = $value;
                                }
                            } else {
                                if (!array_key_exists($key, $cursor)) {
                                    $cursor[$key] = [];
                                } elseif (!is_array($cursor[$key])) {
                                    throw new RuntimeException("not an array");
                                }
                            }
                            $cursor = &$cursor[$key];
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'variableAReferenceIsBoundToKeepsItsTypeWhenTheVariableIsReboundInALoop' => [
                'code' => '<?php
                    /** @param mixed $value */
                    function accessible($value): bool {
                        return is_array($value) || $value instanceof ArrayAccess;
                    }

                    /**
                     * @param array<array-key, mixed> $array
                     * @param list<string> $keys
                     */
                    function forget(array &$array, array $keys): void {
                        $original = &$array;

                        foreach ($keys as $key) {
                            $array = &$original;
                            /** @psalm-check-type-exact $array = array<array-key, mixed> */

                            $parts = explode(".", $key);

                            while (count($parts) > 1) {
                                $part = array_shift($parts);

                                if (isset($array[$part]) && accessible($array[$part])) {
                                    $array = &$array[$part];
                                } else {
                                    continue 2;
                                }
                            }

                            /** @psalm-check-type-exact $original = array<array-key, mixed> */
                            unset($array[(string) array_shift($parts)]);
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedArrayAccess'],
                'php_version' => '8.0',
            ],
            'referenceReboundToAnItemInAnIfDoesNotWriteToTheArrayItWasBoundTo' => [
                'code' => '<?php
                    /** @param array<string, mixed> $data */
                    function set(array &$data, string $key, string $value, bool $nested): void {
                        $cursor = &$data;

                        if ($nested) {
                            $cursor = &$cursor[$key];
                            $cursor = $value;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'referenceToAnItemStaysBoundAfterAnIfAssertingOnTheArray' => [
                'code' => '<?php
                    $a = rand(0, 1) ? ["x" => 1] : null;
                    $r = &$a["x"];

                    if ($a !== null) {
                        echo 1;
                    }

                    $r = "s";',
                'assertions' => [
                    '$a[\'x\']===' => '\'s\'',
                ],
                'ignored_issues' => ['PossiblyNullArrayAccess'],
            ],
            'referenceToAnItemStaysBoundAfterAnElseifAssertingOnTheArray' => [
                'code' => '<?php
                    $a = rand(0, 1) ? ["x" => 1] : null;
                    $r = &$a["x"];

                    if (rand(0, 1)) {
                        echo 1;
                    } elseif ($a !== null) {
                        echo 2;
                    }

                    $r = "s";',
                'assertions' => [
                    '$a[\'x\']===' => '\'s\'',
                ],
                'ignored_issues' => ['PossiblyNullArrayAccess'],
            ],
            'referenceToAnItemStaysBoundAfterAnElseAssertingOnTheArray' => [
                'code' => '<?php
                    $a = rand(0, 1) ? ["x" => 1] : null;
                    $r = &$a["x"];

                    if ($a === null) {
                        echo 1;
                    } else {
                        echo 2;
                    }

                    $r = "s";',
                'assertions' => [
                    '$a[\'x\']===' => '\'s\'',
                ],
                'ignored_issues' => ['PossiblyNullArrayAccess'],
            ],
            'dynamicArrayOffsetReferenceCanBeConditionallySet' => [
                'code' => <<<'PHP'
                    <?php
                    /** @param array<string, string> $form */
                    function hasReference(array &$form, string $identifier): bool {
                        if (isset($form[$identifier])) {
                            $field = &$form[$identifier];
                        } elseif (isset($form[$identifier . '_wrapper'])) {
                            $field = &$form[$identifier . '_wrapper'];
                        }

                        if (isset($field)) {
                            return true;
                        }

                        return false;
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => ['UnsupportedReferenceUsage'],
            ],
            'returnedReferenceAssignmentFromInstanceMethod' => [
                'code' => <<<'PHP'
                    <?php
                    final class InstanceReferenceStore {
                        public string $value = '';

                        /** @psalm-suppress MissingReturnType */
                        public function &getReference() {
                            return $this->value;
                        }
                    }

                    function assignThroughInstanceReference(InstanceReferenceStore $store, string $value): void {
                        $reference = &$store->getReference();
                        $reference = $value;
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => ['MixedAssignment', 'UnsupportedPropertyReferenceUsage'],
            ],
            'returnedReferenceAssignmentFromStaticMethod' => [
                'code' => <<<'PHP'
                    <?php
                    final class StaticReferenceStore {
                        public static string $value = '';

                        /** @psalm-suppress MissingReturnType */
                        public static function &getReference() {
                            return self::$value;
                        }
                    }

                    function assignThroughStaticReference(string $value): void {
                        $reference = &StaticReferenceStore::getReference();
                        $reference = $value;
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => ['MixedAssignment'],
            ],
            'returnedReferenceAssignmentRespectsNamedVarAnnotation' => [
                'code' => <<<'PHP'
                    <?php
                    function &getUnionReference(): int|string {
                        /** @var string $value */
                        static $value = 'text';
                        return $value;
                    }

                    function useNarrowedReturnedReference(): void {
                        /** @var string $reference */
                        $reference = &getUnionReference();
                        /** @psalm-check-type-exact $reference = string */
                        echo strlen($reference);
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'returnedReferenceAssignmentInvalidatesCachedOffsets' => [
                'code' => <<<'PHP'
                    <?php
                    final class ReferenceArray {
                        /** @var array{key: string} */
                        public static array $values = ['key' => 'text'];
                    }

                    /** @return array{key: string} */
                    function &arrayReference(): array {
                        return ReferenceArray::$values;
                    }

                    function getReferenceValue(): string {
                        $values = ['key' => 42];
                        $previousValue = $values['key'];
                        $values = &arrayReference();
                        return $values['key'];
                    }
                    PHP,
            ],
            'dontCrashOnReferenceToMixedVariableArrayOffset' => [
                'code' => '<?php
                    function func(&$a): void
                    {
                        $_ = &$a["f"];
                    }
                ',
                'assertions' => [],
                'ignored_issues' => ['MixedArrayAccess', 'MissingParamType'],
            ],
            'dontCrashOnReferenceToArrayUnknownOffset' => [
                'code' => '<?php
                    function func(array &$a): void
                    {
                        $_ = &$a["f"];
                    }
                ',
                'assertions' => [],
            ],
            'dontCrashOnReferenceToArrayMixedOffset' => [
                'code' => '<?php
                    /** @param array{f: mixed} $a */
                    function func(array &$a): void
                    {
                        $_ = &$a["f"];
                    }
                ',
                'assertions' => [],
            ],
            'allowDocblockTypingOtherVariable' => [
                'code' => '<?php
                    $a = 1;
                    /** @var string $a */
                    $b = &$a;
                ',
                'assertions' => [
                    '$b' => 'string',
                ],
            ],
            'referenceToArrayVariableOffsetDoesntCrashWhenOffsetVariableChangesDueToReconciliation' => [
                'code' => '<?php
                    $a = "a";
                    $b = false;
                    $doesNotMatter = ["a" => ["id" => 1]];
                    $reference = &$doesNotMatter[$a];
                    /** @psalm-suppress TypeDoesNotContainType */
                    $result = ($a === "not-a" && ($b || false));
                ',
                'assertions' => [
                    '$reference===' => 'array{id: 1}',
                ],
            ],
            'multipleReferencesToArrayVariableOffsetThatChangesDueToReconciliation' => [
                'code' => '<?php
                    $a = "a";
                    $b = false;
                    $doesNotMatter = ["a" => ["id" => 1]];
                    $reference1 = &$doesNotMatter[$a];
                    $reference2 = &$doesNotMatter[$a];
                    /** @psalm-suppress TypeDoesNotContainType */
                    $result = ($a === "not-a" && ($b || false));
                    $reference1["id"] = 2;
                ',
                'assertions' => [
                    '$reference1===' => 'array{id: 2}',
                    '$reference2===' => 'array{id: 2}',
                ],
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
            'issueInTheKeyOfAnArrayItemPassedByReference' => [
                'code' => '<?php
                    /**
                     * @param array<string, list<int>> $a
                     * @param array{k?: string} $b
                     */
                    function f(array $a, array $b): void {
                        sort($a[$b["k"]]);
                    }',
                'error_message' => 'PossiblyUndefinedArrayOffset - src' . DIRECTORY_SEPARATOR . 'somefile.php:7:33',
            ],
            'emptyArrayAccessInTheKeyOfAnArrayItemPassedByReference' => [
                'code' => '<?php
                    /** @param array<string, list<int>> $a */
                    function f(array $a): void {
                        $e = [];
                        sort($a[$e[0]]);
                    }',
                'error_message' => 'EmptyArrayAccess - src' . DIRECTORY_SEPARATOR . 'somefile.php:5:33',
            ],
            'itemOfAnEmptyArrayPassedToAParameterPhpDoesNotTakeByReference' => [
                'code' => '<?php
                    $pipes = [];
                    fclose($pipes[0]);',
                'error_message' => 'EmptyArrayAccess',
            ],
            'arrayItemPassedByReferenceIntoATemplateParam' => [
                'code' => '<?php
                    /**
                     * @template T of array{x: int}
                     * @param T $a
                     */
                    function f(array $a): int {
                        preg_match("/x/", "x", $a["x"]);
                        $copy = $a;
                        return $copy["x"];
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'propertyItemPassedByReferenceIsCheckedAgainstThePropertyType' => [
                'code' => '<?php
                    final class Box {
                        /** @var array{x: int} */
                        public array $items = ["x" => 1];

                        public function run(): void {
                            preg_match("/x/", "x", $this->items["x"]);
                        }
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'staticPropertyItemPassedByReferenceIsCheckedAgainstThePropertyType' => [
                'code' => '<?php
                    final class Box {
                        /** @var array{x: int} */
                        public static array $items = ["x" => 1];

                        public static function run(): void {
                            preg_match("/x/", "x", self::$items["x"]);
                        }
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'itemOfAReadonlyPropertyOfACallResultPassedByReference' => [
                'code' => '<?php
                    final class Box {
                        /** @param array{x: list<int>} $items */
                        public function __construct(public readonly array $items) {}
                    }

                    /** @psalm-pure */
                    function same(Box $b): Box {
                        return $b;
                    }

                    function change(Box $b): void {
                        sort(same($b)->items["x"]);
                    }',
                'error_message' => 'InaccessibleProperty',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'itemOfReadonlyPropertyPassedByReference' => [
                'code' => '<?php
                    final class A {
                        /** @param array{x: list<int>} $items */
                        public function __construct(public readonly array $items) {}

                        public function run(): void {
                            sort($this->items["x"]);
                        }
                    }',
                'error_message' => 'InaccessibleProperty',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'arrayCreatedByAnItemPassedByReferenceInALoopIsPossiblyUndefined' => [
                'code' => '<?php
                    /** @param list<string> $s */
                    function f(array $s): string {
                        foreach ($s as $str) {
                            /** @psalm-suppress PossiblyUndefinedVariable */
                            preg_match("/(.*)/", $str, $out["m"]);
                        }
                        return $out["m"][1] ?? "";
                    }',
                'error_message' => 'PossiblyUndefinedVariable - src' . DIRECTORY_SEPARATOR . 'somefile.php:8',
            ],
            'arrayCreatedByAnItemPassedByReferenceInAnIfIsPossiblyUndefined' => [
                'code' => '<?php
                    function f(string $s): string {
                        if (rand(0, 1)) {
                            /** @psalm-suppress UndefinedVariable, MixedArrayAccess */
                            preg_match("/(.*)/", $s, $out["m"]);
                        }
                        return $out["m"][1] ?? "";
                    }',
                'error_message' => 'PossiblyUndefinedVariable - src' . DIRECTORY_SEPARATOR . 'somefile.php:7',
            ],
            'itemOfAnArrayObjectAssignedIsChecked' => [
                'code' => '<?php
                    /** @param ArrayObject<int, list<int>> $o */
                    function set(ArrayObject $o): void {
                        $o[0] = "x";
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:33 - Argument 2 of ArrayObject::offsetSet expects list<int>, but \'x\' provided',
            ],
            'itemOfAnArrayObjectInAnArrayObjectAssignedIsChecked' => [
                'code' => '<?php
                    /** @param ArrayObject<string, ArrayObject<int, string>> $o */
                    function set(ArrayObject $o): void {
                        $o["a"][0] = 5;
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:38 - Argument 2 of ArrayObject::offsetSet expects string, but 5 provided',
            ],
            'referenceReuseForeachValue' => [
                'code' => '<?php
                    /** @var array<int> */
                    $arr = [];

                    foreach ($arr as &$var) {
                        $var += 1;
                    }

                    $var = "foo";
                ',
                'error_message' => 'ReferenceReusedFromConfusingScope',
            ],
            'referenceReuseDeclaredInForeach' => [
                'code' => '<?php
                    /** @var array<int> */
                    $arr = [];

                    foreach ($arr as $val) {
                        $var = &$val;
                        $var += 1;
                    }

                    $var = "foo";
                ',
                'error_message' => 'ReferenceReusedFromConfusingScope',
            ],
            'referenceReuseDeclaredInFor' => [
                'code' => '<?php
                    /** @var list<int> */
                    $arr = [];

                    for ($i = 0; $i < count($arr); ++$i) {
                        $var = &$arr[$i];
                        $var += 1;
                    }

                    $var = "foo";
                ',
                'error_message' => 'ReferenceReusedFromConfusingScope',
            ],
            'referenceReuseDeclaredInIf' => [
                'code' => '<?php
                    /** @var array<int> */
                    $arr = [];

                    if (isset($arr[0])) {
                        $var = &$arr[0];
                        $var += 1;
                    }

                    $var = "foo";
                ',
                'error_message' => 'ReferenceReusedFromConfusingScope',
            ],
            'referenceReboundInALoopIsReusedAfterIt' => [
                'code' => '<?php
                    /**
                     * @param array<string, mixed> $data
                     * @param list<string> $path
                     */
                    function set(array &$data, array $path, string $value): void {
                        $cursor = &$data;

                        foreach ($path as $key) {
                            $cursor = &$cursor[$key];
                        }

                        $cursor = $value;
                    }',
                'error_message' => 'ReferenceReusedFromConfusingScope',
            ],
            'referenceReboundInAnIfIsReusedAfterIt' => [
                'code' => '<?php
                    /** @param array<string, mixed> $data */
                    function set(array &$data, string $key, string $value, bool $nested): void {
                        $cursor = &$data;

                        if ($nested) {
                            $cursor = &$cursor[$key];
                        }

                        $cursor = $value;
                    }',
                'error_message' => 'ReferenceReusedFromConfusingScope',
            ],
            'referenceReuseDeclaredInElseif' => [
                'code' => '<?php
                    /** @var array<int> */
                    $arr = [];

                    if (random_int(0, 1)) {
                    } elseif (isset($arr[0])) {
                        $var = &$arr[0];
                        $var += 1;
                    }

                    $var = "foo";
                ',
                'error_message' => 'ReferenceReusedFromConfusingScope',
            ],
            'referenceReuseDeclaredInElse' => [
                'code' => '<?php
                    /** @var array<int> */
                    $arr = [];

                    if (!isset($arr[0])) {
                    } else {
                        $var = &$arr[0];
                        $var += 1;
                    }

                    $var = "foo";
                ',
                'error_message' => 'ReferenceReusedFromConfusingScope',
            ],
            'referenceReuseDeeplyNested' => [
                'code' => '<?php
                    /** @var list<list<list<int>>> */
                    $arr = [];

                    for ($i = 0; $i < count($arr); ++$i) {
                        foreach ($arr[$i] as $inner_arr) {
                            if (isset($inner_arr[0])) {
                                $var = &$inner_arr[0];
                                $var += 1;
                            }
                        }
                    }

                    $var = "foo";
                ',
                'error_message' => 'ReferenceReusedFromConfusingScope',
            ],
            'referencesIgnoreVarAnnotation' => [
                'code' => '<?php
                    $a = 1;
                    /** @var int */
                    $b = &$a;
                ',
                'error_message' => 'InvalidDocblock - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:21 - Docblock type cannot be used for reference assignment',
            ],
            'unsetOnlyPreventsReferenceConfusionAfterCall' => [
                'code' => '<?php
                    $arr = [1, 2, 3];
                    foreach ($arr as &$i) {
                        ++$i;
                    }

                    for ($i = 0; $i < 10; ++$i) {
                        echo $i;
                    }

                    unset($i);
                ',
                'error_message' => 'ReferenceReusedFromConfusingScope',
            ],
            'assignmentAsReferenceOnlyPreventsReferenceConfusionAfterAssignment' => [
                'code' => '<?php
                    $arr = [1, 2, 3];
                    foreach ($arr as &$i) {
                        ++$i;
                    }

                    for ($i = 0; $i < 10; ++$i) {
                        echo $i;
                    }

                    $i = &$foo;
                ',
                'error_message' => 'ReferenceReusedFromConfusingScope',
            ],
            'unsupportedReferenceUsageWithReferenceToArrayOffsetOfArrayOffset' => [
                'code' => '<?php
                    /** @var array<string, string> */
                    $arr = [];

                    /** @var non-empty-list<string> */
                    $foo = ["foo"];

                    $bar = &$arr[$foo[0]];
                ',
                'error_message' => 'UnsupportedReferenceUsage',
            ],
            'unsupportedReferenceUsageContinuesAnalysis' => [
                'code' => '<?php
                    /** @var array<string, string> */
                    $arr = [];

                    /** @var non-empty-list<string> */
                    $foo = ["foo"];

                    /** @psalm-suppress UnsupportedReferenceUsage */
                    $bar = &$arr[$foo[0]];

                    /** @psalm-trace $bar */;
                ',
                'error_message' => ' - $bar: string',
            ],
        ];
    }

    public function testItemOfAnArrayOrArrayAccessPassedByReferenceIsOnlyWrittenToTheArray(): void
    {
        $this->testConfig->throw_exception = false;
        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                /** @param array{x: int}|ArrayObject<string, int> $a */
                function f($a): int {
                    preg_match("/x/", "x", $a["x"]);
                    $copy = $a;
                    return $copy["x"];
                }',
        );

        $this->analyzeFile($file_path, new Context());

        self::assertSame(
            [
                [6, 'InvalidReturnStatement'],
                [3, 'InvalidReturnType'],
            ],
            array_map(
                static fn(IssueData $issue): array => [$issue->line_from, $issue->type],
                IssueBuffer::getIssuesDataForFile($file_path),
            ),
        );
    }

    public function testIssueInTheKeyOfAnArrayItemPassedByReferenceIsReportedOnce(): void
    {
        $this->testConfig->throw_exception = false;
        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                /** @param array<string, list<int>> $a */
                function f(array $a): void {
                    $n = null;
                    sort($a[$n["x"]]);
                }',
        );

        $this->analyzeFile($file_path, new Context());

        self::assertSame(
            [[5, 29]],
            array_values(array_map(
                static fn(IssueData $issue): array => [$issue->line_from, $issue->column_from],
                array_filter(
                    IssueBuffer::getIssuesDataForFile($file_path),
                    static fn(IssueData $issue): bool => $issue->type === 'NullArrayAccess',
                ),
            )),
        );
    }
}
