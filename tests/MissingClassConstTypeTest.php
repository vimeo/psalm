<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Issue\MissingClassConstType;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

final class MissingClassConstTypeTest extends TestCase
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
            'has type; >= PHP 8.3' => [
                'code' => <<<'PHP'
                    <?php
                    class A {
                        public const int B = 0;
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.3',
            ],
            'no type; >= PHP 8.3; but class is final' => [
                'code' => <<<'PHP'
                    <?php
                    final class A {
                        public const B = 0;
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.3',
            ],
            'no type; >= PHP 8.3; but constant is final' => [
                'code' => <<<'PHP'
                    <?php
                    class A {
                        final public const B = 0;
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.3',
            ],
            'no type; >= PHP 8.3; but constants of the statement are final' => [
                'code' => <<<'PHP'
                    <?php
                    class A {
                        final public const B = 0, C = 1;
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.3',
            ],
            'no type; >= PHP 8.3; but interface constant is final' => [
                'code' => <<<'PHP'
                    <?php
                    interface I {
                        final public const B = 0;
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.3',
            ],
            'no type; >= PHP 8.3; but trait constant is final' => [
                'code' => <<<'PHP'
                    <?php
                    trait T {
                        final public const B = 0;
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.3',
            ],
            'no type; >= PHP 8.3; but psalm-suppressed' => [
                'code' => <<<'PHP'
                    <?php
                    class A {
                        /** @psalm-suppress MissingClassConstType */
                        public const B = 0;
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.3',
            ],
            'no type; >= PHP 8.3; but psalm-suppressed on the class' => [
                'code' => <<<'PHP'
                    <?php
                    /** @psalm-suppress MissingClassConstType */
                    class A {
                        public const B = 0;
                        public const C = 1;
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.3',
            ],
            'no type; >= PHP 8.3; but psalm-suppressed on the interface' => [
                'code' => <<<'PHP'
                    <?php
                    /** @psalm-suppress MissingClassConstType */
                    interface I {
                        public const B = 0;
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.3',
            ],
            'no type; < PHP 8.3' => [
                'code' => <<<'PHP'
                    <?php
                    class A {
                        public const B = 0;
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.2',
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
            'no type; >= PHP 8.3' => [
                'code' => <<<'PHP'
                    <?php
                    class A {
                        public const B = 0;
                    }
                    PHP,
                'error_message' => MissingClassConstType::getIssueType(),
                'error_levels' => [],
                'php_version' => '8.3',
            ],
            'no type; >= PHP 8.3; non-final constant next to a final one' => [
                'code' => <<<'PHP'
                    <?php
                    class A {
                        final public const B = 0;
                        public const C = 1;
                    }
                    PHP,
                'error_message' => 'MissingClassConstType - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:18',
                'error_levels' => [],
                'php_version' => '8.3',
            ],
            'no type; >= PHP 8.3; non-final interface constant' => [
                'code' => <<<'PHP'
                    <?php
                    interface I {
                        public const B = 0;
                    }
                    PHP,
                'error_message' => MissingClassConstType::getIssueType(),
                'error_levels' => [],
                'php_version' => '8.3',
            ],
        ];
    }
}
