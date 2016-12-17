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
    protected static $file_filter;

    public static function setUpBeforeClass()
    {
        self::$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
    }

    public function setUp()
    {
        $config = new TestConfig();
        $config->throw_exception = true;
        FileChecker::clearCache();
    }

    public function testNewVarInIf()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /**
             * @var mixed
             */
            public $foo;

            /** @return void */
            public function bar()
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

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testPropertyWithoutTypeSuppressingIssue()
    {
        $filter = new Config\FileFilter(false);
        $filter->addExcludeFile('somefile.php');
        Config::getInstance()->setIssueHandler('MissingPropertyType', $filter);
        Config::getInstance()->setIssueHandler('MixedAssignment', $filter);

        $stmts = self::$parser->parse('<?php
        class A {
            public $foo;
        }

        $a = (new A)->foo;
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage UndefinedPropertyAssignment
     */
    public function testUndefinedPropertyAssignment()
    {
        $stmts = self::$parser->parse('<?php
        class A {
        }

        (new A)->foo = "cool";
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage UndefinedPropertyFetch
     */
    public function testUndefinedPropertyFetch()
    {
        $stmts = self::$parser->parse('<?php
        class A {
        }

        echo (new A)->foo;
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage UndefinedThisPropertyAssignment
     */
    public function testUndefinedThisPropertyAssignment()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            public function foo() : void {
                $this->foo = "cool";
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage UndefinedThisPropertyFetch
     */
    public function testUndefinedThisPropertyFetch()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            public function foo() : void {
                echo $this->foo;
            }
        }        
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage MissingPropertyDeclaration
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

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage MissingPropertyType
     */
    public function testMissingPropertyType()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            public $foo;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidPropertyAssignment
     */
    public function testBadAssignment()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @var string */
            public $foo;

            public function bar() : void
            {
                $this->foo = 5;
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidPropertyAssignment
     */
    public function testBadAssignmentAsWell()
    {
        $stmts = self::$parser->parse('<?php
        $a = "hello";
        $a->foo = "bar";
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidPropertyFetch
     */
    public function testBadFetch()
    {
        $stmts = self::$parser->parse('<?php
        $a = "hello";
        echo $a->foo;
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

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

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('null|string|int', (string) $context->vars_in_scope['$b']);
    }

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

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('null|string|int', (string) $context->vars_in_scope['$b']);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage MixedPropertyFetch
     */
    public function testMixedPropertyFetch()
    {
        $filter = new Config\FileFilter(false);
        $filter->addExcludeFile('somefile.php');
        Config::getInstance()->setIssueHandler('MissingPropertyType', $filter);
        Config::getInstance()->setIssueHandler('MixedAssignment', $filter);

        $stmts = self::$parser->parse('<?php
        class Foo {
            /** @var string */
            public $foo;
        }

        /** @var mixed */
        $a = (new Foo());

        echo $a->foo;
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage MixedPropertyAssignment
     */
    public function testMixedPropertyAssignment()
    {
        $filter = new Config\FileFilter(false);
        $filter->addExcludeFile('somefile.php');
        Config::getInstance()->setIssueHandler('MissingPropertyType', $filter);
        Config::getInstance()->setIssueHandler('MixedAssignment', $filter);

        $stmts = self::$parser->parse('<?php
        class Foo {
            /** @var string */
            public $foo;
        }

        /** @var mixed */
        $a = (new Foo());

        $a->foo = "hello";
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage NullPropertyAssignment
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

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage NullPropertyFetch
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

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }
}
