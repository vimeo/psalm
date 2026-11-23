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
            'writeOutsideTheHooksTakesTheTypeOfTheSetHookParameter' => [
                'code' => '<?php
                    final class Tags {
                        public string $value = "" {
                            set(string|array $value) {
                                $this->value = is_array($value) ? implode(",", array_map("strval", $value)) : $value;
                            }
                        }

                        public function reset(): void {
                            $this->value = ["none"];
                        }
                    }

                    function fill(Tags $tags): void {
                        $tags->value = ["a", "b"];
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedArgument'],
                'php_version' => '8.4',
            ],
            'writeInTheConstructorGoesThroughTheSetHook' => [
                'code' => '<?php
                    final class Foo {
                        public string $bar {
                            set(string|int|null $value) {
                                $this->bar = (string) $value;
                            }
                        }

                        public function __construct() {
                            $this->bar = null;
                        }
                    }

                    $foo = new Foo();
                    $foo->bar = 5;
                    $foo->bar = "five";',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'readAfterAWriteThroughTheSetHookHasTheTypeOfTheProperty' => [
                'code' => '<?php
                    final class Tags {
                        public string $value = "" {
                            set(string|array $value) {
                                $this->value = is_array($value) ? implode(",", array_map("strval", $value)) : $value;
                            }
                        }
                    }

                    final class Upper {
                        public string $value = "" {
                            set(string $value) {
                                $this->value = strtoupper($value);
                            }
                        }
                    }

                    final class Plain {
                        public string $value = "";
                    }

                    $tags = new Tags();
                    $tags->value = ["a", "b"];
                    $tags_value = $tags->value;

                    $upper = new Upper();
                    $upper->value = "a";
                    $upper_value = $upper->value;

                    $plain = new Plain();
                    $plain->value = "a";
                    $plain_value = $plain->value;',
                'assertions' => [
                    '$tags_value===' => 'string',
                    '$upper_value===' => 'string',
                    '$plain_value===' => '\'a\'',
                ],
                'ignored_issues' => ['MixedArgument'],
                'php_version' => '8.4',
            ],
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
                    '$property_hook->test' => 'int|null',
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
            'magicMethodConstantsInAPropertyHook' => [
                'code' => '<?php
                    namespace N;

                    final class H {
                        /** @var \'N\\H::$method::get\' */
                        public string $method {
                            get => __METHOD__;
                        }

                        /** @var \'$function::get\' */
                        public string $function {
                            get => __FUNCTION__;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'getHookOfANullablePropertyMayReturnANonNullValue' => [
                'code' => '<?php
                    interface Logger {}

                    final class H {
                        public function __construct(private Logger $logger) {}

                        public ?Logger $current {
                            get => $this->logger;
                        }
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
            'propertyHookBodySuppressedOnPropertyAndHook' => [
                'code' => '<?php
                    final class Foo {
                        /** @psalm-suppress UndefinedFunction */
                        public string $name {
                            /** @psalm-suppress MixedReturnStatement */
                            get => undefined_fn();
                        }
                        /** @psalm-suppress UndefinedFunction, MixedReturnStatement */
                        public string $other {
                            /** Computed. */
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
            'propertyHookOfTraitCallsParentHook' => [
                'code' => '<?php
                    class Foo {
                        public string $name = "a";
                    }
                    trait UpperName {
                        public string $name {
                            get => strtoupper(parent::$name::get());
                        }
                    }
                    final class Bar extends Foo {
                        use UpperName;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'propertyHookWithMethodAttribute' => [
                'code' => '<?php
                    #[Attribute(Attribute::TARGET_METHOD)]
                    final class OnlyMethod {}

                    final class Foo {
                        public string $name {
                            #[OnlyMethod]
                            get => "a";
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'propertyHookUntypedValueOfDocblockTypedProperty' => [
                'code' => '<?php
                    final class Foo {
                        /** @var string */
                        public $name = "a" {
                            set($value) { $this->name = $value; }
                        }
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
            'writeInsideTheSetHookTakesTheTypeOfTheProperty' => [
                'code' => '<?php
                    final class Tags {
                        public string $value = "" {
                            set(string|array $value) {
                                $this->value = $value;
                            }
                        }
                    }',
                'error_message' => 'PossiblyInvalidPropertyAssignmentValue',
                'error_levels' => [],
                'php_version' => '8.4',
            ],
            'writeInsideTheSetHookIsRemembered' => [
                'code' => '<?php
                    final class Upper {
                        public string $value = "" {
                            set(string $value) {
                                $this->value = "X";
                                if ($this->value === "Y") {
                                    echo "never";
                                }
                            }
                        }
                    }',
                'error_message' => 'TypeDoesNotContainType',
                'error_levels' => [],
                'php_version' => '8.4',
            ],
            'writeInsideTheSetHookOfATraitPropertyIsCheckedAgainstTheProperty' => [
                'code' => '<?php
                    trait T {
                        public string $v = "" {
                            set(string|array $value) {
                                $this->v = $value;
                            }
                        }
                    }

                    final class UsesT {
                        use T;
                    }',
                'error_message' => 'PossiblyInvalidPropertyAssignmentValue',
                'error_levels' => [],
                'php_version' => '8.4',
            ],
            'writeInsideTheSetHookOfATraitPropertyIsRemembered' => [
                'code' => '<?php
                    trait T {
                        public string $value = "" {
                            set(string $value) {
                                $this->value = "X";
                                if ($this->value === "Y") {
                                    echo "never";
                                }
                            }
                        }
                    }

                    final class UsesT {
                        use T;
                    }',
                'error_message' => 'TypeDoesNotContainType',
                'error_levels' => [],
                'php_version' => '8.4',
            ],
            'writeOutsideTheHooksIsCheckedAgainstTheSetHookParameter' => [
                'code' => '<?php
                    final class Tags {
                        public string $value = "" {
                            set(string|array $value) {
                                $this->value = is_string($value) ? $value : "";
                            }
                        }
                    }

                    function fill(Tags $tags): void {
                        $tags->value = 5;
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue',
                'error_levels' => [],
                'php_version' => '8.4',
            ],
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
            'propertySuppressionStaysInItsHooks' => [
                'code' => '<?php
                    final class Foo {
                        /** @psalm-suppress UndefinedFunction, MixedReturnStatement */
                        public string $a {
                            get => undefined_fn();
                        }
                        public string $b {
                            get => other_undefined_fn();
                        }
                    }',
                'error_message' => 'UndefinedFunction - src' . DIRECTORY_SEPARATOR . 'somefile.php:8:36',
                'error_levels' => [],
                'php_version' => '8.4',
            ],
            'propertySuppressionStaysInItsClass' => [
                'code' => '<?php
                    final class Foo {
                        /** @psalm-suppress UndefinedFunction, MixedReturnStatement */
                        public string $a {
                            get => undefined_fn();
                        }
                    }
                    final class Bar {
                        public function f(): void {
                            other_undefined_fn();
                        }
                    }',
                'error_message' => 'UndefinedFunction - src' . DIRECTORY_SEPARATOR . 'somefile.php:10:29',
                'error_levels' => [],
                'php_version' => '8.4',
            ],
            'propertyHookWithFunctionAttribute' => [
                'code' => '<?php
                    #[Attribute(Attribute::TARGET_FUNCTION)]
                    final class OnlyFunction {}

                    final class Foo {
                        public string $name {
                            #[OnlyFunction]
                            get => "a";
                        }
                    }',
                'error_message' => 'InvalidAttribute',
                'error_levels' => [],
                'php_version' => '8.4',
            ],
            'parentHookCallOutsideOfAHook' => [
                'code' => '<?php
                    class Foo {
                        public string $name = "a";
                    }
                    final class Bar extends Foo {
                        public function f(): string {
                            return parent::$name::get();
                        }
                    }',
                'error_message' => 'UndefinedPropertyFetch',
                'error_levels' => [],
                'php_version' => '8.4',
            ],
            'readingANativePropertyNewerThanTheAnalysedVersion' => [
                'code' => '<?php
                    function f(Dom\Element $e): string {
                        return $e->outerHTML;
                    }',
                'error_message' => 'UndefinedPropertyFetch - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:32 - Dom\Element::$outerHTML is not defined for the analysed PHP version 8.4',
                'error_levels' => [],
                'php_version' => '8.4',
            ],
            'writingANativePropertyNewerThanTheAnalysedVersion' => [
                'code' => '<?php
                    function f(Dom\Element $e): void {
                        $e->outerHTML = "<p></p>";
                    }',
                'error_message' => 'UndefinedPropertyAssignment - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:25 - Dom\Element::$outerHTML is not defined for the analysed PHP version 8.4',
                'error_levels' => [],
                'php_version' => '8.4',
            ],
        ];
    }
}
