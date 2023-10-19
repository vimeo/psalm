<?php

declare(strict_types=1);

namespace Psalm\Tests\Template;

use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class ClassStringMapTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        return [
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
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
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
