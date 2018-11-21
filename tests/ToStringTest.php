<?php
namespace Psalm\Tests;

class ToStringTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return array
     */
    public function providerValidCodeParse()
    {
        return [
            'validToString' => [
                '<?php
                    class A {
                        function __toString() {
                            return "hello";
                        }
                    }
                    echo (new A);',
            ],
            'inheritedToString' => [
                '<?php
                    class A {
                        function __toString() {
                            return "hello";
                        }
                    }
                    class B {
                        function __toString() {
                            return "goodbye";
                        }
                    }
                    class C extends B {}

                    $c = new C();
                    echo (string) $c;',
            ],
            'goodCast' => [
                '<?php
                    class A {
                        public function __toString(): string
                        {
                            return "hello";
                        }
                    }

                    /** @param string|A $b */
                    function fooFoo($b): void {}

                    /** @param A|string $b */
                    function barBar($b): void {}

                    fooFoo(new A());
                    barBar(new A());',
            ],
            'resourceToString' => [
                '<?php
                    $a = fopen("php://memory", "r");
                    if ($a === false) exit;
                    $b = (string) $a;',
            ],
            'canBeObject' => [
                '<?php
                    class A {
                        public function __toString() {
                            return "A";
                        }
                    }

                    /** @param string|object $s */
                    function foo($s) : void {}

                    foo(new A);',
            ],
        ];
    }

    /**
     * @return array
     */
    public function providerInvalidCodeParse()
    {
        return [
            'echoClass' => [
                '<?php
                    class A {}
                    echo (new A);',
                'error_message' => 'InvalidArgument',
            ],
            'echoCastClass' => [
                '<?php
                    class A {}
                    echo (string)(new A);',
                'error_message' => 'InvalidCast',
            ],
            'invalidToStringReturnType' => [
                '<?php
                    class A {
                        function __toString(): void { }
                    }',
                'error_message' => 'InvalidToString',
            ],
            'invalidInferredToStringReturnType' => [
                '<?php
                    class A {
                        function __toString() { }
                    }',
                'error_message' => 'InvalidToString',
            ],
            'implicitCastWithStrictTypes' => [
                '<?php declare(strict_types=1);
                    class A {
                        public function __toString(): string
                        {
                            return "hello";
                        }
                    }

                    function fooFoo(string $b): void {}
                    fooFoo(new A());',
                'error_message' => 'InvalidArgument',
            ],
            'implicitCast' => [
                '<?php
                    class A {
                        public function __toString(): string
                        {
                            return "hello";
                        }
                    }

                    function fooFoo(string $b): void {}
                    fooFoo(new A());',
                'error_message' => 'ImplicitToStringCast',
            ],
            'implicitCastFromInterface' => [
                '<?php
                    interface I {
                        public function __toString();
                    }

                    function takesString(string $str): void { }

                    function takesI(I $i): void
                    {
                        takesString($i);
                    }',
                'error_message' => 'ImplicitToStringCast',
            ],
            'implicitConcatenation' => [
                '<?php
                    interface I {
                        public function __toString();
                    }

                    function takesI(I $i): void
                    {
                        $a = $i . "hello";
                    }',
                'error_message' => 'ImplicitToStringCast',
                [],
                true
            ],
            'resourceCannotBeCoercedToString' => [
                '<?php
                    function takesString(string $s) : void {}
                    $a = fopen("php://memory", "r");
                    takesString($a);',
                'error_message' => 'InvalidArgument',
            ],
        ];
    }
}
