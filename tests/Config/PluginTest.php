<?php
namespace Psalm\Tests\Config;

use PhpParser\Node\Expr\FuncCall;
use PHPUnit\Framework\MockObject\MockObject;
use Psalm\Plugin\Hook\AfterEveryFunctionCallAnalysisInterface;
use Psalm\StatementsSource;
use function define;
use function defined;
use const DIRECTORY_SEPARATOR;
use function dirname;
use function get_class;
use function getcwd;
use function microtime;
use Psalm\Codebase;
use Psalm\Config;
use Psalm\Context;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\IncludeCollector;
use Psalm\Plugin\Hook\AfterCodebasePopulatedInterface;
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

    /** @var ?\Psalm\Internal\Analyzer\ProjectAnalyzer */
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

    /**
     * @return void
     */
    public function testStringAnalyzerPlugin()
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

    /**
     * @return void
     */
    public function testStringAnalyzerPluginWithClassConstant()
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

    /**
     * @return void
     */
    public function testStringAnalyzerPluginWithClassConstantConcat()
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

    /**
     * @return void
     */
    public function testEchoAnalyzerPluginWithJustHtml()
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

    /**
     * @return void
     */
    public function testEchoAnalyzerPluginWithUnescapedConcatenatedString()
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

    /**
     * @return void
     */
    public function testEchoAnalyzerPluginWithUnescapedString()
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

    /**
     * @return void
     */
    public function testEchoAnalyzerPluginWithEscapedString()
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

    /**
     * @return void
     */
    public function testFileAnalyzerPlugin()
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
        $this->assertEmpty($codebase->config->before_file_checks);
        $this->assertEmpty($codebase->config->after_file_checks);
        $codebase->config->initializePlugins($this->project_analyzer);
        $this->assertCount(1, $codebase->config->before_file_checks);
        $this->assertCount(1, $codebase->config->after_file_checks);

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

    /**
     * @return void
     */
    public function testFloatCheckerPlugin()
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

    /**
     * @return void
     */
    public function testFloatCheckerPluginIssueSuppressionByConfig()
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

    /**
     * @return void
     */
    public function testFloatCheckerPluginIssueSuppressionByDocblock()
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

    /** @return void */
    public function testInheritedHookHandlersAreCalled()
    {
        require_once dirname(__DIR__) . '/fixtures/stubs/extending_plugin_entrypoint.php';

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
            $this->project_analyzer->getCodebase()->config->after_function_checks
        );
    }

    /** @return void */
    public function testAfterCodebasePopulatedHookIsLoaded()
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
            public static function afterCodebasePopulated(Codebase $codebase)
            {
            }
        };

        $codebase = $this->project_analyzer->getCodebase();

        $config = $codebase->config;

        (new PluginRegistrationSocket($config, $codebase))->registerHooksFromClass(get_class($hook));

        $this->assertContains(
            get_class($hook),
            $this->project_analyzer->getCodebase()->config->after_codebase_populated
        );
    }

    /** @return void */
    public function testPropertyProviderHooks()
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

    /** @return void */
    public function testMethodProviderHooksValidArg()
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
                class Foo {}

                function i(I $i): void {}

                $foo = new Foo();

                echo $foo->magicMethod("hello");
                echo $foo::magicMethod("hello");

                $foo2 = $foo->magicMethod2("test");
                $foo2->id();
                i($foo2);
                echo $foo2->id();'
        );

        $this->analyzeFile($file_path, new Context());
    }

    /** @return void */
    public function testFunctionProviderHooks()
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

    /** @return void */
    public function testSqlStringProviderHooks()
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

    /**
     * @return void
     */
    public function testPropertyProviderHooksInvalidAssignment()
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

    /**
     * @return void
     */
    public function testMethodProviderHooksInvalidArg()
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

                class Foo {}

                $foo = new Foo();
                echo $foo->magicMethod(5);'
        );

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @return void
     */
    public function testFunctionProviderHooksInvalidArg()
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

    /**
     * @return void
     */
    public function testAfterAnalysisHooks()
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

    /**
     * @return void
     */
    public function testPluginFilenameCanBeAbsolute()
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

            public static function afterEveryFunctionCallAnalysis(
                FuncCall $expr,
                string $function_id,
                Context $context,
                StatementsSource $statements_source,
                Codebase $codebase
            ): void {
                /** @psalm-suppress UndefinedInterfaceMethod */
                self::$m->check($function_id);
            }
        };

        $this->project_analyzer->getCodebase()->config->initializePlugins($this->project_analyzer);
        $this->project_analyzer->getCodebase()->config->after_every_function_checks[] = get_class($plugin);

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
}
