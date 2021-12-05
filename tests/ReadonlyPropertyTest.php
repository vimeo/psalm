<?php
namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

class ReadonlyPropertyTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'docblockReadonlyPropertySetInConstructor' => [
                '<?php
                    class A {
                        /**
                         * @readonly
                         */
                        public string $bar;

                        public function __construct() {
                            $this->bar = "hello";
                        }
                    }

                    echo (new A)->bar;'
            ],
            'readonlyPropertySetInConstructor' => [
                '<?php
                    class A {
                        public readonly string $bar;

                        public function __construct() {
                            $this->bar = "hello";
                        }
                    }

                    echo (new A)->bar;',
                [],
                [],
                '8.1'
            ],
            'docblockReadonlyWithPrivateMutationsAllowedPropertySetInAnotherMethod' => [
                '<?php
                    class A {
                        /**
                         * @readonly
                         * @psalm-allow-private-mutation
                         */
                        public ?string $bar = null;

                        public function setBar(string $s) : void {
                            $this->bar = $s;
                        }
                    }

                    echo (new A)->bar;'
            ],
            'readonlyPublicPropertySetInAnotherMEthod' => [
                '<?php
                    class A {
                        /**
                         * @psalm-readonly-allow-private-mutation
                         */
                        public ?string $bar = null;

                        public function setBar(string $s) : void {
                            $this->bar = $s;
                        }
                    }

                    echo (new A)->bar;'
            ],
            'readonlyPropertySetChildClass' => [
                '<?php
                    abstract class A {
                        /**
                         * @readonly
                         */
                        public string $bar;
                    }

                    class B extends A {
                        public function __construct() {
                            $this->bar = "hello";
                        }
                    }

                    echo (new B)->bar;'
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,1?:string[],2?:bool,3?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'readonlyPropertySetInConstructorAndAlsoAnotherMethodInsideClass' => [
                '<?php
                    class A {
                        /**
                         * @readonly
                         */
                        public string $bar;

                        public function __construct() {
                            $this->bar = "hello";
                        }

                        public function setBar() : void {
                            $this->bar = "goodbye";
                        }
                    }',
                'error_message' => 'InaccessibleProperty',
            ],
            'readonlyPropertySetInConstructorAndAlsoAnotherMethodInSublass' => [
                '<?php
                    class A {
                        /**
                         * @readonly
                         */
                        public string $bar;

                        public function __construct() {
                            $this->bar = "hello";
                        }
                    }

                    class B extends A {
                        public function setBar() : void {
                            $this->bar = "hello";
                        }
                    }',
                'error_message' => 'InaccessibleProperty',
            ],
            'docblockReadonlyPropertySetInConstructorAndAlsoOutsideClass' => [
                '<?php
                    class A {
                        /**
                         * @readonly
                         */
                        public string $bar;

                        public function __construct() {
                            $this->bar = "hello";
                        }
                    }

                    $a = new A();
                    $a->bar = "goodbye";',
                'error_message' => 'InaccessibleProperty - src' . DIRECTORY_SEPARATOR . 'somefile.php:14:21',
            ],
            'readonlyPropertySetInConstructorAndAlsoOutsideClass' => [
                '<?php
                    class A {
                        public readonly string $bar;

                        public function __construct() {
                            $this->bar = "hello";
                        }
                    }

                    $a = new A();
                    $a->bar = "goodbye";',
                'error_message' => 'InaccessibleProperty - src' . DIRECTORY_SEPARATOR . 'somefile.php:11:21',
                [],
                false,
                '8.1',
            ],
            'readonlyPropertySetInConstructorAndAlsoOutsideClassWithAllowPrivate' => [
                '<?php
                    class A {
                        /**
                         * @readonly
                         * @psalm-allow-private-mutation
                         */
                        public string $bar;

                        public function __construct() {
                            $this->bar = "hello";
                        }

                        public function setAgain() : void {
                            $this->bar = "hello";
                        }
                    }

                    $a = new A();
                    $a->bar = "goodbye";',
                'error_message' => 'InaccessibleProperty - src' . DIRECTORY_SEPARATOR . 'somefile.php:19:21',
            ],
            'readonlyPublicPropertySetInConstructorAndAlsoOutsideClass' => [
                '<?php
                    class A {
                        /**
                         * @psalm-readonly-allow-private-mutation
                         */
                        public string $bar;

                        public function __construct() {
                            $this->bar = "hello";
                        }

                        public function setAgain() : void {
                            $this->bar = "hello";
                        }
                    }

                    $a = new A();
                    $a->bar = "goodbye";',
                'error_message' => 'InaccessibleProperty - src' . DIRECTORY_SEPARATOR . 'somefile.php:18:21',
            ],
            'readonlyPropertyAssignOperator' => [
                '<?php
                    class Test {
                        /** @readonly */
                        public int $prop;

                        public function __construct(int $prop) {
                            // Legal initialization.
                            $this->prop = $prop;
                        }
                    }

                    $test = new Test(5);

                    $test->prop += 1;',
                'error_message' => 'InaccessibleProperty'
            ],
            'readonlyPropertyWithDefault' => [
                '<?php
                    class A {
                        public readonly string $s = "a";
                    }',
                'error_message' => 'InvalidPropertyAssignment',
                [],
                false,
                '8.1',
            ],
            'readonlyPromotedPropertyAssignOperator' => [
                '<?php
                    class A {
                        public function __construct(public readonly string $bar) {
                        }
                    }

                    $a = new A("hello");
                    $a->bar = "goodbye";',
                'error_message' => 'InaccessibleProperty - src' . DIRECTORY_SEPARATOR . 'somefile.php:8:21',
                [],
                false,
                '8.1',
            ],
            'readonlyPromotedPropertyAccess' => [
                '<?php
                    class A {
                        public function __construct(private readonly string $bar) {
                        }
                    }

                    $a = new A("hello");
                    $b = $a->bar;',
                'error_message' => 'InaccessibleProperty - src' . DIRECTORY_SEPARATOR . 'somefile.php:8:26',
                [],
                false,
                '8.1',
            ],
        ];
    }
}
