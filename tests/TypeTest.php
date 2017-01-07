<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

class TypeTest extends PHPUnit_Framework_TestCase
{
    /** @var \PhpParser\Parser */
    protected static $parser;

    /** @var \Psalm\Checker\ProjectChecker */
    protected $project_checker;

    public static function setUpBeforeClass()
    {
        self::$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);

        $config = new TestConfig();
        $config->throw_exception = true;
    }

    public function setUp()
    {
        FileChecker::clearCache();
        $this->project_checker = new \Psalm\Checker\ProjectChecker();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     */
    public function testNullableMethodCall()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            public function fooFoo() {}
        }

        class B {
            public function barBar(A $a = null) {
                $a->fooFoo();
            }
        }');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    public function testNullableMethodWithTernaryGuard()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @return void */
            public function fooFoo() {}
        }

        class B {
            /** @return void */
            public function barBar(A $a = null) {
                $b = $a ? $a->fooFoo() : null;
            }
        }');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    public function testNullableMethodWithTernaryIfNullGuard()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @return void */
            public function fooFoo() {}
        }

        class B {
            /** @return void */
            public function barBar(A $a = null) {
                $b = $a === null ? null : $a->fooFoo();
            }
        }');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    public function testNullableMethodWithTernaryEmptyGuard()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @return void */
            public function fooFoo() {}
        }

        class B {
            /** @return void */
            public function barBar(A $a = null) {
                $b = empty($a) ? null : $a->fooFoo();
            }
        }');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    public function testNullableMethodWithTernaryIsNullGuard()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @return void */
            public function fooFoo() {}
        }

        class B {
            /** @return void */
            public function barBar(A $a = null) {
                $b = is_null($a) ? null : $a->fooFoo();
            }
        }');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    public function testNullableMethodWithIfGuard()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @return void */
            public function fooFoo() {}
        }

        class B {
            /** @return void */
            public function barBar(A $a = null) {
                if ($a) {
                    $a->fooFoo();
                }
            }
        }');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     */
    public function testNullableMethodCallWithThis()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @return void */
            public function fooFoo() {}
        }

        class B {
            /** @return void */
            public function barBar(A $a = null) {
                $this->a = $a;
                $this->a->fooFoo();
            }
        }');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    public function testNullableMethodWithTernaryGuardWithThis()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @return void */
            public function fooFoo() {}
        }

        class B {
            /** @var A|null */
            public $a;

            /** @return void */
            public function barBar(A $a = null) {
                $this->a = $a;
                $b = $this->a ? $this->a->fooFoo() : null;
            }
        }');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    public function testNullableMethodWithTernaryIfNullGuardWithThis()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @return void */
            public function fooFoo() {}
        }

        class B {
            /** @var A|null */
            public $a;

            /** @return void */
            public function barBar(A $a = null) {
                $this->a = $a;
                $b = $this->a === null ? null : $this->a->fooFoo();
            }
        }');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    public function testNullableMethodWithIfGuardWithThis()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @return void */
            public function fooFoo() {}
        }

        class B {
            /** @var A|null */
            public $a;

            /** @return void */
            public function barBar(A $a = null) {
                $this->a = $a;

                if ($this->a) {
                    $this->a->fooFoo();
                }
            }
        }');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     */
    public function testNullableMethodWithWrongIfGuard()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function fooFoo() {}
        }

        class Two {
            /** @return void */
            public function fooFoo() {}
        }

        class B {
            /** @return void */
            public function barBar(One $one = null, Two $two = null) {
                if ($one) {
                    $two->fooFoo();
                }
            }
        }');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    public function testNullableMethodWithExceptionThrown()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function fooFoo() {}
        }

        class B {
            /** @return void */
            public function barBar(One $one = null) {
                if (!$one) {
                    throw new Exception();
                }

                $one->fooFoo();
            }
        }');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    public function testNullableMethodWithRedefinitionAndElse()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @var int|null */
            public $two;

            /** @return void */
            public function fooFoo() {}
        }

        class B {
            /** @return void */
            public function barBar(One $one = null) {
                if (!$one) {
                    $one = new One();
                }
                else {
                    $one->two = 3;
                }

                $one->fooFoo();
            }
        }');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     */
    public function testNullableMethodWithWrongBooleanIfGuard()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function fooFoo() {}
        }

        class Two {
            /** @return void */
            public function fooFoo() {}
        }

        class B {
            /** @return void */
            public function barBar(One $one = null, Two $two = null) {
                if ($one || $two) {
                    $two->fooFoo();
                }
            }
        }');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    public function testNullableMethodWithBooleanIfGuard()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function fooFoo() {}
        }

        class Two {
            /** @return void */
            public function fooFoo() {}
        }

        class B {
            /** @return void */
            public function barBar(One $one = null, Two $two = null) {
                if ($one && $two) {
                    $two->fooFoo();
                }
            }
        }');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    public function testNullableMethodWithNonNullBooleanIfGuard()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function fooFoo() {}
        }

        class Two {
            /** @return void */
            public function fooFoo() {}
        }

        class B {
            /** @return void */
            public function barBar(One $one = null, Two $two = null) {
                if ($one !== null && $two) {
                    $one->fooFoo();
                }
            }
        }');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    public function testNullableMethodWithNonNullBooleanIfGuardAndBooleanAnd()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function fooFoo() {}
        }

        class Two {
            /** @return void */
            public function fooFoo() {}
        }

        class B {
            /** @return void */
            public function barBar(One $one = null, Two $two = null) {
                if ($one !== null && ($two || 1 + 1 === 3)) {
                    $one->fooFoo();
                }
            }
        }');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }


    public function testNullableMethodInConditionWithIfGuardBefore()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @var string */
            public $a;

            /** @return void */
            public function fooFoo() {}
        }

        class Two {
            /** @return void */
            public function fooFoo() {}
        }

        class B {
            /** @return void */
            public function barBar(One $one = null, Two $two = null) {
                if ($one === null) {
                    return;
                }

                if (!$one->a && $one->fooFoo()) {
                    // do something
                }
            }
        }');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     */
    public function testNullableMethodWithWrongIfGuardBefore()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function fooFoo() {}
        }

        class Two {
            /** @return void */
            public function fooFoo() {}
        }

        class B {
            /** @return void */
            public function barBar(One $one = null, Two $two = null) {
                if ($two === null) {
                    return;
                }

                $one->fooFoo();
            }
        }');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    public function testNullableMethodWithBooleanIfGuardBefore()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function fooFoo() {}
        }

        class Two {
            /** @return void */
            public function fooFoo() {}
        }

        class B {
            /** @return void */
            public function barBar(One $one = null, Two $two = null) {
                if ($one === null || $two === null) {
                    return;
                }

                $one->fooFoo();
            }
        }');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     */
    public function testNullableMethodWithWrongBooleanIfGuardBefore()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function fooFoo() {}
        }

        class Two {
            /** @return void */
            public function fooFoo() {}
        }

        class B {
            /** @return void */
            public function barBar(One $one = null, Two $two = null) {
                if ($one === null && $two === null) {
                    return;
                }

                $one->fooFoo();
            }
        }');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    public function testNullableMethodWithGuardedRedefinition()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function fooFoo() {}
        }

        class B {
            /** @return void */
            public function barBar(One $one = null) {
                if ($one === null) {
                    $one = new One();
                }

                $one->fooFoo();
            }
        }');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    public function testNullableMethodWithGuardedRedefinitionInElse()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function fooFoo() {}
        }

        class B {
            /** @return void */
            public function barBar(One $one = null) {
                if ($one) {
                    // do nothing
                }
                else {
                    $one = new One();
                }

                $one->fooFoo();
            }
        }');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     */
    public function testMethodWithMeaninglessCheck()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function fooFoo() {}
        }

        class B {
            /** @return void */
            public function barBar(One $one) {
                if (!$one) {
                    // do nothing
                }

                $one->fooFoo();
            }
        }');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     */
    public function testNullableMethodWithGuardedNestedIncompleteRedefinition()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function fooFoo() {}
        }

        class Two {
            /** @return void */
            public function fooFoo() {}
        }

        class B {
            /** @return void */
            public function barBar(One $one = null, Two $two = null) {
                $a = 4;

                if ($one === null) {
                    if ($a === 4) {
                        $one = new One();
                    }
                }

                $one->fooFoo();
            }
        }');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    public function testNullableMethodWithGuardedNestedRedefinition()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function fooFoo() {}
        }

        class Two {
            /** @return void */
            public function fooFoo() {}
        }

        class B {
            /** @return void */
            public function barBar(One $one = null) {
                $a = 4;

                if ($one === null) {
                    if ($a === 4) {
                        $one = new One();
                    }
                    else {
                        $one = new One();
                    }
                }

                $one->fooFoo();
            }
        }');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    public function testNullableMethodWithGuardedSwitchRedefinition()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function fooFoo() {}
        }

        class B {
            /** @return void */
            public function barBar(One $one = null) {
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

                $one->fooFoo();
            }
        }');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     */
    public function testNullableMethodWithGuardedSwitchRedefinitionNoDefault()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function fooFoo() {}
        }

        class B {
            /** @return void */
            public function barBar(One $one = null) {
                $a = 4;

                if ($one === null) {
                    switch ($a) {
                        case 4:
                            $one = new One();
                            break;
                    }
                }

                $one->fooFoo();
            }
        }');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     */
    public function testNullableMethodWithGuardedSwitchRedefinitionEmptyDefault()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function fooFoo() {}
        }

        class B {
            /** @return void */
            public function barBar(One $one = null) {
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

                $one->fooFoo();
            }
        }');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    public function testNullableMethodWithGuardedSwitchRedefinitionDueToException()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function fooFoo() {}
        }

        class B {
            /**
             * @return void
             */
            public function barBar(One $one = null) {
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

                $one->fooFoo();
            }
        }');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    public function testNullableMethodWithGuardedSwitchThatAlwaysReturns()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function fooFoo() {}
        }

        class B {
            /** @return void */
            public function barBar(One $one = null) {
                $a = 4;

                if ($one === null) {
                    switch ($a) {
                        case 4:
                            return;

                        default:
                            return;
                    }
                }

                $one->fooFoo();
            }
        }');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    public function testNullableMethodWithGuardedNestedRedefinitionWithReturn()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function fooFoo() {}
        }

        class B {
            /** @return void */
            public function barBar(One $one = null) {
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

                $one->fooFoo();
            }
        }');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    public function testNullableMethodWithGuardedNestedRedefinitionWithElseReturn()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function fooFoo() {}
        }

        class B {
            /** @return void */
            public function barBar(One $one = null) {
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

                $one->fooFoo();
            }
        }');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     */
    public function testNullableMethodWithGuardedNestedRedefinitionWithUselessElseReturn()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function fooFoo() {}
        }

        class B {
            /** @return void */
            public function barBar(One $one = null) {
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

                $one->fooFoo();
            }
        }');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    public function testNullableMethodWithGuardedNestedRedefinitionWithElseifReturn()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function fooFoo() {}
        }

        class B {
            /** @return void */
            public function barBar(One $one = null) {
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

                $one->fooFoo();
            }
        }');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    public function testNullableMethodWithGuardedSwitchBreak()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function fooFoo() {}
        }

        class B {
            /** @return void */
            public function barBar(One $one = null) {
                $a = 4;

                switch ($a) {
                    case 4:
                        if ($one === null) {
                            break;
                        }

                        $one->fooFoo();
                        break;
                }
            }
        }');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    public function testNullableMethodWithGuardedRedefinitionOnThis()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function fooFoo() {}
        }

        class B {
            /** @var One|null */
            public $one;

            /** @return void */
            public function barBar(One $one = null) {
                $this->one = $one;

                if ($this->one === null) {
                    $this->one = new One();
                }

                $this->one->fooFoo();
            }
        }');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    public function testArrayUnionTypeAssertion()
    {
        $stmts = self::$parser->parse('<?php
            /** @var array|null */
            $ids = (1 + 1 === 2) ? [] : null;

            if ($ids === null) {
                $ids = [];
            }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('array<empty, empty>', (string) $context->vars_in_scope['$ids']);
    }

    public function testArrayUnionTypeAssertionWithIsArray()
    {
        $stmts = self::$parser->parse('<?php
            /** @var array|null */
            $ids = (1 + 1 === 2) ? [] : null;

            if (!is_array($ids)) {
                $ids = [];
            }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('array<empty, empty>', (string) $context->vars_in_scope['$ids']);
    }

    public function testVariableReassignment()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function fooFoo() {}
        }

        class Two {
            /** @return void */
            public function barBar() {}
        }

        $one = new One();

        $one = new Two();

        $one->barBar();

        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    public function testVariableReassignmentInIf()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function fooFoo() {}
        }

        class Two {
            /** @return void */
            public function barBar() {}
        }

        $one = new One();

        if (1 + 1 === 2) {
            $one = new Two();

            $one->barBar();
        }

        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     */
    public function testVariableReassignmentInIfWithOutsideCall()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function fooFoo() {}
        }

        class Two {
            /** @return void */
            public function barBar() {}
        }

        $one = new One();

        if (1 + 1 === 2) {
            $one = new Two();

            $one->barBar();
        }

        $one->barBar();

        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    public function testUnionTypeFlow()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function fooFoo() {}
        }

        class Two {
            /** @return void */
            public function barBar() {}
        }

        class Three {
            /** @return void */
            public function baz() {}
        }

        /** @var One|Two|Three|null */
        $var = null;

        if ($var instanceof One) {
            $var->fooFoo();
        }
        else {
            if ($var instanceof Two) {
                $var->barBar();
            }
            else if ($var) {
                $var->baz();
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    public function testUnionTypeFlowWithThrow()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function fooFoo() {}
        }

        /** @return void */
        function a(One $var = null) {
            if (!$var) {
                throw new \Exception("some exception");
            }
            else {
                $var->fooFoo();
            }
        }

        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    public function testUnionTypeFlowWithElseif()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function fooFoo() {}
        }

        /** @var One|null */
        $var = null;

        if (rand(0,100) === 5) {

        }
        elseif (!$var) {

        }
        else {
            $var->fooFoo();
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     */
    public function testUnnecessaryInstanceof()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            public function fooFoo() {}
        }

        $var = new One();

        if ($var instanceof One) {
            $var->fooFoo();
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     */
    public function testUnNegatableInstanceof()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /** @return void */
            public function fooFoo() {}
        }

        $var = new One();

        if ($var instanceof One) {
            $var->fooFoo();
        }
        else {
            // do something
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
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
        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('int|string', (string) $context->vars_in_scope['$var']);
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
        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('int|string', (string) $context->vars_in_scope['$var']);
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
        ');
        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('A|B', (string) $context->vars_in_scope['$var']);
    }

    public function testWhileTrue()
    {
        $stmts = self::$parser->parse('<?php
        class One {
            /**
             * @return array|false
             */
            public function fooFoo(){
                return rand(0,100) ? ["hello"] : false;
            }

            /** @return void */
            public function barBar(){
                while ($row = $this->fooFoo()) {
                    $row[0] = "bad";
                }
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
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
            public function barBar(A $a) {}
        }

        $b = new B();
        $b->barBar(5);
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    public function testPassingParam()
    {
        $stmts = self::$parser->parse('<?php
        class A {}

        class B {
            /** @return void */
            public function barBar(A $a) {}
        }

        $b = new B();
        $b->barBar(new A);
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    public function testNullToNullableParam()
    {
        $stmts = self::$parser->parse('<?php
        class A {}

        class B {
            /** @return void */
            public function barBar(A $a = null) {}
        }

        $b = new B();
        $b->barBar(null);
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
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
            public function barBar(A $a = null) {}
        }

        $b = new B();
        $b->barBar(5);
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    public function testObjectToNullableObjectParam()
    {
        $stmts = self::$parser->parse('<?php
        class A {}

        class B {
            /** @return void */
            public function barBar(A $a = null) {}
        }

        $b = new B();
        $b->barBar(new A);
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
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
            function fooFoo(A $a) {
                if ($a instanceof B) {
                    $a->barBar();
                }
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    public function testParamCoercion()
    {
        $stmts = self::$parser->parse('<?php
        class A {}
        class B extends A {
            /** @return void */
            public function barBar() {}
        }

        class C {
            /** @return void */
            function fooFoo(A $a) {
                if ($a instanceof B) {
                    $a->barBar();
                }
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    public function testParamElseifCoercion()
    {
        $stmts = self::$parser->parse('<?php
        class A {}
        class B extends A {
            /** @return void */
            public function barBar() {}
        }
        class C extends A {
            /** @return void */
            public function baz() {}
        }

        class D {
            /** @return void */
            function fooFoo(A $a) {
                if ($a instanceof B) {
                    $a->barBar();
                }
                elseif ($a instanceof C) {
                    $a->baz();
                }
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
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

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);

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

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);

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
            public function barBar() {

            }
        }

        foreach (A::loadMultiple() as $a) {
            if ($a === null) {
                // do nothing
            }

            $a->barBar();
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods();
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
            public function barBar() {

            }
        }

        foreach (A::loadMultiple() as $a) {
            if ($a === null) {
                continue;
            }

            $a->barBar();
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods();
    }

    public function testTypeRefinementWithIsNumeric()
    {
        $stmts = self::$parser->parse('<?php
        function fooFoo(string $a) : void {
            if (is_numeric($a)) {

            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods();
    }

    public function testPlusPlus()
    {
        $stmts = self::$parser->parse('<?php
        $a = 0;
        $b = $a++;
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);

        $this->assertSame('int', (string) $context->vars_in_scope['$a']);
    }

    public function testTypedValueAssertion()
    {
        $context = new Context('somefile.php');
        $stmts = self::$parser->parse('<?php
        /**
         * @param array|string $a
         */
        function fooFoo($a) : void {
            $b = "aadad";

            if ($a === $b) {
                echo substr($a, 1);
            }
        }');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods($context);
    }
}
