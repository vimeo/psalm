<?php
namespace Psalm\Tests;

use LSS\XML2Array;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Context;
use Psalm\IssueBuffer;

class ReportOutputTest extends TestCase
{
    /**
     * @return void
     */
    public function setUp()
    {
        // `TestCase::setUp()` creates its own ProjectAnalyzer and Config instance, but we don't want to do that in this
        // case, so don't run a `parent::setUp()` call here.
        FileAnalyzer::clearCache();
        $this->file_provider = new Provider\FakeFileProvider();

        $config = new TestConfig();
        $config->throw_exception = false;

        $this->project_checker = new ProjectAnalyzer(
            $config,
            new \Psalm\Internal\Provider\Providers(
                $this->file_provider,
                new Provider\FakeParserCacheProvider()
            ),
            false
        );
        $this->project_checker->reports['json'] = __DIR__ . '/test-report.json';
    }

    /**
     * @return void
     */
    public function testReportFormatValid()
    {
        $config = new TestConfig();
        $config->throw_exception = false;

        // No exception
        foreach (['.xml', '.txt', '.json', '.emacs'] as $extension) {
            new ProjectAnalyzer(
                $config,
                new \Psalm\Internal\Provider\Providers(
                    $this->file_provider,
                    new Provider\FakeParserCacheProvider()
                ),
                false,
                true,
                ProjectAnalyzer::TYPE_CONSOLE,
                1,
                false,
                '/tmp/report' . $extension
            );
        }
    }

    /**
     * @expectedException \UnexpectedValueException
     *
     * @return void
     */
    public function testReportFormatException()
    {
        $config = new TestConfig();
        $config->throw_exception = false;

        new ProjectAnalyzer(
            $config,
            new \Psalm\Internal\Provider\Providers(
                $this->file_provider,
                new Provider\FakeParserCacheProvider()
            ),
            false,
            true,
            ProjectAnalyzer::TYPE_CONSOLE,
            1,
            false,
            '/tmp/report.log'
        );
    }

    /**
     * @return void
     */
    public function testJsonOutputForGetPsalmDotOrg()
    {
        $file_contents = '<?php
function psalmCanVerify(int $your_code): ?string {
  return $as_you . "type";
}

// and it supports PHP 5.4 - 7.1
echo CHANGE_ME;

if (rand(0, 100) > 10) {
  $a = 5;
} else {
  //$a = 2;
}

echo $a;';

        $this->addFile(
            'somefile.php',
            $file_contents
        );

        $this->analyzeFile('somefile.php', new Context());

        $issue_data = [
            [
                'severity' => 'error',
                'line_from' => 3,
                'line_to' => 3,
                'type' => 'UndefinedVariable',
                'message' => 'Cannot find referenced variable $as_you',
                'file_name' => 'somefile.php',
                'file_path' => 'somefile.php',
                'snippet' => '  return $as_you . "type";',
                'selected_text' => '$as_you',
                'from' => 66,
                'to' => 73,
                'snippet_from' => 57,
                'snippet_to' => 83,
                'column_from' => 10,
                'column_to' => 17,
            ],
            [
                'severity' => 'error',
                'line_from' => 2,
                'line_to' => 2,
                'type' => 'MixedInferredReturnType',
                'message' => 'Could not verify return type \'string|null\' for psalmCanVerify',
                'file_name' => 'somefile.php',
                'file_path' => 'somefile.php',
                'snippet' => 'function psalmCanVerify(int $your_code): ?string {
  return $as_you . "type";
}',
                'selected_text' => '?string',
                'from' => 47,
                'to' => 54,
                'snippet_from' => 6,
                'snippet_to' => 85,
                'column_from' => 42,
                'column_to' => 49,
            ],
            [
                'severity' => 'error',
                'line_from' => 7,
                'line_to' => 7,
                'type' => 'UndefinedConstant',
                'message' => 'Const CHANGE_ME is not defined',
                'file_name' => 'somefile.php',
                'file_path' => 'somefile.php',
                'snippet' => 'echo CHANGE_ME;',
                'selected_text' => 'CHANGE_ME',
                'from' => 125,
                'to' => 134,
                'snippet_from' => 120,
                'snippet_to' => 135,
                'column_from' => 6,
                'column_to' => 15,
            ],
            [
                'severity' => 'error',
                'line_from' => 15,
                'line_to' => 15,
                'type' => 'PossiblyUndefinedGlobalVariable',
                'message' => 'Possibly undefined global variable $a, first seen on line 10',
                'file_name' => 'somefile.php',
                'file_path' => 'somefile.php',
                'snippet' => 'echo $a',
                'selected_text' => '$a',
                'from' => 201,
                'to' => 203,
                'snippet_from' => 196,
                'snippet_to' => 203,
                'column_from' => 6,
                'column_to' => 8,
            ],
        ];

        $emacs = 'somefile.php:3:10:error - Cannot find referenced variable $as_you
somefile.php:2:42:error - Could not verify return type \'string|null\' for psalmCanVerify
somefile.php:7:6:error - Const CHANGE_ME is not defined
somefile.php:15:6:error - Possibly undefined global variable $a, first seen on line 10
';
        $this->assertSame(
            $issue_data,
            json_decode(IssueBuffer::getOutput(ProjectAnalyzer::TYPE_JSON, false), true)
        );
        $this->assertSame(
            $emacs,
            IssueBuffer::getOutput(ProjectAnalyzer::TYPE_EMACS, false)
        );
        // FIXME: The XML parser only return strings, all int value are casted, so the assertSame failed
        //$this->assertSame(
        //    ['report' => ['item' => $issue_data]],
        //    XML2Array::createArray(IssueBuffer::getOutput(ProjectAnalyzer::TYPE_XML, false), LIBXML_NOCDATA)
        //);
    }

    /**
     * @return void
     */
    public function testEmptyReportIfNotError()
    {
        $this->addFile(
            'somefile.php',
            '<?php ?>'
        );

        $this->analyzeFile('somefile.php', new Context());
        $this->assertSame(
            '[]
',
            IssueBuffer::getOutput(ProjectAnalyzer::TYPE_JSON, false)
        );
        $this->assertSame(
            '',
            IssueBuffer::getOutput(ProjectAnalyzer::TYPE_EMACS, false)
        );
        $this->assertSame(
            '<?xml version="1.0" encoding="UTF-8"?>
<report>
  <item/>
</report>
',
            IssueBuffer::getOutput(ProjectAnalyzer::TYPE_XML, false)
        );

        ob_start();
        IssueBuffer::finish($this->project_checker, true, 0);
        ob_end_clean();
        $this->assertFileExists(__DIR__ . '/test-report.json');
        $this->assertSame('[]
', file_get_contents(__DIR__ . '/test-report.json'));
        unlink(__DIR__ . '/test-report.json');
    }
}
