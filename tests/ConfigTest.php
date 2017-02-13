<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

class ConfigTest extends PHPUnit_Framework_TestCase
{
    /** @var \PhpParser\Parser */
    protected static $parser;

    /** @var TestConfig */
    protected static $config;

    /** @var \Psalm\Checker\ProjectChecker */
    protected $project_checker;

    /**
     * @return void
     */
    public static function setUpBeforeClass()
    {
        self::$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        self::$config = new TestConfig();
    }

    /**
     * @return void
     */
    public function setUp()
    {
        FileChecker::clearCache();
        $this->project_checker = new \Psalm\Checker\ProjectChecker();
    }

    /**
     * @return       string[]
     * @psalm-return array<mixed, string>
     */
    public static function getAllIssues()
    {
        return array_filter(
            array_map(
                /**
                 * @param string $file_name
                 * @return string
                 */
                function ($file_name) {
                    return substr($file_name, 0, -4);
                },
                scandir(dirname(__DIR__) . '/src/Psalm/Issue')
            ),
            /**
             * @param string $issue_name
             * @return bool
             */
            function ($issue_name) {
                return !empty($issue_name) && $issue_name !== 'CodeError' && $issue_name !== 'CodeIssue';
            }
        );
    }

    /**
     * @return void
     */
    public function testBarebonesConfig()
    {
        $config = Config::loadFromXML(
            'psalm.xml',
            '<?xml version="1.0"?>
            <psalm>
                <projectFiles>
                    <directory name="src" />
                </projectFiles>
            </psalm>'
        );

        $this->assertTrue($config->isInProjectDirs(realpath('src/Psalm/Type.php')));
        $this->assertFalse($config->isInProjectDirs(realpath('examples/StringChecker.php')));
    }

    /**
     * @return void
     */
    public function testIgnoreProjectDirectory()
    {
        $config = Config::loadFromXML(
            'psalm.xml',
            '<?xml version="1.0"?>
            <psalm>
                <projectFiles>
                    <directory name="src" />
                    <ignoreFiles>
                        <directory name="src/Psalm/Checker" />
                    </ignoreFiles>
                </projectFiles>
            </psalm>'
        );

        $this->assertTrue($config->isInProjectDirs(realpath('src/Psalm/Type.php')));
        $this->assertFalse($config->isInProjectDirs(realpath('src/Psalm/Checker/FileChecker.php')));
        $this->assertFalse($config->isInProjectDirs(realpath('examples/StringChecker.php')));
    }

    /**
     * @return void
     */
    public function testIssueHandler()
    {
        $config = Config::loadFromXML(
            'psalm.xml',
            '<?xml version="1.0"?>
            <psalm>
                <projectFiles>
                    <directory name="src" />
                    <directory name="tests" />
                </projectFiles>

                <issueHandlers>
                    <MissingReturnType errorLevel="suppress" />
                </issueHandlers>
            </psalm>'
        );

        $this->assertTrue($config->excludeIssueInFile('MissingReturnType', realpath('tests/ConfigTest.php')));
        $this->assertTrue($config->excludeIssueInFile('MissingReturnType', realpath('src/Psalm/Type.php')));
    }

    /**
     * @return void
     */
    public function testIssueHandlerWithCustomErrorLevels()
    {
        $config = Config::loadFromXML(
            'psalm.xml',
            '<?xml version="1.0"?>
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
                            <directory name="src/Psalm/Checker" />
                        </errorLevel>
                    </MissingReturnType>
                </issueHandlers>
            </psalm>'
        );

        $this->assertTrue($config->excludeIssueInFile('MissingReturnType', realpath('tests/ConfigTest.php')));
        $this->assertFalse($config->excludeIssueInFile('MissingReturnType', realpath('src/Psalm/Type.php')));
        $this->assertFalse($config->excludeIssueInFile('MissingReturnType', realpath('src/Psalm/Checker/FileChecker.php')));

        $this->assertSame('info', $config->getReportingLevelForFile('MissingReturnType', realpath('src/Psalm/Type.php')));
        $this->assertSame('error', $config->getReportingLevelForFile('MissingReturnType', realpath('src/Psalm/Checker/FileChecker.php')));
    }

    /**
     * @return void
     */
    public function testAllPossibleIssues()
    {
        $all_possible_handlers = implode(
            ' ',
            array_map(
                /**
                 * @param string $issue_name
                 * @return string
                 */
                function ($issue_name) {
                    return '<' . $issue_name . ' errorLevel="suppress" />' . PHP_EOL;
                },
                self::getAllIssues()
            )
        );

        Config::loadFromXML(
            'psalm.xml',
            '<?xml version="1.0"?>
            <psalm>
                <projectFiles>
                    <directory name="src" />
                </projectFiles>

                <issueHandlers>
                ' . $all_possible_handlers . '
                </issueHandlers>
            </psalm>'
        );
    }

    /**
     * @expectedException        \Psalm\Exception\ConfigException
     * @expectedExceptionMessage This element is not expected
     * @return                   void
     */
    public function testImpossibleIssue()
    {
        Config::loadFromXML(
            'psalm.xml',
            '<?xml version="1.0"?>
            <psalm>
                <projectFiles>
                    <directory name="src" />
                </projectFiles>

                <issueHandlers>
                    <ImpossibleIssue errorLevel="suppress" />
                </issueHandlers>
            </psalm>'
        );
    }

    /**
     * @expectedException        \Psalm\Exception\ConfigException
     * @expectedExceptionMessage Cannot resolve stubfile path
     * @return                   void
     */
    public function testNonexistentStubFile()
    {
        Config::loadFromXML(
            'psalm.xml',
            '<?xml version="1.0"?>
            <psalm>
                <projectFiles>
                    <directory name="src" />
                </projectFiles>

                <stubs>
                    <file name="stubs/invalidfile.php" />
                </stubs>
            </psalm>'
        );
    }

    /**
     * @return void
     */
    public function testStubFile()
    {
        $this->project_checker->setConfig(
            TestConfig::loadFromXML(
                'psalm.xml',
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>

                    <stubs>
                        <file name="tests/stubs/systemclass.php" />
                    </stubs>
                </psalm>'
            )
        );

        $stmts = self::$parser->parse('<?php
        $a = new SystemClass();
        echo SystemClass::HELLO;

        $b = $a->foo(5, "hello");
        $c = SystemClass::bar(5, "hello");
        ');

        $file_checker = new FileChecker(getcwd() . '/src/somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testStubFileWithExistingClassDefinition()
    {
        $this->project_checker->setConfig(
            TestConfig::loadFromXML(
                'psalm.xml',
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>

                    <stubs>
                        <file name="tests/stubs/logicexception.php" />
                    </stubs>
                </psalm>'
            )
        );

        $stmts = self::$parser->parse('<?php
        $a = new LogicException("bad");
        ');

        $file_checker = new FileChecker(getcwd() . '/src/somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage MissingReturnType
     * @return                   void
     */
    public function testRequireVoidReturnTypeExists()
    {
        $this->project_checker->setConfig(
            TestConfig::loadFromXML(
                'psalm.xml',
                '<?xml version="1.0"?>
                <psalm
                    requireVoidReturnType="true">
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                </psalm>'
            )
        );

        $stmts = self::$parser->parse('<?php
        function foo() {}
        ');

        $file_checker = new FileChecker(getcwd() . '/src/somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testDoNotRequireVoidReturnTypeExists()
    {
        $this->project_checker->setConfig(
            TestConfig::loadFromXML(
                'psalm.xml',
                '<?xml version="1.0"?>
                <psalm
                    requireVoidReturnType="false">
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                </psalm>'
            )
        );

        $stmts = self::$parser->parse('<?php
        function foo() {}
        ');

        $file_checker = new FileChecker(getcwd() . '/src/somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testTemplatedFiles()
    {
        foreach (['1.xml', '2.xml', '3.xml', '4.xml', '5.xml'] as $file_name) {
            Config::loadFromXMLFile(realpath(dirname(__DIR__) . '/assets/config_levels/' . $file_name));
        }

    }
}
