<?php
namespace Psalm\Tests;

use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

class ConfigTest extends TestCase
{
    /** @var TestConfig */
    protected static $config;

    /** @var \Psalm\Checker\ProjectChecker */
    protected $project_checker;

    /**
     * @return void
     */
    public static function setUpBeforeClass()
    {
        self::$config = new TestConfig();
    }

    /**
     * @return void
     */
    public function setUp()
    {
        FileChecker::clearCache();
        $this->file_provider = new Provider\FakeFileProvider();
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
                 *
                 * @return string
                 */
                function ($file_name) {
                    return substr($file_name, 0, -4);
                },
                scandir(dirname(__DIR__) . '/src/Psalm/Issue')
            ),
            /**
             * @param string $issue_name
             *
             * @return bool
             */
            function ($issue_name) {
                return !empty($issue_name)
                    && $issue_name !== 'CodeError'
                    && $issue_name !== 'CodeIssue'
                    && $issue_name !== 'FixableCodeIssue';
            }
        );
    }

    /**
     * @param  Config $config
     *
     * @return \Psalm\Checker\ProjectChecker
     */
    private function getProjectCheckerWithConfig(Config $config)
    {
        return new \Psalm\Checker\ProjectChecker(
            $config,
            $this->file_provider,
            new Provider\FakeParserCacheProvider()
        );
    }

    /**
     * @return void
     */
    public function testBarebonesConfig()
    {
        $this->project_checker = $this->getProjectCheckerWithConfig(
            Config::loadFromXML(
                'psalm.xml',
                (string)getcwd(),
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                </psalm>'
            )
        );

        $config = $this->project_checker->getConfig();

        $this->assertTrue($config->isInProjectDirs(realpath('src/Psalm/Type.php')));
        $this->assertFalse($config->isInProjectDirs(realpath('examples/StringChecker.php')));
    }

    /**
     * @return void
     */
    public function testIgnoreProjectDirectory()
    {
        $this->project_checker = $this->getProjectCheckerWithConfig(
            Config::loadFromXML(
                'psalm.xml',
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="src" />
                        <ignoreFiles>
                            <directory name="src/Psalm/Checker" />
                        </ignoreFiles>
                    </projectFiles>
                </psalm>'
            )
        );

        $config = $this->project_checker->getConfig();

        $this->assertTrue($config->isInProjectDirs(realpath('src/Psalm/Type.php')));
        $this->assertFalse($config->isInProjectDirs(realpath('src/Psalm/Checker/FileChecker.php')));
        $this->assertFalse($config->isInProjectDirs(realpath('examples/StringChecker.php')));
    }

    /**
     * @return void
     */
    public function testIssueHandler()
    {
        $this->project_checker = $this->getProjectCheckerWithConfig(
            Config::loadFromXML(
                'psalm.xml',
                dirname(__DIR__),
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
            )
        );

        $config = $this->project_checker->getConfig();

        $this->assertFalse($config->reportIssueInFile('MissingReturnType', realpath('tests/ConfigTest.php')));
        $this->assertFalse($config->reportIssueInFile('MissingReturnType', realpath('src/Psalm/Type.php')));
    }

    /**
     * @return void
     */
    public function testIssueHandlerWithCustomErrorLevels()
    {
        $this->project_checker = $this->getProjectCheckerWithConfig(
            Config::loadFromXML(
                'psalm.xml',
                dirname(__DIR__),
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
            )
        );

        $config = $this->project_checker->getConfig();

        $this->assertSame(
            'info',
            $config->getReportingLevelForFile(
                'MissingReturnType',
                realpath('src/Psalm/Type.php')
            )
        );

        $this->assertSame(
            'error',
            $config->getReportingLevelForFile(
                'MissingReturnType',
                realpath('src/Psalm/Checker/FileChecker.php')
            )
        );
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
                 *
                 * @return string
                 */
                function ($issue_name) {
                    return '<' . $issue_name . ' errorLevel="suppress" />' . PHP_EOL;
                },
                self::getAllIssues()
            )
        );

        $this->project_checker = $this->getProjectCheckerWithConfig(
            Config::loadFromXML(
                'psalm.xml',
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>

                    <issueHandlers>
                    ' . $all_possible_handlers . '
                    </issueHandlers>
                </psalm>'
            )
        );
    }

    /**
     * @expectedException        \Psalm\Exception\ConfigException
     * @expectedExceptionMessage This element is not expected
     *
     * @return                   void
     */
    public function testImpossibleIssue()
    {
        $this->project_checker = $this->getProjectCheckerWithConfig(
            Config::loadFromXML(
                'psalm.xml',
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>

                    <issueHandlers>
                        <ImpossibleIssue errorLevel="suppress" />
                    </issueHandlers>
                </psalm>'
            )
        );
    }

    /**
     * @expectedException        \Psalm\Exception\ConfigException
     * @expectedExceptionMessage Cannot resolve stubfile path
     *
     * @return                   void
     */
    public function testNonexistentStubFile()
    {
        $this->project_checker = $this->getProjectCheckerWithConfig(
            Config::loadFromXML(
                'psalm.xml',
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>

                    <stubs>
                        <file name="stubs/invalidfile.php" />
                    </stubs>
                </psalm>'
            )
        );
    }

    /**
     * @return void
     */
    public function testStubFile()
    {
        $this->project_checker = $this->getProjectCheckerWithConfig(
            TestConfig::loadFromXML(
                'psalm.xml',
                dirname(__DIR__),
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

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                $a = new SystemClass();
                echo SystemClass::HELLO;

                $b = $a->foo(5, "hello");
                $c = SystemClass::bar(5, "hello");'
        );

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @return void
     */
    public function testNamespacedStubClass()
    {
        $this->project_checker = $this->getProjectCheckerWithConfig(
            TestConfig::loadFromXML(
                'psalm.xml',
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>

                    <stubs>
                        <file name="tests/stubs/namespaced_class.php" />
                    </stubs>
                </psalm>'
            )
        );

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                $a = new Foo\SystemClass();
                echo Foo\SystemClass::HELLO;

                $b = $a->foo(5, "hello");
                $c = Foo\SystemClass::bar(5, "hello");'
        );

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @return void
     */
    public function testStubFunction()
    {
        $this->project_checker = $this->getProjectCheckerWithConfig(
            TestConfig::loadFromXML(
                'psalm.xml',
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>

                    <stubs>
                        <file name="tests/stubs/custom_functions.php" />
                    </stubs>
                </psalm>'
            )
        );

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                echo barBar("hello");'
        );

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage UndefinedFunction - /src/somefile.php:2 - Function barBar does not exist
     *
     * @return                   void
     */
    public function testNoStubFunction()
    {
        $this->project_checker = $this->getProjectCheckerWithConfig(
            TestConfig::loadFromXML(
                'psalm.xml',
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                </psalm>'
            )
        );

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                echo barBar("hello");'
        );

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @return void
     */
    public function testNamespacedStubFunction()
    {
        $this->project_checker = $this->getProjectCheckerWithConfig(
            TestConfig::loadFromXML(
                'psalm.xml',
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>

                    <stubs>
                        <file name="tests/stubs/namespaced_functions.php" />
                    </stubs>
                </psalm>'
            )
        );

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                echo Foo\barBar("hello");'
        );

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @return void
     */
    public function testConditionalNamespacedStubFunction()
    {
        $this->project_checker = $this->getProjectCheckerWithConfig(
            TestConfig::loadFromXML(
                'psalm.xml',
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>

                    <stubs>
                        <file name="tests/stubs/conditional_namespaced_functions.php" />
                    </stubs>
                </psalm>'
            )
        );

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                echo Foo\barBar("hello");'
        );

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @return void
     */
    public function testStubFileWithExistingClassDefinition()
    {
        $this->project_checker = $this->getProjectCheckerWithConfig(
            TestConfig::loadFromXML(
                'psalm.xml',
                dirname(__DIR__),
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

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                $a = new LogicException(5);'
        );

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage MissingReturnType
     *
     * @return                   void
     */
    public function testRequireVoidReturnTypeExists()
    {
        $this->project_checker = $this->getProjectCheckerWithConfig(
            TestConfig::loadFromXML(
                'psalm.xml',
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm
                    requireVoidReturnType="true">
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                </psalm>'
            )
        );

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                function foo() {}'
        );

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @return void
     */
    public function testDoNotRequireVoidReturnTypeExists()
    {
        $this->project_checker = $this->getProjectCheckerWithConfig(
            TestConfig::loadFromXML(
                'psalm.xml',
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm
                    requireVoidReturnType="false">
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                </psalm>'
            )
        );

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                function foo() {}'
        );

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @return void
     */
    public function testTemplatedFiles()
    {
        foreach (['1.xml', '2.xml', '3.xml', '4.xml', '5.xml'] as $file_name) {
            Config::loadFromXMLFile(
                realpath(dirname(__DIR__) . '/assets/config_levels/' . $file_name),
                dirname(__DIR__)
            );
        }
    }
}
