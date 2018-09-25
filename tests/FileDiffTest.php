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
    public function testCode(string $a, string $b, array $same_methods, array $same_signatures = [])
    {
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
                ['foo\a::$aB', 'foo\a::F', 'foo\a::foo', 'foo\a::bar']
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
                        $a = 1;
                    }
                    public function bar() {
                        $b = 2;
                    }
                }',
                ['foo\a::foo'],
                ['foo\a::bar']
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
                []
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
                []
            ],
            'addDocblock' => [
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
                ['foo\a::foo']
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
                ['foo\a::foo']
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
                ['foo\a::foo']
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
                ['foo\a::foo', 'foo\a::bar']
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
                ['foo\a::bar', 'foo\a::bat']
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
                ['foo\a::foo', 'foo\a::bat']
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
                ['foo\a::foo', 'foo\a::bar']
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
                ['foo\a::foo', 'foo\a::bar']
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
                ['foo\a::foo', 'foo\a::bar']
            ],
            'whiteSpaceOnly' => [
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
                ['foo\a::foo', 'foo\a::bar']
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
                ['foo\a::__construct', 'foo\a::bar', 'foo\b::bat']
            ],
        ];
    }
}
