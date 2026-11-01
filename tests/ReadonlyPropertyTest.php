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
            'incrementMutableNumericStringProperty' => [
                'code' => '<?php
                    final class A {
                        /** @var numeric-string */
                        public string $n = "5";

                        public function next(): void {
                            $this->n++;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'foreachByRefOverReadonlyObjectAndMutableArrayProperty' => [
                'code' => '<?php
                    final class A {
                        /** @var list<int> */
                        public array $items = [1];

                        /** @var ArrayObject<int, int> */
                        public readonly ArrayObject $bag;

                        public function __construct() {
                            $this->bag = new ArrayObject([1]);
                        }

                        public function touch(): void {
                            foreach ($this->bag as &$item) {
                                $item = 1;
                            }

                            foreach ($this->items as &$value) {
                                $value = 1;
                            }
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'writesThroughUndefinedDocblockClassReceiver' => [
                'code' => '<?php
                    final class H {
                        /** @var Unknown */
                        public $u;

                        /** @param Unknown $u */
                        public function __construct($u) {
                            $this->u = $u;
                        }
                    }

                    function viaPropertyType(H $h): void {
                        foreach ($h->u->arr as &$item) {
                            $item = 1;
                        }
                    }

                    function viaVarAnnotation(): void {
                        /** @var Unknown $u */
                        $u = $GLOBALS["u"];

                        foreach ($u->arr as &$item) {
                            $item = 1;
                        }

                        if (is_string($u->s)) {
                            $u->s++;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['UndefinedDocblockClass', 'MixedAssignment', 'StringIncrement'],
                'php_version' => '8.1',
            ],
            'foreachByRefOverReadonlyArrayObjectPropertyNarrowedFromUnion' => [
                'code' => '<?php
                    final class A {
                        /** @var array<int, int>|ArrayObject<int, int> */
                        public readonly array|ArrayObject $bag;

                        public function __construct() {
                            $this->bag = new ArrayObject([1]);
                        }

                        public function touch(): void {
                            if ($this->bag instanceof ArrayObject) {
                                foreach ($this->bag as &$item) {
                                    $item = 1;
                                }
                            }
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'incrementAndForeachByRefOnPrivateMutationAllowedReadonlyPropertyWithCapabilities' => [
                'code' => '<?php
                    final class A {
                        /**
                         * @var numeric-string
                         * @psalm-readonly-allow-private-mutation
                         */
                        public string $n = "5";

                        /**
                         * @var list<int>
                         * @psalm-readonly-allow-private-mutation
                         */
                        public array $items = [1];

                        /** @psalm-capabilities read-props|write-this-props|write-refs */
                        public function touch(): void {
                            $this->n++;

                            foreach ($this->items as &$value) {
                                $value = 1;
                            }
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
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
            'readonlyStringPropertyIncrement' => [
                'code' => '<?php
                    final class A {
                        public function __construct(public readonly string $s) {}

                        public function next(): void {
                            $this->s++;
                        }
                    }',
                'error_message' => 'InaccessibleProperty - src' . DIRECTORY_SEPARATOR . 'somefile.php:6:29 - A::$s is marked readonly',
                'error_levels' => ['StringIncrement'],
                'php_version' => '8.1',
            ],
            'readonlyStringPropertyIncrementThroughTemplateReceiver' => [
                'code' => '<?php
                    final class A {
                        public function __construct(public readonly string $s) {}
                    }

                    /**
                     * @template T of A
                     * @param T $t
                     */
                    function bump(A $t): void {
                        $t->s++;
                    }',
                'error_message' => 'InaccessibleProperty - src' . DIRECTORY_SEPARATOR . 'somefile.php:11:25 - A::$s is marked readonly',
                'error_levels' => ['StringIncrement'],
                'php_version' => '8.1',
            ],
            'readonlyIntOrStringPropertyPreIncrement' => [
                'code' => '<?php
                    final class A {
                        public function __construct(public readonly int|string $v) {}

                        public function next(): void {
                            ++$this->v;
                        }
                    }',
                'error_message' => 'InaccessibleProperty - src' . DIRECTORY_SEPARATOR . 'somefile.php:6:31 - A::$v is marked readonly',
                'error_levels' => ['StringIncrement'],
                'php_version' => '8.1',
            ],
            'readonlyPropertyForeachByRef' => [
                'code' => '<?php
                    final class A {
                        /** @var list<int> */
                        public readonly array $items;

                        public function __construct() {
                            $this->items = [1];
                        }

                        public function touch(): void {
                            foreach ($this->items as &$item) {
                                $item = 1;
                            }
                        }
                    }',
                'error_message' => 'InaccessibleProperty - src' . DIRECTORY_SEPARATOR . 'somefile.php:11:38 - A::$items is marked readonly',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'readonlyPropertyForeachByRefOutsideClass' => [
                'code' => '<?php
                    final class A {
                        /** @var list<int> */
                        public readonly array $items;

                        public function __construct() {
                            $this->items = [1];
                        }
                    }

                    function bump(A $a): void {
                        foreach ($a->items as &$item) {
                            $item = 1;
                        }
                    }',
                'error_message' => 'InaccessibleProperty - src' . DIRECTORY_SEPARATOR . 'somefile.php:12:34 - A::$items is marked readonly',
                'error_levels' => [],
                'php_version' => '8.1',
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
        ];
    }
}
