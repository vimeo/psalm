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
            public function bar() : void {}
        }

        Foo::bar();
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    public function testValidNonStaticInvocation()
    {
        $stmts = self::$parser->parse('<?php
        class Foo {
            public static function bar() : void {}
        }

        (new Foo())->bar();
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
        $filter = new Config\FileFilter(false);
        $filter->addExcludeFile('somefile.php');
        Config::getInstance()->setIssueHandler('MissingPropertyType', $filter);
        Config::getInstance()->setIssueHandler('MixedAssignment', $filter);

        $stmts = self::$parser->parse('<?php
        class Foo {
            public static function bar() : void {}
        }

        /** @var mixed */
        $a = (new Foo());

        $a->bar();
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    public function testValidStaticInvocation()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            public static function foo() : void {}
        }

        class B extends A {

        }

        B::foo();
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
            public function bar() : void {
                parent::bar();
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }
}
