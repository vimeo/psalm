<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Config;

class IssueSuppressionTest extends PHPUnit_Framework_TestCase
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
        $this->project_checker = new \Psalm\Checker\ProjectChecker();
        $this->project_checker->setConfig(new TestConfig());
    }

    /**
     * @return void
     */
    public function testUndefinedClass()
    {
        $stmts = self::$parser->parse('<?php

        class A{
            /**
             * @psalm-suppress UndefinedClass
             * @psalm-suppress MixedMethodCall
             * @psalm-suppress MissingReturnType
             */
            public function b() {
                B::fooFoo()->barBar()->bat()->baz()->bam()->bas()->bee()->bet()->bes()->bis();
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testExcludeIssue()
    {
        Config::getInstance()->setCustomErrorLevel('UndefinedFunction', Config::REPORT_SUPPRESS);

        $stmts = self::$parser->parse('<?php
        fooFoo();
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }
}
