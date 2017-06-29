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
        $file_checker = new FileChecker(
            'somefile.php',
            $this->project_checker,
            self::$parser->parse('<?php
                if ($b) {
                    $foo["a"] = "hello";
                }
            ')
        );

        $context = new Context();
        $context->vars_in_scope['$b'] = \Psalm\Type::getBool();
        $context->vars_in_scope['$foo'] = \Psalm\Type::getArray();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertFalse(isset($context->vars_in_scope['$foo[\'a\']']));
    }

    /**
     * @return void
     */
    public function testImplementsArrayAccess()
    {
        $stmts = self::$parser->parse('<?php
        class A implements \ArrayAccess {
            public function offsetSet($offset, $value) : void {
            }

            public function offsetExists($offset) : bool {
                return true;
            }

            public function offsetUnset($offset) : void {
            }

            public function offsetGet($offset) : int {
                return 1;
            }
        }

        $a = new A();
        $a["bar"] = "cool";
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertSame('A', (string) $context->vars_in_scope['$a']);
        $this->assertFalse(isset($context->vars_in_scope['$a[\'bar\']']));
    }

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'genericArrayCreation' => [
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
                    $e = $a[$d];',
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
                'error_message' => 'TypeCoercion',
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
