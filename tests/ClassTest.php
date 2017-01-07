<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

class ClassTest extends PHPUnit_Framework_TestCase
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

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage UndefinedClass
     */
    public function testUndefinedClass()
    {
        $stmts = self::$parser->parse('<?php
        (new Foo());
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidClass
     */
    public function testWrongCaseClass()
    {
        $stmts = self::$parser->parse('<?php
        class Foo {}
        (new foo());
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidScope
     */
    public function testInvalidThisFetch()
    {
        $stmts = self::$parser->parse('<?php
        echo $this;
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidScope
     */
    public function testInvalidThisAssignment()
    {
        $stmts = self::$parser->parse('<?php
        $this = "hello";
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage UndefinedConstant
     */
    public function testUndefinedConstant()
    {
        $stmts = self::$parser->parse('<?php
        echo HELLO;
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage UndefinedConstant
     */
    public function testUndefinedClassConstant()
    {
        $stmts = self::$parser->parse('<?php
        class A {}
        echo A::HELLO;
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
    }

    public function testSingleFileInheritance()
    {
        $stmts = self::$parser->parse('<?php
        class A extends B {}

        class B {
            public function fooFoo() : void {
                $a = new A();
                $a->barBar();
            }

            protected function barBar() : void {
                echo "hello";
            }
        }');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidParent
     */
    public function testInheritanceLoopOne()
    {
        $this->markTestSkipped('A bug');
        $stmts = self::$parser->parse('<?php
        class C extends C {}
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidParent
     */
    public function testInheritanceLoopTwo()
    {
        $this->markTestSkipped('A bug');
        $stmts = self::$parser->parse('<?php
        class E extends F {}
        class F extends E {}
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidParent
     */
    public function testInheritanceLoopThree()
    {
        $this->markTestSkipped('A bug');
        $stmts = self::$parser->parse('<?php
        class G extends H {}
        class H extends I {}
        class I extends G {}
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
    }

    public function testConstSandwich()
    {
        $stmts = self::$parser->parse('<?php
        class A { const B = 42;}
        $a = A::B;
        class C {}
        ');
        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
    }

    public function testDeferredReference()
    {
        $stmts = self::$parser->parse('<?php
        class B {
            const C = A;
        }

        const A = 5;

        $a = B::C;
        ');
        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('int', (string) $context->vars_in_scope['$a']);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage UndefinedConstant
     */
    public function testInvalidDeferredReference()
    {
        $stmts = self::$parser->parse('<?php
        class B {
            const C = A;
        }

        $b = (new B);

        const A = 5;
        ');
        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
    }

    public function testMoreCyclicalReferences()
    {
        $stmts = self::$parser->parse('<?php
        class B extends C {
            public function d() : A {
                return new A;
            }
        }
        class C {
            /** @var string */
            public $p = A::class;
            public static function e() : void {}
        }
        class A extends B {
            private function f() : void {
                self::e();
            }
        }
        ');
        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
    }


}
