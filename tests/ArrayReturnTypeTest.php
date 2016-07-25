<?php

namespace Psalm\Tests;

use PhpParser;
use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;

class ArrayReturnTypeTest extends PHPUnit_Framework_TestCase
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
                    $out[] = 4;
                }

                return $out;
            }
        }');

        $file_checker = new \Psalm\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testGeneric2DArrayCreation()
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

        $file_checker = new \Psalm\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testGeneric2DArrayCreationAddedInIf()
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

        $file_checker = new \Psalm\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testGenericArrayCreationWithObjectAddedInIf()
    {
        $stmts = self::$_parser->parse('<?php
        class B {
            /**
             * @return array<B>
             */
            public function bar(array $in) {
                $out = [];

                if (rand(0,10) === 10) {
                    $out[] = new B();
                }

                return $out;
            }
        }');

        $file_checker = new \Psalm\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testGenericArrayCreationWithElementAddedInSwitch()
    {
        $stmts = self::$_parser->parse('<?php
        class B {
            /**
             * @return array<int>
             */
            public function bar(array $in) {
                $out = [];

                switch (rand(0,10)) {
                    case 5:
                        $out[] = 4;
                        break;

                    case 6:
                        // do nothing
                }

                return $out;
            }
        }');

        $file_checker = new \Psalm\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testGenericArrayCreationWithElementsAddedInSwitch()
    {
        $stmts = self::$_parser->parse('<?php
        class B {
            /**
             * @return array<int|string>
             */
            public function bar(array $in) {
                $out = [];

                switch (rand(0,10)) {
                    case 5:
                        $out[] = 4;
                        break;

                    case 6:
                        $out[] = "hello";
                        break;
                }

                return $out;
            }
        }');

        $file_checker = new \Psalm\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testGenericArrayCreationWithElementsAddedInSwitchWithNothing()
    {
        $stmts = self::$_parser->parse('<?php
        class B {
            /**
             * @return array<int|string>
             */
            public function bar(array $in) {
                $out = [];

                switch (rand(0,10)) {
                    case 5:
                        $out[] = 4;
                        break;

                    case 6:
                        $out[] = "hello";
                        break;

                    case 7:
                        // do nothing
                }

                return $out;
            }
        }');

        $file_checker = new \Psalm\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }
}
