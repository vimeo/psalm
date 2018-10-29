<?php
namespace Psalm\Tests;

use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

class PluginTest extends TestCase
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
     * @param  Config $config
     *
     * @return \Psalm\Checker\ProjectChecker
     */
    private function getProjectCheckerWithConfig(Config $config)
    {
        return new \Psalm\Checker\ProjectChecker(
            $config,
            new \Psalm\Provider\Providers(
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
    public function testStringCheckerPlugin()
    {
        $this->project_checker = $this->getProjectCheckerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__) . DIRECTORY_SEPARATOR,
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                    <plugins>
                        <plugin filename="examples/StringChecker.php" />
                    </plugins>
                </psalm>'
            )
        );

        $this->project_checker->config->initializePlugins($this->project_checker);

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                $a = "Psalm\Checker\ProjectChecker";'
        );

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidClass
     *
     * @return                   void
     */
    public function testStringCheckerPluginWithClassConstant()
    {
        $this->project_checker = $this->getProjectCheckerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__) . DIRECTORY_SEPARATOR,
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                    <plugins>
                        <plugin filename="examples/StringChecker.php" />
                    </plugins>
                </psalm>'
            )
        );

        $this->project_checker->config->initializePlugins($this->project_checker);

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                class A {
                    const C = [
                        "foo" => "Psalm\Checker\ProjectChecker",
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
    public function testStringCheckerPluginWithClassConstantConcat()
    {
        $this->project_checker = $this->getProjectCheckerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__) . DIRECTORY_SEPARATOR,
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                    <plugins>
                        <plugin filename="examples/StringChecker.php" />
                    </plugins>
                </psalm>'
            )
        );

        $this->project_checker->config->initializePlugins($this->project_checker);

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                class A {
                    const C = [
                        "foo" => \Psalm\Checker\ProjectChecker::class . "::foo",
                    ];
                }'
        );

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @return                   void
     */
    public function testEchoCheckerPluginWithJustHtml()
    {
        $this->project_checker = $this->getProjectCheckerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__) . DIRECTORY_SEPARATOR,
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                    <plugins>
                        <plugin filename="examples/EchoChecker.php" />
                    </plugins>
                </psalm>'
            )
        );

        $this->project_checker->config->initializePlugins($this->project_checker);

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
    public function testEchoCheckerPluginWithUnescapedConcatenatedString()
    {
        $this->project_checker = $this->getProjectCheckerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__) . DIRECTORY_SEPARATOR,
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                    <plugins>
                        <plugin filename="examples/EchoChecker.php" />
                    </plugins>
                    <issueHandlers>
                        <UndefinedGlobalVariable errorLevel="suppress" />
                        <MixedArgument errorLevel="suppress" />
                        <MixedOperand errorLevel="suppress" />
                    </issueHandlers>
                </psalm>'
            )
        );

        $this->project_checker->config->initializePlugins($this->project_checker);

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
    public function testEchoCheckerPluginWithUnescapedString()
    {
        $this->project_checker = $this->getProjectCheckerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__) . DIRECTORY_SEPARATOR,
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                    <plugins>
                        <plugin filename="examples/EchoChecker.php" />
                    </plugins>
                    <issueHandlers>
                        <UndefinedGlobalVariable errorLevel="suppress" />
                        <MixedArgument errorLevel="suppress" />
                    </issueHandlers>
                </psalm>'
            )
        );

        $this->project_checker->config->initializePlugins($this->project_checker);

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
    public function testEchoCheckerPluginWithEscapedString()
    {
        $this->project_checker = $this->getProjectCheckerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__) . DIRECTORY_SEPARATOR,
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                    <plugins>
                        <plugin filename="examples/EchoChecker.php" />
                    </plugins>
                    <issueHandlers>
                        <UndefinedGlobalVariable errorLevel="suppress" />
                        <MixedArgument errorLevel="suppress" />
                    </issueHandlers>
                </psalm>'
            )
        );

        $this->project_checker->config->initializePlugins($this->project_checker);

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
}
