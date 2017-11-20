<?php
namespace Psalm\Tests;

use Psalm\Checker\FileChecker;
use Psalm\Context;

class ArrayAssignmentTest extends TestCase
{
    use Traits\FileCheckerInvalidCodeParseTestTrait;
    use Traits\FileCheckerValidCodeParseTestTrait;

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

        $file_checker = new FileChecker('somefile.php', $this->project_checker);

        $context = new Context();
        $context->vars_in_scope['$b'] = \Psalm\Type::getBool();
        $context->vars_in_scope['$foo'] = \Psalm\Type::getArray();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertFalse(isset($context->vars_in_scope['$foo[\'a\']']));
    }

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'genericArrayCreationWithInt' => [
                '<?php
                    $out = [];

                    foreach ([1, 2, 3, 4, 5] as $value) {
                        $out[] = 4;
                    }',
                'assertions' => [
                    '$out' => 'array<int, int>',
                ],
            ],
            'generic2dArrayCreation' => [
                '<?php
                    $out = [];

                    foreach ([1, 2, 3, 4, 5] as $value) {
                        $out[] = [4];
                    }',
                'assertions' => [
                    '$out' => 'array<int, array<int, int>>',
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

                    if ($bits) {
                        $out[] = $bits;
                    }',
                'assertions' => [
                    '$out' => 'array<int, array<int, int>>',
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
                    '$out' => 'array<int, int|string>',
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
                    '$out' => 'array<int, int|string>',
                ],
            ],
            'implicitIntArrayCreation' => [
                '<?php
                    $foo = [];
                    $foo[] = "hello";',
                'assertions' => [
                    '$foo' => 'array<int, string>',
                ],
            ],
            'implicit2dIntArrayCreation' => [
                '<?php
                    $foo = [];
                    $foo[][] = "hello";',
                'assertions' => [
                    '$foo' => 'array<int, array<int, string>>',
                ],
            ],
            'implicit3dIntArrayCreation' => [
                '<?php
                    $foo = [];
                    $foo[][][] = "hello";',
                'assertions' => [
                    '$foo' => 'array<int, array<int, array<int, string>>>',
                ],
            ],
            'implicit4dIntArrayCreation' => [
                '<?php
                    $foo = [];
                    $foo[][][][] = "hello";',
                'assertions' => [
                    '$foo' => 'array<int, array<int, array<int, array<int, string>>>>',
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
                    '$foo' => 'array<int, string>',
                    '$bar' => 'array<int, int>',
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
            'conflictingTypes' => [
                '<?php
                    $foo = [
                        "bar" => ["a" => "b"],
                        "baz" => [1]
                    ];',
                'assertions' => [
                    '$foo' => 'array{bar:array{a:string}, baz:array<int, int>}',
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
                    '$foo' => 'array{bar:array{a:string, bam:array{baz:string}}, baz:array<int, int>}',
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
                    '$a' => 'array<string, array<int, string>>',
                    '$c' => 'array<string, array<string, array<int, string>>>',
                ],
            ],
            'assignExplicitValueToGeneric' => [
                '<?php
                    /** @var array<string, array<string, string>> */
                    $a = [];
                    $a["foo"] = ["bar" => "baz"];',
                'assertions' => [
                    '$a' => 'array<string, array<string, string>>',
                ],
            ],
            'additionWithEmpty' => [
                '<?php
                    $a = [];
                    $a += ["bar"];

                    $b = [] + ["bar"];',
                'assertions' => [
                    '$a' => 'array<int, string>',
                    '$b' => 'array<int, string>',
                ],
            ],
            'additionDifferentType' => [
                '<?php
                    $a = ["bar"];
                    $a += [1];

                    $b = ["bar"] + [1];',
                'assertions' => [
                    '$a' => 'array<int, string|int>',
                    '$b' => 'array<int, string|int>',
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
                    '$foo' => 'array{a:int, b:array<int, int>}',
                ],
            ],
            'nestedObjectLikeArrayAddition' => [
                '<?php
                    $foo = [];
                    $foo["root"]["a"] = 1;
                    $foo["root"] += ["b" => [2, 3]];',
                'assertions' => [
                    '$foo' => 'array{root:array{a:int, b:array<int, int>}}',
                ],
            ],
            'updateStringIntKey' => [
                '<?php
                    $string = "c";
                    $int = 5;

                    $a = [];

                    $a["a"] = 5;
                    $a[0] = 3;

                    $b = [];

                    $b[$string] = 5;
                    $b[0] = 3;

                    $c = [];

                    $c[0] = 3;
                    $c[$string] = 5;

                    $d = [];

                    $d[$int] = 3;
                    $d["a"] = 5;

                    $e = [];

                    $e[$int] = 3;
                    $e[$string] = 5;',
                'assertions' => [
                    '$a' => 'array<int|string, int>',
                    '$b' => 'array<string|int, int>',
                    '$c' => 'array<int|string, int>',
                    '$d' => 'array<int|string, int>',
                    '$e' => 'array<int|string, int>',
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
                    '$a' => 'array<int, array<int|string, int>>',
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
                    '$b' => 'array<int, array<string|int, int>>',
                    '$c' => 'array<int, array<int|string, int>>',
                    '$d' => 'array<int, array<int|string, int>>',
                    '$e' => 'array<int, array<int|string, int>>',
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
                    '$a' => 'array{root:array<int|string, int>}',
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
                    '$b' => 'array{root:array<string|int, int>}',
                    '$c' => 'array{root:array<int|string, int>}',
                    '$d' => 'array{root:array<int|string, int>}',
                    '$e' => 'array{root:array<int|string, int>}',
                ],
            ],
            'mixedArrayAssignmentWithStringKeys' => [
                '<?php
                    /** @var array<mixed, mixed> */
                    $a = [];
                    $a["b"]["c"] = 5;
                    echo $a["b"]["d"];',
                'assertions' => [
                    '$a' => 'array<mixed, mixed>',
                ],
                'error_levels' => ['MixedArrayAssignment', 'MixedArrayAccess', 'MixedArgument'],
            ],
            'mixedArrayCoercion' => [
                '<?php
                    /** @param int[] $arg */
                    function expect_int_array($arg) : void { }
                    /** @return array */
                    function generic_array() { return []; }

                    expect_int_array(generic_array());

                    function expect_int(int $arg) : void {}
                    /** @return mixed */
                    function return_mixed() { return 2; }
                    expect_int(return_mixed());',
                'assertions' => [],
                'error_levels' => ['MixedTypeCoercion', 'MixedArgument'],
            ],
            'suppressMixedObjectOffset' => [
                '<?php
                    function getThings() : array {
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
                        public function offsetSet($offset, $value) : void {}

                        /** @param string|int $offset */
                        public function offsetExists($offset) : bool {
                            return true;
                        }

                        /** @param string|int $offset */
                        public function offsetUnset($offset) : void {}

                        /** @return mixed */
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
            'assignToNullDontDie' => [
                '<?php
                    $a = null;
                    $a[0][] = 1;',
                'assertions' => [
                    '$a' => 'array<int, array<int, int>>',
                ],
                'error_levels' => ['PossiblyNullArrayAssignment'],
            ],
        ];
    }

    /**
     * @return array
     */
    public function providerFileCheckerInvalidCodeParse()
    {
        return [
            'objectAssignment' => [
                '<?php
                    class A {}
                    (new A)["b"] = 1;',
                'error_message' => 'InvalidArrayAssignment',
            ],
            'invalidArrayAccess' => [
                '<?php
                    $a = 5;
                    $a[0] = 5;',
                'error_message' => 'InvalidArrayAssignment',
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
                    function fooFoo(array $foo) : void { }

                    function barBar(array $bar) : void {
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
                'error_message' => 'InvalidPropertyAssignment',
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
                'error_message' => 'InvalidPropertyAssignment',
            ],
        ];
    }
}
