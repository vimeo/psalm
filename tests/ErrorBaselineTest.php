<?php

namespace Psalm\Tests;

use DOMDocument;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Psalm\ErrorBaseline;
use Psalm\Exception\ConfigException;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\Internal\Provider\FileProvider;
use Psalm\Internal\RuntimeCaches;

use const LIBXML_NOBLANKS;

class ErrorBaselineTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var FileProvider&MockInterface */
    private $fileProvider;

    public function setUp(): void
    {
        RuntimeCaches::clearAll();
        $this->fileProvider = Mockery::mock(FileProvider::class);
    }

    public function testLoadShouldParseXmlBaselineToPhpArray(): void
    {
        $baselineFilePath = 'baseline.xml';

        $this->fileProvider->allows()->fileExists($baselineFilePath)->andReturns(true);
        $this->fileProvider->allows()->getContents($baselineFilePath)->andReturns(
            '<?xml version="1.0" encoding="UTF-8"?>
            <files>
              <file src="sample/sample-file.php">
                <InvalidReturnStatement occurrences="1"/>
              </file>
              <file src="sample\sample-file2.php">
                <PossiblyUnusedMethod occurrences="2">
                  <code>foo</code>
                  <code>bar</code>
                </PossiblyUnusedMethod>
              </file>
            </files>'
        );

        $expectedParsedBaseline = [
            'sample/sample-file.php' => [
                'InvalidReturnStatement' => ['o' => 1, 's' => []],
            ],
            'sample/sample-file2.php' => [
                'PossiblyUnusedMethod' => ['o' => 2, 's' => ['foo', 'bar']],
            ],
        ];

        $this->assertSame(
            $expectedParsedBaseline,
            ErrorBaseline::read($this->fileProvider, $baselineFilePath)
        );
    }

    public function testLoadShouldThrowExceptionWhenFilesAreNotDefinedInBaselineFile(): void
    {
        $this->expectException(ConfigException::class);

        $baselineFile = 'baseline.xml';

        $this->fileProvider->allows()->fileExists($baselineFile)->andReturns(true);
        $this->fileProvider->allows()->getContents($baselineFile)->andReturns(
            '<?xml version="1.0" encoding="UTF-8"?>
             <other>
             </other>
            '
        );

        ErrorBaseline::read($this->fileProvider, $baselineFile);
    }

    public function testLoadShouldThrowExceptionWhenBaselineFileDoesNotExist(): void
    {
        $this->expectException(ConfigException::class);

        $baselineFile = 'baseline.xml';

        $this->fileProvider->expects()->fileExists($baselineFile)->andReturns(false);

        ErrorBaseline::read($this->fileProvider, $baselineFile);
    }

    public function testCountTotalIssuesShouldReturnCorrectNumber(): void
    {
        $existingIssues = [
            'sample/sample-file.php' => [
                'MixedOperand' => ['o' => 2, 's' => []],
            ],
            'sample/sample-file2.php' => [
                'TypeCoercion' => ['o' => 1, 's' => []],
            ],
        ];

        $totalIssues = ErrorBaseline::countTotalIssues($existingIssues);

        $this->assertSame($totalIssues, 3);
    }

    public function testCreateShouldAggregateIssuesPerFile(): void
    {
        $baselineFile = 'baseline.xml';

        $this->fileProvider = Mockery::spy(FileProvider::class);


        ErrorBaseline::create(
            $this->fileProvider,
            $baselineFile,
            [
                'sample/sample-file.php' => [
                    new IssueData(
                        'error',
                        0,
                        0,
                        'MixedOperand',
                        'Message',
                        'sample/sample-file.php',
                        'sample/sample-file.php',
                        'bing',
                        'bing',
                        0,
                        0,
                        0,
                        0,
                        0,
                        0
                    ),
                    new IssueData(
                        'info',
                        0,
                        0,
                        'AssignmentToVoid',
                        'Message',
                        'sample/sample-file.php',
                        'sample/sample-file.php',
                        'bong',
                        'bong',
                        0,
                        0,
                        0,
                        0,
                        0,
                        0
                    ),
                ],
                'sample/sample-file2.php' => [
                    new IssueData(
                        'error',
                        0,
                        0,
                        'TypeCoercion',
                        'Message',
                        'sample/sample-file2.php',
                        'sample/sample-file2.php',
                        'hardy' . "\n",
                        'hardy' . "\n",
                        0,
                        0,
                        0,
                        0,
                        0,
                        0
                    ),
                ],
            ],
            false
        );

        $this->fileProvider->shouldHaveReceived()
            ->setContents(
                $baselineFile,
                Mockery::on(function (string $document): bool {
                    $baselineDocument = new DOMDocument();
                    $baselineDocument->loadXML($document, LIBXML_NOBLANKS);

                    /** @var DOMElement[] $files */
                    $files = $baselineDocument->getElementsByTagName('files')[0]->childNodes;

                    [$file1, $file2] = $files;

                    $this->assertSame('sample/sample-file.php', $file1->getAttribute('src'));
                    $this->assertSame('sample/sample-file2.php', $file2->getAttribute('src'));

                    /** @var DOMElement[] $file1Issues */
                    $file1Issues = $file1->childNodes;
                    /** @var DOMElement[] $file2Issues */
                    $file2Issues = $file2->childNodes;

                    $this->assertSame('MixedOperand', $file1Issues[0]->tagName);
                    $this->assertSame(
                        '1',
                        $file1Issues[0]->getAttribute('occurrences'),
                        'MixedOperand should have occured 1 time'
                    );

                    $this->assertSame('TypeCoercion', $file2Issues[0]->tagName);
                    $this->assertSame(
                        '1',
                        $file2Issues[0]->getAttribute('occurrences'),
                        'TypeCoercion should have occured 1 time'
                    );

                    return true;
                })
            );
    }

    public function testUpdateShouldRemoveExistingIssuesWithoutAddingNewOnes(): void
    {
        $baselineFile = 'baseline.xml';

        $this->fileProvider->allows()->fileExists($baselineFile)->andReturns(true);
        $this->fileProvider->allows()->getContents($baselineFile)->andReturns(
            '<?xml version="1.0" encoding="UTF-8"?>
            <files>
              <file src="sample/sample-file.php">
                <MixedOperand occurrences="1"/>
              </file>
              <file src="sample/sample-file2.php">
                <TypeCoercion occurrences="1"/>
              </file>
            </files>'
        );

        $this->fileProvider->allows()->setContents(Mockery::andAnyOtherArgs());

        $newIssues = [
            'sample/sample-file.php' => [
                new IssueData(
                    'error',
                    0,
                    0,
                    'MixedOperand',
                    'Message',
                    'sample/sample-file.php',
                    'sample/sample-file.php',
                    'bat',
                    'bat',
                    0,
                    0,
                    0,
                    0,
                    0,
                    0
                ),
                new IssueData(
                    'error',
                    0,
                    0,
                    'MixedOperand',
                    'Message',
                    'sample/sample-file.php',
                    'sample/sample-file.php',
                    'bam',
                    'bam',
                    0,
                    0,
                    0,
                    0,
                    0,
                    0
                ),
            ],
            'sample/sample-file2.php' => [
                new IssueData(
                    'error',
                    0,
                    0,
                    'TypeCoercion',
                    'Message',
                    'sample/sample-file2.php',
                    'sample/sample-file2.php',
                    'tar',
                    'tar',
                    0,
                    0,
                    0,
                    0,
                    0,
                    0
                ),
            ],
        ];

        $remainingBaseline = ErrorBaseline::update(
            $this->fileProvider,
            $baselineFile,
            $newIssues,
            false
        );

        $this->assertSame([
            'sample/sample-file.php' => [
                'MixedOperand' => ['o' => 1, 's' => []],
            ],
            'sample/sample-file2.php' => [
                'TypeCoercion' => ['o' => 1, 's' => []],
            ],
        ], $remainingBaseline);
    }

    public function testAddingACommentInBaselineDoesntTriggerNotice(): void
    {
        $baselineFilePath = 'baseline.xml';

        $this->fileProvider->allows()->fileExists($baselineFilePath)->andReturns(true);
        $this->fileProvider->allows()->getContents($baselineFilePath)->andReturns(
            '<?xml version="1.0" encoding="UTF-8"?>
            <files>
              <file src="sample/sample-file.php">
                <!-- here is a comment ! //-->
                <InvalidReturnStatement occurrences="1"/>
              </file>
              <!-- And another one ! //-->
              <file src="sample\sample-file2.php">
                <PossiblyUnusedMethod occurrences="2">
                  <code>foo</code>
                  <code>bar</code>
                </PossiblyUnusedMethod>
              </file>
            </files>'
        );

        $expectedParsedBaseline = [
            'sample/sample-file.php' => [
                'InvalidReturnStatement' => ['o' => 1, 's' => []],
            ],
            'sample/sample-file2.php' => [
                'PossiblyUnusedMethod' => ['o' => 2, 's' => ['foo', 'bar']],
            ],
        ];

        $this->assertSame(
            $expectedParsedBaseline,
            ErrorBaseline::read($this->fileProvider, $baselineFilePath)
        );
    }
}
