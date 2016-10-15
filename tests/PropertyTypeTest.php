<?php

namespace Psalm\Tests;

use Psalm\Context;
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

    public function testSharedPropertyInIf()
    {
        $stmts = self::$_parser->parse('<?php
        class A {
            /** @var int */
            public $foo;
        }
        class B {
            /** @var string */
            public $foo;
        }

        $a = null;
        $b = null;

        if ($a instanceof A || $a instanceof B) {
            $b = $a->foo;
        }
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('null|string|int', (string) $context->vars_in_scope['$b']);
    }

    public function testSharedPropertyInElseIf()
    {
        $stmts = self::$_parser->parse('<?php
        class A {
            /** @var int */
            public $foo;
        }
        class B {
            /** @var string */
            public $foo;
        }

        $a = null;
        $b = null;

        if (rand(0, 10) === 4) {
            // do nothing
        }
        elseif ($a instanceof A || $a instanceof B) {
            $b = $a->foo;
        }
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('null|string|int', (string) $context->vars_in_scope['$b']);
    }
}
