<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

class TypeAlgebraTest extends PHPUnit_Framework_TestCase
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
     * @return void
     */
    public function testTwoVarLogic()
    {
        $this->markTestSkipped('PHP 7.1 annotations');
        $stmts = self::$parser->parse('<?php
        function takesString(string $s) : void {}

        function foo(?string $a, ?string $b) : void {
            if ($a !== null || $b !== null) {
                if ($a !== null) {
                    $c = $a;
                } else {
                    $c = $b;
                }

                takesString($c);
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testThreeVarLogic()
    {
        $this->markTestSkipped('PHP 7.1 annotations');
        $stmts = self::$parser->parse('<?php
        function takesString(string $s) : void {}

        function foo(?string $a, ?string $b, ?string $c) : void {
            if ($a !== null || $b !== null || $c !== null) {
                if ($a !== null) {
                    $d = $a;
                } elseif ($b !== null) {
                    $d = $b;
                } else {
                    $d = $c;
                }

                takesString($d);
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage NullArgument
     * @return                   void
     */
    public function testThreeVarLogicWithChange()
    {
        $this->markTestSkipped('PHP 7.1 annotations');
        $stmts = self::$parser->parse('<?php
        function takesString(string $s) : void {}

        function foo(?string $a, ?string $b, ?string $c) : void {
            if ($a !== null || $b !== null || $c !== null) {
                $c = null;

                if ($a !== null) {
                    $d = $a;
                } elseif ($b !== null) {
                    $d = $b;
                } else {
                    $d = $c;
                }

                takesString($d);
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage NullArgument
     * @return                   void
     */
    public function testThreeVarLogicWithException()
    {
        $this->markTestSkipped('PHP 7.1 annotations');
        $stmts = self::$parser->parse('<?php
        function takesString(string $s) : void {}

        function foo(?string $a, ?string $b, ?string $c) : void {
            if ($a !== null || $b !== null || $c !== null) {
                if ($c !== null) {
                    throw new \Exception("bad");
                }

                if ($a !== null) {
                    $d = $a;
                } elseif ($b !== null) {
                    $d = $b;
                } else {
                    $d = $c;
                }

                takesString($d);
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testTwoVarLogicNotNested()
    {
        $this->markTestSkipped('PHP 7.1 annotations');
        $stmts = self::$parser->parse('<?php
        function foo(?string $a, ?string $b) : string {
            if (!$a && !$b) return "bad";
            if (!$a) return $b;
            return $a;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testTwoVarLogicNotNestedWithAllPathsReturning()
    {
        $this->markTestSkipped('PHP 7.1 annotations');
        $stmts = self::$parser->parse('<?php
        function foo(?string $a, ?string $b) : string {
            if (!$a && !$b) {
                return "bad";
            } else {
                if (!$a) {
                    return $b;
                } else {
                    return $a;
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
    public function testTwoVarLogicNotNestedWithAssignmentBeforeReturn()
    {
        $this->markTestSkipped('PHP 7.1 annotations');
        $stmts = self::$parser->parse('<?php
        function foo(?string $a, ?string $b) : string {
            if (!$a && !$b) {
                $a = 5;
                return "bad";
            }

            if (!$a) {
                $a = 7;
                return $b;
            }

            return $a;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testInvertedTwoVarLogicNotNested()
    {
        $this->markTestSkipped('PHP 7.1 annotations');
        $stmts = self::$parser->parse('<?php
        function foo(?string $a, ?string $b) : string {
            if ($a || $b) {
                // do nothing
            } else {
                return "bad";
            }

            if (!$a) return $b;
            return $a;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testInvertedTwoVarLogicNotNestedWithAssignmentBeforeReturn()
    {
        $this->markTestSkipped('PHP 7.1 annotations');
        $stmts = self::$parser->parse('<?php
        function foo(?string $a, ?string $b) : string {
            if ($a || $b) {
                // do nothing
            } else {
                $a = 5;
                return "bad";
            }

            if (!$a) return $b;
            return $a;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidReturnType
     * @return                   void
     */
    public function testInvertedTwoVarLogicNotNestedWithVarChange()
    {
        $this->markTestSkipped('PHP 7.1 annotations');
        $stmts = self::$parser->parse('<?php
        function foo(?string $a, ?string $b) : string {
            if ($a || $b) {
                $b = null;
            } else {
                return "bad";
            }

            if (!$a) return $b;
            return $a;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidReturnType
     * @return                   void
     */
    public function testInvertedTwoVarLogicNotNestedWithElseif()
    {
        $this->markTestSkipped('PHP 7.1 annotations');
        $stmts = self::$parser->parse('<?php
        function foo(?string $a, ?string $b) : string {
            if (rand(0, 1)) {
                // do nothing
            } elseif ($a || $b) {
                // do nothing here
            } else {
                return "bad";
            }

            if (!$a) return $b;
            return $a;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testTwoVarLogicNotNestedWithElseif()
    {
        $this->markTestSkipped('PHP 7.1 annotations');
        $stmts = self::$parser->parse('<?php
        function foo(?string $a, ?string $b) : string {
            if ($a) {
                // do nothing
            } elseif ($b) {
                // do nothing here
            } else {
                return "bad";
            }

            if (!$a) return $b;
            return $a;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testThreeVarLogicNotNested()
    {
        $this->markTestSkipped('PHP 7.1 annotations');
        $stmts = self::$parser->parse('<?php
        function foo(?string $a, ?string $b, ?string $c) : string {
            if ($a) {
                // do nothing
            } elseif ($b) {
                // do nothing here
            } elseif ($c) {
                // do nothing here
            } else {
                return "bad";
            }

            if (!$a && !$b) return $c;
            if (!$a) return $b;
            return $a;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testThreeVarLogicNotNestedAndOr()
    {
        $this->markTestSkipped('PHP 7.1 annotations');
        $stmts = self::$parser->parse('<?php
        function foo(?string $a, ?string $b, ?string $c) : string {
            if ($a) {
                // do nothing
            } elseif ($b || $c) {
                // do nothing here
            } else {
                return "bad";
            }

            if (!$a && !$b) return $c;
            if (!$a) return $b;
            return $a;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidReturnType
     * @return                   void
     */
    public function testThreeVarLogicWithElseifAndAnd()
    {
        $this->markTestSkipped('PHP 7.1 annotations');
        $stmts = self::$parser->parse('<?php
        function foo(?string $a, ?string $b, ?string $c) : string {
            if ($a) {
                // do nothing
            } elseif ($b && $c) {
                // do nothing here
            } else {
                return "bad";
            }

            if (!$a && !$b) return $c;
            if (!$a) return $b;
            return $a;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidReturnType
     * @return                   void
     */
    public function testTwoVarLogicNotNestedWithElseifNegatedInIf()
    {
        $this->markTestSkipped('PHP 7.1 annotations');
        $stmts = self::$parser->parse('<?php
        function foo(?string $a, ?string $b) : string {
            if ($a) {
                $a = null;
            } elseif ($b) {
                // do nothing here
            } else {
                return "bad";
            }

            if (!$a) return $b;
            return $a;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testTwoVarLogicNotNestedWithElseifCorrectlyNegatedInElseIf()
    {
        $this->markTestSkipped('PHP 7.1 annotations');
        $stmts = self::$parser->parse('<?php
        function foo(?string $a, ?string $b) : string {
            if ($a) {
                // do nothing here
            } elseif ($b) {
                $a = null;
            } else {
                return "bad";
            }

            if (!$a) return $b;
            return $a;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidReturnType
     * @return                   void
     */
    public function testTwoVarLogicNotNestedWithElseifIncorrectlyReinforcedInIf()
    {
        $stmts = self::$parser->parse('<?php
        function foo(?string $a, ?string $b) : string {
            if ($a) {
                $a = "";
            } elseif ($b) {
                // do nothing
            } else {
                return "bad";
            }

            if (!$a) return $b;
            return $a;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testNestedReassignment()
    {
        $stmts = self::$parser->parse('<?php
        function foo(?string $a) : void {
            if ($a === null) {
                $a = "blah-blah";
            } else {
                $a = rand(0, 1) ? "blah" : null;

                if ($a === null) {

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
    public function testTwoVarLogicNotNestedWithElseifCorrectlyReinforcedInIf()
    {
        $stmts = self::$parser->parse('<?php
        class A {}
        class B extends A {}

        function foo(?A $a, ?A $b) : A {
            if ($a) {
                $a = new B;
            } elseif ($b) {
                // do nothing
            } else {
                return new A;
            }

            if (!$a) return $b;
            return $a;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage ParadoxicalCondition
     * @return                   void
     */
    public function testRepeatedIfStatements()
    {
        $stmts = self::$parser->parse('<?php
        /** @return string|null */
        function foo(?string $a) {
            if ($a) {
                return $a;
            }

            if ($a) {

            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage ParadoxicalCondition
     * @return                   void
     */
    public function testRepeatedConditionals()
    {
        $stmts = self::$parser->parse('<?php
        function foo(?string $a) : void {
            if ($a) {
                // do something
            } elseif ($a) {
                // can never get here
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * This shouldn't throw an error
     *
     * @return void
     */
    public function testDifferentValueChecks()
    {
        $stmts = self::$parser->parse('<?php
        function foo(string $a) : void {
            if ($a === "foo") {
                // do something
            } elseif ($a === "bar") {
                // can never get here
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * This shouldn't throw an error
     *
     * @return void
     */
    public function testRepeatedSet()
    {
        $stmts = self::$parser->parse('<?php
        function foo() : void {
            if ($a = rand(0, 1) ? "" : null) {
                return;
            }

            if (rand(0, 1)) {
                $a = rand(0, 1) ? "hello" : null;

                if ($a) {

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
    public function testRepeatedSetInsideWhile()
    {
        $stmts = self::$parser->parse('<?php
        function foo() : void {
            if ($a = rand(0, 1) ? "" : null) {
                return;
            } else {
                while (rand(0, 1)) {
                    $a = rand(0, 1) ? "hello" : null;
                }

                if ($a) {

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
    public function testByRefAssignment()
    {
        $stmts = self::$parser->parse('<?php
        function foo() : void {
            preg_match("/hello/", "hello molly", $matches);

            if (!$matches) {
                return;
            }

            preg_match("/hello/", "hello dolly", $matches);

            if (!$matches) {

            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }
}
