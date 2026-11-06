<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Context;
use Psalm\Exception\CodeException;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class ClassScopeTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function testInaccessiblePrivateMethodFromTheGlobalScopeHasNoContext(): void
    {
        $this->expectException(CodeException::class);
        $this->expectExceptionMessageMatches('/InaccessibleMethod - .*Cannot access private method A::__construct$/');

        $this->addFile(
            'somefile.php',
            '<?php
                class A {
                    private function __construct() {}
                }

                new A();',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testMethodDeniedByAProviderFromTheGlobalScopeHasNoContext(): void
    {
        $this->expectException(CodeException::class);
        $this->expectExceptionMessageMatches('/InaccessibleMethod - .*Cannot access method VisibilityDenied::foo$/');

        $this->project_analyzer->getCodebase()->methods->visibility_provider->registerClosure(
            'VisibilityDenied',
            static fn(): bool => false,
        );

        $this->addFile(
            'somefile.php',
            '<?php
                class VisibilityDenied {
                    public function foo(): void {}
                }

                (new VisibilityDenied())->foo();',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'accessiblePrivateMethodFromSubclass' => [
                'code' => '<?php
                    class A {
                        private function fooFoo(): void {

                        }

                        private function barBar(): void {
                            $this->fooFoo();
                        }
                    }',
            ],
            'accessibleProtectedMethodFromSubclass' => [
                'code' => '<?php
                    class A {
                        protected function fooFoo(): void {
                        }
                    }

                    class B extends A {
                        public function doFoo(): void {
                            $this->fooFoo();
                        }
                    }',
            ],
            'accessibleProtectedMethodFromOtherSubclass' => [
                'code' => '<?php
                    class A {
                        protected function fooFoo(): void {
                        }
                    }

                    class B extends A { }

                    class C extends A {
                        public function doFoo(): void {
                            (new B)->fooFoo();
                        }
                    }',
            ],
            'accessibleProtectedPropertyFromSubclass' => [
                'code' => '<?php
                    class A {
                        /** @var string */
                        protected $fooFoo = "";
                    }

                    class B extends A {
                        public function doFoo(): void {
                            echo $this->fooFoo;
                        }
                    }',
            ],
            'accessibleProtectedPropertyFromGreatGrandparent' => [
                'code' => '<?php
                    class A {
                        /** @var string */
                        protected $fooFoo = "";
                    }

                    class B extends A { }

                    class C extends B { }

                    class D extends C {
                        public function doFoo(): void {
                            echo $this->fooFoo;
                        }
                    }',
            ],
            'accessibleProtectedPropertyFromOtherSubclass' => [
                'code' => '<?php
                    class A {
                        /** @var string */
                        protected $fooFoo = "";
                    }

                    class B extends A {
                    }

                    class C extends A {
                        public function fooFoo(): void {
                            $b = new B();
                            $b->fooFoo = "hello";
                        }
                    }',
            ],
            'accessibleStaticPropertyFromSubclass' => [
                'code' => '<?php
                    class A {
                        /** @var string */
                        protected static $fooFoo = "";

                        public function barBar(): void {
                            echo self::$fooFoo;
                        }
                    }

                    class B extends A {
                        public function doFoo(): void {
                            echo A::$fooFoo;
                        }
                    }',
            ],
            'definedPrivateMethod' => [
                'code' => '<?php
                    class A {
                        public function foo(): void {
                            if ($this instanceof B) {
                                $this->boop();
                            }
                        }

                        private function boop(): void {}
                    }

                    class B extends A {
                        private function boop(): void {}
                    }',
            ],
            'allowMethodCallToProtectedFromParent' => [
                'code' => '<?php
                    class A {
                        public function __construct() {
                            B::foo();
                        }
                    }

                    class B extends A {
                        protected static function foo(): void {
                            echo "here";
                        }
                    }',
            ],
        ];
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerInvalidCodeParse(): iterable
    {
        return [
            'inaccessiblePrivateMethod' => [
                'code' => '<?php
                    class A {
                        private function fooFoo(): void {

                        }
                    }

                    (new A())->fooFoo();',
                'error_message' => 'InaccessibleMethod',
            ],
            'inaccessibleProtectMethod' => [
                'code' => '<?php
                    class A {
                        protected function fooFoo(): void {

                        }
                    }

                    (new A())->fooFoo();',
                'error_message' => 'InaccessibleMethod',
            ],
            'inaccessiblePrivateMethodFromSubclass' => [
                'code' => '<?php
                    class A {
                        private function fooFoo(): void {

                        }
                    }

                    class B extends A {
                        public function doFoo(): void {
                            $this->fooFoo();
                        }
                    }',
                'error_message' => 'UndefinedMethod',
            ],
            'inaccessibleProtectredMethodFromOtherSubclass' => [
                'code' => '<?php
                    trait T {
                        protected function fooFoo(): void {
                        }
                    }

                    class B {
                        use T;
                    }

                    class C {
                        use T;

                        public function doFoo(): void {
                            (new B)->fooFoo();
                        }
                    }',
                'error_message' => 'InaccessibleMethod',
            ],
            'inaccessiblePrivateProperty' => [
                'code' => '<?php
                    class A {
                        /** @var string */
                        private $fooFoo;
                    }

                    echo (new A())->fooFoo;',
                'error_message' => 'InaccessibleProperty',
            ],
            'inaccessibleProtectedProperty' => [
                'code' => '<?php
                    class A {
                        /** @var string */
                        protected $fooFoo;
                    }

                    echo (new A())->fooFoo;',
                'error_message' => 'InaccessibleProperty',
            ],
            'inaccessiblePrivatePropertyFromSubclass' => [
                'code' => '<?php
                    class A {
                        /** @var string */
                        private $fooFoo = "";
                    }

                    class B extends A {
                        public function doFoo(): void {
                            echo $this->fooFoo;
                        }
                    }',
                'error_message' => 'UndefinedThisPropertyFetch',
            ],
            'inaccessibleStaticPrivateProperty' => [
                'code' => '<?php
                    class A {
                        /** @var string */
                        private static $fooFoo;
                    }

                    echo A::$fooFoo;',
                'error_message' => 'InaccessibleProperty',
            ],
            'inaccessibleStaticProtectedProperty' => [
                'code' => '<?php
                    class A {
                        /** @var string */
                        protected static $fooFoo;
                    }

                    echo A::$fooFoo;',
                'error_message' => 'InaccessibleProperty',
            ],
            'inaccessibleStaticPrivatePropertyFromSubclass' => [
                'code' => '<?php
                    class A {
                        /** @var string */
                        private static $fooFoo;
                    }

                    class B extends A {
                        public function doFoo(): void {
                            echo A::$fooFoo;
                        }
                    }',
                'error_message' => 'InaccessibleProperty',
            ],
            'privateConstructorInheritanceNoCall' => [
                'code' => '<?php
                    class A {
                        private function __construct() { }
                    }
                    class B extends A {}
                    new B();',
                'error_message' => 'InaccessibleMethod',
            ],
            'privateConstructorInheritanceCall' => [
                'code' => '<?php
                    class A {
                        private function __construct() { }
                    }
                    class B extends A {
                        public function __construct() {
                            parent::__construct();
                        }
                    }',
                'error_message' => 'InaccessibleMethod',
            ],
            'noSelfInFunctionConstant' => [
                'code' => '<?php
                    function foo() : void {
                        echo self::SOMETHING;
                    }',
                'error_message' => 'NonStaticSelfCall',
            ],
            'noSelfInFunctionCall' => [
                'code' => '<?php
                    function foo() : void {
                        echo self::bar();
                    }',
                'error_message' => 'NonStaticSelfCall',
            ],
        ];
    }
}
