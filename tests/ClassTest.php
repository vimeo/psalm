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
     * @expectedExceptionMessage UndefinedClass
     * @return                   void
     */
    public function testUndefinedClass()
    {
        $stmts = self::$parser->parse('<?php
        (new Foo());
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidClass
     * @return                   void
     */
    public function testWrongCaseClass()
    {
        $stmts = self::$parser->parse('<?php
        class Foo {}
        (new foo());
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidScope
     * @return                   void
     */
    public function testInvalidThisFetch()
    {
        $stmts = self::$parser->parse('<?php
        echo $this;
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidScope
     * @return                   void
     */
    public function testInvalidThisAssignment()
    {
        $stmts = self::$parser->parse('<?php
        $this = "hello";
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage UndefinedConstant
     * @return                   void
     */
    public function testUndefinedConstant()
    {
        $stmts = self::$parser->parse('<?php
        echo HELLO;
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage UndefinedConstant
     * @return                   void
     */
    public function testUndefinedClassConstant()
    {
        $stmts = self::$parser->parse('<?php
        class A {}
        echo A::HELLO;
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
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
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidParent
     * @return                   void
     */
    public function testInheritanceLoopOne()
    {
        $this->markTestSkipped('A bug');
        $stmts = self::$parser->parse('<?php
        class C extends C {}
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidParent
     * @return                   void
     */
    public function testInheritanceLoopTwo()
    {
        $this->markTestSkipped('A bug');
        $stmts = self::$parser->parse('<?php
        class E extends F {}
        class F extends E {}
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidParent
     * @return                   void
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
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testConstSandwich()
    {
        $stmts = self::$parser->parse('<?php
        class A { const B = 42;}
        $a = A::B;
        class C {}
        ');
        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
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
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('int', (string) $context->vars_in_scope['$a']);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage UndefinedConstant
     * @return                   void
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
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
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
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testReferenceToSubclassInMethod()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            public function b(B $b) : void {

            }

            public function c() : void {

            }
        }

        class B extends A {
            public function d() : void {
                $this->c();
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
    public function testReferenceToClassInMethod()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            public function b(A $b) : void {
                $b->b(new A());
            }
        }
        ');
        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage OverriddenMethodAccess
     * @return                   void
     */
    public function testOverridePublicAccessLevelToPrivate()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            public function fooFoo() : void {}
        }

        class B extends A {
            private function fooFoo() : void {}
        }
        ');
        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage OverriddenMethodAccess
     * @return                   void
     */
    public function testOverridePublicAccessLevelToProtected()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            public function fooFoo() : void {}
        }

        class B extends A {
            protected function fooFoo() : void {}
        }
        ');
        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage OverriddenMethodAccess
     * @return                   void
     */
    public function testOverrideProtectedAccessLevelToPrivate()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            protected function fooFoo() : void {}
        }

        class B extends A {
            private function fooFoo() : void {}
        }
        ');
        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testOverrideProtectedAccessLevelToPublic()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            protected function fooFoo() : void {}
        }

        class B extends A {
            public function fooFoo() : void {}
        }
        ');
        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testReflectedParents()
    {
        $stmts = self::$parser->parse('<?php
        $e = rand(0, 10)
          ? new RuntimeException("m")
          : null;

        if ($e instanceof Exception) {
          echo "good";
        }
        ');
        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testNamespacedAliasedClassCall()
    {
        $stmts = self::$parser->parse('<?php
        namespace Aye {
            class Foo {}
        }
        namespace Bee {
            use Aye as A;

            new A\Foo();
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage DuplicateClass
     * @return                   void
     */
    public function testClassRedefinition()
    {
        $stmts = self::$parser->parse('<?php
        class Foo {}
        class Foo {}
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage DuplicateClass
     * @return                   void
     */
    public function testClassRedefinitionInNamespace()
    {
        $stmts = self::$parser->parse('<?php
        namespace Aye {
            class Foo {}
            class Foo {}
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage DuplicateClass
     * @return                   void
     */
    public function testClassRedefinitionInSeparateNamespace()
    {
        $stmts = self::$parser->parse('<?php
        namespace Aye {
            class Foo {}
        }
        namespace Aye {
            class Foo {}
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage AbstractInstantiation
     * @return                   void
     */
    public function testAbstractClassInstantiation()
    {
        $stmts = self::$parser->parse('<?php
        abstract class A {}
        new A();
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage UndefinedClass
     * @return                   void
     */
    public function testMissingParent()
    {
        $stmts = self::$parser->parse('<?php
        class A extends B { }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testMissingParentWithFunction()
    {
        Config::getInstance()->setCustomErrorLevel('UndefinedClass', Config::REPORT_SUPPRESS);
        Config::getInstance()->setCustomErrorLevel('MissingReturnType', Config::REPORT_SUPPRESS);

        $stmts = self::$parser->parse('<?php
        class B extends C {
            public function fooA() { }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }
}
