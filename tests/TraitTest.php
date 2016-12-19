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
        trait A {
            private function foo() : void {
            }
        }

        class B {
            use A;

            public function doFoo() : void {
                $this->foo();
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testAccessibleProtectedMethodFromTrait()
    {
        $stmts = self::$parser->parse('<?php
        trait A {
            protected function foo() : void {
            }
        }

        class B {
            use A;

            public function doFoo() : void {
                $this->foo();
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testAccessiblePublicMethodFromTrait()
    {
        $stmts = self::$parser->parse('<?php
        trait A {
            public function foo() : void {
            }
        }

        class B {
            use A;

            public function doFoo() : void {
                $this->foo();
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage UndefinedMethod
     */
    public function testInccessiblePrivateMethodFromInheritedTrait()
    {
        $stmts = self::$parser->parse('<?php
        trait A {
            private function foo() : void {
            }
        }

        class B {
            use A;
        }

        class C extends B {
            public function doFoo() : void {
                $this->foo();
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testAccessibleProtectedMethodFromInheritedTrait()
    {
        $stmts = self::$parser->parse('<?php
        trait A {
            protected function foo() : void {
            }
        }

        class B {
            use A;
        }

        class C extends B {
            public function doFoo() : void {
                $this->foo();
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testAccessiblePublicMethodFromInheritedTrait()
    {
        $stmts = self::$parser->parse('<?php
        trait A {
            public function foo() : void {
            }
        }

        class B {
            use A;
        }

        class C extends B {
            public function doFoo() : void {
                $this->foo();
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
}
