<?php

namespace Psalm\Tests;

use PhpParser;
use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Context;
use Psalm\Checker\TypeChecker;
use Psalm\Type;

class Php70Test extends PHPUnit_Framework_TestCase
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

    public function testFunctionTypeHints()
    {
        $stmts = self::$_parser->parse('<?php
        function indexof(string $haystack, string $needle) : int
        {
            $pos = strpos($haystack, $needle);

            if ($pos === false) {
                return -1;
            }

            return $pos;
        }

        $a = indexof("arr", "a");
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('int', (string) $context->vars_in_scope['$a']);
    }

    public function testMethodTypeHints()
    {
        $stmts = self::$_parser->parse('<?php
        class Foo {
            public static function indexof(string $haystack, string $needle) : int
            {
                $pos = strpos($haystack, $needle);

                if ($pos === false) {
                    return -1;
                }

                return $pos;
            }
        }

        $a = Foo::indexof("arr", "a");
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('int', (string) $context->vars_in_scope['$a']);
    }
}
