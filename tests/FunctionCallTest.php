<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

class FunctionCallTest extends PHPUnit_Framework_TestCase
{
    /** @var \PhpParser\Parser */
    protected static $parser;

    public static function setUpBeforeClass()
    {
        self::$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
    }

    public function setUp()
    {
        $config = new TestConfig();
        FileChecker::clearCache();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidScalarArgument
     */
    public function testInvalidScalarArgument()
    {
        $stmts = self::$parser->parse('<?php
        function foo(int $a) : void {}
        foo("string");
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage MixedArgument
     */
    public function testMixedArgument()
    {
        $filter = new Config\FileFilter(false);
        $filter->addFile('somefile.php');
        Config::getInstance()->setIssueHandler('MixedAssignment', $filter);

        $stmts = self::$parser->parse('<?php
        function foo(int $a) : void {}
        /** @var mixed */
        $a = "hello";
        foo($a);
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage NullArgument
     */
    public function testNullArgument()
    {
        $stmts = self::$parser->parse('<?php
        function foo(int $a) : void {}
        foo(null);
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage TooFewArguments
     */
    public function testTooFewArguments()
    {
        $stmts = self::$parser->parse('<?php
        function foo(int $a) : void {}
        foo();
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage TooManyArguments
     */
    public function testTooManyArguments()
    {
        $stmts = self::$parser->parse('<?php
        function foo(int $a) : void {}
        foo(5, "dfd");
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage TypeCoercion
     */
    public function testTypeCoercion()
    {
        $stmts = self::$parser->parse('<?php
        class A {}
        class B extends A{}

        function foo(B $b) : void {}
        foo(new A());
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }
}
