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
        function fooFoo(int $a) : void {}
        fooFoo("string");
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
        Config::getInstance()->setCustomErrorLevel('MixedAssignment', Config::REPORT_SUPPRESS);

        $stmts = self::$parser->parse('<?php
        function fooFoo(int $a) : void {}
        /** @var mixed */
        $a = "hello";
        fooFoo($a);
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
        function fooFoo(int $a) : void {}
        fooFoo(null);
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
        function fooFoo(int $a) : void {}
        fooFoo();
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
        function fooFoo(int $a) : void {}
        fooFoo(5, "dfd");
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

        function fooFoo(B $b) : void {}
        fooFoo(new A());
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    public function testTypedArrayWithDefault()
    {
        $stmts = self::$parser->parse('<?php
        class A {}

        /** @param array<A> $a */
        function fooFoo(array $a = []) : void {

        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }
}
