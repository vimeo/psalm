<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Issue\MissingClassConstType;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class MissingClassConstTypeTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

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
