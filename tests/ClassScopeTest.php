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
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InaccessibleMethod
     * @return                   void
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

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InaccessibleMethod
     * @return                   void
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

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
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

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InaccessibleMethod
     * @return                   void
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

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
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

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
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

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InaccessibleMethod
     * @return                   void
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

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InaccessibleProperty
     * @return                   void
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

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InaccessibleProperty
     * @return                   void
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

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InaccessibleProperty
     * @return                   void
     */
    public function testInaccessiblePrivatePropertyFromSubclass()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @var string */
            private $fooFoo = "";
        }

        class B extends A {
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
     * @expectedExceptionMessage InaccessibleProperty
     * @return                   void
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

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InaccessibleProperty
     * @return                   void
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

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InaccessibleProperty
     * @return                   void
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

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testAccessibleProtectedPropertyFromSubclass()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @var string */
            protected $fooFoo = "";
        }

        class B extends A {
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
    public function testAccessibleProtectedPropertyFromGreatGrandparent()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @var string */
            protected $fooFoo = "";
        }

        class B extends A { }

        class C extends B { }

        class D extends C {
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
    public function testAccessibleProtectedPropertyFromOtherSubclass()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @var string */
            protected $fooFoo = "";
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

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testAccessibleStaticPropertyFromSubclass()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @var string */
            protected static $fooFoo = "";

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

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testDefinedPrivateMethod()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            public function foo() : void {
                if ($this instanceof B) {
                    $this->boop();
                }
            }

            private function boop() : void {}
        }

        class B extends A {
            private function boop() : void {}
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }
}
