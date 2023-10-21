<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Context;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;
use Psalm\Type;

class ArrayAssignmentTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function testConditionalAssignment(): void
    {
        $this->addFile(
            'somefile.php',
            '<?php
                if ($b) {
                    $foo["a"] = "hello";
                }',
        );

        $context = new Context();
        $context->vars_in_scope['$b'] = Type::getBool();
        $context->vars_in_scope['$foo'] = Type::getArray();

        $this->analyzeFile('somefile.php', $context);

        $this->assertFalse(isset($context->vars_in_scope['$foo[\'a\']']));
    }

    public function providerValidCodeParse(): iterable
    {
        return [
            'genericArrayCreationWithSingleIntValue' => [
                'code' => '<?php
                    $out = [];

                    $out[] = 4;',
                'assertions' => [
                    '$out' => 'list{int}',
                ],
            ],
            'genericArrayCreationWithInt' => [
                'code' => '<?php
                    $out = [];

                    foreach ([1, 2, 3, 4, 5] as $value) {
                        $out[] = 4;
                    }',
                'assertions' => [
                    '$out' => 'non-empty-list<int>',
                ],
            ],
            'generic2dArrayCreation' => [
                'code' => '<?php
                    $out = [];

                    foreach ([1, 2, 3, 4, 5] as $value) {
                        $out[] = [4];
                    }',
                'assertions' => [
                    '$out' => 'non-empty-list<list{int}>',
                ],
            ],
            'generic2dArrayCreationAddedInIf' => [
                'code' => '<?php
                    $out = [];

                    $bits = [];

                    foreach ([1, 2, 3, 4, 5] as $value) {
                        if (rand(0,100) > 50) {
                            $out[] = $bits;
                            $bits = [];
                        }

                        $bits[] = 4;
                    }

                    $out[] = $bits;',
                'assertions' => [
                    '$out' => 'non-empty-list<non-empty-list<int>>',
                ],
            ],
            'genericArrayCreationWithObjectAddedInIf' => [
                'code' => '<?php
                    class B {}

                    $out = [];

                    if (rand(0,10) === 10) {
                        $out[] = new B();
                    }',
                'assertions' => [
                    '$out' => 'list{0?: B}',
                ],
            ],
            'genericArrayCreationWithElementAddedInSwitch' => [
                'code' => '<?php
                    $out = [];

                    switch (rand(0,10)) {
                        case 5:
                            $out[] = 4;
                            break;

                        case 6:
                            // do nothing
                    }',
                'assertions' => [
                    '$out' => 'list{0?: int}',
                ],
            ],
            'genericArrayCreationWithElementsAddedInSwitch' => [
                'code' => '<?php
                    $out = [];

                    switch (rand(0,10)) {
                        case 5:
                            $out[] = 4;
                            break;

                        case 6:
                            $out[] = "hello";
                            break;
                    }',
                'assertions' => [
                    '$out' => 'list{0?: int|string}',
                ],
            ],
            'genericArrayCreationWithElementsAddedInSwitchWithNothing' => [
                'code' => '<?php
                    $out = [];

                    switch (rand(0,10)) {
                        case 5:
                            $out[] = 4;
                            break;

                        case 6:
                            $out[] = "hello";
                            break;

                        case 7:
                            // do nothing
                    }',
                'assertions' => [
                    '$out' => 'list{0?: int|string}',
                ],
            ],
            'implicit2dIntArrayCreation' => [
                'code' => '<?php
                    $foo = [];
                    $foo[][] = "hello";',
                'assertions' => [
                    '$foo' => 'non-empty-list<non-empty-list<string>>',
                ],
            ],
            'implicit3dIntArrayCreation' => [
                'code' => '<?php
                    $foo = [];
                    $foo[][][] = "hello";',
                'assertions' => [
                    '$foo' => 'non-empty-list<list<non-empty-list<string>>>',
                ],
            ],
            'implicit4dIntArrayCreation' => [
                'code' => '<?php
                    $foo = [];
                    $foo[][][][] = "hello";',
                'assertions' => [
                    '$foo' => 'non-empty-list<list<list<non-empty-list<string>>>>',
                ],
            ],
            'implicitIndexedIntArrayCreation' => [
                'code' => '<?php
                    $foo = [];
                    $foo[0] = "a";
                    $foo[1] = "b";
                    $foo[2] = "c";

                    $bar = [0, 1, 2];

                    $bat = [];

                    foreach ($foo as $i => $text) {
                        $bat[$text] = $bar[$i];
                    }',
                'assertions' => [
                    '$foo' => 'array{0: string, 1: string, 2: string}',
                    '$bar' => 'list{int, int, int}',
                    '$bat' => 'non-empty-array<string, int>',
                ],
            ],
            'implicitStringArrayCreation' => [
                'code' => '<?php
                    $foo = [];
                    $foo["bar"] = "hello";',
                'assertions' => [
                    '$foo' => 'array{bar: string}',
                    '$foo[\'bar\']' => 'string',
                ],
            ],
            'implicit2dStringArrayCreation' => [
                'code' => '<?php
                    $foo = [];
                    $foo["bar"]["baz"] = "hello";',
                'assertions' => [
                    '$foo' => 'array{bar: array{baz: string}}',
                    '$foo[\'bar\'][\'baz\']' => 'string',
                ],
            ],
            'implicit3dStringArrayCreation' => [
                'code' => '<?php
                    $foo = [];
                    $foo["bar"]["baz"]["bat"] = "hello";',
                'assertions' => [
                    '$foo' => 'array{bar: array{baz: array{bat: string}}}',
                    '$foo[\'bar\'][\'baz\'][\'bat\']' => 'string',
                ],
            ],
            'implicit4dStringArrayCreation' => [
                'code' => '<?php
                    $foo = [];
                    $foo["bar"]["baz"]["bat"]["bap"] = "hello";',
                'assertions' => [
                    '$foo' => 'array{bar: array{baz: array{bat: array{bap: string}}}}',
                    '$foo[\'bar\'][\'baz\'][\'bat\'][\'bap\']' => 'string',
                ],
            ],
            '2Step2dStringArrayCreation' => [
                'code' => '<?php
                    $foo = ["bar" => []];
                    $foo["bar"]["baz"] = "hello";',
                'assertions' => [
                    '$foo' => 'array{bar: array{baz: string}}',
                    '$foo[\'bar\'][\'baz\']' => 'string',
                ],
            ],
            '2StepImplicit3dStringArrayCreation' => [
                'code' => '<?php
                    $foo = ["bar" => []];
                    $foo["bar"]["baz"]["bat"] = "hello";',
                'assertions' => [
                    '$foo' => 'array{bar: array{baz: array{bat: string}}}',
                ],
            ],
            'conflictingTypesWithNoAssignment' => [
                'code' => '<?php
                    $foo = [
                        "bar" => ["a" => "b"],
                        "baz" => [1]
                    ];',
                'assertions' => [
                    '$foo' => 'array{bar: array{a: string}, baz: list{int}}',
                ],
            ],
            'implicitTKeyedArrayCreation' => [
                'code' => '<?php
                    $foo = [
                        "bar" => 1,
                    ];
                    $foo["baz"] = "a";',
                'assertions' => [
                    '$foo' => 'array{bar: int, baz: string}',
                ],
            ],
            'conflictingTypesWithAssignment' => [
                'code' => '<?php
                    $foo = [
                        "bar" => ["a" => "b"],
                        "baz" => [1]
                    ];
                    $foo["bar"]["bam"]["baz"] = "hello";',
                'assertions' => [
                    '$foo' => 'array{bar: array{a: string, bam: array{baz: string}}, baz: list{int}}',
                ],
            ],
            'conflictingTypesWithAssignment2' => [
                'code' => '<?php
                    $foo = [];
                    $foo["a"] = "hello";
                    $foo["b"][] = "goodbye";
                    $bar = $foo["a"];',
                'assertions' => [
                    '$foo' => 'array{a: string, b: non-empty-list<string>}',
                    '$foo[\'a\']' => 'string',
                    '$foo[\'b\']' => 'non-empty-list<string>',
                    '$bar' => 'string',
                ],
            ],
            'conflictingTypesWithAssignment3' => [
                'code' => '<?php
                    $foo = [];
                    $foo["a"] = "hello";
                    $foo["b"]["c"]["d"] = "goodbye";',
                'assertions' => [
                    '$foo' => 'array{a: string, b: array{c: array{d: string}}}',
                ],
            ],
            'nestedTKeyedArrayAssignment' => [
                'code' => '<?php
                    $foo = [];
                    $foo["a"]["b"] = "hello";
                    $foo["a"]["c"] = 1;',
                'assertions' => [
                    '$foo' => 'array{a: array{b: string, c: int}}',
                ],
            ],
            'conditionalTKeyedArrayAssignment' => [
                'code' => '<?php
                    $foo = ["a" => "hello"];
                    if (rand(0, 10) === 5) {
                        $foo["b"] = 1;
                    }
                    else {
                        $foo["b"] = 2;
                    }',
                'assertions' => [
                    '$foo' => 'array{a: string, b: int}',
                ],
            ],
            'arrayKey' => [
                'code' => '<?php
                    $a = ["foo", "bar"];
                    $b = $a[0];

                    $c = ["a" => "foo", "b"=> "bar"];
                    $d = "a";
                    $e = $c[$d];',
                'assertions' => [
                    '$b' => 'string',
                    '$e' => 'string',
                ],
            ],
            'conditionalCheck' => [
                'code' => '<?php
                    /**
                     * @param  array{b:string} $a
                     * @return null|string
                     */
                    function fooFoo($a) {
                        if ($a["b"]) {
                            return $a["b"];
                        }
                    }',
                'assertions' => [],
            ],
            'variableKeyArrayCreate' => [
                'code' => '<?php
                    $a = [];
                    $b = "boop";
                    $a[$b][] = "bam";

                    $c = [];
                    $c[$b][$b][] = "bam";',
                'assertions' => [
                    '$a' => 'array{boop: non-empty-list<string>}',
                    '$c' => 'array{boop: array{boop: non-empty-list<string>}}',
                ],
            ],
            'assignExplicitValueToGeneric' => [
                'code' => '<?php
                    /** @var array<string, array<string, string>> */
                    $a = [];
                    $a["foo"] = ["bar" => "baz"];',
                'assertions' => [
                    '$a' => 'array{foo: array{bar: string}, ...<string, array<string, string>>}',
                ],
            ],
            'additionWithEmpty' => [
                'code' => '<?php
                    $a = [];
                    $a += ["bar"];

                    $b = [] + ["bar"];',
                'assertions' => [
                    '$a' => 'list{string}',
                    '$b' => 'list{string}',
                ],
            ],
            'additionDifferentType' => [
                'code' => '<?php
                    $a = ["bar"];
                    $a += [1];

                    $b = ["bar"] + [1];',
                'assertions' => [
                    '$a' => 'array{0: string}',
                    '$b' => 'array{0: string}',
                ],
            ],
            'present1dArrayTypeWithVarKeys' => [
                'code' => '<?php
                    /** @var array<string, array<int, string>> */
                    $a = [];

                    $foo = "foo";

                    $a[$foo][] = "bat";',
                'assertions' => [],
            ],
            'present2dArrayTypeWithVarKeys' => [
                'code' => '<?php
                    /** @var array<string, array<string, array<int, string>>> */
                    $b = [];

                    $foo = "foo";
                    $bar = "bar";

                    $b[$foo][$bar][] = "bat";',
                'assertions' => [],
            ],
            'objectLikeWithIntegerKeys' => [
                'code' => '<?php
                    /** @var array{0: string, 1: int} **/
                    $a = ["hello", 5];
                    $b = $a[0]; // string
                    $c = $a[1]; // int
                    list($d, $e) = $a; // $d is string, $e is int',
                'assertions' => [
                    '$b' => 'string',
                    '$c' => 'int',
                    '$d' => 'string',
                    '$e' => 'int',
                ],
            ],
            'objectLikeArrayAdditionNotNested' => [
                'code' => '<?php
                    $foo = [];
                    $foo["a"] = 1;
                    $foo += ["b" => [2, 3]];',
                'assertions' => [
                    '$foo' => 'array{a: int, b: list{int, int}}',
                ],
            ],
            'objectLikeArrayIsNonEmpty' => [
                'code' => '<?php
                    /**
                     * @param array{a?: string, b: string} $arg
                     * @return non-empty-array<string, string>
                     */
                    function test(array $arg): array {
                        return $arg;
                    }
                ',
            ],
            'nestedTKeyedArrayAddition' => [
                'code' => '<?php
                    $foo = [];
                    $foo["root"]["a"] = 1;
                    $foo["root"] += ["b" => [2, 3]];',
                'assertions' => [
                    '$foo' => 'array{root: array{a: int, b: list{int, int}}}',
                ],
            ],
            'updateStringIntKey1' => [
                'code' => '<?php
                    $a = [];

                    $a["a"] = 5;
                    $a[0] = 3;',
                'assertions' => [
                    '$a' => 'array{0: int, a: int}',
                ],
            ],
            'updateStringIntKey2' => [
                'code' => '<?php
                    $string = "c";

                    $b = [];

                    $b[$string] = 5;
                    $b[0] = 3;',
                'assertions' => [
                    '$b' => 'array{0: int, c: int}',
                ],
            ],
            'updateStringIntKey3' => [
                'code' => '<?php
                    $string = "c";

                    $c = [];

                    $c[0] = 3;
                    $c[$string] = 5;',
                'assertions' => [
                    '$c' => 'array{0: int, c: int}',
                ],
            ],
            'updateStringIntKey4' => [
                'code' => '<?php
                    $int = 5;

                    $d = [];

                    $d[$int] = 3;
                    $d["a"] = 5;',
                'assertions' => [
                    '$d' => 'array{5: int, a: int}',
                ],
            ],
            'updateStringIntKey5' => [
                'code' => '<?php
                    $string = "c";
                    $int = 5;

                    $e = [];

                    $e[$int] = 3;
                    $e[$string] = 5;',
                'assertions' => [
                    '$e' => 'array{5: int, c: int}',
                ],
            ],
            'updateStringIntKeyWithIntRootAndNumberOffset' => [
                'code' => '<?php
                    $string = "c";
                    $int = 5;

                    $a = [];

                    $a[0]["a"] = 5;
                    $a[0][0] = 3;',
                'assertions' => [
                    '$a' => 'array{0: array{0: int, a: int}}',
                ],
            ],
            'updateStringIntKeyWithIntRoot' => [
                'code' => '<?php
                    $string = "c";
                    $int = 5;

                    $b = [];

                    $b[0][$string] = 5;
                    $b[0][0] = 3;

                    $c = [];

                    $c[0][0] = 3;
                    $c[0][$string] = 5;

                    $d = [];

                    $d[0][$int] = 3;
                    $d[0]["a"] = 5;

                    $e = [];

                    $e[0][$int] = 3;
                    $e[0][$string] = 5;',
                'assertions' => [
                    '$b' => 'array{0: array{0: int, c: int}}',
                    '$c' => 'array{0: array{0: int, c: int}}',
                    '$d' => 'array{0: array{5: int, a: int}}',
                    '$e' => 'array{0: array{5: int, c: int}}',
                ],
            ],
            'updateStringIntKeyWithTKeyedArrayRootAndNumberOffset' => [
                'code' => '<?php
                    $string = "c";
                    $int = 5;

                    $a = [];

                    $a["root"]["a"] = 5;
                    $a["root"][0] = 3;',
                'assertions' => [
                    '$a' => 'array{root: array{0: int, a: int}}',
                ],
            ],
            'updateStringIntKeyWithTKeyedArrayRoot' => [
                'code' => '<?php
                    $string = "c";
                    $int = 5;

                    $b = [];

                    $b["root"][$string] = 5;
                    $b["root"][0] = 3;

                    $c = [];

                    $c["root"][0] = 3;
                    $c["root"][$string] = 5;

                    $d = [];

                    $d["root"][$int] = 3;
                    $d["root"]["a"] = 5;

                    $e = [];

                    $e["root"][$int] = 3;
                    $e["root"][$string] = 5;',
                'assertions' => [
                    '$b' => 'array{root: array{0: int, c: int}}',
                    '$c' => 'array{root: array{0: int, c: int}}',
                    '$d' => 'array{root: array{5: int, a: int}}',
                    '$e' => 'array{root: array{5: int, c: int}}',
                ],
            ],
            'mixedArrayAssignmentWithStringKeys' => [
                'code' => '<?php
                    /** @psalm-suppress MixedArgument */
                    function foo(array $a) : array {
                        /** @psalm-suppress MixedArrayAssignment */
                        $a["b"]["c"] = 5;
                        /** @psalm-suppress MixedArrayAccess */
                        echo $a["b"]["d"];
                        echo $a["a"];
                        return $a;
                    }',
            ],
            'mixedArrayCoercion' => [
                'code' => '<?php
                    /** @param int[] $arg */
                    function expect_int_array($arg): void { }
                    /** @return array */
                    function generic_array() { return []; }

                    /** @psalm-suppress MixedArgumentTypeCoercion */
                    expect_int_array(generic_array());

                    function expect_int(int $arg): void {}

                    /** @return mixed */
                    function return_mixed() { return 2; }

                    /** @psalm-suppress MixedArgument */
                    expect_int(return_mixed());',
            ],
            'suppressMixedObjectOffset' => [
                'code' => '<?php
                    function getThings(): array {
                      return [];
                    }

                    $arr = [];

                    foreach (getThings() as $a) {
                      $arr[$a->id] = $a;
                    }

                    echo $arr[0];',
                'assertions' => [],
                'ignored_issues' => ['MixedAssignment', 'MixedPropertyFetch', 'MixedArrayOffset', 'MixedArgument'],
            ],
            'changeTKeyedArrayType' => [
                'code' => '<?php
                    $a = ["b" => "c"];
                    $a["d"] = ["e" => "f"];
                    $a["b"] = 4;
                    $a["d"]["e"] = 5;',
                'assertions' => [
                    '$a[\'b\']' => 'int',
                    '$a[\'d\']' => 'array{e: int}',
                    '$a[\'d\'][\'e\']' => 'int',
                    '$a' => 'array{b: int, d: array{e: int}}',
                ],
            ],
            'changeTKeyedArrayTypeInIf' => [
                'code' => '<?php
                    $a = [];

                    if (rand(0, 5) > 3) {
                      $a["b"] = new stdClass;
                    } else {
                      $a["b"] = ["e" => "f"];
                    }

                    if ($a["b"] instanceof stdClass) {
                      $a["b"] = [];
                    }

                    $a["b"]["e"] = "d";',
                'assertions' => [
                    '$a' => 'array{b: array{e: string}}',
                    '$a[\'b\']' => 'array{e: string}',
                    '$a[\'b\'][\'e\']' => 'string',
                ],
            ],
            'implementsArrayAccess' => [
                'code' => '<?php
                    /**
                     * @implements \ArrayAccess<array-key, mixed>
                     */
                    class A implements \ArrayAccess {
                        /**
                         * @param  string|int $offset
                         * @param  mixed $value
                         */
                        public function offsetSet($offset, $value): void {}

                        /** @param string|int $offset */
                        public function offsetExists($offset): bool {
                            return true;
                        }

                        /** @param string|int $offset */
                        public function offsetUnset($offset): void {}

                        /**
                         * @param  string $offset
                         * @return mixed
                         */
                        public function offsetGet($offset) {
                            return 1;
                        }
                    }

                    $a = new A();
                    $a["bar"] = "cool";
                    $a["bar"]->foo();',
                'assertions' => [
                    '$a' => 'A',
                ],
                'ignored_issues' => ['MixedMethodCall'],
            ],
            'mixedSwallowsArrayAssignment' => [
                'code' => '<?php
                    /** @psalm-suppress MixedAssignment */
                    $a = $GLOBALS["foo"];

                    /** @psalm-suppress MixedArrayAssignment */
                    $a["bar"] = "cool";

                    /** @psalm-suppress MixedMethodCall */
                    $a->offsetExists("baz");',
            ],
            'implementsArrayAccessInheritingDocblock' => [
                'code' => '<?php
                    /**
                     * @implements \ArrayAccess<string, mixed>
                     */
                    class A implements \ArrayAccess
                    {
                        /**
                         * @var array<string, mixed>
                         */
                        protected $data = [];

                        /**
                         * @param array<string, mixed> $data
                         */
                        public function __construct(array $data = [])
                        {
                            $this->data = $data;
                        }

                        /**
                         * @param  string $offset
                         */
                        public function offsetExists($offset): bool
                        {
                            return isset($this->data[$offset]);
                        }

                        /**
                         * @param  string $offset
                         */
                        public function offsetGet($offset)
                        {
                            return $this->data[$offset];
                        }

                        /**
                         * @param  string $offset
                         * @param  mixed  $value
                         */
                        public function offsetSet($offset, $value): void
                        {
                            $this->data[$offset] = $value;
                        }

                        /**
                         * @param  string $offset
                         */
                        public function offsetUnset($offset): void
                        {
                            unset($this->data[$offset]);
                        }
                    }

                    class B extends A {
                        /**
                         * {@inheritdoc}
                         */
                        public function offsetSet($offset, $value): void
                        {
                            echo "some log";
                            $this->data[$offset] = $value;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedAssignment', 'MixedReturnStatement'],
            ],
            'assignToNullDontDie' => [
                'code' => '<?php
                    $a = null;
                    $a[0][] = 1;',
                'assertions' => [
                    '$a' => 'array{0: non-empty-list<int>}',
                ],
                'ignored_issues' => ['PossiblyNullArrayAssignment'],
            ],
            'stringAssignment' => [
                'code' => '<?php
                    $str = "hello";
                    $str[0] = "i";',
                'assertions' => [
                    '$str' => 'string',
                ],
            ],
            'ignoreInvalidArrayOffset' => [
                'code' => '<?php
                    $a = [
                        "b" => [],
                    ];

                    $a["b"]["c"] = 0;

                    foreach ([1, 2, 3] as $i) {
                        /**
                         * @psalm-suppress InvalidArrayOffset
                         * @psalm-suppress MixedOperand
                         * @psalm-suppress PossiblyUndefinedArrayOffset
                         * @psalm-suppress MixedAssignment
                         */
                        $a["b"]["d"] += $a["b"][$i];
                    }',
                'assertions' => [],
            ],
            'keyedIntOffsetArrayValues' => [
                'code' => '<?php
                    $a = ["hello", 5];
                    /** @psalm-suppress RedundantFunctionCall */
                    $a_values = array_values($a);
                    $a_keys = array_keys($a);',
                'assertions' => [
                    '$a' => 'list{string, int}',
                    '$a_values' => 'non-empty-list<int|string>',
                    '$a_keys' => 'non-empty-list<int<0, 1>>',
                ],
            ],
            'changeIntOffsetKeyValuesWithDirectAssignment' => [
                'code' => '<?php
                    $b = ["hello", 5];
                    $b[0] = 3;',
                'assertions' => [
                    '$b' => 'list{int, int}',
                ],
            ],
            'changeIntOffsetKeyValuesAfterCopy' => [
                'code' => '<?php
                    $b = ["hello", 5];
                    $c = $b;
                    $c[0] = 3;',
                'assertions' => [
                    '$b' => 'list{string, int}',
                    '$c' => 'list{int, int}',
                ],
            ],
            'mergeIntOffsetValues' => [
                'code' => '<?php
                    $d = array_merge(["hello", 5], []);
                    $e = array_merge(["hello", 5], ["hello again"]);',
                'assertions' => [
                    '$d' => 'list{string, int}',
                    '$e' => 'list{string, int, string}',
                ],
            ],
            'addIntOffsetToEmptyArray' => [
                'code' => '<?php
                    $f = [];
                    $f[0] = "hello";',
                'assertions' => [
                    '$f' => 'array{0: string}',
                ],
            ],
            'dontIncrementIntOffsetForKeyedItems' => [
                'code' => '<?php
                    $a = [1, "a" => 2, 3];',
                'assertions' => [
                    '$a' => 'array{0: int, 1: int, a: int}',
                ],
            ],
            'assignArrayOrSetNull' => [
                'code' => '<?php
                    $a = [];

                    if (rand(0, 1)) {
                        $a[] = 4;
                    }

                    if (!$a) {
                        $a = null;
                    }',
                'assertions' => [
                    '$a===' => 'list{4}|null',
                ],
            ],
            'assignArrayOrSetNullInElseIf' => [
                'code' => '<?php
                    $a = [];

                    if (rand(0, 1)) {
                        $a[] = 4;
                    }

                    if ($a) {
                    } elseif (rand(0, 1)) {
                        $a = null;
                    }',
                'assertions' => [
                    '$a' => 'list{0?: int}|null',
                ],
            ],
            'assignArrayOrSetNullInElse' => [
                'code' => '<?php
                    $a = [];

                    if (rand(0, 1)) {
                        $a[] = 4;
                    }

                    if ($a) {
                    } else {
                        $a = null;
                    }',
                'assertions' => [
                    '$a' => 'list{int}|null',
                ],
            ],
            'mixedMethodCallArrayAccess' => [
                'code' => '<?php
                    function foo(object $obj) : array {
                        $ret = [];
                        $ret["a"][$obj->foo()] = 1;
                        return $ret["a"];
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedMethodCall', 'MixedArrayOffset'],
            ],
            'mixedAccessNestedKeys' => [
                'code' => '<?php
                    function takesString(string $s) : string { return "hello"; }
                    function updateArray(array $arr) : array {
                        foreach ($arr as $i => $item) {
                            $arr[$i]["a"]["b"] = 5;
                            $arr[$i]["a"]["c"] = takesString($arr[$i]["a"]["c"]);
                        }

                        return $arr;
                    }',
                'assertions' => [],
                'ignored_issues' => [
                    'MixedArrayAccess', 'MixedAssignment', 'MixedArrayOffset', 'MixedArrayAssignment', 'MixedArgument',
                ],
            ],
            'possiblyUndefinedArrayAccessWithIsset' => [
                'code' => '<?php
                    if (rand(0,1)) {
                      $a = ["a" => 1];
                    } else {
                      $a = [2, 3];
                    }

                    if (isset($a[0])) {
                        echo $a[0];
                    }',
            ],
            'accessArrayAfterSuppressingBugs' => [
                'code' => '<?php
                    $a = [];

                    foreach (["one", "two", "three"] as $key) {
                        $a[$key] += rand(0, 10);
                    }

                    $a["four"] = true;

                    if ($a["one"]) {}',
            ],
            'noDuplicateImplicitIntArrayKey' => [
                'code' => '<?php
                    $arr = [1 => 0, 1, 2, 3];
                    $arr = [1 => "one", 2 => "two", "three"];',
            ],
            'noDuplicateImplicitIntArrayKeyLargeOffset' => [
                'code' => '<?php
                    $arr = [
                        48 => "A",
                        95 => "a", "b",
                    ];',
            ],
            'constArrayAssignment' => [
                'code' => '<?php
                    const BAR = 2;
                    $arr = [1 => 2];
                    $arr[BAR] = [6];
                    $bar = $arr[BAR][0];',
            ],
            'castToArray' => [
                'code' => '<?php
                    $a = (array) (rand(0, 1) ? [1 => "one"] : 0);
                    $b = (array) null;',
                'assertions' => [
                    '$a' => 'array{0?: int, 1?: string}',
                    '$b' => 'array<never, never>',
                ],
            ],
            'getOnCoercedArray' => [
                'code' => '<?php
                    function getArray() : array {
                        return rand(0, 1) ? ["attr" => []] : [];
                    }

                    $out = getArray();
                    $out["attr"] = (array) ($out["attr"] ?? []);
                    $out["attr"]["bar"] = 1;',
                'assertions' => [
                    '$out[\'attr\'][\'bar\']' => 'int',
                ],
            ],
            'arrayAssignmentOnMixedArray' => [
                'code' => '<?php
                    function foo(array $arr) : void {
                        $arr["a"] = 1;

                        foreach ($arr["b"] as $b) {}
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedAssignment'],
            ],
            'implementsArrayAccessAllowNullOffset' => [
                'code' => '<?php
                    /**
                     * @template-implements ArrayAccess<?int, string>
                     */
                    class C implements ArrayAccess {
                        public function offsetExists(int $offset) : bool { return true; }

                        public function offsetGet($offset) : string { return "";}

                        public function offsetSet(?int $offset, string $value) : void {}

                        public function offsetUnset(int $offset) : void { }
                    }

                    $c = new C();
                    $c[] = "hello";',
            ],
            'checkEmptinessAfterConditionalArrayAdjustment' => [
                'code' => '<?php
                    class A {
                        public array $arr = [];

                        public function foo() : void {
                            if (rand(0, 1)) {
                                $this->arr["a"] = "hello";
                            }

                            if (!$this->arr) {}
                        }
                    }',
            ],
            'arrayAssignmentAddsTypePossibilities' => [
                'code' => '<?php
                    function bar(array $value): void {
                        $value["b"] = "hello";
                        $value = $value + ["a" => 0];
                        if (is_int($value["a"])) {}
                    }',
            ],
            'coercePossiblyNullKeyToEmptyString' => [
                'code' => '<?php
                    function string_or_null(): ?string {
                      return rand(0, 1) !== 0 ? "aaa" : null;
                    }

                    /**
                     * @return array<string, null>
                     */
                    function foo(): array {
                        $array = [];
                        /** @psalm-suppress PossiblyNullArrayOffset */
                        $array[string_or_null()] = null;
                        return $array;
                    }',
            ],
            'coerceNullKeyToEmptyString' => [
                'code' => '<?php
                    /**
                     * @return array<string, null>
                     */
                    function foo(): array {
                        $array = [];
                        /** @psalm-suppress NullArrayOffset */
                        $array[null] = null;
                        return $array;
                    }',
            ],
            'listUsedAsArray' => [
                'code' => '<?php
                    function takesArray(array $arr) : void {}

                    $a = [];
                    $a[] = 1;
                    $a[] = 2;

                    takesArray($a);',
                'assertions' => [
                    '$a' => 'list{int, int}',
                ],
            ],
            'listTakesEmptyArray' => [
                'code' => '<?php
                    /** @param list<int> $arr */
                    function takesList(array $arr) : void {}

                    $a = [];

                    takesList($a);',
                'assertions' => [
                    '$a' => 'array<never, never>',
                ],
            ],
            'listCreatedInSingleStatementUsedAsArray' => [
                'code' => '<?php
                    function takesArray(array $arr) : void {}

                    /** @param list<int> $arr */
                    function takesList(array $arr) : void {}

                    $a = [1, 2];

                    takesArray($a);
                    takesList($a);

                    $a[] = 3;

                    takesArray($a);
                    takesList($a);

                    $b = $a;

                    $b[] = rand(0, 10);',
                'assertions' => [
                    '$a' => 'list{int, int, int}',
                    '$b' => 'list{int, int, int, int<0, 10>}',
                ],
            ],
            'listMergedWithTKeyedArrayList' => [
                'code' => '<?php
                    /** @param list<int> $arr */
                    function takesAnotherList(array $arr) : void {}

                    /** @param list<int> $arr */
                    function takesList(array $arr) : void {
                        if (rand(0, 1)) {
                            $arr = [1, 2, 3];
                        }

                        takesAnotherList($arr);
                    }',
            ],
            'listMergedWithTKeyedArrayListAfterAssertion' => [
                'code' => '<?php
                    /** @param list<int> $arr */
                    function takesAnotherList(array $arr) : void {}

                    /** @param list<int> $arr */
                    function takesList(array $arr) : void {
                        if ($arr) {
                            $arr = [4, 5, 6];
                        }

                        takesAnotherList($arr);
                    }',
            ],
            'nonEmptyAssertionOnListElement' => [
                'code' => '<?php
                    /** @param list<array<string, string>> $arr */
                    function takesList(array $arr) : void {
                        if (!empty($arr[0])) {
                            foreach ($arr[0] as $k => $v) {}
                        }
                    }',
            ],
            'nonEmptyAssignmentToListElement' => [
                'code' => '<?php
                    /**
                     * @param non-empty-list<string> $arr
                     * @return non-empty-list<string>
                     */
                    function takesList(array $arr) : array {
                        $arr[0] = "food";

                        return $arr;
                    }',
            ],
            'unpackedArgIsList' => [
                'code' => '<?php
                    final class Values
                    {
                        /**
                         * @psalm-var list<int>
                         */
                        private $ints = [];

                        /** @no-named-arguments */
                        public function set(int ...$ints): void {
                            $this->ints = $ints;
                        }
                    }',
            ],
            'assignStringFirstChar' => [
                'code' => '<?php
                    /** @param non-empty-list<string> $arr */
                    function foo(array $arr) : string {
                        $arr[0][0] = "a";
                        return $arr[0];
                    }',
            ],
            'arraySpread' => [
                'code' => '<?php
                    $arrayA = [1, 2, 3];
                    $arrayB = [4, 5];
                    $result = [0, ...$arrayA, ...$arrayB, 6 ,7];

                    $arr1 = [3 => 1, 1 => 2, 3];
                    $arr2 = [...$arr1];
                    $arr3 = [1 => 0, ...$arr1];',
                'assertions' => [
                    '$result' => 'list{int, int, int, int, int, int, int, int}',
                    '$arr2' => 'list{int, int, int}',
                    '$arr3' => 'array{1: int, 2: int, 3: int, 4: int}',
                ],
            ],
            'arraySpreadWithString' => [
                'code' => '<?php
                    $x = [
                        "a" => 0,
                        ...["a" => 1],
                        ...["b" => 2]
                    ];',
                'assertions' => [
                    '$x===' => 'array{a: 1, b: 2}',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'listPropertyAssignmentAfterIsset' => [
                'code' => '<?php
                    class Collection {
                        /** @var list<string> */
                        private $list = [];

                        public function override(int $offset): void {
                            if (isset($this->list[$offset])) {
                                $this->list[$offset] = "a";
                            }
                        }
                    }',
            ],
            'propertyAssignmentToTKeyedArrayIntKeys' => [
                'code' => '<?php
                    class Bar {
                        /** @var array{0: string, 1:string} */
                        private array $baz = ["a", "b"];

                        public function append(string $str) : void {
                            $this->baz[rand(0, 1) ? 0 : 1] = $str;
                        }
                    }',
            ],
            'propertyAssignmentToTKeyedArrayStringKeys' => [
                'code' => '<?php
                    class Bar {
                        /** @var array{a: string, b:string} */
                        private array $baz = ["a" => "c", "b" => "d"];

                        public function append(string $str) : void {
                            $this->baz[rand(0, 1) ? "a" : "b"] = $str;
                        }
                    }',
            ],
            'arrayMixedMixedNotAllowedFromObject' => [
                'code' => '<?php
                    function foo(ArrayObject $a) : array {
                        $arr = [];

                        /**
                         * @psalm-suppress MixedAssignment
                         */
                        foreach ($a as $k => $v) {
                            $arr[$k] = $v;
                        }

                        return $arr;
                    }',
            ],
            'arrayMixedMixedNotAllowedFromMixed' => [
                'code' => '<?php
                    /** @psalm-suppress MissingParamType */
                    function foo($a) : array {
                        $arr = ["a" => "foo"];

                        /**
                         * @psalm-suppress MixedAssignment
                         * @psalm-suppress MixedArrayOffset
                         */
                        foreach ($a as $k => $v) {
                            $arr[$k] = $v;
                        }

                        return $arr;
                    }',
            ],
            'assignNestedKey' => [
                'code' => '<?php
                    /**
                     * @psalm-suppress MixedAssignment
                     * @psalm-suppress MixedArrayOffset
                     *
                     * @psalm-return array<true>
                     */
                    function getAutoComplete(array $data): array {
                        $response = ["s" => []];

                        foreach ($data as $suggestion) {
                            $response["s"][$suggestion] = true;
                        }

                        return $response["s"];
                    }',
            ],
            'assignArrayUnion' => [
                'code' => '<?php
                    /**
                     * @psalm-suppress MixedArrayOffset
                     */
                    function foo(array $out) : array {
                        $key = 1;

                        if (rand(0, 1)) {
                            /** @var mixed */
                            $key = null;
                        }

                        $out[$key] = 5;
                        return $out;
                    }',
            ],
            'mergeWithNestedMixed' => [
                'code' => '<?php
                    function getArray() : array {
                        return [];
                    }

                    $arr = getArray();

                    if (rand(0, 1)) {
                        /** @psalm-suppress MixedArrayAssignment */
                        $arr["hello"]["goodbye"] = 5;
                    }',
                'assertions' => [
                    '$arr' => 'array<array-key, mixed>',
                ],
            ],
            'dontUpdateMixedArrayWithStringKey' => [
                'code' => '<?php
                    class A {}

                    /**
                     * @psalm-suppress MixedArgument
                     */
                    function run1(array $arguments): void {
                        if (rand(0, 1)) {
                            $arguments["c"] = new A();
                        }

                        if ($arguments["b"]) {
                            echo $arguments["b"];
                        }
                    }',
            ],
            'manipulateArrayTwice' => [
                'code' => '<?php
                    /** @var array */
                    $options = [];
                    $options[\'a\'] = 1;
                    /** @psalm-suppress MixedArrayAssignment */
                    $options[\'b\'][\'c\'] = 2;',
                'assertions' => [
                    '$options[\'b\']' => 'mixed',
                ],
            ],
            'assignWithLiteralStringKey' => [
                'code' => '<?php
                    /**
                     * @param array<int, array{internal: bool, ported: bool}> $i
                     * @return array<int, array{internal: bool, ported: bool}>
                     */
                    function addOneEntry(array $i, int $id): array {
                        $i[$id][rand(0, 1) ? "internal" : "ported"] = true;
                        return $i;
                    }',
            ],
            'binaryOperation' => [
                'code' => '<?php
                    $a = array_map(
                        function (string $x) {
                            return new RuntimeException($x);
                        },
                        ["c" => ""]
                    );

                    $a += ["e" => new RuntimeException()];',
                'assertions' => [
                    '$a' => 'array{c: RuntimeException, e: RuntimeException}',
                ],
            ],
            'mergeArrayKeysProperly' => [
                'code' => '<?php
                    interface EntityInterface {}

                    class SomeEntity implements EntityInterface {}

                    /**
                     * @param array<class-string<EntityInterface>, bool> $arr
                     * @return array<class-string<EntityInterface>, bool>
                     */
                    function createForEntity(array $arr)
                    {
                        $arr[SomeEntity::class] = true;

                        return $arr;
                    }',
            ],
            'lowercaseStringMergeWithLiteral' => [
                'code' => '<?php
                    /**
                     * @param array<lowercase-string, bool> $foo
                     * @return array<lowercase-string, bool>
                     */
                    function foo(array $foo) : array {
                        $foo["hello"] = true;
                        return $foo;
                    }',
            ],
            'updateListValueAndMaintainListnessAfterGreaterThanOrEqual' => [
                'code' => '<?php
                    /**
                     * @param list<int> $l
                     * @return list<int>
                     */
                    function takesList(array $l) {
                        if (count($l) < 2) {
                            throw new \Exception("bad");
                        }

                        $l[1] = $l[1] + 1;

                        return $l;
                    }',
            ],
            'updateListValueAndMaintainListnessAfterNotIdentical' => [
                'code' => '<?php
                    /**
                     * @param list<int> $l
                     * @return list<int>
                     */
                    function takesList(array $l) {
                        if (count($l) !== 2) {
                            throw new \Exception("bad");
                        }

                        $l[1] = $l[1] + 1;

                        return $l;
                    }',
            ],
            'unpackTypedIterableIntoArray' => [
                'code' => '<?php

                /**
                 * @param iterable<int, string> $data
                 * @return list<string>
                 */
                function unpackIterable(iterable $data): array
                {
                    return [...$data];
                }',
            ],
            'unpackTypedTraversableIntoArray' => [
                'code' => '<?php

                /**
                 * @param Traversable<int, string> $data
                 * @return list<string>
                 */
                function unpackIterable(Traversable $data): array
                {
                    return [...$data];
                }',
            ],
            'unpackEmptyArrayIsEmpty' => [
                'code' => '<?php
                    $x = [];
                    $y = [];

                    $x = [...$x, ...$y];
                ',
                'assertions' => ['$x===' => 'array<never, never>'],
            ],
            'unpackListCanBeEmpty' => [
                'code' => '<?php
                    /** @var list<int> */
                    $x = [];
                    /** @var list<int> */
                    $y = [];

                    $x = [...$x, ...$y];
                ',
                'assertions' => ['$x===' => 'list<int>'],
            ],
            'unpackNonEmptyListIsNotEmpty' => [
                'code' => '<?php
                    /** @var non-empty-list<int> */
                    $x = [];
                    /** @var non-empty-list<int> */
                    $y = [];

                    $x = [...$x, ...$y];
                ',
                'assertions' => ['$x===' => 'list{int, int, ...<int>}'],
            ],
            'unpackEmptyKeepsCorrectKeys' => [
                'code' => '<?php
                    $a = [];
                    $b = [1];
                    $c = [];
                    $d = [2];

                    $e = [...$a, ...$b, ...$c, ...$d, 3];
                ',
                'assertions' => ['$e===' => 'list{1, 2, 3}'],
            ],
            'unpackArrayCanBeEmpty' => [
                'code' => '<?php
                    /** @var array<array-key, int> */
                    $x = [];
                    /** @var array<array-key, int> */
                    $y = [];

                    $x = [...$x, ...$y];
                ',
                'assertions' => ['$x===' => 'array<array-key, int>'],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'unpackNonEmptyArrayIsNotEmpty' => [
                'code' => '<?php
                    /** @var non-empty-array<array-key, int> */
                    $x = [];
                    /** @var non-empty-array<array-key, int> */
                    $y = [];

                    $x = [...$x, ...$y];
                ',
                'assertions' => ['$x===' => 'non-empty-array<array-key, int>'],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'unpackIntKeyedArrayResultsInList' => [
                'code' => '<?php
                    /** @var array<int, int> */
                    $x = [];
                    /** @var array<int, int> */
                    $y = [];

                    $x = [...$x, ...$y];
                ',
                'assertions' => ['$x===' => 'list<int>'],
            ],
            'unpackStringKeyedArrayPhp8.1' => [
                'code' => '<?php
                    /** @var array<string, int> */
                    $x = [];
                    /** @var array<array-key, int> */
                    $y = [];

                    $x = [...$x, ...$y];
                ',
                'assertions' => ['$x===' => 'array<array-key, int>'],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'unpackLiteralStringKeyedArrayPhp8.1' => [
                'code' => '<?php
                    /** @var array<"foo"|"bar", int> */
                    $x = [];
                    /** @var array<"baz", int> */
                    $y = [];

                    $x = [...$x, ...$y];
                ',
                'assertions' => ['$x===' => "array<'bar'|'baz'|'foo', int>"],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'unpackArrayShapesUnionsLaterUnpacks' => [
                'code' => '<?php
                    $shape = ["foo" => 1, "bar" => 2, 10 => 3];
                    /** @var array<int, 4> */
                    $a = [];
                    /** @var list<5> */
                    $b = [];
                    /** @var array<array-key, 6> */
                    $c = [];

                    $x = [...$a, ...$b, ...$c, ...$shape]; // Shape is last so it overrides previous
                    $y = [...$shape, ...$a, ...$b, ...$c]; // Shape is first, but only possibly matching keys union their values
                ',
                'assertions' => [
                    '$x===' => 'array{0: 3, bar: 2, foo: 1, ...<array-key, 4|5|6>}',
                    '$y===' => 'array{0: 3|4|5|6, bar: 2|6, foo: 1|6, ...<array-key, 4|5|6>}',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'unpackNonObjectlike' => [
                'code' => '<?php
                    /** @return list<mixed> */
                    function test(): array {
                        return [];
                    }

                    $x = [...test(), "a" => "b"];
                ',
                'assertions' => ['$x===' => "array{a: 'b', ...<int<0, max>, mixed>}"],
            ],
            'checkTraversableUnpackTemplatesCorrectly' => [
                'code' => '<?php
                    /**
                     * @template T1
                     * @template T2
                     * @template TKey
                     * @template TValue
                     * @extends Traversable<TKey, TValue>
                     */
                    interface Foo extends Traversable {}

                    /**
                     * @param Foo<"a"|"b", "c"|"d", "e"|"f", "g"|"h"> $foo
                     * @return array<"e"|"f", "g"|"h">
                     */
                    function foobar(Foo $foo): array
                    {
                        return [...$foo];
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'unpackIncorrectlyExtendedInterface' => [
                'code' => '<?php
                    /**
                     * @template TKey
                     * @template TValue of scalar
                     * @extends Traversable<TKey, TValue>
                     */
                    interface Foo extends Traversable {}

                    /**
                     * @psalm-suppress MissingTemplateParam
                     * @template TKey
                     * @extends Foo<TKey>
                     */
                    interface Bar extends Foo {}

                    /**
                     * @param Bar<int> $bar
                     * @return list<scalar>
                     */
                    function foobar(Bar $bar): array
                    {
                        $unpacked = [...$bar];
                        return $unpacked;
                    }
                ',
            ],
            'unpackGrandchildOfTraversable' => [
                'code' => '<?php
                    /**
                     * @template T1
                     * @template T2
                     * @template TKey
                     * @template TValue
                     * @extends Traversable<TKey, TValue>
                     */
                    interface Foo extends Traversable {}

                    /** @extends Foo<"a", "b", "c", "d"> */
                    interface Bar extends Foo {}

                    /**
                     * @return array<"c", "d">
                     */
                    function foobar(Bar $bar): array
                    {
                        return [...$bar];
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'unpackNonGenericGrandchildOfTraversable' => [
                'code' => '<?php
                    /** @extends Traversable<string, string> */
                    interface Foo extends Traversable {}

                    interface Bar extends Foo {}

                    /**
                     * @return array<string, string>
                     */
                    function foobar(Bar $bar): array
                    {
                        return [...$bar];
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'unpackTNamedObjectShouldUseTemplateConstraints' => [
                'code' => '<?php
                    /**
                     * @template TKey of "a"|"b"
                     * @template TValue of "c"|"d"
                     * @extends Traversable<TKey, TValue>
                     */
                    interface Foo extends Traversable {}

                    /**
                     * @return array<"a"|"b", "c"|"d">
                     */
                    function foobar(Foo $foo): array
                    {
                        return [...$foo];
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],

            'ArrayOffsetNumericSupPHPINTMAX' => [
                'code' => '<?php
                    $_a = [
                        "9223372036854775808" => 1,
                        "9223372036854775809" => 2
                    ];
                ',
            ],
            'assignToListWithForeachKey' => [
                'code' => '<?php
                    /**
                     * @param list<string> $list
                     * @return list<string>
                     */
                    function getList(array $list): array {
                        foreach ($list as $key => $value) {
                            $list[$key] = $value . "!";
                        }

                        return $list;
                    }',
            ],
            'ArrayCreateTemplateArrayKey' => [
                'code' => '<?php
                /**
                  * @template K of array-key
                  * @param K $key
                  */
                function with($key): void
                {
                    [$key => 123];
                }',
            ],
            'assignStringIndexed' => [
                'code' => '<?php
                    /**
                     * @param array<string, mixed> $array
                     * @return non-empty-array<string, mixed>
                     */
                    function getArray(array $array): array {
                        if (rand(0, 1)) {
                            $array["a"] = 2;
                        } else {
                            $array["b"] = 1;
                        }
                        return $array;
                    }',
            ],
            'castPossiblyArray'  => [
                'code' => '<?php
                    /**
                     * @psalm-param string|list<string> $a
                     * @return list<string>
                     */
                    function addHeaders($a): array {
                        return (array)$a;
                    }',
            ],
            'ClassConstantAsKey'  => [
                'code' => '<?php
                    /**
                     * @property Foo::C_* $aprop
                     */
                    class Foo {
                        public const C_ONE = 1;
                        public const C_TWO = 2;

                        public function __get(string $prop) {
                            if ($prop === "aprop")
                                return self::C_ONE;
                            throw new \RuntimeException("Unsupported property: $prop");
                        }

                        /** @return array<Foo::C_*, string> */
                        public static function getNames(): array {
                            return [
                                self::C_ONE => "One",
                                self::C_TWO => "Two",
                            ];
                        }

                        public function getThisName(): string {
                            $names = self::getNames();
                            $aprop = $this->aprop;

                            return $names[$aprop];
                        }
                    }',
            ],
            'AddTwoSealedArrays'  => [
                'code' => '<?php
                    final class Token
                    {
                        public const ONE = [
                            16 => 16,
                        ];

                        public const TWO = [
                            17 => 17,
                        ];

                        public const THREE = [
                            18 => 18,
                        ];
                    }
                    $_a = Token::ONE + Token::TWO + Token::THREE;
                    ',
                'assertions' => ['$_a===' => 'array{16: 16, 17: 17, 18: 18}'],
            ],
            'unpackTypedIterableWithStringKeysIntoArray' => [
                'code' => '<?php

                /**
                 * @param iterable<string, string> $data
                 * @return array<string, string>
                 */
                function unpackIterable(iterable $data): array
                {
                    return [...$data];
                }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'unpackTypedTraversableWithStringKeysIntoArray' => [
                'code' => '<?php

                    /**
                     * @param Traversable<string, string> $data
                     * @return array<string, string>
                     */
                    function unpackIterable(Traversable $data): array
                    {
                        return [...$data];
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'unpackArrayWithArrayKeyIntoArray' => [
                'code' => '<?php

                /**
                 * @param array<array-key, mixed> $data
                 * @return array<array-key, mixed>
                 */
                function unpackArray(array $data): array
                {
                    return [...$data];
                }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'unpackArrayWithTwoTypesNotObjectLike' => [
                'code' => '<?php
                    function int(): int
                    {
                        return 0;
                    }

                    /**
                     * @return list<positive-int>
                     */
                    function posiviteIntegers(): array
                    {
                        return [1];
                    }

                    $_a = [...posiviteIntegers(), int()];
                    /** @psalm-check-type $_a = non-empty-list<int> */
                ',
            ],
            'nullableDestructuring' => [
                'code' => '<?php
                    /**
                     * @return array{"foo", "bar"}|null
                     */
                    function foobar(): ?array
                    {
                        return null;
                    }

                    [$_foo, $_bar] = foobar();
                    ',
                'assertions' => [
                    '$_foo' => 'null|string',
                    '$_bar' => 'null|string',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'allowsArrayAccessNullOffset' => [
                'code' => '<?php
                    /**
                     * @template-implements ArrayAccess<int, string>
                     */
                    class C implements ArrayAccess {
                        public function offsetExists(int $offset) : bool { return true; }

                        public function offsetGet($offset) : string { return "";}

                        public function offsetSet(int $offset, string $value) : void {}

                        public function offsetUnset(int $offset) : void { }
                    }

                    $c = new C();
                    $c[] = "hello";',
            ],
            'conditionalRestrictedDocblockKeyAssignment' => [
                'code' => '<?php


                /**
                 * @return array{booking: array{active: false, icon: "settings"}, phones: array{active: false, icon: "phone-tube"}, stat: array{active: false, icon: "review"}, support: array{active: false, icon: "help"}}
                 */
                function getSections(): array {
                    return [
                            "phones" => [
                                "active" => false,
                                "icon" => "phone-tube",
                            ],
                            "stat" => [
                                "active" => false,
                                "icon" => "review",
                            ],
                            "booking" => [
                                "active" => false,
                                "icon" => "settings",
                            ],
                            "support" => [
                                "active" => false,
                                "icon" => "help",
                            ],
                    ];
                }
                $items = getSections();
                /** @var string */
                $currentAction = "";
                if (\array_key_exists($currentAction, $items)) {
                    $items[$currentAction]["active"] = true;
                }',
            ],
            'listAppendShape' => [
                'code' => '<?php
                    $a = [];
                    $a[]= 0;
                    $a[]= 1;
                    $a[]= 2;

                    $b = [0];
                    $b[]= 1;
                    $b[]= 2;',
                'assertions' => [
                    '$a===' => 'list{0, 1, 2}',
                    '$b===' => 'list{0, 1, 2}',
                ],
            ],
            'appendValuesToMap' => [
                'code' => '<?php
                    /**
                     * @return array{foo:numeric-string}&array<non-empty-string,non-empty-string>
                     */
                    function defaultQueryParams(): array
                    {
                        return [
                           "foo" => "123",
                           "bar" => "baz",
                        ];
                    }

                    /**
                     * @return array<non-empty-string, non-empty-string>
                     */
                    function getQueryParams(): array
                    {
                        $queryParams = defaultQueryParams();
                        $queryParams["a"] = "zzz";
                        return $queryParams;
                    }',
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'objectAssignment' => [
                'code' => '<?php
                    class A {}
                    (new A)["b"] = 1;',
                'error_message' => 'UndefinedMethod',
            ],
            'invalidArrayAccess' => [
                'code' => '<?php
                    $a = 5;
                    $a[0] = 5;',
                'error_message' => 'InvalidArrayAssignment',
            ],
            'possiblyUndefinedArrayAccess' => [
                'code' => '<?php
                    if (rand(0,1)) {
                      $a = ["a" => 1];
                    } else {
                      $a = [2, 3];
                    }

                    echo $a[0];',
                'error_message' => 'PossiblyUndefinedArrayOffset',
            ],
            'mixedStringOffsetAssignment' => [
                'code' => '<?php
                    /** @var mixed */
                    $a = 5;
                    "hello"[0] = $a;',
                'error_message' => 'MixedStringOffsetAssignment',
                'ignored_issues' => ['MixedAssignment'],
            ],
            'mixedArrayArgument' => [
                'code' => '<?php
                    /** @param array<mixed, int|string> $foo */
                    function fooFoo(array $foo): void { }

                    function barBar(array $bar): void {
                        fooFoo($bar);
                    }

                    barBar([1, "2"]);',
                'error_message' => 'MixedArgumentTypeCoercion',
                'ignored_issues' => ['MixedAssignment'],
            ],
            'arrayPropertyAssignment' => [
                'code' => '<?php
                    class A {
                        /** @var string[] */
                        public $strs = ["a", "b", "c"];

                        /** @return void */
                        public function bar() {
                            $this->strs = [new stdClass()]; // no issue emitted
                        }
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'incrementalArrayPropertyAssignment' => [
                'code' => '<?php
                    class A {
                        /** @var string[] */
                        public $strs = ["a", "b", "c"];

                        /** @return void */
                        public function bar() {
                            $this->strs[] = new stdClass(); // no issue emitted
                        }
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'duplicateStringArrayKey' => [
                'code' => '<?php
                    $arr = [
                        "a" => 1,
                        "b" => 2,
                        "c" => 3,
                        "c" => 4,
                    ];',
                'error_message' => 'DuplicateArrayKey',
            ],
            'duplicateIntArrayKey' => [
                'code' => '<?php
                    $arr = [
                        0 => 1,
                        1 => 2,
                        2 => 3,
                        2 => 4,
                    ];',
                'error_message' => 'DuplicateArrayKey',
            ],
            'duplicateImplicitIntArrayKey' => [
                'code' => '<?php
                    $arr = [
                        1,
                        2,
                        3,
                        2 => 4,
                    ];',
                'error_message' => 'DuplicateArrayKey',
            ],
            'mixedArrayAssignmentOnVariable' => [
                'code' => '<?php
                    function foo(array $arr) : void {
                        $arr["foo"][0] = "5";
                    }',
                'error_message' => 'MixedArrayAssignment',
            ],
            'storageKeyMustBeObject' => [
                'code' => '<?php
                    $key = [1,2,3];
                    $storage = new \SplObjectStorage();
                    $storage[$key] = "test";',
                'error_message' => 'InvalidArgument',
            ],
            'listUsedAsArrayWrongType' => [
                'code' => '<?php
                    /** @param string[] $arr */
                    function takesArray(array $arr) : void {}

                    $a = [];
                    $a[] = 1;
                    $a[] = 2;

                    takesArray($a);',
                'error_message' => 'InvalidArgument',
            ],
            'listUsedAsArrayWrongListType' => [
                'code' => '<?php
                    /** @param list<string> $arr */
                    function takesArray(array $arr) : void {}

                    $a = [];
                    $a[] = 1;
                    $a[] = 2;

                    takesArray($a);',
                'error_message' => 'InvalidArgument',
            ],
            'nonEmptyAssignmentToListElementChangeType' => [
                'code' => '<?php
                    /**
                     * @param non-empty-list<string> $arr
                     * @return non-empty-list<string>
                     */
                    function takesList(array $arr) : array {
                        $arr[0] = 5;

                        return $arr;
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'preventArrayAssignmentOnReturnValue' => [
                'code' => '<?php
                    class A {
                        public function foo() : array {
                            return [1, 2, 3];
                        }
                    }

                    (new A)->foo()[3] = 5;',
                'error_message' => 'InvalidArrayAssignment',
            ],
            'mergeIntWithMixed' => [
                'code' => '<?php
                    function getCachedMixed(array $cache, string $locale) : string {
                        if (!isset($cache[$locale])) {
                            $cache[$locale] = 5;
                        }

                        /**
                         * @psalm-suppress MixedReturnStatement
                         */
                        return $cache[$locale];
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'mergeIntWithNestedMixed' => [
                'code' => '<?php
                    function getCachedMixed(array $cache, string $locale) : string {
                        if (!isset($cache[$locale][$locale])) {
                            /**
                             * @psalm-suppress MixedArrayAssignment
                             */
                            $cache[$locale][$locale] = 5;
                        }

                        /**
                         * @psalm-suppress MixedArrayAccess
                         * @psalm-suppress MixedReturnStatement
                         */
                        return $cache[$locale][$locale];
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'mergeWithDeeplyNestedArray' => [
                'code' => '<?php
                    /**
                     * @psalm-suppress MixedInferredReturnType
                     */
                    function getTwoPartsLocale(array $cache, string $a, string $b) : string
                    {
                        if (!isset($cache[$b])) {
                            $cache[$b] = array();
                        }

                        if (!isset($cache[$b][$a])) {
                            if (rand(0, 1)) {
                                /** @psalm-suppress MixedArrayAssignment */
                                $cache[$b][$a] = "hello";
                            } else {
                                /** @psalm-suppress MixedArrayAssignment */
                                $cache[$b][$a] = rand(0, 1) ? "string" : null;
                            }
                        }

                        /**
                         * @psalm-suppress MixedArrayAccess
                         * @psalm-suppress MixedReturnStatement
                         */
                        return $cache[$b][$a];
                    }',
                'error_message' => 'NullableReturnStatement',
            ],
            'ArrayCreateOffsetObject' => [
                'code' => '<?php
                    $_a = [new stdClass => "a"];
                ',
                'error_message' => 'InvalidArrayOffset',
            ],
            'ArrayDimOffsetObject' => [
                'code' => '<?php
                    $_a = [];
                    $_a[new stdClass] = "a";
                ',
                'error_message' => 'InvalidArrayOffset',
            ],
            'ArrayCreateOffsetResource' => [
                'code' => '<?php
                    $_a = [fopen("", "") => "a"];
                ',
                'error_message' => 'InvalidArrayOffset',
            ],
            'ArrayDimOffsetResource' => [
                'code' => '<?php
                    $_a = [];
                    $_a[fopen("", "")] = "a";
                ',
                'error_message' => 'InvalidArrayOffset',
            ],
            'ArrayCreateOffsetBool' => [
                'code' => '<?php
                    $_a = [true => "a"];
                ',
                'error_message' => 'InvalidArrayOffset',
            ],
            'ArrayDimOffsetBool' => [
                'code' => '<?php
                    $_a = [];
                    $_a[true] = "a";
                ',
                'error_message' => 'InvalidArrayOffset',
            ],
            'ArrayCreateOffsetStringable' => [
                'code' => '<?php
                    $a = new class{public function __toString(){return "";}};
                    $_a = [$a => "a"];',
                'error_message' => 'InvalidArrayOffset',
            ],
            'ArrayDimOffsetStringable' => [
                'code' => '<?php
                    $_a = [];
                    $a = new class{public function __toString(){return "";}};
                    $_a[$a] = "a";',
                'error_message' => 'InvalidArrayOffset',
            ],
            'coerceListToArray' => [
                'code' => '<?php
                    /**
                     * @param list<int> $_bar
                     */
                    function foo(array $_bar) : void {}

                    /**
                     * @param list<int> $bar
                     */
                    function baz(array $bar) : void {
                        foo((array) $bar);
                    }',
                'error_message' => 'RedundantCast',
            ],
            'arrayValuesOnList' => [
                'code' => '<?php
                    /**
                     * @param list<int> $a
                     * @return list<int>
                     */
                    function foo(array $a) : array {
                        return array_values($a);
                    }',
                'error_message' => 'RedundantFunctionCall',
            ],
            'assignToListWithUpdatedForeachKey' => [
                'code' => '<?php
                    /**
                     * @param list<string> $list
                     * @return list<string>
                     */
                    function getList(array $list): array {
                        foreach ($list as $key => $value) {
                            $list[$key + 1] = $value . "!";
                        }

                        return $list;
                    }',
                'error_message' => 'LessSpecificReturnStatement',
            ],
            // Skipped because the ref-type of array_pop was fixed (list->list)
            'SKIPPED-assignToListWithAlteredForeachKeyVar' => [
                'code' => '<?php
                    /**
                     * @param list<string> $list
                     * @return list<string>
                     */
                    function getList(array $list): array {
                        foreach ($list as $key => $value) {
                            if (rand(0, 1)) {
                                array_pop($list);
                            }

                            $list[$key] = $value . "!";
                        }

                        return $list;
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'createArrayWithMixedOffset' => [
                'code' => '<?php
                    /**
                     * @param mixed $index
                     */
                    function test($index): array {
                        $arr = [$index => 5];
                        return $arr;
                    }',
                'error_message' => 'MixedArrayOffset',
            ],
            'falseArrayAssignment' => [
                'code' => '<?php
                    function foo(): array {
                        $array = [];
                        $array[false] = "";
                        echo $array[0];
                        return $array;
                    }',
                'error_message' => 'InvalidArrayOffset',
            ],
            'TemplateAsKey' => [
                'code' => '<?php

                class Foo {

                    /**
                     * @psalm-template T of array
                     * @param T $offset
                     * @param array<array, string> $weird_array
                     */
                    public function getThisName($offset, $weird_array): string {
                        return $weird_array[$offset];
                    }
                }',
                'error_message' => 'InvalidArrayOffset',
            ],
            'unpackTypedIterableWithStringKeysIntoArray' => [
                'code' => '<?php
                    /**
                     * @param iterable<string, string> $data
                     * @return list<string>
                     */
                    function unpackIterable(iterable $data): array
                    {
                        return [...$data];
                    }
                ',
                'error_message' => 'DuplicateArrayKey',
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'unpackTypedTraversableWithStringKeysIntoArray' => [
                'code' => '<?php
                    /**
                     * @param Traversable<string, string> $data
                     * @return list<string>
                     */
                    function unpackIterable(Traversable $data): array
                    {
                        return [...$data];
                    }
                ',
                'error_message' => 'DuplicateArrayKey',
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'unpackArrayWithArrayKeyIntoArray' => [
                'code' => '<?php
                    /**
                     * @param array<array-key, mixed> $data
                     * @return list<mixed>
                     */
                    function unpackArray(array $data): array
                    {
                        return [...$data];
                    }
                ',
                'error_message' => 'DuplicateArrayKey',
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'unpackNonIterable' => [
                'code' => '<?php
                    class Foo {}
                    $foo = new Foo();
                    $arr = [...$foo];
                ',
                'error_message' => 'InvalidOperand',
            ],
            'cantUnpackWhenKeyIsntArrayKey' => [
                'code' => '<?php
                    /** @var Traversable<object, object> */
                    $foo = [];
                    $bar = [...$foo];
                ',
                'error_message' => 'InvalidOperand',
            ],
            'unpackTraversableWithKeyOmitted' => [
                'code' => '<?php
                    /** @extends Traversable<int> */
                    interface Foo extends Traversable {}

                    /**
                     * @return array<int, mixed>
                     */
                    function foobar(Foo $foo): array
                    {
                        return [...$foo];
                    }
                ',
                'error_message' => 'InvalidOperand',
            ],
        ];
    }
}
