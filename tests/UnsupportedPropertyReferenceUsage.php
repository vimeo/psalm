<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class UnsupportedPropertyReferenceUsage extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    /**
     * @psalm-pure
     */
    #[Override]
    public function providerValidCodeParse(): iterable
    {
        return [
            'can be suppressed' => [
                'code' => <<<'PHP'
                    <?php
                    class A {
                        public int $b = 0;
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
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'instance property' => [
                'code' => <<<'PHP'
                    <?php
                    class A {
                        public int $b = 0;
                    }
                    $a = new A();
                    $b = &$a->b;
                    $b = ''; // Fatal error
                    PHP,
                'error_message' => 'UnsupportedPropertyReferenceUsage',
            ],
            'static property' => [
                'code' => <<<'PHP'
                    <?php
                    class A {
                        public static int $b = 0;
                    }
                    $b = &A::$b;
                    $b = ''; // Fatal error
                    PHP,
                'error_message' => 'UnsupportedPropertyReferenceUsage',
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
