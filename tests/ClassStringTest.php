<?php
namespace Psalm\Tests;

use Psalm\Config;
use Psalm\Context;

class ClassStringTest extends TestCase
{
    use Traits\FileCheckerInvalidCodeParseTestTrait;
    use Traits\FileCheckerValidCodeParseTestTrait;

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
        ];
    }

    /**
     * @return array
     */
    public function providerFileCheckerInvalidCodeParse()
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
        ];
    }
}
