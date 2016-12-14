<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

class ToStringTest extends PHPUnit_Framework_TestCase
{
    protected static $parser;

    public static function setUpBeforeClass()
    {
        self::$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);

        $config = new TestConfig();
        $config->throw_exception = true;
        $config->use_docblock_types = true;
    }

    public function setUp()
    {
        FileChecker::clearCache();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidArgument
     */
    public function testEchoClass()
    {
        $stmts = self::$parser->parse('<?php
        class A {}
        echo (new A);
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidToString
     */
    public function testInvalidToStringReturnType()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            function __toString() : void { }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidToString
     */
    public function testInvalidInferredToStringReturnType()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /**
             * @psalm-suppress MissingReturnType
             */
            function __toString() { }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    public function testValidToString()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            function __toString() : string {
                return "hello";
            }
        }
        echo (new A);
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    public function testValidInferredToStringType()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /**
             * @psalm-suppress MissingReturnType
             */
            function __toString() {
                return "hello";
            }
        }
        echo (new A);
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }
}
