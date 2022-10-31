<?php

namespace Psalm\Tests;

use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class Php40Test extends TestCase
{
    use ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{code:string,assertions?:array<string,string>,ignored_issues?:list<string>}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'extendOldStyleConstructor' => [
                'code' => '<?php
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
                'code' => '<?php
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
