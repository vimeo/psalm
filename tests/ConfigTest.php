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

    public static function getAllIssues()
    {
        return array_filter(
            array_map(
                function ($file_name) {
                    return substr($file_name, 0, -4);
                },
                scandir(dirname(__DIR__) . '/src/Psalm/Issue')
            ),
            function ($issue_name) {
                return !empty($issue_name) && $issue_name !== 'CodeError' && $issue_name !== 'CodeIssue';
            }
        );
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

    public function testIgnoreProjectDirectory()
    {
        $config = Config::loadFromXML('psalm.xml', '<?xml version="1.0"?>
<psalm>
    <projectFiles>
        <directory name="src" />
        <ignoreFiles>
            <directory name="src/ignoreme" />
        </ignoreFiles>
    </projectFiles>
</psalm>');

        $this->assertTrue($config->isInProjectDirs('src/main.php'));
        $this->assertFalse($config->isInProjectDirs('src/ignoreme/main.php'));
        $this->assertFalse($config->isInProjectDirs('main.php'));
    }

    public function testIssueHandler()
    {
        $config = Config::loadFromXML('psalm.xml', '<?xml version="1.0"?>
<psalm>
    <projectFiles>
        <directory name="src" />
        <directory name="tests" />
    </projectFiles>

    <issueHandlers>
        <MissingReturnType errorLevel="suppress" />
    </issueHandlers>
</psalm>');

        $this->assertTrue($config->excludeIssueInFile('MissingReturnType', 'tests/somefile.php'));
        $this->assertTrue($config->excludeIssueInFile('MissingReturnType', 'src/somefile.php'));
    }

    public function testIssueHandlerWithCustomErrorLevels()
    {
        $config = Config::loadFromXML('psalm.xml', '<?xml version="1.0"?>
<psalm>
    <projectFiles>
        <directory name="src" />
        <directory name="tests" />
    </projectFiles>

    <issueHandlers>
        <MissingReturnType errorLevel="info">
            <errorLevel type="suppress">
                <directory name="tests" />
            </errorLevel>
            <errorLevel type="error">
                <directory name="src/Core" />
            </errorLevel>
        </MissingReturnType>
    </issueHandlers>
</psalm>');

        $this->assertTrue($config->excludeIssueInFile('MissingReturnType', 'tests/somefile.php'));
        $this->assertFalse($config->excludeIssueInFile('MissingReturnType', 'src/somefile.php'));
        $this->assertFalse($config->excludeIssueInFile('MissingReturnType', 'src/Core/somefile.php'));

        $this->assertSame('info', $config->getReportingLevelForFile('MissingReturnType', 'src/somefile.php'));
        $this->assertSame('error', $config->getReportingLevelForFile('MissingReturnType', 'src/Core/somefile.php'));
    }

    public function testAllPossibleIssues()
    {
        $all_possible_handlers = implode(
            ' ',
            array_map(
                function ($issue_name) {
                    return '<' . $issue_name . ' errorLevel="suppress" />' . PHP_EOL;
                },
                self::getAllIssues()
            )
        );

        $config = Config::loadFromXML('psalm.xml', '<?xml version="1.0"?>
<psalm>
    <projectFiles>
        <directory name="src" />
    </projectFiles>

    <issueHandlers>
    ' . $all_possible_handlers . '
    </issueHandlers>
</psalm>');
    }

    /**
     * @expectedException \Psalm\Exception\ConfigException
     * @expectedExceptionMessage This element is not expected
     */
    public function testImpossibleIssue()
    {
        $config = Config::loadFromXML('psalm.xml', '<?xml version="1.0"?>
<psalm>
    <projectFiles>
        <directory name="src" />
    </projectFiles>

    <issueHandlers>
        <ImpossibleIssue errorLevel="suppress" />
    </issueHandlers>
</psalm>');
    }
}
