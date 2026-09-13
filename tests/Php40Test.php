<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class Php40Test extends TestCase
{
    use ValidCodeAnalysisTestTrait;

    /**
     * @psalm-pure
     */
    #[Override]
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
