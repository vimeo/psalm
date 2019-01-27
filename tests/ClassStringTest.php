<?php
namespace Psalm\Tests;

use Psalm\Config;
use Psalm\Context;

class ClassStringTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

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
    public function providerValidCodeParse()
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
                    }'
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
            'createClassOfTypeFromStringUsingIsSubclassOfString' => [
                '<?php
                    class A {}

                    /**
                     * @return class-string<A> $s
                     */
                    function foo(string $s) : string {
                        if (!class_exists($s)) {
                            throw new \UnexpectedValueException("bad");
                        }

                        if (!is_subclass_of($s, "\A")) {
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
                     * @param class-string<Foo&Bar> $className
                     */
                    function foo($className) : void {
                        $className::one();
                        $className::two();
                    }'
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
                     * @param class-string<Bar> $className
                     */
                    function foo($className) : void {
                        $className::two();

                        if (is_subclass_of($className, Foo::class, true)) {
                            $className::one();
                            $className::two();
                        }
                    }'
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
                    }'
            ],
        ];
    }

    /**
     * @return array
     */
    public function providerInvalidCodeParse()
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
