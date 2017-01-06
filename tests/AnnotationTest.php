<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

class AnnotationTest extends PHPUnit_Framework_TestCase
{
    /** @var \PhpParser\Parser */
    protected static $parser;

    /** @var \Psalm\Checker\ProjectChecker */
    protected $project_checker;

    public static function setUpBeforeClass()
    {
        self::$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);

        $config = new TestConfig();
    }

    public function setUp()
    {
        FileChecker::clearCache();
        $this->project_checker = new \Psalm\Checker\ProjectChecker();
    }

    public function testDeprecatedMethod()
    {
        $stmts = self::$parser->parse('<?php
        class Foo {
            /**
             * @deprecated
             */
            public static function barBar() : void {
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndCheckMethods($context);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage DeprecatedMethod
     */
    public function testDeprecatedMethodWithCall()
    {
        $stmts = self::$parser->parse('<?php
        class Foo {
            /**
             * @deprecated
             */
            public static function barBar() : void {
            }
        }

        Foo::barBar();
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndCheckMethods($context);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidDocblock
     */
    public function testInvalidDocblockParam()
    {
        $stmts = self::$parser->parse('<?php
        /**
         * @param int $bar
         */
        function fooFoo(array $bar) : void {
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndCheckMethods($context);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidDocblock - somefile.php:3 - Parameter $bar does not appear in the argument list for fooBar
     */
    public function testExtraneousDocblockParam()
    {
        $stmts = self::$parser->parse('<?php
        /**
         * @param int $bar
         */
        function fooBar() : void {
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndCheckMethods($context);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidDocblock - somefile.php:2 - Badly-formatted @param in docblock for fooBar
     */
    public function testMissingParamType()
    {
        $stmts = self::$parser->parse('<?php
        /**
         * @param $bar
         */
        function fooBar() : void {
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndCheckMethods($context);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidDocblock - somefile.php:2 - Badly-formatted @param in docblock for fooBar
     */
    public function testMissingParamVar()
    {
        $stmts = self::$parser->parse('<?php
        /**
         * @param string
         */
        function fooBar() : void {
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndCheckMethods($context);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidDocblock
     */
    public function testInvalidDocblockReturn()
    {
        $stmts = self::$parser->parse('<?php
        /**
         * @return string
         */
        function fooFoo() : void {
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndCheckMethods($context);
    }

    public function testValidDocblockReturn()
    {
        $stmts = self::$parser->parse('<?php
        /**
         * @return string
         */
        function fooFoo() : string {
            return "boop";
        }

        /**
         * @return array<int, string>
         */
        function foo2() : array {
            return ["hello"];
        }

        /**
         * @return array<int, string>
         */
        function foo3() : array {
            return ["hello"];
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndCheckMethods($context);
    }

    public function testNopType()
    {
        $stmts = self::$parser->parse('<?php
        $a = "hello";

        /** @var int $a */
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndCheckMethods($context);
        $this->assertEquals('int', (string) $context->vars_in_scope['$a']);
    }

    public function testGoodDocblock()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /**
             * @param A $a
             * @param bool $b
             */
            public function g(A $a, $b) : void {
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndCheckMethods($context);
    }

    public function testGoodDocblockInNamespace()
    {
        $stmts = self::$parser->parse('<?php
        namespace Foo;

        class A {
            /**
             * @param \Foo\A $a
             * @param bool $b
             */
            public function g(A $a, $b) : void {
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndCheckMethods($context);
    }
}
