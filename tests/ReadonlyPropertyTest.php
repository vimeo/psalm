<?php

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

class ReadonlyPropertyTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        return [
            'docblockReadonlyPropertySetInConstructor' => [
                'code' => '<?php
                    class A {
                        /**
                         * @readonly
                         */
                        public string $bar;

                        public function __construct() {
                            $this->bar = "hello";
                        }
                    }

                    echo (new A)->bar;',
            ],
            'readonlyPropertySetInConstructor' => [
                'code' => '<?php
                    class A {
                        public readonly string $bar;

                        public function __construct() {
                            $this->bar = "hello";
                        }
                    }

                    echo (new A)->bar;',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'docblockReadonlyWithPrivateMutationsAllowedPropertySetInAnotherMethod' => [
                'code' => '<?php
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

                    echo (new A)->bar;',
            ],
            'readonlyPublicPropertySetInAnotherMethod' => [
                'code' => '<?php
                    class A {
                        /**
                         * @psalm-readonly-allow-private-mutation
                         */
                        public ?string $bar = null;

                        public function setBar(string $s) : void {
                            $this->bar = $s;
                        }
                    }

                    echo (new A)->bar;',
            ],
            'docblockReadonlyWithPrivateMutationsAllowedConstructorPropertySetInAnotherMethod' => [
                'code' => '<?php
                    class A {
                        public function __construct(
                            /**
                             * @readonly
                             * @psalm-allow-private-mutation
                             */
                            public ?string $bar = null,
                        ) {}

                        public function setBar(string $s) : void {
                            $this->bar = $s;
                        }
                    }

                    echo (new A)->bar;',
            ],
            'readonlyPublicConstructorPropertySetInAnotherMethod' => [
                'code' => '<?php
                    class A {
                        public function __construct(
                            /**
                             * @psalm-readonly-allow-private-mutation
                             */
                            public ?string $bar = null,
                        ) {}

                        public function setBar(string $s) : void {
                            $this->bar = $s;
                        }
                    }

                    echo (new A)->bar;',
            ],
            'readonlyPropertySetChildClass' => [
                'code' => '<?php
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

                    echo (new B)->bar;',
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'readonlyPropertySetInConstructorAndAlsoAnotherMethodInsideClass' => [
                'code' => '<?php
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
            'readonlyPropertySetInConstructorAndAlsoAnotherMethodInSubclass' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    class A {
                        public readonly string $bar;

                        public function __construct() {
                            $this->bar = "hello";
                        }
                    }

                    $a = new A();
                    $a->bar = "goodbye";',
                'error_message' => 'InaccessibleProperty - src' . DIRECTORY_SEPARATOR . 'somefile.php:11:21',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'readonlyPropertySetInConstructorAndAlsoOutsideClassWithAllowPrivate' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'error_message' => 'InaccessibleProperty',
            ],
            'readonlyPropertyWithDefault' => [
                'code' => '<?php
                    class A {
                        public readonly string $s = "a";
                    }',
                'error_message' => 'InvalidPropertyAssignment',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'readonlyPromotedPropertyAssignOperator' => [
                'code' => '<?php
                    class A {
                        public function __construct(public readonly string $bar) {
                        }
                    }

                    $a = new A("hello");
                    $a->bar = "goodbye";',
                'error_message' => 'InaccessibleProperty - src' . DIRECTORY_SEPARATOR . 'somefile.php:8:21',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'readonlyPromotedPropertyAccess' => [
                'code' => '<?php
                    class A {
                        public function __construct(private readonly string $bar) {
                        }
                    }

                    $a = new A("hello");
                    $b = $a->bar;',
                'error_message' => 'InaccessibleProperty - src' . DIRECTORY_SEPARATOR . 'somefile.php:8:26',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'readonlyPhpDocPromotedPropertyAssignOperator' => [
                'code' => '<?php

                    final class A
                    {
                        public function __construct(
                            /**
                             * @psalm-readonly
                             */
                            private string $string,
                        ) {
                        }

                        private function mutateString(): void
                        {
                            $this->string = "";
                        }
                    }',
                'error_message' => 'InaccessibleProperty',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
        ];
    }
}
