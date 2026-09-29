<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

final class AsymmetricVisibilityTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     * @psalm-pure
     */
    #[Override]
    public function providerValidCodeParse(): iterable
    {
        return [
            'privateSetWrittenInsideClass' => [
                'code' => '<?php
                    final class A {
                        public private(set) string $foo = "a";

                        public function set(string $s): void {
                            $this->foo = $s;
                        }
                    }

                    $a = new A();
                    $a->set("b");
                    echo $a->foo;',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'privateSetCompoundAndArrayWritesInsideClass' => [
                'code' => '<?php
                    final class A {
                        public private(set) string $foo = "a";
                        public private(set) int $count = 0;
                        /** @var list<int> */
                        public private(set) array $items = [];

                        public function mutate(): void {
                            $this->foo .= "b";
                            $this->count++;
                            $this->items[] = 1;
                        }
                    }

                    $a = new A();
                    $a->mutate();
                    echo $a->foo;',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'protectedSetWrittenFromChild' => [
                'code' => '<?php
                    class A {
                        public protected(set) int $foo = 1;
                    }

                    final class B extends A {
                        public function bump(): void {
                            $this->foo = 2;
                        }
                    }

                    $b = new B();
                    $b->bump();
                    echo $b->foo;',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'protectedSetWrittenFromParentOnChildInstance' => [
                'code' => '<?php
                    class A {
                        public protected(set) int $foo = 1;

                        public function set(B $b): void {
                            $b->foo = 2;
                        }
                    }

                    final class B extends A {}',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'privateSetPromotedProperty' => [
                'code' => '<?php
                    final class A {
                        public function __construct(
                            public private(set) string $foo,
                            public protected(set) int $bar = 1,
                        ) {}
                    }

                    $a = new A("x");
                    echo $a->foo;
                    echo $a->bar;',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'privateSetReadonlySetInConstructor' => [
                'code' => '<?php
                    final class A {
                        public private(set) readonly string $foo;

                        public function __construct() {
                            $this->foo = "a";
                        }
                    }

                    echo (new A())->foo;',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'explicitPublicSet' => [
                'code' => '<?php
                    final class A {
                        public public(set) string $foo = "a";
                    }

                    $a = new A();
                    $a->foo = "b";
                    echo $a->foo;',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'privateSetInTraitWrittenByUsingClass' => [
                'code' => '<?php
                    trait T {
                        public private(set) string $foo = "a";
                    }

                    final class A {
                        use T;

                        public function set(): void {
                            $this->foo = "b";
                        }
                    }

                    echo (new A())->foo;',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'overrideWithWiderSetVisibility' => [
                'code' => '<?php
                    class A {
                        public protected(set) int $foo = 1;
                    }

                    final class B extends A {
                        public int $foo = 2;
                    }

                    $b = new B();
                    $b->foo = 3;',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'unsetPrivateSetInsideClass' => [
                'code' => '<?php
                    final class A {
                        public private(set) ?string $foo = "a";

                        public function clear(): void {
                            unset($this->foo);
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'finalPropertyNotOverridden' => [
                'code' => '<?php
                    class A {
                        final public string $foo = "a";
                    }

                    final class B extends A {
                        public function get(): string {
                            return $this->foo;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'reflectionPropertySetVisibilityMethods' => [
                'code' => '<?php
                    final class A {
                        public private(set) string $foo = "a";
                    }

                    $p = new ReflectionProperty(A::class, "foo");
                    $a = $p->isPrivateSet();
                    $b = $p->isProtectedSet();
                    $c = $p->isPublicSet();',
                'assertions' => [
                    '$a' => 'bool',
                    '$b' => 'bool',
                    '$c' => 'bool',
                ],
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'staticPrivateSetWrittenInsideClassOnPhp85' => [
                'code' => '<?php
                    final class A {
                        public private(set) static ?string $model = null;

                        /** @var array<int, int> */
                        public private(set) static array $list = [];

                        public static function set(string $model): void {
                            self::$model = $model;
                            self::$list[] = 1;
                            sort(self::$list);
                            unset(self::$list[0]);
                        }
                    }

                    A::set("b");
                    echo A::$model ?? "";',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'privateStaticInTraitPassedByReference' => [
                'code' => '<?php
                    trait Registry {
                        /** @var list<int> */
                        private static array $queue = [];

                        public static function push(int $i): void {
                            self::$queue[] = $i;
                            sort(self::$queue);
                        }
                    }

                    final class C { use Registry; }
                    C::push(1);',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'staticProtectedSetWrittenFromChildOnPhp85' => [
                'code' => '<?php
                    abstract class A {
                        public protected(set) static int $count = 0;
                    }

                    final class B extends A {
                        public static function bump(): void {
                            self::$count++;
                            parent::$count += 2;
                        }
                    }

                    B::bump();
                    echo A::$count;',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
        ];
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'privateSetWrittenFromOutside' => [
                'code' => '<?php
                    final class A {
                        public private(set) string $foo = "a";
                    }

                    $a = new A();
                    $a->foo = "b";',
                'error_message' => 'InaccessibleProperty - src' . DIRECTORY_SEPARATOR . 'somefile.php:7:21 - '
                    . 'Cannot modify private(set) property A::$foo',
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'privateSetWrittenFromChild' => [
                'code' => '<?php
                    class A {
                        public private(set) string $foo = "a";
                    }

                    final class B extends A {
                        public function set(): void {
                            $this->foo = "b";
                        }
                    }',
                'error_message' => 'InaccessibleProperty - src' . DIRECTORY_SEPARATOR . 'somefile.php:8:29 - '
                    . 'Cannot modify private(set) property B::$foo from context B',
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'protectedSetWrittenFromOutside' => [
                'code' => '<?php
                    final class A {
                        public protected(set) int $foo = 1;
                    }

                    $a = new A();
                    $a->foo = 2;',
                'error_message' => 'InaccessibleProperty - src' . DIRECTORY_SEPARATOR . 'somefile.php:7:21 - '
                    . 'Cannot modify protected(set) property A::$foo',
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'protectedSetWrittenFromUnrelatedClass' => [
                'code' => '<?php
                    final class A {
                        public protected(set) int $foo = 1;
                    }

                    final class C {
                        public function set(A $a): void {
                            $a->foo = 2;
                        }
                    }',
                'error_message' => 'InaccessibleProperty - src' . DIRECTORY_SEPARATOR . 'somefile.php:8:29 - '
                    . 'Cannot modify protected(set) property A::$foo from context C',
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'privateSetCompoundAssignmentFromOutside' => [
                'code' => '<?php
                    final class A {
                        public private(set) string $foo = "a";
                    }

                    $a = new A();
                    $a->foo .= "b";',
                'error_message' => 'InaccessibleProperty',
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'privateSetIncrementFromOutside' => [
                'code' => '<?php
                    final class A {
                        public private(set) int $foo = 1;
                    }

                    $a = new A();
                    $a->foo++;',
                'error_message' => 'InaccessibleProperty',
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'privateSetArrayPushFromOutside' => [
                'code' => '<?php
                    final class A {
                        /** @var list<int> */
                        public private(set) array $foo = [];
                    }

                    $a = new A();
                    $a->foo[] = 1;',
                'error_message' => 'InaccessibleProperty',
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'privateSetUnsetFromOutside' => [
                'code' => '<?php
                    final class A {
                        public private(set) ?string $foo = "a";
                    }

                    $a = new A();
                    unset($a->foo);',
                'error_message' => 'InaccessibleProperty',
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'privateSetPromotedPropertyWrittenFromOutside' => [
                'code' => '<?php
                    final class A {
                        public function __construct(public private(set) string $foo) {}
                    }

                    $a = new A("a");
                    $a->foo = "b";',
                'error_message' => 'InaccessibleProperty',
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'privateSetReadonlyWrittenFromChildConstructor' => [
                'code' => '<?php
                    class A {
                        public private(set) readonly string $foo;

                        public function __construct() {
                            $this->foo = "a";
                        }
                    }

                    final class B extends A {
                        public function __construct() {
                            $this->foo = "b";
                        }
                    }',
                'error_message' => 'InaccessibleProperty',
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'overrideWithNarrowerSetVisibility' => [
                'code' => '<?php
                    class A {
                        public protected(set) int $foo = 1;
                    }

                    final class B extends A {
                        public private(set) int $foo = 2;
                    }',
                'error_message' => 'OverriddenPropertyAccess',
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'overrideImplicitlyFinalPrivateSetProperty' => [
                'code' => '<?php
                    class A {
                        public private(set) int $foo = 1;
                    }

                    final class B extends A {
                        public int $foo = 2;
                    }',
                'error_message' => 'OverriddenFinalProperty',
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'overrideExplicitlyFinalProperty' => [
                'code' => '<?php
                    class A {
                        final public int $foo = 1;
                    }

                    final class B extends A {
                        public int $foo = 2;
                    }',
                'error_message' => 'OverriddenFinalProperty',
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'asymmetricVisibilityRequiresType' => [
                'code' => '<?php
                    final class A {
                        public private(set) $foo;
                    }',
                'error_message' => 'ParseError',
                'ignored_issues' => ['MissingPropertyType'],
                'php_version' => '8.4',
            ],
            'asymmetricVisibilityOnStaticPropertyBeforePhp85' => [
                'code' => '<?php
                    final class A {
                        public static private(set) int $foo = 1;
                    }',
                'error_message' => 'ParseError - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:25 - '
                    . 'Asymmetric visibility on static properties is only available in PHP 8.5 and later, '
                    . 'but A::$foo uses it',
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'staticPrivateSetWrittenFromOutsideOnPhp85' => [
                'code' => '<?php
                    final class A {
                        public private(set) static ?string $model = null;
                    }

                    A::$model = "b";',
                'error_message' => 'InaccessibleProperty - src' . DIRECTORY_SEPARATOR . 'somefile.php:6:21 - '
                    . 'Cannot modify private(set) property A::$model',
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'staticProtectedSetIncrementFromOutsideOnPhp85' => [
                'code' => '<?php
                    final class A {
                        public protected(set) static int $count = 0;
                    }

                    A::$count++;',
                'error_message' => 'InaccessibleProperty',
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'staticPrivateSetArrayPushFromOutsideOnPhp85' => [
                'code' => '<?php
                    final class A {
                        /** @var list<int> */
                        public private(set) static array $list = [];
                    }

                    A::$list[] = 1;',
                'error_message' => 'InaccessibleProperty',
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'staticPrivateSetPassedByReferenceFromOutsideOnPhp85' => [
                'code' => '<?php
                    final class A {
                        /** @var list<int> */
                        public private(set) static array $list = [];
                    }

                    sort(A::$list);',
                'error_message' => 'InaccessibleProperty',
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'staticPrivateSetUnsetElementFromOutsideOnPhp85' => [
                'code' => '<?php
                    final class A {
                        /** @var array<int, int> */
                        public private(set) static array $list = [];
                    }

                    unset(A::$list[0]);',
                'error_message' => 'InaccessibleProperty',
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'setVisibilityWiderThanGetVisibility' => [
                'code' => '<?php
                    final class A {
                        protected public(set) int $foo = 1;
                    }',
                'error_message' => 'ParseError',
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'readonlyWithPublicSet' => [
                'code' => '<?php
                    final class A {
                        public public(set) readonly int $foo;

                        public function __construct() {
                            $this->foo = 1;
                        }
                    }',
                'error_message' => 'ParseError',
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'asymmetricVisibilityBeforePhp84' => [
                'code' => '<?php
                    final class A {
                        public private(set) int $foo = 1;
                    }',
                'error_message' => 'ParseError',
                'ignored_issues' => [],
                'php_version' => '8.3',
            ],
        ];
    }
}
