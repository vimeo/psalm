<?php
namespace Psalm\Tests\Config;

use PhpParser\Node\Expr;
use PhpParser\Node\Stmt\ClassLike;
use PHPUnit\Framework\MockObject\MockObject;
use Psalm\Codebase;
use Psalm\FileSource;
use Psalm\Plugin\EventHandler\AfterEveryFunctionCallAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterCodebasePopulatedEvent;
use Psalm\Plugin\EventHandler\Event\AfterEveryFunctionCallAnalysisEvent;
use Psalm\Plugin\Hook\AfterClassLikeVisitInterface;
use Psalm\Plugin\Hook\AfterMethodCallAnalysisInterface;
use Psalm\StatementsSource;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Type\Union;
use function define;
use function defined;
use const DIRECTORY_SEPARATOR;
use function dirname;
use function get_class;
use function getcwd;
use function microtime;
use Psalm\Config;
use Psalm\Context;
use Psalm\Internal\IncludeCollector;
use Psalm\Internal\RuntimeCaches;
use Psalm\Plugin\EventHandler\AfterCodebasePopulatedInterface;
use Psalm\PluginRegistrationSocket;
use Psalm\Tests\Internal\Provider;
use Psalm\Tests\TestConfig;
use function sprintf;
use function ob_start;
use function ob_end_clean;

class PluginTest extends \Psalm\Tests\TestCase
{
    /** @var TestConfig */
    protected static $config;

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
        $this->file_provider = new Provider\FakeFileProvider();
    }

    private function getProjectAnalyzerWithConfig(Config $config): \Psalm\Internal\Analyzer\ProjectAnalyzer
    {
        $config->setIncludeCollector(new IncludeCollector());
        return new \Psalm\Internal\Analyzer\ProjectAnalyzer(
            $config,
            new \Psalm\Internal\Provider\Providers(
                $this->file_provider,
                new Provider\FakeParserCacheProvider()
            ),
            new \Psalm\Report\ReportOptions()
        );
    }

    public function testStringAnalyzerPlugin(): void
    {
        $this->expectExceptionMessage('InvalidClass');
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2) . DIRECTORY_SEPARATOR,
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                    <plugins>
                        <plugin filename="examples/plugins/StringChecker.php" />
                    </plugins>
                </psalm>'
            )
        );

        $this->project_analyzer->getCodebase()->config->initializePlugins($this->project_analyzer);

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                $a = "Psalm\Internal\Analyzer\ProjectAnalyzer";'
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testStringAnalyzerPluginWithClassConstant(): void
    {
        $this->expectExceptionMessage('InvalidClass');
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2) . DIRECTORY_SEPARATOR,
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                    <plugins>
                        <plugin filename="examples/plugins/StringChecker.php" />
                    </plugins>
                </psalm>'
            )
        );

        $this->project_analyzer->getCodebase()->config->initializePlugins($this->project_analyzer);

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                class A {
                    const C = [
                        "foo" => "Psalm\Internal\Analyzer\ProjectAnalyzer",
                    ];
                }'
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testStringAnalyzerPluginWithClassConstantConcat(): void
    {
        $this->expectExceptionMessage('UndefinedMethod');
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2) . DIRECTORY_SEPARATOR,
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                    <plugins>
                        <plugin filename="examples/plugins/StringChecker.php" />
                    </plugins>
                </psalm>'
            )
        );

        $this->project_analyzer->getCodebase()->config->initializePlugins($this->project_analyzer);

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                namespace Psalm;

                class A {
                    const C = [
                        "foo" => \Psalm\Internal\Analyzer\ProjectAnalyzer::class . "::foo",
                    ];
                }'
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testEchoAnalyzerPluginWithJustHtml(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2) . DIRECTORY_SEPARATOR,
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                    <plugins>
                        <plugin filename="examples/plugins/composer-based/echo-checker/EchoChecker.php" />
                    </plugins>
                </psalm>'
            )
        );

        $this->project_analyzer->getCodebase()->config->initializePlugins($this->project_analyzer);

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<h3>This is a header</h3>'
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testEchoAnalyzerPluginWithUnescapedConcatenatedString(): void
    {
        $this->expectExceptionMessage('TypeCoercion');
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2) . DIRECTORY_SEPARATOR,
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                    <plugins>
                        <plugin filename="examples/plugins/composer-based/echo-checker/EchoChecker.php" />
                    </plugins>
                    <issueHandlers>
                        <UndefinedGlobalVariable errorLevel="suppress" />
                        <MixedArgument errorLevel="suppress" />
                        <MixedOperand errorLevel="suppress" />
                    </issueHandlers>
                </psalm>'
            )
        );

        $this->project_analyzer->getCodebase()->config->initializePlugins($this->project_analyzer);

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?= $unsafe . "safeString" ?>'
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testEchoAnalyzerPluginWithUnescapedString(): void
    {
        $this->expectExceptionMessage('TypeCoercion');
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2) . DIRECTORY_SEPARATOR,
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                    <plugins>
                        <plugin filename="examples/plugins/composer-based/echo-checker/EchoChecker.php" />
                    </plugins>
                    <issueHandlers>
                        <UndefinedGlobalVariable errorLevel="suppress" />
                        <MixedArgument errorLevel="suppress" />
                    </issueHandlers>
                </psalm>'
            )
        );

        $this->project_analyzer->getCodebase()->config->initializePlugins($this->project_analyzer);

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?= $unsafe ?>'
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testEchoAnalyzerPluginWithEscapedString(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2) . DIRECTORY_SEPARATOR,
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                    <plugins>
                        <plugin filename="examples/plugins/composer-based/echo-checker/EchoChecker.php" />
                    </plugins>
                    <issueHandlers>
                        <UndefinedGlobalVariable errorLevel="suppress" />
                        <MixedArgument errorLevel="suppress" />
                    </issueHandlers>
                </psalm>'
            )
        );

        $this->project_analyzer->getCodebase()->config->initializePlugins($this->project_analyzer);

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                /**
                 * @param mixed $s
                 * @return html-escaped-string
                 */
                function escapeHtml($s) : string {
                    if (!is_scalar($s)) {
                        throw new \UnexpectedValueException("bad value passed to escape");
                    }
                    /** @var html-escaped-string */
                    return htmlentities((string) $s);
                }
            ?>
            Some text
            <?= escapeHtml($unsafe) ?>'
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testFileAnalyzerPlugin(): void
    {
        require_once __DIR__ . '/Plugin/FilePlugin.php';

        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2) . DIRECTORY_SEPARATOR,
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                    <plugins>
                        <pluginClass class="Psalm\\Test\\Config\\Plugin\\FilePlugin" />
                    </plugins>
                </psalm>'
            )
        );

        $codebase = $this->project_analyzer->getCodebase();
        $this->assertEmpty($codebase->config->eventDispatcher->before_file_checks);
        $this->assertEmpty($codebase->config->eventDispatcher->after_file_checks);
        $codebase->config->initializePlugins($this->project_analyzer);
        $this->assertCount(1, $codebase->config->eventDispatcher->before_file_checks);
        $this->assertCount(1, $codebase->config->eventDispatcher->after_file_checks);

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
            $a = 0;
            '
        );

        $this->analyzeFile($file_path, new Context());
        $file_storage = $codebase->file_storage_provider->get($file_path);
        $this->assertEquals(
            [
                'before-analysis' => true,
                'after-analysis' => true,
            ],
            $file_storage->custom_metadata
        );
    }

    public function testFloatCheckerPlugin(): void
    {
        $this->expectExceptionMessage('NoFloatAssignment');
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2) . DIRECTORY_SEPARATOR,
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                    <plugins>
                        <plugin filename="examples/plugins/PreventFloatAssignmentChecker.php" />
                    </plugins>
                </psalm>'
            )
        );

        $this->project_analyzer->getCodebase()->config->initializePlugins($this->project_analyzer);

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php

            $a = 5.0;'
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testFloatCheckerPluginIssueSuppressionByConfig(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2) . DIRECTORY_SEPARATOR,
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                    <plugins>
                        <plugin filename="examples/plugins/PreventFloatAssignmentChecker.php" />
                    </plugins>

                    <issueHandlers>
                        <PluginIssue name="NoFloatAssignment" errorLevel="suppress" />
                        <PluginIssue name="SomeOtherCustomIssue" errorLevel="suppress" />
                    </issueHandlers>
                </psalm>'
            )
        );

        $this->project_analyzer->getCodebase()->config->initializePlugins($this->project_analyzer);

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php

            $a = 5.0;'
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testFloatCheckerPluginIssueSuppressionByDocblock(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2) . DIRECTORY_SEPARATOR,
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                    <plugins>
                        <plugin filename="examples/plugins/PreventFloatAssignmentChecker.php" />
                    </plugins>
                </psalm>'
            )
        );

        $this->project_analyzer->getCodebase()->config->initializePlugins($this->project_analyzer);

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php

            /** @psalm-suppress NoFloatAssignment */
            $a = 5.0;'
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testInheritedHookHandlersAreCalled(): void
    {
        require_once dirname(__DIR__) . '/fixtures/stubs/extending_plugin_entrypoint.phpstub';

        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2) . DIRECTORY_SEPARATOR,
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                    <plugins>
                        <pluginClass class="ExtendingPluginRegistration" />
                    </plugins>
                </psalm>'
            )
        );

        $this->project_analyzer->getCodebase()->config->initializePlugins($this->project_analyzer);
        $this->assertContains(
            'ExtendingPlugin',
            $this->project_analyzer->getCodebase()->config->eventDispatcher->after_function_checks
        );
    }

    public function testAfterCodebasePopulatedHookIsLoaded(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2) . DIRECTORY_SEPARATOR,
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                </psalm>'
            )
        );

        $hook = new class implements AfterCodebasePopulatedInterface {
            /** @return void */
            public static function afterCodebasePopulated(AfterCodebasePopulatedEvent $event)
            {
            }
        };

        $codebase = $this->project_analyzer->getCodebase();

        $config = $codebase->config;

        (new PluginRegistrationSocket($config, $codebase))->registerHooksFromClass(get_class($hook));

        $this->assertContains(
            get_class($hook),
            $this->project_analyzer->getCodebase()->config->eventDispatcher->after_codebase_populated
        );
    }

    public function testAfterMethodCallAnalysisLegacyHookIsLoaded(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2) . DIRECTORY_SEPARATOR,
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                </psalm>'
            )
        );

        $hook = new class implements AfterMethodCallAnalysisInterface {
            public static function afterMethodCallAnalysis(
                Expr $expr,
                string $method_id,
                string $appearing_method_id,
                string $declaring_method_id,
                Context $context,
                StatementsSource $statements_source,
                Codebase $codebase,
                array &$file_replacements = [],
                Union &$return_type_candidate = null
            ): void {
            }
        };

        $codebase = $this->project_analyzer->getCodebase();

        $config = $codebase->config;

        (new PluginRegistrationSocket($config, $codebase))->registerHooksFromClass(get_class($hook));

        $this->assertTrue($this->project_analyzer->getCodebase()->config->eventDispatcher->hasAfterMethodCallAnalysisHandlers());
    }

    public function testAfterClassLikeAnalysisLegacyHookIsLoaded(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2) . DIRECTORY_SEPARATOR,
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                </psalm>'
            )
        );

        $hook = new class implements AfterClassLikeVisitInterface {
            public static function afterClassLikeVisit(
                ClassLike $stmt,
                ClassLikeStorage $storage,
                FileSource $statements_source,
                Codebase $codebase,
                array &$file_replacements = []
            ): void {
            }
        };

        $codebase = $this->project_analyzer->getCodebase();

        $config = $codebase->config;

        (new PluginRegistrationSocket($config, $codebase))->registerHooksFromClass(get_class($hook));

        $this->assertTrue($this->project_analyzer->getCodebase()->config->eventDispatcher->hasAfterClassLikeVisitHandlers());
    }

    public function testPropertyProviderHooks(): void
    {
        require_once __DIR__ . '/Plugin/PropertyPlugin.php';

        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2) . DIRECTORY_SEPARATOR,
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                    <plugins>
                        <pluginClass class="Psalm\\Test\\Config\\Plugin\\PropertyPlugin" />
                    </plugins>
                </psalm>'
            )
        );

        $this->project_analyzer->getCodebase()->config->initializePlugins($this->project_analyzer);

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                namespace Ns;

                class Foo {}

                $foo = new Foo();
                echo $foo->magic_property;'
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testMethodProviderHooksValidArg(): void
    {
        require_once __DIR__ . '/Plugin/MethodPlugin.php';

        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2) . DIRECTORY_SEPARATOR,
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                    <plugins>
                        <pluginClass class="Psalm\\Test\\Config\\Plugin\\MethodPlugin" />
                    </plugins>
                </psalm>'
            )
        );

        $this->project_analyzer->getCodebase()->config->initializePlugins($this->project_analyzer);

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                namespace Ns;

                interface I {}
                class Foo2 implements I {
                    public function id(): int { return 1; }
                }

                /**
                 * @method static int magicMethod(string $s)  this method return type gets overridden
                 */
                class Foo {
                    public function __call(string $method_name, array $args) {}
                    public static function __callStatic(string $method_name, array $args) {}
                }

                function i(I $i): void {}

                $foo = new Foo();

                echo $foo->magicMethod("hello");
                echo strlen($foo->magicMethod("hello"));
                echo $foo::magicMethod("hello");
                echo strlen($foo::magicMethod("hello"));

                $foo2 = $foo->magicMethod2("test");
                $foo2->id();
                i($foo2);
                echo $foo2->id();'
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testFunctionProviderHooks(): void
    {
        require_once __DIR__ . '/Plugin/FunctionPlugin.php';

        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2) . DIRECTORY_SEPARATOR,
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                    <plugins>
                        <pluginClass class="Psalm\\Test\\Config\\Plugin\\FunctionPlugin" />
                    </plugins>
                </psalm>'
            )
        );

        $this->project_analyzer->getCodebase()->config->initializePlugins($this->project_analyzer);

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                magicFunction("hello");'
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testSqlStringProviderHooks(): void
    {
        require_once __DIR__ . '/Plugin/SqlStringProviderPlugin.php';

        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2) . DIRECTORY_SEPARATOR,
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                    <plugins>
                        <pluginClass class="Psalm\\Test\\Config\\Plugin\\SqlStringProviderPlugin" />
                    </plugins>
                </psalm>'
            )
        );

        $this->project_analyzer->getCodebase()->config->initializePlugins($this->project_analyzer);

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                $a = "select * from videos;";'
        );

        $context = new Context();
        $this->analyzeFile($file_path, $context);

        $this->assertTrue(isset($context->vars_in_scope['$a']));

        foreach ($context->vars_in_scope['$a']->getAtomicTypes() as $type) {
            $this->assertInstanceOf(\Psalm\Test\Config\Plugin\Hook\StringProvider\TSqlSelectString::class, $type);
        }
    }

    public function testPropertyProviderHooksInvalidAssignment(): void
    {
        $this->expectExceptionMessage('InvalidPropertyAssignmentValue');
        $this->expectException(\Psalm\Exception\CodeException::class);
        require_once __DIR__ . '/Plugin/PropertyPlugin.php';

        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2) . DIRECTORY_SEPARATOR,
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                    <plugins>
                        <pluginClass class="Psalm\\Test\\Config\\Plugin\\PropertyPlugin" />
                    </plugins>
                </psalm>'
            )
        );

        $this->project_analyzer->getCodebase()->config->initializePlugins($this->project_analyzer);

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                namespace Ns;

                class Foo {}

                $foo = new Foo();
                $foo->magic_property = 5;'
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testMethodProviderHooksInvalidArg(): void
    {
        $this->expectExceptionMessage('InvalidScalarArgument');
        $this->expectException(\Psalm\Exception\CodeException::class);
        require_once __DIR__ . '/Plugin/MethodPlugin.php';

        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2) . DIRECTORY_SEPARATOR,
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                    <plugins>
                        <pluginClass class="Psalm\\Test\\Config\\Plugin\\MethodPlugin" />
                    </plugins>
                </psalm>'
            )
        );

        $this->project_analyzer->getCodebase()->config->initializePlugins($this->project_analyzer);

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                namespace Ns;

                class Foo {
                    public function __call(string $method_name, array $args) {}
                }

                $foo = new Foo();
                echo strlen($foo->magicMethod(5));'
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testFunctionProviderHooksInvalidArg(): void
    {
        $this->expectExceptionMessage('InvalidScalarArgument');
        $this->expectException(\Psalm\Exception\CodeException::class);
        require_once __DIR__ . '/Plugin/FunctionPlugin.php';

        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2) . DIRECTORY_SEPARATOR,
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                    <plugins>
                        <pluginClass class="Psalm\\Test\\Config\\Plugin\\FunctionPlugin" />
                    </plugins>
                </psalm>'
            )
        );

        $this->project_analyzer->getCodebase()->config->initializePlugins($this->project_analyzer);

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                magicFunction(5);'
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testAfterAnalysisHooks(): void
    {
        require_once __DIR__ . '/Plugin/AfterAnalysisPlugin.php';

        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2) . DIRECTORY_SEPARATOR,
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                >
                    <projectFiles>
                        <directory name="tests/fixtures/DummyProject" />
                    </projectFiles>
                    <plugins>
                        <pluginClass class="Psalm\\Test\\Config\\Plugin\\AfterAnalysisPlugin" />
                    </plugins>
                </psalm>'
            )
        );

        $this->project_analyzer->getCodebase()->config->initializePlugins($this->project_analyzer);

        $this->assertNotNull($this->project_analyzer->stdout_report_options);

        $this->project_analyzer->stdout_report_options->format = \Psalm\Report::TYPE_JSON;

        $this->project_analyzer->check('tests/fixtures/DummyProject', true);
        ob_start();
        \Psalm\IssueBuffer::finish($this->project_analyzer, true, microtime(true));
        ob_end_clean();
    }

    public function testPluginFilenameCanBeAbsolute(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2) . DIRECTORY_SEPARATOR,
                sprintf(
                    '<?xml version="1.0"?>
                    <psalm
                    errorLevel="1"
                >
                        <projectFiles>
                            <directory name="src" />
                        </projectFiles>
                        <plugins>
                            <plugin filename="%s/examples/plugins/StringChecker.php" />
                        </plugins>
                    </psalm>',
                    __DIR__ . '/../..'
                )
            )
        );

        $this->project_analyzer->getCodebase()->config->initializePlugins($this->project_analyzer);
    }

    public function testPluginInvalidAbsoluteFilenameThrowsException() : void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('does-not-exist/plugins/StringChecker.php');

        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2) . DIRECTORY_SEPARATOR,
                sprintf(
                    '<?xml version="1.0"?>
                    <psalm
                    errorLevel="1"
                >
                        <projectFiles>
                            <directory name="src" />
                        </projectFiles>
                        <plugins>
                            <plugin filename="%s/does-not-exist/plugins/StringChecker.php" />
                        </plugins>
                    </psalm>',
                    __DIR__ . '/..'
                )
            )
        );

        $this->project_analyzer->getCodebase()->config->initializePlugins($this->project_analyzer);
    }

    public function testAfterEveryFunctionPluginIsCalledInAllCases(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2) . DIRECTORY_SEPARATOR,
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                ></psalm>'
            )
        );

        $mock = $this->getMockBuilder(\stdClass::class)->setMethods(['check'])->getMock();
        $mock->expects($this->exactly(4))
            ->method('check')
            ->withConsecutive(
                [$this->equalTo('b')],
                [$this->equalTo('array_map')],
                [$this->equalTo('fopen')],
                [$this->equalTo('a')]
            );
        $plugin = new class($mock) implements AfterEveryFunctionCallAnalysisInterface {
            /** @var MockObject */
            private static $m;

            public function __construct(MockObject $m)
            {
                self::$m = $m;
            }

            public static function afterEveryFunctionCallAnalysis(AfterEveryFunctionCallAnalysisEvent $event): void
            {
                $function_id = $event->getFunctionId();
                /** @psalm-suppress UndefinedInterfaceMethod */
                self::$m->check($function_id);
            }
        };

        $this->project_analyzer->getCodebase()->config->initializePlugins($this->project_analyzer);
        $this->project_analyzer->getCodebase()->config->eventDispatcher->after_every_function_checks[] = get_class($plugin);

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php

            function a(): void {}
            function b(int $e): int { return $e; }

            array_map("b", [1,3,3]);
            fopen("/tmp/foo.dat", "r");
            a();
            '
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testRemoveTaints(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2) . DIRECTORY_SEPARATOR,
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="6"
                    runTaintAnalysis="true"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                    <plugins>
                        <plugin filename="examples/plugins/SafeArrayKeyChecker.php" />
                    </plugins>
                </psalm>'
            )
        );

        $this->project_analyzer->getCodebase()->config->initializePlugins($this->project_analyzer);

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php // --taint-analysis

            /**
             * @psalm-taint-sink html $build
             */
            function output(array $build) {}

            $build = [
                "nested" => [
                    "safe_key" => $_GET["input"],
                ],
            ];
            output($build);'
        );

        $this->project_analyzer->trackTaintedInputs();

        $this->analyzeFile($file_path, new Context());

        $this->addFile(
            $file_path,
            '<?php // --taint-analysis

            /**
             * @psalm-taint-sink html $build
             */
            function output(array $build) {}

            $build = [
                "nested" => [
                    "safe_key" => $_GET["input"],
                    "a" => $_GET["input"],
                ],
            ];
            output($build);'
        );

        $this->project_analyzer->trackTaintedInputs();

        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessageRegExp('/TaintedHtml/');

        $this->analyzeFile($file_path, new Context());
    }
}
