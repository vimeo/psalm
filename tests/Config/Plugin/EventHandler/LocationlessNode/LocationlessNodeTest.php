<?php

declare(strict_types=1);

namespace Psalm\Tests\Config\Plugin\EventHandler\LocationlessNode;

use Override;
use Psalm\Config;
use Psalm\Context;
use Psalm\Exception\CodeException;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\IncludeCollector;
use Psalm\Internal\Provider\Providers;
use Psalm\Report\ReportOptions;
use Psalm\Tests\Internal\Provider\FakeParserCacheProvider;
use Psalm\Tests\TestCase;
use Psalm\Tests\TestConfig;

use function define;
use function defined;
use function dirname;
use function getcwd;

use const DIRECTORY_SEPARATOR;

final class LocationlessNodeTest extends TestCase
{
    #[Override]
    public static function setUpBeforeClass(): void
    {
        new TestConfig();

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
        $p = new ProjectAnalyzer(
            $config,
            new Providers(
                $this->file_provider,
                new FakeParserCacheProvider(),
            ),
            new ReportOptions(),
        );
        $p->initExtraFiles();
        $p->initProjectFiles();
        return $p;
    }

    public function testFlowFromANodeWithoutLocationIsReportedAtTheSink(): void
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
                    <issueHandlers>
                        <MissingPureAnnotation errorLevel="suppress"/>
                        <ImpureFunctionCall errorLevel="suppress"/>
                    </issueHandlers>
                </psalm>',
            ),
        );
        $this->project_analyzer->getCodebase()->config->eventDispatcher->registerClass(RelayPlugin::class);

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php // --taint-analysis

            function relay(string $value): void {}
            function deliver(): void {}

            relay((string) $_GET["name"]);
            deliver();
            ',
        );

        // the flow reaches the sink from a node with no location: it is reported at the sink
        $this->expectException(CodeException::class);
        $this->expectExceptionMessageMatches('#^TaintedHtml - (.*[\\\\/])?src[\\\\/]somefile\.php:7:13#');

        $this->analyzeFile($file_path, new Context(), true, true);
    }
}
