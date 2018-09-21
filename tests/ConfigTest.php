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

        if (!defined('PSALM_VERSION')) {
            define('PSALM_VERSION', '2.0.0');
        }

        if (!defined('PHP_PARSER_VERSION')) {
            define('PHP_PARSER_VERSION', '4.0.0');
        }
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
                    && $issue_name !== 'MethodIssue'
                    && $issue_name !== 'PropertyIssue'
                    && $issue_name !== 'ClassIssue'
                    && $issue_name !== 'CodeIssue';
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
            new Provider\FakeParserCacheProvider(),
            new \Psalm\Provider\NoCache\NoFileStorageCacheProvider(),
            new \Psalm\Provider\NoCache\NoClassLikeStorageCacheProvider()
        );
    }

    /**
     * @return void
     */
    public function testBarebonesConfig()
    {
        $this->project_checker = $this->getProjectCheckerWithConfig(
            Config::loadFromXML(
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
    public function testIgnoreWildcardProjectDirectory()
    {
        $this->project_checker = $this->getProjectCheckerWithConfig(
            Config::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="src" />
                        <ignoreFiles>
                            <directory name="src/**/Checker" />
                        </ignoreFiles>
                    </projectFiles>
                </psalm>'
            )
        );

        $config = $this->project_checker->getConfig();

        $this->assertTrue($config->isInProjectDirs(realpath('src/Psalm/Type.php')));
        $this->assertFalse($config->isInProjectDirs(realpath('src/Psalm/Checker/FileChecker.php')));
        $this->assertFalse($config->isInProjectDirs(realpath('src/Psalm/Checker/Statements/ReturnChecker.php')));
        $this->assertFalse($config->isInProjectDirs(realpath('examples/StringChecker.php')));
    }

    /**
     * @return void
     */
    public function testIgnoreWildcardFiles()
    {
        $this->project_checker = $this->getProjectCheckerWithConfig(
            Config::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="src" />
                        <ignoreFiles>
                            <file name="src/Psalm/Checker/*Checker.php" />
                        </ignoreFiles>
                    </projectFiles>
                </psalm>'
            )
        );

        $config = $this->project_checker->getConfig();

        $this->assertTrue($config->isInProjectDirs(realpath('src/Psalm/Type.php')));
        $this->assertFalse($config->isInProjectDirs(realpath('src/Psalm/Checker/FileChecker.php')));
        $this->assertTrue($config->isInProjectDirs(realpath('src/Psalm/Checker/Statements/ReturnChecker.php')));
        $this->assertFalse($config->isInProjectDirs(realpath('examples/StringChecker.php')));
    }

    /**
     * @return void
     */
    public function testIgnoreWildcardFilesInWildcardFolder()
    {
        $this->project_checker = $this->getProjectCheckerWithConfig(
            Config::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="src" />
                        <directory name="examples" />
                        <ignoreFiles>
                            <file name="src/Psalm/**/*Checker.php" />
                            <file name="src/Psalm/**/**/*Checker.php" />
                        </ignoreFiles>
                    </projectFiles>
                </psalm>'
            )
        );

        $config = $this->project_checker->getConfig();

        $this->assertTrue($config->isInProjectDirs(realpath('src/Psalm/Type.php')));
        $this->assertTrue($config->isInProjectDirs(realpath('src/Psalm/Visitor/DependencyFinderVisitor.php')));
        $this->assertFalse($config->isInProjectDirs(realpath('src/Psalm/Checker/FileChecker.php')));
        $this->assertFalse($config->isInProjectDirs(realpath('src/Psalm/Checker/Statements/ReturnChecker.php')));
        $this->assertTrue($config->isInProjectDirs(realpath('examples/StringChecker.php')));
    }

    /**
     * @return void
     */
    public function testIgnoreWildcardFilesInAllPossibleWildcardFolders()
    {
        $this->project_checker = $this->getProjectCheckerWithConfig(
            Config::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="src" />
                        <directory name="examples" />
                        <ignoreFiles>
                            <file name="**/*Checker.php" />
                            <file name="**/**/**/*Checker.php" />
                            <file name="**/**/**/**/*Checker.php" />
                        </ignoreFiles>
                    </projectFiles>
                </psalm>'
            )
        );

        $config = $this->project_checker->getConfig();

        $this->assertTrue($config->isInProjectDirs(realpath('src/Psalm/Type.php')));
        $this->assertTrue($config->isInProjectDirs(realpath('src/Psalm/Visitor/DependencyFinderVisitor.php')));
        $this->assertFalse($config->isInProjectDirs(realpath('src/Psalm/Checker/FileChecker.php')));
        $this->assertFalse($config->isInProjectDirs(realpath('src/Psalm/Checker/Statements/ReturnChecker.php')));
        $this->assertFalse($config->isInProjectDirs(realpath('examples/StringChecker.php')));
    }

    /**
     * @return void
     */
    public function testIssueHandler()
    {
        $this->project_checker = $this->getProjectCheckerWithConfig(
            Config::loadFromXML(
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
                        <UndefinedClass>
                            <errorLevel type="suppress">
                                <referencedClass name="Psalm\Badger" />
                            </errorLevel>
                        </UndefinedClass>
                        <UndefinedMethod>
                            <errorLevel type="suppress">
                                <referencedMethod name="Psalm\Bodger::find1" />
                            </errorLevel>
                        </UndefinedMethod>
                        <UndefinedPropertyFetch>
                            <errorLevel type="suppress">
                                <referencedProperty name="Psalm\Bodger::$find3" />
                            </errorLevel>
                        </UndefinedPropertyFetch>
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

        $this->assertSame(
            'suppress',
            $config->getReportingLevelForClass(
                'UndefinedClass',
                'Psalm\Badger'
            )
        );

        $this->assertSame(
            'error',
            $config->getReportingLevelForClass(
                'UndefinedClass',
                'Psalm\Bodger'
            )
        );

        $this->assertSame(
            'suppress',
            $config->getReportingLevelForMethod(
                'UndefinedMethod',
                'Psalm\Bodger::find1'
            )
        );

        $this->assertSame(
            'error',
            $config->getReportingLevelForProperty(
                'UndefinedMethod',
                'Psalm\Bodger::$find3'
            )
        );

        $this->assertSame(
            'error',
            $config->getReportingLevelForProperty(
                'UndefinedMethod',
                'Psalm\Bodger::$find4'
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
                    return '<' . $issue_name . ' errorLevel="suppress" />' . "\n";
                },
                self::getAllIssues()
            )
        );

        $this->project_checker = $this->getProjectCheckerWithConfig(
            Config::loadFromXML(
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
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage MissingReturnType
     *
     * @return                   void
     */
    public function testRequireVoidReturnTypeExists()
    {
        $this->project_checker = $this->getProjectCheckerWithConfig(
            TestConfig::loadFromXML(
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
    public function testMethodCallMemoize()
    {
        $this->project_checker = $this->getProjectCheckerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm memoizeMethodCallResults="true">
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
                class A {
                    function getFoo() : ?Foo {
                        return rand(0, 1) ? new Foo : null;
                    }
                }
                class Foo {
                    function getBar() : ?Bar {
                        return rand(0, 1) ? new Bar : null;
                    }
                }
                class Bar {
                    public function bat() : void {}
                };

                $a = new A();

                if ($a->getFoo()) {
                    if ($a->getFoo()->getBar()) {
                        $a->getFoo()->getBar()->bat();
                    }
                }'
        );

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @return void
     */
    public function testExitFunctions()
    {
        $this->project_checker = $this->getProjectCheckerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm>
                    <exitFunctions>
                        <function name="leave" />
                        <function name="Foo\namespacedLeave" />
                        <function name="Foo\Bar::staticLeave" />
                    </exitFunctions>
                </psalm>'
            )
        );

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                namespace {
                    function leave() : void {
                        exit();
                    }

                    function mightLeave() : string {
                        if (rand(0, 1)) {
                            leave();
                        } else {
                            return "here";
                        }
                    }

                    function mightLeaveWithNamespacedFunction() : string {
                        if (rand(0, 1)) {
                            \Foo\namespacedLeave();
                        } else {
                            return "here";
                        }
                    }

                    function mightLeaveWithStaticMethod() : string {
                        if (rand(0, 1)) {
                            Foo\Bar::staticLeave();
                        } else {
                            return "here";
                        }
                    }
                }

                namespace Foo {
                    function namespacedLeave() : void {
                        exit();
                    }

                    class Bar {
                        public static function staticLeave() : void {
                            exit();
                        }
                    }
                }'
        );

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @return void
     */
    public function testAllowedEchoFunction()
    {
        $this->project_checker = $this->getProjectCheckerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm></psalm>'
            )
        );

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                echo "hello";'
        );

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @expectedException  \Psalm\Exception\CodeException
     * @expectedExceptionMessage  ForbiddenCode
     * @return void
     */
    public function testForbiddenEchoFunctionViaFunctions()
    {
        $this->project_checker = $this->getProjectCheckerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm>
                    <forbiddenFunctions>
                        <function name="echo" />
                    </forbiddenFunctions>
                </psalm>'
            )
        );

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                echo "hello";'
        );

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @expectedException  \Psalm\Exception\CodeException
     * @expectedExceptionMessage  ForbiddenEcho
     * @return void
     */
    public function testForbiddenEchoFunctionViaFlag()
    {
        $this->project_checker = $this->getProjectCheckerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm forbidEcho="true"></psalm>'
            )
        );

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                echo "hello";'
        );

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @return void
     */
    public function testAllowedVarExportFunction()
    {
        $this->project_checker = $this->getProjectCheckerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm></psalm>'
            )
        );

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                $a = [1, 2, 3];
                var_export($a);'
        );

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @expectedException  \Psalm\Exception\CodeException
     * @expectedExceptionMessage  ForbiddenCode
     * @return  void
     */
    public function testForbiddenVarExportFunction()
    {
        $this->project_checker = $this->getProjectCheckerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm>
                    <forbiddenFunctions>
                        <function name="var_export" />
                    </forbiddenFunctions>
                </psalm>'
            )
        );

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                $a = [1, 2, 3];
                var_export($a);'
        );

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @expectedException  \Psalm\Exception\CodeException
     * @expectedExceptionMessage  InvalidCatch
     * @return void
     */
    public function testValidThrowInvalidCatch()
    {
        $this->project_checker = $this->getProjectCheckerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm>
                    <issueHandlers>
                        <InvalidThrow>
                            <errorLevel type="suppress">
                                <referencedClass name="I" />
                            </errorLevel>
                        </InvalidThrow>
                    </issueHandlers>
                </psalm>'
            )
        );

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                interface I {}

                class E extends Exception implements I {}

                function foo() : void {
                    throw new E();
                }

                function handleThrow(I $e) : void {
                    echo "about to throw";
                    throw $e;
                }

                try {
                    foo();
                } catch (I $e) {
                    handleThrow($e);
                }'
        );

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @expectedException  \Psalm\Exception\CodeException
     * @expectedExceptionMessage  InvalidThrow
     * @return void
     */
    public function testInvalidThrowValidCatch()
    {
        $this->project_checker = $this->getProjectCheckerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm>
                    <issueHandlers>
                        <InvalidCatch>
                            <errorLevel type="suppress">
                                <referencedClass name="I" />
                            </errorLevel>
                        </InvalidCatch>
                    </issueHandlers>
                </psalm>'
            )
        );

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                interface I {}

                class E extends Exception implements I {}

                function foo() : void {
                    throw new E();
                }

                function handleThrow(I $e) : void {
                    echo "about to throw";
                    throw $e;
                }

                try {
                    foo();
                } catch (I $e) {
                    handleThrow($e);
                }'
        );

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @return void
     */
    public function testValidThrowValidCatch()
    {
        $this->project_checker = $this->getProjectCheckerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm>
                    <issueHandlers>
                        <InvalidCatch>
                            <errorLevel type="suppress">
                                <referencedClass name="I" />
                            </errorLevel>
                        </InvalidCatch>
                        <InvalidThrow>
                            <errorLevel type="suppress">
                                <referencedClass name="I" />
                            </errorLevel>
                        </InvalidThrow>
                    </issueHandlers>
                </psalm>'
            )
        );

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                interface I {}

                class E extends Exception implements I {}

                function foo() : void {
                    throw new E();
                }

                function handleThrow(I $e) : void {
                    echo "about to throw";
                    throw $e;
                }

                try {
                    foo();
                } catch (I $e) {
                    handleThrow($e);
                }'
        );

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @return void
     */
    public function testTemplatedFiles()
    {
        foreach (['1.xml', '2.xml', '3.xml', '4.xml', '5.xml', '6.xml', '7.xml', '8.xml'] as $file_name) {
            Config::loadFromXMLFile(
                realpath(dirname(__DIR__) . '/assets/config_levels/' . $file_name),
                dirname(__DIR__)
            );
        }
    }
}
