<?php

namespace Psalm\Tests;

use Psalm\Type;

use PhpParser;
use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;

class PropertyTypeTest extends PHPUnit_Framework_TestCase
{
    protected static $_parser;
    protected static $_file_filter;

    public static function setUpBeforeClass()
    {
        self::$_parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);

        $config = \Psalm\Config::getInstance();
        $config->throw_exception = true;
    }

    public function setUp()
    {
        \Psalm\Checker\FileChecker::clearCache();
    }

    public function testNewVarInIf()
    {
        $stmts = self::$_parser->parse('<?php
        class A {
            /**
             * @var mixed
             */
            public $foo;

            public function bar()
            {
                if (rand(0,10) === 5) {
                    $this->foo = [];
                }

                if (!is_array($this->foo)) {
                    // do something
                }
            }
        }
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }
}
