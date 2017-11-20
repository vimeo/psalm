<?php
namespace Psalm\Tests;

class TemplateTest extends TestCase
{
    use Traits\FileCheckerInvalidCodeParseTestTrait;
    use Traits\FileCheckerValidCodeParseTestTrait;

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'classTemplate' => [
                '<?php
                    class A {}
                    class B {}
                    class C {}
                    class D {}

                    /**
                     * @template T as object
                     */
                    class Foo {
                        /** @var string */
                        public $T;

                        /**
                         * @param string $T
                         * @template-typeof T $T
                         */
                        public function __construct(string $T) {
                            $this->T = $T;
                        }

                        /**
                         * @return T
                         */
                        public function bar() {
                            $t = $this->T;
                            return new $t();
                        }
                    }

                    $at = "A";

                    /** @var Foo<A> */
                    $afoo = new Foo($at);
                    $afoo_bar = $afoo->bar();

                    $bfoo = new Foo(B::class);
                    $bfoo_bar = $bfoo->bar();

                    $cfoo = new Foo("C");
                    $cfoo_bar = $cfoo->bar();

                    $dt = "D";
                    $dfoo = new Foo($dt);',
                'assertions' => [
                    '$afoo' => 'Foo<A>',
                    '$afoo_bar' => 'A',

                    '$bfoo' => 'Foo<B>',
                    '$bfoo_bar' => 'B',

                    '$cfoo' => 'Foo<C>',
                    '$cfoo_bar' => 'C',

                    '$dfoo' => 'Foo<mixed>',
                ],
            ],
            'classTemplateExternalClasses' => [
                '<?php
                    /**
                     * @template T as object
                     */
                    class Foo {
                        /** @var string */
                        public $T;

                        /**
                         * @param string $T
                         * @template-typeof T $T
                         */
                        public function __construct(string $T) {
                            $this->T = $T;
                        }

                        /**
                         * @return T
                         */
                        public function bar() {
                            $t = $this->T;
                            return new $t();
                        }
                    }

                    $efoo = new Foo(Exception::class)
                    $efoo_bar = $efoo->bar();

                    $ffoo = new Foo("LogicException");
                    $ffoo_bar = $ffoo->bar();',
                'assertions' => [
                    '$efoo' => 'Foo<Exception>',
                    '$efoo_bar' => 'Exception',

                    '$ffoo' => 'Foo<LogicException>',
                    '$ffoo_bar' => 'LogicException',
                ],
            ],
            'classTemplateContainer' => [
                '<?php
                    class A {}

                    /**
                     * @template T
                     */
                    class Foo {
                        /** @var T */
                        public $obj;

                        /**
                         * @param T $obj
                         */
                        public function __construct($obj) {
                            $this->obj = $obj;
                        }

                        /**
                         * @return T
                         */
                        public function bar() {
                            return $this->obj;
                        }

                        public function __toString() : string {
                            return "hello " . $this->obj;
                        }
                    }

                    $afoo = new Foo(new A());
                    $afoo_bar = $afoo->bar();',
                'assertions' => [
                    '$afoo' => 'Foo<A>',
                    '$afoo_bar' => 'A',
                ],
                'error_levels' => ['MixedOperand'],
            ],
            'phanTuple' => [
                '<?php
                    namespace Phan\Library;

                    /**
                     * An abstract tuple.
                     */
                    abstract class Tuple
                    {
                        const ARITY = 0;

                        /**
                         * @return int
                         * The arity of this tuple
                         */
                        public function arity() : int
                        {
                            return (int)static::ARITY;
                        }

                        /**
                         * @return array
                         * An array of all elements in this tuple.
                         */
                        abstract public function toArray() : array;
                    }

                    /**
                     * A tuple of 1 element.
                     *
                     * @template T0
                     * The type of element zero
                     */
                    class Tuple1 extends Tuple
                    {
                        /** @var int */
                        const ARITY = 1;

                        /** @var T0 */
                        public $_0;

                        /**
                         * @param T0 $_0
                         * The 0th element
                         */
                        public function __construct($_0) {
                            $this->_0 = $_0;
                        }

                        /**
                         * @return int
                         * The arity of this tuple
                         */
                        public function arity() : int
                        {
                            return (int)static::ARITY;
                        }

                        /**
                         * @return array
                         * An array of all elements in this tuple.
                         */
                        public function toArray() : array
                        {
                            return [
                                $this->_0,
                            ];
                        }
                    }

                    /**
                     * A tuple of 2 elements.
                     *
                     * @template T0
                     * The type of element zero
                     *
                     * @template T1
                     * The type of element one
                     *
                     * @inherits Tuple1<T0>
                     */
                    class Tuple2 extends Tuple1
                    {
                        /** @var int */
                        const ARITY = 2;

                        /** @var T1 */
                        public $_1;

                        /**
                         * @param T0 $_0
                         * The 0th element
                         *
                         * @param T1 $_1
                         * The 1st element
                         */
                        public function __construct($_0, $_1) {
                            parent::__construct($_0);
                            $this->_1 = $_1;
                        }

                        /**
                         * @return array
                         * An array of all elements in this tuple.
                         */
                        public function toArray() : array
                        {
                            return [
                                $this->_0,
                                $this->_1,
                            ];
                        }
                    }

                    $a = new Tuple2("cool", 5);

                    /** @return void */
                    function takes_int(int $i) {}

                    /** @return void */
                    function takes_string(string $s) {}

                    takes_string($a->_0);
                    takes_int($a->_1);',
            ],
            'validTemplatedType' => [
                '<?php
                    namespace FooFoo;

                    /**
                     * @template T
                     * @param T $x
                     * @return T
                     */
                    function foo($x) {
                        return $x;
                    }

                    function bar(string $a) : void { }

                    bar(foo("string"));',
            ],
            'validTemplatedStaticMethodType' => [
                '<?php
                    namespace FooFoo;

                    class A {
                        /**
                         * @template T
                         * @param T $x
                         * @return T
                         */
                        public static function foo($x) {
                            return $x;
                        }
                    }

                    function bar(string $a) : void { }

                    bar(A::foo("string"));',
            ],
            'validTemplatedInstanceMethodType' => [
                '<?php
                    namespace FooFoo;

                    class A {
                        /**
                         * @template T
                         * @param T $x
                         * @return T
                         */
                        public function foo($x) {
                            return $x;
                        }
                    }

                    function bar(string $a) : void { }

                    bar((new A())->foo("string"));',
            ],
            'genericArrayKeys' => [
                '<?php
                    /**
                     * @template T
                     *
                     * @param array<T, mixed> $arr
                     * @return array<int, T>
                     */
                    function my_array_keys($arr) {
                        return array_keys($arr);
                    }

                    $a = my_array_keys(["hello" => 5, "goodbye" => new \Exception()]);',
                'assertions' => [
                    '$a' => 'array<int, string>',
                ],
            ],
            'genericArrayReverse' => [
                '<?php
                    /**
                     * @template TKey
                     * @template TValue
                     *
                     * @param array<TKey, TValue> $arr
                     * @return array<TValue, TKey>
                     */
                    function my_array_reverse($arr) {
                        return array_reverse($arr);
                    }

                    $b = my_array_reverse(["hello" => 5, "goodbye" => 6]);',
                'assertions' => [
                    '$b' => 'array<int, string>',
                ],
            ],
            'genericArrayPop' => [
                '<?php
                    /**
                     * @template TValue
                     *
                     * @param array<mixed, TValue> $arr
                     * @return TValue
                     */
                    function my_array_pop(array &$arr) {
                        return array_pop($arr);
                    }

                    /** @var mixed */
                    $b = ["a" => 5, "c" => 6];
                    $a = my_array_pop($b);',
                'assertions' => [
                    '$a' => 'mixed',
                    '$b' => 'array<mixed, mixed>',
                ],
                'error_levels' => ['MixedAssignment', 'MixedArgument'],
            ],
        ];
    }

    /**
     * @return array
     */
    public function providerFileCheckerInvalidCodeParse()
    {
        return [
            'invalidTemplatedType' => [
                '<?php
                    namespace FooFoo;

                    /**
                     * @template T
                     * @param T $x
                     * @return T
                     */
                    function foo($x) {
                        return $x;
                    }

                    function bar(string $a) : void { }

                    bar(foo(4));',
                'error_message' => 'InvalidScalarArgument',
            ],
            'invalidTemplatedStaticMethodType' => [
                '<?php
                    namespace FooFoo;

                    class A {
                        /**
                         * @template T
                         * @param T $x
                         * @return T
                         */
                        public static function foo($x) {
                            return $x;
                        }
                    }

                    function bar(string $a) : void { }

                    bar(A::foo(4));',
                'error_message' => 'InvalidScalarArgument',
            ],
            'invalidTemplatedInstanceMethodType' => [
                '<?php
                    namespace FooFoo;

                    class A {
                        /**
                         * @template T
                         * @param T $x
                         * @return T
                         */
                        public function foo($x) {
                            return $x;
                        }
                    }

                    function bar(string $a) : void { }

                    bar((new A())->foo(4));',
                'error_message' => 'InvalidScalarArgument',
            ],
        ];
    }
}
