<?php
namespace Psalm\Tests;

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psalm\ErrorBaseline;
use Psalm\Exception\ConfigException;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\Internal\Provider\FileProvider;
use Psalm\Internal\RuntimeCaches;

use const LIBXML_NOBLANKS;

class ErrorBaselineTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy */
    private $fileProvider;

    public function setUp() : void
    {
        RuntimeCaches::clearAll();
        $this->fileProvider = $this->prophesize(FileProvider::class);
    }

    public function testLoadShouldParseXmlBaselineToPhpArray(): void
    {
        $baselineFilePath = 'baseline.xml';

        $this->fileProvider->fileExists($baselineFilePath)->willReturn(true);
        $this->fileProvider->getContents($baselineFilePath)->willReturn(
            '<?xml version="1.0" encoding="UTF-8"?>
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
            </files>'
        );

        $expectedParsedBaseline = [
            'sample/sample-file.php' => [
                'MixedAssignment' => ['o' => 2, 's' => ['foo', 'bar']],
                'InvalidReturnStatement' => ['o' => 1, 's' => []],
            ],
            'sample/sample-file2.php' => [
                'PossiblyUnusedMethod' => ['o' => 2, 's' => ['foo', 'bar']],
            ],
        ];

        $this->assertSame(
            $expectedParsedBaseline,
            ErrorBaseline::read($this->fileProvider->reveal(), $baselineFilePath)
        );
    }

    public function testLoadShouldIgnoreLineEndingsInIssueSnippet(): void
    {
        $baselineFilePath = 'baseline.xml';

        $this->fileProvider->fileExists($baselineFilePath)->willReturn(true);
        $this->fileProvider->getContents($baselineFilePath)->willReturn(
            "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
            <files>
              <file src=\"sample/sample-file.php\">
                <MixedAssignment occurrences=\"1\">
                  <code>foo\r</code>
                </MixedAssignment>
              </file>
            </files>"
        );

        $expectedParsedBaseline = [
            'sample/sample-file.php' => [
                'MixedAssignment' => ['o' => 1, 's' => ['foo']],
            ],
        ];

        $this->assertSame(
            $expectedParsedBaseline,
            ErrorBaseline::read($this->fileProvider->reveal(), $baselineFilePath)
        );
    }

    public function testLoadShouldThrowExceptionWhenFilesAreNotDefinedInBaselineFile(): void
    {
        $this->expectException(ConfigException::class);

        $baselineFile = 'baseline.xml';

        $this->fileProvider->fileExists($baselineFile)->willReturn(true);
        $this->fileProvider->getContents($baselineFile)->willReturn(
            '<?xml version="1.0" encoding="UTF-8"?>
             <other>
             </other>
            '
        );

        ErrorBaseline::read($this->fileProvider->reveal(), $baselineFile);
    }

    public function testLoadShouldThrowExceptionWhenBaselineFileDoesNotExist(): void
    {
        $this->expectException(ConfigException::class);

        $baselineFile = 'baseline.xml';

        $this->fileProvider->fileExists($baselineFile)->willReturn(false);

        ErrorBaseline::read($this->fileProvider->reveal(), $baselineFile);
    }

    public function testCountTotalIssuesShouldReturnCorrectNumber(): void
    {
        $existingIssues = [
            'sample/sample-file.php' => [
                'MixedAssignment' => ['o' => 2, 's' => ['bar']],
                'MixedOperand' => ['o' => 2, 's' => []],
            ],
            'sample/sample-file2.php' => [
                'TypeCoercion' => ['o' => 1, 's' => []],
            ],
        ];

        $totalIssues = ErrorBaseline::countTotalIssues($existingIssues);

        $this->assertSame($totalIssues, 5);
    }

    public function testCreateShouldAggregateIssuesPerFile(): void
    {
        $baselineFile = 'baseline.xml';

        $documentContent = null;

        $this->fileProvider->setContents(
            $baselineFile,
            Argument::that(function (string $document) use (&$documentContent): bool {
                $documentContent = $document;

                return true;
            })
        );

        ErrorBaseline::create(
            $this->fileProvider->reveal(),
            $baselineFile,
            [
                'sample/sample-file.php' => [
                    new IssueData(
                        'error',
                        0,
                        0,
                        'MixedAssignment',
                        'Message',
                        'sample/sample-file.php',
                        'sample/sample-file.php',
                        'foo',
                        'foo',
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
                        'MixedAssignment',
                        'Message',
                        'sample/sample-file.php',
                        'sample/sample-file.php',
                        'bar',
                        'bar',
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
                        'MixedAssignment',
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
                        'MixedAssignment',
                        'Message',
                        'sample/sample-file2.php',
                        'sample/sample-file2.php',
                        'boardy',
                        'boardy',
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
                        'MixedAssignment',
                        'Message',
                        'sample/sample-file2.php',
                        'sample/sample-file2.php',
                        'bardy',
                        'bardy',
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

        $baselineDocument = new \DOMDocument();
        $baselineDocument->loadXML($documentContent, LIBXML_NOBLANKS);

        /** @var \DOMElement[] $files */
        $files = $baselineDocument->getElementsByTagName('files')[0]->childNodes;

        [$file1, $file2] = $files;

        $this->assertSame('sample/sample-file.php', $file1->getAttribute('src'));
        $this->assertSame('sample/sample-file2.php', $file2->getAttribute('src'));

        /** @var \DOMElement[] $file1Issues */
        $file1Issues = $file1->childNodes;
        /** @var \DOMElement[] $file2Issues */
        $file2Issues = $file2->childNodes;

        $this->assertSame('MixedAssignment', $file1Issues[0]->tagName);
        $this->assertSame('3', $file1Issues[0]->getAttribute('occurrences'));
        $this->assertSame('MixedOperand', $file1Issues[1]->tagName);
        $this->assertSame('1', $file1Issues[1]->getAttribute('occurrences'));

        $this->assertSame('MixedAssignment', $file2Issues[0]->tagName);
        $this->assertSame('2', $file2Issues[0]->getAttribute('occurrences'));
        $this->assertSame('TypeCoercion', $file2Issues[1]->tagName);
        $this->assertSame('1', $file2Issues[1]->getAttribute('occurrences'));
    }

    public function testUpdateShouldRemoveExistingIssuesWithoutAddingNewOnes(): void
    {
        $baselineFile = 'baseline.xml';

        $this->fileProvider->fileExists($baselineFile)->willReturn(true);
        $this->fileProvider->getContents($baselineFile)->willReturn(
            '<?xml version="1.0" encoding="UTF-8"?>
            <files>
              <file src="sample/sample-file.php">
                <MixedAssignment occurrences="3">
                    <code>bar</code>
                    <code>bat</code>
                </MixedAssignment>
                <MixedOperand occurrences="1"/>
              </file>
              <file src="sample/sample-file2.php">
                <MixedAssignment occurrences="2"/>
                <TypeCoercion occurrences="1"/>
              </file>
              <file src="sample/sample-file3.php">
                <MixedAssignment occurrences="1"/>
              </file>
            </files>'
        );
        $this->fileProvider->setContents(Argument::cetera());

        $newIssues = [
            'sample/sample-file.php' => [
                new IssueData(
                    'error',
                    0,
                    0,
                    'MixedAssignment',
                    'Message',
                    'sample/sample-file.php',
                    'sample/sample-file.php',
                    'foo',
                    'foo',
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
                    'MixedAssignment',
                    'Message',
                    'sample/sample-file.php',
                    'sample/sample-file.php',
                    'bar',
                    'bar',
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
            $this->fileProvider->reveal(),
            $baselineFile,
            $newIssues,
            false
        );

        $this->assertSame([
            'sample/sample-file.php' => [
                'MixedAssignment' => ['o' => 2, 's' => ['bar']],
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

        $this->fileProvider->fileExists($baselineFilePath)->willReturn(true);
        $this->fileProvider->getContents($baselineFilePath)->willReturn(
            '<?xml version="1.0" encoding="UTF-8"?>
            <files>
              <file src="sample/sample-file.php">
                <!-- here is a comment ! //-->
                <MixedAssignment occurrences="2">
                  <code>foo</code>
                  <code>bar</code>
                </MixedAssignment>
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
                'MixedAssignment' => ['o' => 2, 's' => ['foo', 'bar']],
                'InvalidReturnStatement' => ['o' => 1, 's' => []],
            ],
            'sample/sample-file2.php' => [
                'PossiblyUnusedMethod' => ['o' => 2, 's' => ['foo', 'bar']],
            ],
        ];

        $this->assertSame(
            $expectedParsedBaseline,
            ErrorBaseline::read($this->fileProvider->reveal(), $baselineFilePath)
        );
    }
}
