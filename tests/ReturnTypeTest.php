<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

class ReturnTypeTest extends PHPUnit_Framework_TestCase
{
    protected static $parser;

    public static function setUpBeforeClass()
    {
        self::$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);

        $config = Config::getInstance();
        $config->throw_exception = true;
        $config->use_docblock_types = true;
    }

    public function setUp()
    {
        FileChecker::clearCache();
    }

    public function testReturnTypeAfterUselessNullcheck()
    {
        $stmts = self::$parser->parse('<?php
        class One {}

        class B {
            /**
             * @return One|null
             */
            public function bar() {
                $baz = rand(0,100) > 50 ? new One() : null;

                // should have no effect
                if ($baz === null) {
                    $baz = null;
                }

                return $baz;
            }
        }');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testReturnTypeNotEmptyCheck()
    {
        $stmts = self::$parser->parse('<?php
        class B {
            /**
             * @param string|null $str
             * @return string
             */
            public function bar($str) {
                if (empty($str)) {
                    $str = "";
                }
                return $str;
            }
        }');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testReturnTypeNotEmptyCheckInElseIf()
    {
        $stmts = self::$parser->parse('<?php
        class B {
            /**
             * @param string|null $str
             * @return string
             */
            public function bar($str) {
                if ($str === "badger") {
                    // do nothing
                }
                elseif (empty($str)) {
                    $str = "";
                }
                return $str;
            }
        }');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testReturnTypeNotEmptyCheckInElse()
    {
        $stmts = self::$parser->parse('<?php
        class B {
            /**
             * @param string|null $str
             * @return string
             */
            public function bar($str) {
                if (!empty($str)) {
                    // do nothing
                }
                else {
                    $str = "";
                }
                return $str;
            }
        }');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testReturnTypeAfterIf()
    {
        $stmts = self::$parser->parse('<?php
        class B {
            /**
             * @return string|null
             */
            public function bar() {
                $str = null;
                $bar1 = rand(0, 100) > 40;
                if ($bar1) {
                    $str = "";
                }
                return $str;
            }
        }');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testReturnTypeAfterTwoIfsWithThrow()
    {
        $stmts = self::$parser->parse('<?php
        class A1 {
        }
        class A2 {
        }
        class B {
            /**
             * @return A1
             */
            public function bar(A1 $a1 = null, A2 $a2 = null) {
                if (!$a1) {
                    throw new \Exception();
                }
                if (!$a2) {
                    throw new \Exception();
                }
                return $a1;
            }
        }');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testReturnTypeAfterIfElseIfWithThrow()
    {
        $stmts = self::$parser->parse('<?php
        class A1 {
        }
        class A2 {
        }
        class B {
            /**
             * @return A1
             */
            public function bar(A1 $a1 = null, A2 $a2 = null) {
                if (!$a1) {
                    throw new \Exception();
                }
                elseif (!$a2) {
                    throw new \Exception();
                }
                return $a1;
            }
        }');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testTryCatchReturnType()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @return bool */
            public function foo() {
                try {
                    // do a thing
                    return true;
                }
                catch (\Exception $e) {
                    throw $e;
                }
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testSwitchReturnTypeWithFallthrough()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @return bool */
            public function foo() {
                switch (rand(0,10)) {
                    case 1:
                    default:
                        return true;
                }
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testSwitchReturnTypeWithFallthroughAndStatement()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @return bool */
            public function foo() {
                switch (rand(0,10)) {
                    case 1:
                        $a = 5;
                    default:
                        return true;
                }
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     */
    public function testSwitchReturnTypeWithFallthroughAndBreak()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @return bool */
            public function foo() {
                switch (rand(0,10)) {
                    case 1:
                        break;
                    default:
                        return true;
                }
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     */
    public function testSwitchReturnTypeWithFallthroughAndConditionalBreak()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @return bool */
            public function foo() {
                switch (rand(0,10)) {
                    case 1:
                        if (rand(0,10) === 5) {
                            break;
                        }
                    default:
                        return true;
                }
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     */
    public function testSwitchReturnTypeWithNoDefault()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @return bool */
            public function foo() {
                switch (rand(0,10)) {
                    case 1:
                    case 2:
                        return true;
                }
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testSwitchReturnTypeWitDefaultException()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /**
             * @psalm-suppress TooManyArguments
             * @return bool
             */
            public function foo() {
                switch (rand(0,10)) {
                    case 1:
                    case 2:
                        return true;

                    default:
                        throw new \Exception("badness");
                }
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testExtendsStaticCallReturnType()
    {
        $stmts = self::$parser->parse('<?php
        abstract class A {
            /** @return static */
            public static function load() {
                return new static();
            }
        }

        class B extends A {
        }

        $b = B::load();
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);

        $this->assertEquals('B', (string) $context->vars_in_scope['$b']);
    }

    public function testExtendsStaticCallArrayReturnType()
    {
        $stmts = self::$parser->parse('<?php
        abstract class A {
            /** @return array<int,static> */
            public static function loadMultiple() {
                return [new static()];
            }
        }

        class B extends A {
        }

        $bees = B::loadMultiple();
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);

        $this->assertEquals('array<int, B>', (string) $context->vars_in_scope['$bees']);
    }

    public function testIssetReturnType()
    {
        $stmts = self::$parser->parse('<?php
        /**
         * @param  mixed $foo
         * @return bool
         */
        function a($foo = null) {
            return isset($foo);
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    public function testThisReturnType()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @return $this */
            public function getThis() {
                return $this;
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    public function testOverrideReturnType()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @return string|null */
            public function blah() {
                return rand(0, 10) === 4 ? "blah" : null;
            }
        }

        class B extends A {
            /** @return string */
            public function blah() {
                return "blah";
            }
        }

        $blah = (new B())->blah();
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('string', (string) $context->vars_in_scope['$blah']);
    }

    public function testInterfaceReturnType()
    {
        $stmts = self::$parser->parse('<?php
        interface A {
            /** @return string|null */
            public function blah();
        }

        class B implements A {
            public function blah() {
                return rand(0, 10) === 4 ? "blah" : null;
            }
        }

        $blah = (new B())->blah();
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('string|null', (string) $context->vars_in_scope['$blah']);
    }

    public function testOverrideReturnTypeInGrandparent()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @return string|null */
            public function blah();
        }

        class B extends A {
        }

        class C extends B {
            public function blah() {
                return rand(0, 10) === 4 ? "blahblah" : null;
            }
        }

        $blah = (new C())->blah();
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('string|null', (string) $context->vars_in_scope['$blah']);
    }
}
