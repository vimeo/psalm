<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Config;

class ScopeTest extends PHPUnit_Framework_TestCase
{
    protected static $parser;
    protected static $file_filter;

    public static function setUpBeforeClass()
    {
        self::$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);

        $config = Config::getInstance();
        $config->throw_exception = true;

        self::$file_filter = null;
    }

    public function setUp()
    {
        FileChecker::clearCache();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage PossiblyUndefinedVariable - somefile.php:6 - Possibly undefined variable $b, first seen
     *  on line 3
     */
    public function testPossiblyUndefinedVarInIf()
    {
        $stmts = self::$parser->parse('<?php
        if (rand(0,100) === 10) {
            $b = "s";
        }

        echo $b;
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testNewVarInIf()
    {
        $stmts = self::$parser->parse('<?php
        if (rand(0,100) === 10) {
            $badge = "hello";
        }
        else {
            $badge = "goodbye";
        }

        echo $badge;
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testNewVarInIfWithElseReturn()
    {
        $stmts = self::$parser->parse('<?php
        if (rand(0,100) === 10) {
            $badge = "hello";
        }
        else {
            throw new \Exception();
        }

        echo $badge;
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage PossiblyUndefinedVariable - somefile.php:3 - Possibly undefined variable $array, first
     *  seen on line 3
     */
    public function testPossiblyUndefinedArrayInIf()
    {
        $stmts = self::$parser->parse('<?php
        if (rand(0,100) === 10) {
            $array[] = "hello";
        }

        echo $array;
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage PossiblyUndefinedVariable - somefile.php:3 - Possibly undefined variable $array, first
     *  seen on line 3
     */
    public function testPossiblyUndefinedArrayInForeach()
    {
        $stmts = self::$parser->parse('<?php
        foreach ([1, 2, 3, 4] as $b) {
            $array[] = "hello";
        }

        echo $array;
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage PossiblyUndefinedVariable - somefile.php:4 - Possibly undefined variable $array, first
     *  seen on line 4
     */
    public function testPossiblyUndefinedArrayInWhileAndForeach()
    {
        $stmts = self::$parser->parse('<?php
        for ($i = 0; $i < 4; $i++) {
            while (rand(0,10) === 5) {
                $array[] = "hello";
            }
        }

        echo $array;
        ');

        Config::getInstance()->setIssueHandler('PossiblyUndefinedVariable', self::$file_filter);

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage PossiblyUndefinedVariable - somefile.php:6 - Possibly undefined variable $car, first
     *  seen on line 3
     */
    public function testPossiblyUndefinedVariableInForeach()
    {
        $stmts = self::$parser->parse('<?php
        foreach ([1, 2, 3, 4] as $b) {
            $car = "Volvo";
        }

        echo $car;
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testSwitchVariableWithContinue()
    {
        $stmts = self::$parser->parse('<?php
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
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testSwitchVariableWithContinueAndIfs()
    {
        $stmts = self::$parser->parse('<?php
        foreach ([\'a\', \'b\', \'c\'] as $letter) {
            switch ($letter) {
                case \'a\':
                    if (rand(0, 10) === 1) {
                        continue;
                    }
                    $foo = 1;
                    break;
                case \'b\':
                    if (rand(0, 10) === 1) {
                        continue;
                    }
                    $foo = 2;
                    break;
                default:
                    continue;
            }

            $moo = $foo;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testSwitchVariableWithFallthrough()
    {
        $stmts = self::$parser->parse('<?php
        foreach ([\'a\', \'b\', \'c\'] as $letter) {
            switch ($letter) {
                case \'a\':
                case \'b\':
                    $foo = 2;
                    break;

                default:
                    $foo = 3;
                    break;
            }

            $moo = $foo;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testSwitchVariableWithFallthroughStatement()
    {
        $stmts = self::$parser->parse('<?php
        foreach ([\'a\', \'b\', \'c\'] as $letter) {
            switch ($letter) {
                case \'a\':
                    $bar = 1;

                case \'b\':
                    $foo = 2;
                    break;

                default:
                    $foo = 3;
                    break;
            }

            $moo = $foo;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testTryCatchVar()
    {
        $stmts = self::$parser->parse('<?php
        try {
            $worked = true;
        }
        catch (\Exception $e) {
            $worked = false;
        }

        if ($worked) {

        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();

        $conditional = $stmts[1]->cond;

        $this->assertSame('bool', (string) $conditional->inferredType);
    }

    public function testWhileVar()
    {
        $stmts = self::$parser->parse('<?php

        $worked = false;

        while (rand(0,100) === 10) {
            $worked = true;
        }

        if ($worked) {

        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();

        $conditional = $stmts[2]->cond;

        $this->assertSame('bool', (string) $conditional->inferredType);
    }

    public function testDoWhileVar()
    {
        $stmts = self::$parser->parse('<?php
        $worked = false;

        do {
            $worked = true;
        }
        while (rand(0,100) === 10);

        if ($worked) {

        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();

        $conditional = $stmts[2]->cond;

        $this->assertSame('bool', (string) $conditional->inferredType);
    }

    public function testAssignmentInIf()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            public static function bar() {
                return true;
            }

            public function baz() {
                if (!($a = A::bar())) {
                    return;
                }

                echo $a;
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testPassByRefInIf()
    {
        $stmts = self::$parser->parse('<?php
        if (preg_match("/bad/", "badger", $matches)) {
            echo $matches[0];
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testPassByRefInIfCheckAfter()
    {
        $stmts = self::$parser->parse('<?php
        if (!preg_match("/bad/", "badger", $matches)) {
            exit();
        }
        echo $matches[0];
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testPassByRefInIfWithBoolean()
    {
        $stmts = self::$parser->parse('<?php
        $a = true;
        if ($a && preg_match("/bad/", "badger", $matches)) {
            echo $matches[0];
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage PossiblyUndefinedVariable - somefile.php:9 - Possibly undefined variable $a, first
     *  seen on line 4
     */
    public function testPossiblyUndefinedVariableInForeachAndIf()
    {
        $stmts = self::$parser->parse('<?php
        foreach ([1,2,3,4] as $i) {
            if ($i === 1) {
                $a = true;
                break;
            }
        }

        echo $a;
        ');

        Config::getInstance()->setIssueHandler('PossiblyUndefinedVariable', self::$file_filter);

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testFunctionExists()
    {
        $stmts = self::$parser->parse('<?php
        if (true && function_exists("flabble")) {
            flabble();
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testNestedPropertyFetchInElseif()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @var A|null */
            public $foo;
        }

        $a = rand(0, 10) === 5 ? new A() : null;

        if (false) {

        }
        elseif ($a && $a->foo) {
            echo $a;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testGlobalReturn()
    {
        $stmts = self::$parser->parse('<?php
        $foo = "foo";

        function a() : string {
            global $foo;

            return $foo;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testStatic()
    {
        $stmts = self::$parser->parse('<?php
        function a() : string {
            static $foo = "foo";

            return $foo;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }
}
