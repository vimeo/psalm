<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

class ArrayAccessTest extends PHPUnit_Framework_TestCase
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

    public function testInstanceOfStringOffset()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            public function foo() : void { }
        }
        function bar (array $a) : void {
            if ($a["a"] instanceof A) {
                $a["a"]->foo();
            }
        }
        ');
        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    public function testInstanceOfIntOffset()
    {
        $context = new Context('somefile.php');
        $stmts = self::$parser->parse('<?php
        class A {
            public function foo() : void { }
        }
        function bar (array $a) : void {
            if ($a[0] instanceof A) {
                $a[0]->foo();
            }
        }
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $file_checker->check(true, true, $context);
    }

    public function testNotEmptyStringOffset()
    {
        $context = new Context('somefile.php');
        $stmts = self::$parser->parse('<?php
        /**
         * @param  array<string>  $a
         */
        function bar (array $a) : string {
            if ($a["bat"]) {
                return $a["bat"];
            }

            return "blah";
        }
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $file_checker->check(true, true, $context);
    }

    public function testNotEmptyIntOffset()
    {
        $context = new Context('somefile.php');
        $stmts = self::$parser->parse('<?php
        /**
         * @param  array<string>  $a
         */
        function bar (array $a) : string {
            if ($a[0]) {
                return $a[0];
            }

            return "blah";
        }
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $file_checker->check(true, true, $context);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidArrayAccess
     */
    public function testInvalidArrayAccess()
    {
        $context = new Context('somefile.php');
        $stmts = self::$parser->parse('<?php
        $a = 5;
        echo $a[0];
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $file_checker->check(true, true, $context);
    }
}
