<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\BaselineFormatter;

use PHPUnit\Framework\TestCase;
use Psalm\ErrorBaseline;
use Psalm\Exception\ConfigException;
use Psalm\Internal\BaselineFormatter\XmlBaselineFormatter;

use function define;
use function defined;

/**
 * @psalm-import-type psalmFormattedBaseline from ErrorBaseline
 */
class XmlBaselineFormatterTest extends TestCase
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
        $sut = new XmlBaselineFormatter();
        $this->assertSame($expectedResult, $sut->read($content));
    }

    /**
     * @return iterable<int, list{string, array}>
     */
    public function provideForTestRead(): iterable
    {
        yield [
            <<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <files>
                  <file src="sample/sample-file.php">
                    <MixedAssignment occurrences="2">
                      <code>foo</code>
                      <code>bar</code>
                    </MixedAssignment>
                    <InvalidReturnStatement occurrences="1"/>
                  </file>
                  <file src="sample\sample-file2.php">
                    <PossiblyUnusedMethod occurrences="2">
                      <code>foo</code>
                      <code>bar</code>
                    </PossiblyUnusedMethod>
                  </file>
                </files>
                XML,
            [
                'sample/sample-file.php' => [
                    'MixedAssignment' => ['o' => 2, 's' => ['foo', 'bar']],
                    'InvalidReturnStatement' => ['o' => 1, 's' => []],
                ],
                'sample/sample-file2.php' => [
                    'PossiblyUnusedMethod' => ['o' => 2, 's' => ['foo', 'bar']],
                ],
            ],
        ];
    }

    public function testExceptionOnEmptyContent(): void
    {
        $sut = new XmlBaselineFormatter();
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Baseline file is empty.');
        $sut->read('');
    }

    public function testExceptionOnNoFilesElement(): void
    {
        $sut = new XmlBaselineFormatter();
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Baseline file does not contain <files>.');
        $sut->read('<?xml version="1.0" encoding="UTF-8"?><bogus/>');
    }

    /**
     * @dataProvider provideForTestFormat
     * @param psalmFormattedBaseline $grouped_issues
     */
    public function testFormat(array $grouped_issues, string $expectedResult): void
    {
        $sut = new XmlBaselineFormatter();
        $this->assertSame($expectedResult, $sut->format($grouped_issues, false));
    }

    /**
     * @return iterable<int, list{psalmFormattedBaseline, string}>
     */
    public function provideForTestFormat(): iterable
    {
        yield [
            [],
            <<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <files psalm-version="0.0.0"/>

                XML,
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
            <<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <files psalm-version="0.0.0">
                  <file src="sample/sample-file.php">
                    <MixedAssignment occurrences="2">
                      <code>bar</code>
                      <code>foo</code>
                    </MixedAssignment>
                    <InvalidReturnStatement occurrences="1"/>
                  </file>
                  <file src="sample/sample-file2.php">
                    <PossiblyUnusedMethod occurrences="2">
                      <code>bar</code>
                      <code>foo</code>
                    </PossiblyUnusedMethod>
                  </file>
                </files>

                XML,
        ];
    }
}
