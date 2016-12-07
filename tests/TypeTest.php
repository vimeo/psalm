<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

class TypeTest extends PHPUnit_Framework_TestCase
{
    protected static $parser;

    public static function setUpBeforeClass()
    {
        self::$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);

        $config = Config::getInstance();
        $config->throw_exception = true;
    }

    public function setUp()
    {
        FileChecker::clearCache();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     */
    public function testNullableMethodCall()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            public function foo() {}
        }

        class B {
            public function bar(A $a = null) {
                $a->foo();
            }
        }');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testNullableMethodWithTernaryGuard()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @return void */
            public function foo() {}
        }

        class B {
            /** @return void */
            public function bar(A $a = null) {
                $b = $a ? $a->foo() : null;
            }
        }');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testNullableMethodWithTernaryIfNullGuard()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @return void */
            public function foo() {}
        }

        class B {
            /** @return void */
            public function bar(A $a = null) {
                $b = $a === null ? null : $a->foo();
            }
        }');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testNullableMethodWithTernaryEmptyGuard()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @return void */
            public function foo() {}
        }

        class B {
            /** @return void */
            public function bar(A $a = null) {
                $b = empty($a) ? null : $a->foo();
            }
        }');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testNullableMethodWithTernaryIsNullGuard()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @return void */
            public function foo() {}
        }

        class B {
            /** @return void */
            public function bar(A $a = null) {
                $b = is_null($a) ? null : $a->foo();
            }
        }');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testNullableMethodWithIfGuard()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @return void */
            public function foo() {}
        }

        class B {
            /** @return void */
            public function bar(A $a = null) {
                if ($a) {
                    $a->foo();
                }
            }
        }');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     */
    public function testNullableMethodCallWithThis()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @return void */
            public function foo() {}
        }

        class B {
            /** @return void */
            public function bar(A $a = null) {
                $this->a = $a;
                $this->a->foo();
            }
        }');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testNullableMethodWithTernaryGuardWithThis()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @return void */
            public function foo() {}
        }

        class B {
            /** @var A|null */
            public $a;

            /** @return void */
            public function bar(A $a = null) {
                $this->a = $a;
                $b = $this->a ? $this->a->foo() : null;
            }
        }');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testNullableMethodWithTernaryIfNullGuardWithThis()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @return void */
            public function foo() {}
        }

        class B {
            /** @var A|null */
            public $a;

            /** @return void */
            public function bar(A $a = null) {
                $this->a = $a;
                $b = $this->a === null ? null : $this->a->foo();
            }
        }');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testNullableMethodWithIfGuardWithThis()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @return void */
            public function foo() {}
        }

        class B {
            /** @var A|null */
            public $a;

            /** @return void */
            public function bar(A $a = null) {
                $this->a = $a;

                if ($this->a) {
                    $this->a->foo();
                }
            }
        }');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     */
    public function testNullableMethodWithWrongIfGuard()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function foo() {}
        }

        class Two {
            /** @return void */
            public function foo() {}
        }

        class B {
            /** @return void */
            public function bar(One $one = null, Two $two = null) {
                if ($one) {
                    $two->foo();
                }
            }
        }');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testNullableMethodWithExceptionThrown()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function foo() {}
        }

        class B {
            /** @return void */
            public function bar(One $one = null) {
                if (!$one) {
                    throw new Exception();
                }

                $one->foo();
            }
        }');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testNullableMethodWithRedefinitionAndElse()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @var int|null */
            public $two;

            /** @return void */
            public function foo() {}
        }

        class B {
            /** @return void */
            public function bar(One $one = null) {
                if (!$one) {
                    $one = new One();
                }
                else {
                    $one->two = 3;
                }

                $one->foo();
            }
        }');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     */
    public function testNullableMethodWithWrongBooleanIfGuard()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function foo() {}
        }

        class Two {
            /** @return void */
            public function foo() {}
        }

        class B {
            /** @return void */
            public function bar(One $one = null, Two $two = null) {
                if ($one || $two) {
                    $two->foo();
                }
            }
        }');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testNullableMethodWithBooleanIfGuard()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function foo() {}
        }

        class Two {
            /** @return void */
            public function foo() {}
        }

        class B {
            /** @return void */
            public function bar(One $one = null, Two $two = null) {
                if ($one && $two) {
                    $two->foo();
                }
            }
        }');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testNullableMethodWithNonNullBooleanIfGuard()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function foo() {}
        }

        class Two {
            /** @return void */
            public function foo() {}
        }

        class B {
            /** @return void */
            public function bar(One $one = null, Two $two = null) {
                if ($one !== null && $two) {
                    $one->foo();
                }
            }
        }');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testNullableMethodWithNonNullBooleanIfGuardAndBooleanAnd()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function foo() {}
        }

        class Two {
            /** @return void */
            public function foo() {}
        }

        class B {
            /** @return void */
            public function bar(One $one = null, Two $two = null) {
                if ($one !== null && ($two || 1 + 1 === 3)) {
                    $one->foo();
                }
            }
        }');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }


    public function testNullableMethodInConditionWithIfGuardBefore()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @var string */
            public $a;

            /** @return void */
            public function foo() {}
        }

        class Two {
            /** @return void */
            public function foo() {}
        }

        class B {
            /** @return void */
            public function bar(One $one = null, Two $two = null) {
                if ($one === null) {
                    return;
                }

                if (!$one->a && $one->foo()) {
                    // do something
                }
            }
        }');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     */
    public function testNullableMethodWithWrongIfGuardBefore()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function foo() {}
        }

        class Two {
            /** @return void */
            public function foo() {}
        }

        class B {
            /** @return void */
            public function bar(One $one = null, Two $two = null) {
                if ($two === null) {
                    return;
                }

                $one->foo();
            }
        }');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testNullableMethodWithBooleanIfGuardBefore()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function foo() {}
        }

        class Two {
            /** @return void */
            public function foo() {}
        }

        class B {
            /** @return void */
            public function bar(One $one = null, Two $two = null) {
                if ($one === null || $two === null) {
                    return;
                }

                $one->foo();
            }
        }');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     */
    public function testNullableMethodWithWrongBooleanIfGuardBefore()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function foo() {}
        }

        class Two {
            /** @return void */
            public function foo() {}
        }

        class B {
            /** @return void */
            public function bar(One $one = null, Two $two = null) {
                if ($one === null && $two === null) {
                    return;
                }

                $one->foo();
            }
        }');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testNullableMethodWithGuardedRedefinition()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function foo() {}
        }

        class B {
            /** @return void */
            public function bar(One $one = null) {
                if ($one === null) {
                    $one = new One();
                }

                $one->foo();
            }
        }');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testNullableMethodWithGuardedRedefinitionInElse()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function foo() {}
        }

        class B {
            /** @return void */
            public function bar(One $one = null) {
                if ($one) {
                    // do nothing
                }
                else {
                    $one = new One();
                }

                $one->foo();
            }
        }');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     */
    public function testMethodWithMeaninglessCheck()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function foo() {}
        }

        class B {
            /** @return void */
            public function bar(One $one) {
                if (!$one) {
                    // do nothing
                }

                $one->foo();
            }
        }');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     */
    public function testNullableMethodWithGuardedNestedIncompleteRedefinition()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function foo() {}
        }

        class Two {
            /** @return void */
            public function foo() {}
        }

        class B {
            /** @return void */
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

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testNullableMethodWithGuardedNestedRedefinition()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function foo() {}
        }

        class Two {
            /** @return void */
            public function foo() {}
        }

        class B {
            /** @return void */
            public function bar(One $one = null) {
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

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testNullableMethodWithGuardedSwitchRedefinition()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function foo() {}
        }

        class B {
            /** @return void */
            public function bar(One $one = null) {
                $a = 4;

                if ($one === null) {
                    switch ($a) {
                        case 4:
                            $one = new One();
                            break;

                        default:
                            $one = new One();
                            break;
                    }
                }

                $one->foo();
            }
        }');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     */
    public function testNullableMethodWithGuardedSwitchRedefinitionNoDefault()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function foo() {}
        }

        class B {
            /** @return void */
            public function bar(One $one = null) {
                $a = 4;

                if ($one === null) {
                    switch ($a) {
                        case 4:
                            $one = new One();
                            break;
                    }
                }

                $one->foo();
            }
        }');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     */
    public function testNullableMethodWithGuardedSwitchRedefinitionEmptyDefault()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function foo() {}
        }

        class B {
            /** @return void */
            public function bar(One $one = null) {
                $a = 4;

                if ($one === null) {
                    switch ($a) {
                        case 4:
                            $one = new One();
                            break;

                        default:
                            break;
                    }
                }

                $one->foo();
            }
        }');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testNullableMethodWithGuardedSwitchRedefinitionDueToException()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function foo() {}
        }

        class B {
            /**
             * @return void
             */
            public function bar(One $one = null) {
                $a = 4;

                if ($one === null) {
                    switch ($a) {
                        case 4:
                            $one = new One();
                            break;

                        default:
                            throw new \Exception("bad");
                    }
                }

                $one->foo();
            }
        }');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testNullableMethodWithGuardedSwitchThatAlwaysReturns()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function foo() {}
        }

        class B {
            /** @return void */
            public function bar(One $one = null) {
                $a = 4;

                if ($one === null) {
                    switch ($a) {
                        case 4:
                            return;

                        default:
                            return;
                    }
                }

                $one->foo();
            }
        }');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testNullableMethodWithGuardedNestedRedefinitionWithReturn()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function foo() {}
        }

        class B {
            /** @return void */
            public function bar(One $one = null) {
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

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testNullableMethodWithGuardedNestedRedefinitionWithElseReturn()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function foo() {}
        }

        class B {
            /** @return void */
            public function bar(One $one = null) {
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

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     */
    public function testNullableMethodWithGuardedNestedRedefinitionWithUselessElseReturn()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function foo() {}
        }

        class B {
            /** @return void */
            public function bar(One $one = null) {
                $a = 4;

                if ($one === null) {
                    if ($a === 4) {
                        $one = new One();
                    }
                    else if ($a === 3) {
                        // do nothing
                    }
                    else {
                        $one = new One();
                        return;
                    }
                }

                $one->foo();
            }
        }');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testNullableMethodWithGuardedNestedRedefinitionWithElseifReturn()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function foo() {}
        }

        class B {
            /** @return void */
            public function bar(One $one = null) {
                $a = 4;

                if ($one === null) {
                    if ($a === 4) {
                        $one = new One();
                    }
                    else if ($a === 3) {
                        // do nothing
                        return;
                    }
                    else {
                        $one = new One();
                    }
                }

                $one->foo();
            }
        }');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testNullableMethodWithGuardedSwitchBreak()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function foo() {}
        }

        class B {
            /** @return void */
            public function bar(One $one = null) {
                $a = 4;

                switch ($a) {
                    case 4:
                        if ($one === null) {
                            break;
                        }

                        $one->foo();
                        break;
                }
            }
        }');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testNullableMethodWithGuardedRedefinitionOnThis()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function foo() {}
        }

        class B {
            /** @var One|null */
            public $one;

            /** @return void */
            public function bar(One $one = null) {
                $this->one = $one;

                if ($this->one === null) {
                    $this->one = new One();
                }

                $this->one->foo();
            }
        }');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testArrayUnionTypeAssertion()
    {
        $stmts = self::$parser->parse('<?php
            /** @var array|null */
            $ids = (1 + 1 === 2) ? [] : null;

            if ($ids === null) {
                $ids = [];
            }

            foreach ($ids as $id) {

            }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testArrayUnionTypeAssertionWithIsArray()
    {
        $stmts = self::$parser->parse('<?php
            /** @var array|null */
            $ids = (1 + 1 === 2) ? [] : null;

            if (!is_array($ids)) {
                $ids = [];
            }

            foreach ($ids as $id) {

            }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testVariableReassignment()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function foo() {}
        }

        class Two {
            /** @return void */
            public function bar() {}
        }

        $one = new One();

        $one = new Two();

        $one->bar();

        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testVariableReassignmentInIf()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function foo() {}
        }

        class Two {
            /** @return void */
            public function bar() {}
        }

        $one = new One();

        if (1 + 1 === 2) {
            $one = new Two();

            $one->bar();
        }

        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     */
    public function testVariableReassignmentInIfWithOutsideCall()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function foo() {}
        }

        class Two {
            /** @return void */
            public function bar() {}
        }

        $one = new One();

        if (1 + 1 === 2) {
            $one = new Two();

            $one->bar();
        }

        $one->bar();

        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testUnionTypeFlow()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function foo() {}
        }

        class Two {
            /** @return void */
            public function bar() {}
        }

        class Three {
            /** @return void */
            public function baz() {}
        }

        /** @var One|Two|Three|null */
        $var = null;

        if ($var instanceof One) {
            $var->foo();
        }
        else {
            if ($var instanceof Two) {
                $var->bar();
            }
            else if ($var) {
                $var->baz();
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testUnionTypeFlowWithThrow()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function foo() {}
        }

        /** @return void */
        function a(One $var = null) {
            if (!$var) {
                throw new \Exception("some exception");
            }
            else {
                $var->foo();
            }
        }

        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testUnionTypeFlowWithElseif()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function foo() {}
        }

        /** @var One|null */
        $var = null;

        if (rand(0,100) === 5) {

        }
        elseif (!$var) {

        }
        else {
            $var->foo();
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     */
    public function testUnnecessaryInstanceof()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            public function foo() {}
        }

        $var = new One();

        if ($var instanceof One) {
            $var->foo();
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     */
    public function testUnNegatableInstanceof()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function foo() {}
        }

        $var = new One();

        if ($var instanceof One) {
            $var->foo();
        }
        else {
            // do something
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testTypeAdjustment()
    {
        $stmts = self::$parser->parse('<?php
        $var = 0;

        if (5 + 3 === 8) {
            $var = "hello";
        }

        echo $var;
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();

        $return_stmt = array_pop($stmts);

        $this->assertSame('int|string', (string) $return_stmt->exprs[0]->inferredType);
    }

    public function testTypeMixedAdjustment()
    {
        $stmts = self::$parser->parse('<?php
        $var = 0;

        $arr = ["hello"];

        if (5 + 3 === 8) {
            $var = $arr[0];
        }

        echo $var;
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();

        $return_stmt = array_pop($stmts);

        $this->assertSame('int|string', (string) $return_stmt->exprs[0]->inferredType);
    }

    public function testTypeAdjustmentIfNull()
    {
        $stmts = self::$parser->parse('<?php
        class A {}
        class B {}

        $var = rand(0,10) > 5 ? new A : null;

        if ($var === null) {
            $var = new B;
        }

        echo $var;
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();

        $return_stmt = array_pop($stmts);

        $this->assertSame('A|B', (string) $return_stmt->exprs[0]->inferredType);
    }

    public function testWhileTrue()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /**
             * @return array|false
             */
            public function foo(){
                return rand(0,100) ? ["hello"] : false;
            }

            /** @return void */
            public function bar(){
                while ($row = $this->foo()) {
                    $row[0] = "bad";
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
    public function testWrongParam()
    {
        $stmts = self::$parser->parse('<?php
        class A {}

        class B {
            /** @return void */
            public function bar(A $a) {}
        }

        $b = new B();
        $b->bar(5);
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testPassingParam()
    {
        $stmts = self::$parser->parse('<?php
        class A {}

        class B {
            /** @return void */
            public function bar(A $a) {}
        }

        $b = new B();
        $b->bar(new A);
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testNullToNullableParam()
    {
        $stmts = self::$parser->parse('<?php
        class A {}

        class B {
            /** @return void */
            public function bar(A $a = null) {}
        }

        $b = new B();
        $b->bar(null);
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     */
    public function testIntToNullableObjectParam()
    {
        $stmts = self::$parser->parse('<?php
        class A {}

        class B {
            /** @return void */
            public function bar(A $a = null) {}
        }

        $b = new B();
        $b->bar(5);
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testObjectToNullableObjectParam()
    {
        $stmts = self::$parser->parse('<?php
        class A {}

        class B {
            /** @return void */
            public function bar(A $a = null) {}
        }

        $b = new B();
        $b->bar(new A);
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     */
    public function testParamCoercionWithBadArg()
    {
        $stmts = self::$parser->parse('<?php
        class A {}
        class B extends A {
            /** @return void */
            public function blab() {}
        }

        class C {
            /** @return void */
            function foo(A $a) {
                if ($a instanceof B) {
                    $a->bar();
                }
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testParamCoercion()
    {
        $stmts = self::$parser->parse('<?php
        class A {}
        class B extends A {
            /** @return void */
            public function bar() {}
        }

        class C {
            /** @return void */
            function foo(A $a) {
                if ($a instanceof B) {
                    $a->bar();
                }
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testParamElseifCoercion()
    {
        $stmts = self::$parser->parse('<?php
        class A {}
        class B extends A {
            /** @return void */
            public function bar() {}
        }
        class C extends A {
            /** @return void */
            public function baz() {}
        }

        class D {
            /** @return void */
            function foo(A $a) {
                if ($a instanceof B) {
                    $a->bar();
                }
                elseif ($a instanceof C) {
                    $a->baz();
                }
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testAssignInsideForeach()
    {
        $stmts = self::$parser->parse('<?php
        $b = false;

        foreach ([1, 2, 3, 4] as $a) {
            if ($a === rand(0, 10)) {
                $b = true;
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);

        $this->assertSame('bool', (string) $context->vars_in_scope['$b']);
    }

    public function testAssignInsideForeachWithBreak()
    {
        $stmts = self::$parser->parse('<?php
        $b = false;

        foreach ([1, 2, 3, 4] as $a) {
            if ($a === rand(0, 10)) {
                $b = true;
                break;
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);

        $this->assertSame('bool', (string) $context->vars_in_scope['$b']);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage NullReference
     */
    public function testNullCheckInsideForeachWithNoLeaveStatement()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @return array<A|null> */
            public static function loadMultiple()
            {
                return [new A, null];
            }

            /** @return void */
            public function bar() {

            }
        }

        foreach (A::loadMultiple() as $a) {
            if ($a === null) {
                // do nothing
            }

            $a->bar();
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check();
    }

    public function testNullCheckInsideForeachWithContinue()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @return array<A|null> */
            public static function loadMultiple()
            {
                return [new A, null];
            }

            /** @return void */
            public function bar() {

            }
        }

        foreach (A::loadMultiple() as $a) {
            if ($a === null) {
                continue;
            }

            $a->bar();
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check();
    }

    public function testIfNotEqualFalse()
    {
        $this->markTestIncomplete('This currently fails');
        $stmts = self::$parser->parse('<?php
        if (($row = rand(0,10) ? [] : false) !== false) {
           $row[0] = "good";
           echo $row[0];
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }
}
