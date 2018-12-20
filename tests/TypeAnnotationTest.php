<?php
namespace Psalm\Tests;

use Psalm\Config;
use Psalm\Context;

class TypeAnnotationTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return array
     */
    public function providerValidCodeParse()
    {
        return [
            'typeAliasBeforeClass' => [
                '<?php
                    /**
                     * @psalm-type CoolType = A|B|null
                     */

                    class A {}
                    class B {}

                    /** @return CoolType */
                    function foo() {
                        if (rand(0, 1)) {
                            return new A();
                        }

                        if (rand(0, 1)) {
                            return new B();
                        }

                        return null;
                    }

                    /** @param CoolType $a **/
                    function bar ($a) : void { }

                    bar(foo());'
            ],
            'typeAliasBeforeFunction' => [
                '<?php
                    /**
                     * @psalm-type A_OR_B = A|B
                     * @psalm-type CoolType = A_OR_B|null
                     * @return CoolType
                     */
                    function foo() {
                        if (rand(0, 1)) {
                            return new A();
                        }

                        if (rand(0, 1)) {
                            return new B();
                        }

                        return null;
                    }

                    class A {}
                    class B {}

                    /** @param CoolType $a **/
                    function bar ($a) : void { }

                    bar(foo());'
            ],
            'typeAliasInSeparateBlockBeforeFunction' => [
                '<?php
                    /**
                     * @psalm-type CoolType = A|B|null
                     */
                    /**
                     * @return CoolType
                     */
                    function foo() {
                        if (rand(0, 1)) {
                            return new A();
                        }

                        if (rand(0, 1)) {
                            return new B();
                        }

                        return null;
                    }

                    class A {}
                    class B {}

                    /** @param CoolType $a **/
                    function bar ($a) : void { }

                    bar(foo());'
            ],
            'almostFreeStandingTypeAlias' => [
                '<?php
                    /**
                     * @psalm-type CoolType = A|B|null
                     */

                    // this breaks up the line

                    class A {}
                    class B {}

                    /** @return CoolType */
                    function foo() {
                        if (rand(0, 1)) {
                            return new A();
                        }

                        if (rand(0, 1)) {
                            return new B();
                        }

                        return null;
                    }

                    /** @param CoolType $a **/
                    function bar ($a) : void { }

                    bar(foo());'
            ],
            'typeAliasUsedTwice' => [
                '<?php
                    /** @psalm-type TA = array<int, string> */

                    class Bar {
                        public function foo() : void {
                            $bar =
                                /** @return TA */
                                function() {
                                    return ["hello"];
                            };

                            /** @var array<int, TA> */
                            $bat = [$bar(), $bar()];

                            foreach ($bat as $b) {
                                echo $b[0];
                            }
                        }
                    }

                    /**
                      * @psalm-type _A=array{elt:int}
                      * @param _A $p
                      * @return _A
                      */
                    function f($p) {
                        /** @var _A */
                        $r = $p;
                        return $r;
                    }',
            ],
            'namespacedType' => [
                '<?php
                    namespace Foo {
                        class A {
                            /**
                             * @psalm-type self::TBaz = A|\Bar\B
                             */
                            public function foo() : void {}
                        }
                    }

                    namespace Bar {
                        class B {
                            /**
                             * @param \Foo\A::TBaz $a
                             */
                            public function tender($a) : void {
                                $a->foo();
                            }

                            public function foo() : void {}
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
            'invalidTypeAlias' => [
                '<?php
                    /**
                     * @psalm-type CoolType = A|B>
                     */

                    class A {}',
                'error_message' => 'InvalidDocblock',
            ],
            'typeAliasInObjectLike' => [
                '<?php
                    /**
                     * @psalm-type aType null|"a"|"b"|"c"|"d"
                     */

                    /** @psalm-return array{0:bool,1:aType} */
                    function f(): array {
                        return [(bool)rand(0,1), rand(0,1) ? "z" : null];
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
        ];
    }
}
