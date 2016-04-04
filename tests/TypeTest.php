<?php

namespace CodeInspector\Tests;

use PhpParser;
use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;

class TypeTest extends PHPUnit_Framework_TestCase
{
    protected static $_parser;

    public static function setUpBeforeClass()
    {
        self::$_parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
    }

    /**
     * @expectedException CodeInspector\CodeException
     */
    public function testNullableMethodCall()
    {
        $stmts = self::$_parser->parse('<?php
        class A {
            public function foo() {}
        }

        class B {
            public function bar(A $a = null) {
                $a->foo();
            }
        }');

        $file_checker = new \CodeInspector\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testNullableMethodWithTernaryGuard()
    {
        $stmts = self::$_parser->parse('<?php
        class A {
            public function foo() {}
        }

        class B {
            public function bar(A $a = null) {
                $b = $a ? $a->foo() : null;
            }
        }');

        $file_checker = new \CodeInspector\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testNullableMethodWithTernaryIfNullGuard()
    {
        $stmts = self::$_parser->parse('<?php
        class A {
            public function foo() {}
        }

        class B {
            public function bar(A $a = null) {
                $b = $a === null ? null : $a->foo();
            }
        }');

        $file_checker = new \CodeInspector\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testNullableMethodWithIfGuard()
    {
        $stmts = self::$_parser->parse('<?php
        class A {
            public function foo() {}
        }

        class B {
            public function bar(A $a = null) {
                if ($a) {
                    $a->foo();
                }
            }
        }');

        $file_checker = new \CodeInspector\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException CodeInspector\CodeException
     */
    public function testNullableMethodCallWithThis()
    {
        $stmts = self::$_parser->parse('<?php
        class A {
            public function foo() {}
        }

        class B {
            public function bar(A $a = null) {
                $this->a = $a;
                $this->a->foo();
            }
        }');

        $file_checker = new \CodeInspector\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testNullableMethodWithTernaryGuardWithThis()
    {
        $stmts = self::$_parser->parse('<?php
        class A {
            public function foo() {}
        }

        class B {
            public function bar(A $a = null) {
                $this->a = $a;
                $b = $this->a ? $this->a->foo() : null;
            }
        }');

        $file_checker = new \CodeInspector\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testNullableMethodWithTernaryIfNullGuardWithThis()
    {
        $stmts = self::$_parser->parse('<?php
        class A {
            public function foo() {}
        }

        class B {
            public function bar(A $a = null) {
                $this->a = $a;
                $b = $this->a === null ? null : $this->a->foo();
            }
        }');

        $file_checker = new \CodeInspector\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testNullableMethodWithIfGuardWithThis()
    {
        $stmts = self::$_parser->parse('<?php
        class A {
            public function foo() {}
        }

        class B {
            public function bar(A $a = null) {
                $this->a = $a;

                if ($this->a) {
                    $this->a->foo();
                }
            }
        }');

        $file_checker = new \CodeInspector\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException CodeInspector\CodeException
     */
    public function testNullableMethodWithWrongIfGuard()
    {
        $stmts = self::$_parser->parse('<?php
        class One {
            public function foo() {}
        }

        class Two {
            public function foo() {}
        }

        class B {
            public function bar(One $one = null, Two $two = null) {
                if ($one) {
                    $two->foo();
                }
            }
        }');

        $file_checker = new \CodeInspector\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException CodeInspector\CodeException
     */
    public function testNullableMethodWithWrongBooleanIfGuard()
    {
        $stmts = self::$_parser->parse('<?php
        class One {
            public function foo() {}
        }

        class Two {
            public function foo() {}
        }

        class B {
            public function bar(One $one = null, Two $two = null) {
                if ($one || $two) {
                    $two->foo();
                }
            }
        }');

        $file_checker = new \CodeInspector\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testNullableMethodWithBooleanIfGuard()
    {
        $stmts = self::$_parser->parse('<?php
        class One {
            public function foo() {}
        }

        class Two {
            public function foo() {}
        }

        class B {
            public function bar(One $one = null, Two $two = null) {
                if ($one && $two) {
                    $two->foo();
                }
            }
        }');

        $file_checker = new \CodeInspector\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testNullableMethodWithNonNullBooleanIfGuard()
    {
        $stmts = self::$_parser->parse('<?php
        class One {
            public function foo() {}
        }

        class Two {
            public function foo() {}
        }

        class B {
            public function bar(One $one = null, Two $two = null) {
                if ($one !== null && $two) {
                    $one->foo();
                }
            }
        }');

        $file_checker = new \CodeInspector\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testNullableMethodWithNonNullBooleanIfGuardAndBooleanAnd()
    {
        $stmts = self::$_parser->parse('<?php
        class One {
            public function foo() {}
        }

        class Two {
            public function foo() {}
        }

        class B {
            public function bar(One $one = null, Two $two = null) {
                if ($one !== null && ($two || 1 + 1 === 3)) {
                    $one->foo();
                }
            }
        }');

        $file_checker = new \CodeInspector\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testNullableMethodWithIfGuardBefore()
    {
        $stmts = self::$_parser->parse('<?php
        class One {
            public function foo() {}
        }

        class Two {
            public function foo() {}
        }

        class B {
            public function bar(One $one = null, Two $two = null) {
                if ($one === null) {
                    return;
                }

                $one->foo();
            }
        }');

        $file_checker = new \CodeInspector\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException CodeInspector\CodeException
     */
    public function testNullableMethodWithWrongIfGuardBefore()
    {
        $stmts = self::$_parser->parse('<?php
        class One {
            public function foo() {}
        }

        class Two {
            public function foo() {}
        }

        class B {
            public function bar(One $one = null, Two $two = null) {
                if ($two === null) {
                    return;
                }

                $one->foo();
            }
        }');

        $file_checker = new \CodeInspector\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testNullableMethodWithBooleanIfGuardBefore()
    {
        $stmts = self::$_parser->parse('<?php
        class One {
            public function foo() {}
        }

        class Two {
            public function foo() {}
        }

        class B {
            public function bar(One $one = null, Two $two = null) {
                if ($one === null || $two === null) {
                    return;
                }

                $one->foo();
            }
        }');

        $file_checker = new \CodeInspector\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException CodeInspector\CodeException
     */
    public function testNullableMethodWithWrongBooleanIfGuardBefore()
    {
        $stmts = self::$_parser->parse('<?php
        class One {
            public function foo() {}
        }

        class Two {
            public function foo() {}
        }

        class B {
            public function bar(One $one = null, Two $two = null) {
                if ($one === null && $two === null) {
                    return;
                }

                $one->foo();
            }
        }');

        $file_checker = new \CodeInspector\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testNullableMethodWithGuardedRedefinition()
    {
        $stmts = self::$_parser->parse('<?php
        class One {
            public function foo() {}
        }

        class Two {
            public function foo() {}
        }

        class B {
            public function bar(One $one = null, Two $two = null) {
                if ($one === null) {
                    $one = new One();
                }

                $one->foo();
            }
        }');

        $file_checker = new \CodeInspector\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException CodeInspector\CodeException
     */
    public function testNullableMethodWithGuardedNestedIncompleteRedefinition()
    {
        $stmts = self::$_parser->parse('<?php
        class One {
            public function foo() {}
        }

        class Two {
            public function foo() {}
        }

        class B {
            public function bar(One $one = null, Two $two = null) {
                $a = 4;

                if ($one === null) {
                    if ($a === 4) {
                        $one = new One();
                    }
                }

                $one->foo();
            }
        }');

        $file_checker = new \CodeInspector\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testNullableMethodWithGuardedNestedRedefinition()
    {
        $stmts = self::$_parser->parse('<?php
        class One {
            public function foo() {}
        }

        class Two {
            public function foo() {}
        }

        class B {
            public function bar(One $one = null, Two $two = null) {
                $a = 4;

                if ($one === null) {
                    if ($a === 4) {
                        $one = new One();
                    }
                    else {
                        $one = new One();
                    }
                }

                $one->foo();
            }
        }');

        $file_checker = new \CodeInspector\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testNullableMethodWithGuardedNestedRedefinitionWithReturn()
    {
        $stmts = self::$_parser->parse('<?php
        class One {
            public function foo() {}
        }

        class Two {
            public function foo() {}
        }

        class B {
            public function bar(One $one = null, Two $two = null) {
                $a = 4;

                if ($one === null) {
                    if ($a === 4) {
                        $one = new One();
                        return;
                    }
                    else {
                        $one = new One();
                    }
                }

                $one->foo();
            }
        }');

        $file_checker = new \CodeInspector\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException CodeInspector\CodeException
     */
    public function testNullableMethodWithGuardedNestedRedefinitionWithBadReturn()
    {
        $stmts = self::$_parser->parse('<?php
        class One {
            public function foo() {}
        }

        class Two {
            public function foo() {}
        }

        class B {
            public function bar(One $one = null, Two $two = null) {
                $a = 4;

                if ($one === null) {
                    if ($a === 4) {
                        $one = new One();
                    }
                    else {
                        $one = new One();
                        return;
                    }
                }

                $one->foo();
            }
        }');

        $file_checker = new \CodeInspector\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testNullableMethodWithGuardedRedefinitionOnThis()
    {
        $stmts = self::$_parser->parse('<?php
        class One {
            public function foo() {}
        }

        class Two {
            public function foo() {}
        }

        class B {
            public function bar(One $one = null, Two $two = null) {
                $this->one = $one;

                if ($this->one === null) {
                    $this->one = new One();
                }

                $this->one->foo();
            }
        }');

        $file_checker = new \CodeInspector\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }
}
