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
        $this->assertEquals('A', (string) $context->vars_in_scope['$a']);
        $this->assertFalse(isset($context->vars_in_scope['$a[\'bar\']']));
    }

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'generic-array-creation' => [
                '<?php
                    $out = [];
            
                    foreach ([1, 2, 3, 4, 5] as $value) {
                        $out[] = 4;
                    }',
                'assertions' => [
                    ['array<int, int>' => '$out']
                ]
            ],
            'generic-2d-array-creation' => [
                '<?php
                    $out = [];
            
                    foreach ([1, 2, 3, 4, 5] as $value) {
                        $out[] = [4];
                    }',
                'assertions' => [
                    ['array<int, array<int, int>>' => '$out']
                ]
            ],
            'generic-2d-array-creation-added-in-if' => [
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
                    ['array<int, array<int, int>>' => '$out']
                ]
            ],
            'generic-array-creation-with-object-added-in-if' => [
                '<?php
                    class B {}
            
                    $out = [];
            
                    if (rand(0,10) === 10) {
                        $out[] = new B();
                    }',
                'assertions' => [
                    ['array<int, B>' => '$out']
                ]
            ],
            'generic-array-creation-with-element-added-in-switch' => [
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
                    ['array<int, int>' => '$out']
                ]
            ],
            'generic-array-creation-with-elements-added-in-switch' => [
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
                    ['array<int, int|string>' => '$out']
                ]
            ],
            'generic-array-creation-with-elements-added-in-switch-with-nothing' => [
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
                    ['array<int, int|string>' => '$out']
                ]
            ],
            'implicit-int-array-creation' => [
                '<?php
                    $foo = [];
                    $foo[] = "hello";',
                'assertions' => [
                    ['array<int, string>' => '$foo']
                ]
            ],
            'implicit-2d-int-array-creation' => [
                '<?php
                    $foo = [];
                    $foo[][] = "hello";',
                'assertions' => [
                    ['array<int, array<int, string>>' => '$foo']
                ]
            ],
            'implicit-3d-int-array-creation' => [
                '<?php
                    $foo = [];
                    $foo[][][] = "hello";',
                'assertions' => [
                    ['array<int, array<int, array<int, string>>>' => '$foo']
                ]
            ],
            'implicit-4d-int-array-creation' => [
                '<?php
                    $foo = [];
                    $foo[][][][] = "hello";',
                'assertions' => [
                    ['array<int, array<int, array<int, array<int, string>>>>' => '$foo']
                ]
            ],
            'implicit-indexed-int-array-creation' => [
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
                    ['array<int, string>' => '$foo'],
                    ['array<int, int>' => '$bar'],
                    ['array<string, int>' => '$bat']
                ]
            ],
            'implicit-string-array-creation' => [
                '<?php
                    $foo = [];
                    $foo["bar"] = "hello";',
                'assertions' => [
                    ['array{bar:string}' => '$foo'],
                    ['string' => '$foo[\'bar\']']
                ]
            ],
            'implicit-2d-string-array-creation' => [
                '<?php
                    $foo = [];
                    $foo["bar"]["baz"] = "hello";',
                'assertions' => [
                    ['array{bar:array{baz:string}}' => '$foo'],
                    ['string' => '$foo[\'bar\'][\'baz\']']
                ]
            ],
            'implicit-3d-string-array-creation' => [
                '<?php
                    $foo = [];
                    $foo["bar"]["baz"]["bat"] = "hello";',
                'assertions' => [
                    ['array{bar:array{baz:array{bat:string}}}' => '$foo'],
                    ['string' => '$foo[\'bar\'][\'baz\'][\'bat\']']
                ]
            ],
            'implicit-4d-string-array-creation' => [
                '<?php
                    $foo = [];
                    $foo["bar"]["baz"]["bat"]["bap"] = "hello";',
                'assertions' => [
                    ['array{bar:array{baz:array{bat:array{bap:string}}}}' => '$foo'],
                    ['string' => '$foo[\'bar\'][\'baz\'][\'bat\'][\'bap\']']
                ]
            ],
            '2-step-2d-string-array-creation' => [
                '<?php
                    $foo = ["bar" => []];
                    $foo["bar"]["baz"] = "hello";',
                'assertions' => [
                    ['array{bar:array{baz:string}}' => '$foo'],
                    ['string' => '$foo[\'bar\'][\'baz\']']
                ]
            ],
            '2-step-implicit-3d-string-array-creation' => [
                '<?php
                    $foo = ["bar" => []];
                    $foo["bar"]["baz"]["bat"] = "hello";',
                'assertions' => [
                    ['array{bar:array{baz:array{bat:string}}}' => '$foo']
                ]
            ],
            'conflicting-types' => [
                '<?php
                    $foo = [
                        "bar" => ["a" => "b"],
                        "baz" => [1]
                    ];',
                'assertions' => [
                    ['array{bar:array{a:string}, baz:array<int, int>}' => '$foo']
                ]
            ],
            'implicit-object-like-creation' => [
                '<?php
                    $foo = [
                        "bar" => 1,
                    ];
                    $foo["baz"] = "a";',
                'assertions' => [
                    ['array{bar:int, baz:string}' => '$foo']
                ]
            ],
            'conflicting-types-with-assignment' => [
                '<?php
                    $foo = [
                        "bar" => ["a" => "b"],
                        "baz" => [1]
                    ];
                    $foo["bar"]["bam"]["baz"] = "hello";',
                'assertions' => [
                    ['array{bar:array{a:string, bam:array{baz:string}}, baz:array<int, int>}' => '$foo']
                ]
            ],
            'conflicting-types-with-assignment-2' => [
                '<?php
                    $foo = [];
                    $foo["a"] = "hello";
                    $foo["b"][] = "goodbye";
                    $bar = $foo["a"];',
                'assertions' => [
                    ['array{a:string, b:array<int, string>}' => '$foo'],
                    ['string' => '$foo[\'a\']'],
                    ['array<int, string>' => '$foo[\'b\']'],
                    ['string' => '$bar']
                ]
            ],
            'conflicting-types-with-assignment-3' => [
                '<?php
                    $foo = [];
                    $foo["a"] = "hello";
                    $foo["b"]["c"]["d"] = "goodbye";',
                'assertions' => [
                    ['array{a:string, b:array{c:array{d:string}}}' => '$foo']
                ]
            ],
            'nested-object-like-assignment' => [
                '<?php
                    $foo = [];
                    $foo["a"]["b"] = "hello";
                    $foo["a"]["c"] = 1;',
                'assertions' => [
                    ['array{a:array{b:string, c:int}}' => '$foo']
                ]
            ],
            'conditional-object-like-assignment' => [
                '<?php
                    $foo = ["a" => "hello"];
                    if (rand(0, 10) === 5) {
                        $foo["b"] = 1;
                    }
                    else {
                        $foo["b"] = 2;
                    }',
                'assertions' => [
                    ['array{a:string, b:int}' => '$foo']
                ]
            ],
            'array-key' => [
                '<?php
                    $a = ["foo", "bar"];
                    $b = $a[0];
        
                    $c = ["a" => "foo", "b"=> "bar"];
                    $d = "a";
                    $e = $a[$d];',
                'assertions' => [
                    ['string' => '$b'],
                    ['string' => '$e']
                ]
            ],
            'conditional-check' => [
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
                'assertions' => []
            ],
            'variable-key-array-create' => [
                '<?php
                    $a = [];
                    $b = "boop";
                    $a[$b][] = "bam";
            
                    $c = [];
                    $c[$b][$b][] = "bam";',
                'assertions' => [
                    ['array<string, array<int, string>>' => '$a'],
                    ['array<string, array<string, array<int, string>>>' => '$c']
                ]
            ],
            'assign-explicit-value-to-generic' => [
                '<?php
                    /** @var array<string, array<string, string>> */
                    $a = [];
                    $a["foo"] = ["bar" => "baz"];',
                'assertions' => [
                    ['array<string, array<string, string>>' => '$a']
                ]
            ],
            'addition-with-empty' => [
                '<?php
                    $a = [];
                    $a += ["bar"];
            
                    $b = [] + ["bar"];',
                'assertions' => [
                    ['array<int, string>' => '$a'],
                    ['array<int, string>' => '$b']
                ]
            ],
            'addition-different-type' => [
                '<?php
                    $a = ["bar"];
                    $a += [1];
            
                    $b = ["bar"] + [1];',
                'assertions' => [
                    ['array<int, string|int>' => '$a'],
                    ['array<int, string|int>' => '$b']
                ]
            ],
            'present-1d-array-type-with-var-keys' => [
                '<?php
                    /** @var array<string, array<int, string>> */
                    $a = [];
            
                    $foo = "foo";
            
                    $a[$foo][] = "bat";',
                'assertions' => []
            ],
            'present-2d-array-type-with-var-keys' => [
                '<?php
                    /** @var array<string, array<string, array<int, string>>> */
                    $b = [];
            
                    $foo = "foo";
                    $bar = "bar";
            
                    $b[$foo][$bar][] = "bat";',
                'assertions' => []
            ],
            'object-like-with-integer-keys' => [
                '<?php
                    /** @var array{0: string, 1: int} **/
                    $a = ["hello", 5];
                    $b = $a[0]; // string
                    $c = $a[1]; // int
                    list($d, $e) = $a; // $d is string, $e is int',
                'assertions' => [
                    ['string' => '$b'],
                    ['int' => '$c'],
                    ['string' => '$d'],
                    ['int' => '$e']
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    public function providerFileCheckerInvalidCodeParse()
    {
        return [
            'object-assignment' => [
                '<?php
                    class A {}
                    (new A)["b"] = 1;',
                'error_message' => 'InvalidArrayAssignment'
            ],
            'invalid-array-access' => [
                '<?php
                    $a = 5;
                    $a[0] = 5;',
                'error_message' => 'InvalidArrayAssignment'
            ],
            'mixed-string-offset-assignment' => [
                '<?php
                    /** @var mixed */
                    $a = 5;
                    "hello"[0] = $a;',
                'error_message' => 'MixedStringOffsetAssignment',
                'error_level' => ['MixedAssignment']
            ],
            'mixed-array-argument' => [
                '<?php
                    /** @param array<mixed, int|string> $foo */
                    function fooFoo(array $foo) : void { }
            
                    function barBar(array $bar) : void {
                        fooFoo($bar);
                    }
            
                    barBar([1, "2"]);',
                'error_message' => 'TypeCoercion',
                'error_level' => ['MixedAssignment']
            ],
            'array-property-assignment' => [
                '<?php
                    class A {
                        /** @var string[] */
                        public $strs = ["a", "b", "c"];
            
                        /** @return void */
                        public function bar() {
                            $this->strs = [new stdClass()]; // no issue emitted
                        }
                    }',
                'error_message' => 'InvalidPropertyAssignment'
            ],
            'incremental-array-property-assignment' => [
                '<?php
                    class A {
                        /** @var string[] */
                        public $strs = ["a", "b", "c"];
            
                        /** @return void */
                        public function bar() {
                            $this->strs[] = new stdClass(); // no issue emitted
                        }
                    }',
                'error_message' => 'InvalidPropertyAssignment'
            ]
        ];
    }
}
