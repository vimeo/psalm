<?php

declare(strict_types=1);

namespace Psalm\Tests\Config\Plugin\EventHandler\RemoveTaints;

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

final class RemoveTaintsInterfaceTest extends TestCase
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

    #[Override]
    public function setUp(): void
    {
        RuntimeCaches::clearAll();
        $this->file_provider = new FakeFileProvider();
    }


    public function testRemoveAllTaints(): void
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
                        <plugin filename="tests/Config/Plugin/EventHandler/RemoveTaints/RemoveAllTaintsPlugin.php" />
                    </plugins>
                </psalm>',
            ),
        );

        $this->project_analyzer->getCodebase()->config->initializePlugins($this->project_analyzer);

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php // --taint-analysis

            /**
             * @psalm-mutation-free
             * @psalm-taint-sink html $string
             */
            function output($string) {}

            echo $_POST["username"];',
        );

        $this->project_analyzer->trackTaintedInputs();

        $this->analyzeFile($file_path, new Context());
    }

    public function testRemoveTaintsSafeArrayKeyChecker(): void
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
                        <plugin filename="examples/plugins/SafeArrayKeyChecker.php" />
                    </plugins>
                </psalm>',
            ),
        );

        $this->project_analyzer->getCodebase()->config->initializePlugins($this->project_analyzer);

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php // --taint-analysis

            /**
             * @psalm-mutation-free
             * 
             * @psalm-taint-sink html $build
             */
            function output(array $build) {}

            $build = [
                "nested" => [
                    "safe_key" => $_GET["input"],
                ],
            ];
            output($build);',
        );

        $this->project_analyzer->trackTaintedInputs();

        $this->analyzeFile($file_path, new Context());

        $this->addFile(
            $file_path,
            '<?php // --taint-analysis

            /**
             * @psalm-mutation-free
             * @psalm-taint-sink html $build
             */
            function output(array $build) {}

            $build = [
                "nested" => [
                    "safe_key" => $_GET["input"],
                    "a" => $_GET["input"],
                ],
            ];
            output($build);',
        );

        $this->project_analyzer->trackTaintedInputs();

        $this->expectException(CodeException::class);
        $this->expectExceptionMessageMatches('/TaintedHtml/');

        $this->analyzeFile($file_path, new Context());
    }
}
