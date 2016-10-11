<?php

namespace Psalm\Tests;

use Psalm\Type;

use PhpParser;
use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;

class IssueSuppressionTest extends PHPUnit_Framework_TestCase
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
        \Psalm\Checker\FileChecker::clearCache();
        \Psalm\Config::getInstance()->setIssueHandler('PossiblyUndefinedVariable', null);
    }

    public function testUndefinedClass()
    {
        $stmts = self::$_parser->parse('<?php

        class A{
            /**
             * @psalm-suppress UndefinedClass
             */
            public function a() {
                B::foo()->bar()->bat()->baz()->bam()->bas()->bee()->bet()->bes()->bis();
            }
        }
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }
}
