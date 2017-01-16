<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

class PropertyTypeTest extends PHPUnit_Framework_TestCase
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
        $config = new TestConfig();
        $config->throw_exception = true;
        FileChecker::clearCache();
        $this->project_checker = new \Psalm\Checker\ProjectChecker();
    }

    /**
     * @return void
     */
    public function testNewVarInIf()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /**
             * @var mixed
             */
            public $foo;

            /** @return void */
            public function barBar()
            {
                if (rand(0,10) === 5) {
                    $this->foo = [];
                }

                if (!is_array($this->foo)) {
                    // do something
                }
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testPropertyWithoutTypeSuppressingIssue()
    {
        Config::getInstance()->setCustomErrorLevel('MissingPropertyType', Config::REPORT_SUPPRESS);
        Config::getInstance()->setCustomErrorLevel('MixedAssignment', Config::REPORT_SUPPRESS);

        $stmts = self::$parser->parse('<?php
        class A {
            public $foo;
        }

        $a = (new A)->foo;
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage UndefinedPropertyAssignment
     * @return                   void
     */
    public function testUndefinedPropertyAssignment()
    {
        $stmts = self::$parser->parse('<?php
        class A {
        }

        (new A)->foo = "cool";
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage UndefinedPropertyFetch
     * @return                   void
     */
    public function testUndefinedPropertyFetch()
    {
        $stmts = self::$parser->parse('<?php
        class A {
        }

        echo (new A)->foo;
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage UndefinedThisPropertyAssignment
     * @return                   void
     */
    public function testUndefinedThisPropertyAssignment()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            public function fooFoo() : void {
                $this->foo = "cool";
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage UndefinedThisPropertyFetch
     * @return                   void
     */
    public function testUndefinedThisPropertyFetch()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            public function fooFoo() : void {
                echo $this->foo;
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage MissingPropertyDeclaration
     * @return                   void
     */
    public function testMissingPropertyDeclaration()
    {
        $stmts = self::$parser->parse('<?php
        class A {
        }

        /** @psalm-suppress UndefinedPropertyAssignment */
        function fooDo() : void {
            (new A)->foo = "cool";
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage MissingPropertyType
     * @return                   void
     */
    public function testMissingPropertyType()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            public $foo;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidPropertyAssignment
     * @return                   void
     */
    public function testBadAssignment()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @var string */
            public $foo;

            public function barBar() : void
            {
                $this->foo = 5;
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidPropertyAssignment
     * @return                   void
     */
    public function testBadAssignmentAsWell()
    {
        $stmts = self::$parser->parse('<?php
        $a = "hello";
        $a->foo = "bar";
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidPropertyFetch
     * @return                   void
     */
    public function testBadFetch()
    {
        $stmts = self::$parser->parse('<?php
        $a = "hello";
        echo $a->foo;
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testSharedPropertyInIf()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @var int */
            public $foo;
        }
        class B {
            /** @var string */
            public $foo;
        }

        $a = rand(0, 10) ? new A() : (rand(0, 10) ? new B() : null);
        $b = null;

        if ($a instanceof A || $a instanceof B) {
            $b = $a->foo;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('null|string|int', (string) $context->vars_in_scope['$b']);
    }

    /**
     * @return void
     */
    public function testSharedPropertyInElseIf()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @var int */
            public $foo;
        }
        class B {
            /** @var string */
            public $foo;
        }

        $a = rand(0, 10) ? new A() : new B();
        $b = null;

        if (rand(0, 10) === 4) {
            // do nothing
        }
        elseif ($a instanceof A || $a instanceof B) {
            $b = $a->foo;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('null|string|int', (string) $context->vars_in_scope['$b']);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage MixedPropertyFetch
     * @return                   void
     */
    public function testMixedPropertyFetch()
    {
        Config::getInstance()->setCustomErrorLevel('MissingPropertyType', Config::REPORT_SUPPRESS);
        Config::getInstance()->setCustomErrorLevel('MixedAssignment', Config::REPORT_SUPPRESS);

        $stmts = self::$parser->parse('<?php
        class Foo {
            /** @var string */
            public $foo;
        }

        /** @var mixed */
        $a = (new Foo());

        echo $a->foo;
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage MixedPropertyAssignment
     * @return                   void
     */
    public function testMixedPropertyAssignment()
    {
        Config::getInstance()->setCustomErrorLevel('MissingPropertyType', Config::REPORT_SUPPRESS);
        Config::getInstance()->setCustomErrorLevel('MixedAssignment', Config::REPORT_SUPPRESS);

        $stmts = self::$parser->parse('<?php
        class Foo {
            /** @var string */
            public $foo;
        }

        /** @var mixed */
        $a = (new Foo());

        $a->foo = "hello";
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage NullPropertyAssignment
     * @return                   void
     */
    public function testNullablePropertyAssignment()
    {
        $stmts = self::$parser->parse('<?php
        class Foo {
            /** @var string */
            public $foo;
        }

        $a = rand(0, 10) ? new Foo() : null;

        $a->foo = "hello";
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage NullPropertyFetch
     * @return                   void
     */
    public function testNullablePropertyFetch()
    {
        $stmts = self::$parser->parse('<?php
        class Foo {
            /** @var string */
            public $foo;
        }

        $a = rand(0, 10) ? new Foo() : null;

        echo $a->foo;
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testNullablePropertyCheck()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @var string */
            public $aa;
        }

        class B {
            /** @var A|null */
            public $bb;
        }

        $b = rand(0, 10) ? new A() : new B();

        if ($b instanceof B && isset($b->bb) && $b->bb->aa === "aa") {
            echo $b->bb->aa;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testNullablePropertyAfterGuard()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @var string|null */
            public $aa;
        }

        $a = new A();

        if (!$a->aa) {
            $a->aa = "hello";
        }

        echo substr($a->aa, 1);
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testNullableStaticPropertyWithIfCheck()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @var A|null */
            public static $fooFoo;

            public static function getFoo() : A {
                if (!self::$fooFoo) {
                    self::$fooFoo = new A();
                }

                return self::$fooFoo;
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
    public function testReflectionProperties()
    {
        $stmts = self::$parser->parse('<?php
        class Foo {
        }

        $a = new \ReflectionMethod("Foo", "__construct");

        echo $a->name . " - " . $a->class;
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testGrandparentReflectedProperties()
    {
        $stmts = self::$parser->parse('<?php
        $a = new DOMElement("foo");
        $owner = $a->ownerDocument;
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('DOMDocument', (string) $context->vars_in_scope['$owner']);
    }

    /**
     * @return void
     */
    public function testGoodArrayProperties()
    {
        Config::getInstance()->setCustomErrorLevel('MixedAssignment', Config::REPORT_SUPPRESS);

        $context = new Context();
        $stmts = self::$parser->parse('<?php

        interface I1 {}

        class A1 implements I1{}

        class B1 implements I1 {}

        class C1 {
            /** @var array<I1> */
            public $is;
        }

        $c = new C1;
        $c->is = [new A1];
        $c->is = [new A1, new A1];
        $c->is = [new A1, new B1];
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidPropertyAssignment
     * @return                   void
     */
    public function testBadArrayProperty()
    {
        Config::getInstance()->setCustomErrorLevel('MixedAssignment', Config::REPORT_SUPPRESS);

        $context = new Context();
        $stmts = self::$parser->parse('<?php
        class A {}

        class B {}

        class C {
            /** @var array<B> */
            public $bb;
        }

        $c = new C;
        $c->bb = [new A, new B];
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testIssetPropertyDoesNotExist()
    {
        $stmts = self::$parser->parse('<?php
        class A {
        }

        $a = new A();

        if (isset($a->bar)) {

        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }
}
