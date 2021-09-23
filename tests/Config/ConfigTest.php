<?php
namespace Psalm\Tests\Config;

use Psalm\Config;
use Psalm\Context;
use Psalm\Exception\ConfigException;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\Provider\FakeFileProvider;
use Psalm\Internal\RuntimeCaches;
use Psalm\Internal\Scanner\FileScanner;
use Psalm\Tests\Config\Plugin\FileTypeSelfRegisteringPlugin;
use Psalm\Tests\Internal\Provider;
use Psalm\Tests\TestConfig;

use function array_map;
use function define;
use function defined;
use function dirname;
use function error_get_last;
use function get_class;
use function getcwd;
use function implode;
use function is_array;
use function preg_match;
use function realpath;
use function sprintf;
use function symlink;
use function uniqid;
use function unlink;

use const DIRECTORY_SEPARATOR;

class ConfigTest extends \Psalm\Tests\TestCase
{
    /** @var TestConfig */
    protected static $config;

    /** @var \Psalm\Internal\Analyzer\ProjectAnalyzer */
    protected $project_analyzer;

    public static function setUpBeforeClass() : void
    {
        self::$config = new TestConfig();

        if (!defined('PSALM_VERSION')) {
            define('PSALM_VERSION', '4.0.0');
        }

        if (!defined('PHP_PARSER_VERSION')) {
            define('PHP_PARSER_VERSION', '4.0.0');
        }
    }

    public function setUp() : void
    {
        RuntimeCaches::clearAll();
        $this->file_provider = new FakeFileProvider();
    }

    private function getProjectAnalyzerWithConfig(Config $config): \Psalm\Internal\Analyzer\ProjectAnalyzer
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

    public function testBarebonesConfig(): void
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

    public function testIgnoreProjectDirectory(): void
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

    public function testIgnoreMissingProjectDirectory(): void
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

    public function testIgnoreWildcardProjectDirectory(): void
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

    public function testIgnoreWildcardFiles(): void
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

    public function testIgnoreWildcardFilesInWildcardFolder(): void
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
        $this->assertTrue($config->isInProjectDirs(realpath('src/Psalm/Internal/PhpVisitor/ReflectorVisitor.php')));
        $this->assertFalse($config->isInProjectDirs(realpath('src/Psalm/Internal/Analyzer/FileAnalyzer.php')));
        $this->assertFalse($config->isInProjectDirs(realpath('src/Psalm/Internal/Analyzer/Statements/ReturnAnalyzer.php')));
        $this->assertTrue($config->isInProjectDirs(realpath('examples/plugins/StringChecker.php')));
    }

    public function testIgnoreWildcardFilesInAllPossibleWildcardFolders(): void
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
        $this->assertTrue($config->isInProjectDirs(realpath('src/Psalm/Internal/PhpVisitor/ReflectorVisitor.php')));
        $this->assertFalse($config->isInProjectDirs(realpath('src/Psalm/Internal/Analyzer/FileAnalyzer.php')));
        $this->assertFalse($config->isInProjectDirs(realpath('src/Psalm/Internal/Analyzer/Statements/ReturnAnalyzer.php')));
        $this->assertFalse($config->isInProjectDirs(realpath('examples/StringAnalyzer.php')));
    }

    public function testIssueHandler(): void
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

    public function testIssueHandlerWithCustomErrorLevels(): void
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
                        <UndefinedGlobalVariable>
                            <errorLevel type="suppress">
                                <referencedVariable name="a" />
                            </errorLevel>
                        </UndefinedGlobalVariable>
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

        $this->assertNull(
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

        $this->assertNull(
            $config->getReportingLevelForProperty(
                'UndefinedMethod',
                'Psalm\Bodger::$find3'
            )
        );

        $this->assertNull(
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

        $this->assertSame(
            'suppress',
            $config->getReportingLevelForVariable(
                'UndefinedGlobalVariable',
                'a'
            )
        );

        $this->assertNull(
            $config->getReportingLevelForVariable(
                'UndefinedGlobalVariable',
                'b'
            )
        );
    }

    public function testIssueHandlerSetDynamically(): void
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
                </psalm>'
            )
        );

        $config = $this->project_analyzer->getConfig();
        $config->setAdvancedErrorLevel('MissingReturnType', [
            [
                'type' => 'suppress',
                'directory' => [['name' => 'tests']]
            ],
            [
                'type' => 'error',
                'directory' => [['name' => 'src/Psalm/Internal/Analyzer']]
            ]
        ], 'info');
        $config->setAdvancedErrorLevel('UndefinedClass', [
            [
                'type' => 'suppress',
                'referencedClass' => [
                    ['name' => 'Psalm\Badger'],
                    ['name' => 'Psalm\*Actor'],
                    ['name' => '*MagicFactory'],
                ]
            ]
        ]);
        $config->setAdvancedErrorLevel('UndefinedMethod', [
            [
                'type' => 'suppress',
                'referencedMethod' => [
                    ['name' => 'Psalm\Bodger::find1'],
                    ['name' => '*::find2'],
                ]
            ]
        ]);
        $config->setAdvancedErrorLevel('UndefinedFunction', [
            [
                'type' => 'suppress',
                'referencedFunction' => [
                    ['name' => 'fooBar']
                ]
            ]
        ]);
        $config->setAdvancedErrorLevel('PossiblyInvalidArgument', [
            [
                'type' => 'suppress',
                'directory' => [
                    ['name' => 'tests'],
                ]
            ],
            [
                'type' => 'info',
                'directory' => [
                    ['name' => 'examples'],
                ]
            ]
        ]);
        $config->setAdvancedErrorLevel('UndefinedPropertyFetch', [
            [
                'type' => 'suppress',
                'referencedProperty' => [
                    ['name' => 'Psalm\Bodger::$find3']
                ]
            ]
        ]);
        $config->setAdvancedErrorLevel('UndefinedGlobalVariable', [
            [
                'type' => 'suppress',
                'referencedVariable' => [
                    ['name' => 'a']
                ]
            ]
        ]);

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

        $this->assertNull(
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

        $this->assertNull(
            $config->getReportingLevelForProperty(
                'UndefinedMethod',
                'Psalm\Bodger::$find3'
            )
        );

        $this->assertNull(
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

        $this->assertSame(
            'suppress',
            $config->getReportingLevelForVariable(
                'UndefinedGlobalVariable',
                'a'
            )
        );

        $this->assertNull(
            $config->getReportingLevelForVariable(
                'UndefinedGlobalVariable',
                'b'
            )
        );
    }

    public function testAllPossibleIssues(): void
    {
        $all_possible_handlers = implode(
            ' ',
            array_map(
                /**
                 * @param string $issue_name
                 *
                 * @return string
                 */
                function ($issue_name): string {
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

    public function testImpossibleIssue(): void
    {
        $this->expectExceptionMessage('This element is not expected');
        $this->expectException(ConfigException::class);
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

    public function testThing(): void
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

    public function testExitFunctions(): void
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

    public function testAllowedEchoFunction(): void
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

    public function testForbiddenEchoFunctionViaFunctions(): void
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

    public function testForbiddenEchoFunctionViaFlag(): void
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

    public function testAllowedPrintFunction(): void
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
                print "hello";'
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testForbiddenPrintFunction(): void
    {
        $this->expectExceptionMessage('ForbiddenCode');
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2),
                '<?xml version="1.0"?>
                <psalm>
                    <forbiddenFunctions>
                        <function name="print" />
                    </forbiddenFunctions>
                </psalm>'
            )
        );

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                print "hello";'
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testAllowedVarExportFunction(): void
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

    public function testForbiddenVarExportFunction(): void
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

    public function testForbiddenEmptyFunction(): void
    {
        $this->expectExceptionMessage('ForbiddenCode');
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2),
                '<?xml version="1.0"?>
                <psalm>
                    <forbiddenFunctions>
                        <function name="empty" />
                    </forbiddenFunctions>
                </psalm>'
            )
        );

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                empty(false);'
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testValidThrowInvalidCatch(): void
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

    public function testInvalidThrowValidCatch(): void
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

    public function testValidThrowValidCatch(): void
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

    public function testModularConfig(): void
    {
        $root = __DIR__ . '/../fixtures/ModularConfig';
        $config = Config::loadFromXMLFile($root . '/psalm.xml', $root);
        $this->assertEquals(
            [
                realpath($root . '/Bar.php'),
                realpath($root . '/Bat.php')
            ],
            $config->getProjectFiles()
        );
    }

    public function tearDown(): void
    {
        parent::tearDown();

        if ($this->getName() === 'testTemplatedFiles') {
            $project_root = dirname(__DIR__, 2);
            foreach (['1.xml', '2.xml', '3.xml', '4.xml', '5.xml', '6.xml', '7.xml', '8.xml'] as $file_name) {
                @unlink($project_root . DIRECTORY_SEPARATOR . $file_name);
            }
        }
    }

    public function testGlobals(): void
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
                    ord($glob1 ?: "str");
                    ord($_GET["str"] ?? "str");

                    function example4(): void {
                        global $glob1;
                        ord($glob1 ?: "str");
                        ord($_GET["str"] ?? "str");
                    }
                }'
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testIgnoreExceptions(): void
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
                        <classAndDescendants name="Exc5" />
                    </ignoreExceptions>
                </psalm>'
            )
        );

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                class Exc1 extends Exception {}
                /** @throws Exc1 */
                function throwsExc1(): void {}

                class Exc2 extends Exception {}
                /** @throws Exc2 */
                function throwsExc2(): void {}

                class Exc3 extends Exception {}
                /** @throws Exc3 */
                function throwsExc3(): void {}

                class Exc4 extends Exception {}
                /** @throws Exc4 */
                function throwsExc4(): void {}

                interface Exc5 {}
                interface Exc6 extends Exc5 {}
                /**
                 * @psalm-suppress InvalidThrow
                 * @throws Exc6
                 */
                function throwsExc6() : void {}

                throwsExc1();
                throwsExc2();
                throwsExc3();
                throwsExc4();
                throwsExc6();

                function example() : void {
                    throwsExc6();
                    throwsExc1();
                    throwsExc3();
                    throwsExc6();
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

    public function testGetPossiblePsr4Path(): void
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
                </psalm>'
            )
        );

        $config = $this->project_analyzer->getConfig();

        $classloader = new \Composer\Autoload\ClassLoader();
        $classloader->addPsr4(
            'Psalm\\',
            [
                dirname(__DIR__, 2)
                    . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR
                    . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR
                    . 'src' . DIRECTORY_SEPARATOR . 'Psalm',
            ]
        );

        $classloader->addPsr4(
            'Psalm\\Tests\\',
            [
                dirname(__DIR__, 2)
                    . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR
                    . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR
                    . 'tests',
            ]
        );

        $config->setComposerClassLoader($classloader);

        $this->assertSame(
            dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Psalm' . DIRECTORY_SEPARATOR . 'Foo.php',
            $config->getPotentialComposerFilePathForClassLike('Psalm\\Foo')
        );

        $this->assertSame(
            dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'Foo.php',
            $config->getPotentialComposerFilePathForClassLike('Psalm\\Tests\\Foo')
        );
    }

    public function testTakesPhpVersionFromConfigFile(): void
    {
        $cfg = Config::loadFromXML(
            dirname(__DIR__, 2),
            '<?xml version="1.0"?><psalm phpVersion="7.1"></psalm>'
        );
        $this->assertSame('7.1', $cfg->getPhpVersion());
    }

    public function testReadsComposerJsonForPhpVersion(): void
    {

        $root = __DIR__ . '/../fixtures/ComposerPhpVersion';
        $cfg = Config::loadFromXML($root, "<?xml version=\"1.0\"?><psalm></psalm>");
        $this->assertSame('7.2', $cfg->getPhpVersion());

        $cfg = Config::loadFromXML($root, "<?xml version=\"1.0\"?><psalm phpVersion='8.0'></psalm>");
        $this->assertSame('8.0', $cfg->getPhpVersion());
    }

    public function testSetsUsePhpStormMetaPath(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2),
                '<?xml version="1.0"?>
                <psalm usePhpStormMetaPath="false">
                </psalm>'
            )
        );

        $this->assertFalse($this->project_analyzer->getConfig()->use_phpstorm_meta_path);
    }

    /** @return void */
    public function testSetsUniversalObjectCrates()
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2),
                '<?xml version="1.0"?>
                <psalm>
                    <universalObjectCrates>
                        <class name="DateTime" />
                    </universalObjectCrates>
                </psalm>'
            )
        );

        $this->assertContains('datetime', $this->project_analyzer->getConfig()->getUniversalObjectCrates());
    }

    public function testInferPropertyTypesFromConstructorIsRead(): void
    {
        $cfg = Config::loadFromXML(
            dirname(__DIR__, 2),
            '<?xml version="1.0"?><psalm inferPropertyTypesFromConstructor="false"></psalm>'
        );
        $this->assertFalse($cfg->infer_property_types_from_constructor);
    }

    /**
     * @return array<string, array{0: int, 1: int|null}>
     */
    public function pluginRegistersScannerAndAnalyzerDataProvider(): array
    {
        return [
            'regular' => [0, null], // flags, expected exception code
            'invalid scanner class' => [FileTypeSelfRegisteringPlugin::FLAG_SCANNER_INVALID, 1622727271],
            'invalid analyzer class' => [FileTypeSelfRegisteringPlugin::FLAG_ANALYZER_INVALID, 1622727281],
            'override scanner' => [FileTypeSelfRegisteringPlugin::FLAG_SCANNER_TWICE, 1622727272],
            'override analyzer' => [FileTypeSelfRegisteringPlugin::FLAG_ANALYZER_TWICE, 1622727282],
        ];
    }

    /**
     * @test
     * @dataProvider pluginRegistersScannerAndAnalyzerDataProvider
     */
    public function pluginRegistersScannerAndAnalyzer(int $flags, ?int $expectedExceptionCode): void
    {
        $extension = uniqid('test');
        $names = [
            'scanner' => uniqid('PsalmTestFileTypeScanner'),
            'analyzer' => uniqid('PsalmTestFileTypeAnaylzer'),
            'extension' => $extension,
        ];
        $scannerMock = $this->getMockBuilder(FileScanner::class)
            ->setMockClassName($names['scanner'])
            ->disableOriginalConstructor()
            ->getMock();
        $analyzerMock = $this->getMockBuilder(FileAnalyzer::class)
            ->setMockClassName($names['analyzer'])
            ->disableOriginalConstructor()
            ->getMock();

        FileTypeSelfRegisteringPlugin::$names = $names;
        FileTypeSelfRegisteringPlugin::$flags = $flags;

        $projectAnalyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2),
                sprintf(
                    '<?xml version="1.0"?>
                    <psalm><plugins><pluginClass class="%s"/></plugins></psalm>',
                    FileTypeSelfRegisteringPlugin::class
                )
            )
        );


        try {
            $config = $projectAnalyzer->getConfig();
            $config->initializePlugins($projectAnalyzer);
        } catch (ConfigException $exception) {
            $actualExceptionCode = $exception->getPrevious()
                ? $exception->getPrevious()->getCode()
                : null;
            self::assertSame(
                $expectedExceptionCode,
                $actualExceptionCode,
                'Exception code did not match.'
            );
            return;
        }

        self::assertContains($extension, $config->getFileExtensions());
        self::assertSame(get_class($scannerMock), $config->getFiletypeScanners()[$extension] ?? null);
        self::assertSame(get_class($analyzerMock), $config->getFiletypeAnalyzers()[$extension] ?? null);
        self::assertNull($expectedExceptionCode, 'Expected exception code was not thrown');
    }
}
