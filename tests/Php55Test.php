<?php

namespace Psalm\Tests;

use PhpParser;
use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Context;
use Psalm\Checker\TypeChecker;
use Psalm\Type;

class Php55Test extends PHPUnit_Framework_TestCase
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

    public function testGenerator()
    {
        $stmts = self::$_parser->parse('<?php
        function xrange($start, $limit, $step = 1) {
            for ($i = $start; $i <= $limit; $i += $step) {
                yield $i;
            }
        }

        /*
         * Note that an array is never created or returned,
         * which saves memory.
         */
        foreach (xrange(1, 9, 2) as $number) {
            echo "$number ";
        }

        echo "\n";
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    public function testFinally()
    {
        $stmts = self::$_parser->parse('<?php
        try {
        }
        catch (\Exception $e) {
        }
        finally {
        }
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    public function testForeachList()
    {
        $stmts = self::$_parser->parse('<?php
        $array = [
            [1, 2],
            [3, 4],
        ];

        foreach ($array as list($a, $b)) {
            echo "A: $a; B: $b\n";
        }
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    public function testArrayStringDereferencing()
    {
        $stmts = self::$_parser->parse('<?php
        $a = [1, 2, 3][0];
        $b = "PHP"[0];
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);$file_checker->check(true, true, $context);
        $this->assertEquals('int', (string) $context->vars_in_scope['$a']);
        $this->assertEquals('string', (string) $context->vars_in_scope['$b']);
    }

    public function testClassString()
    {
        $stmts = self::$_parser->parse('<?php
        class ClassName {}

        $a = ClassName::class;
        ?>
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);

        $this->assertEquals('string', (string) $context->vars_in_scope['$a']);
    }
}
