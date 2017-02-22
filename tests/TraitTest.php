<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

class TraitTest extends PHPUnit_Framework_TestCase
{
    /** @var \PhpParser\Parser */
    protected static $parser;

    /** @var TestConfig */
    protected static $config;

    /** @var \Psalm\Checker\ProjectChecker */
    protected $project_checker;

    /**
     * @return void
     */
    public static function setUpBeforeClass()
    {
        self::$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        self::$config = new TestConfig();
    }

    /**
     * @return void
     */
    public function setUp()
    {
        FileChecker::clearCache();
        $this->project_checker = new \Psalm\Checker\ProjectChecker();
        $this->project_checker->setConfig(self::$config);
    }

    /**
     * @return void
     */
    public function testAccessiblePrivateMethodFromTrait()
    {
        $stmts = self::$parser->parse('<?php
        trait T {
            private function fooFoo() : void {
            }
        }

        class B {
            use T;

            public function doFoo() : void {
                $this->fooFoo();
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testAccessibleProtectedMethodFromTrait()
    {
        $stmts = self::$parser->parse('<?php
        trait T {
            protected function fooFoo() : void {
            }
        }

        class B {
            use T;

            public function doFoo() : void {
                $this->fooFoo();
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testAccessiblePublicMethodFromTrait()
    {
        $stmts = self::$parser->parse('<?php
        trait T {
            public function fooFoo() : void {
            }
        }

        class B {
            use T;

            public function doFoo() : void {
                $this->fooFoo();
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testAccessiblePrivatePropertyFromTrait()
    {
        $stmts = self::$parser->parse('<?php
        trait T {
            /** @var string */
            private $fooFoo = "";
        }

        class B {
            use T;

            public function doFoo() : void {
                echo $this->fooFoo;
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testAccessibleProtectedPropertyFromTrait()
    {
        $stmts = self::$parser->parse('<?php
        trait T {
            /** @var string */
            protected $fooFoo = "";
        }

        class B {
            use T;

            public function doFoo() : void {
                echo $this->fooFoo;
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testAccessiblePublicPropertyFromTrait()
    {
        $stmts = self::$parser->parse('<?php
        trait T {
            /** @var string */
            public $fooFoo = "";
        }

        class B {
            use T;

            public function doFoo() : void {
                echo $this->fooFoo;
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InaccessibleMethod
     * @return                   void
     */
    public function testInccessiblePrivateMethodFromInheritedTrait()
    {
        $stmts = self::$parser->parse('<?php
        trait T {
            private function fooFoo() : void {
            }
        }

        class B {
            use T;
        }

        class C extends B {
            public function doFoo() : void {
                $this->fooFoo();
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testAccessibleProtectedMethodFromInheritedTrait()
    {
        $stmts = self::$parser->parse('<?php
        trait T {
            protected function fooFoo() : void {
            }
        }

        class B {
            use T;
        }

        class C extends B {
            public function doFoo() : void {
                $this->fooFoo();
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testAccessiblePublicMethodFromInheritedTrait()
    {
        $stmts = self::$parser->parse('<?php
        trait T {
            public function fooFoo() : void {
            }
        }

        class B {
            use T;
        }

        class C extends B {
            public function doFoo() : void {
                $this->fooFoo();
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testStaticClassMethodFromWithinTrait()
    {
        $stmts = self::$parser->parse('<?php
        trait T {
            public function fooFoo() : void {
                self::barBar();
            }
        }

        class B {
            use T;

            public static function barBar() : void {

            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage UndefinedTrait
     * @return                   void
     */
    public function testUndefinedTrait()
    {
        $stmts = self::$parser->parse('<?php
        class B {
            use A;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testRedefinedTraitMethodWithoutAlias()
    {
        $stmts = self::$parser->parse('<?php
        trait T {
            public function fooFoo() : void {
            }
        }

        class B {
            use T;

            public function fooFoo(string $a) : void {
            }
        }

        (new B)->fooFoo("hello");
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testRedefinedTraitMethodWithAlias()
    {
        $stmts = self::$parser->parse('<?php
        trait T {
            public function fooFoo() : void {
            }
        }

        class B {
            use T {
                fooFoo as barBar;
            }

            public function fooFoo() : void {
                $this->barBar();
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testTraitSelf()
    {
        $stmts = self::$parser->parse('<?php
        trait T {
            public function g(): self
            {
                return $this;
            }
        }

        class A {
            use T;
        }

        $a = (new A)->g();
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('A', (string) $context->vars_in_scope['$a']);
    }

    /**
     * @return void
     */
    public function testParentTraitSelf()
    {
        $stmts = self::$parser->parse('<?php
        trait T {
            public function g(): self
            {
                return $this;
            }
        }

        class A {
            use T;
        }

        class B extends A {
        }

        class C {
            use T;
        }

        $a = (new B)->g();
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('A', (string) $context->vars_in_scope['$a']);
    }

    /**
     * @return void
     */
    public function testDirectStaticCall()
    {
        $stmts = self::$parser->parse('<?php
        trait T {
            /** @return void */
            public static function foo() {}
        }
        class A {
            use T;

            /** @return void */
            public function bar() {
                T::foo();
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
    public function testAbstractTraitMethod()
    {
        $stmts = self::$parser->parse('<?php
        trait T {
            /** @return void */
            abstract public function foo();
        }

        abstract class A {
            use T;

            /** @return void */
            public function bar() {
                $this->foo();
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }


}
