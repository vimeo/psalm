<?php
namespace Psalm\Tests\Template;

use Psalm\Tests\TestCase;
use Psalm\Tests\Traits;

class ClassStringMapTest extends TestCase
{
    use Traits\ValidCodeAnalysisTestTrait;
    use Traits\InvalidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'basicClassStringMap' => [
                '<?php
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
                '<?php
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
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,1?:string[],2?:bool,3?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'assignInvalidClass' => [
                '<?php
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
                'error_message' => 'InvalidPropertyAssignmentValue'
            ],
            'assignInvalidClassDifferentTemplateName' => [
                '<?php
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
                'error_message' => 'InvalidPropertyAssignmentValue'
            ],
        ];
    }
}
