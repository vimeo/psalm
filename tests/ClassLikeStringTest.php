<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Config;
use Psalm\Context;
use Psalm\Exception\CodeException;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class ClassLikeStringTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function testDontAllowStringStandInForNewClass(): void
    {
        $this->expectExceptionMessage('InvalidStringClass');
        $this->expectException(CodeException::class);
        Config::getInstance()->allow_string_standin_for_class = false;

        $this->addFile(
            'somefile.php',
            '<?php
                class A {}

                $a = "A";

                new $a();',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testDontAllowStringStandInForStaticMethodCall(): void
    {
        $this->expectExceptionMessage('InvalidStringClass');
        $this->expectException(CodeException::class);
        Config::getInstance()->allow_string_standin_for_class = false;

        $this->addFile(
            'somefile.php',
            '<?php
                class A {
                    public static function foo() : void {}
                }

                $a = "A";

                $a::foo();',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function providerValidCodeParse(): iterable
    {
        return [
            'arrayOfClassConstants' => [
                'code' => '<?php
                    /**
                     * @param array<class-string> $arr
                     */
                    function takesClassConstants(array $arr) : void {}

                    class A {}
                    class B {}

                    takesClassConstants([A::class, B::class]);',
            ],
            'arrayOfStringClasses' => [
                'code' => '<?php
                    /**
                     * @param array<class-string> $arr
                     */
                    function takesClassConstants(array $arr) : void {}

                    class A {}
                    class B {}

                    takesClassConstants(["A", "B"]);',
                'assertions' => [],
                'ignored_issues' => ['ArgumentTypeCoercion'],
            ],
            'singleClassConstantAsConstant' => [
                'code' => '<?php
                    /**
                     * @param class-string $s
                     */
                    function takesClassConstants(string $s) : void {}

                    class A {}

                    takesClassConstants(A::class);',
            ],
            'singleClassConstantWithString' => [
                'code' => '<?php
                    /**
                     * @param class-string $s
                     */
                    function takesClassConstants(string $s) : void {}

                    class A {}

                    /** @psalm-suppress ArgumentTypeCoercion */
                    takesClassConstants("A");',
                'assertions' => [],
            ],
            'returnClassConstant' => [
                'code' => '<?php
                    class A {}

                    /**
                     * @return class-string
                     */
                    function takesClassConstants() : string {
                        return A::class;
                    }',
            ],
            'returnClassConstantAllowCoercion' => [
                'code' => '<?php
                    class A {}

                    /**
                     * @return class-string
                     */
                    function takesClassConstants() : string {
                        return "A";
                    }',
                'assertions' => [],
                'ignored_issues' => ['LessSpecificReturnStatement', 'MoreSpecificReturnType'],
            ],
            'returnClassConstantArray' => [
                'code' => '<?php
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
                'code' => '<?php
                    class A {}
                    class B {}

                    /**
                     * @return array<class-string>
                     */
                    function takesClassConstants() : array {
                        return ["A", "B"];
                    }',
                'assertions' => [],
                'ignored_issues' => ['LessSpecificReturnStatement', 'MoreSpecificReturnType'],
            ],
            'ifClassStringEquals' => [
                'code' => '<?php
                    class A {}
                    class B {}

                    /** @param class-string $class */
                    function foo(string $class) : void {
                        if ($class === A::class) {}
                        if ($class === A::class || $class === B::class) {}
                    }',
            ],
            'classStringCombination' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'ignored_issues' => ['MixedMethodCall'],
            ],
            'constantArrayOffset' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    class A {}

                    function foo(string $s) : ?A {
                        if ($s !== A::class) {
                            return null;
                        }
                        return new $s();
                    }',
            ],
            'takesChildOfClass' => [
                'code' => '<?php
                    class A {}
                    class AChild extends A {}

                    /**
                     * @param class-string<A> $s
                     */
                    function foo(string $s) : void {}

                    foo(AChild::class);',
            ],
            'returnClassConstantClassStringParameterized' => [
                'code' => '<?php
                    class A {}

                    /**
                     * @return class-string<A> $s
                     */
                    function foo(A $a) : string {
                        return $a::class;
                    }',
            ],
            'returnGetCalledClassClassStringParameterized' => [
                'code' => '<?php
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
                'code' => '<?php
                    class A {}

                    /**
                     * @return class-string<A> $s
                     */
                    function foo(A $a) : string {
                        return get_class($a);
                    }',
            ],
            'returnGetParentClassClassStringParameterizedNoArg' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    function f(Exception $e): ?InvalidArgumentException {
                        $type = InvalidArgumentException::class;
                        if ($e instanceof $type) {
                            return $e;
                        } else {
                            return null;
                        }
                    }',
            ],
            'instanceofClassStringNotLiteral' => [
                'code' => '<?php
                    final class Z {
                    /**
                     * @psalm-var class-string<stdClass> $class
                     */
                    private string $class = stdClass::class;

                    public function go(object $object): ?stdClass {
                        $a = $this->class;
                        if ($object instanceof $a) {
                            return $object;
                        }
                        return null;
                    }
                }',
            ],
            'returnTemplatedClassString' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    class A {
                        const CLASSES = ["foobar" => B::class];

                        function foo(): bool {
                            return self::CLASSES["foobar"] === static::class;
                        }
                    }

                    class B extends A {}',
            ],
            'noCrashWhenClassExists' => [
                'code' => '<?php
                    class A {}

                    if (class_exists(A::class)) {
                        new \RuntimeException();
                    }',
            ],

            'noCrashWhenClassExistsNegated' => [
                'code' => '<?php
                    class A {}

                    if (!class_exists(A::class)) {
                        new \RuntimeException();
                    }',
            ],
            'convertToStringClassExistsNegated' => [
                'code' => '<?php
                    /** @param class-string $className */
                    $className = stdClass::class;
                    if (class_exists($className)) {
                        throw new \RuntimeException($className);
                    }',
                'assertions' => [
                    '$className===' => 'string',
                ],

            ],
            'createNewObjectFromGetClass' => [
                'code' => '<?php
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
                    function takesExampleClassString(string $className): void {}',
            ],
            'noCrashOnPolyfill' => [
                'code' => '<?php
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
                'code' => '<?php
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
                    }',
            ],
            'traitClassStringClone' => [
                'code' => '<?php
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
                    }',
            ],
            'staticClassReturn' => [
                'code' => '<?php
                    /**
                     * @psalm-consistent-constructor
                     */
                    class A {
                        /** @return static */
                        public static function getInstance() {
                            $class = static::class;
                            return new $class();
                        }
                    }',
            ],
            'getCalledClassIsStaticClass' => [
                'code' => '<?php
                    /**
                     * @psalm-consistent-constructor
                     */
                    class A {
                        /** @return static */
                        public function getStatic() {
                            $c = get_called_class();
                            return new $c();
                        }
                    }',
            ],
            'accessConstantOnClassStringVariable' => [
                'code' => '<?php
                    class Beep {
                        /** @var string */
                        public static $boop = "boop";
                    }
                    $beep = rand(0, 1) ? new Beep() : Beep::class;
                    echo $beep::$boop;
                ',
            ],
            'ClassConstFetchWithTemplate' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    /** @return ReflectionClass<object> */
                    function takesString(string $s) {
                        /** @psalm-suppress ArgumentTypeCoercion */
                        return new ReflectionClass($s);
                    }',
            ],
            'checkDifferentSubclass' => [
                'code' => '<?php
                    class A {}
                    class B {}

                    /** @param class-string<A> $s */
                    function takesAString(string $a): void {}
                    /** @param class-string<B> $s */
                    function takesBString(string $a): void {}

                    /** @param class-string $s */
                    function foo(string $s): void {
                        if (is_subclass_of($s, A::class)) {
                            takesAString($s);
                        }
                        if (is_subclass_of($s, B::class)) {
                            takesBString($s);
                        }
                    }',
            ],
            'checkDifferentSubclassAfterNotClassExists' => [
                'code' => '<?php
                    class A {}
                    class B {}

                    /** @param class-string<A> $s */
                    function takesAString(string $a): void {}
                    /** @param class-string<B> $s */
                    function takesBString(string $a): void {}

                    function foo(string $s): void {
                        if (!class_exists($s, false)) {
                            return;
                        }
                        if (is_subclass_of($s, A::class)) {
                            takesAString($s);
                        }
                        if (is_subclass_of($s, B::class)) {
                            takesBString($s);
                        }
                    }',
            ],
            'compareGetClassToLiteralClass' => [
                'code' => '<?php
                    class A {}
                    class B extends A {}

                    function foo(A $a): void {
                        if (get_class($a) === A::class) {}
                    }',
            ],
            'classStringUnion' => [
                'code' => '<?php
                    class Foo
                    {
                        /** @var class-string<TypeOne>|class-string<TypeTwo> */
                        public ?string $bar = null;
                        /** @var class-string<TypeOne|TypeTwo> */
                        public ?string $baz = null;
                    }

                    class TypeOne {}

                    class TypeTwo {}

                    $foo = new Foo;
                    $foo->bar = TypeOne::class;
                    $foo->bar = TypeOne::class;
                    $foo->baz = TypeTwo::class;
                    $foo->baz = TypeTwo::class;',
            ],
            'classStringOfUnionTypeParameter' => [
                'code' => '<?php

                    class A {}
                    class B {}

                    /**
                     * @template T as A|B
                     *
                     * @param class-string<T> $class
                     * @return class-string<T>
                     */
                    function test(string $class): string {
                        return $class;
                    }

                    $r = test(A::class);',
                'assertions' => [
                    '$r' => 'class-string<A>',
                ],
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'arrayOfStringClasses' => [
                'code' => '<?php
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
                'code' => '<?php
                    /**
                     * @param array<class-string> $arr
                     */
                    function takesClassConstants(array $arr) : void {}
                    /** @psalm-suppress ArgumentTypeCoercion */
                    takesClassConstants(["A", "B"]);',
                'error_message' => 'UndefinedClass',
            ],
            'singleClassConstantWithInvalidDocblock' => [
                'code' => '<?php
                    /**
                     * @param clas-string $s
                     */
                    function takesClassConstants(string $s) : void {}',
                'error_message' => 'InvalidDocblock',
            ],
            'returnClassConstantDisallowCoercion' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    class A {}

                    /**
                     * @return array<class-string>
                     */
                    function takesClassConstants() : array {
                        return ["A", "B"];
                    }',
                'error_message' => 'UndefinedClass',
                'ignored_issues' => ['LessSpecificReturnStatement', 'MoreSpecificReturnType'],
            ],
            'badClassStringConstructor' => [
                'code' => '<?php
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
                'code' => '<?php
                    /** @param class-string $s */
                    function bar(string $s) : void {
                        new $s();
                    }',
                'error_message' => 'MixedMethodCall',
            ],
            'doesNotTakeChildOfClass' => [
                'code' => '<?php
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
                'code' => '<?php
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
