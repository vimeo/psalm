<?php
namespace Psalm\Tests;

class FileDiffTest extends TestCase
{
    /**
     * @dataProvider getChanges
     *
     * @param string $a
     * @param string $b
     * @param string[] $same_methods
     *
     * @return void
     */
    public function testCode(
        string $a,
        string $b,
        array $same_methods,
        array $same_signatures,
        array $changed_methods,
        array $diff_map_offsets
    ) {
        if (strpos($this->getTestName(), 'SKIPPED-') !== false) {
            $this->markTestSkipped();
        }

        $a_stmts = \Psalm\Provider\StatementsProvider::parseStatements($a);
        $b_stmts = \Psalm\Provider\StatementsProvider::parseStatements($b);

        $diff = \Psalm\Diff\FileStatementsDiffer::diff($a_stmts, $b_stmts, $a, $b);

        $this->assertSame(
            $same_methods,
            $diff[0]
        );

        $this->assertSame(
            $same_signatures,
            $diff[1]
        );

        $this->assertSame(
            $changed_methods,
            $diff[2]
        );

        $this->assertSame(count($diff_map_offsets), count($diff[3]));

        $found_offsets = array_map(
            /**
             * @param array{0: int, 1: int, 2: int, 3: int} $arr
             *
             * @return array{0: int, 1: int}
             */
            function (array $arr) {
                return [$arr[2], $arr[3]];
            },
            $diff[3]
        );

        $this->assertSame($diff_map_offsets, $found_offsets);
    }

    /**
     * @return array
     */
    public function getChanges()
    {
        return [
            'sameFile' => [
                '<?php
                namespace Foo;

                class A {
                    public $aB = 5;

                    const F = 1;

                    public function foo() {
                        $a = 1;
                    }
                    public function bar() {
                        $b = 1;
                    }
                }',
                '<?php
                namespace Foo;

                class A {
                    public $aB = 5;

                    const F = 1;

                    public function foo() {
                        $a = 1;
                    }
                    public function bar() {
                        $b = 1;
                    }
                }',
                ['foo\a::$aB', 'foo\a::F', 'foo\a::foo', 'foo\a::bar'],
                [],
                [],
                [[0, 0], [0, 0], [0, 0], [0, 0]]
            ],
            'lineChanges' => [
                '<?php
                namespace Foo;

                class A {
                    public $aB = 5;

                    const F = 1;

                    public function foo() {
                        $a = 1;
                    }
                    public function bar() {
                        $b = 1;
                    }
                }',
                '<?php
                namespace Foo;

                class A {

                    public $aB = 5;


                    const F = 1;

                    public function foo() {
                        $a = 1;
                    }



                    public function bar() {
                        $b = 1;
                    }
                }',
                ['foo\a::$aB', 'foo\a::F', 'foo\a::foo', 'foo\a::bar'],
                [],
                [],
                [[1, 1], [2, 2], [2, 2], [5, 5]]
            ],
            'simpleBodyChange' => [
                '<?php
                namespace Foo;

                class A {
                    public function foo() {
                        $a = 1;
                    }
                    public function bar() {
                        $b = 1;
                    }
                }',
                '<?php
                namespace Foo;

                class A {
                    public function foo() {
                        $a = 12;
                    }
                    public function bar() {
                        $b = 1;
                    }
                }',
                ['foo\a::bar'],
                ['foo\a::foo'],
                [],
                [[1, 0]]
            ],
            'simpleBodyChangeWithSignatureChange' => [
                '<?php
                namespace Foo;

                class A {
                    public function foo() {
                        $a = 1;
                    }
                    public function bar() {
                        $b = 1;
                    }
                }',
                '<?php
                namespace Foo;

                class A {
                    public function foo() {
                        $a = 1;
                    }
                    public function bar(string $a) {
                        $b = 1;
                    }
                }',
                ['foo\a::foo'],
                [],
                ['foo\a::bar'],
                [[0, 0]]
            ],
            'propertyChange' => [
                '<?php
                namespace Foo;

                class A {
                    public $a;
                }',
                '<?php
                namespace Foo;

                class A {
                    public $b;
                }',
                [],
                [],
                ['foo\a::$a'],
                []
            ],
            'propertyDefaultChange' => [
                '<?php
                namespace Foo;

                class A {
                    public $a = 1;
                }',
                '<?php
                namespace Foo;

                class A {
                    public $a = 2;
                }',
                [],
                ['foo\a::$a'],
                [],
                []
            ],
            'propertyDefaultAddition' => [
                '<?php
                namespace Foo;

                class A {
                    public $a;
                }',
                '<?php
                namespace Foo;

                class A {
                    public $a = 2;
                }',
                [],
                ['foo\a::$a'],
                [],
                []
            ],
            'propertySignatureChange' => [
                '<?php
                namespace Foo;

                class A {
                    /** @var ?string */
                    public $a;
                }',
                '<?php
                namespace Foo;

                class A {
                    /** @var ?int */
                    public $a;
                }',
                [],
                [],
                ['foo\a::$a'],
                []
            ],
            'propertyStaticChange' => [
                '<?php
                namespace Foo;

                class A {
                    /** @var ?string */
                    public static $a;
                }',
                '<?php
                namespace Foo;

                class A {
                    /** @var ?string */
                    public $a;
                }',
                [],
                [],
                ['foo\a::$a'],
                []
            ],
            'propertyVisibilityChange' => [
                '<?php
                namespace Foo;

                class A {
                    /** @var ?string */
                    public $a;
                }',
                '<?php
                namespace Foo;

                class A {
                    /** @var ?string */
                    private $a;
                }',
                [],
                [],
                ['foo\a::$a'],
                []
            ],
            'addDocblockToFirst' => [
                '<?php
                namespace Foo;

                class A {
                    public function foo() {
                        $a = 1;
                    }
                    public function bar() {
                        $b = 2;
                    }
                }',
                '<?php
                namespace Foo;

                class A {
                    /**
                     * @return void
                     */
                    public function foo() {
                        $a = 1;
                    }
                    public function bar() {
                        $b = 2;
                    }
                }',
                ['foo\a::bar'],
                [],
                ['foo\a::foo'],
                [[84, 3]]
            ],
            'addDocblockToSecond' => [
                '<?php
                namespace Foo;

                class A {
                    public function foo() {
                        $a = 1;
                    }
                    public function bar() {
                        $b = 2;
                    }
                }',
                '<?php
                namespace Foo;

                class A {
                    public function foo() {
                        $a = 1;
                    }
                    /**
                     * @return void
                     */
                    public function bar() {
                        $b = 2;
                    }
                }',
                ['foo\a::foo'],
                [],
                ['foo\a::bar'],
                [[0, 0]]
            ],
            'removeDocblock' => [
                '<?php
                namespace Foo;

                class A {
                    public function foo() {
                        $a = 1;
                    }
                    /**
                     * @return void
                     */
                    public function bar() {
                        $b = 2;
                    }
                }',
                '<?php
                namespace Foo;

                class A {
                    public function foo() {
                        $a = 1;
                    }
                    public function bar() {
                        $b = 2;
                    }
                }',
                ['foo\a::foo'],
                [],
                ['foo\a::bar'],
                [[0, 0]]
            ],
            'changeDocblock' => [
                '<?php
                namespace Foo;

                class A {
                    public function foo() {
                        $a = 1;
                    }
                    /**
                     * @return string
                     */
                    public function bar() {
                        $b = 2;
                    }
                }',
                '<?php
                namespace Foo;

                class A {
                    public function foo() {
                        $a = 1;
                    }
                    /**
                     * @return void
                     */
                    public function bar() {
                        $b = 2;
                    }
                }',
                ['foo\a::foo'],
                [],
                ['foo\a::bar'],
                [[0, 0]]
            ],
            'changeMethodVisibility' => [
                '<?php
                namespace Foo;

                class A {
                    public function foo() {
                        $a = 1;
                    }
                    public function bar() {
                        $b = 2;
                    }
                }',
                '<?php
                namespace Foo;

                class A {
                    public function foo() {
                        $a = 1;
                    }
                    private function bar() {
                        $b = 2;
                    }
                }',
                ['foo\a::foo'],
                [],
                ['foo\a::bar'],
                [[0, 0]]
            ],
            'removeFunctionAtEnd' => [
                '<?php
                namespace Foo;

                class A {
                    public function foo() {
                        $a = 1;
                    }
                    public function bar() {
                        $b = 1;
                    }
                    public function bat() {
                        $c = 1;
                    }
                }',
                '<?php
                namespace Foo;

                class A {
                    public function foo() {
                        $a = 1;
                    }
                    public function bar() {
                        $b = 1;
                    }
                }',
                ['foo\a::foo', 'foo\a::bar'],
                [],
                ['foo\a::bat'],
                [[0, 0], [0, 0]]
            ],
            'removeFunctionAtBeginning' => [
                '<?php
                namespace Foo;

                class A {
                    public function foo() {
                        $a = 1;
                    }
                    public function bar() {
                        $b = 1;
                    }
                    public function bat() {
                        $c = 1;
                    }
                }',
                '<?php
                namespace Foo;

                class A {
                    public function bar() {
                        $b = 1;
                    }
                    public function bat() {
                        $c = 1;
                    }
                }',
                ['foo\a::bar', 'foo\a::bat'],
                [],
                ['foo\a::foo'],
                [[-98, -3], [-98, -3]]
            ],
            'removeFunctionInMiddle' => [
                '<?php
                namespace Foo;

                class A {
                    public function foo() {
                        $a = 1;
                    }
                    public function bar() {
                        $b = 1;
                    }
                    public function bat() {
                        $c = 1;
                    }
                }',
                '<?php
                namespace Foo;

                class A {
                    public function foo() {
                        $a = 1;
                    }
                    public function bat() {
                        $c = 1;
                    }
                }',
                ['foo\a::foo', 'foo\a::bat'],
                [],
                ['foo\a::bar'],
                [[0, 0], [-98, -3]],
            ],
            'changeNamespace' => [
                '<?php
                namespace Bar;

                class A {
                    public function foo() {
                        $a = 1;
                    }
                    public function bar() {
                        $b = 1;
                    }
                }',
                '<?php
                namespace Foo;

                class A {
                    public function bar() {
                        $b = 2;
                    }
                }',
                [],
                [],
                [],
                []
            ],
            'removeNamespace' => [
                '<?php
                namespace Bar;

                class A {
                    public function foo() {
                        $a = 1;
                    }
                    public function bar() {
                        $b = 1;
                    }
                }',
                '<?php
                class A {
                    public function bar() {
                        $b = 2;
                    }
                }',
                [],
                [],
                [],
                []
            ],
            'newFunctionAtEnd' => [
                '<?php
                namespace Foo;

                class A {
                    public function foo() {
                        $a = 1;
                    }
                    public function bar() {
                        $b = 1;
                    }
                }',
                '<?php
                namespace Foo;

                class A {
                    public function foo() {
                        $a = 1;
                    }
                    public function bar() {
                        $b = 1;
                    }
                    public function bat() {
                        $c = 1;
                    }
                }',
                ['foo\a::foo', 'foo\a::bar'],
                [],
                [],
                [[0, 0], [0, 0]]
            ],
            'newFunctionAtBeginning' => [
                '<?php
                namespace Foo;

                class A {
                    public function foo() {
                        $a = 1;
                    }
                    public function bar() {
                        $b = 1;
                    }
                }',
                '<?php
                namespace Foo;

                class A {
                    public function bat() {
                        $c = 1;
                    }
                    public function foo() {
                        $a = 1;
                    }
                    public function bar() {
                        $b = 1;
                    }
                }',
                ['foo\a::foo', 'foo\a::bar'],
                [],
                [],
                [[98, 3], [98, 3]]
            ],
            'newFunctionInMiddle' => [
                '<?php
                namespace Foo;

                class A {
                    public function foo() {
                        $a = 1;
                    }
                    public function bar() {
                        $b = 1;
                    }
                }',
                '<?php
                namespace Foo;

                class A {
                    public function foo() {
                        $a = 1;
                    }
                    public function bat() {
                        $c = 1;
                    }
                    public function bar() {
                        $b = 1;
                    }
                }',
                ['foo\a::foo', 'foo\a::bar'],
                [],
                [],
                [[0, 0], [98, 3]]
            ],
            'SKIPPED-whiteSpaceOnly' => [
                '<?php
                namespace Foo;

                class A {
                    public function foo() {
                        $a = 1;
                    }
                    public function bar() {
                        $b = 1;
                    }
                }',
                '<?php
                    namespace Foo;
                 class A {
                    public function foo() {

                            $a  = 1  ;
                    }

                    public function bar() {
                          $b  =   1;

                    }
                }',
                ['foo\a::foo', 'foo\a::bar'],
                [],
                [],
                []
            ],
            'changeDeclaredMethodId' => [
                '<?php
                    namespace Foo;

                    class A {
                        public function __construct() {}
                        public static function bar() : void {}
                    }

                    class B extends A {
                        public static function bat() : void {}
                    }

                    class C extends B { }',
                '<?php
                    namespace Foo;

                    class A {
                        public function __construct() {}
                        public static function bar() : void {}
                    }

                    class B extends A {
                        public function __construct() {}
                        public static function bar() : void {}
                        public static function bat() : void {}
                    }

                    class C extends B { }',
                ['foo\a::__construct', 'foo\a::bar', 'foo\b::bat'],
                [],
                [],
                [[0, 0], [0, 0], [120, 2]]
            ],
            'sameTrait' => [
                '<?php
                namespace Foo;

                trait T {
                    public $aB = 5;

                    const F = 1;

                    public function foo() {
                        $a = 1;
                    }
                    public function bar() {
                        $b = 1;
                    }
                }',
                '<?php
                namespace Foo;

                trait T {
                    public $aB = 5;

                    const F = 1;

                    public function foo() {
                        $a = 1;
                    }
                    public function bar() {
                        $b = 1;
                    }
                }',
                ['foo\t::$aB', 'foo\t::F', 'foo\t::foo', 'foo\t::bar'],
                [],
                [],
                [[0, 0], [0, 0], [0, 0], [0, 0]]
            ],
            'traitPropertyChange' => [
                '<?php
                namespace Foo;

                trait T {
                    public $a;
                }',
                '<?php
                namespace Foo;

                trait T {
                    public $b;
                }',
                [],
                [],
                ['foo\t::$a'],
                []
            ],
        ];
    }
}
