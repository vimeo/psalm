<?php
namespace Psalm\Tests;

class ClassTest extends TestCase
{
    use Traits\FileCheckerInvalidCodeParseTestTrait;
    use Traits\FileCheckerValidCodeParseTestTrait;

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'overrideProtectedAccessLevelToPublic' => [
                '<?php
                    class A {
                        protected function fooFoo(): void {}
                    }

                    class B extends A {
                        public function fooFoo(): void {}
                    }',
            ],
            'reflectedParents' => [
                '<?php
                    $e = rand(0, 10)
                      ? new RuntimeException("m")
                      : null;

                    if ($e instanceof Exception) {
                      echo "good";
                    }',
            ],
            'namespacedAliasedClassCall' => [
                '<?php
                    namespace Aye {
                        class Foo {}
                    }
                    namespace Bee {
                        use Aye as A;

                        new A\Foo();
                    }',
            ],
            'abstractExtendsAbstract' => [
                '<?php
                    abstract class A {
                        /** @return void */
                        abstract public function foo();
                    }

                    abstract class B extends A {
                        /** @return void */
                        public function bar() {
                            $this->foo();
                        }
                    }',
            ],
            'missingParentWithFunction' => [
                '<?php
                    class B extends C {
                        public function fooA() { }
                    }',
                'assertions' => [],
                'error_levels' => [
                    'UndefinedClass',
                    'MissingReturnType',
                ],
            ],
            'subclassWithSimplerArg' => [
                '<?php
                    class A {}
                    class B extends A {}

                    class E1 {
                        /**
                         * @param A|B|null $a
                         */
                        public function __construct($a) {
                        }
                    }

                    class E2 extends E1 {
                        /**
                         * @param A|null $a
                         */
                        public function __construct($a) {
                            parent::__construct($a);
                        }
                    }',
            ],
            'PHP7-subclassOfInvalidArgumentExceptionWithSimplerArg' => [
                '<?php
                    class A extends InvalidArgumentException {
                        /**
                         * @param string $message
                         * @param int $code
                         * @param Throwable|null $previous_exception
                         */
                        public function __construct($message, $code, $previous_exception) {
                            parent::__construct($message, $code, $previous_exception);
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
            'undefinedClass' => [
                '<?php
                    (new Foo());',
                'error_message' => 'UndefinedClass',
            ],
            'wrongCaseClass' => [
                '<?php
                    class Foo {}
                    (new foo());',
                'error_message' => 'InvalidClass',
            ],
            'wrongCaseClassWithCall' => [
                '<?php
                    class A {}
                    needsA(new A);
                    function needsA(a $x): void {}',
                'error_message' => 'InvalidClass',
            ],
            'invalidThisFetch' => [
                '<?php
                    echo $this;',
                'error_message' => 'InvalidScope',
            ],
            'invalidThisArgument' => [
                '<?php
                    $this = "hello";',
                'error_message' => 'InvalidScope',
            ],
            'undefinedConstant' => [
                '<?php
                    echo HELLO;',
                'error_message' => 'UndefinedConstant',
            ],
            'undefinedClassConstant' => [
                '<?php
                    class A {}
                    echo A::HELLO;',
                'error_message' => 'UndefinedConstant',
            ],
            'overridePublicAccessLevelToPrivate' => [
                '<?php
                    class A {
                        public function fooFoo(): void {}
                    }

                    class B extends A {
                        private function fooFoo(): void {}
                    }',
                'error_message' => 'OverriddenMethodAccess',
            ],
            'overridePublicAccessLevelToProtected' => [
                '<?php
                    class A {
                        public function fooFoo(): void {}
                    }

                    class B extends A {
                        protected function fooFoo(): void {}
                    }',
                'error_message' => 'OverriddenMethodAccess',
            ],
            'overrideProtectedAccessLevelToPrivate' => [
                '<?php
                    class A {
                        protected function fooFoo(): void {}
                    }

                    class B extends A {
                        private function fooFoo(): void {}
                    }',
                'error_message' => 'OverriddenMethodAccess',
            ],
            'overridePublicPropertyAccessLevelToPrivate' => [
                '<?php
                    class A {
                        /** @var string|null */
                        public $foo;
                    }

                    class B extends A {
                        /** @var string|null */
                        private $foo;
                    }',
                'error_message' => 'OverriddenPropertyAccess',
            ],
            'overridePublicPropertyAccessLevelToProtected' => [
                '<?php
                    class A {
                        /** @var string|null */
                        public $foo;
                    }

                    class B extends A {
                        /** @var string|null */
                        protected $foo;
                    }',
                'error_message' => 'OverriddenPropertyAccess',
            ],
            'overrideProtectedPropertyAccessLevelToPrivate' => [
                '<?php
                    class A {
                        /** @var string|null */
                        protected $foo;
                    }

                    class B extends A {
                        /** @var string|null */
                        private $foo;
                    }',
                'error_message' => 'OverriddenPropertyAccess',
            ],
            'classRedefinition' => [
                '<?php
                    class Foo {}
                    class Foo {}',
                'error_message' => 'DuplicateClass',
            ],
            'classRedefinitionInNamespace' => [
                '<?php
                    namespace Aye {
                        class Foo {}
                        class Foo {}
                    }',
                'error_message' => 'DuplicateClass',
            ],
            'classRedefinitionInSeparateNamespace' => [
                '<?php
                    namespace Aye {
                        class Foo {}
                    }
                    namespace Aye {
                        class Foo {}
                    }',
                'error_message' => 'DuplicateClass',
            ],
            'abstractClassInstantiation' => [
                '<?php
                    abstract class A {}
                    new A();',
                'error_message' => 'AbstractInstantiation',
            ],
            'abstractClassMethod' => [
                '<?php
                abstract class A {
                    abstract public function foo();
                }

                class B extends A { }',
                'error_message' => 'UnimplementedAbstractMethod',
            ],
            'missingParent' => [
                '<?php
                    class A extends B { }',
                'error_message' => 'UndefinedClass',
            ],
            'lessSpecificReturnStatement' => [
                '<?php
                    class A {}
                    class B extends A {}

                    function foo(A $a): B {
                        return $a;
                    }',
                'error_message' => 'LessSpecificReturnStatement',
            ],
            'circularReference' => [
                '<?php
                    class A extends A {}',
                'error_message' => 'CircularReference',
            ],
        ];
    }
}
