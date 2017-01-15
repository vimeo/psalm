<?php
namespace Psalm\Tests;

use Psalm\Checker\FileChecker;
use Psalm\Checker\ProjectChecker;
use Psalm\IssueBuffer;

class JsonOutputTest extends TestCase
{
    /**
     * @return void
     */
    public function setUp()
    {
        // `TestCase::setUp()` creates its own ProjectChecker and Config instance, but we don't want to do that in this
        // case, so don't run a `parent::setUp()` call here.
        FileChecker::clearCache();
        $this->project_checker = new ProjectChecker(false, true, ProjectChecker::TYPE_JSON);

        $config = new TestConfig();
        $config->throw_exception = false;
        $config->stop_on_first_error = false;
        $this->project_checker->setConfig($config);
    }

    /**
     * @dataProvider providerTestJsonOutputErrors
     * @param string $code
     * @param string $message
     * @param integer $line_number
     * @param string $error
     * @return void
     */
    public function testJsonOutputErrors($code, $message, $line_number, $error)
    {
        $this->project_checker->registerFile('somefile.php', $code);

        $file_checker = new FileChecker('somefile.php', $this->project_checker);
        $file_checker->visitAndAnalyzeMethods();
        $issue_data = IssueBuffer::getIssueData()[0];

        $this->assertSame('somefile.php', $issue_data['file_path']);
        $this->assertSame('error', $issue_data['type']);
        $this->assertSame($message, $issue_data['message']);
        $this->assertSame($line_number, $issue_data['line_number']);
        $this->assertSame(
            $error,
            substr($code, $issue_data['from'], $issue_data['to'] - $issue_data['from'])
        );
    }
}
