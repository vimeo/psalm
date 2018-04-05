<?php
namespace Psalm\Tests;

use Psalm\Config;
use Psalm\Context;

class AnnotationTest extends TestCase
{
    use Traits\FileCheckerInvalidCodeParseTestTrait;
    use Traits\FileCheckerValidCodeParseTestTrait;

    /**
     * @return void
     */
    public function testPhpStormGenericsWithValidArgument()
    {
        Config::getInstance()->allow_phpstorm_generics = true;

        $this->addFile(
            'somefile.php',
            '<?php
                function takesString(string $s): void {}

                /** @param ArrayIterator|string[] $i */
                function takesArrayIteratorOfString(ArrayIterator $i): void {
                    $s = $i->offsetGet("a");
                    takesString($s);

                    foreach ($i as $s2) {
                        takesString($s2);
                    }
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

     /**
     * @return void
     */
    public function testPhpStormGenericsWithClassProperty()
    {
        Config::getInstance()->allow_phpstorm_generics = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /** @psalm-suppress MissingConstructor */
                class Foo {
                  /** @var \stdClass[]|\ArrayObject */
                  public $bar;

                  /**
                   * @return \stdClass[]|\ArrayObject
                   */
                  public function getBar(): \ArrayObject
                  {
                    return $this->bar;
                  }
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return void
     */
    public function testPhpStormGenericsWithValidIterableArgument()
    {
        Config::getInstance()->allow_phpstorm_generics = true;

        $this->addFile(
            'somefile.php',
            '<?php
                function takesString(string $s): void {}

                /** @param iterable|string[] $i */
                function takesArrayIteratorOfString(iterable $i): void {
                    foreach ($i as $s2) {
                        takesString($s2);
                    }
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidScalarArgument
     *
     * @return                   void
     */
    public function testPhpStormGenericsInvalidArgument()
    {
        Config::getInstance()->allow_phpstorm_generics = true;

        $this->addFile(
            'somefile.php',
            '<?php
                function takesInt(int $s): void {}

                /** @param ArrayIterator|string[] $i */
                function takesArrayIteratorOfString(ArrayIterator $i): void {
                    $s = $i->offsetGet("a");
                    takesInt($s);
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage PossiblyInvalidMethodCall
     *
     * @return                   void
     */
    public function testPhpStormGenericsNoTypehint()
    {
        Config::getInstance()->allow_phpstorm_generics = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /** @param ArrayIterator|string[] $i */
                function takesArrayIteratorOfString($i): void {
                    $s = $i->offsetGet("a");
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidArgument
     *
     * @return                   void
     */
    public function testDontAllowStringConstCoercion()
    {
        Config::getInstance()->allow_coercion_from_string_to_class_const = false;

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @param class-string $s
                 */
                function takesClassConstants(string $s) : void {}

                class A {}

                takesClassConstants("A");'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidStringClass
     *
     * @return                   void
     */
    public function testDontAllowStringStandInForNewClass()
    {
        Config::getInstance()->allow_string_standin_for_class = false;

        $this->addFile(
            'somefile.php',
            '<?php
                class A {}

                $a = "A";

                new $a();'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidStringClass
     *
     * @return                   void
     */
    public function testDontAllowStringStandInForStaticMethodCall()
    {
        Config::getInstance()->allow_string_standin_for_class = false;

        $this->addFile(
            'somefile.php',
            '<?php
                class A {
                    public static function foo() : void {}
                }

                $a = "A";

                $a::foo();'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'nopType' => [
                '<?php
                    $a = "hello";

                    /** @var int $a */',
                'assertions' => [
                    '$a' => 'int',
                ],
            ],
            'deprecatedMethod' => [
                '<?php
                    class Foo {
                        /**
                         * @deprecated
                         */
                        public static function barBar(): void {
                        }
                    }',
            ],
            'validDocblockReturn' => [
                '<?php
                    /**
                     * @return string
                     */
                    function fooFoo(): string {
                        return "boop";
                    }

                    /**
                     * @return array<int, string>
                     */
                    function foo2(): array {
                        return ["hello"];
                    }

                    /**
                     * @return array<int, string>
                     */
                    function foo3(): array {
                        return ["hello"];
                    }',
            ],
            'reassertWithIs' => [
                '<?php
                    /** @param array $a */
                    function foo($a): void {
                        if (is_array($a)) {
                            // do something
                        }
                    }',
                'assertions' => [],
                'error_level' => ['RedundantConditionGivenDocblockType'],
            ],
            'checkArrayWithIs' => [
                '<?php
                    /** @param mixed $b */
                    function foo($b): void {
                        /** @var array */
                        $a = (array)$b;
                        if (is_array($a)) {
                            // do something
                        }
                    }',
                'assertions' => [],
                'error_level' => ['RedundantConditionGivenDocblockType'],
            ],
            'checkArrayWithIsInsideLoop' => [
                '<?php
                    /** @param array<mixed, array<mixed, mixed>> $data */
                    function foo($data): void {
                        foreach ($data as $key => $val) {
                            if (!\is_array($data)) {
                                $data = [$key => null];
                            } else {
                                $data[$key] = !empty($val);
                            }
                        }
                    }',
                'assertions' => [],
                'error_level' => ['LoopInvalidation', 'MixedArrayOffset', 'RedundantConditionGivenDocblockType'],
            ],
            'goodDocblock' => [
                '<?php
                    class A {
                        /**
                         * @param A $a
                         * @param bool $b
                         */
                        public function g(A $a, $b): void {
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
                        public function g(A $a, $b): void {
                        }
                    }',
            ],
            'propertyDocblock' => [
                '<?php
                    namespace Bar;

                    /**
                     * @property string $foo
                     */
                    class A {
                        /** @param string $name */
                        public function __get($name): ?string {
                            if ($name === "foo") {
                                return "hello";
                            }
                        }

                        /**
                         * @param string $name
                         * @param mixed $value
                         */
                        public function __set($name, $value): void {
                        }
                    }

                    $a = new A();
                    $a->foo = "hello";
                    $a->bar = "hello"; // not a property',
            ],
            'propertyOfTypeClassDocblock' => [
                '<?php
                    namespace Bar;

                    class PropertyType {}

                    /**
                     * @property PropertyType $foo
                     */
                    class A {
                        /** @param string $name */
                        public function __get($name): ?string {
                            if ($name === "foo") {
                                return "hello";
                            }
                        }

                        /**
                         * @param string $name
                         * @param mixed $value
                         */
                        public function __set($name, $value): void {
                        }
                    }

                    $a = new A();
                    $a->foo = new PropertyType();',
            ],
            'propertySealedDocblockDefinedPropertyFetch' => [
                '<?php
                    namespace Bar;
                    /**
                     * @property string $foo
                     * @psalm-seal-properties
                     */
                    class A {
                         public function __get(string $name): ?string {
                              if ($name === "foo") {
                                   return "hello";
                              }
                         }

                         /** @param mixed $value */
                         public function __set(string $name, $value): void {
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
                        public function foo(): void {}
                    }

                    /**
                     * @return ?A
                     * @psalm-ignore-nullable-return
                     */
                    function makeA() {
                        return rand(0, 1) ? new A(): null;
                    }

                    function takeA(A $a): void { }

                    $a = makeA();
                    $a->foo();
                    $a->bar = 7;
                    takeA($a);',
            ],
            'invalidDocblockParamSuppress' => [
                '<?php
                    /**
                     * @param int $bar
                     * @psalm-suppress MismatchingDocblockParamType
                     */
                    function fooFoo(array $bar): void {
                    }',
            ],
            'differentDocblockParamClassSuppress' => [
                '<?php
                    class A {}

                    /**
                     * @param B $bar
                     * @psalm-suppress MismatchingDocblockParamType
                     */
                    function fooFoo(A $bar): void {
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
                        public function foo($a): void {}
                    }

                    class B extends A {
                        public function foo($a): void {}
                    }',
            ],
            'intDocblockParamTypeDefinedInParent' => [
                '<?php
                    class A {
                        /** @param int $a */
                        public function foo($a): void {}
                    }

                    class B extends A {
                        public function foo($a): void {}
                    }',
            ],
            'varSelf' => [
                '<?php
                    class A
                    {
                        public function foo(): void {}

                        public function getMeAgain(): void {
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

                        public function updateFoo(): void {
                            $this->foo[5] = "hello";
                        }
                    }',
            ],
            'psalmParam' => [
                '<?php
                    function takesInt(int $a): void {}

                    /**
                     * @psalm-param  array<int, string> $a
                     * @param string[] $a
                     */
                    function foo(array $a): void {
                        foreach ($a as $key => $value) {
                            takesInt($key);
                        }
                    }',
            ],
            'returnDocblock' => [
                '<?php
                    function foo(int $i): int {
                        /** @var int */
                        return $i;
                    }',
            ],
            'doubleVar' => [
                '<?php
                    function foo() : array {
                        return ["hello" => new stdClass, "goodbye" => new stdClass];
                    }

                    $a = null;
                    $b = null;

                    /**
                     * @var string $key
                     * @var stdClass $value
                     */
                    foreach (foo() as $key => $value) {
                        $a = $key;
                        $b = $value;
                    }',
                'assertions' => [
                    '$a' => 'null|string',
                    '$b' => 'null|stdClass',
                ],
            ],
            /**
             * With a magic setter and no annotations specifying properties or types, we can
             * set anything we want on any variable name. The magic setter is trusted to figure
             * it out.
             */
            'magicSetterUndefinedPropertyNoAnnotation' => [
                '<?php
                    class A {
                        public function __get(string $name): ?string {
                            if ($name === "foo") {
                                return "hello";
                            }
                        }

                        /** @param mixed $value */
                        public function __set(string $name, $value): void {
                        }

                        public function goodSet(): void {
                            $this->__set("foo", new stdClass());
                        }
                    }',
            ],
            /**
             * With a magic getter and no annotations specifying properties or types, we can
             * get anything we want with any variable name. The magic getter is trusted to figure
             * it out.
             */
            'magicGetterUndefinedPropertyNoAnnotation' => [
                '<?php
                    class A {
                        public function __get(string $name): ?string {
                            if ($name === "foo") {
                                return "hello";
                            }
                        }

                        /** @param mixed $value */
                        public function __set(string $name, $value): void {
                        }

                        public function goodGet(): void {
                            echo $this->__get("foo");
                        }
                    }',
            ],
            /**
             * The property $foo is defined as a string with the `@property` annotation. We
             * use the magic setter to set it to a string, so everything is cool.
             */
            'magicSetterValidAssignmentType' => [
                '<?php
                    /**
                     * @property string $foo
                     */
                    class A {
                        public function __get(string $name): ?string {
                            if ($name === "foo") {
                                return "hello";
                            }
                        }

                        /** @param mixed $value */
                        public function __set(string $name, $value): void {
                        }

                        public function goodSet(): void {
                            $this->__set("foo", "value");
                        }
                    }',
            ],
            'propertyDocblockAssignmentToMixed' => [
                '<?php
                    /**
                     * @property string $foo
                     */
                    class A {
                         public function __get(string $name): ?string {
                              if ($name === "foo") {
                                   return "hello";
                              }
                         }

                         /** @param mixed $value */
                         public function __set(string $name, $value): void {
                         }
                    }

                    /** @param mixed $b */
                    function foo($b) : void {
                        $a = new A();
                        $a->__set("foo", $b);
                    }',
                'assertions' => [],
                'error_level' => ['MixedAssignment', 'MixedTypeCoercion'],
            ],
            'arrayOfClassConstants' => [
                '<?php
                    /**
                     * @param array<class-string> $arr
                     */
                    function takesClassConstants(array $arr) : void {}

                    class A {}
                    class B {}

                    takesClassConstants([A::class, B::class]);',
            ],
            'arrayOfStringClasses' => [
                '<?php
                    /**
                     * @param array<class-string> $arr
                     */
                    function takesClassConstants(array $arr) : void {}

                    class A {}
                    class B {}

                    takesClassConstants(["A", "B"]);',
                'annotations' => [],
                'error_levels' => ['TypeCoercion'],
            ],
            'singleClassConstant' => [
                '<?php
                    /**
                     * @param class-string $s
                     */
                    function takesClassConstants(string $s) : void {}

                    class A {}

                    takesClassConstants(A::class);',
            ],
            'singleClassConstantWithString' => [
                '<?php
                    /**
                     * @param class-string $s
                     */
                    function takesClassConstants(string $s) : void {}

                    class A {}

                    takesClassConstants("A");',
                'annotations' => [],
                'error_levels' => ['TypeCoercion'],
            ],
            'returnClassConstant' => [
                '<?php
                    class A {}

                    /**
                     * @return class-string
                     */
                    function takesClassConstants() : string {
                        return A::class;
                    }',
            ],
            'returnClassConstantAllowCoercion' => [
                '<?php
                    class A {}

                    /**
                     * @return class-string
                     */
                    function takesClassConstants() : string {
                        return "A";
                    }',
                'annotations' => [],
                'error_levels' => ['LessSpecificReturnStatement', 'MoreSpecificReturnType'],
            ],
            'returnClassConstantArray' => [
                '<?php
                    class A {}
                    class B {}

                    /**
                     * @return array<class-string>
                     */
                    function takesClassConstants() : array {
                        return [A::class, B::class];
                    }',
            ],
            'returnClassConstantArrayAllowCoercion' => [
                '<?php
                    class A {}
                    class B {}

                    /**
                     * @return array<class-string>
                     */
                    function takesClassConstants() : array {
                        return ["A", "B"];
                    }',
                'annotations' => [],
                'error_levels' => ['LessSpecificReturnStatement', 'MoreSpecificReturnType'],
            ],
            'allowOptionalParamsToBeEmptyArray' => [
                '<?php
                    /** @param array{b?: int, c?: string} $a */
                    function foo(array $a = []) : void {}',
            ],
            'allowEmptyVarAnnotation' => [
                '<?php
                    /**
                     * @param $x
                     */
                    function example(array $x) : void {}',
            ],
            'allowCapitalisedNamespacedString' => [
                '<?php
                    namespace Foo;

                    /**
                     * @param String $x
                     */
                    function example(string $x) : void {}',
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
            'invalidReturnBrackets' => [
                '<?php
                    interface I {
                        /**
                         * @return []
                         */
                        public static function barBar();
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'invalidPropertyClass' => [
                '<?php
                    class A {
                        /**
                         * @var 1
                         */
                        public $bar;
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'invalidPropertyBrackets' => [
                '<?php
                    class A {
                        /**
                         * @var []
                         */
                        public $bar;
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
                        public static function barBar(): void {
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
                        public static function barBar(): void {
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
            'deprecatedClassWithExtends' => [
                '<?php
                    /**
                     * @deprecated
                     */
                    class Foo { }

                    class Bar extends Foo {}',
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
                    function fooBar(): void {
                    }

                    fooBar("hello");',
                'error_message' => 'TooManyArguments - src/somefile.php:8 - Too many arguments for method fooBar '
                    . '- expecting 0 but saw 1',
            ],
            'missingParamVar' => [
                '<?php
                    /**
                     * @param string
                     */
                    function fooBar(): void {
                    }',
                'error_message' => 'InvalidDocblock - src/somefile.php:5 - Badly-formatted @param',
            ],
            'invalidDocblockReturn' => [
                '<?php
                    /**
                     * @return string
                     */
                    function fooFoo(): int {
                        return 5;
                    }',
                'error_message' => 'MismatchingDocblockReturnType',
            ],
            'propertyDocblockInvalidAssignment' => [
                '<?php
                    /**
                     * @property string $foo
                     */
                    class A {
                         public function __get(string $name): ?string {
                              if ($name === "foo") {
                                   return "hello";
                              }
                         }

                         /** @param mixed $value */
                         public function __set(string $name, $value): void {
                         }
                    }

                    $a = new A();
                    $a->foo = 5;',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'propertyInvalidClassAssignment' => [
                '<?php
                    namespace Bar;

                    class PropertyType {}
                    class SomeOtherPropertyType {}

                    /**
                     * @property PropertyType $foo
                     */
                    class A {
                        /** @param string $name */
                        public function __get($name): ?string {
                            if ($name === "foo") {
                                return "hello";
                            }
                        }

                        /**
                         * @param string $name
                         * @param mixed $value
                         */
                        public function __set($name, $value): void {
                        }
                    }

                    $a = new A();
                    $a->foo = new SomeOtherPropertyType();',
                'error_message' => 'InvalidPropertyAssignmentValue - src/somefile.php:27 - $a->foo with declared type'
                    . ' \'Bar\PropertyType\' cannot',
            ],
            'propertyWriteDocblockInvalidAssignment' => [
                '<?php
                    /**
                     * @property-write string $foo
                     */
                    class A {
                         public function __get(string $name): ?string {
                              if ($name === "foo") {
                                   return "hello";
                              }
                         }

                         /** @param mixed $value */
                         public function __set(string $name, $value): void {
                         }
                    }

                    $a = new A();
                    $a->foo = 5;',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'propertySealedDocblockUndefinedPropertyAssignment' => [
                '<?php
                    /**
                     * @property string $foo
                     * @psalm-seal-properties
                     */
                    class A {
                         public function __get(string $name): ?string {
                              if ($name === "foo") {
                                   return "hello";
                              }
                         }

                         /** @param mixed $value */
                         public function __set(string $name, $value): void {
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
                         public function __get(string $name): ?string {
                              if ($name === "foo") {
                                   return "hello";
                              }
                         }

                         /** @param mixed $value */
                         public function __set(string $name, $value): void {
                         }
                    }

                    $a = new A();
                    $a->foo = 5;',
                'error_message' => 'InvalidPropertyAssignmentValue',
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
                         public function __get(string $name): ?string {
                              if ($name === "foo") {
                                   return "hello";
                              }
                         }

                         /** @param mixed $value */
                         public function __set(string $name, $value): void {
                         }
                    }

                    $a = new A();
                    echo $a->bar;',
                'error_message' => 'UndefinedPropertyFetch',
            ],
            'noStringParamType' => [
                '<?php
                    function fooFoo($a): void {
                        echo substr($a, 4, 2);
                    }',
                'error_message' => 'MissingParamType - src/somefile.php:2 - Parameter $a has no provided type,'
                    . ' should be string',
                'error_levels' => ['MixedArgument'],
            ],
            'noParamTypeButConcat' => [
                '<?php
                    function fooFoo($a): void {
                        echo $a . "foo";
                    }',
                'error_message' => 'MissingParamType - src/somefile.php:2 - Parameter $a has no provided type,'
                    . ' should be string',
                'error_levels' => ['MixedOperand'],
            ],
            'noParamTypeButAddition' => [
                '<?php
                    function fooFoo($a): void {
                        echo $a + 5;
                    }',
                'error_message' => 'MissingParamType - src/somefile.php:2 - Parameter $a has no provided type,'
                    . ' should be int|float',
                'error_levels' => ['MixedOperand', 'MixedArgument'],
            ],
            'noParamTypeButDivision' => [
                '<?php
                    function fooFoo($a): void {
                        echo $a / 5;
                    }',
                'error_message' => 'MissingParamType - src/somefile.php:2 - Parameter $a has no provided type,'
                    . ' should be int|float',
                'error_levels' => ['MixedOperand', 'MixedArgument'],
            ],
            'noParamTypeButTemplatedString' => [
                '<?php
                    function fooFoo($a): void {
                        echo "$a";
                    }',
                'error_message' => 'MissingParamType - src/somefile.php:2 - Parameter $a has no provided type,'
                    . ' should be string',
                'error_levels' => ['MixedOperand'],
            ],
            'noStringIntParamType' => [
                '<?php
                    function fooFoo($a): void {
                        if (is_string($a)) {
                            echo substr($a, 4, 2);
                        } else {
                            echo substr("hello", $a, 2);
                        }
                    }',
                'error_message' => 'MissingParamType - src/somefile.php:2 - Parameter $a has no provided type,'
                    . ' should be int|string',
                'error_levels' => ['MixedArgument'],
            ],
            'intParamTypeDefinedInParent' => [
                '<?php
                    class A {
                        public function foo(int $a): void {}
                    }

                    class B extends A {
                        public function foo($a): void {}
                    }',
                'error_message' => 'MissingParamType',
                'error_levels' => ['MethodSignatureMismatch'],
            ],
            'alreadyHasCheck' => [
                '<?php
                    function takesString(string $s): void {}

                    function shouldTakeString($s): void {
                      if (is_string($s)) takesString($s);
                    }',
                'error_message' => 'MissingParamType - src/somefile.php:4 - Parameter $s has no provided type,'
                    . ' could not infer',
                'error_levels' => ['MixedArgument'],
            ],
            'isSetBeforeInferrence' => [
                'input' => '<?php
                    function takesString(string $s): void {}

                    /** @return mixed */
                    function returnsMixed() {}

                    function shouldTakeString($s): void {
                      $s = returnsMixed();
                      takesString($s);
                    }',
                'error_message' => 'MissingParamType - src/somefile.php:7 - Parameter $s has no provided type,'
                    . ' could not infer',
                'error_levels' => ['MixedArgument', 'InvalidReturnType', 'MixedAssignment'],
            ],
            'psalmInvalidVar' => [
                '<?php
                    class A
                    {
                        /** @psalm-var array<int, string> */
                        public $foo = [];

                        public function updateFoo(): void {
                            $this->foo["boof"] = "hello";
                        }
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue',
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
                    function myvalue($x): void {
                        /** @var $myVar MyNS\OtherClass */
                        $myVar = $x->conn()->method();
                        $myVar->otherMethod();
                    }',
                'error_message' => 'MissingDocblockType',
            ],
            'dontOverrideSameType' => [
                '<?php
                    class A {
                        /** @return ?int */
                        public function foo(): ?int {
                            if (rand(0, 1)) return 5;
                        }
                    }',
                'error_message' => 'InvalidReturnType',
            ],
            'alwaysCheckReturnType' => [
                '<?php
                    class A {}

                    /**
                     * @return A
                     * @psalm-suppress MismatchingDocblockReturnType
                     */
                    function foo(): B {
                      return new A;
                    }',
                'error_message' => 'UndefinedClass',
            ],
            'preventBadBoolean' => [
                '<?php
                    function foo(): boolean {
                        return true;
                    }',
                'error_message' => 'UndefinedClass',
            ],
            'preventBadObjectLikeFormat' => [
                '<?php
                    /**
                     * @param array{} $arr
                     */
                    function bar(array $arr): void {}',
                'error_message' => 'InvalidDocblock',
            ],
            'noPhpStormAnnotationsThankYou' => [
                '<?php
                    /** @param ArrayIterator|string[] $i */
                    function takesArrayIteratorOfString(ArrayIterator $i): void {}',
                'error_message' => 'MismatchingDocblockParamType',
            ],
            'noPhpStormAnnotationsPossiblyInvalid' => [
                '<?php
                    /** @param ArrayIterator|string[] $i */
                    function takesArrayIteratorOfString($i): void {
                        $s = $i->offsetGet("a");
                    }',
                'error_message' => 'PossiblyInvalidMethodCall',
            ],
            /**
             * The property $foo is not defined on the object, but accessed with the magic setter.
             * This is an error because `@psalm-seal-properties` is specified on the class block.
             */
            'magicSetterUndefinedProperty' => [
                '<?php
                    /**
                     * @psalm-seal-properties
                     */
                    class A {
                        public function __get(string $name): ?string {
                            if ($name === "foo") {
                                return "hello";
                            }
                        }

                        /** @param mixed $value */
                        public function __set(string $name, $value): void {
                        }

                        public function badSet(): void {
                            $this->__set("foo", "value");
                        }
                    }',
                'error_message' => 'UndefinedThisPropertyAssignment',
            ],
            /**
             * The property $foo is not defined on the object, but accessed with the magic getter.
             * This is an error because `@psalm-seal-properties` is specified on the class block.
             */
            'magicGetterUndefinedProperty' => [
                '<?php
                    /**
                     * @psalm-seal-properties
                     */
                    class A {
                        public function __get(string $name): ?string {
                            if ($name === "foo") {
                                return "hello";
                            }
                        }

                        /** @param mixed $value */
                        public function __set(string $name, $value): void {
                        }

                        public function badGet(): void {
                            $this->__get("foo");
                        }
                    }',
                'error_message' => 'UndefinedThisPropertyFetch',
            ],
            /**
             * The property $foo is defined as a string with the `@property` annotation, but
             * the magic setter is used to set it to an object.
             */
            'magicSetterInvalidAssignmentType' => [
                '<?php
                    /**
                     * @property string $foo
                     */
                    class A {
                        public function __get(string $name): ?string {
                            if ($name === "foo") {
                                return "hello";
                            }
                        }

                        /** @param mixed $value */
                        public function __set(string $name, $value): void {
                        }

                        public function badSet(): void {
                            $this->__set("foo", new stdClass());
                        }
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'propertyDocblockAssignmentToMixed' => [
                '<?php
                    /**
                     * @property string $foo
                     */
                    class A {
                         public function __get(string $name): ?string {
                              if ($name === "foo") {
                                   return "hello";
                              }
                         }

                         /** @param mixed $value */
                         public function __set(string $name, $value): void {
                         }
                    }

                    /** @param mixed $b */
                    function foo($b) : void {
                        $a = new A();
                        $a->__set("foo", $b);
                    }',
                'error_message' => 'MixedTypeCoercion',
                'error_levels' => ['MixedAssignment'],
            ],
            'arrayOfStringClasses' => [
                '<?php
                    /**
                     * @param array<class-string> $arr
                     */
                    function takesClassConstants(array $arr) : void {}

                    class A {}
                    class B {}

                    takesClassConstants(["A", "B"]);',
                'error_message' => 'TypeCoercion',
            ],
            'arrayOfNonExistentStringClasses' => [
                '<?php
                    /**
                     * @param array<class-string> $arr
                     */
                    function takesClassConstants(array $arr) : void {}
                    takesClassConstants(["A", "B"]);',
                'error_message' => 'UndefinedClass',
                'error_levels' => ['TypeCoercion'],
            ],
            'singleClassConstantWithInvalidDocblock' => [
                '<?php
                    /**
                     * @param clas-string $s
                     */
                    function takesClassConstants(string $s) : void {}',
                'error_message' => 'InvalidDocblock',
            ],
            'returnClassConstantDisallowCoercion' => [
                '<?php
                    class A {}

                    /**
                     * @return class-string
                     */
                    function takesClassConstants() : string {
                        return "A";
                    }',
                'error_message' => 'LessSpecificReturnStatement',
            ],
            'returnClassConstantArrayDisallowCoercion' => [
                '<?php
                    class A {}

                    /**
                     * @return array<class-string>
                     */
                    function takesClassConstants() : array {
                        return ["A", "B"];
                    }',
                'error_message' => 'LessSpecificReturnStatement',
            ],
            'returnClassConstantArrayAllowCoercionWithUndefinedClass' => [
                '<?php
                    class A {}

                    /**
                     * @return array<class-string>
                     */
                    function takesClassConstants() : array {
                        return ["A", "B"];
                    }',
                'error_message' => 'UndefinedClass',
                'error_levels' => ['LessSpecificReturnStatement', 'MoreSpecificReturnType'],
            ],
            'badStaticVar' => [
                '<?php
                    /** @var static */
                    $a = new stdClass();',
                'error_message' => 'InvalidDocblock',
            ],
            'doubleBar' => [
                '<?php
                    /** @param PDO||Closure|numeric $a */
                    function foo($a) : void {}',
                'error_message' => 'InvalidDocblock',
            ],
            'badStringVar' => [
                '<?php
                    /** @var string; */
                    $a = "hello";',
                'error_message' => 'InvalidDocblock',
            ],
        ];
    }
}
