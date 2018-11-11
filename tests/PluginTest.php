<?php
namespace Psalm\Tests;

use Prophecy\Argument;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Config;
use Psalm\Context;
use Psalm\PluginApi\PluginEntryPointInterface;
use Psalm\PluginApi\RegistrationInterface;
use SimpleXMLElement;

class PluginTest extends TestCase
{
    /** @var TestConfig */
    protected static $config;

    /** @var \Psalm\Internal\Analyzer\ProjectAnalyzer */
    protected $project_analyzer;

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
        return new \Psalm\Internal\Analyzer\ProjectAnalyzer(
            $config,
            new \Psalm\Internal\Provider\Providers(
                $this->file_provider,
                new Provider\FakeParserCacheProvider()
            )
        );
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidClass
     *
     * @return                   void
     */
    public function testStringAnalyzerPlugin()
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__) . DIRECTORY_SEPARATOR,
                '<?xml version="1.0"?>
                <psalm>
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
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidClass
     *
     * @return                   void
     */
    public function testStringAnalyzerPluginWithClassConstant()
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__) . DIRECTORY_SEPARATOR,
                '<?xml version="1.0"?>
                <psalm>
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
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage UndefinedMethod
     *
     * @return                   void
     */
    public function testStringAnalyzerPluginWithClassConstantConcat()
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__) . DIRECTORY_SEPARATOR,
                '<?xml version="1.0"?>
                <psalm>
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
                        "foo" => \Psalm\Internal\Analyzer\ProjectAnalyzer::class . "::foo",
                    ];
                }'
        );

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @return                   void
     */
    public function testEchoAnalyzerPluginWithJustHtml()
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__) . DIRECTORY_SEPARATOR,
                '<?xml version="1.0"?>
                <psalm>
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
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage TypeCoercion
     *
     * @return                   void
     */
    public function testEchoAnalyzerPluginWithUnescapedConcatenatedString()
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__) . DIRECTORY_SEPARATOR,
                '<?xml version="1.0"?>
                <psalm>
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
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage TypeCoercion
     *
     * @return                   void
     */
    public function testEchoAnalyzerPluginWithUnescapedString()
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__) . DIRECTORY_SEPARATOR,
                '<?xml version="1.0"?>
                <psalm>
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
     * @return                   void
     */
    public function testEchoAnalyzerPluginWithEscapedString()
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__) . DIRECTORY_SEPARATOR,
                '<?xml version="1.0"?>
                <psalm>
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

    /** @return void */
    public function testInheritedHookHandlersAreCalled()
    {
        require_once __DIR__ . '/stubs/extending_plugin_entrypoint.php';

        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__) . DIRECTORY_SEPARATOR,
                '<?xml version="1.0"?>
                <psalm>
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
}
