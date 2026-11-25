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

use function array_filter;
use function array_values;
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

        function dirty(string $s): string {
            return $s;
        }

        function retclean(string $s): string {
            return $s;
        }

        final class Handled {
            public static function dirty(string $s): string {
                return $s;
            }

            public static function retclean(string $s): string {
                return $s;
            }
        }

        final class Instance {
            public function dirty(string $s): string {
                return $s;
            }

            public function retclean(string $s): string {
                return $s;
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
     * @param list<string> $events
     */
    #[DataProvider('providerCallTaintEvents')]
    public function testCallTaintEventsAreDispatchedWhereTaintAnalysisRuns(
        string $docblock,
        string $statement,
        array $events,
    ): void {
        CallTaintsRecorder::flush();

        $this->analyze(
            $docblock . '
            function calls(): void {
                clean("a");
                Handled::dirty("b");
                (new Instance())->dirty("c");
                ' . $statement . '
            }',
            // a suppressed TaintedInput is reported unused, though the taint isn't reported
            false,
        );

        // the echos of the declarations
        $this->assertSame(
            $events,
            array_values(array_filter(
                CallTaintsRecorder::flush(),
                static fn(string $event): bool => $event !== 'echo#0',
            )),
        );
    }

    /**
     * @return iterable<string, array{string, string, list<string>}>
     * @psalm-pure
     */
    public static function providerCallTaintEvents(): iterable
    {
        yield 'taint analysis running' => [
            '',
            '',
            ['clean#0', 'clean', 'handled::dirty#0', 'handled::dirty', 'instance::dirty#0', 'instance::dirty'],
        ];
        yield 'TaintedInput suppressed' => [
            '/** @psalm-suppress TaintedInput */',
            'echo (string) $_GET["x"];',
            [],
        ];
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
        yield 'builtin without a stub' => ['call_through("shell_exec", (string) $_GET["x"]);', 'TaintedShell'];
        yield 'call event adding to the return of a PHP function call' => ['echo dirty("safe");', 'TaintedHtml'];
        yield 'call event adding to the return of a static call' => ['echo Handled::dirty("safe");', 'TaintedHtml'];
        yield 'decoder giving back what was escaped' => [
            'echo call_through("html_entity_decode", escape((string) $_GET["x"]));',
            'TaintedHtml',
        ];
        yield 'json decoder giving back what was escaped' => [
            'echo call_through("json_decode", call_through("json_encode", $_GET["x"], JSON_HEX_TAG | JSON_HEX_AMP));',
            'TaintedHtml',
        ];
        yield 'builtin given flags that may not escape' => [
            'echo call_through("json_encode", $_GET["x"], rand(0, 1) ? JSON_HEX_TAG | JSON_HEX_AMP : JSON_HEX_TAG);',
            'TaintedHtml',
        ];
        yield 'call event adding to the return of a method call' => [
            'echo (new Instance())->dirty("safe");',
            'TaintedHtml',
        ];
        yield 'call event adding to the return of a wired call' => ['echo call_through("dirty", "safe");', 'TaintedHtml'];
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
        yield 'method of an unknown class' => ['call_through("Unknown::show", (string) $_GET["x"]);'];
        yield 'call event removing from the return of a PHP function call' => ['echo retclean((string) $_GET["x"]);'];
        yield 'call event removing from the return of a static call' => [
            'echo Handled::retclean((string) $_GET["x"]);',
        ];
        yield 'builtin escaping by its flags' => [
            'echo call_through("htmlspecialchars", (string) $_GET["x"], ENT_QUOTES);
            echo call_through("json_encode", $_GET["y"], JSON_HEX_TAG | JSON_HEX_AMP);',
        ];
        yield 'builtin given flags that always escape' => [
            'echo call_through("json_encode", $_GET["y"], rand(0, 1) ? JSON_HEX_TAG | JSON_HEX_AMP : JSON_HEX_TAG | JSON_HEX_AMP | JSON_PRETTY_PRINT);',
        ];
        yield 'decoder giving nothing back to what holds no input' => [
            'echo call_through("html_entity_decode", "safe");',
        ];
        yield 'call event removing from the return of a method call' => [
            'echo (new Instance())->retclean((string) $_GET["x"]);',
        ];
        yield 'call event removing from the return of a wired call' => [
            'echo call_through("retclean", (string) $_GET["x"]);',
        ];
    }

    private function analyze(string $code, bool $track_unused_suppressions = true): void
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
        $config->eventDispatcher->registerClass(CallTaintsRecorder::class);
        $this->project_analyzer->trackTaintedInputs();

        $file_path = (string) getcwd() . '/src/somefile.php';
        $this->addFile($file_path, self::DECLARATIONS . $code);

        $this->analyzeFile($file_path, new Context(), $track_unused_suppressions);
    }
}
