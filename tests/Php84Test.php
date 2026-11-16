<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

final class Php84Test extends TestCase
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
            'initializeLazyObject' => [
                'code' => '<?php
                    class Foo {}
                    $reflectionClass = new ReflectionClass(Foo::class);
                    $lazyProxy = $reflectionClass->newLazyProxy(fn() => new Foo);
                    $realInstance = $reflectionClass->initializeLazyObject($lazyProxy);',
                'assertions' => [
                    '$realInstance' => 'Foo',
                ],
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'markLazyObjectAsInitialized' => [
                'code' => '<?php
                    class Foo {}
                    $reflectionClass = new ReflectionClass(Foo::class);
                    $lazyProxy = $reflectionClass->newLazyProxy(fn() => new Foo);
                    $lazyProxyReturned = $reflectionClass->markLazyObjectAsInitialized($lazyProxy);',
                'assertions' => [
                    '$lazyProxyReturned' => 'Foo',
                ],
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'newLazyGhost' => [
                'code' => '<?php
                    class Foo {}
                    $reflectionClass = new ReflectionClass(Foo::class);
                    $lazyGhost = $reflectionClass->newLazyGhost(function (Foo $foo) {});',
                'assertions' => [
                    '$lazyGhost' => 'Foo',
                ],
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'newLazyProxy' => [
                'code' => '<?php
                    class Foo {}
                    $reflectionClass = new ReflectionClass(Foo::class);
                    $lazyProxy = $reflectionClass->newLazyProxy(fn() => new Foo);',
                'assertions' => [
                    '$lazyProxy' => 'Foo',
                ],
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'propertyHook' => [
                'code' => '<?php
                    class Foo {
                        public int|null $test = null{
                            get {
                                return $this->test;
                            }
                            set(int $value) {
                                $this->test = $value;
                            }
                        }
                    }
                    $property_hook = new Foo();
                    $property_hook->test = 5;
                    ',
                'assertions' => [
                    '$property_hook->test' => 'int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'propertyHookBodies' => [
                'code' => '<?php
                    final class Foo {
                        private string $first = "a";
                        public string $name {
                            get => $this->name;
                            set { $this->name = $this->normalize($value); }
                        }
                        public string $lower {
                            set => strtolower($value);
                        }
                        public string $wide {
                            set(string|int $value) { $this->wide = (string) $value; }
                        }
                        public string $full {
                            get { return $this->first . $this->name; }
                        }
                        /** @var list<int> */
                        public array $list = [] {
                            &get => $this->list;
                        }
                        public string $ignored {
                            set(string $value) { }
                        }
                        public function __construct() {
                            $this->name = "a";
                            $this->lower = "b";
                            $this->wide = "c";
                        }
                        private function normalize(string $s): string { return trim($s); }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'propertyHookOfTemplatedClass' => [
                'code' => '<?php
                    /** @template T */
                    final class Box {
                        /** @var T */
                        public mixed $value {
                            get => $this->value;
                            set { $this->value = $value; }
                        }
                        /** @param T $value */
                        public function __construct(mixed $value) { $this->value = $value; }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'propertyHookCallsParentHook' => [
                'code' => '<?php
                    class Foo {
                        public string $name {
                            get => "a";
                            set { $this->name = $value; }
                        }
                        public function __construct() { $this->name = "a"; }
                    }
                    final class Bar extends Foo {
                        public string $name {
                            get => strtoupper(parent::$name::get());
                            set { parent::$name::set(trim($value)); }
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'propertyHookBodySuppressedOnProperty' => [
                'code' => '<?php
                    final class Foo {
                        /** @psalm-suppress UndefinedFunction, MixedReturnStatement */
                        public string $name {
                            get => undefined_fn();
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'abstractAndInterfacePropertyHooks' => [
                'code' => '<?php
                    interface HasName {
                        public string $name { get; }
                    }
                    abstract class Foo implements HasName {
                        abstract public string $name { get; }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'promotedPropertyHookBodies' => [
                'code' => '<?php
                    final class Foo {
                        public function __construct(
                            public string $name {
                                get => strtoupper($this->name);
                                set => strtolower($value);
                            },
                        ) {}
                    }',
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
            'getLazyInitializerWithBadType' => [
                'code' => '<?php
                    class Foo {}
                    class Bar {}
                    $reflectionClass = new ReflectionClass(Foo::class);
                    $reflectionClass->getLazyInitializer(new Bar);',
                'error_message' => 'IncompatibleTypeParameters - src' . DIRECTORY_SEPARATOR
                    . 'somefile.php:5:58 - Type Bar should be a subtype of Foo',
                'error_levels' => [],
                'php_version' => '8.4',
            ],
            'initializeLazyObjectWithBadType' => [
                'code' => '<?php
                    class Foo {}
                    class Bar {}
                    $reflectionClass = new ReflectionClass(Foo::class);
                    $reflectionClass->initializeLazyObject(new Bar);',
                'error_message' => 'IncompatibleTypeParameters - src' . DIRECTORY_SEPARATOR
                    . 'somefile.php:5:60 - Type Bar should be a subtype of Foo',
                'error_levels' => [],
                'php_version' => '8.4',
            ],
            'isUninitializedLazyObjectWithBadType' => [
                'code' => '<?php
                    class Foo {}
                    class Bar {}
                    $reflectionClass = new ReflectionClass(Foo::class);
                    $reflectionClass->isUninitializedLazyObject(new Bar);',
                'error_message' => 'IncompatibleTypeParameters - src' . DIRECTORY_SEPARATOR
                    . 'somefile.php:5:65 - Type Bar should be a subtype of Foo',
                'error_levels' => [],
                'php_version' => '8.4',
            ],
            'markLazyObjectAsInitializedWithBadType' => [
                'code' => '<?php
                    class Foo {}
                    class Bar {}
                    $reflectionClass = new ReflectionClass(Foo::class);
                    $reflectionClass->markLazyObjectAsInitialized(new Bar);',
                'error_message' => 'IncompatibleTypeParameters - src' . DIRECTORY_SEPARATOR
                    . 'somefile.php:5:67 - Type Bar should be a subtype of Foo',
                'error_levels' => [],
                'php_version' => '8.4',
            ],
            'newLazyGhostWithBadType' => [
                'code' => '<?php
                    class Foo {}
                    class Bar {}
                    $reflectionClass = new ReflectionClass(Foo::class);
                    $reflectionClass->newLazyGhost(function (Bar $foo) {});',
                'error_message' => 'Argument 1 of ReflectionClass::newLazyGhost expects callable[impure](Foo):void, but Closure[pure](Bar):void provided',
                'error_levels' => [],
                'php_version' => '8.4',
            ],
            'newLazyProxyWithBadType_1' => [
                'code' => '<?php
                    class Foo {}
                    class Bar {}
                    $reflectionClass = new ReflectionClass(Foo::class);
                    $reflectionClass->newLazyProxy(fn(Bar $bar) => new Foo);',
                'error_message' => 'Argument 1 of ReflectionClass::newLazyProxy expects callable[impure](Foo):Foo, but Closure[pure](Bar):Foo provided',
                'error_levels' => [],
                'php_version' => '8.4',
            ],
            'newLazyProxyWithBadType_2' => [
                'code' => '<?php
                    class Foo {}
                    class Bar {}
                    $reflectionClass = new ReflectionClass(Foo::class);
                    $reflectionClass->newLazyProxy(fn(Foo $foo) => new Bar);',
                'error_message' => 'Argument 1 of ReflectionClass::newLazyProxy expects callable[impure](Foo):Foo, but Closure[pure](Foo):Bar provided',
                'error_levels' => [],
                'php_version' => '8.4',
            ],
            'resetAsLazyGhostWithBadType_1' => [
                'code' => '<?php
                    class Foo {}
                    class Bar {}
                    $reflectionClass = new ReflectionClass(Foo::class);
                    $reflectionClass->resetAsLazyGhost(new Bar, function (Foo $foo) {});',
                'error_message' => 'IncompatibleTypeParameters - src' . DIRECTORY_SEPARATOR
                    . 'somefile.php:5:56 - Type Bar should be a subtype of Foo',
                'error_levels' => [],
                'php_version' => '8.4',
            ],
            'resetAsLazyGhostWithBadType_2' => [
                'code' => '<?php
                    class Foo {}
                    class Bar {}
                    $reflectionClass = new ReflectionClass(Foo::class);
                    $reflectionClass->resetAsLazyGhost(new Foo, function (Bar $foo) {});',
                'error_message' => 'Argument 2 of ReflectionClass::resetAsLazyGhost expects callable[impure](Foo):void, but Closure[pure](Bar):void provided',
                'error_levels' => [],
                'php_version' => '8.4',
            ],
            'resetAsLazyProxyWithBadType_1' => [
                'code' => '<?php
                    class Foo {}
                    class Bar {}
                    $reflectionClass = new ReflectionClass(Foo::class);
                    $reflectionClass->resetAsLazyProxy(new Bar, fn(Foo $foo) => new Foo);',
                'error_message' => 'IncompatibleTypeParameters - src' . DIRECTORY_SEPARATOR
                    . 'somefile.php:5:56 - Type Bar should be a subtype of Foo',
                'error_levels' => [],
                'php_version' => '8.4',
            ],
            'resetAsLazyProxyWithBadType_2' => [
                'code' => '<?php
                    class Foo {}
                    class Bar {}
                    $reflectionClass = new ReflectionClass(Foo::class);
                    $reflectionClass->resetAsLazyProxy(new Foo, fn(Bar $bar) => new Foo);',
                'error_message' => 'Argument 2 of ReflectionClass::resetAsLazyProxy expects callable[impure](Foo):Foo, but Closure[pure](Bar):Foo provided',
                'error_levels' => [],
                'php_version' => '8.4',
            ],
            'resetAsLazyProxyWithBadType_3' => [
                'code' => '<?php
                    class Foo {}
                    class Bar {}
                    $reflectionClass = new ReflectionClass(Foo::class);
                    $reflectionClass->resetAsLazyProxy(new Foo, fn(Foo $foo) => new Bar);',
                'error_message' => 'Argument 2 of ReflectionClass::resetAsLazyProxy expects callable[impure](Foo):Foo, but Closure[pure](Foo):Bar provided',
                'error_levels' => [],
                'php_version' => '8.4',
            ],
            'propertyHookGetBodyIsAnalysed' => [
                'code' => '<?php
                    final class Foo {
                        public string $name {
                            get => strtoupper($this->name) . undefined_fn();
                        }
                        public function __construct() { $this->name = "a"; }
                    }',
                'error_message' => 'UndefinedFunction',
                'error_levels' => [],
                'php_version' => '8.4',
            ],
            'propertyHookSetBodyIsAnalysed' => [
                'code' => '<?php
                    final class Foo {
                        public string $name {
                            set(string $value) {
                                $this->name = $value + [];
                            }
                        }
                        public function __construct() { $this->name = "a"; }
                    }',
                'error_message' => 'InvalidOperand',
                'error_levels' => [],
                'php_version' => '8.4',
            ],
            'propertyHookGetReturnsThePropertyType' => [
                'code' => '<?php
                    final class Foo {
                        public int $count {
                            get => "not an int";
                        }
                    }',
                'error_message' => 'InvalidReturnStatement - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:36'
                    . ' - The inferred type \'\'not an int\'\' does not match the declared return type \'int\''
                    . ' for Foo::$count::get',
                'error_levels' => ['InvalidReturnType'],
                'php_version' => '8.4',
            ],
            'propertyHookGetReturnsTheDocblockPropertyType' => [
                'code' => '<?php
                    final class Foo {
                        /** @var non-empty-string */
                        public string $name {
                            get { return ""; }
                        }
                    }',
                'error_message' => 'InvalidReturnStatement',
                'error_levels' => ['InvalidReturnType'],
                'php_version' => '8.4',
            ],
            'propertyHookGetMustReturn' => [
                'code' => '<?php
                    final class Foo {
                        public int $count {
                            get { }
                        }
                    }',
                'error_message' => 'InvalidReturnType',
                'error_levels' => [],
                'php_version' => '8.4',
            ],
            'propertyHookSetReturnsNothing' => [
                'code' => '<?php
                    final class Foo {
                        public int $count {
                            set { return 1; }
                        }
                    }',
                'error_message' => 'InvalidReturnStatement',
                'error_levels' => ['InvalidReturnType'],
                'php_version' => '8.4',
            ],
            'propertyHookImplicitValueHasThePropertyType' => [
                'code' => '<?php
                    final class Foo {
                        public string $name {
                            set { $this->name = $value; echo $value + 1; }
                        }
                        public function __construct() { $this->name = "a"; }
                    }',
                'error_message' => 'InvalidOperand',
                'error_levels' => [],
                'php_version' => '8.4',
            ],
            'propertyHookShortSetAssignsTheProperty' => [
                'code' => '<?php
                    final class Foo {
                        public string $name {
                            set => 5;
                        }
                        public function __construct() { $this->name = "a"; }
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue',
                'error_levels' => [],
                'php_version' => '8.4',
            ],
            'propertyHookOfTraitIsAnalysed' => [
                'code' => '<?php
                    trait HasCount {
                        public int $count {
                            get => undefined_fn();
                        }
                    }
                    final class Foo {
                        use HasCount;
                    }',
                'error_message' => 'UndefinedFunction',
                'error_levels' => [],
                'php_version' => '8.4',
            ],
            'propertyHookCallsMissingParentHook' => [
                'code' => '<?php
                    class Foo {}
                    final class Bar extends Foo {
                        public string $name {
                            get => parent::$name::get();
                        }
                    }',
                'error_message' => 'UndefinedPropertyFetch',
                'error_levels' => [],
                'php_version' => '8.4',
            ],
            'closureInPropertyHookIsAnalysed' => [
                'code' => '<?php
                    final class Foo {
                        public int $count {
                            get {
                                $f = fn(): int => $this->missing;
                                return $f();
                            }
                        }
                    }',
                'error_message' => 'UndefinedThisPropertyFetch',
                'error_levels' => [],
                'php_version' => '8.4',
            ],
            'promotedPropertyHookBodyIsAnalysed' => [
                'code' => '<?php
                    final class Foo {
                        public function __construct(
                            public string $name {
                                set => strtolower($value) . undefined_fn();
                            },
                        ) {}
                    }',
                'error_message' => 'UndefinedFunction',
                'error_levels' => [],
                'php_version' => '8.4',
            ],
            'promotedPropertyHookGetReturnsThePropertyType' => [
                'code' => '<?php
                    final class Foo {
                        public function __construct(
                            public int $count {
                                get => "not an int";
                            },
                        ) {}
                    }',
                'error_message' => 'InvalidReturnStatement',
                'error_levels' => ['InvalidReturnType'],
                'php_version' => '8.4',
            ],
        ];
    }
}
