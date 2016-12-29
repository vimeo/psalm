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
    protected static $file_filter;

    public static function setUpBeforeClass()
    {
        self::$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
    }

    public function setUp()
    {
        $config = new TestConfig();
        $config->throw_exception = true;
        FileChecker::clearCache();
    }

    public function testUndefinedClass()
    {
        $stmts = self::$parser->parse('<?php

        class A{
            /**
             * @psalm-suppress UndefinedClass
             * @psalm-suppress MixedMethodCall
             * @psalm-suppress MissingReturnType
             */
            public function a() {
                B::foo()->bar()->bat()->baz()->bam()->bas()->bee()->bet()->bes()->bis();
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testExcludeFile()
    {
        $filter = new Config\FileFilter(false);
        $filter->addIgnoreFile('somefile.php');
        Config::getInstance()->setIssueHandler('UndefinedFunction', $filter);

        $stmts = self::$parser->parse('<?php
        foo();
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage UndefinedFunction - somefile.php:2 - Function foo does not exist
     */
    public function testIncludeFile()
    {
        $filter = new Config\FileFilter(true);
        $filter->addOnlyFile('somefile.php');
        Config::getInstance()->setIssueHandler('UndefinedFunction', $filter);

        $stmts = self::$parser->parse('<?php
        foo();
        ');

        $file_checker = new FileChecker('someotherfile.php', $stmts);
        $file_checker->check();

        $stmts = self::$parser->parse('<?php
        foo();
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testExcludeDirectory()
    {
        $filter = new Config\FileFilter(false);
        $filter->addIgnoreDirectory('src');
        Config::getInstance()->setIssueHandler('UndefinedFunction', $filter);

        $stmts = self::$parser->parse('<?php
        foo();
        ');

        $file_checker = new FileChecker('src/somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage UndefinedFunction - src2/somefile.php:2 - Function foo does not exist
     */
    public function testIncludeDirectory()
    {
        $filter = new Config\FileFilter(true);
        $filter->addOnlyDirectory('src2');
        Config::getInstance()->setIssueHandler('UndefinedFunction', $filter);

        (new FileChecker(
            'src1/somefile.php',
            self::$parser->parse('<?php
            foo();
            ')
        ))->check();

        (new FileChecker(
            'src2/somefile.php',
            self::$parser->parse('<?php
            foo();
            ')
        ))->check();
    }
}
