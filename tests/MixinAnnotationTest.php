<?php
namespace Psalm\Tests;

use const DIRECTORY_SEPARATOR;
use Psalm\Config;
use Psalm\Context;

class MixinAnnotationTest extends TestCase
{
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse()
    {
        return [
            'validSimpleAnnotations' => [
                '<?php
                    class ParentClass {
                        public function __call(string $name, array $args) {}
                    }

                    class Provider {
                        public function getString() : string {
                            return "hello";
                        }

                        public function setInteger(int $i) : void {}
                    }

                    /** @mixin Provider */
                    class Child extends ParentClass {}

                    $child = new Child();

                    $a = $child->getString();
                    $child->setInteger(4);',
                'assertions' => [
                    '$a' => 'string',
                ],
            ],
            'anotherSimpleExample' => [
                '<?php
                    /**
                     * @mixin B
                     */
                    class A {
                        /** @var B */
                        private $b;

                        public function __construct() {
                            $this->b = new B();
                        }

                        public function c(string $s) : void {}

                        /**
                         * @param array<mixed> $arguments
                         * @return mixed
                         */
                        public function __call(string $method, array $arguments)
                        {
                            return $this->b->$method(...$arguments);
                        }
                    }

                    class B {
                        public function b(): void {
                            echo "b";
                        }

                        public function c(int $s) : void {}
                    }

                    $a = new A();
                    $a->b();'
            ],
            'allowConstructor' => [
                '<?php
                    abstract class AParent {
                        protected int $i;

                        public function __construct() {
                            $this->i = 1;
                        }
                    }

                    class M {
                        public function __construct() {}
                    }

                    /**
                     * @mixin M
                     */
                    class A extends AParent {}'
            ],
            'implicitMixin' => [
                '<?php
                    function foo(string $dir) : void {
                        $iterator = new \RecursiveIteratorIterator(
                            new \RecursiveDirectoryIterator($dir)
                        );

                        while ($iterator->valid()) {
                            if (!$iterator->isDot() && $iterator->isLink()) {}

                            $iterator->next();
                        }
                    }'
            ],
            'wrapCustomIterator' => [
                '<?php
                    class Subject implements Iterator {
                        /**
                         * the index method exists
                         *
                         * @param int $index
                         * @return bool
                         */
                        public function index($index) {
                            return true;
                        }

                        public function current() {
                            return 2;
                        }

                        public function next() {}

                        public function key() {
                            return 1;
                        }

                        public function valid() {
                            return false;
                        }

                        public function rewind() {}
                    }

                    $iter = new IteratorIterator(new Subject());
                    $b = $iter->index(0);',
                [
                    '$b' => 'bool',
                ]
            ],
            'templatedMixin' => [
                '<?php

                    /**
                     * @template T
                     */
                    abstract class Foo {
                        /** @return T */
                        abstract public function hi();
                    }

                    /**
                     * @mixin Foo<string>
                     */
                    class Bar {}

                    $bar = new Bar();
                    $b = $bar->hi();',
                [
                    '$b' => 'string',
                ]
            ],
        ];
    }
}
