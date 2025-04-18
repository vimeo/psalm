<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class PropertiesOfTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        return [
            'propertiesOfIntersection' => [
                'code' => '<?php

                interface i {
                }
                class b {
                    public int $a = 123;
                }


                /**
                 * @psalm-suppress InvalidReturnType
                 * @return properties-of<$a>
                 */
                function test1($a) {}
                /**
                 * @psalm-suppress InvalidReturnType
                 * @return properties-of<i&b>
                 */
                function test2() {}
                /**
                 * @psalm-suppress InvalidReturnType
                 * @return properties-of<b&i>
                 */
                function test3() {}

                /** @var i $i */
                assert($i instanceof b);
                $result1 = test1($i);
                $result2 = test2();
                $result3 = test3();
                ',
                'assertions' => [
                    '$result1===' => 'array{a: int, ...<string, mixed>}',
                    '$result2===' => 'array{a: int, ...<string, mixed>}',
                    '$result3===' => 'array{a: int, ...<string, mixed>}',
                ],
            ],
            'publicPropertiesOf' => [
                'code' => '<?php
                    class A {
                        /** @var bool */
                        public $foo = false;
                        /** @var string */
                        private $bar = "";
                        /** @var int */
                        protected $adams = 42;
                    }

                    /** @return public-properties-of<A> */
                    function returnPropertyOfA() {
                        return ["foo" => true];
                    }
                ',
            ],
            'protectedPropertiesOf' => [
                'code' => '<?php
                    class A {
                        /** @var bool */
                        public $foo = false;
                        /** @var string */
                        private $bar = "";
                        /** @var int */
                        protected $adams = 42;
                    }

                    /** @return protected-properties-of<A> */
                    function returnPropertyOfA() {
                        return ["adams" => 42];
                    }
                ',
            ],
            'privatePropertiesOf' => [
                'code' => '<?php
                    class A {
                        /** @var bool */
                        public $foo = false;
                        /** @var string */
                        private $bar = "";
                        /** @var int */
                        protected $adams = 42;
                    }

                    /** @return private-properties-of<A> */
                    function returnPropertyOfA() {
                        return ["bar" => "foo"];
                    }
                ',
            ],
            'allPropertiesOf' => [
                'code' => '<?php
                    class A {
                        /** @var bool */
                        public $foo = false;
                        /** @var string */
                        private $bar = "";
                        /** @var int */
                        protected $adams = 42;
                    }

                    /** @return properties-of<A> */
                    function returnPropertyOfA(int $visibility) {
                        return [
                            "foo" => true,
                            "bar" => "foo",
                            "adams" => 1
                        ];
                    }
                ',
            ],
            'finalPropertiesOf' => [
                'code' => '<?php
                    class A {
                        /** @var bool */
                        public $foo = false;
                    }

                    /** @return properties-of<A> */
                    function returnPropertyOfA() {
                        return ["foo" => true];
                    }
                ',
            ],
            'allPropertiesOfContainsNoStatic' => [
                'code' => '<?php
                    class A {
                        /** @var bool */
                        public static $imStatic = true;

                        /** @var bool */
                        public $foo = false;
                        /** @var string */
                        private $bar = "";
                        /** @var int */
                        protected $adams = 42;
                    }

                    /** @return properties-of<A> */
                    function returnPropertyOfA(int $visibility) {
                        return [
                            "foo" => true,
                            "bar" => "foo",
                            "adams" => 1
                        ];
                    }
                ',
            ],
            'usePropertiesOfSelfAsArrayKey' => [
                'code' => '<?php
                    class A {
                        /** @var int */
                        public $a = 1;
                        /** @var int */
                        public $b = 2;

                        /** @return properties-of<self> */
                        public function asArray() {
                            return [
                                "a" => $this->a,
                                "b" => $this->b
                            ];
                        }
                    }
                ',
            ],
            'usePropertiesOfStaticAsArrayKey' => [
                'code' => '<?php
                    class A {
                        /** @var int */
                        public $a = 1;
                        /** @var int */
                        public $b = 2;

                        /** @return properties-of<static> */
                        public function asArray() {
                            return [
                                "a" => $this->a,
                                "b" => $this->b
                            ];
                        }
                    }

                    class B extends A {
                        /** @var int */
                        public $c = 3;

                        public function asArray() {
                            return [
                                "a" => $this->a,
                                "b" => $this->b,
                                "c" => $this->c,
                            ];
                        }
                    }
                ',
            ],
            'propertiesOfMultipleInheritanceStaticAsArrayKey' => [
                'code' => '<?php
                    class A {
                        /** @var int */
                        public $a = 1;
                        /** @var int */
                        public $b = 2;

                        /** @return properties-of<static> */
                        public function asArray() {
                            return [
                                "a" => $this->a,
                                "b" => $this->b
                            ];
                        }
                    }

                    class B extends A {
                        /** @var int */
                        public $c = 3;
                    }

                    class C extends B {
                        /** @var int */
                        public $d = 4;

                        public function asArray() {
                            return [
                                "a" => $this->a,
                                "b" => $this->b,
                                "c" => $this->c,
                                "d" => $this->d,
                            ];
                        }
                    }
                ',
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'onlyOneTemplateParam' => [
                'code' => '<?php
                    class A {}
                    class B {}

                    /** @var properties-of<A, B> */
                    $test = "foobar";
                ',
                'error_message' => 'InvalidDocblock',
            ],
            'propertiesOfPicksNoStatic' => [
                'code' => '<?php
                    class A {
                        /** @var mixed */
                        public static $foo;
                    }

                    /** @return properties-of<A> */
                    function returnPropertyOfA() {
                        return ["foo" => true];
                    }
                ',
                'error_message' => 'InvalidReturnStatement',
            ],
            'publicPropertiesOfPicksNoPrivate' => [
                'code' => '<?php
                    class A {
                        /** @var mixed */
                        public $foo;
                        /** @var mixed */
                        private $bar;
                        /** @var mixed */
                        protected $adams;
                    }

                    /** @return public-properties-of<A> */
                    function returnPropertyOfA() {
                        return ["bar" => true];
                    }
                ',
                'error_message' => 'InvalidReturnStatement',
            ],
            'publicPropertiesOfPicksNoProtected' => [
                'code' => '<?php
                    class A {
                        /** @var mixed */
                        public $foo;
                        /** @var mixed */
                        private $bar;
                        /** @var mixed */
                        protected $adams;
                    }

                    /** @return public-properties-of<A> */
                    function returnPropertyOfA() {
                        return ["adams" => true];
                    }
                ',
                'error_message' => 'InvalidReturnStatement',
            ],
            'protectedPropertiesOfPicksNoPublic' => [
                'code' => '<?php
                    class A {
                        /** @var mixed */
                        public $foo;
                        /** @var mixed */
                        private $bar;
                        /** @var mixed */
                        protected $adams;
                    }

                    /** @return protected-properties-of<A> */
                    function returnPropertyOfA() {
                        return ["foo" => true];
                    }
                ',
                'error_message' => 'InvalidReturnStatement',
            ],
            'protectedPropertiesOfPicksNoPrivate' => [
                'code' => '<?php
                    class A {
                        /** @var mixed */
                        public $foo;
                        /** @var mixed */
                        private $bar;
                        /** @var mixed */
                        protected $adams;
                    }

                    /** @return protected-properties-of<A> */
                    function returnPropertyOfA() {
                        return ["bar" => true];
                    }
                ',
                'error_message' => 'InvalidReturnStatement',
            ],
            'privatePropertiesOfPicksNoPublic' => [
                'code' => '<?php
                    class A {
                        /** @var mixed */
                        public $foo;
                        /** @var mixed */
                        private $bar;
                        /** @var mixed */
                        protected $adams;
                    }

                    /** @return private-properties-of<A> */
                    function returnPropertyOfA() {
                        return ["foo" => true];
                    }
                ',
                'error_message' => 'InvalidReturnStatement',
            ],
            'privatePropertiesOfPicksNoProtected' => [
                'code' => '<?php
                    class A {
                        /** @var mixed */
                        public $foo;
                        /** @var mixed */
                        private $bar;
                        /** @var mixed */
                        protected $adams;
                    }

                    /** @return private-properties-of<A> */
                    function returnPropertyOfA() {
                        return ["adams" => true];
                    }
                ',
                'error_message' => 'InvalidReturnStatement',
            ],
            'finalPropertiesOfInexact' => [
                'code' => '<?php
                    final class A {
                        /** @var bool */
                        public $foo = false;
                        /** @var string */
                        private $bar = "";
                        /** @var int */
                        protected $adams = 42;
                    }

                    /** @return properties-of<A> */
                    function returnPropertyOfA() {
                        return ["foo" => true];
                    }
                ',
                'error_message' => 'InvalidReturnStatement',
            ],
        ];
    }
}
