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
    public function providerValidCodeParse(): iterable
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
                'ignored_issues' => ['UnusedVariable'],
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
                'ignored_issues' => ['UnusedVariable'],
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
                'ignored_issues' => ['UnusedVariable'],
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
                'ignored_issues' => ['UnusedVariable'],
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
        ];
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function providerInvalidCodeParse(): iterable
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
                'error_message' => 'Argument 1 of ReflectionClass::newLazyGhost expects impure-callable(Foo):void, but pure-Closure(Bar):void provided',
                'error_levels' => [],
                'php_version' => '8.4',
            ],
            'newLazyProxyWithBadType_1' => [
                'code' => '<?php
                    class Foo {}
                    class Bar {}
                    $reflectionClass = new ReflectionClass(Foo::class);
                    $reflectionClass->newLazyProxy(fn(Bar $bar) => new Foo);',
                'error_message' => 'Argument 1 of ReflectionClass::newLazyProxy expects impure-callable(Foo):Foo, but pure-Closure(Bar):Foo provided',
                'error_levels' => [],
                'php_version' => '8.4',
            ],
            'newLazyProxyWithBadType_2' => [
                'code' => '<?php
                    class Foo {}
                    class Bar {}
                    $reflectionClass = new ReflectionClass(Foo::class);
                    $reflectionClass->newLazyProxy(fn(Foo $foo) => new Bar);',
                'error_message' => 'Argument 1 of ReflectionClass::newLazyProxy expects impure-callable(Foo):Foo, but pure-Closure(Foo):Bar provided',
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
                'error_message' => 'Argument 2 of ReflectionClass::resetAsLazyGhost expects impure-callable(Foo):void, but pure-Closure(Bar):void provided',
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
                'error_message' => 'Argument 2 of ReflectionClass::resetAsLazyProxy expects impure-callable(Foo):Foo, but pure-Closure(Bar):Foo provided',
                'error_levels' => [],
                'php_version' => '8.4',
            ],
            'resetAsLazyProxyWithBadType_3' => [
                'code' => '<?php
                    class Foo {}
                    class Bar {}
                    $reflectionClass = new ReflectionClass(Foo::class);
                    $reflectionClass->resetAsLazyProxy(new Foo, fn(Foo $foo) => new Bar);',
                'error_message' => 'Argument 2 of ReflectionClass::resetAsLazyProxy expects impure-callable(Foo):Foo, but pure-Closure(Foo):Bar provided',
                'error_levels' => [],
                'php_version' => '8.4',
            ],
        ];
    }
}
