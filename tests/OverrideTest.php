<?php

namespace Psalm\Tests;

use Psalm\Config;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class OverrideTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    protected function makeConfig(): Config
    {
        $config = parent::makeConfig();
        $config->ensure_override_attribute = true;
        return $config;
    }

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
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.3',
            ],
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
            'canBeUsedOnPureMethods' => [
                'code' => <<<'PHP'
                    <?php
                    class A {
                        /** @psalm-pure */
                        public function f(): void {}
                    }
                    class B extends A {
                        /** @psalm-pure */
                        #[Override]
                        public function f(): void {}
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.3',
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
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.3',
            ],
            'Test case #10982 - https://github.com/vimeo/psalm/issues/10982' => [
                'code' => '
                    <?php
                    trait Foo
                    {
                        private function inTrait(): void { echo "foobar\n"; }
                    }

                    class A {
                        use Foo;

                        public function bar(): void {
                            $this->inTrait();
                        }
                    }

                    class B extends A {
                        use Foo;

                        function baz(): void
                        {
                            $this->inTrait();
                        }
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.3',
            ],
            'Test case #10989 - https://github.com/vimeo/psalm/pull/10989#discussion_r1615149365' => [
                'code' => '
                    <?php

                    trait Foo
                    {
                        protected function inTrait(): void { echo "foobar\n"; }
                    }

                    class A {
                        use Foo;

                        public function bar(): void {
                            $this->inTrait();
                        }
                    }

                    class B extends A {
                        use Foo;

                        function baz(): void
                        {
                            $this->inTrait();
                        }
                    }

                    $b = new B();
                    $b->baz();
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.3',
            ],
            'Valid examples #1 - https://wiki.php.net/rfc/marking_overriden_methods' => [
                'code' => '
                    <?php
                    class P {
                        protected function p(): void {}
                    }

                    class C extends P {
                        #[\Override]
                        public function p(): void {}
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.3',
            ],
            'Valid examples #2 - https://wiki.php.net/rfc/marking_overriden_methods' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @implements \IteratorAggregate<T>
                     */
                    class Foo implements IteratorAggregate
                    {
                        #[\Override]
                        public function getIterator(): Traversable
                        {
                            yield from [];
                        }
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.3',
            ],
            'Valid examples #3 - https://wiki.php.net/rfc/marking_overriden_methods' => [
                'code' => '<?php
                    trait T {
                        #[\Override]
                        public function t(): void {}
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.3',
            ],
            'Valid examples #4 - https://wiki.php.net/rfc/marking_overriden_methods' => [
                'code' => '<?php
                    trait T {
                        #[\Override]
                        public function i(): void {}
                    }

                    interface I {
                        public function i(): void;
                    }

                    class Foo implements I {
                        use T;
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.3',
            ],
            'Valid examples #5 - https://wiki.php.net/rfc/marking_overriden_methods' => [
                'code' => '<?php
                    interface I {
                        public function i();
                    }

                    interface II extends I {
                        #[\Override]
                        public function i();
                    }

                    class P {
                        public function p1() {}
                        public function p2() {}
                        public function p3() {}
                        public function p4() {}
                    }

                    class PP extends P {
                        #[\Override]
                        public function p1() {}
                        #[\Override]
                        public function p2() {}
                        #[\Override]
                        public function p3() {}
                    }

                    class C extends PP implements I {
                        #[\Override]
                        public function i() {}
                        #[\Override]
                        public function p1() {}
                        #[\Override]
                        public function p2() {}
                        #[\Override]
                        public function p3() {}
                        #[\Override]
                        public function p4() {}
                        public function c() {}
                    }
                ',
                'assertions' => [],
                'ignored_issues' => ['MissingReturnType'],
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
            'Invalid examples #1 - https://wiki.php.net/rfc/marking_overriden_methods' => [
                'code' => '<?php
                    class C
                    {
                        #[\Override]
                        public function c(): void {}
                        // Fatal error: C::c() has #[\Override] attribute, but no matching parent method exists
                    }
                ',
                'error_message' => 'InvalidOverride',
                'error_levels' => [],
                'php_version' => '8.3',
            ],
            'Invalid examples #2 - https://wiki.php.net/rfc/marking_overriden_methods' => [
                'code' => '<?php
                    interface I {
                        public function i(): void;
                    }

                    class P {
                        #[\Override]
                        public function i(): void {}
                        // Fatal error: P::i() has #[\Override] attribute, but no matching parent method exists
                    }

                    class C extends P implements I {}
                ',
                'error_message' => 'InvalidOverride',
                'error_levels' => [],
                'php_version' => '8.3',
            ],
            'Invalid examples #3 - https://wiki.php.net/rfc/marking_overriden_methods' => [
                'code' => '<?php
                    trait T {
                        #[\Override]
                        public function t(): void {}
                    }

                    class Foo {
                        use T;
                        // Fatal error: Foo::t() has #[\Override] attribute, but no matching parent method exists
                    }
                ',
                'error_message' => 'InvalidOverride',
                'error_levels' => [],
                'php_version' => '8.3',
            ],
            'Invalid examples #4 - https://wiki.php.net/rfc/marking_overriden_methods' => [
                'code' => '<?php
                    class P {
                        private function p(): void {}
                    }

                    class C extends P {
                        #[\Override]
                        public function p(): void {}
                        // Fatal error: C::p() has #[\Override] attribute, but no matching parent method exists
                    }
                ',
                'error_message' => 'InvalidOverride',
                'error_levels' => [],
                'php_version' => '8.3',
            ],
            'Invalid examples #5 - https://wiki.php.net/rfc/marking_overriden_methods' => [
                'code' => '<?php
                    trait T {
                        public function t(): void {}
                    }

                    class C {
                        use T;

                        #[\Override]
                        public function t(): void {}
                        // Fatal error: C::t() has #[\Override] attribute, but no matching parent method exists
                    }
                ',
                'error_message' => 'InvalidOverride',
                'error_levels' => [],
                'php_version' => '8.3',
            ],
            'Invalid examples #6 - https://wiki.php.net/rfc/marking_overriden_methods' => [
                'code' => '<?php
                    interface I {
                        #[\Override]
                        public function i(): void;
                        // Fatal error: I::i() has #[\Override] attribute, but no matching parent method exists
                    }
                ',
                'error_message' => 'InvalidOverride',
                'error_levels' => [],
                'php_version' => '8.3',
            ],
        ];
    }
}
