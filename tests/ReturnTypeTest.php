<?php

namespace Psalm\Tests;

use PhpParser;
use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;

class ReturnTypeTest extends PHPUnit_Framework_TestCase
{
    protected static $_parser;

    public static function setUpBeforeClass()
    {
        self::$_parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);

        $config = \Psalm\Config::getInstance();
        $config->throw_exception = true;
        $config->use_docblock_types = true;
    }

    public function setUp()
    {
        \Psalm\ClassMethodChecker::clearCache();
        \Psalm\FileChecker::clearCache();
    }

    public function testReturnTypeAfterUselessNullcheck()
    {
        $stmts = self::$_parser->parse('<?php
        class One {
            public function foo() {}
        }

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

        $file_checker = new \Psalm\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testReturnTypeAfterNotEmptyCheck()
    {
        $stmts = self::$_parser->parse('<?php
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

        $file_checker = new \Psalm\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testTryCatchReturnType()
    {
        $stmts = self::$_parser->parse('<?php
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

        $file_checker = new \Psalm\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testSwitchReturnTypeWithFallthrough()
    {
        $stmts = self::$_parser->parse('<?php
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

        $file_checker = new \Psalm\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testSwitchReturnTypeWithFallthroughAndStatement()
    {
        $stmts = self::$_parser->parse('<?php
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

        $file_checker = new \Psalm\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException Psalm\Exception\CodeException
     */
    public function testSwitchReturnTypeWithFallthroughAndBreak()
    {
        $stmts = self::$_parser->parse('<?php
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

        $file_checker = new \Psalm\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException Psalm\Exception\CodeException
     */
    public function testSwitchReturnTypeWithFallthroughAndConditionalBreak()
    {
        $stmts = self::$_parser->parse('<?php
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

        $file_checker = new \Psalm\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException Psalm\Exception\CodeException
     */
    public function testSwitchReturnTypeWithNoDefault()
    {
        $stmts = self::$_parser->parse('<?php
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

        $file_checker = new \Psalm\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testSwitchReturnTypeWitDefaultException()
    {
        $stmts = self::$_parser->parse('<?php
        class A {
            /** @return bool */
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

        $file_checker = new \Psalm\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }
}
