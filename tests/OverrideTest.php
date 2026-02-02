<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Config;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class OverrideTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    #[Override]
    protected function makeConfig(): Config
    {
        $config = parent::makeConfig();
        $config->ensure_override_attribute = true;
        return $config;
    }

    #[Override]
    public function providerValidCodeParse(): iterable
    {
        return [
            'constructor' => [
                'code' => '<?php
                    /**
                     * @psalm-consistent-constructor
                     */
                    class C {
                        public function __construct() {}
                    }

                    class C2 extends C {
                        public function __construct() {}
                    }
                ',
            ],
            'overrideClass' => [
                'code' => '<?php
                    class C {
                        public function f(): void {}
                    }

                    class C2 extends C {
                        #[\Override]
                        public function f(): void {}
                    }
                ',
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
            ],
            'traitNotMissingAttribute1' => [
                'code' => '<?php
                    trait B {
                        public function f(): void {}
                    }

                    class C {
                        use B;
                    }

                    class C2 extends C {
                        use B;
                    }
                ',
            ],
            'traitNotMissingAttribute2' => [
                'code' => '<?php
                    trait B {
                        public function f(): void {}
                    }

                    class C {
                        public function f(): void {}
                    }

                    class C2 extends C {
                        use B;
                    }
                ',
            ],
            'canBeUsedOnPureMethods' => [
                'code' => <<<'PHP'
                    <?php
                    class A {
                        /** @psalm-pure */
                        public function f(int $a): int {
                            return $a;
                        }
                    }
                    class B extends A {
                        /** @psalm-pure */
                        #[Override]
                        public function f(int $a): int {
                            return $a + 1;
                        }
                    }
                    PHP,
            ],
            'ignoreImplicitStringable' => [
                'code' => '
                    <?php
                    class A {
                        public function __toString(): string {
                            return "";
                        }
                    }
                ',
            ],
        ];
    }

    #[Override]
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
                'error_message' => 'InvalidOverride',
                'error_levels' => [],
                'php_version' => '8.3',
            ],
            'classMissingAttribute' => [
                'code' => '<?php
                    class C {
                        public function f(): void {}
                    }

                    class C2 extends C {
                        public function f(): void {}
                    }
                ',
                'error_message' => 'MissingOverrideAttribute',
                'error_levels' => [],
                'php_version' => '8.3',
            ],
            'classUsingTrait' => [
                'code' => '<?php
                    trait T {
                        abstract public function f(): void;
                    }

                    class C {
                        use T;

                        public function f(): void {}
                    }
                ',
                'error_message' => 'MissingOverrideAttribute',
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
                'error_message' => 'InvalidOverride',
                'error_levels' => [],
                'php_version' => '8.3',
            ],
            'interfaceMissingAttribute' => [
                'code' => '<?php
                    interface I {
                        public function f(): void;
                    }

                    interface I2 extends I {
                        public function f(): void;
                    }
                ',
                'error_message' => 'MissingOverrideAttribute',
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
                'error_message' => 'InvalidOverride',
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
                'error_message' => 'InvalidOverride',
                'error_levels' => [],
                'php_version' => '8.3',
            ],
            'explicitStringable' => [
                'code' => '
                    <?php
                    class A implements Stringable {
                        public function __toString(): string {
                            return "";
                        }
                    }
                ',
                'error_message' => 'MissingOverrideAttribute',
                'error_levels' => [],
                'php_version' => '8.3',
            ],
        ];
    }
}
