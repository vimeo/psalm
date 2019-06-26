<?php
namespace Psalm\Tests;

use Psalm\Context;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\IssueBuffer;
use Psalm\Tests\Internal\Provider;
use function substr;

class JsonOutputTest extends TestCase
{
    /**
     * @return void
     */
    public function setUp() : void
    {
        // `TestCase::setUp()` creates its own ProjectAnalyzer and Config instance, but we don't want to do that in this
        // case, so don't run a `parent::setUp()` call here.
        FileAnalyzer::clearCache();
        $this->file_provider = new Provider\FakeFileProvider();

        $config = new TestConfig();
        $config->throw_exception = false;

        $stdout_report_options = new \Psalm\Report\ReportOptions();
        $stdout_report_options->format = \Psalm\Report::TYPE_JSON;

        $this->project_analyzer = new ProjectAnalyzer(
            $config,
            new \Psalm\Internal\Provider\Providers(
                $this->file_provider,
                new Provider\FakeParserCacheProvider()
            ),
            $stdout_report_options
        );

        $this->project_analyzer->getCodebase()->reportUnusedCode();
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
        $this->analyzeFile('somefile.php', new Context());
        $issue_data = IssueBuffer::getIssuesData()[0];

        $this->assertSame('somefile.php', $issue_data['file_path']);
        $this->assertSame('error', $issue_data['severity']);
        $this->assertSame($message, $issue_data['message']);
        $this->assertSame($line_number, $issue_data['line_from']);
        $this->assertSame(
            $error,
            substr($code, $issue_data['from'], $issue_data['to'] - $issue_data['from'])
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

        $this->project_analyzer->checkClassReferences();

        $this->analyzeFile('somefile.php', new Context());

        $issue_data = IssueBuffer::getIssuesData();
        $this->assertSame(
            [
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
                    'type' => 'UnusedParam',
                    'message' => 'Param $your_code is never referenced in this method',
                    'file_name' => 'somefile.php',
                    'file_path' => 'somefile.php',
                    'snippet' => 'function psalmCanVerify(int $your_code): ?string {',
                    'selected_text' => '$your_code',
                    'from' => 34,
                    'to' => 44,
                    'snippet_from' => 6,
                    'snippet_to' => 56,
                    'column_from' => 29,
                    'column_to' => 39,
                ],
                [
                    'severity' => 'error',
                    'line_from' => 2,
                    'line_to' => 2,
                    'type' => 'MixedInferredReturnType',
                    'message' => 'Could not verify return type \'string|null\' for psalmCanVerify',
                    'file_name' => 'somefile.php',
                    'file_path' => 'somefile.php',
                    'snippet' => 'function psalmCanVerify(int $your_code): ?string {',
                    'selected_text' => '?string',
                    'from' => 47,
                    'to' => 54,
                    'snippet_from' => 6,
                    'snippet_to' => 56,
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
            ],
            $issue_data
        );
    }

    /**
     * @return array<string,array{string,message:string,line:int,error:string}>
     */
    public function providerTestJsonOutputErrors()
    {
        return [
            'returnTypeError' => [
                '<?php
                    function fooFoo(int $a): string {
                        return $a + 1;
                    }',
                'message' => "The type 'int' does not match the declared return type 'string' for fooFoo",
                'line' => 3,
                'error' => '$a + 1',
            ],
            'undefinedVar' => [
                '<?php
                    function fooFoo(int $a): int {
                        return $b + 1;
                    }',
                'message' => 'Cannot find referenced variable $b',
                'line' => 3,
                'error' => '$b',
            ],
            'unknownParamClass' => [
                '<?php
                    function fooFoo(Badger\Bodger $a): Badger\Bodger {
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
                'error' => 'fooFoo',
            ],
            'wrongMultilineReturnType' => [
                '<?php
                    /**
                     * @return int
                     */
                    function fooFoo() {
                        return "hello";
                    }',
                'message' => "The type 'string(hello)' does not match the declared return type 'int' for fooFoo",
                'line' => 6,
                'error' => '"hello"',
            ],
        ];
    }
}
