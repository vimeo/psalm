<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Checker\ProjectChecker;
use Psalm\Config;
use Psalm\Context;
use Psalm\IssueBuffer;

class JsonOutputTest extends PHPUnit_Framework_TestCase
{
    /** @var \PhpParser\Parser */
    protected static $parser;

    /** @var \Psalm\Checker\ProjectChecker */
    protected $project_checker;

    /**
     * @return void
     */
    public static function setUpBeforeClass()
    {
        self::$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
    }

    /**
     * @return void
     */
    public function setUp()
    {
        FileChecker::clearCache();
        $this->project_checker = new ProjectChecker(false, true, ProjectChecker::TYPE_JSON);

        $config = new TestConfig();
        $config->throw_exception = false;
        $config->stop_on_first_error = false;
        $this->project_checker->setConfig($config);
    }

    /**
     * @return void
     */
    public function testJsonOutputForReturnTypeError()
    {
        $file_contents = '<?php
        function fooFoo(int $a) : string {
            return $a + 1;
        }';

        $this->project_checker->registerFile(
            'somefile.php',
            $file_contents
        );

        $file_checker = new FileChecker('somefile.php', $this->project_checker);
        $file_checker->visitAndAnalyzeMethods();
        $issue_data = IssueBuffer::getIssueData()[0];
        $this->assertSame('somefile.php', $issue_data['file_path']);
        $this->assertSame('error', $issue_data['type']);
        $this->assertSame("The given return type 'string' for fooFoo is incorrect, got 'int'", $issue_data['message']);
        $this->assertSame(2, $issue_data['line_number']);
        $this->assertSame(
            'string',
            substr($file_contents, $issue_data['from'], $issue_data['to'] - $issue_data['from'])
        );
    }

    /**
     * @return void
     */
    public function testJsonOutputForUndefinedVar()
    {
        $file_contents = '<?php
        function fooFoo(int $a) : int {
            return $b + 1;
        }';

        $this->project_checker->registerFile(
            'somefile.php',
            $file_contents
        );

        $file_checker = new FileChecker('somefile.php', $this->project_checker);
        $file_checker->visitAndAnalyzeMethods();
        $issue_data = IssueBuffer::getIssueData()[0];
        $this->assertSame('somefile.php', $issue_data['file_path']);
        $this->assertSame('error', $issue_data['type']);
        $this->assertSame('Cannot find referenced variable $b', $issue_data['message']);
        $this->assertSame(3, $issue_data['line_number']);
        $this->assertSame(
            '$b',
            substr($file_contents, $issue_data['from'], $issue_data['to'] - $issue_data['from'])
        );
    }

    /**
     * @return void
     */
    public function testJsonOutputForUnknownParamClass()
    {
        $file_contents = '<?php
        function fooFoo(Badger\Bodger $a) : Badger\Bodger {
            return $a;
        }';

        $this->project_checker->registerFile(
            'somefile.php',
            $file_contents
        );

        $file_checker = new FileChecker('somefile.php', $this->project_checker);
        $file_checker->visitAndAnalyzeMethods();
        $issue_data = IssueBuffer::getIssueData()[0];
        $this->assertSame('somefile.php', $issue_data['file_path']);
        $this->assertSame('error', $issue_data['type']);
        $this->assertSame('Class or interface Badger\\Bodger does not exist', $issue_data['message']);
        $this->assertSame(2, $issue_data['line_number']);
        $this->assertSame(
            'Badger\\Bodger',
            substr($file_contents, $issue_data['from'], $issue_data['to'] - $issue_data['from'])
        );
    }

    /**
     * @return void
     */
    public function testJsonOutputForMissingReturnType()
    {
        $file_contents = '<?php
        function fooFoo() {
            return "hello";
        }';

        $this->project_checker->registerFile(
            'somefile.php',
            $file_contents
        );

        $file_checker = new FileChecker('somefile.php', $this->project_checker);
        $file_checker->visitAndAnalyzeMethods();
        $issue_data = IssueBuffer::getIssueData()[0];
        $this->assertSame('somefile.php', $issue_data['file_path']);
        $this->assertSame('error', $issue_data['type']);
        $this->assertSame('Method fooFoo does not have a return type, expecting string', $issue_data['message']);
        $this->assertSame(2, $issue_data['line_number']);
        $this->assertSame(
            'function fooFoo() {',
            substr($file_contents, $issue_data['from'], $issue_data['to'] - $issue_data['from'])
        );
    }

    /**
     * @return void
     */
    public function testJsonOutputForWrongMultilineReturnType()
    {
        $file_contents = '<?php
        /**
         * @return int
         */
        function fooFoo() {
            return "hello";
        }';

        $this->project_checker->registerFile(
            'somefile.php',
            $file_contents
        );

        $file_checker = new FileChecker('somefile.php', $this->project_checker);
        $file_checker->visitAndAnalyzeMethods();
        $issue_data = IssueBuffer::getIssueData()[0];
        $this->assertSame('somefile.php', $issue_data['file_path']);
        $this->assertSame('error', $issue_data['type']);
        $this->assertSame('The given return type \'int\' for fooFoo is incorrect, got \'string\'', $issue_data['message']);
        $this->assertSame(3, $issue_data['line_number']);
        $this->assertSame(
            '@return int',
            substr($file_contents, $issue_data['from'], $issue_data['to'] - $issue_data['from'])
        );
    }

    /**
     * @return void
     */
    public function testJsonOutputForWrongSingleLineReturnType()
    {
        $file_contents = '<?php
        /** @return int */
        function fooFoo() {
            return "hello";
        }';

        $this->project_checker->registerFile(
            'somefile.php',
            $file_contents
        );

        $file_checker = new FileChecker('somefile.php', $this->project_checker);
        $file_checker->visitAndAnalyzeMethods();
        $issue_data = IssueBuffer::getIssueData()[0];
        $this->assertSame('somefile.php', $issue_data['file_path']);
        $this->assertSame('error', $issue_data['type']);
        $this->assertSame('The given return type \'int\' for fooFoo is incorrect, got \'string\'', $issue_data['message']);
        $this->assertSame(2, $issue_data['line_number']);
        $this->assertSame(
            '@return int',
            substr($file_contents, $issue_data['from'], $issue_data['to'] - $issue_data['from'])
        );
    }
}
