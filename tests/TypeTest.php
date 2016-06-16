<?php

namespace CodeInspector\Tests;

use CodeInspector\Type;

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

    public function testReconciliation()
    {
        $this->assertEquals('Object', \CodeInspector\TypeChecker::reconcileTypes('!null', ['Object']));
        $this->assertEquals('Object', \CodeInspector\TypeChecker::reconcileTypes('!null', ['Object', 'null']));
        $this->assertEquals('Object|false', \CodeInspector\TypeChecker::reconcileTypes('!null', ['Object', 'false']));
        $this->assertEquals('Object', \CodeInspector\TypeChecker::reconcileTypes('!empty', ['Object']));
        $this->assertEquals('Object', \CodeInspector\TypeChecker::reconcileTypes('!empty', ['Object', 'null']));
        $this->assertEquals('Object', \CodeInspector\TypeChecker::reconcileTypes('!empty', ['Object', 'false']));
        $this->assertEquals('null', \CodeInspector\TypeChecker::reconcileTypes('null', ['Object', 'null']));
        $this->assertEquals('null', \CodeInspector\TypeChecker::reconcileTypes('null', ['Object']));
        $this->assertEquals('null', \CodeInspector\TypeChecker::reconcileTypes('null', ['Object', 'false']));
        $this->assertEquals('null', \CodeInspector\TypeChecker::reconcileTypes('empty', ['Object']));
        $this->assertEquals('false', \CodeInspector\TypeChecker::reconcileTypes('empty', ['Object', 'false']));
        $this->assertEquals('false', \CodeInspector\TypeChecker::reconcileTypes('empty', ['Object', 'bool']));

        $this->assertEquals('bool', \CodeInspector\TypeChecker::reconcileTypes('!Object', ['Object', 'bool']));
        $this->assertEquals('Object', \CodeInspector\TypeChecker::reconcileTypes('Object', ['Object', 'bool']));
        $this->assertEquals('null', \CodeInspector\TypeChecker::reconcileTypes('!Object', ['Object', 'null']));
        $this->assertEquals('ObjectA', \CodeInspector\TypeChecker::reconcileTypes('ObjectA', ['ObjectA', 'ObjectB']));
        $this->assertEquals('ObjectB', \CodeInspector\TypeChecker::reconcileTypes('!ObjectA', ['ObjectA', 'ObjectB']));

        $this->assertEquals('mixed', \CodeInspector\TypeChecker::reconcileTypes('!empty', ['mixed']));
        $this->assertEquals('mixed', \CodeInspector\TypeChecker::reconcileTypes('!null', ['mixed']));
        $this->assertEquals('mixed', \CodeInspector\TypeChecker::reconcileTypes('mixed', ['mixed']));
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

    public function testNullableMethodWithTernaryEmptyGuard()
    {
        $stmts = self::$_parser->parse('<?php
        class A {
            public function foo() {}
        }

        class B {
            public function bar(A $a = null) {
                $b = empty($a) ? null : $a->foo();
            }
        }');

        $file_checker = new \CodeInspector\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testNullableMethodWithTernaryIsNullGuard()
    {
        $stmts = self::$_parser->parse('<?php
        class A {
            public function foo() {}
        }

        class B {
            public function bar(A $a = null) {
                $b = is_null($a) ? null : $a->foo();
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

    public function testNullableMethodWithExceptionThrown()
    {
        $stmts = self::$_parser->parse('<?php
        class One {
            public function foo() {}
        }

        class B {
            public function bar(One $one = null) {
                if (!$one) {
                    throw new Exception();
                }

                $one->foo();
            }
        }');

        $file_checker = new \CodeInspector\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testNullableMethodWithRedefinitionAndElse()
    {
        $stmts = self::$_parser->parse('<?php
        class One {
            public function foo() {}
        }

        class B {
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


    public function testNullableMethodInConditionWithIfGuardBefore()
    {
        $stmts = self::$_parser->parse('<?php
        class One {
            public $a;

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

                if (!$one->a && $one->foo()) {
                    // do something
                }
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

    public function testNullableMethodWithGuardedRedefinitionInElse()
    {
        $stmts = self::$_parser->parse('<?php
        class One {
            public function foo() {}
        }

        class B {
            public function bar(One $one = null, Two $two = null) {
                if ($one) {
                    // do nothing
                }
                else {
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

    public function testNullableMethodWithGuardedSwitchRedefinition()
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

    public function testNullableMethodWithGuardedNestedRedefinitionWithElseReturn()
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

    /**
     * @expectedException CodeInspector\CodeException
     */
    public function testNullableMethodWithGuardedNestedRedefinitionWithUselessElseReturn()
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

        $file_checker = new \CodeInspector\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testNullableMethodWithGuardedNestedRedefinitionWithElseifReturn()
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

        $file_checker = new \CodeInspector\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testNullableMethodWithGuardedSwitchBreak()
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

    public function testArrayUnionTypeAssertion()
    {
        $stmts = self::$_parser->parse('<?php
            /** @var array|null */
            $ids = (1 + 1 === 2) ? [] : null;

            if ($ids === null) {
                $ids = [];
            }

            foreach ($ids as $id) {

            }
        ');

        $file_checker = new \CodeInspector\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testArrayUnionTypeAssertionWithIsArray()
    {
        $stmts = self::$_parser->parse('<?php
            /** @var array|null */
            $ids = (1 + 1 === 2) ? [] : null;

            if (!is_array($ids)) {
                $ids = [];
            }

            foreach ($ids as $id) {

            }
        ');

        $file_checker = new \CodeInspector\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testVariableReassignment()
    {
        $stmts = self::$_parser->parse('<?php
        class One {
            public function foo() {}
        }

        class Two {
            public function bar() {}
        }

        $one = new One();

        $one = new Two();

        $one->bar();

        ');

        $file_checker = new \CodeInspector\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testVariableReassignmentInIf()
    {
        $stmts = self::$_parser->parse('<?php
        class One {
            public function foo() {}
        }

        class Two {
            public function bar() {}
        }

        $one = new One();

        if (1 + 1 === 2) {
            $one = new Two();

            $one->bar();
        }

        ');

        $file_checker = new \CodeInspector\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException CodeInspector\CodeException
     */
    public function testVariableReassignmentInIfWithOutsideCall()
    {
        $stmts = self::$_parser->parse('<?php
        class One {
            public function foo() {}
        }

        class Two {
            public function bar() {}
        }

        $one = new One();

        if (1 + 1 === 2) {
            $one = new Two();

            $one->bar();
        }

        $one->bar();

        ');

        $file_checker = new \CodeInspector\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testUnionTypeFlow()
    {
        $stmts = self::$_parser->parse('<?php
        class One {
            public function foo() {}
        }

        class Two {
            public function bar() {}
        }

        class Three {
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

        $file_checker = new \CodeInspector\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testUnnecessaryInstanceof()
    {
        $stmts = self::$_parser->parse('<?php
        class One {
            public function foo() {}
        }

        class Two {
            public function bar() {}
        }

        class Three {
            public function baz() {}
        }


        $var = new One();

        if ($var instanceof One) {
            $var->foo();
        }
        ');

        $file_checker = new \CodeInspector\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException CodeInspector\CodeException
     */
    public function testUnNegatableInstanceof()
    {
        $stmts = self::$_parser->parse('<?php
        class One {
            public function foo() {}
        }

        class Two {
            public function bar() {}
        }

        class Three {
            public function baz() {}
        }


        $var = new One();

        if ($var instanceof One) {
            $var->foo();
        }
        else {
            // do something
        }
        ');

        $file_checker = new \CodeInspector\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testTypeAdjustment()
    {
        $stmts = self::$_parser->parse('<?php
        class One {
            public function foo(){
                $var = 0;

                if (5 + 3 === 8) {
                    $var = "hello";
                }

                return $var;
            }
        }
        ');

        $file_checker = new \CodeInspector\FileChecker('somefile.php', $stmts);
        $stmts = $file_checker->check();

        $method_stmts = $stmts[0]->stmts[0]->stmts;

        $return_stmt = array_pop($method_stmts);

        $this->assertSame('int|string', (string) $return_stmt->returnType);
    }

    public function testTypeMixedAdjustment()
    {
        $stmts = self::$_parser->parse('<?php
        class One {
            public function foo(){
                $var = 0;

                $arr = ["hello"];

                if (5 + 3 === 8) {
                    $var = $arr[0];
                }

                return $var;
            }
        }
        ');

        $file_checker = new \CodeInspector\FileChecker('somefile.php', $stmts);
        $stmts = $file_checker->check();

        $method_stmts = $stmts[0]->stmts[0]->stmts;

        $return_stmt = array_pop($method_stmts);

        $this->assertSame('mixed', (string) $return_stmt->returnType);
    }

    public function testSwitchVariableWithContinue()
    {
        $stmts = self::$_parser->parse('<?php
        class B {
            public function bar() {
                foreach ([\'a\', \'b\', \'c\'] as $letter) {
                    switch ($letter) {
                        case \'a\':
                            $foo = 1;
                            break;
                        case \'b\':
                            $foo = 2;
                            break;
                        default:
                            continue;
                    }

                    $moo = $foo;
                }
            }
        }');

        $file_checker = new \CodeInspector\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }
}
