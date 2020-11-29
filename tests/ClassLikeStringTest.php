<?php
namespace Psalm\Tests;

use Psalm\Config;
use Psalm\Context;

class ClassLikeStringTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    public function testDontAllowStringStandInForNewClass(): void
    {
        $this->expectExceptionMessage('InvalidStringClass');
        $this->expectException(\Psalm\Exception\CodeException::class);
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

    public function testDontAllowStringStandInForStaticMethodCall(): void
    {
        $this->expectExceptionMessage('InvalidStringClass');
        $this->expectException(\Psalm\Exception\CodeException::class);
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
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
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
                'error_levels' => ['ArgumentTypeCoercion'],
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

                    /** @psalm-suppress ArgumentTypeCoercion */
                    takesClassConstants("A");',
                'annotations' => [],
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
            'classStringCombination' => [
                '<?php
                    class A {}

                    /** @return class-string */
                    function foo() : string {
                        return A::class;
                    }

                    /** @param class-string $a */
                    function bar(string $a) : void {}

                    bar(rand(0, 1) ? foo() : A::class);
                    bar(rand(0, 1) ? A::class : foo());',
            ],
            'assertionToClassString' => [
                '<?php
                    class A {}

                    function foo(string $s) : void {
                        if ($s === A::class) {
                            bar($s);
                        }
                    }

                    /** @param class-string $s */
                    function bar(string $s) : void {
                        new $s();
                    }',
                'assertions' => [],
                'error_levels' => ['MixedMethodCall'],
            ],
            'constantArrayOffset' => [
                '<?php
                    class A {
                        const FOO = [
                            B::class => "bar",
                        ];
                    }
                    class B {}

                    /** @param class-string $s */
                    function bar(string $s) : void {}

                    foreach (A::FOO as $class => $_) {
                        bar($class);
                    }',
            ],
            'arrayEquivalence' => [
                '<?php
                    class A {}
                    class B {}

                    $foo = [
                        A::class,
                        B::class
                    ];

                    foreach ($foo as $class) {
                        if ($class === A::class) {}
                    }',
            ],
            'switchMixedVar' => [
                '<?php
                    class A {}
                    class B {}
                    class C {}

                    /** @param mixed $a */
                    function foo($a) : void {
                        switch ($a) {
                            case A::class:
                                return;

                            case B::class:
                            case C::class:
                                return;
                        }
                    }',
            ],
            'reconcileToFalsy' => [
                '<?php
                    /** @psalm-param ?class-string $s */
                    function bar(?string $s) : void {}

                    class A {}

                    /** @psalm-return ?class-string */
                    function bat() {
                        if (rand(0, 1)) return null;
                        return A::class;
                    }

                    $a = bat();
                    $a ? 1 : 0;
                    bar($a);',
            ],
            'allowTraitClassComparison' => [
                '<?php
                    trait T {
                        public function foo() : void {
                            if (self::class === A::class) {}
                            if (self::class !== A::class) {}
                        }
                    }

                    class A {
                        use T;
                    }

                    class B {
                        use T;
                    }',
            ],
            'refineStringToClassString' => [
                '<?php
                    class A {}

                    function foo(string $s) : ?A {
                        if ($s !== A::class) {
                            return null;
                        }
                        return new $s();
                    }',
            ],
            'takesChildOfClass' => [
                '<?php
                    class A {}
                    class AChild extends A {}

                    /**
                     * @param class-string<A> $s
                     */
                    function foo(string $s) : void {}

                    foo(AChild::class);',
            ],
            'returnClassConstantClassStringParameterized' => [
                '<?php
                    class A {}

                    /**
                     * @return class-string<A> $s
                     */
                    function foo(A $a) : string {
                        return $a::class;
                    }',
            ],
            'returnGetCalledClassClassStringParameterized' => [
                '<?php
                    class A {
                        /**
                         * @return class-string<A> $s
                         */
                        function foo() : string {
                            return get_called_class();
                        }
                    }',
            ],
            'returnGetClassClassStringParameterized' => [
                '<?php
                    class A {}

                    /**
                     * @return class-string<A> $s
                     */
                    function foo(A $a) : string {
                        return get_class($a);
                    }',
            ],
            'returnGetParentClassClassStringParameterizedNoArg' => [
                '<?php
                    class A {}

                    class B extends A {
                        /**
                         * @return class-string<A> $s
                         */
                        function foo() : string {
                            return get_parent_class();
                        }
                    }',
            ],
            'createClassOfTypeFromString' => [
                '<?php
                    class A {}

                    /**
                     * @return class-string<A> $s
                     */
                    function foo(string $s) : string {
                        if (!class_exists($s)) {
                            throw new \UnexpectedValueException("bad");
                        }

                        if (!is_a($s, A::class, true)) {
                            throw new \UnexpectedValueException("bad");
                        }

                        return $s;
                    }',
            ],
            'createClassOfTypeFromStringUsingIsSubclassOf' => [
                '<?php
                    class A {}

                    /**
                     * @return class-string<A> $s
                     */
                    function foo(string $s) : string {
                        if (!class_exists($s)) {
                            throw new \UnexpectedValueException("bad");
                        }

                        if (!is_subclass_of($s, A::class)) {
                            throw new \UnexpectedValueException("bad");
                        }

                        return $s;
                    }',
            ],
            'checkSubclassOfAbstract' => [
                '<?php
                    interface Foo {
                        public static function Bar() : bool;
                    };

                    class FooClass implements Foo {
                        public static function Bar() : bool {
                            return true;
                        }
                    }

                    function foo(string $className) : bool {
                        if (is_subclass_of($className, Foo::class, true)) {
                            return $className::Bar();
                        }

                        return false;
                    }',
            ],
            'explicitIntersectionClassString' => [
                '<?php
                    interface Foo {
                        public static function one() : void;
                    };

                    interface Bar {
                        public static function two() : void;
                    }

                    /**
                     * @param interface-string<Foo&Bar> $className
                     */
                    function foo($className) : void {
                        $className::one();
                        $className::two();
                    }',
            ],
            'implicitIntersectionClassString' => [
                '<?php
                    interface Foo {
                        public static function one() : bool;
                    };

                    interface Bar {
                        public static function two() : bool;
                    }

                    /**
                     * @param interface-string<Bar> $className
                     */
                    function foo(string $className) : void {
                        $className::two();

                        if (is_subclass_of($className, Foo::class, true)) {
                            $className::one();
                            $className::two();
                        }
                    }',
            ],
            'instanceofClassString' => [
                '<?php
                    function f(Exception $e): ?InvalidArgumentException {
                        $type = InvalidArgumentException::class;
                        if ($e instanceof $type) {
                            return $e;
                        } else {
                            return null;
                        }
                    }',
            ],
            'returnTemplatedClassString' => [
                '<?php
                    /**
                     * @template T
                     *
                     * @param class-string<T> $shouldBe
                     * @return class-string<T>
                     */
                    function identity(string $shouldBe) : string {  return $shouldBe; }

                    identity(DateTimeImmutable::class)::createFromMutable(new DateTime());',
            ],
            'filterIsObject' => [
                '<?php
                    /**
                     * @param interface-string<DateTimeInterface>|DateTimeInterface $maybe
                     *
                     * @return interface-string<DateTimeInterface>
                     */
                    function Foo($maybe) : string {
                        if (is_object($maybe)) {
                            return get_class($maybe);
                        }

                        return $maybe;
                    }',
            ],
            'filterIsString' => [
                '<?php
                    /**
                     * @param interface-string<DateTimeInterface>|DateTimeInterface $maybe
                     *
                     * @return interface-string<DateTimeInterface>
                     */
                    function Bar($maybe) : string {
                        if (is_string($maybe)) {
                            return $maybe;
                        }

                        return get_class($maybe);
                    }',
            ],
            'mergeLiteralClassStringsWithGeneric' => [
                '<?php
                    class Base {}
                    class A extends Base {}
                    class B extends Base {}

                    /**
                     * @param array<A::class|B::class> $literal_classes
                     * @param array<class-string<Base>> $generic_classes
                     * @return array<class-string<Base>>
                     */
                    function foo(array $literal_classes, array $generic_classes) {
                        return array_merge($literal_classes, $generic_classes);
                    }',
            ],
            'mergeGenericClassStringsWithLiteral' => [
                '<?php
                    class Base {}
                    class A extends Base {}
                    class B extends Base {}

                    /**
                     * @param array<A::class|B::class> $literal_classes
                     * @param array<class-string<Base>> $generic_classes
                     * @return array<class-string<Base>>
                     */
                    function bar(array $literal_classes, array $generic_classes) {
                        return array_merge($generic_classes, $literal_classes);
                    }',
            ],
            'noCrashWithIsSubclassOfNonExistentVariable' => [
                '<?php
                    class A {}

                    function foo() : void {
                        /**
                         * @psalm-suppress UndefinedVariable
                         * @psalm-suppress MixedArgument
                         */
                        if (!is_subclass_of($s, A::class)) {}
                    }',
            ],
            'allowClassExistsCheckOnClassString' => [
                '<?php
                    class C
                    {
                        public function __construct() {
                            if (class_exists(\Doesnt\Really::class)) {
                                \Doesnt\Really::something();
                            }
                        }
                    }',
            ],
            'allowClassExistsCheckOnString' => [
                '<?php
                    class C
                    {
                        public function __construct() {
                            if (class_exists("Doesnt\\Really")) {
                                \Doesnt\Really::something();
                            }
                        }
                    }',
            ],
            'allowComparisonToStaticClassString' => [
                '<?php
                    class A {
                        const CLASSES = ["foobar" => B::class];

                        function foo(): bool {
                            return self::CLASSES["foobar"] === static::class;
                        }
                    }

                    class B extends A {}',
            ],
            'noCrashWhenClassExists' => [
                '<?php
                    class A {}

                    if (class_exists(A::class)) {
                        new \RuntimeException();
                    }',
            ],

            'noCrashWhenClassExistsNegated' => [
                '<?php
                    class A {}

                    if (!class_exists(A::class)) {
                        new \RuntimeException();
                    }',
            ],
            'createNewObjectFromGetClass' => [
                '<?php
                    /**
                     * @psalm-consistent-constructor
                     */
                    class Example {
                        static function staticMethod(): string {
                            return "";
                        }

                        public function instanceMethod(): string {
                            $className = get_class();

                            return $className::staticMethod();
                        }
                    }

                    /**
                     * @param class-string<Example> $className
                     */
                    function example(string $className, Example $object): string {
                        $objectClassName = get_class($object);

                        takesExampleClassString($className);
                        takesExampleClassString($objectClassName);

                        if (rand(0, 1)) {
                            return (new $className)->instanceMethod();
                        }

                        if (rand(0, 1)) {
                            return (new $objectClassName)->instanceMethod();
                        }

                        if (rand(0, 1)) {
                            return $className::staticMethod();
                        }

                        return $objectClassName::staticMethod();
                    }


                    /** @param class-string<Example> $className */
                    function takesExampleClassString(string $className): void {}'
            ],
            'noCrashOnPolyfill' => [
                '<?php
                    if (class_exists(My_Parent::class) && !class_exists(My_Extend::class)) {
                        /**
                         * Extended class
                         */
                        class My_Extend extends My_Parent {
                            /**
                             * Construct
                             *
                             * @return void
                             */
                            public function __construct() {
                                echo "foo";
                            }
                        }
                    }',
            ],
            'selfResolvedOnStaticProperty' => [
                '<?php
                    namespace Bar;

                    class Foo {
                        /** @var class-string<self> */
                        private static $c;

                        /**
                         * @return class-string<self>
                         */
                        public static function r() : string
                        {
                            return self::$c;
                        }
                    }'
            ],
            'traitClassStringClone' => [
                '<?php
                    trait Factory
                    {
                        /** @return class-string<static> */
                        public static function getFactoryClass()
                        {
                            return static::class;
                        }
                    }

                    /**
                     * @psalm-consistent-constructor
                     */
                    class A
                    {
                        use Factory;

                        public static function factory(): self
                        {
                            $class = static::getFactoryClass();
                            return new $class;
                        }
                    }

                    /**
                     * @psalm-consistent-constructor
                     */
                    class B
                    {
                        use Factory;

                        public static function factory(): self
                        {
                            $class = static::getFactoryClass();
                            return new $class;
                        }
                    }'
            ],
            'staticClassReturn' => [
                '<?php
                    /**
                     * @psalm-consistent-constructor
                     */
                    class A {
                        /** @return static */
                        public static function getInstance() {
                            $class = static::class;
                            return new $class();
                        }
                    }'
            ],
            'getCalledClassIsStaticClass' => [
                '<?php
                    /**
                     * @psalm-consistent-constructor
                     */
                    class A {
                        /** @return static */
                        public function getStatic() {
                            $c = get_called_class();
                            return new $c();
                        }
                    }'
            ],
            'accessConstantOnClassStringVariable' => [
                '<?php
                    class Beep {
                        /** @var string */
                        public static $boop = "boop";
                    }
                    $beep = rand(0, 1) ? new Beep() : Beep::class;
                    echo $beep::$boop;
                ',
            ],
            'ClassConstFetchWithTemplate' => [
                '<?php
                    /**
                     * @template T of object
                     * @psalm-param T $obj
                     * @return class-string<T>
                     */
                    function a($obj) {
                        $class = $obj::class;

                        return $class;
                    }',
            ],
            'classStringAllowsClasses' => [
                '<?php
                    /**
                     * @param class-string $s
                     */
                    function takesOpen(string $s): void {}

                    /**
                     * @param class-string<Exception> $s
                     */
                    function takesException(string $s): void {}

                    /**
                     * @param class-string<Exception> $s
                     */
                    function takesThrowable(string $s): void {}

                    takesOpen(InvalidArgumentException::class);
                    takesException(InvalidArgumentException::class);
                    takesThrowable(InvalidArgumentException::class);',
            ],
            'reflectionClassCoercion' => [
                '<?php
                    /** @return ReflectionClass<object> */
                    function takesString(string $s) {
                        /** @psalm-suppress ArgumentTypeCoercion */
                        return new ReflectionClass($s);
                    }',
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,1?:string[],2?:bool,3?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'arrayOfStringClasses' => [
                '<?php
                    /**
                     * @param array<class-string> $arr
                     */
                    function takesClassConstants(array $arr) : void {}

                    class A {}
                    class B {}

                    takesClassConstants(["A", "B"]);',
                'error_message' => 'ArgumentTypeCoercion',
            ],
            'arrayOfNonExistentStringClasses' => [
                '<?php
                    /**
                     * @param array<class-string> $arr
                     */
                    function takesClassConstants(array $arr) : void {}
                    /** @psalm-suppress ArgumentTypeCoercion */
                    takesClassConstants(["A", "B"]);',
                'error_message' => 'UndefinedClass',
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
            'badClassStringConstructor' => [
                '<?php
                    class Foo
                    {
                        public function __construct(int $_)
                        {
                        }
                    }

                    /**
                     * @return Foo
                     */
                    function makeFoo()
                    {
                        $fooClass = Foo::class;
                        return new $fooClass;
                    }',
                'error_message' => 'TooFewArguments',
            ],
            'unknownConstructorCall' => [
                '<?php
                    /** @param class-string $s */
                    function bar(string $s) : void {
                        new $s();
                    }',
                'error_message' => 'MixedMethodCall',
            ],
            'doesNotTakeChildOfClass' => [
                '<?php
                    class A {}
                    class AChild extends A {}

                    /**
                     * @param A::class $s
                     */
                    function foo(string $s) : void {}

                    foo(AChild::class);',
                'error_message' => 'InvalidArgument',
            ],
            'createClassOfWrongTypeFromString' => [
                '<?php
                    class A {}
                    class B {}

                    /**
                     * @return class-string<A> $s
                     */
                    function foo(string $s) : string {
                        if (!class_exists($s)) {
                            throw new \UnexpectedValueException("bad");
                        }

                        if (!is_a($s, B::class, true)) {
                            throw new \UnexpectedValueException("bad");
                        }

                        return $s;
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
        ];
    }
}
