<?php
namespace Psalm\Tests;

use Psalm\Checker\FileChecker;
use Psalm\Context;

class AnnotationTest extends TestCase
{
    use Traits\FileCheckerInvalidCodeParseTestTrait;
    use Traits\FileCheckerValidCodeParseTestTrait;

    /**
     * @return void
     */
    public function testNopType()
    {
        $this->addFile(
            'somefile.php',
            '<?php
                $a = "hello";

                /** @var int $a */
            '
        );

        $file_checker = new FileChecker('somefile.php', $this->project_checker);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertSame('int', (string) $context->vars_in_scope['$a']);
    }

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'deprecatedMethod' => [
                '<?php
                    class Foo {
                        /**
                         * @deprecated
                         */
                        public static function barBar() : void {
                        }
                    }',
            ],
            'validDocblockReturn' => [
                '<?php
                    /**
                     * @return string
                     */
                    function fooFoo() : string {
                        return "boop";
                    }

                    /**
                     * @return array<int, string>
                     */
                    function foo2() : array {
                        return ["hello"];
                    }

                    /**
                     * @return array<int, string>
                     */
                    function foo3() : array {
                        return ["hello"];
                    }',
            ],
            'reassertWithIs' => [
                '<?php
                    /** @param array $a */
                    function foo($a) : void {
                        if (is_array($a)) {
                            // do something
                        }
                    }',
            ],
            'checkArrayWithIs' => [
                '<?php
                    /** @param mixed $b */
                    function foo($b) : void {
                        /** @var array */
                        $a = (array)$b;
                        if (is_array($a)) {
                            // do something
                        }
                    }',
            ],
            'checkArrayWithIsInsideLoop' => [
                '<?php
                    /** @param array<mixed, array<mixed, mixed>> $data */
                    function foo($data) : void {
                        foreach ($data as $key => $val) {
                            if (!\is_array($data)) {
                                $data = [$key => null];
                            } else {
                                $data[$key] = !empty($val);
                            }
                        }
                    }',
            ],
            'goodDocblock' => [
                '<?php
                    class A {
                        /**
                         * @param A $a
                         * @param bool $b
                         */
                        public function g(A $a, $b) : void {
                        }
                    }',
            ],
            'goodDocblockInNamespace' => [
                '<?php
                    namespace Foo;

                    class A {
                        /**
                         * @param \Foo\A $a
                         * @param bool $b
                         */
                        public function g(A $a, $b) : void {
                        }
                    }',
            ],
            'propertyDocblock' => [
                '<?php
                    /**
                     * @property string $foo
                     */
                    class A {
                         public function __get($name) : ?string {
                              if ($name === "foo") {
                                   return "hello";
                              }
                         }

                         public function __set($name, $value) : void {
                         }
                    }

                    $a = new A();
                    $a->foo = "hello";',
            ],
            'ignoreNullableReturn' => [
                '<?php
                    class A {
                        /** @var int */
                        public $bar = 5;
                        public function foo() : void {}
                    }

                    /**
                     * @return ?A
                     * @psalm-ignore-nullable-return
                     */
                    function makeA() {
                        return rand(0, 1) ? new A() : null;
                    }

                    function takeA(A $a) : void { }

                    $a = makeA();
                    $a->foo();
                    $a->bar = 7;
                    takeA($a);',
            ],
            'invalidDocblockParamSuppress' => [
                '<?php
                    /**
                     * @param int $bar
                     * @psalm-suppress InvalidDocblock
                     */
                    function fooFoo(array $bar) : void {
                    }',
            ],
            'differentDocblockParamClassSuppress' => [
                '<?php
                    class A {}

                    /**
                     * @param B $bar
                     * @psalm-suppress InvalidDocblock
                     */
                    function fooFoo(A $bar) : void {
                    }',
            ],
            'varDocblock' => [
                '<?php
                    /** @var array<Exception> */
                    $a = [];

                    $a[0]->getMessage();',
            ],
        ];
    }

    /**
     * @return array
     */
    public function providerFileCheckerInvalidCodeParse()
    {
        return [
            'invalidReturn' => [
                '<?php
                    interface I {
                        /**
                         * @return $thus
                         */
                        public static function barBar();
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'invalidReturnClass' => [
                '<?php
                    interface I {
                        /**
                         * @return 1
                         */
                        public static function barBar();
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'invalidReturnClassWithComma' => [
                '<?php
                    interface I {
                        /**
                         * @return 1,
                         */
                        public static function barBar();
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'returnClassWithComma' => [
                '<?php
                    interface I {
                        /**
                         * @return a,
                         */
                        public static function barBar();
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'deprecatedMethodWithCall' => [
                '<?php
                    class Foo {
                        /**
                         * @deprecated
                         */
                        public static function barBar() : void {
                        }
                    }

                    Foo::barBar();',
                'error_message' => 'DeprecatedMethod',
            ],
            'deprecatedClassWithStaticCall' => [
                '<?php
                    /**
                     * @deprecated
                     */
                    class Foo {
                        public static function barBar() : void {
                        }
                    }

                    Foo::barBar();',
                'error_message' => 'DeprecatedClass',
            ],
            'deprecatedClassWithNew' => [
                '<?php
                    /**
                     * @deprecated
                     */
                    class Foo { }

                    $a = new Foo();',
                'error_message' => 'DeprecatedClass',
            ],
            'deprecatedPropertyGet' => [
                '<?php
                    class A{
                      /**
                       * @deprecated
                       * @var ?int
                       */
                      public $foo;
                    }
                    echo (new A)->foo;',
                'error_message' => 'DeprecatedProperty',
            ],
            'deprecatedPropertySet' => [
                '<?php
                    class A{
                      /**
                       * @deprecated
                       * @var ?int
                       */
                      public $foo;
                    }
                    $a = new A;
                    $a->foo = 5;',
                'error_message' => 'DeprecatedProperty',
            ],
            'missingParamType' => [
                '<?php
                    /**
                     * @param string $bar
                     */
                    function fooBar() : void {
                    }

                    fooBar("hello");',
                'error_message' => 'TooManyArguments',
            ],
            'missingParamVar' => [
                '<?php
                    /**
                     * @param string
                     */
                    function fooBar() : void {
                    }',
                'error_message' =>
                    'InvalidDocblock - src/somefile.php:5 - Badly-formatted @param in docblock for fooBar',
            ],
            'invalidDocblockReturn' => [
                '<?php
                    /**
                     * @return string
                     */
                    function fooFoo() : int {
                        return 5;
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'propertyDocblockInvalidAssignment' => [
                '<?php
                    /**
                     * @property string $foo
                     */
                    class A {
                         public function __get($name) : ?string {
                              if ($name === "foo") {
                                   return "hello";
                              }
                         }

                         public function __set($name, $value) : void {
                         }
                    }

                    $a = new A();
                    $a->foo = 5;',
                'error_message' => 'InvalidPropertyAssignment',
            ],
        ];
    }
}
