<?php

namespace CodeInspector\Tests;

use PhpParser;
use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;

class ReturnTypeTest extends PHPUnit_Framework_TestCase
{
    protected static $_parser;

    public static function setUpBeforeClass()
    {
        self::$_parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
    }

    public function setUp()
    {
        \CodeInspector\ClassMethodChecker::clearCache();
    }

    public function testGenericArrayCreation()
    {
        $stmts = self::$_parser->parse('<?php
        class B {
            /**
             * @return array<int>
             */
            public function bar(array $in) {
                $out = [];

                foreach ($in as $key => $value) {

                }

                return $out;
            }
        }');

        $file_checker = new \CodeInspector\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testGenericArrayCreationWithElements()
    {
        $stmts = self::$_parser->parse('<?php
        class B {
            /**
             * @return array<array<int>>
             */
            public function bar(array $in) {
                $out = [];

                foreach ($in as $key => $value) {
                    $out[] = 4;
                }

                return $out;
            }
        }');

        $file_checker = new \CodeInspector\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function test2DGenericArrayCreationWithElements()
    {
        $stmts = self::$_parser->parse('<?php
        class B {
            /**
             * @return array<array<int>>
             */
            public function bar(array $in) {
                $out = [];

                foreach ($in as $key => $value) {
                    $out[] = [4];
                }

                return $out;
            }
        }');

        $file_checker = new \CodeInspector\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function test2DGenericArrayCreationWithElementsAddedInIf()
    {
        $stmts = self::$_parser->parse('<?php
        class B {
            /**
             * @return array<array<int>>
             */
            public function bar(array $in) {
                $out = [];

                $bits = [];

                foreach ($in as $key => $value) {
                    if (rand(0,100) > 50) {
                        $out[] = $bits;
                        $bits = [];
                    }

                    $bits[] = 4;
                }

                if ($bits) {
                    $out[] = $bits;
                }

                return $out;
            }
        }');

        $file_checker = new \CodeInspector\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testReturnTypeAfterUselessNullcheck()
    {
        $stmts = self::$_parser->parse('<?php
        class One {
            public function foo() {}
        }

        class B {
            public $baz;

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

        $file_checker = new \CodeInspector\FileChecker('somefile.php', $stmts);
        $file_checker->check();

        $method_stmts = $stmts[1]->stmts[1]->stmts;

        $return_stmt = array_pop($method_stmts);

        $this->assertSame('One|null', (string) $return_stmt->inferredType);
    }
}
