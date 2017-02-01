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
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidStaticInvocation
     * @return                   void
     */
    public function testInvalidStaticInvocation()
    {
        $stmts = self::$parser->parse('<?php
        class Foo {
            public function barBar() : void {}
        }

        Foo::barBar();
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidStaticInvocation
     * @return                   void
     */
    public function testInvalidParentStaticCall()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @return void */
            public function foo(){}
        }

        class B extends A {
            /** @return void */
            public static function bar(){
                parent::foo();
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testValidParentStaticCall()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @return void */
            public static function foo(){}
        }

        class B extends A {
            /** @return void */
            public static function bar(){
                parent::foo();
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testValidNonStaticInvocation()
    {
        $stmts = self::$parser->parse('<?php
        class Foo {
            public static function barBar() : void {}
        }

        (new Foo())->barBar();
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage MixedMethodCall
     * @return                   void
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

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
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

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage NonStaticSelfCall
     * @return                   void
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

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage ParentNotFound
     * @return                   void
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

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }
}
