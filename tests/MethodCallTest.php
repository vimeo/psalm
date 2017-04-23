<?php
namespace Psalm\Tests;

class MethodCallTest extends TestCase
{
    use Traits\FileCheckerInvalidCodeParseTestTrait;
    use Traits\FileCheckerValidCodeParseTestTrait;

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'parent-static-call' => [
                '<?php
                    class A {
                        /** @return void */
                        public static function foo(){}
                    }
            
                    class B extends A {
                        /** @return void */
                        public static function bar(){
                            parent::foo();
                        }
                    }'
            ],
            'non-static-invocation' => [
                '<?php
                    class Foo {
                        public static function barBar() : void {}
                    }
            
                    (new Foo())->barBar();'
            ],
            'static-invocation' => [
                '<?php
                    class A {
                        public static function fooFoo() : void {}
                    }
            
                    class B extends A {
            
                    }
            
                    B::fooFoo();'
            ]
        ];
    }

    /**
     * @return array
     */
    public function providerFileCheckerInvalidCodeParse()
    {
        return [
            'static-invocation' => [
                '<?php
                    class Foo {
                        public function barBar() : void {}
                    }
            
                    Foo::barBar();',
                'error_message' => 'InvalidStaticInvocation'
            ],
            'parent-static-call' => [
                '<?php
                    class A {
                        /** @return void */
                        public function foo(){}
                    }
            
                    class B extends A {
                        /** @return void */
                        public static function bar(){
                            parent::foo();
                        }
                    }',
                'error_message' => 'InvalidStaticInvocation'
            ],
            'mixed-method-call' => [
                '<?php
                    class Foo {
                        public static function barBar() : void {}
                    }
            
                    /** @var mixed */
                    $a = (new Foo());
            
                    $a->barBar();',
                'error_message' => 'MixedMethodCall',
                'error_levels' => [
                    'MissingPropertyType',
                    'MixedAssignment'
                ]
            ],
            'self-non-static-invocation' => [
                '<?php
                    class A {
                        public function fooFoo() : void {}
            
                        public function barBar() : void {
                            self::fooFoo();
                        }
                    }',
                'error_message' => 'NonStaticSelfCall'
            ],
            'no-parent' => [
                '<?php
                    class Foo {
                        public function barBar() : void {
                            parent::barBar();
                        }
                    }',
                'error_message' => 'ParentNotFound'
            ],
            'coerced-class' => [
                '<?php
                    class NullableClass {
                    }
            
                    class NullableBug {
                        /**
                         * @param string $className
                         * @return object|null
                         */
                        public static function mock($className) {
                            if (!$className) { return null; }
                            return new $className();
                        }
            
                        /**
                         * @return NullableClass
                         */
                        public function returns_nullable_class() {
                            return self::mock("NullableClass");
                        }
                    }',
                'error_message' => 'MoreSpecificReturnType',
                'error_levels' => ['MixedInferredReturnType']
            ]
        ];
    }
}
