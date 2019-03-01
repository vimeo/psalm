<?php
namespace Psalm\Tests;

use Psalm\Context;

class ArrayAssignmentTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return void
     */
    public function testConditionalAssignment()
    {
        $this->addFile(
            'somefile.php',
            '<?php
                if ($b) {
                    $foo["a"] = "hello";
                }'
        );

        $context = new Context();
        $context->vars_in_scope['$b'] = \Psalm\Type::getBool();
        $context->vars_in_scope['$foo'] = \Psalm\Type::getArray();

        $this->analyzeFile('somefile.php', $context);

        $this->assertFalse(isset($context->vars_in_scope['$foo[\'a\']']));
    }

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse()
    {
        return [
            'genericArrayCreationWithSingleIntValue' => [
                '<?php
                    $out = [];

                    $out[] = 4;',
                'assertions' => [
                    '$out' => 'non-empty-array<int, int>',
                ],
            ],
            'genericArrayCreationWithInt' => [
                '<?php
                    $out = [];

                    foreach ([1, 2, 3, 4, 5] as $value) {
                        $out[] = 4;
                    }',
                'assertions' => [
                    '$out' => 'non-empty-array<int, int>',
                ],
            ],
            'generic2dArrayCreation' => [
                '<?php
                    $out = [];

                    foreach ([1, 2, 3, 4, 5] as $value) {
                        $out[] = [4];
                    }',
                'assertions' => [
                    '$out' => 'non-empty-array<int, array{0:int}>',
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
                    '$out' => 'non-empty-array<int, non-empty-array<int, int>>',
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
                    '$out' => 'array<int, B>',
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
                    '$out' => 'array<int, int>',
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
                    '$out' => 'array<int, string|int>',
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
                    '$out' => 'array<int, string|int>',
                ],
            ],
            'implicitIntArrayCreation' => [
                '<?php
                    $foo = [];
                    $foo[] = "hello";',
                'assertions' => [
                    '$foo' => 'non-empty-array<int, string>',
                ],
            ],
            'implicit2dIntArrayCreation' => [
                '<?php
                    $foo = [];
                    $foo[][] = "hello";',
                'assertions' => [
                    '$foo' => 'non-empty-array<int, array<int, string>>',
                ],
            ],
            'implicit3dIntArrayCreation' => [
                '<?php
                    $foo = [];
                    $foo[][][] = "hello";',
                'assertions' => [
                    '$foo' => 'non-empty-array<int, array<int, array<int, string>>>',
                ],
            ],
            'implicit4dIntArrayCreation' => [
                '<?php
                    $foo = [];
                    $foo[][][][] = "hello";',
                'assertions' => [
                    '$foo' => 'non-empty-array<int, array<int, array<int, array<int, string>>>>',
                ],
            ],
            'implicitIndexedIntArrayCreation' => [
                '<?php
                    $foo = [];
                    $foo[0] = "hello";
                    $foo[1] = "hello";
                    $foo[2] = "hello";

                    $bar = [0, 1, 2];

                    $bat = [];

                    foreach ($foo as $i => $text) {
                        $bat[$text] = $bar[$i];
                    }',
                'assertions' => [
                    '$foo' => 'array{0:string, 1:string, 2:string}',
                    '$bar' => 'array{0:int, 1:int, 2:int}',
                    '$bat' => 'array<string, int>',
                ],
            ],
            'implicitStringArrayCreation' => [
                '<?php
                    $foo = [];
                    $foo["bar"] = "hello";',
                'assertions' => [
                    '$foo' => 'array{bar:string}',
                    '$foo[\'bar\']' => 'string',
                ],
            ],
            'implicit2dStringArrayCreation' => [
                '<?php
                    $foo = [];
                    $foo["bar"]["baz"] = "hello";',
                'assertions' => [
                    '$foo' => 'array{bar:array{baz:string}}',
                    '$foo[\'bar\'][\'baz\']' => 'string',
                ],
            ],
            'implicit3dStringArrayCreation' => [
                '<?php
                    $foo = [];
                    $foo["bar"]["baz"]["bat"] = "hello";',
                'assertions' => [
                    '$foo' => 'array{bar:array{baz:array{bat:string}}}',
                    '$foo[\'bar\'][\'baz\'][\'bat\']' => 'string',
                ],
            ],
            'implicit4dStringArrayCreation' => [
                '<?php
                    $foo = [];
                    $foo["bar"]["baz"]["bat"]["bap"] = "hello";',
                'assertions' => [
                    '$foo' => 'array{bar:array{baz:array{bat:array{bap:string}}}}',
                    '$foo[\'bar\'][\'baz\'][\'bat\'][\'bap\']' => 'string',
                ],
            ],
            '2Step2dStringArrayCreation' => [
                '<?php
                    $foo = ["bar" => []];
                    $foo["bar"]["baz"] = "hello";',
                'assertions' => [
                    '$foo' => 'array{bar:array{baz:string}}',
                    '$foo[\'bar\'][\'baz\']' => 'string',
                ],
            ],
            '2StepImplicit3dStringArrayCreation' => [
                '<?php
                    $foo = ["bar" => []];
                    $foo["bar"]["baz"]["bat"] = "hello";',
                'assertions' => [
                    '$foo' => 'array{bar:array{baz:array{bat:string}}}',
                ],
            ],
            'conflictingTypesWithNoAssignment' => [
                '<?php
                    $foo = [
                        "bar" => ["a" => "b"],
                        "baz" => [1]
                    ];',
                'assertions' => [
                    '$foo' => 'array{bar:array{a:string}, baz:array{0:int}}',
                ],
            ],
            'implicitObjectLikeCreation' => [
                '<?php
                    $foo = [
                        "bar" => 1,
                    ];
                    $foo["baz"] = "a";',
                'assertions' => [
                    '$foo' => 'array{bar:int, baz:string}',
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
                    '$foo' => 'array{bar:array{a:string, bam:array{baz:string}}, baz:array{0:int}}',
                ],
            ],
            'conflictingTypesWithAssignment2' => [
                '<?php
                    $foo = [];
                    $foo["a"] = "hello";
                    $foo["b"][] = "goodbye";
                    $bar = $foo["a"];',
                'assertions' => [
                    '$foo' => 'array{a:string, b:array<int, string>}',
                    '$foo[\'a\']' => 'string',
                    '$foo[\'b\']' => 'array<int, string>',
                    '$bar' => 'string',
                ],
            ],
            'conflictingTypesWithAssignment3' => [
                '<?php
                    $foo = [];
                    $foo["a"] = "hello";
                    $foo["b"]["c"]["d"] = "goodbye";',
                'assertions' => [
                    '$foo' => 'array{a:string, b:array{c:array{d:string}}}',
                ],
            ],
            'nestedObjectLikeAssignment' => [
                '<?php
                    $foo = [];
                    $foo["a"]["b"] = "hello";
                    $foo["a"]["c"] = 1;',
                'assertions' => [
                    '$foo' => 'array{a:array{b:string, c:int}}',
                ],
            ],
            'conditionalObjectLikeAssignment' => [
                '<?php
                    $foo = ["a" => "hello"];
                    if (rand(0, 10) === 5) {
                        $foo["b"] = 1;
                    }
                    else {
                        $foo["b"] = 2;
                    }',
                'assertions' => [
                    '$foo' => 'array{a:string, b:int}',
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
                    '$a' => 'non-empty-array<string, array<int, string>>',
                    '$c' => 'non-empty-array<string, array<string, array<int, string>>>',
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
                    '$a' => 'array{0:string}',
                    '$b' => 'array{0:string}',
                ],
            ],
            'additionDifferentType' => [
                '<?php
                    $a = ["bar"];
                    $a += [1];

                    $b = ["bar"] + [1];',
                'assertions' => [
                    '$a' => 'array{0:string}',
                    '$b' => 'array{0:string}',
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
            'objectLikeArrayAddition' => [
                '<?php
                    $foo = [];
                    $foo["a"] = 1;
                    $foo += ["b" => [2, 3]];',
                'assertions' => [
                    '$foo' => 'array{a:int, b:array{0:int, 1:int}}',
                ],
            ],
            'nestedObjectLikeArrayAddition' => [
                '<?php
                    $foo = [];
                    $foo["root"]["a"] = 1;
                    $foo["root"] += ["b" => [2, 3]];',
                'assertions' => [
                    '$foo' => 'array{root:array{a:int, b:array{0:int, 1:int}}}',
                ],
            ],
            'updateStringIntKey1' => [
                '<?php
                    $a = [];

                    $a["a"] = 5;
                    $a[0] = 3;',
                'assertions' => [
                    '$a' => 'array{a:int, 0:int}',
                ],
            ],
            'updateStringIntKey2' => [
                '<?php
                    $string = "c";

                    $b = [];

                    $b[$string] = 5;
                    $b[0] = 3;',
                'assertions' => [
                    '$b' => 'array{0:int, c:int}',
                ],
            ],
            'updateStringIntKey3' => [
                '<?php
                    $string = "c";

                    $c = [];

                    $c[0] = 3;
                    $c[$string] = 5;',
                'assertions' => [
                    '$c' => 'array{0:int, c:int}',
                ],
            ],
            'updateStringIntKey4' => [
                '<?php
                    $int = 5;

                    $d = [];

                    $d[$int] = 3;
                    $d["a"] = 5;',
                'assertions' => [
                    '$d' => 'non-empty-array<int|string, int>',
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
                    '$e' => 'non-empty-array<string|int, int>',
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
                    '$a' => 'array{0:array{a:int, 0:int}}',
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
                    '$b' => 'array{0:array{0:int, c:int}}',
                    '$c' => 'array{0:array{0:int, c:int}}',
                    '$d' => 'array{0:array<int|string, int>}',
                    '$e' => 'array{0:array<string|int, int>}',
                ],
            ],
            'updateStringIntKeyWithObjectLikeRootAndNumberOffset' => [
                '<?php
                    $string = "c";
                    $int = 5;

                    $a = [];

                    $a["root"]["a"] = 5;
                    $a["root"][0] = 3;',
                'assertions' => [
                    '$a' => 'array{root:array{a:int, 0:int}}',
                ],
            ],
            'updateStringIntKeyWithObjectLikeRoot' => [
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
                    '$b' => 'array{root:array{0:int, c:int}}',
                    '$c' => 'array{root:array{0:int, c:int}}',
                    '$d' => 'array{root:array<int|string, int>}',
                    '$e' => 'array{root:array<string|int, int>}',
                ],
            ],
            'mixedArrayAssignmentWithStringKeys' => [
                '<?php
                    function foo(array $a) : array {
                        $a["b"]["c"] = 5;
                        echo $a["b"]["d"];
                        echo $a["a"];
                        return $a;
                    }',
                'assertions' => [],
                'error_levels' => ['MixedArrayAssignment', 'MixedArrayAccess', 'MixedArgument'],
            ],
            'mixedArrayCoercion' => [
                '<?php
                    /** @param int[] $arg */
                    function expect_int_array($arg): void { }
                    /** @return array */
                    function generic_array() { return []; }

                    expect_int_array(generic_array());

                    function expect_int(int $arg): void {}
                    /** @return mixed */
                    function return_mixed() { return 2; }
                    expect_int(return_mixed());',
                'assertions' => [],
                'error_levels' => ['MixedTypeCoercion', 'MixedArgument'],
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
            'changeObjectLikeType' => [
                '<?php
                    $a = ["b" => "c"];
                    $a["d"] = ["e" => "f"];
                    $a["b"] = 4;
                    $a["d"]["e"] = 5;',
                'assertions' => [
                    '$a[\'b\']' => 'int',
                    '$a[\'d\']' => 'array{e:int}',
                    '$a[\'d\'][\'e\']' => 'int',
                    '$a' => 'array{b:int, d:array{e:int}}',
                ],
            ],
            'changeObjectLikeTypeInIf' => [
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
                    '$a' => 'array{b:array{e:string}}',
                    '$a[\'b\']' => 'array{e:string}',
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
                'error_levels' => ['MixedAssignment'],
            ],
            'assignToNullDontDie' => [
                '<?php
                    $a = null;
                    $a[0][] = 1;',
                'assertions' => [
                    '$a' => 'array{0:array<int, int>}',
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
                         */
                        $a["b"]["d"] += $a["b"][$i];
                    }',
                'assertions' => [],
            ],
            'keyedIntOffsetArrayValues' => [
                '<?php
                    $a = ["hello", 5];
                    $a_values = array_values($a);
                    $a_keys = array_keys($a);',
                'assertions' => [
                    '$a' => 'array{0:string, 1:int}',
                    '$a_values' => 'array<int, string|int>',
                    '$a_keys' => 'array<int, int>',
                ],
            ],
            'changeIntOffsetKeyValuesWithDirectAssignment' => [
                '<?php
                    $b = ["hello", 5];
                    $b[0] = 3;',
                'assertions' => [
                    '$b' => 'array{0:int, 1:int}',
                ],
            ],
            'changeIntOffsetKeyValuesAfterCopy' => [
                '<?php
                    $b = ["hello", 5];
                    $c = $b;
                    $c[0] = 3;',
                'assertions' => [
                    '$b' => 'array{0:string, 1:int}',
                    '$c' => 'array{0:int, 1:int}',
                ],
            ],
            'mergeIntOffsetValues' => [
                '<?php
                    $d = array_merge(["hello", 5], []);
                    $e = array_merge(["hello", 5], ["hello again"]);',
                'assertions' => [
                    '$d' => 'array{0:string, 1:int}',
                    '$e' => 'array{0:string, 1:int, 2:string}',
                ],
            ],
            'addIntOffsetToEmptyArray' => [
                '<?php
                    $f = [];
                    $f[0] = "hello";',
                'assertions' => [
                    '$f' => 'array{0:string}',
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
                    '$a' => 'non-empty-array<int, int>|null',
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
                    '$a' => 'array<int, int>|null',
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
                    '$a' => 'non-empty-array<int, int>|null',
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
                'error_levels' => ['MixedMethodCall', 'MixedArrayOffset', 'MixedTypeCoercion'],
            ],
            'mixedAccessNestedKeys' => [
                '<?php
                    function takesString(string $s) : void {}
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
            'possiblyUndefinedArrayAccessWithArrayKeyExists' => [
                '<?php
                    if (rand(0,1)) {
                      $a = ["a" => 1];
                    } else {
                      $a = [2, 3];
                    }

                    if (array_key_exists(0, $a)) {
                        echo $a[0];
                    }',
            ],
            'noCrashOnArrayKeyExistsBracket' => [
                '<?php
                    class MyCollection {
                        /**
                         * @param int $commenter
                         * @param int $numToGet
                         * @return int[]
                         */
                        public function getPosters($commenter, $numToGet=10) {
                            $posters = array();
                            $count = 0;
                            $a = new ArrayObject([[1234]]);
                            $iter = $a->getIterator();
                            while ($iter->valid() && $count < $numToGet) {
                                $value = $iter->current();
                                if ($value[0] != $commenter) {
                                    if (!array_key_exists($value[0], $posters)) {
                                        $posters[$value[0]] = 1;
                                        $count++;
                                    }
                                }
                                $iter->next();
                            }
                            return array_keys($posters);
                        }
                    }',
                'assertions' => [],
                'error_levels' => [
                    'MixedArrayAccess', 'MixedAssignment', 'MixedArrayOffset',
                    'MixedArgument', 'MixedTypeCoercion',
                ],
            ],
            'accessArrayAfterSuppressingBugs' => [
                '<?php
                    $a = [];

                    foreach (["one", "two", "three"] as $key) {
                      /**
                       * @psalm-suppress EmptyArrayAccess
                       * @psalm-suppress InvalidOperand
                       * @psalm-suppress MixedOperand
                       */
                      $a[$key] += 5;
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
                    '$a' => 'array{1?:string, 0?:int}',
                    '$b' => 'array<empty, empty>',
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
                    '$out[\'attr\'][\'bar\']' => 'int'
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
                     * @template-implements ArrayAccess<int, string>
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
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,2?:string[],3?:bool,4?:string}>
     */
    public function providerInvalidCodeParse()
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
                'error_message' => 'MixedTypeCoercion',
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
            'possiblyUndefinedArrayAccessWithArrayKeyExistsOnWrongKey' => [
                '<?php
                    if (rand(0,1)) {
                      $a = ["a" => 1];
                    } else {
                      $a = [2, 3];
                    }

                    if (array_key_exists("a", $a)) {
                        echo $a[0];
                    }',
                'error_message' => 'PossiblyUndefinedArrayOffset',
            ],
            'possiblyUndefinedArrayAccessWithArrayKeyExistsOnMissingKey' => [
                '<?php
                    if (rand(0,1)) {
                      $a = ["a" => 1];
                    } else {
                      $a = [2, 3];
                    }

                    if (array_key_exists("b", $a)) {
                        echo $a[0];
                    }',
                'error_message' => 'PossiblyUndefinedArrayOffset',
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
            'mixedArrayAssignment' => [
                '<?php
                    $_GET["foo"][0] = "5";',
                'error_message' => 'MixedArrayAssignment',
            ],
            'implementsArrayAccessAllowNullOffset' => [
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
                'error_message' => 'InvalidArgument'
            ],
        ];
    }
}
