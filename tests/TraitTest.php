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

    /** @var \Psalm\Checker\ProjectChecker */
    protected $project_checker;

    public static function setUpBeforeClass()
    {
        self::$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
    }

    public function setUp()
    {
        $config = new TestConfig();
        FileChecker::clearCache();
        $this->project_checker = new \Psalm\Checker\ProjectChecker();
    }

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
        $file_checker->visitAndCheckMethods();
    }

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
        $file_checker->visitAndCheckMethods();
    }

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
        $file_checker->visitAndCheckMethods();
    }

    public function testAccessiblePrivatePropertyFromTrait()
    {
        $stmts = self::$parser->parse('<?php
        trait T {
            /** @var string */
            private $fooFoo;
        }

        class B {
            use T;

            public function doFoo() : void {
                echo $this->fooFoo;
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndCheckMethods();
    }

    public function testAccessibleProtectedPropertyFromTrait()
    {
        $stmts = self::$parser->parse('<?php
        trait T {
            /** @var string */
            protected $fooFoo;
        }

        class B {
            use T;

            public function doFoo() : void {
                echo $this->fooFoo;
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndCheckMethods();
    }

    public function testAccessiblePublicPropertyFromTrait()
    {
        $stmts = self::$parser->parse('<?php
        trait T {
            /** @var string */
            public $fooFoo;
        }

        class B {
            use T;

            public function doFoo() : void {
                echo $this->fooFoo;
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndCheckMethods();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage InaccessibleMethod
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
        $file_checker->visitAndCheckMethods();
    }

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
        $file_checker->visitAndCheckMethods();
    }

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
        $file_checker->visitAndCheckMethods();
    }

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
        $file_checker->visitAndCheckMethods();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage UndefinedTrait
     */
    public function testUndefinedTrait()
    {
        $stmts = self::$parser->parse('<?php
        class B {
            use A;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndCheckMethods();
    }

    public function testRedefinedTraitMethod()
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
        $file_checker->visitAndCheckMethods();
    }

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
        $file_checker->visitAndCheckMethods();
    }

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
        $context = new Context('somefile.php');
        $file_checker->visitAndCheckMethods($context);
        $this->assertEquals('A', (string) $context->vars_in_scope['$a']);
    }

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
        $context = new Context('somefile.php');
        $file_checker->visitAndCheckMethods($context);
        $this->assertEquals('A', (string) $context->vars_in_scope['$a']);
    }
}
