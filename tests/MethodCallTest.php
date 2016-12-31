<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

class MethodCallTest extends PHPUnit_Framework_TestCase
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
     * @expectedExceptionMessage InvalidStaticInvocation
     */
    public function testInvalidStaticInvocation()
    {
        $stmts = self::$parser->parse('<?php
        class Foo {
            public function barBar() : void {}
        }

        Foo::barBar();
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    public function testValidNonStaticInvocation()
    {
        $stmts = self::$parser->parse('<?php
        class Foo {
            public static function barBar() : void {}
        }

        (new Foo())->barBar();
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage MixedMethodCall
     */
    public function testMixedMethodCall()
    {
        Config::getInstance()->setCustomErrorLevel('MissingPropertyType', Config::REPORT_SUPPRESS);
        Config::getInstance()->setCustomErrorLevel('MixedAssignment', Config::REPORT_SUPPRESS);

        $stmts = self::$parser->parse('<?php
        class Foo {
            public static function barBar() : void {}
        }

        /** @var mixed */
        $a = (new Foo());

        $a->barBar();
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    public function testValidStaticInvocation()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            public static function fooFoo() : void {}
        }

        class B extends A {

        }

        B::fooFoo();
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage NonStaticSelfCall
     */
    public function testSelfNonStaticInvocation()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            public function fooFoo() : void {}

            public function barBar() : void {
                self::fooFoo();
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage ParentNotFound
     */
    public function testNoParent()
    {
        $stmts = self::$parser->parse('<?php
        class Foo {
            public function barBar() : void {
                parent::barBar();
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }
}
