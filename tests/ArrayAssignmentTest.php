<?php

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
                }'
        );

        $context = new Context();
        $context->vars_in_scope['$b'] = Type::getBool();
        $context->vars_in_scope['$foo'] = Type::getArray();

        $this->analyzeFile('somefile.php', $context);

        $this->assertFalse(isset($context->vars_in_scope['$foo[\'a\']']));
    }

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'genericArrayCreationWithSingleIntValue' => [
                '<?php
                    $out = [];

                    $out[] = 4;',
                'assertions' => [
                    '$out' => 'non-empty-list<int>',
                ],
            ],
            'genericArrayCreationWithInt' => [
                '<?php
                    $out = [];

                    foreach ([1, 2, 3, 4, 5] as $value) {
                        $out[] = 4;
                    }',
                'assertions' => [
                    '$out' => 'non-empty-list<int>',
                ],
            ],
            'generic2dArrayCreation' => [
                '<?php
                    $out = [];

                    foreach ([1, 2, 3, 4, 5] as $value) {
                        $out[] = [4];
                    }',
                'assertions' => [
                    '$out' => 'non-empty-list<array{int}>',
                ],
            ],
            'generic2dArrayCreationAddedInIf' => [
                '<?php
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
                '<?php
                    class B {}

                    $out = [];

                    if (rand(0,10) === 10) {
                        $out[] = new B();
                    }',
                'assertions' => [
                    '$out' => 'list<B>',
                ],
            ],
            'genericArrayCreationWithElementAddedInSwitch' => [
                '<?php
                    $out = [];

                    switch (rand(0,10)) {
                        case 5:
                            $out[] = 4;
                            break;

                        case 6:
                            // do nothing
                    }',
                'assertions' => [
                    '$out' => 'list<int>',
                ],
            ],
            'genericArrayCreationWithElementsAddedInSwitch' => [
                '<?php
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
                    '$out' => 'list<int|string>',
                ],
            ],
            'genericArrayCreationWithElementsAddedInSwitchWithNothing' => [
                '<?php
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
                    '$out' => 'list<int|string>',
                ],
            ],
            'implicit2dIntArrayCreation' => [
                '<?php
                    $foo = [];
                    $foo[][] = "hello";',
                'assertions' => [
                    '$foo' => 'non-empty-list<array<int, string>>',
                ],
            ],
            'implicit3dIntArrayCreation' => [
                '<?php
                    $foo = [];
                    $foo[][][] = "hello";',
                'assertions' => [
                    '$foo' => 'non-empty-list<list<array<int, string>>>',
                ],
            ],
            'implicit4dIntArrayCreation' => [
                '<?php
                    $foo = [];
                    $foo[][][][] = "hello";',
                'assertions' => [
                    '$foo' => 'non-empty-list<list<list<array<int, string>>>>',
                ],
            ],
            'implicitIndexedIntArrayCreation' => [
                '<?php
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
                    '$bar' => 'array{int, int, int}',
                    '$bat' => 'non-empty-array<string, int>',
                ],
            ],
            'implicitStringArrayCreation' => [
                '<?php
                    $foo = [];
                    $foo["bar"] = "hello";',
                'assertions' => [
                    '$foo' => 'array{bar: string}',
                    '$foo[\'bar\']' => 'string',
                ],
            ],
            'implicit2dStringArrayCreation' => [
                '<?php
                    $foo = [];
                    $foo["bar"]["baz"] = "hello";',
                'assertions' => [
                    '$foo' => 'array{bar: array{baz: string}}',
                    '$foo[\'bar\'][\'baz\']' => 'string',
                ],
            ],
            'implicit3dStringArrayCreation' => [
                '<?php
                    $foo = [];
                    $foo["bar"]["baz"]["bat"] = "hello";',
                'assertions' => [
                    '$foo' => 'array{bar: array{baz: array{bat: string}}}',
                    '$foo[\'bar\'][\'baz\'][\'bat\']' => 'string',
                ],
            ],
            'implicit4dStringArrayCreation' => [
                '<?php
                    $foo = [];
                    $foo["bar"]["baz"]["bat"]["bap"] = "hello";',
                'assertions' => [
                    '$foo' => 'array{bar: array{baz: array{bat: array{bap: string}}}}',
                    '$foo[\'bar\'][\'baz\'][\'bat\'][\'bap\']' => 'string',
                ],
            ],
            '2Step2dStringArrayCreation' => [
                '<?php
                    $foo = ["bar" => []];
                    $foo["bar"]["baz"] = "hello";',
                'assertions' => [
                    '$foo' => 'array{bar: array{baz: string}}',
                    '$foo[\'bar\'][\'baz\']' => 'string',
                ],
            ],
            '2StepImplicit3dStringArrayCreation' => [
                '<?php
                    $foo = ["bar" => []];
                    $foo["bar"]["baz"]["bat"] = "hello";',
                'assertions' => [
                    '$foo' => 'array{bar: array{baz: array{bat: string}}}',
                ],
            ],
            'conflictingTypesWithNoAssignment' => [
                '<?php
                    $foo = [
                        "bar" => ["a" => "b"],
                        "baz" => [1]
                    ];',
                'assertions' => [
                    '$foo' => 'array{bar: array{a: string}, baz: array{int}}',
                ],
            ],
            'implicitTKeyedArrayCreation' => [
                '<?php
                    $foo = [
                        "bar" => 1,
                    ];
                    $foo["baz"] = "a";',
                'assertions' => [
                    '$foo' => 'array{bar: int, baz: string}',
                ],
            ],
            'conflictingTypesWithAssignment' => [
                '<?php
                    $foo = [
                        "bar" => ["a" => "b"],
                        "baz" => [1]
                    ];
                    $foo["bar"]["bam"]["baz"] = "hello";',
                'assertions' => [
                    '$foo' => 'array{bar: array{a: string, bam: array{baz: string}}, baz: array{int}}',
                ],
            ],
            'conflictingTypesWithAssignment2' => [
                '<?php
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
                '<?php
                    $foo = [];
                    $foo["a"] = "hello";
                    $foo["b"]["c"]["d"] = "goodbye";',
                'assertions' => [
                    '$foo' => 'array{a: string, b: array{c: array{d: string}}}',
                ],
            ],
            'nestedTKeyedArrayAssignment' => [
                '<?php
                    $foo = [];
                    $foo["a"]["b"] = "hello";
                    $foo["a"]["c"] = 1;',
                'assertions' => [
                    '$foo' => 'array{a: array{b: string, c: int}}',
                ],
            ],
            'conditionalTKeyedArrayAssignment' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
                    /** @var array<string, array<string, string>> */
                    $a = [];
                    $a["foo"] = ["bar" => "baz"];',
                'assertions' => [
                    '$a' => 'non-empty-array<string, non-empty-array<string, string>>',
                ],
            ],
            'additionWithEmpty' => [
                '<?php
                    $a = [];
                    $a += ["bar"];

                    $b = [] + ["bar"];',
                'assertions' => [
                    '$a' => 'array{0: string}',
                    '$b' => 'array{0: string}',
                ],
            ],
            'additionDifferentType' => [
                '<?php
                    $a = ["bar"];
                    $a += [1];

                    $b = ["bar"] + [1];',
                'assertions' => [
                    '$a' => 'array{0: string}',
                    '$b' => 'array{0: string}',
                ],
            ],
            'present1dArrayTypeWithVarKeys' => [
                '<?php
                    /** @var array<string, array<int, string>> */
                    $a = [];

                    $foo = "foo";

                    $a[$foo][] = "bat";',
                'assertions' => [],
            ],
            'present2dArrayTypeWithVarKeys' => [
                '<?php
                    /** @var array<string, array<string, array<int, string>>> */
                    $b = [];

                    $foo = "foo";
                    $bar = "bar";

                    $b[$foo][$bar][] = "bat";',
                'assertions' => [],
            ],
            'objectLikeWithIntegerKeys' => [
                '<?php
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
                '<?php
                    $foo = [];
                    $foo["a"] = 1;
                    $foo += ["b" => [2, 3]];',
                'assertions' => [
                    '$foo' => 'array{a: int, b: array{int, int}}',
                ],
            ],
            'objectLikeArrayIsNonEmpty' => [
                '<?php
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
                '<?php
                    $foo = [];
                    $foo["root"]["a"] = 1;
                    $foo["root"] += ["b" => [2, 3]];',
                'assertions' => [
                    '$foo' => 'array{root: array{a: int, b: array{int, int}}}',
                ],
            ],
            'updateStringIntKey1' => [
                '<?php
                    $a = [];

                    $a["a"] = 5;
                    $a[0] = 3;',
                'assertions' => [
                    '$a' => 'array{0: int, a: int}',
                ],
            ],
            'updateStringIntKey2' => [
                '<?php
                    $string = "c";

                    $b = [];

                    $b[$string] = 5;
                    $b[0] = 3;',
                'assertions' => [
                    '$b' => 'array{0: int, c: int}',
                ],
            ],
            'updateStringIntKey3' => [
                '<?php
                    $string = "c";

                    $c = [];

                    $c[0] = 3;
                    $c[$string] = 5;',
                'assertions' => [
                    '$c' => 'array{0: int, c: int}',
                ],
            ],
            'updateStringIntKey4' => [
                '<?php
                    $int = 5;

                    $d = [];

                    $d[$int] = 3;
                    $d["a"] = 5;',
                'assertions' => [
                    '$d' => 'array{5: int, a: int}',
                ],
            ],
            'updateStringIntKey5' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
                    function getThings(): array {
                      return [];
                    }

                    $arr = [];

                    foreach (getThings() as $a) {
                      $arr[$a->id] = $a;
                    }

                    echo $arr[0];',
                'assertions' => [],
                'error_levels' => ['MixedAssignment', 'MixedPropertyFetch', 'MixedArrayOffset', 'MixedArgument'],
            ],
            'changeTKeyedArrayType' => [
                '<?php
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
                '<?php
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
                '<?php
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
                'error_levels' => ['MixedMethodCall'],
            ],
            'mixedSwallowsArrayAssignment' => [
                '<?php
                    /** @psalm-suppress MixedAssignment */
                    $a = $_GET["foo"];

                    /** @psalm-suppress MixedArrayAssignment */
                    $a["bar"] = "cool";

                    /** @psalm-suppress MixedMethodCall */
                    $a->offsetExists("baz");',
            ],
            'implementsArrayAccessInheritingDocblock' => [
                '<?php
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
                'error_levels' => ['MixedAssignment', 'MixedReturnStatement'],
            ],
            'assignToNullDontDie' => [
                '<?php
                    $a = null;
                    $a[0][] = 1;',
                'assertions' => [
                    '$a' => 'array{0: non-empty-list<int>}',
                ],
                'error_levels' => ['PossiblyNullArrayAssignment'],
            ],
            'stringAssignment' => [
                '<?php
                    $str = "hello";
                    $str[0] = "i";',
                'assertions' => [
                    '$str' => 'string',
                ],
            ],
            'ignoreInvalidArrayOffset' => [
                '<?php
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
                '<?php
                    $a = ["hello", 5];
                    /** @psalm-suppress RedundantFunctionCall */
                    $a_values = array_values($a);
                    $a_keys = array_keys($a);',
                'assertions' => [
                    '$a' => 'array{string, int}',
                    '$a_values' => 'non-empty-list<int|string>',
                    '$a_keys' => 'non-empty-list<int>',
                ],
            ],
            'changeIntOffsetKeyValuesWithDirectAssignment' => [
                '<?php
                    $b = ["hello", 5];
                    $b[0] = 3;',
                'assertions' => [
                    '$b' => 'array{int, int}',
                ],
            ],
            'changeIntOffsetKeyValuesAfterCopy' => [
                '<?php
                    $b = ["hello", 5];
                    $c = $b;
                    $c[0] = 3;',
                'assertions' => [
                    '$b' => 'array{string, int}',
                    '$c' => 'array{int, int}',
                ],
            ],
            'mergeIntOffsetValues' => [
                '<?php
                    $d = array_merge(["hello", 5], []);
                    $e = array_merge(["hello", 5], ["hello again"]);',
                'assertions' => [
                    '$d' => 'array{0: string, 1: int}',
                    '$e' => 'array{0: string, 1: int, 2: string}',
                ],
            ],
            'addIntOffsetToEmptyArray' => [
                '<?php
                    $f = [];
                    $f[0] = "hello";',
                'assertions' => [
                    '$f' => 'array{0: string}',
                ],
            ],
            'dontIncrementIntOffsetForKeyedItems' => [
                '<?php
                    $a = [1, "a" => 2, 3];',
                'assertions' => [
                    '$a' => 'array{0: int, 1: int, a: int}',
                ],
            ],
            'assignArrayOrSetNull' => [
                '<?php
                    $a = [];

                    if (rand(0, 1)) {
                        $a[] = 4;
                    }

                    if (!$a) {
                        $a = null;
                    }',
                'assertions' => [
                    '$a' => 'non-empty-list<int>|null',
                ],
            ],
            'assignArrayOrSetNullInElseIf' => [
                '<?php
                    $a = [];

                    if (rand(0, 1)) {
                        $a[] = 4;
                    }

                    if ($a) {
                    } elseif (rand(0, 1)) {
                        $a = null;
                    }',
                'assertions' => [
                    '$a' => 'list<int>|null',
                ],
            ],
            'assignArrayOrSetNullInElse' => [
                '<?php
                    $a = [];

                    if (rand(0, 1)) {
                        $a[] = 4;
                    }

                    if ($a) {
                    } else {
                        $a = null;
                    }',
                'assertions' => [
                    '$a' => 'non-empty-list<int>|null',
                ],
            ],
            'mixedMethodCallArrayAccess' => [
                '<?php
                    function foo(object $obj) : array {
                        $ret = [];
                        $ret["a"][$obj->foo()] = 1;
                        return $ret["a"];
                    }',
                'assertions' => [],
                'error_levels' => ['MixedMethodCall', 'MixedArrayOffset'],
            ],
            'mixedAccessNestedKeys' => [
                '<?php
                    function takesString(string $s) : string { return "hello"; }
                    function updateArray(array $arr) : array {
                        foreach ($arr as $i => $item) {
                            $arr[$i]["a"]["b"] = 5;
                            $arr[$i]["a"]["c"] = takesString($arr[$i]["a"]["c"]);
                        }

                        return $arr;
                    }',
                'assertions' => [],
                'error_levels' => [
                    'MixedArrayAccess', 'MixedAssignment', 'MixedArrayOffset', 'MixedArrayAssignment', 'MixedArgument',
                ],
            ],
            'possiblyUndefinedArrayAccessWithIsset' => [
                '<?php
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
                '<?php
                    $a = [];

                    foreach (["one", "two", "three"] as $key) {
                        $a[$key] += rand(0, 10);
                    }

                    $a["four"] = true;

                    if ($a["one"]) {}',
            ],
            'noDuplicateImplicitIntArrayKey' => [
                '<?php
                    $arr = [1 => 0, 1, 2, 3];
                    $arr = [1 => "one", 2 => "two", "three"];',
            ],
            'noDuplicateImplicitIntArrayKeyLargeOffset' => [
                '<?php
                    $arr = [
                        48 => "A",
                        95 => "a", "b",
                    ];',
            ],
            'constArrayAssignment' => [
                '<?php
                    const BAR = 2;
                    $arr = [1 => 2];
                    $arr[BAR] = [6];
                    $bar = $arr[BAR][0];',
            ],
            'castToArray' => [
                '<?php
                    $a = (array) (rand(0, 1) ? [1 => "one"] : 0);
                    $b = (array) null;',
                'assertions' => [
                    '$a' => 'array{0?: int, 1?: string}',
                    '$b' => 'array<never, never>',
                ],
            ],
            'getOnCoercedArray' => [
                '<?php
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
                '<?php
                    function foo(array $arr) : void {
                        $arr["a"] = 1;

                        foreach ($arr["b"] as $b) {}
                    }',
                'assertions' => [],
                'error_levels' => ['MixedAssignment'],
            ],
            'implementsArrayAccessAllowNullOffset' => [
                '<?php
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
                '<?php
                    class A {
                        public array $arr = [];

                        public function foo() : void {
                            if (rand(0, 1)) {
                                $this->arr["a"] = "hello";
                            }

                            if (!$this->arr) {}
                        }
                    }'
            ],
            'arrayAssignmentAddsTypePossibilities' => [
                '<?php
                    function bar(array $value): void {
                        $value["b"] = "hello";
                        $value = $value + ["a" => 0];
                        if (is_int($value["a"])) {}
                    }'
            ],
            'coercePossiblyNullKeyToZero' => [
                '<?php
                    function int_or_null(): ?int {
                      return rand(0, 1) !== 0 ? 42 : null;
                    }

                    /**
                     * @return array<array-key, null>
                     */
                    function foo(): array {
                        $array = [];
                        /** @psalm-suppress PossiblyNullArrayOffset */
                        $array[int_or_null()] = null;
                        return $array;
                    }'
            ],
            'coerceNullKeyToZero' => [
                '<?php
                    /**
                     * @return array<int, null>
                     */
                    function foo(): array {
                        $array = [];
                        /** @psalm-suppress NullArrayOffset */
                        $array[null] = null;
                        return $array;
                    }'
            ],
            'listUsedAsArray' => [
                '<?php
                    function takesArray(array $arr) : void {}

                    $a = [];
                    $a[] = 1;
                    $a[] = 2;

                    takesArray($a);',
                'assertions' => [
                    '$a' => 'non-empty-list<int>'
                ],
            ],
            'listTakesEmptyArray' => [
                '<?php
                    /** @param list<int> $arr */
                    function takesList(array $arr) : void {}

                    $a = [];

                    takesList($a);',
                'assertions' => [
                    '$a' => 'array<never, never>'
                ],
            ],
            'listCreatedInSingleStatementUsedAsArray' => [
                '<?php
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
                    '$a' => 'array{int, int, int}',
                    '$b' => 'array{int, int, int, int<0, 10>}',
                ],
            ],
            'listMergedWithTKeyedArrayList' => [
                '<?php
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
                '<?php
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
                '<?php
                    /** @param list<array<string, string>> $arr */
                    function takesList(array $arr) : void {
                        if (!empty($arr[0])) {
                            foreach ($arr[0] as $k => $v) {}
                        }
                    }',
            ],
            'nonEmptyAssignmentToListElement' => [
                '<?php
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
                '<?php
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
                    }'
            ],
            'assignStringFirstChar' => [
                '<?php
                    /** @param non-empty-list<string> $arr */
                    function foo(array $arr) : string {
                        $arr[0][0] = "a";
                        return $arr[0];
                    }'
            ],
            'arraySpread' => [
                '<?php
                    $arrayA = [1, 2, 3];
                    $arrayB = [4, 5];
                    $result = [0, ...$arrayA, ...$arrayB, 6 ,7];

                    $arr1 = [3 => 1, 1 => 2, 3];
                    $arr2 = [...$arr1];
                    $arr3 = [1 => 0, ...$arr1];',
                [
                    '$result' => 'array{int, int, int, int, int, int, int, int}',
                    '$arr2' => 'array{int, int, int}',
                    '$arr3' => 'array{1: int, 2: int, 3: int, 4: int}',
                ]
            ],
            'arraySpreadWithString' => [
                '<?php
                    $x = [
                        "a" => 0,
                        ...["a" => 1],
                        ...["b" => 2]
                    ];',
                [
                    '$x===' => 'array{a: 1, b: 2}',
                ],
                [],
                '8.1'
            ],
            'listPropertyAssignmentAfterIsset' => [
                '<?php
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
                '<?php
                    class Bar {
                        /** @var array{0: string, 1:string} */
                        private array $baz = ["a", "b"];

                        public function append(string $str) : void {
                            $this->baz[rand(0, 1) ? 0 : 1] = $str;
                        }
                    }'
            ],
            'propertyAssignmentToTKeyedArrayStringKeys' => [
                '<?php
                    class Bar {
                        /** @var array{a: string, b:string} */
                        private array $baz = ["a" => "c", "b" => "d"];

                        public function append(string $str) : void {
                            $this->baz[rand(0, 1) ? "a" : "b"] = $str;
                        }
                    }',
            ],
            'arrayMixedMixedNotAllowedFromObject' => [
                '<?php
                    function foo(ArrayObject $a) : array {
                        $arr = [];

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
            'arrayMixedMixedNotAllowedFromMixed' => [
                '<?php
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
                '<?php
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
                    }'
            ],
            'assignArrayUnion' => [
                '<?php
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
                    }'
            ],
            'mergeWithNestedMixed' => [
                '<?php
                    function getArray() : array {
                        return [];
                    }

                    $arr = getArray();

                    if (rand(0, 1)) {
                        /** @psalm-suppress MixedArrayAssignment */
                        $arr["hello"]["goodbye"] = 5;
                    }',
                [
                    '$arr' => 'array<array-key, mixed>',
                ]
            ],
            'dontUpdateMixedArrayWithStringKey' => [
                '<?php
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
                '<?php
                    /** @var array */
                    $options = [];
                    $options[\'a\'] = 1;
                    /** @psalm-suppress MixedArrayAssignment */
                    $options[\'b\'][\'c\'] = 2;',
                [
                    '$options[\'b\']' => 'mixed'
                ]
            ],
            'assignWithLiteralStringKey' => [
                '<?php
                    /**
                     * @param array<int, array{internal: bool, ported: bool}> $i
                     * @return array<int, array{internal: bool, ported: bool}>
                     */
                    function addOneEntry(array $i, int $id): array {
                        $i[$id][rand(0, 1) ? "internal" : "ported"] = true;
                        return $i;
                    }'
            ],
            'binaryOperation' => [
                '<?php
                    $a = array_map(
                        function (string $x) {
                            return new RuntimeException($x);
                        },
                        ["c" => ""]
                    );

                    $a += ["e" => new RuntimeException()];',
                [
                    '$a' => 'array{c: RuntimeException, e: RuntimeException}',
                ]
            ],
            'mergeArrayKeysProperly' => [
                '<?php
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
                    }'
            ],
            'lowercaseStringMergeWithLiteral' => [
                '<?php
                    /**
                     * @param array<lowercase-string, bool> $foo
                     * @return array<lowercase-string, bool>
                     */
                    function foo(array $foo) : array {
                        $foo["hello"] = true;
                        return $foo;
                    }'
            ],
            'updateListValueAndMaintainListnessAfterGreaterThanOrEqual' => [
                '<?php
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
                    }'
            ],
            'updateListValueAndMaintainListnessAfterNotIdentical' => [
                '<?php
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
                    }'
            ],
            'unpackTypedIterableIntoArray' => [
                '<?php

                /**
                 * @param iterable<int, string> $data
                 * @return list<string>
                 */
                function unpackIterable(iterable $data): array
                {
                    return [...$data];
                }'
            ],
            'unpackTypedTraversableIntoArray' => [
                '<?php

                /**
                 * @param Traversable<int, string> $data
                 * @return list<string>
                 */
                function unpackIterable(Traversable $data): array
                {
                    return [...$data];
                }'
            ],
            'unpackCanBeEmpty' => [
                '<?php
                    $x = [];
                    $y = [];

                    $x = [...$x, ...$y];

                    $x ? 1 : 0;
                ',
            ],
            'unpackEmptyKeepsCorrectKeys' => [
                '<?php
                    $a = [];
                    $b = [1];
                    $c = [];
                    $d = [2];

                    $e = [...$a, ...$b, ...$c, ...$d, 3];
                ',
                'assertions' => ['$e' => 'array{int, int, int}']
            ],
            'unpackNonObjectlikePreventsObjectlikeArray' => [
                '<?php
                    /** @return list<mixed> */
                    function test(): array {
                        return [];
                    }

                    $x = [...test(), "a" => "b"];
                ',
                'assertions' => ['$x' => 'non-empty-array<int|string, mixed|string>']
            ],
            'ArrayOffsetNumericSupPHPINTMAX' => [
                '<?php
                    $_a = [
                        "9223372036854775808" => 1,
                        "9223372036854775809" => 2
                    ];
                ',
            ],
            'assignToListWithForeachKey' => [
                '<?php
                    /**
                     * @param list<string> $list
                     * @return list<string>
                     */
                    function getList(array $list): array {
                        foreach ($list as $key => $value) {
                            $list[$key] = $value . "!";
                        }

                        return $list;
                    }'
            ],
            'ArrayCreateTemplateArrayKey' => [
                '<?php
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
                '<?php
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
                    }'
            ],
            'castPossiblyArray'  => [
                '<?php
                    /**
                     * @psalm-param string|list<string> $a
                     * @return list<string>
                     */
                    function addHeaders($a): array {
                        return (array)$a;
                    }',
            ],
            'ClassConstantAsKey'  => [
                '<?php
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
                '<?php
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
                'assertions' => ['$_a===' => 'array{16: 16, 17: 17, 18: 18}']
            ],
            'unpackTypedIterableWithStringKeysIntoArray' => [
                '<?php

                /**
                 * @param iterable<string, string> $data
                 * @return list<string>
                 */
                function unpackIterable(iterable $data): array
                {
                    return [...$data];
                }',
                [],
                [],
                '8.1'
            ],
            'unpackTypedTraversableWithStringKeysIntoArray' => [
                '<?php

                    /**
                     * @param Traversable<string, string> $data
                     * @return list<string>
                     */
                    function unpackIterable(Traversable $data): array
                    {
                        return [...$data];
                    }',
                [],
                [],
                '8.1'
            ],
            'unpackArrayWithArrayKeyIntoArray' => [
                '<?php

                /**
                 * @param array<array-key, mixed> $data
                 * @return list<mixed>
                 */
                function unpackArray(array $data): array
                {
                    return [...$data];
                }',
                [],
                [],
                '8.1'
            ],
            'unpackArrayWithTwoTypesNotObjectLike' => [
                '<?php
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

                    $_a = [...posiviteIntegers(), int()];',
                'assertions' => [
                    '$_a' => 'non-empty-list<int>',
                ],
                [],
                '8.1'
            ],
            'nullableDestructuring' => [
                '<?php
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
                [],
                '8.1'
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,1?:string[],2?:bool,3?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'objectAssignment' => [
                '<?php
                    class A {}
                    (new A)["b"] = 1;',
                'error_message' => 'UndefinedMethod',
            ],
            'invalidArrayAccess' => [
                '<?php
                    $a = 5;
                    $a[0] = 5;',
                'error_message' => 'InvalidArrayAssignment',
            ],
            'possiblyUndefinedArrayAccess' => [
                '<?php
                    if (rand(0,1)) {
                      $a = ["a" => 1];
                    } else {
                      $a = [2, 3];
                    }

                    echo $a[0];',
                'error_message' => 'PossiblyUndefinedArrayOffset',
            ],
            'mixedStringOffsetAssignment' => [
                '<?php
                    /** @var mixed */
                    $a = 5;
                    "hello"[0] = $a;',
                'error_message' => 'MixedStringOffsetAssignment',
                'error_level' => ['MixedAssignment'],
            ],
            'mixedArrayArgument' => [
                '<?php
                    /** @param array<mixed, int|string> $foo */
                    function fooFoo(array $foo): void { }

                    function barBar(array $bar): void {
                        fooFoo($bar);
                    }

                    barBar([1, "2"]);',
                'error_message' => 'MixedArgumentTypeCoercion',
                'error_level' => ['MixedAssignment'],
            ],
            'arrayPropertyAssignment' => [
                '<?php
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
                '<?php
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
                '<?php
                    $arr = [
                        "a" => 1,
                        "b" => 2,
                        "c" => 3,
                        "c" => 4,
                    ];',
                'error_message' => 'DuplicateArrayKey',
            ],
            'duplicateIntArrayKey' => [
                '<?php
                    $arr = [
                        0 => 1,
                        1 => 2,
                        2 => 3,
                        2 => 4,
                    ];',
                'error_message' => 'DuplicateArrayKey',
            ],
            'duplicateImplicitIntArrayKey' => [
                '<?php
                    $arr = [
                        1,
                        2,
                        3,
                        2 => 4,
                    ];',
                'error_message' => 'DuplicateArrayKey',
            ],
            'mixedArrayAssignmentOnVariable' => [
                '<?php
                    function foo(array $arr) : void {
                        $arr["foo"][0] = "5";
                    }',
                'error_message' => 'MixedArrayAssignment',
            ],
            'implementsArrayAccessPreventNullOffset' => [
                '<?php
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
                'error_message' => 'NullArgument',
            ],
            'storageKeyMustBeObject' => [
                '<?php
                    $key = [1,2,3];
                    $storage = new \SplObjectStorage();
                    $storage[$key] = "test";',
                'error_message' => 'InvalidArgument',
            ],
            'listUsedAsArrayWrongType' => [
                '<?php
                    /** @param string[] $arr */
                    function takesArray(array $arr) : void {}

                    $a = [];
                    $a[] = 1;
                    $a[] = 2;

                    takesArray($a);',
                'error_message' => 'InvalidScalarArgument',
            ],
            'listUsedAsArrayWrongListType' => [
                '<?php
                    /** @param list<string> $arr */
                    function takesArray(array $arr) : void {}

                    $a = [];
                    $a[] = 1;
                    $a[] = 2;

                    takesArray($a);',
                'error_message' => 'InvalidScalarArgument',
            ],
            'nonEmptyAssignmentToListElementChangeType' => [
                '<?php
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
                '<?php
                    class A {
                        public function foo() : array {
                            return [1, 2, 3];
                        }
                    }

                    (new A)->foo()[3] = 5;',
                'error_message' => 'InvalidArrayAssignment',
            ],
            'mergeIntWithMixed' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
                    $_a = [new stdClass => "a"];
                ',
                'error_message' => 'InvalidArrayOffset'
            ],
            'ArrayDimOffsetObject' => [
                '<?php
                    $_a = [];
                    $_a[new stdClass] = "a";
                ',
                'error_message' => 'InvalidArrayOffset'
            ],
            'ArrayCreateOffsetResource' => [
                '<?php
                    $_a = [fopen("", "") => "a"];
                ',
                'error_message' => 'InvalidArrayOffset'
            ],
            'ArrayDimOffsetResource' => [
                '<?php
                    $_a = [];
                    $_a[fopen("", "")] = "a";
                ',
                'error_message' => 'InvalidArrayOffset'
            ],
            'ArrayCreateOffsetBool' => [
                '<?php
                    $_a = [true => "a"];
                ',
                'error_message' => 'InvalidArrayOffset'
            ],
            'ArrayDimOffsetBool' => [
                '<?php
                    $_a = [];
                    $_a[true] = "a";
                ',
                'error_message' => 'InvalidArrayOffset'
            ],
            'ArrayCreateOffsetStringable' => [
                '<?php
                    $a = new class{public function __toString(){return "";}};
                    $_a = [$a => "a"];',
                'error_message' => 'InvalidArrayOffset',
            ],
            'ArrayDimOffsetStringable' => [
                '<?php
                    $_a = [];
                    $a = new class{public function __toString(){return "";}};
                    $_a[$a] = "a";',
                'error_message' => 'InvalidArrayOffset',
            ],
            'coerceListToArray' => [
                '<?php
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
                '<?php
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
                '<?php
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
            'assignToListWithAlteredForeachKeyVar' => [
                '<?php
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
                'error_message' => 'LessSpecificReturnStatement',
            ],
            'createArrayWithMixedOffset' => [
                '<?php
                    /**
                     * @param mixed $index
                     */
                    function test($index): array {
                        $arr = [$index => 5];
                        return $arr;
                    }',
                'error_message' => 'MixedArrayOffset'
            ],
            'falseArrayAssignment' => [
                '<?php
                    function foo(): array {
                        $array = [];
                        $array[false] = "";
                        echo $array[0];
                        return $array;
                    }',
                'error_message' => 'InvalidArrayOffset'
            ],
            'TemplateAsKey' => [
                '<?php

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
                'error_message' => 'InvalidArrayOffset'
            ],
        ];
    }
}
