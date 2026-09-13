<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Issue\MissingClassConstType;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class MissingClassConstTypeTest extends TestCase
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
    public function providerInvalidCodeParse(): iterable
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
        ];
    }
}
