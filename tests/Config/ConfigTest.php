<?php
namespace Psalm\Tests\Config;

use Psalm\Config;
use Psalm\Context;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Tests\Internal\Provider;
use Psalm\Tests\TestConfig;

class ConfigTest extends \Psalm\Tests\TestCase
{
    /** @var TestConfig */
    protected static $config;

    /** @var \Psalm\Internal\Analyzer\ProjectAnalyzer */
    protected $project_analyzer;

    /**
     * @return void
     */
    public static function setUpBeforeClass() : void
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
    public function setUp() : void
    {
        FileAnalyzer::clearCache();
        $this->file_provider = new Provider\FakeFileProvider();
    }

    /**
     * @param  Config $config
     *
     * @return \Psalm\Internal\Analyzer\ProjectAnalyzer
     */
    private function getProjectAnalyzerWithConfig(Config $config)
    {
        $p = new \Psalm\Internal\Analyzer\ProjectAnalyzer(
            $config,
            new \Psalm\Internal\Provider\Providers(
                $this->file_provider,
                new Provider\FakeParserCacheProvider()
            )
        );

        $p->setPhpVersion('7.3');

        return $p;
    }

    /**
     * @return void
     */
    public function testBarebonesConfig()
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
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

        $config = $this->project_analyzer->getConfig();

        $this->assertTrue($config->isInProjectDirs(realpath('src/Psalm/Type.php')));
        $this->assertFalse($config->isInProjectDirs(realpath('examples/StringAnalyzer.php')));
    }

    /**
     * @return void
     */
    public function testIgnoreProjectDirectory()
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            Config::loadFromXML(
                dirname(__DIR__, 2),
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="src" />
                        <ignoreFiles>
                            <directory name="src/Psalm/Internal/Analyzer" />
                        </ignoreFiles>
                    </projectFiles>
                </psalm>'
            )
        );

        $config = $this->project_analyzer->getConfig();

        $this->assertTrue($config->isInProjectDirs(realpath('src/Psalm/Type.php')));
        $this->assertFalse($config->isInProjectDirs(realpath('src/Psalm/Internal/Analyzer/FileAnalyzer.php')));
        $this->assertFalse($config->isInProjectDirs(realpath('examples/StringAnalyzer.php')));
    }

    /**
     * @return void
     */
    public function testIgnoreMissingProjectDirectory()
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            Config::loadFromXML(
                dirname(__DIR__, 2),
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="src" />
                        <ignoreFiles allowMissingFiles="true">
                            <directory name="does/not/exist" />
                        </ignoreFiles>
                    </projectFiles>
                </psalm>'
            )
        );

        $config = $this->project_analyzer->getConfig();

        $this->assertTrue($config->isInProjectDirs(realpath('src/Psalm/Type.php')));
        $this->assertFalse($config->isInProjectDirs(realpath('does/not/exist/FileAnalyzer.php')));
        $this->assertFalse($config->isInProjectDirs(realpath('examples/StringAnalyzer.php')));
    }

    /**
     * @return void
     */
    public function testIgnoreSymlinkedProjectDirectory()
    {
        @unlink(dirname(__DIR__, 1) . '/fixtures/symlinktest/ignored/b');

        $no_symlinking_error = 'symlink(): Cannot create symlink, error code(1314)';
        $last_error = error_get_last();
        $check_symlink_error =
            !is_array($last_error) ||
            !isset($last_error['message']) ||
            $no_symlinking_error !== $last_error['message'];

        @symlink(dirname(__DIR__, 1) . '/fixtures/symlinktest/a', dirname(__DIR__, 1) . '/fixtures/symlinktest/ignored/b');

        if ($check_symlink_error) {
            $last_error = error_get_last();

            if (is_array($last_error) && $no_symlinking_error === $last_error['message']) {
                $this->markTestSkipped($no_symlinking_error);

                return;
            }
        }

        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            Config::loadFromXML(
                dirname(__DIR__, 2),
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="tests" />
                        <ignoreFiles>
                            <directory name="tests/fixtures/symlinktest/ignored" />
                        </ignoreFiles>
                    </projectFiles>
                </psalm>'
            )
        );

        $config = $this->project_analyzer->getConfig();

        $this->assertTrue($config->isInProjectDirs(realpath('tests/AnnotationTest.php')));
        $this->assertFalse($config->isInProjectDirs(realpath('tests/fixtures/symlinktest/a/ignoreme.php')));
        $this->assertFalse($config->isInProjectDirs(realpath('examples/StringAnalyzer.php')));

        $regex = '/^unlink\([^\)]+\): (?:Permission denied|No such file or directory)$/';
        $last_error = error_get_last();

        $check_unlink_error =
            !is_array($last_error) ||
            !preg_match($regex, $last_error['message']);

        @unlink(__DIR__ . '/fixtures/symlinktest/ignored/b');

        if ($check_unlink_error) {
            $last_error = error_get_last();

            if (is_array($last_error) && !preg_match($regex, $last_error['message'])) {
                throw new \ErrorException(
                    $last_error['message'],
                    0,
                    $last_error['type'],
                    $last_error['file'],
                    $last_error['line']
                );
            }
        }
    }

    /**
     * @return void
     */
    public function testIgnoreWildcardProjectDirectory()
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            Config::loadFromXML(
                dirname(__DIR__, 2),
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="src" />
                        <ignoreFiles>
                            <directory name="src/**/Internal/Analyzer" />
                        </ignoreFiles>
                    </projectFiles>
                </psalm>'
            )
        );

        $config = $this->project_analyzer->getConfig();

        $this->assertTrue($config->isInProjectDirs(realpath('src/Psalm/Type.php')));
        $this->assertFalse($config->isInProjectDirs(realpath('src/Psalm/Internal/Analyzer/FileAnalyzer.php')));
        $this->assertFalse($config->isInProjectDirs(realpath('src/Psalm/Internal/Analyzer/Statements/ReturnAnalyzer.php')));
        $this->assertFalse($config->isInProjectDirs(realpath('examples/StringAnalyzer.php')));
    }

    /**
     * @return void
     */
    public function testIgnoreWildcardFiles()
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            Config::loadFromXML(
                dirname(__DIR__, 2),
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="src" />
                        <ignoreFiles>
                            <file name="src/Psalm/Internal/Analyzer/*Analyzer.php" />
                        </ignoreFiles>
                    </projectFiles>
                </psalm>'
            )
        );

        $config = $this->project_analyzer->getConfig();

        $this->assertTrue($config->isInProjectDirs(realpath('src/Psalm/Type.php')));
        $this->assertFalse($config->isInProjectDirs(realpath('src/Psalm/Internal/Analyzer/FileAnalyzer.php')));
        $this->assertTrue($config->isInProjectDirs(realpath('src/Psalm/Internal/Analyzer/Statements/ReturnAnalyzer.php')));
        $this->assertFalse($config->isInProjectDirs(realpath('examples/StringAnalyzer.php')));
    }

    /**
     * @return void
     */
    public function testIgnoreWildcardFilesInWildcardFolder()
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            Config::loadFromXML(
                dirname(__DIR__, 2),
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="src" />
                        <directory name="examples" />
                        <ignoreFiles>
                            <file name="src/Psalm/**/**/*Analyzer.php" />
                            <file name="src/Psalm/**/**/**/*Analyzer.php" />
                        </ignoreFiles>
                    </projectFiles>
                </psalm>'
            )
        );

        $config = $this->project_analyzer->getConfig();

        $this->assertTrue($config->isInProjectDirs(realpath('src/Psalm/Type.php')));
        $this->assertTrue($config->isInProjectDirs(realpath('src/Psalm/Internal/Visitor/ReflectorVisitor.php')));
        $this->assertFalse($config->isInProjectDirs(realpath('src/Psalm/Internal/Analyzer/FileAnalyzer.php')));
        $this->assertFalse($config->isInProjectDirs(realpath('src/Psalm/Internal/Analyzer/Statements/ReturnAnalyzer.php')));
        $this->assertTrue($config->isInProjectDirs(realpath('examples/plugins/StringChecker.php')));
    }

    /**
     * @return void
     */
    public function testIgnoreWildcardFilesInAllPossibleWildcardFolders()
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            Config::loadFromXML(
                dirname(__DIR__, 2),
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="src" />
                        <directory name="examples" />
                        <ignoreFiles>
                            <file name="**/**/**/**/*Analyzer.php" />
                            <file name="**/**/**/**/**/*Analyzer.php" />
                        </ignoreFiles>
                    </projectFiles>
                </psalm>'
            )
        );

        $config = $this->project_analyzer->getConfig();

        $this->assertTrue($config->isInProjectDirs(realpath('src/Psalm/Type.php')));
        $this->assertTrue($config->isInProjectDirs(realpath('src/Psalm/Internal/Visitor/ReflectorVisitor.php')));
        $this->assertFalse($config->isInProjectDirs(realpath('src/Psalm/Internal/Analyzer/FileAnalyzer.php')));
        $this->assertFalse($config->isInProjectDirs(realpath('src/Psalm/Internal/Analyzer/Statements/ReturnAnalyzer.php')));
        $this->assertFalse($config->isInProjectDirs(realpath('examples/StringAnalyzer.php')));
    }

    /**
     * @return void
     */
    public function testIssueHandler()
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            Config::loadFromXML(
                dirname(__DIR__, 2),
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

        $config = $this->project_analyzer->getConfig();

        $this->assertFalse($config->reportIssueInFile('MissingReturnType', realpath('tests/ConfigTest.php')));
        $this->assertFalse($config->reportIssueInFile('MissingReturnType', realpath('src/Psalm/Type.php')));
    }

    /**
     * @return void
     */
    public function testIssueHandlerWithCustomErrorLevels()
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            Config::loadFromXML(
                dirname(__DIR__, 2),
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
                                <directory name="src/Psalm/Internal/Analyzer" />
                            </errorLevel>
                        </MissingReturnType>
                        <UndefinedClass>
                            <errorLevel type="suppress">
                                <referencedClass name="Psalm\Badger" />
                                <referencedClass name="Psalm\*Actor" />
                                <referencedClass name="*MagicFactory" />
                            </errorLevel>
                        </UndefinedClass>
                        <UndefinedMethod>
                            <errorLevel type="suppress">
                                <referencedMethod name="Psalm\Bodger::find1" />
                                <referencedMethod name="*::find2" />
                            </errorLevel>
                        </UndefinedMethod>
                        <UndefinedFunction>
                            <errorLevel type="suppress">
                                <referencedFunction name="fooBar" />
                            </errorLevel>
                            <errorLevel type="info">
                                <directory name="examples" />
                            </errorLevel>
                        </UndefinedFunction>
                        <PossiblyInvalidArgument>
                            <errorLevel type="suppress">
                                <directory name="tests" />
                            </errorLevel>
                            <errorLevel type="info">
                                <directory name="examples" />
                            </errorLevel>
                        </PossiblyInvalidArgument>
                        <UndefinedPropertyFetch>
                            <errorLevel type="suppress">
                                <referencedProperty name="Psalm\Bodger::$find3" />
                            </errorLevel>
                        </UndefinedPropertyFetch>
                    </issueHandlers>
                </psalm>'
            )
        );

        $config = $this->project_analyzer->getConfig();

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
                realpath('src/Psalm/Internal/Analyzer/FileAnalyzer.php')
            )
        );

        $this->assertSame(
            'error',
            $config->getReportingLevelForFile(
                'PossiblyInvalidArgument',
                realpath('src/psalm.php')
            )
        );

        $this->assertSame(
            'info',
            $config->getReportingLevelForFile(
                'PossiblyInvalidArgument',
                realpath('examples/TemplateChecker.php')
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
            'suppress',
            $config->getReportingLevelForClass(
                'UndefinedClass',
                'Psalm\BadActor'
            )
        );

        $this->assertSame(
            'suppress',
            $config->getReportingLevelForClass(
                'UndefinedClass',
                'Psalm\GoodActor'
            )
        );

        $this->assertSame(
            'suppress',
            $config->getReportingLevelForClass(
                'UndefinedClass',
                'Psalm\MagicFactory'
            )
        );

        $this->assertSame(
            null,
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
            'suppress',
            $config->getReportingLevelForMethod(
                'UndefinedMethod',
                'Psalm\Bodger::find2'
            )
        );

        $this->assertSame(
            'suppress',
            $config->getReportingLevelForMethod(
                'UndefinedMethod',
                'Psalm\Badger::find2'
            )
        );

        $this->assertSame(
            null,
            $config->getReportingLevelForProperty(
                'UndefinedMethod',
                'Psalm\Bodger::$find3'
            )
        );

        $this->assertSame(
            null,
            $config->getReportingLevelForProperty(
                'UndefinedMethod',
                'Psalm\Bodger::$find4'
            )
        );

        $this->assertSame(
            'suppress',
            $config->getReportingLevelForMethod(
                'UndefinedFunction',
                'fooBar'
            )
        );

        $this->assertSame(
            'suppress',
            $config->getReportingLevelForMethod(
                'UndefinedFunction',
                'foobar'
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
                \Psalm\Config\IssueHandler::getAllIssueTypes()
            )
        );

        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            Config::loadFromXML(
                dirname(__DIR__, 2),
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
     *
     * @return void
     */
    public function testImpossibleIssue()
    {
        $this->expectExceptionMessage('This element is not expected');
        $this->expectException(\Psalm\Exception\ConfigException::class);
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            Config::loadFromXML(
                dirname(__DIR__, 2),
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
     *
     * @return void
     */
    public function testRequireVoidReturnTypeExists()
    {
        $this->expectExceptionMessage('MissingReturnType');
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2),
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
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2),
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
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2),
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
    public function testThing()
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2),
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                    <mockClasses>
                        <class name="MyMockClass" />
                    </mockClasses>
                </psalm>'
            )
        );

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                class MyMockClass {}

                $a = new MyMockClass();
                $a->foo($b = 5);
                echo $b;'
        );

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @return void
     */
    public function testExitFunctions()
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2),
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
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2),
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
     *
     * @return void
     */
    public function testForbiddenEchoFunctionViaFunctions()
    {
        $this->expectExceptionMessage('ForbiddenCode');
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2),
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
     *
     * @return void
     */
    public function testForbiddenEchoFunctionViaFlag()
    {
        $this->expectExceptionMessage('ForbiddenEcho');
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2),
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
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2),
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
     *
     * @return void
     */
    public function testForbiddenVarExportFunction()
    {
        $this->expectExceptionMessage('ForbiddenCode');
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2),
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
     *
     * @return void
     */
    public function testValidThrowInvalidCatch()
    {
        $this->expectExceptionMessage('InvalidCatch');
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2),
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
     *
     * @return void
     */
    public function testInvalidThrowValidCatch()
    {
        $this->expectExceptionMessage('InvalidThrow');
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2),
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
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2),
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
                realpath(dirname(__DIR__, 2) . '/assets/config_levels/' . $file_name),
                dirname(__DIR__, 2)
            );
        }
    }

    /**
     * @return void
     */
    public function testGlobals()
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2),
                '<?xml version="1.0"?>
                <psalm>
                    <globals>
                        <var name="glob1" type="string" />
                        <var name="glob2" type="array{str:string}" />
                        <var name="glob3" type="ns\Clazz" />
                        <var name="glob4" type="string|null" />
                        <var name="_GET" type="array{str:string}" />
                    </globals>
                </psalm>'
            )
        );

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                namespace {
                    ord($glob1);
                    ord($glob2["str"]);
                    $glob3->func();
                    ord($_GET["str"]);

                    assert($glob4 !== null);
                    ord($glob4);

                    function example1(): void {
                        global $glob1, $glob2, $glob3, $glob4;
                        ord($glob1);
                        ord($glob2["str"]);
                        $glob3->func();
                        ord($glob4);
                        ord($_GET["str"]);
                    }

                    $glob1 = 0;
                    error_reporting($glob1);

                    $_GET["str"] = 0;
                    error_reporting($_GET["str"]);

                    function example2(): void {
                        global $glob1, $glob2, $glob3;
                        error_reporting($glob1);
                        ord($glob2["str"]);
                        $glob3->func();
                        ord($_GET["str"]);
                    }
                }
                namespace ns {
                    ord($glob1);
                    ord($glob2["str"]);
                    $glob3->func();
                    ord($_GET["str"]);

                    class Clazz {
                        public function func(): void {}
                    }

                    function example3(): void {
                        global $glob1, $glob2, $glob3;
                        ord($glob1);
                        ord($glob2["str"]);
                        $glob3->func();
                        ord($_GET["str"]);
                    }
                }
                namespace ns2 {
                    /** @psalm-suppress InvalidGlobal */
                    global $glob1, $glob2, $glob3;
                    ord($glob1);
                    ord($glob2["str"]);
                    $glob3->func();
                }
                namespace {
                    ord($glob1 ?? "str");
                    ord($_GET["str"] ?? "str");

                    function example4(): void {
                        global $glob1;
                        ord($glob1 ?? "str");
                        ord($_GET["str"] ?? "str");
                    }
                }'
        );

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @return void
     */
    public function testIgnoreExceptions()
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2),
                '<?xml version="1.0"?>
                <psalm checkForThrowsDocblock="true" checkForThrowsInGlobalScope="true">
                    <ignoreExceptions>
                        <class name="Exc1" />
                        <class name="Exc2" onlyGlobalScope="true" />
                        <classAndDescendants name="Exc3" />
                        <classAndDescendants name="Exc4" onlyGlobalScope="true" />
                    </ignoreExceptions>
                </psalm>'
            )
        );

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                class Exc1 extends Exception {}
                class Exc2 extends Exception {}
                class Exc3 extends Exception {}
                class Exc4 extends Exception {}

                throw new Exc1();
                throw new Exc2();
                throw new Exc3();
                throw new Exc4();

                function example() : void {
                    throw new Exc1();
                    throw new Exc3();
                }'
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testNotIgnoredException() : void
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('MissingThrowsDocblock');

        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2),
                '<?xml version="1.0"?>
                <psalm checkForThrowsDocblock="true" checkForThrowsInGlobalScope="true">
                    <ignoreExceptions>
                        <class name="Exc1" />
                        <class name="Exc2" onlyGlobalScope="true" />
                        <classAndDescendants name="Exc3" />
                        <classAndDescendants name="Exc4" onlyGlobalScope="true" />
                    </ignoreExceptions>
                </psalm>'
            )
        );

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                class Exc2 extends Exception {}

                function example() : void {
                    throw new Exc2();
                }'
        );

        $this->analyzeFile($file_path, new Context());
    }
}
