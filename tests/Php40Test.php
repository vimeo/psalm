<?php
namespace Psalm\Tests;

class Php40Test extends TestCase
{
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse()
    {
        return [
            'extendOldStyleConstructor' => [
                '<?php
                    class A {
                        /**
                         * @return string
                         */
                        public function A() {
                            return "hello";
                        }
                    }

                    class B extends A {
                        public function __construct() {
                            parent::__construct();
                        }
                    }',
            ],
            'sameNameMethodWithNewStyleConstructor' => [
                '<?php
                    class A {
                        public function __construct(string $s) { }
                        /** @return void */
                        public function a(int $i) { }
                    }
                    new A("hello");',
            ],
        ];
    }
}
