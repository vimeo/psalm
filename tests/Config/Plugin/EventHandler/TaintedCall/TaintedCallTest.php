<?php

declare(strict_types=1);

namespace Psalm\Tests\Config\Plugin\EventHandler\TaintedCall;

use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Psalm\Context;
use Psalm\Exception\CodeException;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\IncludeCollector;
use Psalm\Internal\Provider\Providers;
use Psalm\Report\ReportOptions;
use Psalm\Tests\DeferredAnalyzerTestCase;
use Psalm\Tests\Internal\Provider\FakeParserCacheProvider;
use Psalm\Tests\TestConfig;

use function define;
use function defined;
use function dirname;
use function getcwd;

use const DIRECTORY_SEPARATOR;

/**
 * A plugin wires calls into the taint graph with Codebase::addTaintedCall(), as Psalm wires those of PHP code.
 */
final class TaintedCallTest extends DeferredAnalyzerTestCase
{
    private const DECLARATIONS = '<?php // --taint-analysis
        /** @param mixed ...$args */
        function call_through(string $function, ...$args): string {
            return "";
        }

        /** @psalm-taint-sink html $s */
        function sink(string $s): void {}

        function show(string $s): void {
            echo $s;
        }

        function same(string $s): string {
            return $s;
        }

        /** @psalm-flow ($s) -> return */
        function flowing(string $s): string {
            return "";
        }

        /** @psalm-taint-escape html */
        function escape(string $s): string {
            return $s;
        }

        /** @psalm-taint-escape ($escape is true ? "html" : null) */
        function maybe_escape(string $s, bool $escape): string {
            return $s;
        }

        /** @psalm-taint-sink ($kind is "sql" ? "sql" : null) $value */
        function query(string $kind, string $value): void {}

        function show_all(string ...$parts): void {
            foreach ($parts as $part) {
                echo $part;
            }
        }

        /** @psalm-taint-source input */
        function input(): string {
            return "";
        }

        /** @psalm-taint-specialize */
        function specialized(string $s): string {
            return $s;
        }

        function clean(string $s): string {
            return $s;
        }

        final class Page {
            public static function show(string $s): void {
                echo $s;
            }
        }
    ';

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

        parent::setUpBeforeClass();
    }

    #[DataProvider('providerInvalidCalls')]
    public function testTaintGoesThroughTheCall(string $code, string $error): void
    {
        $this->expectException(CodeException::class);
        $this->expectExceptionMessageMatches('/^' . $error . ' /');

        $this->analyze($code);
    }

    #[DataProvider('providerValidCalls')]
    public function testTaintDoesNotGoThroughTheCall(string $code): void
    {
        $this->expectNotToPerformAssertions();

        $this->analyze($code);
    }

    /**
     * @return iterable<string, array{string, string}>
     * @psalm-pure
     */
    public static function providerInvalidCalls(): iterable
    {
        yield 'parameter sink' => ['call_through("sink", (string) $_GET["x"]);', 'TaintedHtml'];
        yield 'body' => ['call_through("show", (string) $_GET["x"]);', 'TaintedHtml'];
        yield 'return' => ['echo call_through("same", (string) $_GET["x"]);', 'TaintedHtml'];
        yield 'flow' => ['echo call_through("flowing", (string) $_GET["x"]);', 'TaintedHtml'];
        yield 'conditional escape not taken' => [
            'echo call_through("maybe_escape", (string) $_GET["x"], false);',
            'TaintedHtml',
        ];
        yield 'conditional sink' => ['call_through("query", "sql", (string) $_GET["x"]);', 'TaintedSql'];
        yield 'variadic argument after the first' => [
            'call_through("show_all", "a", (string) $_GET["x"]);',
            'TaintedHtml',
        ];
        yield 'source' => ['echo call_through("input");', 'TaintedHtml'];
        yield 'static method' => ['call_through("Page::show", (string) $_GET["x"]);', 'TaintedHtml'];
        yield 'specialized call taking the taint' => [
            'call_through("specialized", "safe");
            echo call_through("specialized", (string) $_GET["x"]);',
            'TaintedHtml',
        ];
    }

    /**
     * @return iterable<string, array{string}>
     * @psalm-pure
     */
    public static function providerValidCalls(): iterable
    {
        yield 'escape' => ['echo call_through("escape", (string) $_GET["x"]);'];
        yield 'conditional escape taken' => ['echo call_through("maybe_escape", (string) $_GET["x"], true);'];
        yield 'conditional sink not resolved to the taint' => [
            'call_through("query", "other", (string) $_GET["x"]);',
        ];
        yield 'specialized call not taking the taint' => [
            'call_through("specialized", (string) $_GET["x"]);
            echo call_through("specialized", "safe");',
        ];
        yield 'call event of a wired call' => ['echo call_through("clean", (string) $_GET["x"]);'];
        yield 'call event of a PHP call' => ['echo clean((string) $_GET["x"]);'];
    }

    private function analyze(string $code): void
    {
        $config = TestConfig::loadFromXML(
            dirname(__DIR__, 5) . DIRECTORY_SEPARATOR,
            '<?xml version="1.0"?>
            <psalm errorLevel="6" runTaintAnalysis="true">
                <projectFiles>
                    <directory name="src" />
                </projectFiles>
                <plugins>
                    <plugin filename="tests/Config/Plugin/EventHandler/TaintedCall/TaintedCallPlugin.php" />
                </plugins>
                <issueHandlers>
                    <TaintedTextWithQuotes errorLevel="suppress"/>
                    <MissingPureAnnotation errorLevel="suppress"/>
                    <ImpureFunctionCall errorLevel="suppress"/>
                </issueHandlers>
            </psalm>',
        );
        $config->setIncludeCollector(new IncludeCollector());
        $this->project_analyzer = new ProjectAnalyzer(
            $config,
            new Providers($this->file_provider, new FakeParserCacheProvider()),
            new ReportOptions(),
        );
        $this->project_analyzer->initExtraFiles();
        $this->project_analyzer->initProjectFiles();
        $config->initializePlugins($this->project_analyzer);
        $this->project_analyzer->trackTaintedInputs();

        $file_path = (string) getcwd() . '/src/somefile.php';
        $this->addFile($file_path, self::DECLARATIONS . $code);

        $this->analyzeFile($file_path, new Context());
    }
}
