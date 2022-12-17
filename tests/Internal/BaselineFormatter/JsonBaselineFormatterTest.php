<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\BaselineFormatter;

use PHPUnit\Framework\TestCase;
use Psalm\ErrorBaseline;
use Psalm\Internal\BaselineFormatter\JsonBaselineFormatter;

use function define;
use function defined;

/**
 * @psalm-import-type psalmFormattedBaseline from ErrorBaseline
 */
class JsonBaselineFormatterTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!defined('PSALM_VERSION')) {
            define('PSALM_VERSION', '0.0.0');
        }
    }

    /**
     * @dataProvider provideForTestRead
     */
    public function testRead(string $content, array $expectedResult): void
    {
        $sut = new JsonBaselineFormatter();
        $this->assertSame($expectedResult, $sut->read($content));
    }

    /**
     * @return iterable<int, list{string, array}>
     */
    public function provideForTestRead(): iterable
    {
        yield [
            <<<'JSON'
                {
                    "files": {
                        "sample/sample-file.php": {
                            "MixedAssignment": [
                                "foo",
                                "bar"
                            ],
                            "InvalidReturnStatement": [
                                "foo"
                            ]
                        },
                        "sample/sample-file2.php": {
                            "PossiblyUnusedMethod": [
                                "foo",
                                "bar"
                            ]
                        }
                    }
                }
                JSON,
            [
                'sample/sample-file.php' => [
                    'MixedAssignment' => ['o' => 2, 's' => ['foo', 'bar']],
                    'InvalidReturnStatement' => ['o' => 1, 's' => ['foo']],
                ],
                'sample/sample-file2.php' => [
                    'PossiblyUnusedMethod' => ['o' => 2, 's' => ['foo', 'bar']],
                ],
            ],
        ];
    }

    /**
     * @dataProvider provideForTestFormat
     * @param psalmFormattedBaseline $grouped_issues
     */
    public function testFormat(array $grouped_issues, string $expectedResult): void
    {
        $sut = new JsonBaselineFormatter();
        $this->assertSame($expectedResult, $sut->format($grouped_issues, false));
    }

    /**
     * @return iterable<int, list{psalmFormattedBaseline, string}>
     */
    public function provideForTestFormat(): iterable
    {
        yield [
            [],
            <<<'JSON'
                {
                    "psalm_version": "0.0.0"
                }
                JSON,
        ];
        yield [
            [
                'sample/sample-file.php' => [
                    'MixedAssignment' => ['o' => 2, 's' => ['foo', 'bar']],
                    'InvalidReturnStatement' => ['o' => 1, 's' => []],
                ],
                'sample/sample-file2.php' => [
                    'PossiblyUnusedMethod' => ['o' => 2, 's' => ['foo', 'bar']],
                ],
            ],
            <<<'JSON'
                {
                    "psalm_version": "0.0.0",
                    "files": {
                        "sample\/sample-file.php": {
                            "MixedAssignment": [
                                "bar",
                                "foo"
                            ],
                            "InvalidReturnStatement": []
                        },
                        "sample\/sample-file2.php": {
                            "PossiblyUnusedMethod": [
                                "bar",
                                "foo"
                            ]
                        }
                    }
                }
                JSON,
        ];
    }
}
