<?php

declare(strict_types=1);

namespace Psalm\Tests\Template;

use Override;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class ClassStringMapTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'literalClassStringOffsetOfAClassStringMap' => [
                'code' => '<?php
                    abstract class Client {}
                    final class StorageClient extends Client {}
                    final class TokenClient extends Client {}

                    final class Factory {
                        /** @var class-string-map<T as Client, T> */
                        private array $cache = [];

                        public function storage(): StorageClient {
                            if (!isset($this->cache[StorageClient::class])) {
                                $this->cache[StorageClient::class] = new StorageClient();
                            }

                            return $this->cache[StorageClient::class];
                        }

                        public function cachedToken(): TokenClient {
                            return $this->cache[TokenClient::class];
                        }
                    }',
            ],
            'basicClassStringMap' => [
                'code' => '<?php
                    namespace Bar;

                    /**
                     * @psalm-consistent-constructor
                     */
                    class Foo {}

                    class A {
                        /** @var class-string-map<T as Foo, T> */
                        public static array $map = [];

                        /**
                         * @template T as Foo
                         * @param class-string<T> $class
                         * @return T
                         */
                        public function get(string $class) : Foo {
                            if (isset(self::$map[$class])) {
                                return self::$map[$class];
                            }

                            self::$map[$class] = new $class();
                            return self::$map[$class];
                        }
                    }',
            ],
            'basicClassStringMapDifferentTemplateName' => [
                'code' => '<?php
                    namespace Bar;

                    /**
                     * @psalm-consistent-constructor
                     */
                    class Foo {}

                    class A {
                        /** @var class-string-map<T as Foo, T> */
                        public static array $map = [];

                        /**
                         * @template U as Foo
                         * @param class-string<U> $class
                         * @return U
                         */
                        public function get(string $class) : Foo {
                            if (isset(self::$map[$class])) {
                                return self::$map[$class];
                            }

                            self::$map[$class] = new $class();
                            return self::$map[$class];
                        }
                    }',
            ],
            'noCrashWithSplatMap' => [
                'code' => '<?php
                    class A {}

                    /** @param array<array-key, mixed> $args */
                    function takesVariadic(...$args): void {
                    }

                    /** @param class-string-map<A, A> $arr */
                    function foo(array $arr) : void {
                        takesVariadic(...$arr);
                    }',
            ],
            'assignClassStringMapInConstruct' => [
                'code' => '<?php
                    class A {
                        /** @var class-string-map<T,T> */
                        private array $map;
                        /** @param class-string-map<T,T> $map */
                        public function __construct(array $map) {
                            $this->map = $map;
                        }
                    }',
            ],
            'assignClassStringMapInMethod' => [
                'code' => '<?php
                    class A {
                        /** @var class-string-map<T,T> */
                        private array $map = [];
                        /** @param class-string-map<T,T> $map */
                        public function set(array $map): void {
                            $this->map = $map;
                        }
                    }',
            ],
            'simpleSetter' => [
                'code' => '<?php
                    class Container {
                        /** @var class-string-map<T, T> */
                        public array $map = [];
                        /**
                         * @template U of object
                         * @param class-string<U> $key
                         * @param U $obj
                         */
                        public function set(string $key, object $obj): void {
                            $this->map[$key] = $obj;
                        }
                    }'
                    ,
            ],
            'classStringMapCombinedWithAnotherArrayHoldsTheBoundOfItsTemplate' => [
                'code' => '<?php
                    class Foo {}
                    class Bar {}

                    /** @return class-string-map<T as Foo, T> */
                    function getFoos(): array {
                        return [];
                    }

                    /** @return class-string-map<T as Bar, T> */
                    function getBars(): array {
                        return [];
                    }

                    /** @return array<int, int> */
                    function getInts(): array {
                        return [];
                    }

                    $a = rand(0, 1) ? getFoos() : getInts();
                    $b = rand(0, 1) ? getFoos() : getBars();
                    $c = rand(0, 1) ? getFoos() : getFoos();',
                'assertions' => [
                    '$a===' => 'array<class-string<Foo>|int, Foo|int>',
                    '$b===' => 'array<class-string<Bar>|class-string<Foo>, Bar|Foo>',
                    '$c===' => 'class-string-map<T as Foo, T:class-string-map as Foo>',
                ],
            ],
            'foreachOverClassStringMapKeepsTheIntersectionOfItsTemplate' => [
                'code' => '<?php
                    class Foo {}
                    interface Bar {
                        public function bar(): int;
                    }

                    /**
                     * @param class-string-map<T as Foo, T&Bar> $m
                     * @param class-string-map<T, T&Bar> $n
                     */
                    function f(array $m, array $n): int {
                        $i = 0;
                        foreach ($m as $v) {
                            $i += $v->bar();
                        }
                        foreach ($n as $v) {
                            $i += $v->bar();
                        }
                        return $i;
                    }',
            ],
            'classStringMapBoundWithOfInANamespace' => [
                'code' => '<?php
                    namespace NS;

                    class Foo {
                        public function foo(): int {
                            return 1;
                        }
                    }

                    /**
                     * @param class-string-map<T of Foo, T> $m
                     * @param class-string-map<T of \NS\Foo, T> $n
                     */
                    function f(array $m, array $n): int {
                        foreach ($m as $v) {
                            return $v->foo();
                        }
                        foreach ($n as $v) {
                            return $v->foo();
                        }
                        return 0;
                    }

                    /** @return class-string-map<T of Foo, T> */
                    function g(): array {
                        return [];
                    }

                    $m = g();',
                'assertions' => [
                    '$m===' => 'class-string-map<T as NS\Foo, T:class-string-map as NS\Foo>',
                ],
            ],
            'classNamedOfInANamespaceIsStillQualified' => [
                'code' => '<?php
                    namespace NS;

                    class of {}

                    /** @return of */
                    function g() {
                        return new of();
                    }

                    $o = g();',
                'assertions' => [
                    '$o===' => 'NS\of',
                ],
            ],
            'classStringMapCombinedWithAnotherArrayKeepsOtherTemplates' => [
                'code' => '<?php
                    class Foo {}

                    /**
                     * @template Data of array
                     * @template Key of key-of<Data>
                     * @param class-string-map<T as Foo, Data[Key]> $m
                     * @param array<int, int> $a
                     * @return array<class-string<Foo>|int, Data[Key]|int>
                     */
                    function f(array $m, array $a, bool $b): array {
                        return $b ? $m : $a;
                    }',
            ],
            'classNamedOfInsideATypeInANamespaceIsStillQualified' => [
                'code' => '<?php
                    namespace NS;

                    class of {}

                    /**
                     * @param ?of $a
                     * @param list<of> $b
                     * @param array{a: of} $c
                     * @param array<int, (of)> $d
                     * @param null|of $e
                     * @return list{?of, list<of>, array{a: of}, array<int, of>, ?of}
                     */
                    function g($a, $b, $c, $d, $e) {
                        return [$a, $b, $c, $d, $e];
                    }

                    $o = g(null, [], ["a" => new of()], [], null);',
                'assertions' => [
                    '$o===' => 'list{NS\of|null, list<NS\of>, array{a: NS\of}, array<int, NS\of>, NS\of|null}',
                ],
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
            'literalClassStringOffsetOfAClassStringMapGivenAnotherClass' => [
                'code' => '<?php
                    abstract class Client {}
                    final class StorageClient extends Client {}
                    final class TokenClient extends Client {}

                    final class Factory {
                        /** @var class-string-map<T as Client, T> */
                        private array $cache = [];

                        public function storage(): void {
                            $this->cache[StorageClient::class] = new TokenClient();
                        }
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'assignInvalidClass' => [
                'code' => '<?php
                    namespace Bar;

                    class A {
                        /** @var class-string-map<T, T> */
                        public static array $map = [];

                        /**
                         * @template T
                         * @param class-string<T> $class
                         */
                        public function get(string $class) : void {
                            self::$map[$class] = 5;
                        }
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'assignInvalidClassDifferentTemplateName' => [
                'code' => '<?php
                    namespace Bar;

                    class A {
                        /** @var class-string-map<T, T> */
                        public static array $map = [];

                        /**
                         * @template U
                         * @param class-string<U> $class
                         */
                        public function get(string $class) : void {
                            self::$map[$class] = 5;
                        }
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
        ];
    }
}
