<?php

declare(strict_types=1);

namespace Psalm\Tests\Config\Plugin\EventHandler\AddTaints;

use Override;
use Psalm\Config;
use Psalm\Context;
use Psalm\Exception\CodeException;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\IncludeCollector;
use Psalm\Internal\Provider\FakeFileProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Internal\RuntimeCaches;
use Psalm\Report\ReportOptions;
use Psalm\Tests\Internal\Provider\FakeParserCacheProvider;
use Psalm\Tests\TestCase;
use Psalm\Tests\TestConfig;

use function define;
use function defined;
use function dirname;
use function getcwd;

use const DIRECTORY_SEPARATOR;

final class AddTaintsInterfaceTest extends TestCase
{
    protected static TestConfig $config;

    #[Override]
    public static function setUpBeforeClass(): void
    {
        self::$config = new TestConfig();

        if (!defined('PSALM_VERSION')) {
            define('PSALM_VERSION', '4.0.0');
        }

        if (!defined('PHP_PARSER_VERSION')) {
            define('PHP_PARSER_VERSION', '4.0.0');
        }
    }

    private function getProjectAnalyzerWithConfig(Config $config): ProjectAnalyzer
    {
        $config->setIncludeCollector(new IncludeCollector());
        return new ProjectAnalyzer(
            $config,
            new Providers(
                $this->file_provider,
                new FakeParserCacheProvider(),
            ),
            new ReportOptions(),
        );
    }

    private function setupProjectAnalyzerWithTaintBadDataPlugin(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 5) . DIRECTORY_SEPARATOR,
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="6"
                    runTaintAnalysis="true"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                    <plugins>
                        <plugin filename="tests/Config/Plugin/EventHandler/AddTaints/TaintBadDataPlugin.php" />
                    </plugins>
                    <issueHandlers>
                        <UndefinedGlobalVariable errorLevel="suppress"/>
                        <UndefinedVariable errorLevel="suppress"/>
                    </issueHandlers>
                </psalm>',
            ),
        );
        $this->project_analyzer->getCodebase()->config->initializePlugins($this->project_analyzer);
    }

    private function setupProjectAnalyzerWithActiveRecordPlugin(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 5) . DIRECTORY_SEPARATOR,
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="6"
                    runTaintAnalysis="true"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                    <plugins>
                        <plugin filename="examples/plugins/TaintActiveRecords.php" />
                    </plugins>
                </psalm>',
            ),
        );
        $this->project_analyzer->getCodebase()->config->initializePlugins($this->project_analyzer);
    }

    private function expectTaintedHtml(): void
    {
        $this->project_analyzer->trackTaintedInputs();

        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('TaintedHtml');
    }

    #[Override]
    public function setUp(): void
    {
        RuntimeCaches::clearAll();
        $this->file_provider = new FakeFileProvider();
    }

    public function testTaintBadDataVariables(): void
    {
        $this->setupProjectAnalyzerWithTaintBadDataPlugin();

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php // --taint-analysis

            echo $bad_data;
            ',
        );

        $this->expectTaintedHtml();

        $this->analyzeFile($file_path, new Context());
    }

    public function testTaintsArePassedByTaintedAssignments(): void
    {
        $this->setupProjectAnalyzerWithTaintBadDataPlugin();

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php // --taint-analysis

            $foo = $bad_data;
            echo $foo;
            ',
        );

        $this->expectTaintedHtml();

        $this->analyzeFile($file_path, new Context());
    }

    public function testTaintsAreOverriddenByRawAssignments(): void
    {
        $this->setupProjectAnalyzerWithTaintBadDataPlugin();

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php // --taint-analysis

            $foo = $bad_data;
            $foo = "I am not bad!";
            echo $foo;
            ',
        );

        $this->project_analyzer->trackTaintedInputs();
        // No exceptions should be thrown

        $this->analyzeFile($file_path, new Context());
    }

    public function testTaintsArePassedByTaintedFuncReturns(): void
    {
        $this->setupProjectAnalyzerWithTaintBadDataPlugin();

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php // --taint-analysis

            function genBadData() {
                return $bad_html;
            }

            echo genBadData();
            ',
        );

        $this->expectTaintedHtml();

        $this->analyzeFile($file_path, new Context());
    }

    public function testTaintsArePassedByTaintedFuncMultipleReturns(): void
    {
        $this->setupProjectAnalyzerWithTaintBadDataPlugin();

        $file_path = (string) getcwd() . '/src/somefile.php';

        // Test that taints are merged and not replaced by later return stmts
        $this->addFile(
            $file_path,
            '<?php // --taint-analysis

            function genBadData(bool $html) {
                if ($html) {
                    return $bad_html;
                }
                return $bad_sql;
            }

            echo genBadData(false);
            ',
        );

        // Find TaintedHtml here, not TaintedSql, as this is not a sink for echo
        $this->expectTaintedHtml();

        $this->analyzeFile($file_path, new Context());
    }

    public function testTaintsArePassedByTaintedMethodReturns(): void
    {
        $this->setupProjectAnalyzerWithTaintBadDataPlugin();

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php // --taint-analysis

            class Foo {
                public function genBadData() {
                    return $bad_html;
                }
            }

            $foo = new Foo();
            echo $foo->genBadData();
            ',
        );

        $this->expectTaintedHtml();

        $this->analyzeFile($file_path, new Context());
    }

    public function testTaintsArePassedByTaintedStaticMethodReturns(): void
    {
        $this->setupProjectAnalyzerWithTaintBadDataPlugin();

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php // --taint-analysis

            class Foo {
                public static function genBadData() {
                    return $bad_html;
                }
            }

            echo Foo::genBadData();
            ',
        );

        $this->expectTaintedHtml();

        $this->analyzeFile($file_path, new Context());
    }

    public function testTaintsArePassedByProxyCalls(): void
    {
        $this->setupProjectAnalyzerWithTaintBadDataPlugin();

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php // --taint-analysis

            class Foo {
                public static function genBadData() {
                    return $bad_html;
                }
            }

            class Bar {
                public static function proxy() {
                    return Foo::genBadData();
                }
            }

            echo Bar::proxy();
            ',
        );

        $this->expectTaintedHtml();

        $this->analyzeFile($file_path, new Context());
    }

    public function testAddTaintsActiveRecord(): void
    {
        $this->setupProjectAnalyzerWithActiveRecordPlugin();

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php // --taint-analysis

            namespace app\models;

            class User {
                public string $name = "<h1>Micky Mouse</h1>";
            }

            $user = new User();
            echo $user->name;
            ',
        );

        $this->expectTaintedHtml();

        $this->analyzeFile($file_path, new Context());
    }

    public function testAddTaintsActiveRecordList(): void
    {
        $this->setupProjectAnalyzerWithActiveRecordPlugin();

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php // --taint-analysis

            namespace app\models;

            class User {
                public $name;

                /**
                 * @psalm-return list<User>
                 */
                public static function findAll(): array {
                    $mockUser = new self();
                    $mockUser->name = "<h1>Micky Mouse</h1>";

                    return [$mockUser];
                }
            }

            foreach (User::findAll() as $user) {
                echo $user->name;
            }
            ',
        );

        $this->expectTaintedHtml();

        $this->analyzeFile($file_path, new Context());
    }

    public function testAddTaintsActiveRecordListItem(): void
    {
        $this->setupProjectAnalyzerWithActiveRecordPlugin();

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php // --taint-analysis

            namespace app\models;

            class User {
                public $name;

                /**
                 * @psalm-return list<User>
                 */
                public static function findAll(): array {
                    $mockUser = new self();
                    $mockUser->name = "<h1>Micky Mouse</h1>";

                    return [$mockUser];
                }
            }

            $users = User::findAll();
            echo $users[0]->name;
            ',
        );

        $this->expectTaintedHtml();

        $this->analyzeFile($file_path, new Context());
    }
}
