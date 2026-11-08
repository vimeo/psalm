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
            'arrayAccessReadHasItsKeyEvaluatedOnce' => [
                'code' => '<?php
                    /** @var ArrayObject<int, list<int>> $o */
                    $o = new ArrayObject();
                    $i = 0;
                    $n = count($o[$i++]);
                    $j = $i;',
                'assertions' => [
                    '$j===' => '1',
                ],
            ],
            'arrayAccessWriteHasItsKeyEvaluatedOnce' => [
                'code' => '<?php
                    /** @var ArrayObject<int, list<int>> $o */
                    $o = new ArrayObject();
                    $i = 0;
                    $o[$i++] = [1];
                    $j = $i;
                    $o[$i++][] = 2;
                    $k = $i;',
                'assertions' => [
                    '$j===' => '1',
                    '$k===' => '2',
                ],
            ],
            'arrayAccessIssetUnsetCoalesceHaveTheirKeyEvaluatedOnce' => [
                'code' => '<?php
                    /** @var ArrayObject<int, list<int>> $o */
                    $o = new ArrayObject();
                    $i = 0;
                    isset($o[$i++]);
                    $j = $i;
                    unset($o[$i++]);
                    $k = $i;
                    $x = $o[$i++] ?? [];
                    $l = $i;',
                'assertions' => [
                    '$j===' => '1',
                    '$k===' => '2',
                    '$l===' => '3',
                ],
            ],
            'arrayAccessItemPassedByReferenceHasItsKeyEvaluatedOnce' => [
                'code' => '<?php
                    function fill(array &$x): void {
                        $x[] = 1;
                    }

                    /** @var ArrayObject<int, list<int>> $o */
                    $o = new ArrayObject();
                    $i = 0;
                    sort($o[$i++]);
                    $a = $i;
                    array_push($o[$i++], 5, 6);
                    $b = $i;
                    fill($o[$i++]);
                    $c = $i;
                    /** @var ArrayObject<int, array<array-key, string>> $m */
                    $m = new ArrayObject();
                    preg_match("/x/", "x", $m[$i++]);
                    $d = $i;
                    $r = &$o[$i++];
                    $e = $i;',
                'assertions' => [
                    '$a===' => '1',
                    '$b===' => '2',
                    '$c===' => '3',
                    '$d===' => '4',
                    '$e===' => '5',
                ],
            ],
            'arrayAccessItemNestedKeysEvaluatedOnce' => [
                'code' => '<?php
                    /** @var ArrayObject<int, ArrayObject<int, list<int>>> $o */
                    $o = new ArrayObject();
                    $i = 0;
                    $j = 0;
                    sort($o[$i++][$j++]);
                    $k = $i;
                    $l = $j;
                    $n = count($o[$i++][$j++]);
                    $m = $i;
                    $p = $j;',
                'assertions' => [
                    '$k===' => '1',
                    '$l===' => '1',
                    '$m===' => '2',
                    '$p===' => '2',
                ],
            ],
            'arrayAccessReceiverIsEvaluatedOnce' => [
                'code' => '<?php
                    /** @return ArrayObject<int, list<int>> */
                    function g(int $n): ArrayObject {
                        return new ArrayObject([[$n]]);
                    }

                    /** @var list<ArrayObject<int, list<int>>> $a */
                    $a = [];
                    $i = 0;
                    $n = count(g($i++)[0]);
                    $j = $i;
                    sort(g($i++)[0]);
                    $k = $i;
                    g($i++)[0] = [1];
                    $l = $i;
                    $m = count($a[$i++][0]);
                    $p = $i;',
                'assertions' => [
                    '$j===' => '1',
                    '$k===' => '2',
                    '$l===' => '3',
                    '$p===' => '4',
                ],
            ],
            'arrayAccessItemOfAnIntersectionHasItsKeyEvaluatedOnce' => [
                'code' => '<?php
                    interface Marker {}

                    /** @var Marker&ArrayAccess<int, list<int>> $o */
                    $o = new ArrayObject();
                    $i = 0;
                    $n = count($o[$i++]);
                    $j = $i;
                    sort($o[$i++]);
                    $k = $i;
                    $o[$i++] = [1];
                    $l = $i;',
                'assertions' => [
                    '$j===' => '1',
                    '$k===' => '2',
                    '$l===' => '3',
                ],
            ],
            'arrayAccessItemRootsAreEvaluatedOnce' => [
                'code' => '<?php
                    final class Holder {
                        /** @var ArrayObject<int, list<int>> */
                        public ArrayObject $p;

                        /** @var ArrayObject<int, list<int>> */
                        public static ArrayObject $s;

                        public function __construct() {
                            $this->p = new ArrayObject();
                        }

                        public function me(int $n): self {
                            return $this;
                        }
                    }

                    $h = new Holder();
                    $i = 0;
                    $n = count($h->p[$i++]);
                    $a = $i;
                    $i = 0;
                    $n = count(Holder::$s[$i++]);
                    $b = $i;
                    $i = 0;
                    $n = count($h->me($i++)->p[0]);
                    $c = $i;
                    $i = 0;
                    $nullable = rand(0, 1) ? $h : null;
                    $n = count($nullable?->p[$i++]);
                    $d = $i;',
                'assertions' => [
                    '$a===' => '1',
                    '$b===' => '1',
                    '$c===' => '1',
                    '$d===' => '1',
                ],
                'ignored_issues' => ['PossiblyNullReference', 'PossiblyNullArgument', 'PossiblyNullArrayAccess'],
                'php_version' => '8.1',
            ],
            'arrayAccessKeyIsAnalysedAfterTheReceiverAssignsIt' => [
                'code' => '<?php
                    /** @implements ArrayAccess<positive-int, string> */
                    final class Names implements ArrayAccess {
                        public function offsetExists(mixed $offset): bool {
                            return true;
                        }

                        public function offsetGet(mixed $offset): string {
                            return "";
                        }

                        public function offsetSet(mixed $offset, mixed $value): void {}

                        public function offsetUnset(mixed $offset): void {}
                    }

                    function names(int $n): Names {
                        return new Names();
                    }

                    $i = 0;
                    $name = names($i = 1)[$i++];',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'arrayAccessKeyCallKeepsItsContextualTemplateResult' => [
                'code' => '<?php
                    /** @implements ArrayAccess<Closure(int):int, int> */
                    final class Lookup implements ArrayAccess {
                        public function offsetExists(mixed $offset): bool {
                            return true;
                        }

                        /** @param Closure(int):int $offset */
                        public function offsetGet(mixed $offset): int {
                            return $offset(5);
                        }

                        public function offsetSet(mixed $offset, mixed $value): void {}

                        public function offsetUnset(mixed $offset): void {}
                    }

                    /**
                     * @template T
                     * @return Closure(T):T
                     */
                    function factory(): Closure {
                        return /** @param T $x @return T */ static fn($x) => $x;
                    }

                    function read(Lookup $lookup): int {
                        return $lookup[factory()];
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
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
            'arrayItemPassedByReferenceToASortFunctionHasItsKeyEvaluatedOnce' => [
                'code' => '<?php
                    /** @var array<int, mixed> $a */
                    $a = [];
                    $i = 0;
                    asort($a[$i++]);
                    $j = 0;
                    krsort($a[$j++]);
                    $k = 0;
                    natcasesort($a[$k++]);
                    $l = 0;
                    arsort($a[$l++]);
                    $m = 0;
                    natsort($a[$m++]);',
                'assertions' => [
                    '$i===' => '1',
                    '$j===' => '1',
                    '$k===' => '1',
                    '$l===' => '1',
                    '$m===' => '1',
                ],
                'ignored_issues' => ['MixedArgument'],
            ],
            'nestedArrayItemPassedByReferenceToASortFunctionHasItsKeysEvaluatedOnce' => [
                'code' => '<?php
                    /** @var array<int, array<int, mixed>> $a */
                    $a = [];
                    $i = 0;
                    $j = 0;
                    asort($a[$i++][$j++]);
                    $k = $i;
                    $l = $j;',
                'assertions' => [
                    '$k===' => '1',
                    '$l===' => '1',
                ],
                'ignored_issues' => ['MixedArgument'],
            ],
            'propertyItemPassedByReferenceToASortFunctionHasItsKeyEvaluatedOnce' => [
                'code' => '<?php
                    final class Box {
                        /** @var array<int, mixed> */
                        public array $items = [];

                        /** @var array<int, mixed> */
                        public static array $all = [];
                    }

                    $b = new Box();
                    $i = 0;
                    asort($b->items[$i++]);
                    $j = $i;

                    $k = 0;
                    arsort(Box::$all[$k++]);
                    $l = $k;',
                'assertions' => [
                    '$j===' => '1',
                    '$l===' => '1',
                ],
                'ignored_issues' => ['MixedArgument'],
            ],
            'itemPassedByReferenceToSocketSelectHasItsKeyEvaluatedOnce' => [
                'code' => '<?php
                    $a = [[]];
                    $write = null;
                    $except = null;
                    $i = 0;
                    socket_select($a[$i++], $write, $except, 0);
                    $j = $i;',
                'assertions' => [
                    '$j===' => '1',
                ],
                'ignored_issues' => ['InvalidArgument', 'MixedArgumentTypeCoercion'],
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
            'itemOfAListPassedByReferenceToASortFunctionIsReadOnce' => [
                'code' => '<?php
                    $a = [1];
                    $i = 0;
                    arsort($a[$i++]);',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:28 - Argument 1 of arsort expects array<array-key, mixed>, but 1 provided',
            ],
            'undefinedVariablePassedByReferenceToSocketSelectIsReported' => [
                'code' => '<?php
                    function f(): void {
                        socket_select($read, $write, $except, 0);
                    }',
                'error_message' => 'UndefinedVariable - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:39 - Cannot find referenced variable $read',
            ],
            'variablePassedByReferenceToSocketSelectInALoopIsPossiblyUndefined' => [
                'code' => '<?php
                    function f(): void {
                        $write = [];
                        $except = [];
                        while (rand(0, 1)) {
                            socket_select($read, $write, $except, 0);
                            $read = [];
                        }
                    }',
                'error_message' => 'PossiblyUndefinedVariable - src' . DIRECTORY_SEPARATOR . 'somefile.php:6:43 - Possibly undefined variable $read, first seen on line 7',
            ],
            'keyOfAnItemPassedByReferenceToASortFunctionInALoopIsPossiblyUndefined' => [
                'code' => '<?php
                    /** @param array<int, mixed> $a */
                    function f(array $a): void {
                        while (rand(0, 1)) {
                            asort($a[$i]);
                            $i = 1;
                        }
                    }',
                'error_message' => 'PossiblyUndefinedVariable - src' . DIRECTORY_SEPARATOR . 'somefile.php:5:38 - Possibly undefined variable $i, first seen on line 6',
                'error_levels' => ['MixedArgument', 'PossiblyInvalidArrayOffset'],
            ],
            'castKeyOfAnItemPassedByReferenceToASortFunctionInALoopIsPossiblyUndefined' => [
                'code' => '<?php
                    /** @param array<int, mixed> $a */
                    function f(array $a): void {
                        while (rand(0, 1)) {
                            asort($a[(int) $i]);
                            $i = 1;
                        }
                    }',
                'error_message' => 'PossiblyUndefinedVariable - src' . DIRECTORY_SEPARATOR . 'somefile.php:5:44 - Possibly undefined variable $i, first seen on line 6',
                'error_levels' => ['MixedArgument', 'RedundantCast'],
            ],
            'negatedKeyOfAnItemPassedByReferenceToASortFunctionInALoopIsPossiblyUndefined' => [
                'code' => '<?php
                    /** @param array<int, mixed> $a */
                    function f(array $a): void {
                        while (rand(0, 1)) {
                            asort($a[-$i]);
                            $i = 1;
                        }
                    }',
                'error_message' => 'PossiblyUndefinedVariable - src' . DIRECTORY_SEPARATOR . 'somefile.php:5:39 - Possibly undefined variable $i, first seen on line 6',
                'error_levels' => ['MixedArgument'],
            ],
            'yieldedKeyOfAnItemPassedByReferenceToASortFunctionInALoopIsPossiblyUndefined' => [
                'code' => '<?php
                    /** @param array<array-key, mixed> $a */
                    function f(array $a): Generator {
                        while (rand(0, 1)) {
                            asort($a[yield $i]);
                            $i = 1;
                        }
                    }',
                'error_message' => 'PossiblyUndefinedVariable - src' . DIRECTORY_SEPARATOR . 'somefile.php:5:44 - Possibly undefined variable $i, first seen on line 6',
                'error_levels' => ['MixedArgument', 'MixedArrayOffset'],
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
