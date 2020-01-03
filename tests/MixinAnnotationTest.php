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
                    }

                    $a = new A();
                    $a->b();'
            ],
        ];
    }
}
