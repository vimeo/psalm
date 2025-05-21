<?php

declare(strict_types=1);

namespace Psalm\Tests\Config;

use Composer\Autoload\ClassLoader;
use ErrorException;
use Override;
use Psalm\CodeLocation\Raw;
use Psalm\Config;
use Psalm\Config\IssueHandler;
use Psalm\Context;
use Psalm\Exception\CodeException;
use Psalm\Exception\ConfigException;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\ErrorHandler;
use Psalm\Internal\Provider\FakeFileProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Internal\RuntimeCaches;
use Psalm\Internal\Scanner\FileScanner;
use Psalm\Issue\TooManyArguments;
use Psalm\Issue\UndefinedFunction;
use Psalm\Tests\Config\Plugin\FileTypeSelfRegisteringPlugin;
use Psalm\Tests\Internal\Provider\FakeParserCacheProvider;
use Psalm\Tests\TestCase;
use Psalm\Tests\TestConfig;

use function array_map;
use function dirname;
use function error_get_last;
use function get_class;
use function getcwd;
use function implode;
use function in_array;
use function is_array;
use function preg_match;
use function realpath;
use function set_error_handler;
use function sprintf;
use function symlink;
use function uniqid;
use function unlink;

use const DIRECTORY_SEPARATOR;

final class ConfigTest extends TestCase
{
    protected static TestConfig $config;

    protected ProjectAnalyzer $project_analyzer;

    /** @var callable(int, string, string=, int=, array=):bool|null */
    protected $original_error_handler = null;

    #[Override]
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // hack to isolate Psalm from PHPUnit cli arguments
        global $argv;
        $argv = [];

        self::$config = new TestConfig();
    }

    #[Override]
    public function setUp(): void
    {
        RuntimeCaches::clearAll();
        $this->file_provider = new FakeFileProvider();
        $this->original_error_handler = set_error_handler(null);
        set_error_handler($this->original_error_handler);
    }

    private function getProjectAnalyzerWithConfig(Config $config): ProjectAnalyzer
    {
        $p = new ProjectAnalyzer(
            $config,
            new Providers(
                $this->file_provider,
                new FakeParserCacheProvider(),
            ),
        );

        $p->setPhpVersion('7.3', 'tests');

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
                </psalm>',
            ),
        );

        $config = $this->project_analyzer->getConfig();

        $this->assertTrue($config->isInProjectDirs((string) realpath('src/Psalm/Type.php')));
        $this->assertFalse($config->isInProjectDirs((string) realpath('examples/TemplateScanner.php')));
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
                </psalm>',
            ),
        );

        $config = $this->project_analyzer->getConfig();

        $this->assertTrue($config->isInProjectDirs((string) realpath('src/Psalm/Type.php')));
        $this->assertFalse($config->isInProjectDirs((string) realpath('src/Psalm/Internal/Analyzer/FileAnalyzer.php')));
        $this->assertFalse($config->isInProjectDirs((string) realpath('examples/TemplateScanner.php')));
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
                </psalm>',
            ),
        );

        $config = $this->project_analyzer->getConfig();

        $this->assertTrue($config->isInProjectDirs((string) realpath('src/Psalm/Type.php')));
        $this->assertFalse($config->isInProjectDirs((string) realpath(__DIR__ . '/../../') . '/does/not/exist/FileAnalyzer.php'));
        $this->assertFalse($config->isInProjectDirs((string) realpath('examples/TemplateScanner.php')));
    }

    /**
     * @requires OS ^(?!WIN)
     */
    public function testIgnoreSymlinkedProjectDirectory(): void
    {
        @unlink(dirname(__DIR__, 1) . '/fixtures/symlinktest/ignored/b');

        $no_symlinking_error = [
            'symlink(): Cannot create symlink, error code(1314)',
            'symlink(): Permission denied',
        ];
        $last_error = error_get_last();
        $check_symlink_error =
            !is_array($last_error) ||
            !isset($last_error['message']) ||
            !in_array($last_error['message'], $no_symlinking_error);

        @symlink(dirname(__DIR__, 1) . '/fixtures/symlinktest/a', dirname(__DIR__, 1) . '/fixtures/symlinktest/ignored/b');

        if ($check_symlink_error) {
            $last_error = error_get_last();

            if (is_array($last_error) && in_array($last_error['message'], $no_symlinking_error)) {
                $this->markTestSkipped($last_error['message']);
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
                            <directory name="tests/fixtures/symlinktest/ignored" resolveSymlinks="true" />
                        </ignoreFiles>
                    </projectFiles>
                </psalm>',
            ),
        );

        $config = $this->project_analyzer->getConfig();

        $this->assertTrue($config->isInProjectDirs((string) realpath('tests/AnnotationTest.php')));
        $this->assertFalse($config->isInProjectDirs((string) realpath('tests/fixtures/symlinktest/a/ignoreme.php')));
        $this->assertFalse($config->isInProjectDirs((string) realpath('examples/TemplateScanner.php')));

        $regex = '/^unlink\([^\)]+\): (?:Permission denied|No such file or directory)$/';
        $last_error = error_get_last();

        $check_unlink_error =
            !is_array($last_error) ||
            !preg_match($regex, $last_error['message']);

        @unlink(__DIR__ . '/fixtures/symlinktest/ignored/b');

        if ($check_unlink_error) {
            $last_error = error_get_last();

            if (is_array($last_error) && !preg_match($regex, $last_error['message'])) {
                throw new ErrorException(
                    $last_error['message'],
                    0,
                    $last_error['type'],
                    $last_error['file'],
                    $last_error['line'],
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
                            <directory name="src/*/Internal/Analyzer" />
                        </ignoreFiles>
                    </projectFiles>
                </psalm>',
            ),
        );

        $config = $this->project_analyzer->getConfig();

        $this->assertTrue($config->isInProjectDirs((string) realpath('src/Psalm/Type.php')));
        $this->assertFalse($config->isInProjectDirs((string) realpath('src/Psalm/Internal/Analyzer/FileAnalyzer.php')));
        $this->assertFalse($config->isInProjectDirs((string) realpath('src/Psalm/Internal/Analyzer/Statements/ReturnAnalyzer.php')));
        $this->assertFalse($config->isInProjectDirs((string) realpath('examples/TemplateScanner.php')));
    }

    public function testIgnoreRecursiveWildcardProjectDirectory(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            Config::loadFromXML(
                dirname(__DIR__, 2),
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="src" />
                        <ignoreFiles>
                            <directory name="src/**/BinaryOp*" />
                        </ignoreFiles>
                    </projectFiles>
                </psalm>',
            ),
        );

        $config = $this->project_analyzer->getConfig();

        $this->assertTrue($config->isInProjectDirs((string) realpath('src/Psalm/Internal/Analyzer/Statements/Expression/BinaryOpAnalyzer.php')));
        $this->assertFalse($config->isInProjectDirs((string) realpath('src/Psalm/Internal/Analyzer/Statements/Expression/BinaryOp/OrAnalyzer.php')));
        $this->assertFalse($config->isInProjectDirs((string) realpath('src/Psalm/Node/Expr/BinaryOp/VirtualPlus.php')));
    }

    public function testIgnoreRecursiveDoubleWildcardProjectFiles(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            Config::loadFromXML(
                dirname(__DIR__, 2),
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="src" />
                        <ignoreFiles>
                            <file name="src/**/*Analyzer.php" />
                        </ignoreFiles>
                    </projectFiles>
                </psalm>',
            ),
        );

        $config = $this->project_analyzer->getConfig();

        $this->assertTrue($config->isInProjectDirs((string) realpath('src/Psalm/Type.php')));
        $this->assertFalse($config->isInProjectDirs((string) realpath('src/Psalm/Internal/Analyzer/FileAnalyzer.php')));
        $this->assertFalse($config->isInProjectDirs((string) realpath('src/Psalm/Internal/Analyzer/Statements/ReturnAnalyzer.php')));
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
                </psalm>',
            ),
        );

        $config = $this->project_analyzer->getConfig();

        $this->assertTrue($config->isInProjectDirs((string) realpath('src/Psalm/Type.php')));
        $this->assertFalse($config->isInProjectDirs((string) realpath('src/Psalm/Internal/Analyzer/FileAnalyzer.php')));
        $this->assertTrue($config->isInProjectDirs((string) realpath('src/Psalm/Internal/Analyzer/Statements/ReturnAnalyzer.php')));
        $this->assertFalse($config->isInProjectDirs((string) realpath('examples/TemplateScanner.php')));
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
                </psalm>',
            ),
        );

        $config = $this->project_analyzer->getConfig();

        $this->assertTrue($config->isInProjectDirs((string) realpath('src/Psalm/Type.php')));
        $this->assertTrue($config->isInProjectDirs((string) realpath('src/Psalm/Internal/PhpVisitor/ReflectorVisitor.php')));
        $this->assertFalse($config->isInProjectDirs((string) realpath('src/Psalm/Internal/Analyzer/FileAnalyzer.php')));
        $this->assertFalse($config->isInProjectDirs((string) realpath('src/Psalm/Internal/Analyzer/Statements/ReturnAnalyzer.php')));
        $this->assertTrue($config->isInProjectDirs((string) realpath('examples/plugins/StringChecker.php')));
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
                </psalm>',
            ),
        );

        $config = $this->project_analyzer->getConfig();

        $this->assertTrue($config->isInProjectDirs((string) realpath('src/Psalm/Type.php')));
        $this->assertTrue($config->isInProjectDirs((string) realpath('src/Psalm/Internal/PhpVisitor/ReflectorVisitor.php')));
        $this->assertFalse($config->isInProjectDirs((string) realpath('src/Psalm/Internal/Analyzer/FileAnalyzer.php')));
        $this->assertFalse($config->isInProjectDirs((string) realpath('src/Psalm/Internal/Analyzer/Statements/ReturnAnalyzer.php')));
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
                </psalm>',
            ),
        );

        $config = $this->project_analyzer->getConfig();

        $this->assertFalse($config->reportIssueInFile('MissingReturnType', (string) realpath(__FILE__)));
        $this->assertFalse($config->reportIssueInFile('MissingReturnType', (string) realpath('src/Psalm/Type.php')));
    }

    public function testReportMixedIssues(): void
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
                </psalm>',
            ),
        );
        $config = $this->project_analyzer->getConfig();

        $this->assertNull($config->show_mixed_issues);
        $this->assertTrue($config->reportIssueInFile('MixedArgument', (string) realpath(__FILE__)));

        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            Config::loadFromXML(
                dirname(__DIR__, 2),
                '<?xml version="1.0"?>
                <psalm reportMixedIssues="false">
                    <projectFiles>
                        <directory name="src" />
                        <directory name="tests" />
                    </projectFiles>
                </psalm>',
            ),
        );
        $config = $this->project_analyzer->getConfig();

        $this->assertFalse($config->show_mixed_issues);
        $this->assertFalse($config->reportIssueInFile('MixedArgument', (string) realpath(__FILE__)));

        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            Config::loadFromXML(
                dirname(__DIR__, 2),
                '<?xml version="1.0"?>
                <psalm errorLevel="5">
                    <projectFiles>
                        <directory name="src" />
                        <directory name="tests" />
                    </projectFiles>
                </psalm>',
            ),
        );
        $config = $this->project_analyzer->getConfig();

        $this->assertNull($config->show_mixed_issues);
        $this->assertFalse($config->reportIssueInFile('MixedArgument', (string) realpath(__FILE__)));

        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            Config::loadFromXML(
                dirname(__DIR__, 2),
                '<?xml version="1.0"?>
                <psalm errorLevel="5" reportMixedIssues="true">
                    <projectFiles>
                        <directory name="src" />
                        <directory name="tests" />
                    </projectFiles>
                </psalm>',
            ),
        );
        $config = $this->project_analyzer->getConfig();

        $this->assertTrue($config->show_mixed_issues);
        $this->assertTrue($config->reportIssueInFile('MixedArgument', (string) realpath(__FILE__)));
    }

    public function testGlobalUndefinedFunctionSuppression(): void
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
                        <UndefinedFunction>
                            <errorLevel type="suppress">
                                <referencedFunction name="zzz"/>
                            </errorLevel>
                        </UndefinedFunction>
                    </issueHandlers>
                </psalm>',
            ),
        );

        $config = $this->project_analyzer->getConfig();
        $this->assertSame(
            Config::REPORT_SUPPRESS,
            $config->getReportingLevelForFunction('UndefinedFunction', 'Some\Namespace\zzz'),
        );
    }

    public function testMultipleIssueHandlers(): void
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
                    <issueHandlers>
                        <UndefinedClass errorLevel="suppress" />
                    </issueHandlers>
                </psalm>',
            ),
        );

        $config = $this->project_analyzer->getConfig();

        $this->assertFalse($config->reportIssueInFile('MissingReturnType', (string) realpath(__FILE__)));
        $this->assertFalse($config->reportIssueInFile('UndefinedClass', (string) realpath(__FILE__)));
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
                        <InvalidConstantAssignmentValue>
                            <errorLevel type="suppress">
                                <referencedConstant name="Psalm\Bodger::FOO" />
                            </errorLevel>
                        </InvalidConstantAssignmentValue>
                    </issueHandlers>
                </psalm>',
            ),
        );

        $config = $this->project_analyzer->getConfig();

        $this->assertSame(
            'info',
            $config->getReportingLevelForFile(
                'MissingReturnType',
                (string) realpath('src/Psalm/Type.php'),
            ),
        );

        $this->assertSame(
            'error',
            $config->getReportingLevelForFile(
                'MissingReturnType',
                (string) realpath('src/Psalm/Internal/Analyzer/FileAnalyzer.php'),
            ),
        );

        $this->assertSame(
            'error',
            $config->getReportingLevelForFile(
                'PossiblyInvalidArgument',
                (string) realpath('src/psalm.php'),
            ),
        );

        $this->assertSame(
            'info',
            $config->getReportingLevelForFile(
                'PossiblyInvalidArgument',
                (string) realpath('examples/TemplateChecker.php'),
            ),
        );

        $this->assertSame(
            'suppress',
            $config->getReportingLevelForClass(
                'UndefinedClass',
                'Psalm\Badger',
            ),
        );

        $this->assertSame(
            'suppress',
            $config->getReportingLevelForClass(
                'UndefinedClass',
                'Psalm\BadActor',
            ),
        );

        $this->assertSame(
            'suppress',
            $config->getReportingLevelForClass(
                'UndefinedClass',
                'Psalm\GoodActor',
            ),
        );

        $this->assertSame(
            'suppress',
            $config->getReportingLevelForClass(
                'UndefinedClass',
                'Psalm\MagicFactory',
            ),
        );

        $this->assertNull(
            $config->getReportingLevelForClass(
                'UndefinedClass',
                'Psalm\Bodger',
            ),
        );

        $this->assertSame(
            'suppress',
            $config->getReportingLevelForMethod(
                'UndefinedMethod',
                'Psalm\Bodger::find1',
            ),
        );

        $this->assertSame(
            'suppress',
            $config->getReportingLevelForMethod(
                'UndefinedMethod',
                'Psalm\Bodger::find2',
            ),
        );

        $this->assertSame(
            'suppress',
            $config->getReportingLevelForMethod(
                'UndefinedMethod',
                'Psalm\Badger::find2',
            ),
        );

        $this->assertNull(
            $config->getReportingLevelForProperty(
                'UndefinedMethod',
                'Psalm\Bodger::$find3',
            ),
        );

        $this->assertNull(
            $config->getReportingLevelForProperty(
                'UndefinedMethod',
                'Psalm\Bodger::$find4',
            ),
        );

        $this->assertSame(
            'suppress',
            $config->getReportingLevelForMethod(
                'UndefinedFunction',
                'fooBar',
            ),
        );

        $this->assertSame(
            'suppress',
            $config->getReportingLevelForMethod(
                'UndefinedFunction',
                'foobar',
            ),
        );

        $this->assertSame(
            'suppress',
            $config->getReportingLevelForVariable(
                'UndefinedGlobalVariable',
                'a',
            ),
        );

        $this->assertNull(
            $config->getReportingLevelForVariable(
                'UndefinedGlobalVariable',
                'b',
            ),
        );

        $this->assertSame(
            'suppress',
            $config->getReportingLevelForClassConstant(
                'InvalidConstantAssignmentValue',
                'Psalm\Bodger::FOO',
            ),
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
                </psalm>',
            ),
        );

        $config = $this->project_analyzer->getConfig();
        $config->setAdvancedErrorLevel('MissingReturnType', [
            [
                'type' => 'suppress',
                'directory' => [['name' => 'tests']],
            ],
            [
                'type' => 'error',
                'directory' => [['name' => 'src/Psalm/Internal/Analyzer']],
            ],
        ], 'info');
        $config->setAdvancedErrorLevel('UndefinedClass', [
            [
                'type' => 'suppress',
                'referencedClass' => [
                    ['name' => 'Psalm\Badger'],
                    ['name' => 'Psalm\*Actor'],
                    ['name' => '*MagicFactory'],
                ],
            ],
        ]);
        $config->setAdvancedErrorLevel('UndefinedMethod', [
            [
                'type' => 'suppress',
                'referencedMethod' => [
                    ['name' => 'Psalm\Bodger::find1'],
                    ['name' => '*::find2'],
                ],
            ],
        ]);
        $config->setAdvancedErrorLevel('UndefinedFunction', [
            [
                'type' => 'suppress',
                'referencedFunction' => [
                    ['name' => 'fooBar'],
                ],
            ],
        ]);
        $config->setAdvancedErrorLevel('PossiblyInvalidArgument', [
            [
                'type' => 'suppress',
                'directory' => [
                    ['name' => 'tests'],
                ],
            ],
            [
                'type' => 'info',
                'directory' => [
                    ['name' => 'examples'],
                ],
            ],
        ]);
        $config->setAdvancedErrorLevel('UndefinedPropertyFetch', [
            [
                'type' => 'suppress',
                'referencedProperty' => [
                    ['name' => 'Psalm\Bodger::$find3'],
                ],
            ],
        ]);
        $config->setAdvancedErrorLevel('UndefinedGlobalVariable', [
            [
                'type' => 'suppress',
                'referencedVariable' => [
                    ['name' => 'a'],
                ],
            ],
        ]);

        $this->assertSame(
            'info',
            $config->getReportingLevelForFile(
                'MissingReturnType',
                (string) realpath('src/Psalm/Type.php'),
            ),
        );

        $this->assertSame(
            'error',
            $config->getReportingLevelForFile(
                'MissingReturnType',
                (string) realpath('src/Psalm/Internal/Analyzer/FileAnalyzer.php'),
            ),
        );

        $this->assertSame(
            'error',
            $config->getReportingLevelForFile(
                'PossiblyInvalidArgument',
                (string) realpath('src/psalm.php'),
            ),
        );

        $this->assertSame(
            'info',
            $config->getReportingLevelForFile(
                'PossiblyInvalidArgument',
                (string) realpath('examples/TemplateChecker.php'),
            ),
        );

        $this->assertSame(
            'suppress',
            $config->getReportingLevelForClass(
                'UndefinedClass',
                'Psalm\Badger',
            ),
        );

        $this->assertSame(
            'suppress',
            $config->getReportingLevelForClass(
                'UndefinedClass',
                'Psalm\BadActor',
            ),
        );

        $this->assertSame(
            'suppress',
            $config->getReportingLevelForClass(
                'UndefinedClass',
                'Psalm\GoodActor',
            ),
        );

        $this->assertSame(
            'suppress',
            $config->getReportingLevelForClass(
                'UndefinedClass',
                'Psalm\MagicFactory',
            ),
        );

        $this->assertNull(
            $config->getReportingLevelForClass(
                'UndefinedClass',
                'Psalm\Bodger',
            ),
        );

        $this->assertSame(
            'suppress',
            $config->getReportingLevelForMethod(
                'UndefinedMethod',
                'Psalm\Bodger::find1',
            ),
        );

        $this->assertSame(
            'suppress',
            $config->getReportingLevelForMethod(
                'UndefinedMethod',
                'Psalm\Bodger::find2',
            ),
        );

        $this->assertSame(
            'suppress',
            $config->getReportingLevelForMethod(
                'UndefinedMethod',
                'Psalm\Badger::find2',
            ),
        );

        $this->assertNull(
            $config->getReportingLevelForProperty(
                'UndefinedMethod',
                'Psalm\Bodger::$find3',
            ),
        );

        $this->assertNull(
            $config->getReportingLevelForProperty(
                'UndefinedMethod',
                'Psalm\Bodger::$find4',
            ),
        );

        $this->assertSame(
            'suppress',
            $config->getReportingLevelForMethod(
                'UndefinedFunction',
                'fooBar',
            ),
        );

        $this->assertSame(
            'suppress',
            $config->getReportingLevelForMethod(
                'UndefinedFunction',
                'foobar',
            ),
        );

        $this->assertSame(
            'suppress',
            $config->getReportingLevelForVariable(
                'UndefinedGlobalVariable',
                'a',
            ),
        );

        $this->assertNull(
            $config->getReportingLevelForVariable(
                'UndefinedGlobalVariable',
                'b',
            ),
        );
    }

    public function testIssueHandlerOverride(): void
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
                            <MissingReturnType errorLevel="error">
                                <errorLevel type="info">
                                    <directory name="tests" />
                                </errorLevel>
                                <errorLevel type="info">
                                    <directory name="src/Psalm/Internal/Analyzer" />
                                </errorLevel>
                            </MissingReturnType>
                            <UndefinedClass errorLevel="error"></UndefinedClass>
                    </issueHandlers>
                </psalm>',
            ),
        );

        $config = $this->project_analyzer->getConfig();
        $config->setAdvancedErrorLevel('MissingReturnType', [
            [
                'type' => 'error',
                'directory' => [['name' => 'src/Psalm/Internal/Analyzer']],
            ],
        ], 'info');
        $config->setCustomErrorLevel('UndefinedClass', 'suppress');

        $this->assertSame(
            'info',
            $config->getReportingLevelForFile(
                'MissingReturnType',
                (string) realpath('src/Psalm/Type.php'),
            ),
        );

        $this->assertSame(
            'error',
            $config->getReportingLevelForFile(
                'MissingReturnType',
                (string) realpath('src/Psalm/Internal/Analyzer/FileAnalyzer.php'),
            ),
        );
        $this->assertSame(
            'suppress',
            $config->getReportingLevelForFile(
                'UndefinedClass',
                (string) realpath('src/Psalm/Internal/Analyzer/FileAnalyzer.php'),
            ),
        );
    }

    public function testIssueHandlerSafeOverride(): void
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
                            <MissingReturnType errorLevel="error">
                                <errorLevel type="info">
                                    <directory name="tests" />
                                </errorLevel>
                                <errorLevel type="info">
                                    <directory name="src/Psalm/Internal/Analyzer" />
                                </errorLevel>
                            </MissingReturnType>
                            <UndefinedClass errorLevel="info"></UndefinedClass>
                    </issueHandlers>
                </psalm>',
            ),
        );

        $config = $this->project_analyzer->getConfig();
        $config->safeSetAdvancedErrorLevel('MissingReturnType', [
            [
                'type' => 'error',
                'directory' => [['name' => 'src/Psalm/Internal/Analyzer']],
            ],
        ], 'info');
        $config->safeSetCustomErrorLevel('UndefinedClass', 'suppress');

        $this->assertSame(
            'error',
            $config->getReportingLevelForFile(
                'MissingReturnType',
                (string) realpath('src/Psalm/Type.php'),
            ),
        );

        $this->assertSame(
            'info',
            $config->getReportingLevelForFile(
                'MissingReturnType',
                (string) realpath('src/Psalm/Internal/Analyzer/FileAnalyzer.php'),
            ),
        );
        $this->assertSame(
            'info',
            $config->getReportingLevelForFile(
                'UndefinedClass',
                (string) realpath('src/Psalm/Internal/Analyzer/FileAnalyzer.php'),
            ),
        );
    }

    public function testAllPossibleIssues(): void
    {
        $all_possible_handlers = implode(
            ' ',
            array_map(
                /**
                 * @param string $issue_name
                 * @return string
                 */
                static fn($issue_name): string => '<' . $issue_name . ' errorLevel="suppress" />' . "\n",
                IssueHandler::getAllIssueTypes(),
            ),
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
                </psalm>',
            ),
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
                </psalm>',
            ),
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
                </psalm>',
            ),
        );

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                class MyMockClass {}

                $a = new MyMockClass();
                $a->foo($b = 5);
                echo $b;',
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testValidThrowInvalidCatch(): void
    {
        $this->expectExceptionMessage('InvalidCatch');
        $this->expectException(CodeException::class);
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
                </psalm>',
            ),
        );

        $file_path = (string) getcwd() . '/src/somefile.php';

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
                }',
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testInvalidThrowValidCatch(): void
    {
        $this->expectExceptionMessage('InvalidThrow');
        $this->expectException(CodeException::class);
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
                </psalm>',
            ),
        );

        $file_path = (string) getcwd() . '/src/somefile.php';

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
                }',
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
                </psalm>',
            ),
        );

        $file_path = (string) getcwd() . '/src/somefile.php';

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
                }',
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
                realpath($root . '/Bat.php'),
            ],
            $config->getProjectFiles(),
        );
    }

    #[Override]
    public function tearDown(): void
    {
        set_error_handler($this->original_error_handler);

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
                </psalm>',
            ),
        );

        $file_path = (string) getcwd() . '/src/somefile.php';

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

                    $z = $glob1;
                    $z = 0;
                    error_reporting($z);

                    $old = $_GET["str"];
                    $_GET["str"] = 0;
                    error_reporting($_GET["str"]);
                    $_GET["str"] = $old;

                    function example2(): void {
                        global $z, $glob2, $glob3;
                        error_reporting($z);
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
                }',
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
                </psalm>',
            ),
        );

        $file_path = (string) getcwd() . '/src/somefile.php';

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
                }',
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testNotIgnoredException(): void
    {
        $this->expectException(CodeException::class);
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
                </psalm>',
            ),
        );

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                class Exc2 extends Exception {}

                function example() : void {
                    throw new Exc2();
                }',
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
                </psalm>',
            ),
        );

        $config = $this->project_analyzer->getConfig();

        $classloader = new ClassLoader();
        $classloader->addPsr4(
            'Psalm\\',
            [
                dirname(__DIR__, 2)
                    . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR
                    . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR
                    . 'src' . DIRECTORY_SEPARATOR . 'Psalm',
            ],
        );

        $classloader->addPsr4(
            'Psalm\\Tests\\',
            [
                dirname(__DIR__, 2)
                    . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR
                    . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR
                    . 'tests',
            ],
        );

        $config->setComposerClassLoader([$classloader]);

        $this->assertSame(
            dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Psalm' . DIRECTORY_SEPARATOR . 'Foo.php',
            $config->getPotentialComposerFilePathForClassLike('Psalm\\Foo'),
        );

        $this->assertSame(
            dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'Foo.php',
            $config->getPotentialComposerFilePathForClassLike('Psalm\\Tests\\Foo'),
        );
    }

    public function testTakesPhpVersionFromConfigFile(): void
    {
        $cfg = Config::loadFromXML(
            dirname(__DIR__, 2),
            '<?xml version="1.0"?><psalm phpVersion="7.1"></psalm>',
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
                </psalm>',
            ),
        );

        $this->assertFalse($this->project_analyzer->getConfig()->use_phpstorm_meta_path);
    }

    public function testSetsUniversalObjectCrates(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2),
                '<?xml version="1.0"?>
                <psalm>
                    <universalObjectCrates>
                        <class name="DateTime" />
                    </universalObjectCrates>
                </psalm>',
            ),
        );

        $this->assertContains('datetime', $this->project_analyzer->getConfig()->getUniversalObjectCrates());
    }

    public function testInferPropertyTypesFromConstructorIsRead(): void
    {
        $cfg = Config::loadFromXML(
            dirname(__DIR__, 2),
            '<?xml version="1.0"?><psalm inferPropertyTypesFromConstructor="false"></psalm>',
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
            'invalid scanner class' => [FileTypeSelfRegisteringPlugin::FLAG_SCANNER_INVALID, 1_622_727_271],
            'invalid analyzer class' => [FileTypeSelfRegisteringPlugin::FLAG_ANALYZER_INVALID, 1_622_727_281],
            'override scanner' => [FileTypeSelfRegisteringPlugin::FLAG_SCANNER_TWICE, 1_622_727_272],
            'override analyzer' => [FileTypeSelfRegisteringPlugin::FLAG_ANALYZER_TWICE, 1_622_727_282],
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
            'analyzer' => uniqid('PsalmTestFileTypeAnalyzer'),
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

        $xml = sprintf(
            '<?xml version="1.0"?>
            <psalm><plugins><pluginClass class="%s"/></plugins></psalm>',
            FileTypeSelfRegisteringPlugin::class,
        );

        try {
            $projectAnalyzer = $this->getProjectAnalyzerWithConfig(
                TestConfig::loadFromXML(dirname(__DIR__, 2), $xml),
            );
            $config = $projectAnalyzer->getConfig();
            $config->initializePlugins($projectAnalyzer);
        } catch (ConfigException $exception) {
            $actualExceptionCode = $exception->getPrevious()
                ? $exception->getPrevious()->getCode()
                : null;
            self::assertSame(
                $expectedExceptionCode,
                $actualExceptionCode,
                'Exception code did not match.',
            );
            return;
        }

        self::assertContains($extension, $config->getFileExtensions());
        self::assertSame(get_class($scannerMock), $config->getFiletypeScanners()[$extension] ?? null);
        self::assertSame(get_class($analyzerMock), $config->getFiletypeAnalyzers()[$extension] ?? null);
        self::assertNull($expectedExceptionCode, 'Expected exception code was not thrown');
    }

    public function testTypeStatsForFileReporting(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            Config::loadFromXML(
                (string) getcwd(),
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory ignoreTypeStats="true" name="src/Psalm/Config" />
                        <directory ignoreTypeStats="1" name="src/Psalm/Internal" />
                        <directory ignoreTypeStats="true1" name="src/Psalm/Issue" />
                        <directory ignoreTypeStats="false" name="src/Psalm/Node" />
                        <directory ignoreTypeStats="invalid" name="src/Psalm/Plugin" />
                        <directory ignoreTypeStats="0" name="src/Psalm/Progress" />
                        <directory ignoreTypeStats="" name="src/Psalm/Report" />
                        <directory name="src/Psalm/SourceControl" />
                    </projectFiles>
                </psalm>',
            ),
        );

        $config = $this->project_analyzer->getConfig();

        $this->assertFalse($config->reportTypeStatsForFile((string) realpath('src/Psalm/Config') . DIRECTORY_SEPARATOR));
        $this->assertTrue($config->reportTypeStatsForFile((string) realpath('src/Psalm/Internal') . DIRECTORY_SEPARATOR));
        $this->assertTrue($config->reportTypeStatsForFile((string) realpath('src/Psalm/Issue') . DIRECTORY_SEPARATOR));
        $this->assertTrue($config->reportTypeStatsForFile((string) realpath('src/Psalm/Node') . DIRECTORY_SEPARATOR));
        $this->assertTrue($config->reportTypeStatsForFile((string) realpath('src/Psalm/Plugin') . DIRECTORY_SEPARATOR));
        $this->assertTrue($config->reportTypeStatsForFile((string) realpath('src/Psalm/Progress') . DIRECTORY_SEPARATOR));
        $this->assertTrue($config->reportTypeStatsForFile((string) realpath('src/Psalm/Report') . DIRECTORY_SEPARATOR));
        $this->assertTrue($config->reportTypeStatsForFile((string) realpath('src/Psalm/SourceControl') . DIRECTORY_SEPARATOR));
    }

    public function testStrictTypesForFileReporting(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            Config::loadFromXML(
                (string) getcwd(),
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory useStrictTypes="true" name="src/Psalm/Config" />
                        <directory useStrictTypes="1" name="src/Psalm/Internal" />
                        <directory useStrictTypes="true1" name="src/Psalm/Issue" />
                        <directory useStrictTypes="false" name="src/Psalm/Node" />
                        <directory useStrictTypes="invalid" name="src/Psalm/Plugin" />
                        <directory useStrictTypes="0" name="src/Psalm/Progress" />
                        <directory useStrictTypes="" name="src/Psalm/Report" />
                        <directory name="src/Psalm/SourceControl" />
                    </projectFiles>
                </psalm>',
            ),
        );

        $config = $this->project_analyzer->getConfig();

        $this->assertTrue($config->useStrictTypesForFile((string) realpath('src/Psalm/Config') . DIRECTORY_SEPARATOR));
        $this->assertFalse($config->useStrictTypesForFile((string) realpath('src/Psalm/Internal') . DIRECTORY_SEPARATOR));
        $this->assertFalse($config->useStrictTypesForFile((string) realpath('src/Psalm/Issue') . DIRECTORY_SEPARATOR));
        $this->assertFalse($config->useStrictTypesForFile((string) realpath('src/Psalm/Node') . DIRECTORY_SEPARATOR));
        $this->assertFalse($config->useStrictTypesForFile((string) realpath('src/Psalm/Plugin') . DIRECTORY_SEPARATOR));
        $this->assertFalse($config->useStrictTypesForFile((string) realpath('src/Psalm/Progress') . DIRECTORY_SEPARATOR));
        $this->assertFalse($config->useStrictTypesForFile((string) realpath('src/Psalm/Report') . DIRECTORY_SEPARATOR));
        $this->assertFalse($config->useStrictTypesForFile((string) realpath('src/Psalm/SourceControl') . DIRECTORY_SEPARATOR));
    }

    public function testConfigFileWithXIncludeWithoutFallbackShouldThrowException(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessageMatches('/and no fallback was found/');
        ErrorHandler::install();
        Config::loadFromXML(
            dirname(__DIR__, 2),
            '<?xml version="1.0"?>
            <psalm xmlns:xi="http://www.w3.org/2001/XInclude">
                <projectFiles>
                    <directory name="src" />
                    <directory name="tests" />
                </projectFiles>

                <issueHandlers>
                    <xi:include href="zz.xml" />
                </issueHandlers>
            </psalm>',
        );
    }

    public function testConfigFileWithXIncludeWithFallback(): void
    {
        ErrorHandler::install();
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            Config::loadFromXML(
                dirname(__DIR__, 2),
                '<?xml version="1.0"?>
            <psalm xmlns:xi="http://www.w3.org/2001/XInclude">
                <projectFiles>
                    <directory name="src" />
                    <directory name="tests" />
                </projectFiles>

                <issueHandlers>
                    <xi:include href="zz.xml">
                        <xi:fallback>
                            <MixedAssignment>
                                <errorLevel type="suppress">
                                    <file name="src/Psalm/Type.php" />
                                </errorLevel>
                            </MixedAssignment>
                        </xi:fallback>
                    </xi:include>
                </issueHandlers>
            </psalm>',
            ),
        );

        $config = $this->project_analyzer->getConfig();

        $this->assertFalse($config->reportIssueInFile('MixedAssignment', (string) realpath('src/Psalm/Type.php')));
    }

    public function testConfigFileWithWildcardPathIssueHandler(): void
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
                        <MissingReturnType>
                            <errorLevel type="suppress">
                                <file name="src/**/*TypeAlias.php" />
                                <directory name="src/**/BinaryOp*" />
                            </errorLevel>
                        </MissingReturnType>
                    </issueHandlers>
                </psalm>',
            ),
        );

        $config = $this->project_analyzer->getConfig();

        $this->assertTrue($config->reportIssueInFile('MissingReturnType', (string) realpath(__FILE__)));
        $this->assertTrue($config->reportIssueInFile('MissingReturnType', (string) realpath('src/Psalm/Type.php')));
        $this->assertTrue($config->reportIssueInFile('MissingReturnType', (string) realpath('src/Psalm/Internal/Analyzer/Statements/ReturnAnalyzer.php')));

        $this->assertFalse($config->reportIssueInFile('MissingReturnType', (string) realpath('src/Psalm/Node/Expr/BinaryOp/VirtualPlus.php')));
        $this->assertFalse($config->reportIssueInFile('MissingReturnType', (string) realpath('src/Psalm/Internal/Analyzer/Statements/Expression/BinaryOp/OrAnalyzer.php')));
        $this->assertFalse($config->reportIssueInFile('MissingReturnType', (string) realpath('src/Psalm/Internal/Type/TypeAlias.php')));
        $this->assertFalse($config->reportIssueInFile('MissingReturnType', (string) realpath('src/Psalm/Internal/Type/TypeAlias/ClassTypeAlias.php')));
    }

    /**
     * @requires extension apcu
     * @deprecated Remove in Psalm 6.
     */
    public function testConfigWarnsAboutDeprecatedWayToLoadStubsButLoadsTheStub(): void
    {
        $config_xml = Config::loadFromXML(
            (string)getcwd(),
            '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                </psalm>',
        );
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig($config_xml);
        $codebase = $this->project_analyzer->getCodebase();
        $config = $this->project_analyzer->getConfig();

        $config->visitStubFiles($codebase);

        $this->assertContains((string) realpath('stubs/extensions/apcu.phpstub'), $config->internal_stubs);
        $this->assertContains(
            'Psalm 6 will not automatically load stubs for ext-apcu. You should explicitly enable or disable this ext in composer.json or Psalm config.',
            $config->config_warnings,
        );
    }

    /**
     * @requires extension apcu
     * @deprecated Remove deprecation warning part in Psalm 6.
     */
    public function testConfigWithDisableExtensionsDoesNotLoadExtensionStubsAndHidesDeprecationWarning(): void
    {
        $config_xml = Config::loadFromXML(
            (string)getcwd(),
            '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                    <disableExtensions>
                        <extension name="apcu"/>
                    </disableExtensions>
                </psalm>',
        );
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig($config_xml);
        $codebase = $this->project_analyzer->getCodebase();
        $config = $this->project_analyzer->getConfig();

        $config->visitStubFiles($codebase);

        $this->assertNotContains((string) realpath('stubs/extensions/apcu.phpstub'), $config->internal_stubs);
        $this->assertNotContains(
            'Psalm 6 will not automatically load stubs for ext-apcu. You should explicitly enable or disable this ext in composer.json or Psalm config.',
            $config->internal_stubs,
        );
    }

    public function testReferencedFunctionAllowsMethods(): void
    {
        $config_xml = Config::loadFromXML(
            (string) getcwd(),
            <<<XML
            <?xml version="1.0"?>
            <psalm>
                <issueHandlers>
                    <TooManyArguments>
                        <errorLevel type="suppress">
                            <referencedFunction name="Foo\Bar::baz" />
                        </errorLevel>
                    </TooManyArguments>
                </issueHandlers>
            </psalm>
            XML,
        );

        $this->assertSame(
            Config::REPORT_SUPPRESS,
            $config_xml->getReportingLevelForIssue(
                new TooManyArguments(
                    'too many',
                    new Raw('aaa', 'aaa.php', 'aaa.php', 1, 2),
                    'Foo\Bar::baZ',
                ),
            ),
        );
    }

    public function testReferencedFunctionAllowsNamespacedFunctions(): void
    {
        $config_xml = Config::loadFromXML(
            (string) getcwd(),
            <<<XML
            <?xml version="1.0"?>
            <psalm>
                <issueHandlers>
                    <UndefinedFunction>
                        <errorLevel type="suppress">
                            <referencedFunction name="Foo\Bar\baz" />
                        </errorLevel>
                    </UndefinedFunction>
                </issueHandlers>
            </psalm>
            XML,
        );

        $this->assertSame(
            Config::REPORT_SUPPRESS,
            $config_xml->getReportingLevelForIssue(
                new UndefinedFunction(
                    'Function Foo\Bar\baz does not exist',
                    new Raw('aaa', 'aaa.php', 'aaa.php', 1, 2),
                    'foo\bar\baz',
                ),
            ),
        );
    }
}
