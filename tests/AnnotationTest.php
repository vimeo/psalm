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
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidParamDefault
     *
     * @return                   void
     */
    public function testInvalidParamDefault()
    {
        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @param array $arr
                 * @return void
                 */
                function foo($arr = false) {}'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return void
     */
    public function testInvalidParamDefaultButAllowedInConfig()
    {
        Config::getInstance()->add_param_default_to_docblock_type = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @param array $arr
                 * @return void
                 */
                function foo($arr = false) {}
                foo(false);
                foo(["hello"]);'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidParamDefault
     *
     * @return                   void
     */
    public function testInvalidTypehintParamDefaultButAllowedInConfig()
    {
        Config::getInstance()->add_param_default_to_docblock_type = true;

        $this->addFile(
            'somefile.php',
            '<?php
                function foo(array $arr = false) : void {}'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return void
     */
    public function testPhpDocMethodWhenUndefined()
    {
        Config::getInstance()->use_phpdoc_methods_without_call = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @method string getString()
                 * @method  void setInteger(int $integer)
                 * @method setString(int $integer)
                 * @method  getBool(string $foo) : bool
                 * @method (string|int)[] getArray() : array
                 * @method (callable() : string) getCallable() : callable
                 */
                class Child {}

                $child = new Child();

                $a = $child->getString();
                $child->setInteger(4);
                /** @psalm-suppress MixedAssignment */
                $b = $child->setString(5);
                $c = $child->getBool("hello");
                $d = $child->getArray();
                $e = $child->getCallable();'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return void
     */
    public function testCannotOverrideParentRetunTypeWhenIgnoringPhpDocMethod()
    {
        Config::getInstance()->use_phpdoc_methods_without_call = false;

        $this->addFile(
            'somefile.php',
            '<?php
                class Parent {
                    public static function getMe() : self {
                        return new self();
                    }
                }

                /**
                 * @method getMe() : Child
                 */
                class Child extends Parent {}

                $child = Child::getMe();'
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);

        $this->assertSame('Parent', (string) $context->vars_in_scope['$child']);
    }

    /**
     * @return void
     */
    public function testOverrideParentRetunType()
    {
        Config::getInstance()->use_phpdoc_methods_without_call = true;

        $this->addFile(
            'somefile.php',
            '<?php
                class Parent {
                    public static function getMe() : self {
                        return new self();
                    }
                }

                /**
                 * @method getMe() : Child
                 */
                class Child extends Parent {}

                $child = Child::getMe();'
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);

        $this->assertSame('Child', (string) $context->vars_in_scope['$child']);
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
                    class B {}

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
            'singleClassConstantAsConstant' => [
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
            'ifClassStringEquals' => [
                '<?php
                    class A {}
                    class B {}

                    /** @param class-string $class */
                    function foo(string $class) : void {
                        if ($class === A::class) {}
                        if ($class === A::class || $class === B::class) {}
                    }',
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
            'megaClosureAnnotationWithoutSpacing' => [
                '<?php
                    /** @var array{a:Closure():(array<mixed, mixed>|null), b?:Closure():array<mixed, mixed>, c?:Closure():array<mixed, mixed>, d?:Closure():array<mixed, mixed>, e?:Closure():(array{f:null|string, g:null|string, h:null|string, i:string, j:mixed, k:mixed, l:mixed, m:mixed, n:bool, o?:array{0:string}}|null), p?:Closure():(array{f:null|string, g:null|string, h:null|string, q:string, i:string, j:mixed, k:mixed, l:mixed, m:mixed, n:bool, o?:array{0:string}}|null), r?:Closure():(array<mixed, mixed>|null), s:array<mixed, mixed>} */
                    $arr = [];

                    $arr["a"]()',
            ],
            'megaClosureAnnotationWithSpacing' => [
                '<?php
                    /** @var array{
                      a: Closure() : (array<mixed, mixed>|null),
                      b?: Closure() : array<mixed, mixed>,
                      c?: Closure() : array<mixed, mixed>,
                      d?: Closure() : array<mixed, mixed>,
                      e?: Closure() : (array{
                        f: null|string,
                        g: null|string,
                        h: null|string,
                        i: string,
                        j: mixed,
                        k: mixed,
                        l: mixed,
                        m: mixed,
                        n: bool,
                        o?: array{0:string}
                      }|null),
                      p?: Closure() : (array{
                        f: null|string,
                        g: null|string,
                        h: null|string,
                        q: string,
                        i: string,
                        j: mixed,
                        k: mixed,
                        l: mixed,
                        m: mixed,
                        n: bool,
                        o?: array{0:string}
                      }|null),
                      r?: Closure() : (array<mixed, mixed>|null),
                      s: array<mixed, mixed>
                    } */
                    $arr = [];

                    $arr["a"]()',
            ],
            'magicMethodValidAnnotations' => [
                '<?php
                    class Parent {
                        public function __call() {}
                    }

                    /**
                     * @method string getString()
                     * @method  void setInteger(int $integer)
                     * @method setString(int $integer)
                     * @method  getBool(string $foo)  :   bool
                     * @method setBool(string $foo, string|bool $bar)  :   bool
                     * @method (string|int)[] getArray() : array with some text
                     * @method void setArray(array $arr = array(), int $foo = 5) with some more text
                     * @method (callable() : string) getCallable() : callable
                     */
                    class Child extends Parent {}

                    $child = new Child();

                    $a = $child->getString();
                    $child->setInteger(4);
                    /** @psalm-suppress MixedAssignment */
                    $b = $child->setString(5);
                    $c = $child->getBool("hello");
                    $c = $child->setBool("hello", true);
                    $c = $child->setBool("hello", "true");
                    $d = $child->getArray();
                    $child->setArray(["boo"])
                    $e = $child->getCallable();',
                'assertions' => [
                    '$a' => 'string',
                    '$b' => 'mixed',
                    '$c' => 'bool',
                    '$d' => 'array<mixed, string|int>',
                    '$e' => 'callable():string',
                ],
            ],
            'slashAfter?' => [
                '<?php
                    namespace ns;

                    /** @param ?\stdClass $s */
                    function foo($s) : void {
                    }

                    foo(null);
                    foo(new \stdClass);',
            ],
            'generatorReturnType' => [
                '<?php
                    /** @return Generator<int, stdClass> */
                    function g():Generator { yield new stdClass; }

                    $g = g();',
                'assertions' => [
                    '$g' => 'Generator<int, stdClass>',
                ],
            ],
            'returnTypeShouldBeNullable' => [
                '<?php
                    /**
                     * @return stdClass
                     */
                    function foo() : ?stdClass {
                        return rand(0, 1) ? new stdClass : null;
                    }

                    $f = foo();
                    if ($f) {}'
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
                'error_message' => 'TooManyArguments - src' . DIRECTORY_SEPARATOR . 'somefile.php:8 - Too many arguments for method fooBar '
                    . '- expecting 0 but saw 1',
            ],
            'missingParamVar' => [
                '<?php
                    /**
                     * @param string
                     */
                    function fooBar(): void {
                    }',
                'error_message' => 'InvalidDocblock - src' . DIRECTORY_SEPARATOR . 'somefile.php:5 - Badly-formatted @param',
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

            'noStringParamType' => [
                '<?php
                    function fooFoo($a): void {
                        echo substr($a, 4, 2);
                    }',
                'error_message' => 'MissingParamType - src' . DIRECTORY_SEPARATOR . 'somefile.php:2 - Parameter $a has no provided type,'
                    . ' should be string',
                'error_levels' => ['MixedArgument'],
            ],
            'noParamTypeButConcat' => [
                '<?php
                    function fooFoo($a): void {
                        echo $a . "foo";
                    }',
                'error_message' => 'MissingParamType - src' . DIRECTORY_SEPARATOR . 'somefile.php:2 - Parameter $a has no provided type,'
                    . ' should be string',
                'error_levels' => ['MixedOperand'],
            ],
            'noParamTypeButAddition' => [
                '<?php
                    function fooFoo($a): void {
                        echo $a + 5;
                    }',
                'error_message' => 'MissingParamType - src' . DIRECTORY_SEPARATOR . 'somefile.php:2 - Parameter $a has no provided type,'
                    . ' should be int|float',
                'error_levels' => ['MixedOperand', 'MixedArgument'],
            ],
            'noParamTypeButDivision' => [
                '<?php
                    function fooFoo($a): void {
                        echo $a / 5;
                    }',
                'error_message' => 'MissingParamType - src' . DIRECTORY_SEPARATOR . 'somefile.php:2 - Parameter $a has no provided type,'
                    . ' should be int|float',
                'error_levels' => ['MixedOperand', 'MixedArgument'],
            ],
            'noParamTypeButTemplatedString' => [
                '<?php
                    function fooFoo($a): void {
                        echo "$a";
                    }',
                'error_message' => 'MissingParamType - src' . DIRECTORY_SEPARATOR . 'somefile.php:2 - Parameter $a has no provided type,'
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
                'error_message' => 'MissingParamType - src' . DIRECTORY_SEPARATOR . 'somefile.php:2 - Parameter $a has no provided type,'
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
                'error_message' => 'MissingParamType - src' . DIRECTORY_SEPARATOR . 'somefile.php:4 - Parameter $s has no provided type,'
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
                'error_message' => 'MissingParamType - src' . DIRECTORY_SEPARATOR . 'somefile.php:7 - Parameter $s has no provided type,'
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
            'badCallableVar' => [
                '<?php
                    /** @return Closure(int): */
                    function foo() : callable {
                        return function () : void {}
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'magicMethodAnnotationWithoutCall' => [
                '<?php
                    /**
                     * @method string getString()
                     */
                    class Child {}

                    $child = new Child();

                    $a = $child->getString();',
                'error_message' => 'UndefinedMethod',
            ],
            'magicMethodAnnotationWithBadDocblock' => [
                '<?php
                    class Parent {
                        public function __call() {}
                    }

                    /**
                     * @method string getString(\)
                     */
                    class Child extends Parent {}',
                'error_message' => 'InvalidDocblock',
            ],
            'magicMethodAnnotationWithSealed' => [
                '<?php
                    class Parent {
                        public function __call() {}
                    }

                    /**
                     * @method string getString()
                     * @psalm-seal-methods
                     */
                    class Child extends Parent {}

                    $child = new Child();
                    $child->getString();
                    $child->foo();',
                'error_message' => 'UndefinedMethod - src/somefile.php:14 - Method Child::foo does not exist',
            ],
            'magicMethodAnnotationInvalidArg' => [
                '<?php
                    class Parent {
                        public function __call() {}
                    }

                    /**
                     * @method setString(int $integer)
                     */
                    class Child extends Parent {}

                    $child = new Child();

                    $child->setString("five");',
                'error_message' => 'InvalidScalarArgument',
            ],
            'hyphenInType' => [
                '<?php
                    /**
                     * @return - Description
                     */
                    function example() {
                        return "placeholder";
                    }',
                'error_message' => 'InvalidDocblock',
            ],
        ];
    }
}
