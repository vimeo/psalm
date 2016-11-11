<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Config;

class IssueSuppressionTest extends PHPUnit_Framework_TestCase
{
    protected static $parser;
    protected static $file_filter;

    public static function setUpBeforeClass()
    {
        self::$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);

        $config = Config::getInstance();
        $config->throw_exception = true;

        $filter = new Config\FileFilter();
        $filter->addExcludeFile('somefile.php');
        $filter->makeExclusive();

        self::$file_filter = $filter;
    }

    public function setUp()
    {
        FileChecker::clearCache();
        Config::getInstance()->setIssueHandler('PossiblyUndefinedVariable', null);
    }

    public function testUndefinedClass()
    {
        $stmts = self::$parser->parse('<?php

        class A{
            /**
             * @psalm-suppress UndefinedClass
             * @psalm-suppress MixedMethodCall
             * @psalm-suppress MissingReturnType
             */
            public function a() {
                B::foo()->bar()->bat()->baz()->bam()->bas()->bee()->bet()->bes()->bis();
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }
}
