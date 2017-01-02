<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

class ClassScopeTest extends PHPUnit_Framework_TestCase
{
    /** @var \PhpParser\Parser */
    protected static $parser;

    public static function setUpBeforeClass()
    {
        self::$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);

        $config = new TestConfig();
    }

    public function setUp()
    {
        FileChecker::clearCache();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage InaccessibleMethod
     */
    public function testInaccessiblePrivateMethod()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            private function fooFoo() : void {

            }
        }

        (new A())->fooFoo();
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage InaccessibleMethod
     */
    public function testInaccessibleProtectedMethod()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            protected function fooFoo() : void {

            }
        }

        (new A())->fooFoo();
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testAccessiblePrivateMethodFromSubclass()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            private function fooFoo() : void {

            }

            private function barBar() : void {
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
    public function testInaccessiblePrivateMethodFromSubclass()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            private function fooFoo() : void {

            }
        }

        class B extends A {
            public function doFoo() : void {
                $this->fooFoo();
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testAccessibleProtectedMethodFromSubclass()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            protected function fooFoo() : void {
            }
        }

        class B extends A {
            public function doFoo() : void {
                $this->fooFoo();
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testAccessibleProtectedMethodFromOtherSubclass()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            protected function fooFoo() : void {
            }
        }

        class B extends A { }

        class C extends A {
            public function doFoo() : void {
                (new B)->fooFoo();
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
    public function testInaccessibleProtectedMethodFromOtherSubclass()
    {
        $stmts = self::$parser->parse('<?php
        trait T {
            protected function fooFoo() : void {
            }
        }

        class B {
            use T;
        }

        class C {
            use T;

            public function doFoo() : void {
                (new B)->fooFoo();
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage InaccessibleProperty
     */
    public function testInaccessiblePrivateProperty()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @var string */
            private $fooFoo;
        }

        echo (new A())->fooFoo;
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage InaccessibleProperty
     */
    public function testInaccessibleProtectedProperty()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @var string */
            protected $fooFoo;
        }

        echo (new A())->fooFoo;
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage InaccessibleProperty
     */
    public function testInaccessiblePrivatePropertyFromSubclass()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @var string */
            private $fooFoo;
        }

        class B extends A {
            public function doFoo() : void {
                echo $this->fooFoo;
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage InaccessibleProperty
     */
    public function testInaccessibleStaticPrivateProperty()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @var string */
            private static $fooFoo;
        }

        echo A::$fooFoo;
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage InaccessibleProperty
     */
    public function testInaccessibleStaticProtectedProperty()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @var string */
            protected static $fooFoo;
        }

        echo A::$fooFoo;
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage InaccessibleProperty
     */
    public function testInaccessibleStaticPrivatePropertyFromSubclass()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @var string */
            private static $fooFoo;
        }

        class B extends A {
            public function doFoo() : void {
                echo A::$fooFoo;
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testAccessibleProtectedPropertyFromSubclass()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @var string */
            protected $fooFoo;
        }

        class B extends A {
            public function doFoo() : void {
                echo $this->fooFoo;
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testAccessibleProtectedPropertyFromGreatGrandparent()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @var string */
            protected $fooFoo;
        }

        class B extends A { }

        class C extends B { }

        class D extends C {
            public function doFoo() : void {
                echo $this->fooFoo;
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testAccessibleProtectedPropertyFromOtherSubclass()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @var string */
            protected $fooFoo;
        }

        class B extends A {
        }

        class C extends A {
            public function fooFoo() : void {
                $b = new B();
                $b->fooFoo = "hello";
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testAccessibleStaticPropertyFromSubclass()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @var string */
            protected static $fooFoo;

            public function barBar() : void {
                echo self::$fooFoo;
            }
        }

        class B extends A {
            public function doFoo() : void {
                echo A::$fooFoo;
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }
}
