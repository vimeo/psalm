<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Config;

class ConfigTest extends PHPUnit_Framework_TestCase
{
    protected static $file_filter;

    public function setUp()
    {
        FileChecker::clearCache();
    }

    public function testBarebonesConfig()
    {
        $config = Config::loadFromXML('psalm.xml', '<?xml version="1.0"?>
<psalm>
    <projectFiles>
        <directory name="src" />
    </projectFiles>
</psalm>');

        $this->assertTrue($config->isInProjectDirs('src/main.php'));
        $this->assertFalse($config->isInProjectDirs('main.php'));
    }
}
