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
        $this->file_provider = new Provider\FakeFileProvider();

        $this->project_checker = new \Psalm\Checker\ProjectChecker(
            $this->file_provider,
            new Provider\FakeCacheProvider(),
            false,
            true,
            ProjectChecker::TYPE_JSON
        );

        $config = new TestConfig();
        $config->throw_exception = false;
        $config->stop_on_first_error = false;
        $this->project_checker->setConfig($config);
    }

    /**
     * @dataProvider providerTestJsonOutputErrors
     *
     * @param string $code
     * @param string $message
     * @param int $line_number
     * @param string $error
     *
     * @return void
     */
    public function testJsonOutputErrors($code, $message, $line_number, $error)
    {
        $this->addFile('somefile.php', $code);

        $file_checker = new FileChecker('somefile.php', $this->project_checker);
        $file_checker->visitAndAnalyzeMethods();
        $issue_data = IssueBuffer::getIssuesData()[0];

        $this->assertSame('somefile.php', $issue_data['file_path']);
        $this->assertSame('error', $issue_data['severity']);
        $this->assertSame($message, $issue_data['message']);
        $this->assertSame($line_number, $issue_data['line_number']);
        $this->assertSame(
            $error,
            substr($code, $issue_data['from'], $issue_data['to'] - $issue_data['from'])
        );
    }

    /**
     * @return array
     */
    public function providerTestJsonOutputErrors()
    {
        return [
            'returnTypeError' => [
                '<?php
                    function fooFoo(int $a) : string {
                        return $a + 1;
                    }',
                'message' => "The declared return type 'string' for fooFoo is incorrect, got 'int'",
                'line' => 2,
                'error' => 'string',
            ],
            'undefinedVar' => [
                '<?php
                    function fooFoo(int $a) : int {
                        return $b + 1;
                    }',
                'message' => 'Cannot find referenced variable $b',
                'line' => 3,
                'error' => '$b',
            ],
            'unknownParamClass' => [
                '<?php
                    function fooFoo(Badger\Bodger $a) : Badger\Bodger {
                        return $a;
                    }',
                'message' => 'Class or interface Badger\\Bodger does not exist',
                'line' => 2,
                'error' => 'Badger\\Bodger',
            ],
            'missingReturnType' => [
                '<?php
                    function fooFoo() {
                        return "hello";
                    }',
                'message' => 'Method fooFoo does not have a return type, expecting string',
                'line' => 2,
                'error' => 'function fooFoo() {',
            ],
            'wrongMultilineReturnType' => [
                '<?php
                    /**
                     * @return int
                     */
                    function fooFoo() {
                        return "hello";
                    }',
                'message' => 'The declared return type \'int\' for fooFoo is incorrect, got \'string\'',
                'line' => 3,
                'error' => '@return int',
            ],
            'wrongSingleLineReturnType' => [
                '<?php
                    /** @return int */
                    function fooFoo() {
                        return "hello";
                    }',
                'message' => 'The declared return type \'int\' for fooFoo is incorrect, got \'string\'',
                'line' => 2,
                'error' => '@return int',
            ],
        ];
    }
}
