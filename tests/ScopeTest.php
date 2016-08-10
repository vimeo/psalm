<?php

namespace Psalm\Tests;

use Psalm\Type;

use PhpParser;
use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;

class ScopeTest extends PHPUnit_Framework_TestCase
{
    protected static $_parser;
    protected static $_file_filter;

    public static function setUpBeforeClass()
    {
        self::$_parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);

        $config = \Psalm\Config::getInstance();
        $config->throw_exception = true;

        $filter = new \Psalm\Config\FileFilter();
        $filter->addExcludeFile('somefile.php');
        $filter->makeExclusive();

        self::$_file_filter = $filter;
    }

    public function setUp()
    {
        \Psalm\ClassMethodChecker::clearCache();
        \Psalm\FileChecker::clearCache();
        \Psalm\Config::getInstance()->setIssueHandler('PossiblyUndefinedVariable', null);
    }

    public function testNewVarInIf()
    {
        $stmts = self::$_parser->parse('<?php
        if (rand(0,100) === 10) {
            $badge = "hello";
        }
        else {
            $badge = "goodbye";
        }

        echo $badge;
        ');

        $file_checker = new \Psalm\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testNewVarInIfWithElseReturn()
    {
        $stmts = self::$_parser->parse('<?php
        if (rand(0,100) === 10) {
            $badge = "hello";
        }
        else {
            throw new \Exception();
        }

        echo $badge;
        ');

        $file_checker = new \Psalm\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException Psalm\Exception\CodeException
     * @expectedExceptionMessage PossiblyUndefinedVariable - somefile.php:3 - Possibly undefined variable $array, first seen on line 3
     */
    public function testPossiblyUndefinedArrayInIf()
    {
        $stmts = self::$_parser->parse('<?php
        if (rand(0,100) === 10) {
            $array[] = "hello";
        }

        echo $array;
        ');

        $file_checker = new \Psalm\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException Psalm\Exception\CodeException
     * @expectedExceptionMessage PossiblyUndefinedVariable - somefile.php:3 - Possibly undefined variable $array, first seen on line 3
     */
    public function testPossiblyUndefinedArrayInForeach()
    {
        $stmts = self::$_parser->parse('<?php
        foreach ([1, 2, 3, 4] as $b) {
            $array[] = "hello";
        }

        echo $array;
        ');

        $file_checker = new \Psalm\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException Psalm\Exception\CodeException
     * @expectedExceptionMessage PossiblyUndefinedVariable - somefile.php:4 - Possibly undefined variable $array, first seen on line 4
     */
    public function testPossiblyUndefinedArrayInWhileAndForeach()
    {
        $stmts = self::$_parser->parse('<?php
        for ($i = 0; $i < 4; $i++) {
            while (rand(0,10) === 5) {
                $array[] = "hello";
            }
        }

        echo $array;
        ');

        \Psalm\Config::getInstance()->setIssueHandler('PossiblyUndefinedVariable', self::$_file_filter);

        $file_checker = new \Psalm\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException Psalm\Exception\CodeException
     * @expectedExceptionMessage PossiblyUndefinedVariable - somefile.php:6 - Possibly undefined variable $car, first seen on line 3
     */
    public function testPossiblyUndefinedVariableInForeach()
    {
        $stmts = self::$_parser->parse('<?php
        foreach ([1, 2, 3, 4] as $b) {
            $car = "Volvo";
        }

        echo $car;
        ');

        $file_checker = new \Psalm\FileChecker('somefile.php', $stmts);
        $file_checker->check();
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

        $file_checker = new \Psalm\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testTryCatchVar()
    {
        $stmts = self::$_parser->parse('<?php
        try {
            $worked = true;
        }
        catch (\Exception $e) {
            $worked = false;
        }

        if ($worked) {

        }
        ');

        $file_checker = new \Psalm\FileChecker('somefile.php', $stmts);
        $file_checker->check();

        $conditional = $stmts[1]->cond;

        $this->assertSame('bool', (string) $conditional->inferredType);
    }

    public function testWhileVar()
    {
        $stmts = self::$_parser->parse('<?php

        $worked = false;

        while (rand(0,100) === 10) {
            $worked = true;
        }

        if ($worked) {

        }
        ');

        $file_checker = new \Psalm\FileChecker('somefile.php', $stmts);
        $file_checker->check();

        $conditional = $stmts[2]->cond;

        $this->assertSame('bool', (string) $conditional->inferredType);
    }

    public function testDoWhileVar()
    {
        $stmts = self::$_parser->parse('<?php
        $worked = false;

        do {
            $worked = true;
        }
        while (rand(0,100) === 10);

        if ($worked) {

        }
        ');

        $file_checker = new \Psalm\FileChecker('somefile.php', $stmts);
        $file_checker->check();

        $conditional = $stmts[2]->cond;

        $this->assertSame('bool', (string) $conditional->inferredType);
    }

    public function testAssignmentInIf()
    {
        $stmts = self::$_parser->parse('<?php
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

        $file_checker = new \Psalm\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testPassByRefInIf()
    {
        $stmts = self::$_parser->parse('<?php
        if (preg_match("/bad/", "badger", $matches)) {
            echo $matches[0];
        }
        ');

        $file_checker = new \Psalm\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testPassByRefInIfWithBoolean()
    {
        $stmts = self::$_parser->parse('<?php
        $a = true;
        if ($a && preg_match("/bad/", "badger", $matches)) {
            echo $matches[0];
        }
        ');

        $file_checker = new \Psalm\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    /**
     * @expectedException Psalm\Exception\CodeException
     * @expectedExceptionMessage PossiblyUndefinedVariable - somefile.php:9 - Possibly undefined variable $a, first seen on line 4
     */
    public function testPossiblyUndefinedVariableInForeachAndIf()
    {
        $stmts = self::$_parser->parse('<?php
        foreach ([1,2,3,4] as $i) {
            if ($i === 1) {
                $a = true;
                break;
            }
        }

        echo $a;
        ');

        \Psalm\Config::getInstance()->setIssueHandler('PossiblyUndefinedVariable', self::$_file_filter);

        $file_checker = new \Psalm\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }
}
