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

    public static function setUpBeforeClass()
    {
        self::$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
    }

    public function setUp()
    {
        $config = new TestConfig();
        FileChecker::clearCache();
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

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
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

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
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

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
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

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
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

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
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

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
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

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
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

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
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

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
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

        $a = (new B)->g();
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('A', (string) $context->vars_in_scope['$a']);
    }
}
