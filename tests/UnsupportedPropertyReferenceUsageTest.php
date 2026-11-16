<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class UnsupportedPropertyReferenceUsageTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'can be suppressed' => [
                'code' => <<<'PHP'
                    <?php
                    /**
                     * @property int $b
                     */
                    final class A {
                        public function __get(string $name): int {
                            return 0;
                        }
                    }
                    $a = new A();
                    /** @psalm-suppress UnsupportedPropertyReferenceUsage */
                    $b = &$a->b;
                    PHP,
            ],
        ];
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerInvalidCodeParse(): iterable
    {
        return [
            'magic property' => [
                'code' => <<<'PHP'
                    <?php
                    /**
                     * @property int $b
                     */
                    final class A {
                        public function __get(string $name): int {
                            return 0;
                        }
                    }
                    $a = new A();
                    $b = &$a->b; // Notice: Indirect modification of overloaded property
                    PHP,
                'error_message' => 'UnsupportedPropertyReferenceUsage',
            ],
            'item of an ArrayAccess property' => [
                'code' => <<<'PHP'
                    <?php
                    final class A {
                        /** @var ArrayObject<int, int> */
                        public ArrayObject $b;

                        public function __construct() {
                            $this->b = new ArrayObject();
                        }
                    }
                    $a = new A();
                    $b = &$a->b[0];
                    PHP,
                'error_message' => 'UnsupportedPropertyReferenceUsage',
            ],
            'property of objects of different types' => [
                'code' => <<<'PHP'
                    <?php
                    final class A {
                        public int $b = 0;
                    }
                    final class B {
                        public string $b = '';
                    }
                    function f(A|B $o): void {
                        $b = &$o->b;
                        $b = ''; // Fatal error when $o is an A
                    }
                    PHP,
                'error_message' => 'UnsupportedPropertyReferenceUsage',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'hooked property' => [
                'code' => <<<'PHP'
                    <?php
                    final class A {
                        public int $b {
                            get => 1;
                        }
                    }
                    $a = new A();
                    $b = &$a->b; // Error: Indirect modification of a hooked property
                    PHP,
                'error_message' => 'UnsupportedPropertyReferenceUsage',
                'error_levels' => [],
                'php_version' => '8.4',
            ],
            'readonly property' => [
                'code' => <<<'PHP'
                    <?php
                    class A {
                        public function __construct(
                            public readonly int $b,
                        ) {
                        }
                    }
                    $a = new A(0);
                    $b = &$a->b;
                    $b = 1; // Fatal error
                    PHP,
                'error_message' => 'UnsupportedPropertyReferenceUsage',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
        ];
    }
}
