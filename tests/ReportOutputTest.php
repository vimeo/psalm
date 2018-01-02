<?php
namespace Psalm\Tests;

use LSS\XML2Array;
use Psalm\Checker\FileChecker;
use Psalm\Checker\ProjectChecker;
use Psalm\IssueBuffer;

class ReportOutputTest extends TestCase
{
    /**
     * @return void
     */
    public function setUp()
    {
        // `TestCase::setUp()` creates its own ProjectChecker and Config instance, but we don't want to do that in this
        // case, so don't run a `parent::setUp()` call here.
        FileChecker::clearCache();
        $this->file_provider = new Provider\FakeFileProvider();

        $this->project_checker = new ProjectChecker(
            $this->file_provider,
            new Provider\FakeParserCacheProvider(),
            false
        );
        $this->project_checker->reports['json'] = __DIR__ . '/test-report.json';

        $config = new TestConfig();
        $config->throw_exception = false;
        $config->stop_on_first_error = false;
        $this->project_checker->setConfig($config);
    }

    /**
     * @return void
     */
    public function testReportFormatValid()
    {
        // No exception
        foreach (['.xml', '.txt', '.json', '.emacs'] as $extension) {
            new ProjectChecker(
                $this->file_provider,
                new Provider\FakeParserCacheProvider(),
                false,
                true,
                ProjectChecker::TYPE_CONSOLE,
                1,
                false,
                false,
                false,
                null,
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
        new ProjectChecker(
            $this->file_provider,
            new Provider\FakeParserCacheProvider(),
            false,
            true,
            ProjectChecker::TYPE_CONSOLE,
            1,
            false,
            false,
            false,
            null,
            '/tmp/report.log'
        );
    }

    /**
     * @return void
     */
    public function testJsonOutputForGetPsalmDotOrg()
    {
        $file_contents = '<?php
function psalmCanVerify(int $your_code) : ?string {
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

        $file_checker = new FileChecker('somefile.php', $this->project_checker);
        $file_checker->visitAndAnalyzeMethods();
        $issue_data = [
            [
                'severity' => 'error',
                'line_number' => 7,
                'type' => 'UndefinedConstant',
                'message' => 'Const CHANGE_ME is not defined',
                'file_name' => 'somefile.php',
                'file_path' => 'somefile.php',
                'snippet' => 'echo CHANGE_ME;',
                'selected_text' => 'CHANGE_ME',
                'from' => 126,
                'to' => 135,
                'snippet_from' => 121,
                'snippet_to' => 136,
                'column' => 6,
            ],
            [
                'severity' => 'error',
                'line_number' => 15,
                'type' => 'PossiblyUndefinedGlobalVariable',
                'message' => 'Possibly undefined global variable $a, first seen on line 10',
                'file_name' => 'somefile.php',
                'file_path' => 'somefile.php',
                'snippet' => 'echo $a',
                'selected_text' => '$a',
                'from' => 202,
                'to' => 204,
                'snippet_from' => 197,
                'snippet_to' => 204,
                'column' => 6,
            ],
            [
                'severity' => 'error',
                'line_number' => 3,
                'type' => 'UndefinedVariable',
                'message' => 'Cannot find referenced variable $as_you',
                'file_name' => 'somefile.php',
                'file_path' => 'somefile.php',
                'snippet' => '  return $as_you . "type";',
                'selected_text' => '$as_you',
                'from' => 67,
                'to' => 74,
                'snippet_from' => 58,
                'snippet_to' => 84,
                'column' => 10,
            ],
            [
                'severity' => 'error',
                'line_number' => 2,
                'type' => 'MixedInferredReturnType',
                'message' => 'Could not verify return type \'string|null\' for psalmCanVerify',
                'file_name' => 'somefile.php',
                'file_path' => 'somefile.php',
                'snippet' => 'function psalmCanVerify(int $your_code) : ?string {
  return $as_you . "type";
}',
                'selected_text' => '?string',
                'from' => 48,
                'to' => 55,
                'snippet_from' => 6,
                'snippet_to' => 86,
                'column' => 43,
            ],
        ];
        $emacs = 'somefile.php:7:6:error - Const CHANGE_ME is not defined
somefile.php:15:6:error - Possibly undefined global variable $a, first seen on line 10
somefile.php:3:10:error - Cannot find referenced variable $as_you
somefile.php:2:43:error - Could not verify return type \'string|null\' for psalmCanVerify
';
        $this->assertSame(
            $issue_data,
            json_decode(IssueBuffer::getOutput(ProjectChecker::TYPE_JSON, false), true)
        );
        $this->assertSame(
            $emacs,
            IssueBuffer::getOutput(ProjectChecker::TYPE_EMACS, false)
        );
        // FIXME: The XML parser only return strings, all int value are casted, so the assertSame failed
        //$this->assertSame(
        //    ['report' => ['item' => $issue_data]],
        //    XML2Array::createArray(IssueBuffer::getOutput(ProjectChecker::TYPE_XML, false), LIBXML_NOCDATA)
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

        $file_checker = new FileChecker('somefile.php', $this->project_checker);
        $file_checker->visitAndAnalyzeMethods();
        $this->assertSame(
            '[]
',
            IssueBuffer::getOutput(ProjectChecker::TYPE_JSON, false)
        );
        $this->assertSame(
            '',
            IssueBuffer::getOutput(ProjectChecker::TYPE_EMACS, false)
        );
        $this->assertSame(
            '<?xml version="1.0" encoding="UTF-8"?>
<report>
  <item/>
</report>
',
            IssueBuffer::getOutput(ProjectChecker::TYPE_XML, false)
        );

        IssueBuffer::finish($this->project_checker, true, null, ['somefile.php' => true]);
        $this->assertFileExists(__DIR__ . '/test-report.json');
        $this->assertSame('[]
', file_get_contents(__DIR__ . '/test-report.json'));
        unlink(__DIR__ . '/test-report.json');
    }
}
