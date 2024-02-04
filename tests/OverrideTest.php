<?php

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

class OverrideTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        return [
            'overrideClass' => [
                'code' => '<?php
                    class C {
                        public function f(): void {}
                    }

                    class C2 extends C {
                        #[Override]
                        public function f(): void {}
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.3',
            ],
            'overrideInterface' => [
                'code' => '<?php
                    interface I {
                        public function f(): void;
                    }

                    interface I2 extends I {
                        #[Override]
                        public function f(): void;
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.3',
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'noParent' => [
                'code' => '<?php
                    class C {
                        #[Override]
                        public function f(): void {}
                    }
                ',
                'error_message' => 'InvalidOverride - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:25',
                'error_levels' => [],
                'php_version' => '8.3',
            ],
            'constructor' => [
                'code' => '<?php
                    /**
                     * @psalm-consistent-constructor
                     */
                    class C {
                        public function __construct() {}
                    }

                    class C2 extends C {
                        #[Override]
                        public function __construct() {}
                    }
                ',
                'error_message' => 'InvalidOverride - src' . DIRECTORY_SEPARATOR . 'somefile.php:10:25',
                'error_levels' => [],
                'php_version' => '8.3',
            ],
            'privateMethod' => [
                'code' => '<?php
                    class C {
                        private function f(): void {}
                    }

                    class C2 extends C {
                        #[Override]
                        private function f(): void {}
                    }
                ',
                'error_message' => 'InvalidOverride - src' . DIRECTORY_SEPARATOR . 'somefile.php:7:25',
                'error_levels' => [],
                'php_version' => '8.3',
            ],
            'interfaceWithNoParent' => [
                'code' => '<?php
                    interface I {
                        #[Override]
                        public function f(): void;
                    }
                ',
                'error_message' => 'InvalidOverride - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:25',
                'error_levels' => [],
                'php_version' => '8.3',
            ],
        ];
    }
}
