<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

final class ReadonlyPropertyTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
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
            'readonlyPropertyWithDefaultOnPhp86' => [
                'code' => '<?php
                    class A {
                        public readonly string $s = "a";
                    }

                    echo (new A)->s;',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'readonlyPropertyWithDefaultSetInCloneOnPhp86' => [
                'code' => '<?php
                    class A {
                        public readonly string $s = "a";

                        public function __clone() {
                            $this->s = "b";
                        }
                    }

                    echo (clone new A)->s;',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'docblockReadonlyPropertyWithDefaultSetInConstructorOnPhp86' => [
                'code' => '<?php
                    class A {
                        /**
                         * @readonly
                         */
                        public string $s = "a";

                        public function __construct() {
                            $this->s = "b";
                        }
                    }

                    echo (new A)->s;',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'computedNameWritesOnMutableProperty' => [
                'code' => '<?php
                    function change(int &$i): void {
                        $i = 2;
                    }

                    final class A {
                        public array $arr = [];
                        public int $n = 1;

                        public function run(): void {
                            $this->{"arr"}[] = 1;
                            change($this->{"n"});
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'computedNameArrayPushOnInterfacePropertyWithSetHook' => [
                'code' => '<?php
                    interface I {
                        public array $items { get; set; }
                    }

                    final class Impl implements I {
                        public array $items = [];
                    }

                    function f(I $i): void {
                        if ($i->items === []) {
                            $i->{"items"}[] = 1;
                        }
                    }

                    f(new Impl());',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.4',
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
                'error_levels' => [],
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
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'readonlyPropertyWithDefaultOnPhp85' => [
                'code' => '<?php
                    class A {
                        public readonly string $s = "a";
                    }',
                'error_message' => 'InvalidPropertyAssignment',
                'error_levels' => [],
                'php_version' => '8.5',
            ],
            'staticReadonlyPropertyWithDefaultOnPhp86' => [
                'code' => '<?php
                    class A {
                        public static readonly int $x = 1;
                    }',
                'error_message' => 'InvalidPropertyAssignment - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:57'
                    . ' - Readonly property A::$x cannot have a default',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'readonlyPropertyWithDefaultSetInConstructorOnPhp86' => [
                'code' => '<?php
                    class A {
                        public readonly string $s = "a";

                        public function __construct() {
                            $this->s = "b";
                        }
                    }',
                'error_message' => 'InaccessibleProperty - src' . DIRECTORY_SEPARATOR . 'somefile.php:6:29'
                    . ' - A::$s is readonly and already initialised by its default',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'readonlyPropertyWithDefaultSetInMethodOnPhp86' => [
                'code' => '<?php
                    class A {
                        public readonly string $s = "a";

                        public function set(): void {
                            $this->s = "b";
                        }
                    }',
                'error_message' => 'InaccessibleProperty - src' . DIRECTORY_SEPARATOR . 'somefile.php:6:29',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'readonlyPropertyWithDefaultSetInChildConstructorOnPhp86' => [
                'code' => '<?php
                    class A {
                        public readonly string $s = "a";
                    }

                    class B extends A {
                        public function __construct() {
                            $this->s = "b";
                        }
                    }',
                'error_message' => 'InaccessibleProperty - src' . DIRECTORY_SEPARATOR . 'somefile.php:8:29'
                    . ' - B::$s is readonly and already initialised by its default',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'readonlyClassPropertyWithDefaultSetInConstructorOnPhp86' => [
                'code' => '<?php
                    readonly class A {
                        public string $s = "a";

                        public function __construct() {
                            $this->s = "b";
                        }
                    }',
                'error_message' => 'InaccessibleProperty - src' . DIRECTORY_SEPARATOR . 'somefile.php:6:29',
                'error_levels' => [],
                'php_version' => '8.6',
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
                'error_levels' => [],
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
                'error_levels' => [],
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
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'readonlyPropertyComputedNameArrayPush' => [
                'code' => '<?php
                    final class A {
                        public function __construct(public readonly array $arr) {}

                        public function add(): void {
                            $this->{"arr"}[] = 1;
                        }
                    }',
                'error_message' => 'InaccessibleProperty - src' . DIRECTORY_SEPARATOR . 'somefile.php:6:29 - A::$arr is marked readonly',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'readonlyPropertyComputedNameByRefArgument' => [
                'code' => '<?php
                    function change(int &$i): void {
                        $i = 2;
                    }

                    final class A {
                        public function __construct(public readonly int $x) {}

                        public function run(): void {
                            change($this->{"x"});
                        }
                    }',
                'error_message' => 'InaccessibleProperty - src' . DIRECTORY_SEPARATOR . 'somefile.php:10:36 - A::$x is marked readonly',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
        ];
    }
}
