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
                'assertions' => [],
                'error_level' => ['MixedArrayOffset'],
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
                        /** @param string $name */
                        public function __get($name) : ?string {
                            if ($name === "foo") {
                                return "hello";
                            }
                        }

                        /**
                         * @param string $name
                         * @param mixed $value
                         */
                        public function __set($name, $value) : void {
                        }
                    }

                    $a = new A();
                    $a->foo = "hello";
                    $a->bar = "hello"; // not a property',
            ],
            'propertySealedDocblockDefinedPropertyFetch' => [
                '<?php
                    /**
                     * @property string $foo
                     * @psalm-seal-properties
                     */
                    class A {
                         public function __get(string $name) : ?string {
                              if ($name === "foo") {
                                   return "hello";
                              }
                         }

                         /** @param mixed $value */
                         public function __set(string $name, $value) : void {
                         }
                    }

                    $a = new A();
                    echo $a->foo;',
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
            'mixedDocblockParamTypeDefinedInParent' => [
                '<?php
                    class A {
                        /** @param mixed $a */
                        public function foo($a) : void {}
                    }

                    class B extends A {
                        public function foo($a) : void {}
                    }',
            ],
            'intDocblockParamTypeDefinedInParent' => [
                '<?php
                    class A {
                        /** @param int $a */
                        public function foo($a) : void {}
                    }

                    class B extends A {
                        public function foo($a) : void {}
                    }',
            ],
            'varSelf' => [
                '<?php
                    class A
                    {
                        public function foo() : void {}

                        public function getMeAgain() : void {
                            /** @var self */
                            $me = $this;
                            $me->foo();
                        }
                    }',
            ],
            'psalmVar' => [
                '<?php
                    class A
                    {
                        /** @psalm-var array<int, string> */
                        public $foo = [];

                        public function updateFoo() : void {
                            $this->foo[5] = "hello";
                        }
                    }',
            ],
            'psalmParam' => [
                '<?php
                    function takesInt(int $a) : void {}

                    /**
                     * @psalm-param  array<int, string> $a
                     * @param string[] $a
                     */
                    function foo(array $a) : void {
                        foreach ($a as $key => $value) {
                            takesInt($key);
                        }
                    }',
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
                'error_message' => 'MissingDocblockType',
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
                'error_message' => 'InvalidDocblock - src/somefile.php:5 - Badly-formatted @param',
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
                         public function __get(string $name) : ?string {
                              if ($name === "foo") {
                                   return "hello";
                              }
                         }

                         /** @param mixed $value */
                         public function __set(string $name, $value) : void {
                         }
                    }

                    $a = new A();
                    $a->foo = 5;',
                'error_message' => 'InvalidPropertyAssignment',
            ],
            'propertyWriteDocblockInvalidAssignment' => [
                '<?php
                    /**
                     * @property-write string $foo
                     */
                    class A {
                         public function __get(string $name) : ?string {
                              if ($name === "foo") {
                                   return "hello";
                              }
                         }

                         /** @param mixed $value */
                         public function __set(string $name, $value) : void {
                         }
                    }

                    $a = new A();
                    $a->foo = 5;',
                'error_message' => 'InvalidPropertyAssignment',
            ],
            'propertySealedDocblockUndefinedPropertyAssignment' => [
                '<?php
                    /**
                     * @property string $foo
                     * @psalm-seal-properties
                     */
                    class A {
                         public function __get(string $name) : ?string {
                              if ($name === "foo") {
                                   return "hello";
                              }
                         }

                         /** @param mixed $value */
                         public function __set(string $name, $value) : void {
                         }
                    }

                    $a = new A();
                    $a->bar = 5;',
                'error_message' => 'UndefinedPropertyAssignment',
            ],
            'propertySealedDocblockDefinedPropertyAssignment' => [
                '<?php
                    /**
                     * @property string $foo
                     * @psalm-seal-properties
                     */
                    class A {
                         public function __get(string $name) : ?string {
                              if ($name === "foo") {
                                   return "hello";
                              }
                         }

                         /** @param mixed $value */
                         public function __set(string $name, $value) : void {
                         }
                    }

                    $a = new A();
                    $a->foo = 5;',
                'error_message' => 'InvalidPropertyAssignment',
            ],
            'propertyReadInvalidFetch' => [
                '<?php
                    /**
                     * @property-read string $foo
                     */
                    class A {
                         /** @return mixed */
                         public function __get(string $name) {
                              if ($name === "foo") {
                                   return "hello";
                              }
                         }
                    }

                    $a = new A();
                    echo count($a->foo);',
                'error_message' => 'InvalidArgument',
            ],
            'propertySealedDocblockUndefinedPropertyFetch' => [
                '<?php
                    /**
                     * @property string $foo
                     * @psalm-seal-properties
                     */
                    class A {
                         public function __get(string $name) : ?string {
                              if ($name === "foo") {
                                   return "hello";
                              }
                         }

                         /** @param mixed $value */
                         public function __set(string $name, $value) : void {
                         }
                    }

                    $a = new A();
                    echo $a->bar;',
                'error_message' => 'UndefinedPropertyFetch',
            ],
            'noStringParamType' => [
                '<?php
                    function fooFoo($a) : void {
                        echo substr($a, 4, 2);
                    }',
                'error_message' => 'UntypedParam - src/somefile.php:2 - Parameter $a has no provided type,'
                    . ' should be string',
                'error_levels' => ['MixedArgument'],
            ],
            'noParamTypeButConcat' => [
                '<?php
                    function fooFoo($a) : void {
                        echo $a . "foo";
                    }',
                'error_message' => 'UntypedParam - src/somefile.php:2 - Parameter $a has no provided type,'
                    . ' should be string',
                'error_levels' => ['MixedOperand'],
            ],
            'noParamTypeButAddition' => [
                '<?php
                    function fooFoo($a) : void {
                        echo $a + 5;
                    }',
                'error_message' => 'UntypedParam - src/somefile.php:2 - Parameter $a has no provided type,'
                    . ' should be int|float',
                'error_levels' => ['MixedOperand', 'MixedArgument'],
            ],
            'noParamTypeButDivision' => [
                '<?php
                    function fooFoo($a) : void {
                        echo $a / 5;
                    }',
                'error_message' => 'UntypedParam - src/somefile.php:2 - Parameter $a has no provided type,'
                    . ' should be int|float',
                'error_levels' => ['MixedOperand', 'MixedArgument'],
            ],
            'noParamTypeButTemplatedString' => [
                '<?php
                    function fooFoo($a) : void {
                        echo "$a";
                    }',
                'error_message' => 'UntypedParam - src/somefile.php:2 - Parameter $a has no provided type,'
                    . ' should be string',
                'error_levels' => ['MixedOperand'],
            ],
            'noStringIntParamType' => [
                '<?php
                    function fooFoo($a) : void {
                        if (is_string($a)) {
                            echo substr($a, 4, 2);
                        } else {
                            echo substr("hello", $a, 2);
                        }
                    }',
                'error_message' => 'UntypedParam - src/somefile.php:2 - Parameter $a has no provided type,'
                    . ' should be int|string',
                'error_levels' => ['MixedArgument'],
            ],
            'intParamTypeDefinedInParent' => [
                '<?php
                    class A {
                        public function foo(int $a) : void {}
                    }

                    class B extends A {
                        public function foo($a) : void {}
                    }',
                'error_message' => 'UntypedParam',
                'error_levels' => ['MethodSignatureMismatch'],
            ],
            'alreadyHasCheck' => [
                '<?php
                    function takesString(string $s) : void {}

                    function shouldTakeString($s) : void {
                      if (is_string($s)) takesString($s);
                    }',
                'error_message' => 'UntypedParam - src/somefile.php:4 - Parameter $s has no provided type,'
                    . ' could not infer',
                'error_levels' => ['MixedArgument'],
            ],
            'isSetBeforeInferrence' => [
                'input' => '<?php
                    function takesString(string $s) : void {}

                    /** @return mixed */
                    function returnsMixed() {}

                    function shouldTakeString($s) : void {
                      $s = returnsMixed();
                      takesString($s);
                    }',
                'error_message' => 'UntypedParam - src/somefile.php:7 - Parameter $s has no provided type,'
                    . ' could not infer',
                'error_levels' => ['MixedArgument', 'InvalidReturnType', 'MixedAssignment'],
            ],
            'psalmInvalidVar' => [
                '<?php
                    class A
                    {
                        /** @psalm-var array<int, string> */
                        public $foo = [];

                        public function updateFoo() : void {
                            $this->foo["boof"] = "hello";
                        }
                    }',
                'error_message' => 'InvalidPropertyAssignment',
            ],
            'incorrectDocblockOrder' => [
                '<?php
                    class MyClass {
                        /**
                         * Comment
                         * @var $fooPropTypo string
                         */
                        public $fooProp = "/tmp/file.txt";
                    }',
                'error_message' => 'MissingDocblockType',
            ],
            'badlyFormattedVar' => [
                '<?php
                    /**
                     * @return string[]
                     */
                    function returns_strings() {
                        /** @var array(string) $result */
                        $result = ["example"];
                        return $result;
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'badlyWrittenVar' => [
                '<?php
                    /** @param mixed $x */
                    function myvalue($x) : void {
                        /** @var $myVar MyNS\OtherClass */
                        $myVar = $x->conn()->method();
                        $myVar->otherMethod();
                    }',
                'error_message' => 'MissingDocblockType',
            ],
        ];
    }
}
