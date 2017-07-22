<?php
namespace Psalm\Tests;

use Psalm\Checker\FileChecker;
use Psalm\Config;

class ConfigTest extends TestCase
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
        self::$config = new TestConfig();
    }

    /**
     * @return void
     */
    public function setUp()
    {
        FileChecker::clearCache();
        $this->file_provider = new Provider\FakeFileProvider();
        $this->project_checker = new \Psalm\Checker\ProjectChecker($this->file_provider);
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
            (string)getcwd(),
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
        );

        $this->assertFalse($config->reportIssueInFile('MissingReturnType', realpath('tests/ConfigTest.php')));
        $this->assertFalse($config->reportIssueInFile('MissingReturnType', realpath('src/Psalm/Type.php')));
    }

    /**
     * @return void
     */
    public function testIssueHandlerWithCustomErrorLevels()
    {
        $config = Config::loadFromXML(
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
        );

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

        $this->addFile(
            getcwd() . '/src/somefile.php',
            '<?php
                $a = new SystemClass();
                echo SystemClass::HELLO;

                $b = $a->foo(5, "hello");
                $c = SystemClass::bar(5, "hello");'
        );

        $file_checker = new FileChecker(getcwd() . '/src/somefile.php', $this->project_checker);
        $file_checker->visitAndAnalyzeMethods();
        $this->project_checker->checkClassReferences();
    }

    /**
     * @return void
     */
    public function testNamespacedStubClass()
    {
        $this->project_checker->setConfig(
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

        $this->addFile(
            getcwd() . '/src/somefile.php',
            '<?php
                $a = new Foo\SystemClass();
                echo Foo\SystemClass::HELLO;

                $b = $a->foo(5, "hello");
                $c = Foo\SystemClass::bar(5, "hello");'
        );

        $file_checker = new FileChecker(getcwd() . '/src/somefile.php', $this->project_checker);
        $file_checker->visitAndAnalyzeMethods();
        $this->project_checker->checkClassReferences();
    }

    /**
     * @return void
     */
    public function testStubFunction()
    {
        $this->project_checker->setConfig(
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

        $this->addFile(
            getcwd() . '/src/somefile.php',
            '<?php
                echo barBar("hello");'
        );

        $file_checker = new FileChecker(getcwd() . '/src/somefile.php', $this->project_checker);
        $file_checker->visitAndAnalyzeMethods();
        $this->project_checker->checkClassReferences();
    }

    /**
     * @return void
     */
    public function testNamespacedStubFunction()
    {
        $this->project_checker->setConfig(
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

        $this->addFile(
            getcwd() . '/src/somefile.php',
            '<?php
                echo Foo\barBar("hello");'
        );

        $file_checker = new FileChecker(getcwd() . '/src/somefile.php', $this->project_checker);
        $file_checker->visitAndAnalyzeMethods();
        $this->project_checker->checkClassReferences();
    }

    /**
     * @return void
     */
    public function testConditionalNamespacedStubFunction()
    {
        $this->project_checker->setConfig(
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

        $this->addFile(
            getcwd() . '/src/somefile.php',
            '<?php
                echo Foo\barBar("hello");'
        );

        $file_checker = new FileChecker(getcwd() . '/src/somefile.php', $this->project_checker);
        $file_checker->visitAndAnalyzeMethods();
        $this->project_checker->checkClassReferences();
    }

    /**
     * @return void
     */
    public function testStubFileWithExistingClassDefinition()
    {
        $this->project_checker->setConfig(
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

        $this->addFile(
            getcwd() . '/src/somefile.php',
            '<?php
                $a = new LogicException(5);'
        );

        $file_checker = new FileChecker(getcwd() . '/src/somefile.php', $this->project_checker);
        $file_checker->visitAndAnalyzeMethods();
        $this->project_checker->checkClassReferences();
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage MissingReturnType
     *
     * @return                   void
     */
    public function testRequireVoidReturnTypeExists()
    {
        $this->project_checker->setConfig(
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

        $this->addFile(
            getcwd() . '/src/somefile.php',
            '<?php
                function foo() {}'
        );

        $file_checker = new FileChecker(getcwd() . '/src/somefile.php', $this->project_checker);
        $file_checker->visitAndAnalyzeMethods();
        $this->project_checker->checkClassReferences();
    }

    /**
     * @return void
     */
    public function testDoNotRequireVoidReturnTypeExists()
    {
        $this->project_checker->setConfig(
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

        $this->addFile(
            getcwd() . '/src/somefile.php',
            '<?php
                function foo() {}'
        );

        $file_checker = new FileChecker(getcwd() . '/src/somefile.php', $this->project_checker);
        $file_checker->visitAndAnalyzeMethods();
        $this->project_checker->checkClassReferences();
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
