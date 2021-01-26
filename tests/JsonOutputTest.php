<?php
namespace Psalm\Tests;

use Psalm\Context;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\RuntimeCaches;
use Psalm\IssueBuffer;
use Psalm\Tests\Internal\Provider;
use function substr;

class JsonOutputTest extends TestCase
{
    public function setUp() : void
    {
        // `TestCase::setUp()` creates its own ProjectAnalyzer and Config instance, but we don't want to do that in this
        // case, so don't run a `parent::setUp()` call here.
        RuntimeCaches::clearAll();
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
     */
    public function testJsonOutputErrors($code, $message, $line_number, $error): void
    {
        $this->addFile('somefile.php', $code);
        $this->analyzeFile('somefile.php', new Context());
        $issue_data = IssueBuffer::getIssuesData()['somefile.php'][0];

        $this->assertSame('somefile.php', $issue_data->file_path);
        $this->assertSame('error', $issue_data->severity);
        $this->assertSame($message, $issue_data->message);
        $this->assertSame($line_number, $issue_data->line_from);
        $this->assertSame(
            $error,
            substr($code, $issue_data->from, $issue_data->to - $issue_data->from)
        );
    }

    /**
     * @return array<string,array{string,message:string,line:int,error:string}>
     */
    public function providerTestJsonOutputErrors(): array
    {
        return [
            'returnTypeError' => [
                '<?php
                    function fooFoo(int $a): string {
                        return $a + 1;
                    }',
                'message' => "The inferred type 'int' does not match the declared return type 'string' for fooFoo",
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
                'message' => 'Method fooFoo does not have a return type, expecting "hello"',
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
                'message' => "The inferred type '\"hello\"' does not match the declared return type 'int' for fooFoo",
                'line' => 6,
                'error' => '"hello"',
            ],
            'assertCancelsMixedAssignment' => [
                '<?php
                    $a = $_GET["hello"];
                    assert(is_int($a));
                    if (is_int($a)) {}',
                'message' => 'Docblock-defined type int for $a is always int',
                'line' => 4,
                'error' => 'is_int($a)',
            ],
        ];
    }
}
