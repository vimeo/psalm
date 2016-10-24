<?php

namespace Psalm\Tests;

use PhpParser;
use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Context;
use Psalm\Checker\TypeChecker;
use Psalm\Type;

class InterfaceTest extends PHPUnit_Framework_TestCase
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
        \Psalm\Checker\FileChecker::clearCache();
    }

    public function testExtends()
    {
        $stmts = self::$_parser->parse('<?php
        interface a
        {
            /**
             * @return string
             */
            public function foo();
        }

        interface b
        {
            public function bar() {

            }
        }

        interface c extends a, b
        {
            /**
             * @return string
             */
            public function baz() {

            }
        }

        class d implements c
        {
            public function foo()
            {
            }

            public function bar()
            {
            }

            public function baz()
            {
            }
        }

        $cee = (new d())->baz();
        $dee = (new d())->foo();
        ?>
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('string', (string) $context->vars_in_scope['$cee']);
        $this->assertEquals('string', (string) $context->vars_in_scope['$dee']);
    }
}
