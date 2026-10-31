<?php

declare(strict_types=1);

namespace Psalm\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psalm\CodeLocation\Raw;
use Psalm\Config;
use Psalm\Config\IssueHandler;
use Psalm\Context;
use Psalm\Exception\CodeException;
use Psalm\Internal\Analyzer\DataFlowNodeData;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Codebase\TaintFlowGraph;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\Provider\Providers;
use Psalm\IssueBuffer;
use Psalm\Tests\Internal\Provider\FakeParserCacheProvider;
use Psalm\Tests\Progress\DebugMessagesProgress;
use Psalm\Type\TaintKind;

use function array_fill;
use function array_filter;
use function array_flip;
use function array_map;
use function array_values;
use function implode;
use function in_array;
use function preg_match;
use function range;
use function sort;
use function sprintf;
use function str_starts_with;
use function strpos;
use function trim;

use const DIRECTORY_SEPARATOR;

final class TaintTest extends TestCase
{
    // Somewhat legacy, do not add new issues here pls
    public const IGNORE = [
        'RiskyCast', 'PossiblyInvalidArgument', 'PossiblyInvalidCast',
        'ForbiddenCode', 'InvalidOperand', 'MixedAssignment',
        'InvalidScalarArgument', 'MissingParamType', 'UndefinedGlobalVariable', 'InvalidReturnType',
        'MixedArgument', 'PossiblyInvalidArgument', 'PossiblyInvalidCast', 'MixedReturnStatement',
        'MixedArgumentTypeCoercion', 'MixedArrayAccess', 'RedundantFunctionCall',
        'MissingPropertyType', 'UndefinedMagicPropertyAssignment', 'InvalidStringClass', 'PossiblyInvalidIterator',
        'InvalidReturnStatement', 'ArgumentTypeCoercion', 'UnresolvableInclude', 'UndefinedClass', 'RedundantCast',
        'MixedArrayAssignment', 'InvalidReturnStatement', 'InvalidArrayOffset', 'UndefinedFunction', 'ImplicitToStringCast',
        'InvalidArgument', 'UndefinedVariable', 'MissingPureAnnotation', 'MissingImmutableAnnotation',
        'MissingAbstractPureAnnotation', 'MissingInterfaceImmutableAnnotation',
    ];
    public function testTaintKindNoHoles(): void
    {
        $constants = array_flip(TaintKind::TAINT_NAMES);

        for ($i = 0; $i < TaintKind::BUILTIN_TAINT_COUNT; $i++) {
            $current = 1 << $i;
            if (!isset($constants[$current])) {
                $this->fail('TaintKind is missing value for bit ' . $i);
            }
        }
    }
    private function trackUnusedVariables(): void
    {
        $this->project_analyzer->getCodebase()->find_unused_variables = true;

        foreach (IssueHandler::getAllIssueTypes() as $issue_name) {
            if (str_starts_with($issue_name, 'Unused') || str_starts_with($issue_name, 'PossiblyUnused')) {
                Config::getInstance()->setCustomErrorLevel($issue_name, Config::REPORT_SUPPRESS);
            }
        }
    }

    /**
     * A taint flow longer than the old hard-coded resolution depth of 40 hops is
     * now detected: resolution runs to a fixed point rather than stopping at an
     * arbitrary nesting level.
     */
    public function testTaintFlowDeeperThanLegacyResolutionDepth(): void
    {
        $chain = '';
        for ($i = 1; $i <= 60; $i++) {
            $chain .= '                    $a' . $i . ' = $a' . ($i - 1) . ";\n";
        }

        $code = "<?php\n"
            . '                    $a0 = (string) $_GET["x"];' . "\n"
            . $chain
            . '                    echo $a60;';

        $this->expectException(CodeException::class);
        $this->expectExceptionMessageMatches('/\bTaintedHtml\b/');

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->project_analyzer->setPhpVersion('8.0', 'tests');

        $this->addFile($file_path, $code);

        $this->project_analyzer->trackTaintedInputs();
        foreach (self::IGNORE as $issue_name) {
            Config::getInstance()->setCustomErrorLevel($issue_name, Config::REPORT_SUPPRESS);
        }

        $this->analyzeFile($file_path, new Context(), false);
    }

    /**
     * A taint flow through a chain of distinct function calls deeper than the old
     * 40-hop resolution limit is detected. Every call site is a specialized node,
     * so this also exercises the specialization linking of the sink-reachability
     * pruning: if a specialized node on the path were wrongly pruned, the flow
     * would be missed.
     */
    public function testTaintFlowThroughDeepSpecializedCallChain(): void
    {
        $functions = '';
        for ($i = 0; $i < 60; $i++) {
            $functions .= '                    function f' . $i . '(string $s): string { return $s; }' . "\n";
        }

        $calls = '';
        for ($i = 0; $i < 60; $i++) {
            $calls .= '                    $v = f' . $i . "(\$v);\n";
        }

        $code = "<?php\n"
            . $functions
            . '                    $v = (string) $_GET["x"];' . "\n"
            . $calls
            . '                    echo $v;';

        $this->expectException(CodeException::class);
        $this->expectExceptionMessageMatches('/\bTaintedHtml\b/');

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->project_analyzer->setPhpVersion('8.0', 'tests');

        $this->addFile($file_path, $code);

        $this->project_analyzer->trackTaintedInputs();
        foreach (self::IGNORE as $issue_name) {
            Config::getInstance()->setCustomErrorLevel($issue_name, Config::REPORT_SUPPRESS);
        }

        $this->analyzeFile($file_path, new Context(), false);
    }

    /**
     * Every level of this chain of specialized functions calls the next level
     * twice, the second time with what the first call stored in a static
     * property, and every call site of the outermost function is a distinct
     * context. Tracking each chain of calls separately explodes combinatorially
     * (and replaying every call's full trace grows exponentially with the depth),
     * whereas each specialized function body is resolved once per entry and
     * applied to every call, so this resolves in well under a second.
     */
    /**
     * A __toString that isn't analyzed (here a stub's) can still declare the taints it escapes: the objects it
     * converts keep their own taints, less those.
     */
    public function testStringConversionThroughAToStringThatIsNotAnalyzedButDeclaresItsEscapes(): void
    {
        $this->addStubFile(
            'views.phpstub',
            '<?php
                final class EscapingView {
                    /**
                     * @psalm-taint-escape html
                     * @psalm-taint-escape has_quotes
                     */
                    public function __toString(): string {}
                }
                final class RawView {
                    public function __toString(): string {}
                }
                /** @psalm-taint-source input */
                function escaping_view(): EscapingView {}
                /** @psalm-taint-source input */
                function raw_view(): RawView {}
            ',
        );

        $this->testConfig->throw_exception = false;
        $file_path = self::$src_dir_path . 'somefile.php';
        $this->addFile($file_path, '<?php
            echo escaping_view();
            echo (string) escaping_view();
            echo raw_view();
        ');
        $this->project_analyzer->trackTaintedInputs();

        $this->analyzeFile($file_path, new Context(), false);

        $issues = array_values(array_filter(
            IssueBuffer::getIssuesDataForFile($file_path),
            static fn(IssueData $issue): bool => !in_array($issue->type, self::IGNORE, true),
        ));

        self::assertSame(
            [[4, 'TaintedHtml'], [4, 'TaintedTextWithQuotes']],
            array_map(static fn(IssueData $issue): array => [$issue->line_from, $issue->type], $issues),
        );
    }

    /**
     * A sink on the values of a parameter of a stubbed method is reported where the argument comes from, like
     * a sink on the parameter itself: the stub isn't a file issues are reported in.
     */
    public function testSinkOnTheValuesOfAParameterOfAStubbedMethod(): void
    {
        $this->addStubFile(
            'loader.phpstub',
            '<?php
                final class TemplateLoader {
                    /**
                     * @param array<string, string> $templates
                     * @psalm-taint-sink eval $templates[*]
                     */
                    public function __construct(array $templates) {}
                }
            ',
        );

        $this->testConfig->throw_exception = false;
        $file_path = self::$src_dir_path . 'somefile.php';
        $this->addFile($file_path, '<?php
            $input = is_string($_GET["input"]) ? $_GET["input"] : "";
            new TemplateLoader(["name" => $input]);
            new TemplateLoader([$input => "template"]);
        ');
        $this->project_analyzer->trackTaintedInputs();

        $this->analyzeFile($file_path, new Context(), false);

        $issues = array_values(array_filter(
            IssueBuffer::getIssuesDataForFile($file_path),
            static fn(IssueData $issue): bool => !in_array($issue->type, self::IGNORE, true),
        ));

        self::assertSame(
            [[3, 'TaintedEval']],
            array_map(static fn(IssueData $issue): array => [$issue->line_from, $issue->type], $issues),
        );
    }

    /**
     * json_encode() removes the html taint only when its flags are known to escape <, > and &, and never the
     * has_quotes one: the " delimiting a JSON string ends a double-quoted attribute, whatever the flags.
     */
    public function testJsonEncodeRemovesTheHtmlTaintOnlyWithItsHexFlags(): void
    {
        $this->testConfig->throw_exception = false;
        $file_path = self::$src_dir_path . 'somefile.php';
        $this->addFile($file_path, '<?php
            namespace Foo;

            function f(int $flags): void {
                echo \json_encode($_GET["a"], \JSON_HEX_TAG | \JSON_HEX_APOS | \JSON_HEX_QUOT | \JSON_HEX_AMP);
                echo \json_encode($_GET["b"], \JSON_THROW_ON_ERROR);
                echo \json_encode($_GET["c"], \JSON_HEX_TAG | \JSON_HEX_APOS | \JSON_HEX_QUOT);
                echo \json_encode($_GET["d"], $flags);
                $args = [$_GET["e"], \JSON_HEX_TAG | \JSON_HEX_AMP];
                echo \json_encode(...$args);
                echo json_encode($_GET["f"], \JSON_HEX_TAG | \JSON_HEX_AMP);
            }

            /** @psalm-flow ($value) -> return */
            function json_encode(string $value, int $flags = 0): string {
                return $value;
            }
        ');
        $this->project_analyzer->trackTaintedInputs();
        $this->project_analyzer->getCodebase()->config->initializePlugins($this->project_analyzer);

        $this->analyzeFile($file_path, new Context(), false);

        $issues = array_values(array_filter(
            IssueBuffer::getIssuesDataForFile($file_path),
            static fn(IssueData $issue): bool => !in_array($issue->type, self::IGNORE, true),
        ));

        self::assertEqualsCanonicalizing(
            [
                [5, 'TaintedTextWithQuotes'],
                [6, 'TaintedHtml'],
                [6, 'TaintedTextWithQuotes'],
                [7, 'TaintedHtml'],
                [7, 'TaintedTextWithQuotes'],
                [8, 'TaintedHtml'],
                [8, 'TaintedTextWithQuotes'],
                [10, 'TaintedHtml'],
                [10, 'TaintedTextWithQuotes'],
                [11, 'TaintedHtml'],
                [11, 'TaintedTextWithQuotes'],
            ],
            array_map(static fn(IssueData $issue): array => [$issue->line_from, $issue->type], $issues),
        );
    }

    /**
     * What a plugin removes from one returned expression is not removed from the other returns of the function, nor
     * from what its calls give.
     */
    public function testTaintRemovedFromOneReturnedExpressionOnly(): void
    {
        $this->testConfig->throw_exception = false;
        $file_path = self::$src_dir_path . 'somefile.php';
        $this->addFile($file_path, '<?php
            function encodeOrRaw(string $s, bool $encode): string|false {
                if ($encode) {
                    return json_encode($s, JSON_HEX_TAG | JSON_HEX_AMP);
                }
                return $s;
            }

            final class A {
                public function escapeOrRaw(string $s, bool $escape): string {
                    if ($escape) {
                        return htmlspecialchars($s, ENT_QUOTES);
                    }
                    return $s;
                }

                public function escape(string $s): string {
                    return htmlspecialchars($s, ENT_QUOTES);
                }
            }

            echo encodeOrRaw((string) $_GET["x"], false);
            echo (new A)->escapeOrRaw((string) $_GET["x"], false);
            echo (new A)->escape((string) $_GET["x"]);
        ');
        $this->project_analyzer->setPhpVersion('8.0', 'tests');
        $this->project_analyzer->trackTaintedInputs();
        $this->project_analyzer->getCodebase()->config->initializePlugins($this->project_analyzer);

        $this->analyzeFile($file_path, new Context(), false);

        $issues = array_values(array_filter(
            IssueBuffer::getIssuesDataForFile($file_path),
            static fn(IssueData $issue): bool => !in_array($issue->type, self::IGNORE, true),
        ));

        self::assertEqualsCanonicalizing(
            [
                [22, 'TaintedHtml'],
                [22, 'TaintedTextWithQuotes'],
                [23, 'TaintedHtml'],
                [23, 'TaintedTextWithQuotes'],
            ],
            array_map(static fn(IssueData $issue): array => [$issue->line_from, $issue->type], $issues),
        );
    }

    public function testTaintFlowThroughNestedSpecializedCallsAndStaticProperty(): void
    {
        $depth = 20;
        $call_sites = 20;

        $code = "<?php\n"
            . "final class Store { public static string \$value = ''; }\n"
            . "/** @psalm-taint-specialize */\n"
            . "function f$depth(string \$s): string { Store::\$value = \$s; return Store::\$value; }\n";

        for ($i = $depth - 1; $i > 0; $i--) {
            $next = $i + 1;
            $code .= "/** @psalm-taint-specialize */\n"
                . "function f$i(string \$s): string {\n"
                . "    \$r = f$next(\$s);\n"
                . "    Store::\$value = \$r;\n"
                . "    return f$next(Store::\$value) . \$r;\n"
                . "}\n";
        }

        for ($i = 0; $i < $call_sites; $i++) {
            $code .= "function site$i(): void { exec(f1((string) (\$_GET['k$i'] ?? ''))); }\n";
        }

        $this->testConfig->throw_exception = false;
        $file_path = self::$src_dir_path . 'somefile.php';
        $this->addFile($file_path, $code);
        $this->project_analyzer->trackTaintedInputs();

        $this->analyzeFile($file_path, new Context(), false);

        $taint_issue_types = array_values(array_filter(
            array_map(
                static fn(IssueData $issue): string => $issue->type,
                IssueBuffer::getIssuesDataForFile($file_path),
            ),
            static fn(string $type): bool => !in_array($type, self::IGNORE, true),
        ));

        self::assertSame(array_fill(0, $call_sites, 'TaintedShell'), $taint_issue_types);
    }

    /**
     * The sinks of the curl options with several, which a test expecting an issue can't check: it only sees the first
     * one reported.
     *
     * @param list<string> $expected_issues
     */
    #[DataProvider('providerCurlSinks')]
    public function testCurlSinks(string $code, array $expected_issues, bool $track_unused_variables): void
    {
        if ($track_unused_variables) {
            $this->trackUnusedVariables();
        }

        $this->testConfig->throw_exception = false;
        $file_path = self::$src_dir_path . 'somefile.php';
        $this->addFile($file_path, $code);
        $this->project_analyzer->trackTaintedInputs();

        $this->analyzeFile($file_path, new Context(), false);

        $taint_issues = array_values(array_filter(
            array_map(
                static fn(IssueData $issue): string => $issue->type . ' on line ' . $issue->line_from,
                IssueBuffer::getIssuesDataForFile($file_path),
            ),
            static fn(string $issue): bool => str_starts_with($issue, 'Tainted'),
        ));
        sort($taint_issues);

        self::assertSame($expected_issues, $taint_issues);
    }

    /**
     * Each of the keys of an array a flow of its own makes a state at each node the array goes through, and each
     * loop that puts the element of the array back under its key an element slot of its own per key: a node
     * reached with many of them has its states widened (see TaintFlowResolution::WIDENING_STATES) so that they do not
     * multiply with the keys. Its findings are still those of the flows widened, which pass any fetch.
     */
    public function testTaintFlowOfManyKeysPutBackUnderEachInManyLoopsReachesFewStates(): void
    {
        [$taint_issues, $states] = $this->analyzeForTaintIssues(
            '<?php
            $form = [];
' . implode('', array_map(
                static fn(int $i): string => 'if (rand(0, 1)) { $form = ["k' . $i . '" => (string) $_GET["q"]]; }' . "\n",
                range(1, 400),
            )) . implode('', array_fill(0, 30, '
            foreach ($form as $key => $element) {
                $form[$key] = $element;
            }
')) . '
            echo $form["k1"];',
        );

        self::assertSame(['TaintedHtml', 'TaintedTextWithQuotes'], $taint_issues);
        // 400 keys in each of 30 loops take 18000 states of the resolution when they are not widened
        self::assertLessThan(12_000, $states);
    }

    /**
     * A flow reaching a node widened past the fetch of the key of another is reported with the flows of the other keys
     * widened too, each from its own source: but they are one finding of the sink.
     */
    public function testTaintFlowsOfManyKeysReachingASinkThroughAWidenedNodeAreReportedOnce(): void
    {
        [$taint_issues] = $this->analyzeForTaintIssues(
            '<?php
            function render(array $form): array {
                foreach ($form as $key => $element) {
                    $form[$key] = $element;
                }

                return $form;
            }

            $form = [];
' . implode('', array_map(
                static fn(int $i): string => 'if (rand(0, 1)) { $form = ["k' . $i . '" => (string) $_GET["q"]]; }' . "\n",
                range(1, 100),
            )) . '
            $form = render($form);
            echo $form["k100"];',
        );

        self::assertSame(['TaintedHtml', 'TaintedTextWithQuotes'], $taint_issues);
    }

    /**
     * The flows of many sources through a node widened reach a sink one of whose flows is of a file where issues aren't
     * reported: that one adds no issue, so it must not stop the others from being reported.
     */
    public function testWidenedFlowsAreReportedIntoASinkWhoseFirstFlowIsNotReported(): void
    {
        $this->project_analyzer->trackTaintedInputs();
        $codebase = $this->project_analyzer->getCodebase();
        $codebase->config->throw_exception = false;

        $file_path = self::$src_dir_path . 'somefile.php';
        $external_path = '/external/vendor/somefile.php';
        $text = '<?php echo $x;';
        $this->file_provider->registerFile($file_path, $text);
        $this->file_provider->registerFile($external_path, $text);
        $codebase->addFilesToAnalyze([$file_path => $file_path]);

        $location = new Raw($text, $file_path, 'somefile.php', 6, 13);
        $external_location = new Raw($text, $external_path, 'vendor/somefile.php', 6, 13);

        $graph = new TaintFlowGraph();
        $codebase->taint_flow_graph = $graph;

        $sink = DataFlowNode::getForTaint('sink', $external_location, TaintKind::INPUT_SHELL);
        $unreported_source = DataFlowNode::getForTaint('a-unreported', $external_location, TaintKind::INPUT_SHELL);
        $hub = DataFlowNode::getForAssignment('hub', $location);
        $argument = DataFlowNode::getForAssignment('argument', $location);
        $graph->addSink($sink);
        $graph->addSource($unreported_source);
        $graph->addNode($hub);
        $graph->addNode($argument);
        $graph->addPath($unreported_source, $sink, '=');

        // more keys than a node has states for before its flows are widened, none of them the one fetched
        for ($i = 0; $i < 100; ++$i) {
            $source = DataFlowNode::getForTaint(sprintf('z-source-%03d', $i), $location, TaintKind::INPUT_SHELL);
            $graph->addSource($source);
            $graph->addPath($source, $hub, "arrayvalue-assignment-'k" . $i . "'");
        }

        $graph->addPath($hub, $argument, "arrayvalue-fetch-'other'");
        $graph->addPath($argument, $sink, '=');
        $graph->connectSinksAndSources($codebase->progress);

        self::assertSame(
            ['TaintedShell'],
            array_map(
                static fn(IssueData $issue): string => $issue->type,
                IssueBuffer::getIssuesDataForFile($file_path),
            ),
        );
    }

    /**
     * @return array{list<string>, int} the taint issues reported, and the states the taint flow resolution reached
     */
    private function analyzeForTaintIssues(string $code): array
    {
        $progress = new DebugMessagesProgress();

        $this->project_analyzer = new ProjectAnalyzer(
            $this->testConfig,
            new Providers($this->file_provider, new FakeParserCacheProvider()),
            progress: $progress,
        );
        $this->project_analyzer->setPhpVersion('7.4', 'tests');
        $this->project_analyzer->trackTaintedInputs();
        $this->testConfig->throw_exception = false;

        $file_path = self::$src_dir_path . 'somefile.php';
        $this->addFile($file_path, $code);
        $this->analyzeFile($file_path, new Context(), false);

        $taint_issues = array_values(array_filter(
            array_map(
                static fn(IssueData $issue): string => $issue->type,
                IssueBuffer::getIssuesDataForFile($file_path),
            ),
            static fn(string $issue): bool => str_starts_with($issue, 'Tainted'),
        ));
        sort($taint_issues);

        $states = null;

        foreach ($progress->messages as $message) {
            if (preg_match('/^Taint flow resolution reached (\d+) states/', $message, $matches) === 1) {
                $states = (int) $matches[1];
            }
        }

        self::assertNotNull($states, 'The taint flow resolution reported no states');

        return [$taint_issues, $states];
    }

    /**
     * @return array<string, array{string, list<string>, bool}>
     * @psalm-pure
     */
    public static function providerCurlSinks(): array
    {
        $cases = [
            // a URL may name a local file: file:///etc/passwd
            'url' => [
                '<?php
                    $url = (string) $_GET["url"];
                    $curl = curl_init($url);
                    curl_setopt($curl, CURLOPT_URL, $url);
                    curl_setopt_array($curl, [CURLOPT_URL => $url]);',
                [
                    'TaintedFile on line 3',
                    'TaintedFile on line 4',
                    'TaintedFile on line 5',
                    'TaintedSSRF on line 3',
                    'TaintedSSRF on line 4',
                    'TaintedSSRF on line 5',
                ],
            ],
            // the request target sent to a proxy is the URL it fetches
            'requestTarget' => [
                '<?php
                    $target = (string) $_GET["target"];
                    curl_setopt(curl_init(), CURLOPT_REQUEST_TARGET, $target);
                    curl_setopt_array(curl_init(), [CURLOPT_REQUEST_TARGET => $target]);',
                [
                    'TaintedHeader on line 3',
                    'TaintedHeader on line 4',
                    'TaintedSSRF on line 3',
                    'TaintedSSRF on line 4',
                ],
            ],
            'unixSocket' => [
                '<?php
                    curl_setopt(curl_init(), CURLOPT_UNIX_SOCKET_PATH, (string) $_GET["socket"]);',
                [
                    'TaintedFile on line 2',
                    'TaintedSSRF on line 2',
                ],
            ],
            // an option the analysis can't tell may be any option with a sink
            'optionTheAnalysisCantTell' => [
                '<?php
                    function set(\\CurlHandle $curl, int $option): void {
                        curl_setopt($curl, $option, (string) $_GET["value"]);
                    }

                    function setAll(\\CurlHandle $curl, int $option): void {
                        curl_setopt_array($curl, [$option => (string) $_GET["value"]]);
                    }',
                [
                    'TaintedCallable on line 3',
                    'TaintedCallable on line 7',
                    'TaintedFile on line 3',
                    'TaintedFile on line 7',
                    'TaintedHeader on line 3',
                    'TaintedHeader on line 7',
                    'TaintedSSRF on line 3',
                    'TaintedSSRF on line 7',
                    'TaintedSleep on line 3',
                    'TaintedSleep on line 7',
                ],
            ],
        ];

        $data = [];

        foreach ($cases as $name => [$code, $expected_issues]) {
            $data[$name] = [$code, $expected_issues, false];
            $data[$name . ' tracking unused variables'] = [$code, $expected_issues, true];
        }

        return $data;
    }

    /**
     * @return array<string, array{code:string}>
     * @psalm-pure
     */
    public static function providerValidCodeParse(): array
    {
        return [
            'taintSourceUnderAKeyOfTheReturnOnly' => [
                'code' => '<?php // --taint-analysis
                    /**
                     * @psalm-taint-source input return[\'original\']
                     * @return array{original: string, path: string}
                     */
                    function upload(): array {
                        return ["original" => "a.txt", "path" => "/tmp/a"];
                    }

                    final class Uploader {
                        /**
                         * @psalm-taint-source input return["file"]["name"]
                         * @return array{file: array{name: string, size: int}, path: string}
                         */
                        public function handle(): array {
                            return ["file" => ["name" => "a.txt", "size" => 1], "path" => "/tmp/a"];
                        }

                        /**
                         * @psalm-taint-source input return[0]
                         * @return array{string, string}
                         */
                        public static function pair(): array {
                            return ["a", "b"];
                        }
                    }

                    echo upload()["path"];
                    echo (new Uploader())->handle()["path"];
                    echo (string) (new Uploader())->handle()["file"]["size"];
                    echo Uploader::pair()[1];',
            ],
            'specializeTheCallsOfAFunctionThatOnlyReadsSharedState' => [
                'code' => '<?php // --taint-analysis
                    final class Phone {
                        private static string $prefix = "+";

                        public static function format(string $phone): string {
                            return self::$prefix . $phone;
                        }
                    }

                    $tainted = Phone::format((string) $_GET["phone"]);
                    echo Phone::format("12");',
            ],
            'fetchOfAnotherKeyOfAForeachCopyOfTheElements' => [
                // Each element is copied under its own key: "city" isn't "name_tail".
                'code' => '<?php
                    /** @param array<string, string> $data */
                    function show(array $data): void {
                        $copy = [];
                        foreach ($data as $key => $value) {
                            $copy[$key] = $value;
                        }
                        echo $copy["city"] ?? "";
                    }

                    $data = [];
                    $data["name_tail"] = (string)($_GET["name_tail"] ?? "");
                    show($data);
                ',
            ],
            'fetchOfAnotherKeyOfAForeachCopyOfTheElementsFetchedByKey' => [
                'code' => '<?php
                    /** @param array<string, string> $data */
                    function show(array $data): void {
                        $copy = [];
                        foreach ($data as $key => $value) {
                            $copy[$key] = $data[$key];
                        }
                        echo $copy["city"] ?? "";
                    }

                    $data = [];
                    $data["name_tail"] = (string)($_GET["name_tail"] ?? "");
                    show($data);
                ',
            ],
            'fetchOfAnotherKeyOfAForeachCopyOfTheNarrowedElements' => [
                // A narrowing isn't a reassignment.
                'code' => '<?php
                    /** @param array<string, string> $data */
                    function show(array $data): void {
                        $copy = [];
                        foreach ($data as $key => $value) {
                            if ($value !== "") {
                                $copy[$key] = $value;
                            }
                        }
                        echo $copy["city"] ?? "";
                    }

                    $data = [];
                    $data["name_tail"] = (string)($_GET["name_tail"] ?? "");
                    show($data);
                ',
            ],
            'fetchOfAnotherKeyOfAForeachCopyOfTheElementsOfAMixedValue' => [
                // The elements of a mixed value are taken from the value as those of an array.
                'code' => '<?php
                    function show(mixed $data): void {
                        $copy = [];
                        foreach ($data as $key => $value) {
                            if (is_string($key) && $value !== null) {
                                $copy[$key] = $value;
                            }
                        }
                        echo (string)($copy["city"] ?? "");
                    }

                    $data = [];
                    $data["name_tail"] = (string)($_GET["name_tail"] ?? "");
                    show($data);
                ',
            ],
            'fetchOfAnotherKeyOfACopyOfTheElementsOfAnotherArrayUnderTheirKey' => [
                // $diff[$key] = $data[$key] copies an element of $data under its own key, whatever $key is.
                'code' => '<?php
                    /**
                     * @param array<string, string> $old
                     * @param array<string, string> $data
                     */
                    function show(array $old, array $data): void {
                        $diff = [];
                        foreach ($old as $key => $value) {
                            if (array_key_exists($key, $data) && $data[$key] !== $value) {
                                $diff[$key] = $data[$key];
                            }
                        }
                        echo $diff["city"] ?? "";
                    }

                    $data = [];
                    $data["name_tail"] = (string)($_GET["name_tail"] ?? "");
                    show(["name_tail" => ""], $data);
                ',
            ],
            'fetchOfTheKeyOfAForeachAssignmentOfTheEscapedElements' => [
                // An escaped element is no copy of it: its taints aren't those of the element.
                'code' => '<?php
                    /** @param array<string, string> $data */
                    function show(array $data): void {
                        $copy = [];
                        foreach ($data as $key => $value) {
                            $copy[$key] = htmlspecialchars($value, ENT_QUOTES);
                        }
                        echo $copy["name_tail"] ?? "";
                    }

                    $data = [];
                    $data["name_tail"] = (string)($_GET["name_tail"] ?? "");
                    show($data);
                ',
            ],
            'fetchOfAnotherKeyOfAForeachTransformOfTheElementsThroughAConcatenation' => [
                'code' => '<?php
                    /** @param array<string, string> $data */
                    function show(array $data): void {
                        $copy = [];
                        foreach ($data as $key => $value) {
                            $copy[$key] = $value . "x";
                        }
                        echo $copy["city"] ?? "";
                    }

                    $data = [];
                    $data["name_tail"] = (string)($_GET["name_tail"] ?? "");
                    show($data);
                ',
            ],
            'fetchOfAnotherKeyOfAForeachTransformOfTheElementsThroughABuiltin' => [
                // What is made of the value of each element is put back under its key: "city" isn't "name_tail".
                'code' => '<?php
                    /** @param array<string, string> $data */
                    function show(array $data): void {
                        $result = [];
                        foreach ($data as $key => $value) {
                            $result[$key] = strtolower($value);
                        }
                        echo $result["city"] ?? "";
                    }

                    $data = [];
                    $data["name_tail"] = (string)($_GET["name_tail"] ?? "");
                    $data["city"] = "Moscow";
                    show($data);
                ',
            ],
            'fetchOfAnotherKeyOfAForeachTransformOfTheElementsThroughAMethod' => [
                'code' => '<?php
                    final class Field {
                        public function normalize(string $value): string {
                            return trim($value);
                        }
                    }

                    /** @param array<string, string> $data */
                    function show(array $data): void {
                        $result = [];
                        foreach ($data as $key => $value) {
                            $result[$key] = (new Field())->normalize($value);
                        }
                        echo $result["city"] ?? "";
                    }

                    $data = [];
                    $data["name_tail"] = (string)($_GET["name_tail"] ?? "");
                    $data["city"] = "Moscow";
                    show($data);
                ',
            ],
            'fetchOfAKeyStartingWithAnotherStartThanTheAssignedKey' => [
                // The start of the fetched key is known: it can't be "name_tail".
                'code' => '<?php
                    /** @param array<string, string> $data */
                    function show(array $data, int $id): void {
                        echo $data["mpf_{$id}"] ?? "";
                    }

                    $data = [];
                    $data["name_tail"] = (string)($_GET["name_tail"] ?? "");
                    show($data, 1);
                ',
            ],
            'fetchOfAKeyOfAnArrayWhoseItemKeyStartsWithSomethingElse' => [
                // The start of the key of the item is known: it can't be "city".
                'code' => '<?php
                    /** @param array<string, string> $data */
                    function show(array $data): void {
                        echo $data["city"] ?? "";
                    }

                    function make(int $id): void {
                        show(["val_{$id}" => (string)($_GET["value"] ?? "")]);
                    }
                ',
            ],
            'fetchOfAKeyAfterAnAssignmentToAKeyStartingWithSomethingElse' => [
                // The start of the assigned key is known: it can't be "city".
                'code' => '<?php
                    /** @param array<string, string> $data */
                    function show(array $data, int $id): void {
                        $data["_mpf_{$id}"] = (string)($_GET["value"] ?? "");
                        echo $data["city"] ?? "";
                    }

                    show([], 1);
                ',
            ],
            'fetchOfAKeyStartingWithSomethingElseThanTheStartOfTheAssignedKey' => [
                // Neither start is a start of the other: the keys can't be the same.
                'code' => '<?php
                    /** @param array<string, string> $data */
                    function show(array $data, int $id): void {
                        $data["ab" . $id] = (string)($_GET["value"] ?? "");
                        echo $data["cd" . $id] ?? "";
                    }

                    show([], 1);
                ',
            ],
            'fetchUnderAKeyThatIsOneOfAFewLiteralsTakesNothingUnderOtherKeys' => [
                'code' => '<?php
                    function show(array $options): void {
                        $trimmed = [];
                        foreach (["mode", "ids"] as $key) {
                            $trimmed[$key] = trim((string) $options[$key]);
                        }
                        echo $trimmed["ids"];
                    }
                    show(["comment" => $_GET["comment"], "mode" => "a", "ids" => "b"]);',
            ],
            'taintFreeAssignmentUnderOneOfLiteralKeys' => [
                'code' => '<?php
                    function show(string $key): void {
                        $array = [];

                        if ($key === "a" || $key === "b") {
                            $array[$key] = (string) $_GET["value"];
                        }

                        echo (string) ($array["c"] ?? "");
                    }',
            ],
            'taintFreeAssignmentUnderLiteralKeyVariable' => [
                'code' => '<?php
                    $key = "a";
                    $array = [];
                    $array[$key] = (string) $_GET["value"];
                    echo (string) ($array["b"] ?? "");',
            ],
            'keyOfAnArrayGivenToAValueSink' => [
                // Only the values of the array are a sink: its keys (the names of the templates here) aren't.
                'code' => '<?php
                    final class Loader {
                        /** @psalm-taint-sink eval $templates[*] */
                        public function __construct(private array $templates) {}
                    }

                    /** @psalm-taint-sink html $values[*] */
                    function showValues(array $values): void {}

                    $name = (string) ($_GET["name"] ?? "");
                    new Loader([$name => "template"]);
                    showValues([$name => "value"]);
                    showValues(["a" => "value"]);',
            ],
            'keysOfArraysPassedToASpecializedFunctionFromManyCalls' => [
                // more calls than a node can hold states before widening: the copies of the call of render() in
                // the filters of the walk of tag() for each key keep the open assignments of the calls
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    function render(array $attr): string {
                        $html = [];
                        foreach ($attr as $name => $value) {
                            $html[] = $name . "=\"" . htmlspecialchars((string) $value, ENT_QUOTES) . "\"";
                        }
                        return implode(" ", $html);
                    }

                    /** @psalm-taint-specialize */
                    function tag(array $attr): string {
                        return "<a " . render($attr) . ">";
                    }
' . implode('', array_map(
                    static fn(int $i): string => 'echo tag(["k' . $i . '" => (string) $_GET["q"]]);' . "\n",
                    range(1, 1100),
                )),
            ],
            'keyFetchInACallOfACallMadeWhereTheFlowsObservedTwoOpenAssignmentsIgnoresValues' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    function render(array $attr): string {
                        $html = [];
                        foreach ($attr as $name => $value) {
                            $html[] = $name . "=\"" . htmlspecialchars((string) $value, ENT_QUOTES) . "\"";
                        }
                        return implode(" ", $html);
                    }

                    /** @psalm-taint-specialize */
                    function decorate(array $attr): string {
                        return "<a " . render($attr) . ">";
                    }

                    /** @psalm-taint-specialize */
                    function anchor(array $opts): string {
                        return decorate($opts["wrap"]["attr"]);
                    }

                    echo anchor(["wrap" => ["attr" => ["data-q" => (string) $_GET["q"]]]]);',
            ],
            'keyFetchInACallMadeWhereTheFlowsObservedTwoOpenAssignmentsIgnoresValues' => [
                // prepare() asks for the class of an open assignment of the calls entering tag() deeper than its
                // flows have: past that, they are in a filter knowing two of them, which tells apart the calls
                // passing the key fetch in render() from the others, like a filter knowing one.
                'code' => '<?php // --taint-analysis
                    /** @psalm-taint-specialize */
                    function prepare(array $attr): array {
                        foreach ($attr as $value) {
                            if (is_array($value)) {
                                foreach ($value as $key => $_) {
                                    $attr["keys"] = $key;
                                }
                            }
                        }
                        return $attr;
                    }

                    /** @psalm-taint-specialize */
                    function render(array $attr): string {
                        $html = "";
                        foreach ($attr as $name => $_) {
                            $html .= $name . " ";
                        }
                        return $html;
                    }

                    /** @psalm-taint-specialize */
                    function tag(array $holder): string {
                        return render(prepare($holder["attr"]));
                    }

                    echo tag(["attr" => ["href" => (string) $_GET["p"]]]);',
            ],
            'keyFetchTwoCallsDownIgnoresTheValuesOfAKeyFetchedOneCallDown' => [
                'code' => '<?php // --taint-analysis
                    /** @psalm-taint-specialize */
                    function keys(array $attr): string {
                        $out = "";
                        foreach ($attr as $k => $_) {
                            $out .= $k;
                        }
                        return $out;
                    }

                    /** @psalm-taint-specialize */
                    function render(array $data): string {
                        return keys($data["attr"]);
                    }

                    echo render(["attr" => ["href" => (string) $_GET["p"]]]);',
            ],
            'fetchOfOneKeyWhereConvergingKeyedArraysConvergeAgain' => [
                // Differently keyed arrays converge at $row, and its flows converge again at $r with others: the
                // fetch still ignores those of other keys.
                'code' => '<?php
                    function show(array $r): void {
                        echo (string) $r["name"];
                    }

                    function relay(array $row): void {
                        show($row);
                    }

                    relay(["a" => (string)($_GET["a"] ?? "")]);
                    relay(["b" => (string)($_GET["b"] ?? "")]);
                    relay(["c" => (string)($_GET["c"] ?? "")]);
                    relay(["d" => (string)($_GET["d"] ?? "")]);
                    relay(["e" => (string)($_GET["e"] ?? "")]);
                    relay(["f" => (string)($_GET["f"] ?? "")]);
                    relay(["g" => (string)($_GET["g"] ?? "")]);
                    relay(["h" => (string)($_GET["h"] ?? "")]);
                    relay(["name" => "safe"]);
                    show(["i" => (string)($_GET["i"] ?? "")]);
                    show(["j" => (string)($_GET["j"] ?? "")]);
                    show(["k" => (string)($_GET["k"] ?? "")]);
                    show(["l" => (string)($_GET["l"] ?? "")]);
                    show(["m" => (string)($_GET["m"] ?? "")]);
                    show(["n" => (string)($_GET["n"] ?? "")]);
                    show(["o" => (string)($_GET["o"] ?? "")]);
                    show(["p" => (string)($_GET["p"] ?? "")]);
                ',
            ],
            'fetchOfAKeySixLevelsDeep' => [
                // The value assigned to key "cost" is wrapped in five more arrays: the fetch of key "callback"
                // past the fetches of those still ignores it, whatever the keys of the unkeyed ones.
                'code' => '<?php
                    /** @param array<string, array{children: array<string, array{children: list<array<string, string>>}>}> $pack */
                    function render(array $pack): void {
                        foreach ($pack as $category) {
                            foreach ($category["children"] as $subcategory) {
                                foreach ($subcategory["children"] as $dish) {
                                    echo $dish["callback"] ?? "";
                                }
                            }
                        }
                    }

                    $cat = (string)($_GET["cat"] ?? "");
                    $sub = (string)($_GET["sub"] ?? "");
                    $pack = [];
                    $pack[$cat]["children"][$sub]["children"][] = ["cost" => (string)($_GET["cost"] ?? "")];
                    render($pack);
                ',
            ],
            'specializedFetchOfOneKeyWhereManyKeyedArraysConverge' => [
                // Enough differently keyed arrays reach $row for what is reachable from it to be walked once,
                // relative to them: the fetch in the specialized call still ignores those of other keys.
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    function getName(array $p): string { return (string) $p["name"]; }

                    function relay(array $row): void {
                        echo getName($row);
                    }

                    relay(["a" => (string)($_GET["a"] ?? "")]);
                    relay(["b" => (string)($_GET["b"] ?? "")]);
                    relay(["c" => (string)($_GET["c"] ?? "")]);
                    relay(["d" => (string)($_GET["d"] ?? "")]);
                    relay(["e" => (string)($_GET["e"] ?? "")]);
                    relay(["f" => (string)($_GET["f"] ?? "")]);
                    relay(["g" => (string)($_GET["g"] ?? "")]);
                    relay(["h" => (string)($_GET["h"] ?? "")]);
                    relay(["name" => "safe"]);
                ',
            ],
            'keysFetchInASpecializedCallWhereManyKeyedArraysConvergeTakesNoValue' => [
                // The calls entering the convergence of $row with a value under an exact key of "wrapper" share a filter,
                // told apart per fetched key: a fetch of the keys under "wrapper" passes none of them. (More keyed
                // arrays than MAX_CONVERGENCE_KEYS, fewer flows than WIDENING_STATES at a node.)
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    function getKeys(array $p): string {
                        $out = "";
                        foreach ($p["wrapper"] as $k => $_) {
                            $out .= (string) $k;
                        }
                        return $out;
                    }

                    function relay(array $row): void {
                        echo getKeys($row);
                    }

                    relay(["wrapper" => ["a" => (string)($_GET["a"] ?? "")]]);
                    relay(["wrapper" => ["b" => (string)($_GET["b"] ?? "")]]);
                    relay(["wrapper" => ["c" => (string)($_GET["c"] ?? "")]]);
                    relay(["wrapper" => ["d" => (string)($_GET["d"] ?? "")]]);
                    relay(["wrapper" => ["e" => (string)($_GET["e"] ?? "")]]);
                    relay(["wrapper" => ["f" => (string)($_GET["f"] ?? "")]]);
                    relay(["wrapper" => ["g" => (string)($_GET["g"] ?? "")]]);
                    relay(["wrapper" => ["h" => (string)($_GET["h"] ?? "")]]);
                    relay(["wrapper" => ["i" => (string)($_GET["i"] ?? "")]]);
                    relay(["wrapper" => ["j" => (string)($_GET["j"] ?? "")]]);
                    relay(["a" => ["name" => (string)($_GET["a"] ?? "")]]);
                    relay(["b" => ["name" => (string)($_GET["b"] ?? "")]]);
                    relay(["c" => ["name" => (string)($_GET["c"] ?? "")]]);
                ',
            ],
            'fetchOfAKeyOfAWrapperWhereManyDifferentlyWrappedArraysConverge' => [
                // The calls entering the convergence of $row are told apart per fetched key at both levels of the
                // arrays: the fetch of key "name" of key "wrapper" ignores the values under other keys at either.
                // (More keyed arrays than MAX_CONVERGENCE_KEYS, fewer flows than WIDENING_STATES at a node.)
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    function getName(array $p): string { return (string) ($p["wrapper"]["name"] ?? ""); }

                    function relay(array $row): void {
                        echo getName($row);
                    }

                    relay(["wrapper" => ["a" => (string)($_GET["a"] ?? "")]]);
                    relay(["wrapper" => ["b" => (string)($_GET["b"] ?? "")]]);
                    relay(["wrapper" => ["c" => (string)($_GET["c"] ?? "")]]);
                    relay(["wrapper" => ["d" => (string)($_GET["d"] ?? "")]]);
                    relay(["wrapper" => ["e" => (string)($_GET["e"] ?? "")]]);
                    relay(["wrapper" => ["f" => (string)($_GET["f"] ?? "")]]);
                    relay(["wrapper" => ["g" => (string)($_GET["g"] ?? "")]]);
                    relay(["wrapper" => ["h" => (string)($_GET["h"] ?? "")]]);
                    relay(["wrapper" => ["i" => (string)($_GET["i"] ?? "")]]);
                    relay(["wrapper" => ["j" => (string)($_GET["j"] ?? "")]]);
                    relay(["a" => ["name" => (string)($_GET["a"] ?? "")]]);
                    relay(["b" => ["name" => (string)($_GET["b"] ?? "")]]);
                    relay(["c" => ["name" => (string)($_GET["c"] ?? "")]]);
                    relay(["wrapper" => ["name" => "safe"]]);
                ',
            ],
            'dontTaintAPathResolvedFromGlobalsTheOperatorChose' => [
                // like IncludeAnalyzer::getPathTo(), which resolves $GLOBALS["name"] with the value it has in Psalm
                'code' => '<?php
                    /**
                     * @psalm-taint-escape file
                     * @psalm-taint-escape ssrf
                     * @psalm-taint-escape include
                     * @psalm-taint-escape callable
                     * @psalm-taint-escape html
                     * @psalm-taint-escape has_quotes
                     * @psalm-taint-escape url_component
                     * @psalm-taint-escape url_path
                     */
                    function configuredPath(string $name): string {
                        return isset($GLOBALS[$name]) && is_string($GLOBALS[$name]) ? $GLOBALS[$name] : "";
                    }

                    function args(): array {
                        global $argv;
                        return (array) $argv;
                    }

                    echo count(args());
                    echo strlen((string) file_get_contents(configuredPath("dir")));
                    include configuredPath("file");',
            ],
            'negatedKeyCheckKeepsWhatTheOtherOffsetsOfAnArrayHold' => [
                'code' => '<?php
                    function f(array $options): string {
                        if (!isset($options["b"]) || !is_string($options["b"])) {
                            return "";
                        }
                        if (isset($options["a"])) {
                            return "a";
                        } else {
                            /** @psalm-check-type-exact $options[\'b\'] = string */;
                            return $options["b"];
                        }
                    }

                    /** @param array<string, int|string> $options */
                    function g(array $options): string {
                        $options["b"] = "x";
                        if (!array_key_exists("a", $options)) {
                            /** @psalm-check-type-exact $options[\'b\'] = \'x\' */;
                            return $options["b"];
                        }
                        return "";
                    }',
            ],
            'dontTaintSpecializedCallWithWhatAnotherPassesThroughAKeyedStore' => [
                // what the calls leave in the store doesn't leak into what they return directly
                'code' => '<?php
                    /** @psalm-flow ($value) -> Store::$data[$key] */
                    function store_set(string $key, string $value): void {}

                    /** @psalm-taint-specialize */
                    function remember(string $v): string {
                        store_set("k", $v);
                        return $v;
                    }

                    remember((string) $_GET["x"]);
                    echo remember("safe");',
            ],
            'keyedStoreKeepsDifferentExactKeysApart' => [
                'code' => '<?php
                    /** @psalm-flow ($value) -> Store::$data[$key] */
                    function store_set(string $key, string $value): void {}

                    /** @psalm-flow Store::$data[$key] -> return */
                    function store_get(string $key): string {}

                    store_set("a", (string) $_GET["x"]);
                    echo store_get("b");',
            ],
            'keyedStoreKnownStartDoesNotReachAnExactKeyItIsNotTheStartOf' => [
                'code' => '<?php
                    /** @psalm-flow ($value) -> Store::$data[$key] */
                    function store_set(string $key, string $value): void {}

                    /** @psalm-flow Store::$data[$key] -> return */
                    function store_get(string $key): string {}

                    function write(string $id): void {
                        store_set("lock_" . $id, (string) $_GET["x"]);
                    }

                    echo store_get("loc");
                    echo store_get("other_lock_1");',
            ],
            'keyedStoreOfACallableResultKeepsKeysApart' => [
                'code' => '<?php
                    interface Cache {
                        /**
                         * @template T
                         * @param callable(): T $compute
                         * @return T
                         * @psalm-taint-specialize
                         * @psalm-flow ($compute()) -> Cache::$data[$key]
                         * @psalm-flow Cache::$data[$key] -> return
                         * @psalm-flow ($compute()) -> return
                         */
                        public function get(string $key, callable $compute): mixed;
                    }

                    function warm(Cache $cache): void {
                        $cache->get("a", fn(): string => (string) $_GET["x"]);
                    }

                    function show(Cache $cache, string $fallback): void {
                        echo $cache->get("b", fn(): string => $fallback);
                    }',
            ],
            'keyedStoreKeyStartFromImplodeDoesNotReachOtherKeys' => [
                'code' => '<?php
                    /** @psalm-flow ($value) -> Store::$data[$key] */
                    function store_set(string $key, string $value): void {}

                    /** @psalm-flow Store::$data[$key] -> return */
                    function store_get(string $key): string {}

                    function write(string $id): void {
                        store_set(implode("_", ["call", "back", $id]), (string) $_GET["x"]);
                    }

                    echo store_get("call_lock");',
            ],
            'keyedStoreKeyStartOfAClosureUseDoesNotReachOtherKeys' => [
                'code' => '<?php
                    /** @psalm-flow ($value) -> Store::$data[$key] */
                    function store_set(string $key, string $value): void {}

                    /** @psalm-flow Store::$data[$key] -> return */
                    function store_get(string $key): string {}

                    store_set("other_1", (string) $_GET["x"]);

                    function read(string $id): void {
                        $key = "lock_" . $id;
                        $read = function () use ($key): void {
                            echo store_get($key);
                        };
                        $read();
                    }',
            ],
            'keyedStoreConstructorArgumentStartDoesNotReachOtherKeys' => [
                'code' => '<?php
                    /** @psalm-flow ($value) -> Store::$data[$key] */
                    function store_set(string $key, string $value): void {}

                    /** @psalm-flow Store::$data[$key] -> return */
                    function store_get(string $key): string {}

                    final class Prefixed {
                        public function __construct(private string $prefix) {}

                        public function write(string $id, string $value): void {
                            store_set($this->prefix . $id, $value);
                        }
                    }

                    final class Writer {
                        public static function write(): void {
                            (new Prefixed("pfx_"))->write((string) $_GET["id"], (string) $_GET["x"]);
                        }
                    }

                    echo store_get("pf");
                    echo store_get("other");',
            ],
            'keyedStoreKeyStartsOfAppendedItemsDoNotReachOtherKeys' => [
                'code' => '<?php
                    /** @psalm-flow ($value) -> Store::$data[$key] */
                    function store_set(string $key, string $value): void {}

                    /** @psalm-flow Store::$data[$keys[*]] -> return */
                    function store_get_many(array $keys): string {}

                    store_set("other", (string) $_GET["x"]);

                    /** @param list<string> $ids */
                    function read(array $ids): void {
                        $keys = [];
                        foreach ($ids as $id) {
                            $keys[] = "data_" . $id;
                        }
                        echo store_get_many($keys);
                    }',
            ],
            'keyedStoreKeyStartsOfAListAMethodReturnsDoNotReachOtherKeys' => [
                'code' => '<?php
                    /** @psalm-flow ($value) -> Store::$data[$key] */
                    function store_set(string $key, string $value): void {}

                    /** @psalm-flow Store::$data[$keys[*]] -> return */
                    function store_get_many(array $keys): string {}

                    store_set("other", (string) $_GET["x"]);

                    final class Reader {
                        /** @param list<string> $ids */
                        public function read(array $ids): void {
                            echo store_get_many($this->keys($ids));
                        }

                        /**
                         * @param list<string> $ids
                         * @return list<string>
                         */
                        private function keys(array $ids): array {
                            $keys = [];
                            foreach ($ids as $id) {
                                $keys[] = $this->key($id);
                            }
                            return $keys;
                        }

                        private function key(string $id): string {
                            return "data_" . $id;
                        }
                    }',
            ],
            'keyedStoreKeyStartOfAFunctionReturnDoesNotReachOtherKeys' => [
                'code' => '<?php
                    /** @psalm-flow ($value) -> Store::$data[$key] */
                    function store_set(string $key, string $value): void {}

                    /** @psalm-flow Store::$data[$key] -> return */
                    function store_get(string $key): string {}

                    function data_key(string $id): string {
                        return "data_" . $id;
                    }

                    store_set("other", (string) $_GET["x"]);

                    function read(string $id): void {
                        echo store_get(data_key($id));
                    }',
            ],
            'traitMethodEscapingWhatItReturns' => [
                'code' => '<?php
                    trait Escapes {
                        public function escape(string $s): string {
                            return htmlspecialchars($s, ENT_QUOTES);
                        }
                    }

                    final class Escaper {
                        use Escapes;
                    }

                    echo (new Escaper())->escape((string) $_GET["x"]);',
            ],
            'writingToTheStandardErrorStreamIsNotAnHtmlSink' => [
                'code' => '<?php
                    $stream = fopen("php://stderr", "w");
                    if ($stream !== false) {
                        fwrite($stream, (string) $_GET["x"]);
                    }',
            ],
            'writingToAFileIsNotAnHtmlSink' => [
                'code' => '<?php
                    file_put_contents("/var/log/x", (string) $_GET["x"]);
                    $stream = fopen("/var/log/x", "w");
                    if ($stream !== false) {
                        fwrite($stream, (string) $_GET["x"]);
                    }',
            ],
            'writingToAStreamOfUnknownTargetIsNotAnHtmlSink' => [
                'code' => '<?php
                    /** @param resource $stream */
                    function writeTo($stream): void {
                        fwrite($stream, (string) $_GET["x"]);
                    }',
            ],
            'closurePassedToMagicStaticMethod' => [
                'code' => '<?php
                    /** @method static string run(Closure $c) */
                    final class Magic {
                        /** @param list<mixed> $args */
                        public static function __callStatic(string $name, array $args): string {
                            return "";
                        }
                    }
                    echo Magic::run(fn(): string => "a");',
              ],
            'keysOfAnArrayDontHoldTheValuesAssignedUnderAVariableKey' => [
                'code' => '<?php
                    /** @return array<string, array{url: string}> */
                    function getData(string $town): array {
                        $result = [];
                        foreach (["a", "b"] as $key) {
                            $result[$key] = ["url" => "/" . $town];
                        }
                        return $result;
                    }

                    $keys = "";
                    foreach (getData($_GET["town"]) as $category => $_) {
                        $keys .= $category;
                    }
                    echo $keys;',
             ],
            'castOrConcatenationOfAnArrayHoldsNoneOfItsElements' => [
                'code' => '<?php
                    function toString(mixed $value): string {
                        if (is_array($value)) {
                            return "array";
                        }

                        return (string) $value;
                    }

                    function prefix(mixed $value): string {
                        return is_array($value) ? "b" : "a{$value}";
                    }

                    // converted, an array is "Array": the elements are not in what a conversion of it gives
                    echo toString(["key" => $_GET["a"]]);
                    echo prefix(["key" => $_GET["b"]]);',
            ],
            'dontTaintTheYieldResultOfALiteralSentToAGenerator' => [
                'code' => '<?php // --taint-analysis
                    function values(): Generator {
                        $sent = yield 1;
                        echo $sent;
                    }

                    $values = values();
                    $values->current();
                    $values->send("literal");',
            ],
            'dontTaintAnUnusedYieldResult' => [
                'code' => '<?php // --taint-analysis
                    function values(): Generator {
                        yield 1;
                        echo "done";
                    }

                    $values = values();
                    $values->current();
                    $values->send((string) $_GET["value"]);',
            ],
            'dontTaintWhatAnotherCallOfASpecializedGeneratorIsSent' => [
                'code' => '<?php // --taint-analysis
                    /** @psalm-taint-specialize */
                    function relay(): Generator {
                        $sent = yield "";
                        yield $sent;
                    }

                    $tainted = relay();
                    $tainted->current();
                    $tainted->send((string) $_GET["value"]);

                    $literal = relay();
                    $literal->current();
                    $literal->send("literal");
                    echo $literal->current();',
            ],
            'dontTaintAnotherKeyOfWhatAGeneratorYields' => [
                'code' => '<?php // --taint-analysis
                    /** @return Generator<int, array{a: string, b: string}> */
                    function rows(): Generator {
                        yield ["a" => (string) $_GET["value"], "b" => "literal"];
                    }

                    foreach (rows() as $row) {
                        echo $row["b"];
                    }',
            ],
            'dontTaintSsrfAfterAnyOfTheStartsOfAUrlFixingItsServer' => [
                'code' => '<?php // --taint-analysis
                    /**
                     * @psalm-taint-sink ssrf $url
                     * @psalm-taint-sink file $url
                     */
                    function fetch(string $url): void {}

                    $origin = match ((string) $_GET["host"]) {
                        "example.com" => "https://example.com/",
                        default => "https://example.org/",
                    };
                    $path = (string) $_GET["path"];
                    fetch($origin . $path);
                    fetch("{$origin}{$path}");
                    $format = rand(0, 1) ? "https://example.com/%s" : "https://example.org/%s";
                    fetch(sprintf($format, $path));
                    fetch(sprintf("https://example.com/" . $path . "/%s", $path));',
            ],
            'dontTaintSsrfAfterTheStartOfAUrlFixingItsServer' => [
                'code' => '<?php // --taint-analysis
                    /**
                     * @psalm-taint-sink ssrf $url
                     * @psalm-taint-sink file $url
                     */
                    function fetch(string $url): void {}

                    $value = (string) $_GET["value"];
                    fetch("https://api.example.com/search?q=" . $value);
                    fetch("https://api.example.com/items/{$value}/details");
                    fetch(sprintf("https://api.example.com/items/%s", $value));
                    fetch("https://api.example.com/" . $value . "/" . $value);
                    $origin = "https://api.example.com";
                    fetch($origin . "/" . $value);
                    fetch("https://api.example.com" . "/{$value}");',
            ],
            'dontTaintSsrfAfterAPathFromTheRoot' => [
                'code' => '<?php // --taint-analysis
                    /** @psalm-taint-sink ssrf $uri */
                    function request(string $uri): void {}

                    $value = (string) $_GET["value"];
                    request("/v3/clusters/" . $value);
                    request("/topics/{$value}/partitions");
                    request(sprintf("/consumers/%s/instances/%s", $value, $value));',
            ],
            'dontTaintUrlPathInTheQueryOrTheFragmentOfAUrl' => [
                'code' => '<?php // --taint-analysis
                    $value = urlencode((string) $_GET["value"]);
                    file_get_contents("https://api.example.com/search?q=" . $value);
                    file_get_contents("https://api.example.com?q={$value}");
                    file_get_contents(sprintf("https://api.example.com/#%s", $value));
                    file_get_contents("https://api.example.com/users/" . (int) $_GET["id"]);
                    file_get_contents("https://api.example.com/" . http_build_query(["q" => $value]));',
            ],
            'dontTaintUrlComponentAfterEncodingIt' => [
                'code' => '<?php // --taint-analysis
                    $value = (string) $_GET["value"];
                    file_get_contents("https://api.example.com/search?q=" . rawurlencode($value));
                    file_get_contents("https://api.example.com/search?" . http_build_query(["q" => $value]));
                    echo rawurlencode($value);
                    echo http_build_query(["q" => $value]);',
            ],
            'dontTaintWhatSprintfFormatsAsANumber' => [
                'code' => '<?php // --taint-analysis
                    $value = (string) $_GET["value"];
                    file_get_contents(sprintf("https://api.example.com/items/%d", $value));
                    file_get_contents(sprintf("https://api.example.com/items/%2\$s/%1\$05.2f", $value, "a"));
                    echo sprintf("%x %\'*10d %%s", $value, $value);',
            ],
            'dontTaintThePropertiesOfASubclassWithWhatAnInheritedMethodSetsOnAnotherSubclass' => [
                'code' => '<?php // --taint-analysis
                    abstract class Provider {
                        /** @var array<string, string> */
                        protected array $options = [];

                        /** @param array<string, string> $options */
                        public function __construct(array $options) {
                            $this->setOptions($options);
                        }

                        /** @param array<string, string> $options */
                        public function setOptions(array $options): void {
                            foreach ($options as $key => $value) {
                                $this->setOption($key, $value);
                            }
                        }

                        public function setOption(string $key, string $value): void {
                            $this->options[$key] = $value;
                        }
                    }

                    final class Comments extends Provider {}

                    final class Activity extends Provider {
                        public function printIds(): void {
                            echo $this->options["ids"] ?? "";
                        }
                    }

                    new Comments(["comment" => (string) $_GET["comment"]]);
                    (new Activity([]))->printIds();',
            ],
            'dontPassWhatAnInheritedBodyGivesSelfCallsToTheOverridesOfSubclasses' => [
                'code' => '<?php // --taint-analysis
                    class Base {
                        public function show(string $text): void {}

                        public function run(string $text): void {
                            self::show($text);
                        }
                    }

                    class Child extends Base {}

                    final class GrandChild extends Child {
                        public function show(string $text): void {
                            echo $text;
                        }
                    }

                    // self:: runs Base::show, whatever the class of the object
                    (new Child())->run((string) $_GET["text"]);',
            ],
            'dontTaintThePropertiesOfASubclassWithWhatAParentConstructorSetsOnAnotherSubclass' => [
                'code' => '<?php // --taint-analysis
                    class Field {
                        /** @var array<string, string> */
                        protected array $opts;

                        /** @param array<string, string> $opts */
                        public function __construct(array $opts) {
                            $this->opts = $opts;
                        }
                    }

                    final class Checkbox extends Field {
                        /** @param array<string, string> $opts */
                        public function __construct(array $opts) {
                            parent::__construct($opts);
                        }
                    }

                    final class Button extends Field {
                        /** @param array<string, string> $opts */
                        public function __construct(array $opts) {
                            parent::__construct($opts);
                        }

                        public function render(): void {
                            echo $this->opts["label"] ?? "";
                        }
                    }

                    new Checkbox(["label" => (string) $_GET["label"]]);
                    (new Button([]))->render();',
            ],
            'dontTaintThePropertiesOfASubclassWithWhatAnAncestorConstructorCalledByNameSetsOnAnotherSubclass' => [
                'code' => '<?php // --taint-analysis
                    class Field {
                        /** @var array<string, string> */
                        protected array $opts;

                        /** @param array<string, string> $opts */
                        public function __construct(array $opts) {
                            $this->opts = $opts;
                        }
                    }

                    class Select extends Field {}

                    final class MultiSelect extends Select {
                        /** @param array<string, string> $opts */
                        public function __construct(array $opts) {
                            Field::__construct($opts);
                        }
                    }

                    final class Button extends Field {
                        public function render(): void {
                            echo $this->opts["label"] ?? "";
                        }
                    }

                    new MultiSelect(["label" => (string) $_GET["label"]]);
                    (new Button([]))->render();',
            ],
            'dontTaintThePrintfOutputOfWhatIsFormattedAsANumber' => [
                'code' => '<?php // --taint-analysis
                    printf("%d", (string) $_GET["x"]);
                    vprintf("%d items", [(string) $_GET["x"]]);
                    printf("%2\$s %1\$05.2f", (string) $_GET["x"], "a");',
            ],
            'dontTaintWhatVsprintfFormatsAsANumber' => [
                'code' => '<?php // --taint-analysis
                    echo vsprintf("%d items", [(string) $_GET["x"]]);
                    echo vsprintf("%2\$x %1\$05.2f", [(string) $_GET["x"], (string) $_GET["y"]]);',
            ],
            'dontTaintThePhpOutputStreamWritesOfWhatIsFormattedAsANumber' => [
                'code' => '<?php // --taint-analysis
                    $out = fopen("php://output", "w");
                    if ($out !== false) {
                        fprintf($out, "%d", (string) $_GET["x"]);
                        vfprintf($out, "%d items", [(string) $_GET["x"]]);
                    }',
            ],
            'dontTaintAPrivatePropertyWithWhatIsSetToTheOneOfAParentClass' => [
                'code' => '<?php // --taint-analysis
                    class Model {
                        private string $name = "";

                        public function setName(string $name): void {
                            $this->name = $name;
                        }
                    }

                    final class Admin extends Model {
                        private string $name = "";

                        public function printName(): void {
                            echo $this->name;
                        }
                    }

                    $admin = new Admin();
                    $admin->setName((string) $_GET["name"]);
                    $admin->printName();',
            ],
            'dontTaintThePropertyOfASubclassWithWhatIsSetThroughAnotherSubclass' => [
                'code' => '<?php // --taint-analysis
                    class Model {
                        protected string $name = "";
                    }

                    class Admin extends Model {
                        public function printName(): void {
                            echo $this->name;
                        }
                    }

                    final class SuperAdmin extends Admin {}

                    final class Guest extends Model {
                        public function setName(string $name): void {
                            $this->name = $name;
                        }
                    }

                    (new Guest())->setName((string) $_GET["name"]);
                    (new SuperAdmin())->printName();',
            ],
            'variableAssignedInEveryBranchHoldsOnlyItsLastValues' => [
                'code' => '<?php
                    function render(string $input, bool $html): string {
                        if ($html) {
                            $text = trim($input);
                            $text = htmlspecialchars($text, ENT_QUOTES);
                        } else {
                            $text = "none";
                        }

                        return $text;
                    }

                    echo render($_GET["x"], true);',
            ],
            'taintFreeSpecializedCallWhoseReturnTheTaintedCallSiteDoesNotUse' => [
                'code' => '<?php
                    final class Storage {
                        private string $baseUrl = "https://example.com";

                        /** @psalm-taint-specialize */
                        public function getUrl(string $path): string {
                            return sprintf("%s/%s", $this->baseUrl, ltrim($path, "/"));
                        }

                        public function request(string $method, string $path): bool {
                            return ($method . $this->getUrl($path)) !== "";
                        }
                    }

                    function upload(Storage $storage): void {
                        $storage->request("HEAD", (string) $_GET["path"]);
                    }

                    function show(Storage $storage): void {
                        echo $storage->getUrl("safe");
                    }',
            ],
            'taintFreeSpecializedInstanceBuiltInAnotherOne' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    final class Tag {
                        public string $content;

                        public function __construct(string $content) {
                            $this->content = $content;
                        }
                    }

                    /** @psalm-taint-specialize */
                    final class Inline {
                        public Tag $tag;

                        public function __construct(string $content) {
                            $this->tag = new Tag($content);
                        }
                    }

                    function geo(): void {
                        new Inline((string) $_GET["owner"]);
                    }

                    function phone(): void {
                        $tag = new Tag("safe");
                        echo $tag->content;
                    }',
            ],
            'taintFreeUnusedReturnOfSpecializedCall' => [
                'code' => '<?php
                    /** @psalm-pure */
                    function identity(string $value): string {
                        return $value;
                    }

                    identity((string) $_GET["value"]);
                    echo identity("safe");',
            ],
            'dontTaintThePropertiesOfASubclassWithWhatAnInheritedConstructorSetsOnOtherObjects' => [
                'code' => '<?php // --taint-analysis
                    abstract class Provider {
                        /** @var array<string, string> */
                        protected array $options = [];

                        /** @param array<string, string> $options */
                        public function __construct(array $options) {
                            foreach ($options as $key => $value) {
                                $this->options[$key] = $value;
                            }
                        }
                    }

                    final class Comments extends Provider {}

                    final class Activity extends Provider {
                        protected string $title;

                        public function __construct() {
                            parent::__construct([]);
                            $this->title = "";
                        }

                        public function printIds(): void {
                            echo $this->options["ids"] ?? $this->title;
                        }
                    }

                    new Comments(["comment" => (string) $_GET["comment"]]);
                    (new Activity())->printIds();',
            ],
            'dontTaintThePropertiesOfASubclassWithWhatAnInheritedMethodSetsOnOtherObjects' => [
                'code' => '<?php // --taint-analysis
                    class Block {
                        /** @var array<string, string> */
                        protected array $children = [];

                        public function add(string $key, string $child): void {
                            $this->children[$key] = $child;
                        }
                    }

                    final class ErrorBlock extends Block {
                        public function __construct() {
                            $this->add("trace", (string) $_GET["trace"]);
                        }
                    }

                    class Layout extends Block {
                        public function __construct() {
                            $this->add("title", "Title");
                        }
                    }

                    final class Page extends Layout {
                        protected string $footer;

                        public function __construct() {
                            parent::__construct();
                            $this->footer = "";
                        }

                        public function render(): void {
                            echo $this->children["top"] ?? $this->footer;
                        }
                    }',
            ],
            'dontTaintTheOtherKeysOfAnElementABuiltinReturns' => [
                'code' => '<?php // --taint-analysis
                    $files = ["tmp_name" => ["name" => (string) $_GET["name"]]];
                    echo (string) (current($files)["tmp_name"] ?? "");
                    echo (string) (array_pop($files)["tmp_name"] ?? "");',
            ],
            'dontTaintTheKeyOfAnArrayWithItsValues' => [
                'code' => '<?php // --taint-analysis
                    $values = ["key" => (string) $_GET["value"]];
                    echo key($values);
                    echo (string) array_key_first($values);',
            ],
            'dontTaintTheKeysOfAnArrayWithItsValuesThroughArrayKeysOrSearch' => [
                'code' => '<?php // --taint-analysis
                    $values = ["key" => (string) $_GET["value"]];
                    foreach (array_keys($values) as $key) {
                        echo $key;
                    }
                    echo (string) array_search("x", $values);',
            ],
            'dontTaintTheOtherKeysOfTheElementsArrayMapReturns' => [
                'code' => '<?php // --taint-analysis
                    $links = array_map(
                        fn(string $title): array => ["title" => $title, "url" => "/"],
                        ["a" => (string) $_GET["title"]],
                    );
                    foreach ($links as $link) {
                        echo $link["url"];
                    }',
            ],
            'dontTaintTheOtherKeysOfTheElementsArrayMapPassesOn' => [
                'code' => '<?php // --taint-analysis
                    $links = ["a" => ["title" => (string) $_GET["title"], "url" => "/"]];
                    foreach (array_map(fn(array $link): array => $link, $links) as $link) {
                        echo (string) $link["url"];
                    }',
            ],
            'dontTaintTheOtherKeysOfTheArrayArrayMapReturns' => [
                'code' => '<?php // --taint-analysis
                    $query = ["tail" => (string) $_GET["tail"], "city" => "msk"];
                    $params = array_map(fn(string $value): string => $value, $query);
                    echo $params["city"];',
            ],
            'dontTaintTheOtherKeysOfTheArrayArrayMapReturnsThroughClosureVariables' => [
                'code' => '<?php // --taint-analysis
                    $query = ["tail" => (string) $_GET["tail"], "city" => "msk"];
                    $params = array_map(
                        static function (string $value): string {
                            $trimmed = trim($value);
                            return $trimmed;
                        },
                        $query,
                    );
                    echo $params["city"];',
            ],
            'dontTaintTheOtherKeysOfTheArrayArrayMapReturnsThroughCapturedValues' => [
                'code' => '<?php // --taint-analysis
                    $query = ["tail" => (string) $_GET["tail"], "city" => "msk"];
                    $prefix = "p";
                    $suffixes = ["a" => "s"];
                    $params = array_map(
                        static function (string $value) use ($prefix, $suffixes): string {
                            return $prefix . $value . $suffixes["a"];
                        },
                        $query,
                    );
                    $prefixed = array_map(fn(string $value): string => $prefix . $value, $query);
                    echo $params["city"];
                    echo $prefixed["city"];',
            ],
            'dontTaintUnserializeOfWhatSerializeReturns' => [
                'code' => '<?php // --taint-analysis
                    unserialize(serialize((string) $_GET["value"]));',
            ],
            'dontTaintAnotherKeyOfAnUnserializedArray' => [
                'code' => '<?php
                    $a = [];
                    $a["k"] = (string) $_GET["x"];
                    $a["other"] = "safe";
                    $b = unserialize(serialize($a));
                    echo (string) $b["other"];',
            ],
            'dontTaintAnotherKeyOfAJsonDecodedArray' => [
                'code' => '<?php
                    $a = [];
                    $a["k"] = (string) $_GET["x"];
                    $a["other"] = "safe";
                    $b = json_decode(json_encode($a, JSON_THROW_ON_ERROR), true);
                    echo (string) $b["other"];',
            ],
            'dontTaintTheOtherItemsDestructuredFromAnUnshapedArray' => [
                'code' => '<?php // --taint-analysis
                    function getComment(): array {
                        return ["author" => (string) $_GET["author"], "text" => "safe"];
                    }

                    ["author" => $author, "text" => $text] = getComment();
                    echo $text;',
            ],
            'dontTaintTheOtherItemsDestructuredByIntegerKeys' => [
                'code' => '<?php // --taint-analysis
                    function getComment(): array {
                        return [1 => (string) $_GET["author"], 2 => "safe"];
                    }

                    [1 => $author, 2 => $text] = getComment();
                    echo $text;',
            ],
            'dontTaintTheOtherItemsDestructuredFromAShapeReturnedByACall' => [
                'code' => '<?php // --taint-analysis
                    /** @return array{0: string, 1: string} */
                    function getComment(): array {
                        return [(string) $_GET["author"], "safe"];
                    }

                    [$author, $text] = getComment();
                    echo $text;',
            ],
            'dontTaintTheOtherItemsDestructuredInAForeach' => [
                'code' => '<?php // --taint-analysis
                    function getComment(): array {
                        return ["author" => (string) $_GET["author"], "text" => "safe"];
                    }

                    foreach (["first" => getComment()] as ["author" => $author, "text" => $text]) {
                        echo $text;
                    }',
            ],
            'dontTaintTheOtherItemsOfANestedDestructuring' => [
                'code' => '<?php // --taint-analysis
                    function getComment(): array {
                        return ["author" => (string) $_GET["author"], "text" => "safe"];
                    }

                    /** @return non-empty-list<array> */
                    function getComments(): array {
                        return [getComment()];
                    }

                    [["author" => $author, "text" => $text]] = getComments();
                    echo $text;',
            ],
            'dontTaintTheOtherItemsOfANestedDestructuringThroughArrayValues' => [
                'code' => '<?php // --taint-analysis
                    function getComment(): array {
                        return ["author" => (string) $_GET["author"], "text" => "safe"];
                    }

                    [["author" => $author, "text" => $text]] = array_values([getComment()]);
                    echo $text;',
            ],
            'objectIdConvertedToAStringHoldsNoInput' => [
                'code' => '<?php // --taint-analysis
                    function show(mixed $value): string {
                        if ($value instanceof MongoDB\BSON\ObjectId) {
                            // its 24 hexadecimal digits
                            return (string) $value;
                        }
                        return "";
                    }

                    echo show($_GET["id"]);',
            ],
            'objectIdBuiltFromInputHoldsNoInput' => [
                'code' => '<?php // --taint-analysis
                    // the constructor throws unless it gets 24 hexadecimal digits
                    $id = new MongoDB\BSON\ObjectId((string) $_GET["id"]);
                    echo (string) $id;
                    echo "<a href=\'/o/{$id}\'>";',
            ],
            'dontTaintTheStringConversionOfAnObjectWhoseToStringEscapesIt' => [
                'code' => '<?php // --taint-analysis
                    final class Tag {
                        public string $content = "";
                        public function __toString(): string {
                            return htmlspecialchars($this->content);
                        }
                    }

                    /** @psalm-taint-source input */
                    function getTag(): Tag {
                        return new Tag();
                    }

                    $tag = getTag();
                    echo (string) $tag;
                    echo $tag;
                    echo "<p>{$tag}</p>";
                    echo "<p>" . $tag . "</p>";',
            ],
            'dontTaintTheValueAnArrayHeldUnderAKeyAssignedSinceThen' => [
                'code' => '<?php // --taint-analysis
                    $data = ["city" => (string) $_GET["city"], "zip" => "000"];
                    $data["city"] = "msk";
                    echo $data["city"];
                    foreach ($data as $value) {
                        echo $value;
                    }',
            ],
            'dontTaintTheValueAnArrayHeldUnderAKeyUnsetSinceThen' => [
                'code' => '<?php // --taint-analysis
                    $data = ["city" => (string) $_GET["city"], "zip" => "000"];
                    unset($data["city"]);
                    echo $data["city"] ?? "";',
            ],
            'dontTaintTheValueANestedArrayHeldUnderAKeyAssignedSinceThen' => [
                'code' => '<?php // --taint-analysis
                    $data = ["address" => ["city" => (string) $_GET["city"], "zip" => "000"]];
                    $data["address"]["city"] = "msk";
                    echo $data["address"]["city"];',
            ],
            'dontTaintTheValueAWholeArrayHeldUnderAKeyAssignedSinceThen' => [
                'code' => '<?php // --taint-analysis
                    $data = $_GET;
                    $data["city"] = htmlspecialchars((string) $data["city"], ENT_QUOTES);
                    echo $data["city"];',
            ],
            'dontTaintTheValuesAWholeArrayHeldUnderKeysAssignedSinceThen' => [
                'code' => '<?php // --taint-analysis
                    $data = $_GET;
                    $data["city"] = htmlspecialchars((string) $data["city"], ENT_QUOTES);
                    $data["zip"] = htmlspecialchars((string) $data["zip"], ENT_QUOTES);
                    $data["city"] = htmlspecialchars($data["city"], ENT_QUOTES);
                    echo $data["city"];
                    echo $data["zip"];',
            ],
            'dontTaintTheValueOfAKeyAWholeArrayDoesNotHave' => [
                'code' => '<?php // --taint-analysis
                    $data = $_GET;
                    if (array_key_exists("city", $data)) {
                        $data["city"] = htmlspecialchars((string) $data["city"], ENT_QUOTES);
                    }
                    echo (string) ($data["city"] ?? "");',
            ],
            'dontTaintTheValueOfAKeyAWholeArrayDoesNotHaveAfterChecksOfOtherKeys' => [
                'code' => '<?php // --taint-analysis
                    $data = $_GET;
                    $hasZip = array_key_exists("zip", $data) ? 1 : 0;
                    $hasStreet = array_key_exists("street", $data) ? 1 : 0;
                    if (array_key_exists("city", $data)) {
                        $data["city"] = htmlspecialchars((string) $data["city"], ENT_QUOTES);
                    }
                    echo (string) ($data["city"] ?? "");',
            ],
            'dontTaintTheValueOfAKeyAWholeArrayDoesNotHaveInALoopThatChecksIt' => [
                'code' => '<?php // --taint-analysis
                    $data = $_GET;
                    foreach ([1, 2] as $_) {
                        if (array_key_exists("city", $data)) {
                            $data["city"] = htmlspecialchars((string) $data["city"], ENT_QUOTES);
                        }
                    }
                    echo (string) ($data["city"] ?? "");',
            ],
            'dontTaintTheValueOfAKeyAWholeArrayHasNotSet' => [
                'code' => '<?php // --taint-analysis
                    $data = $_GET;
                    if (isset($data["city"])) {
                        $data["city"] = htmlspecialchars((string) $data["city"], ENT_QUOTES);
                    }
                    echo (string) ($data["city"] ?? "");',
            ],
            'dontTaintTheValueOfAKeyWholeArraysMappedDoNotHave' => [
                'code' => '<?php // --taint-analysis
                    $items = array_map(
                        function (array $item): array {
                            if (array_key_exists("city", $item)) {
                                $item["city"] = htmlspecialchars((string) $item["city"], ENT_QUOTES);
                            }
                            return $item;
                        },
                        (array) $_GET["items"],
                    );
                    foreach ($items as $item) {
                        echo (string) ($item["city"] ?? "");
                    }',
            ],
            'dontTaintTheValueAWholeArrayHeldUnderAKeyUnsetSinceThen' => [
                'code' => '<?php // --taint-analysis
                    $data = $_GET;
                    unset($data["city"]);
                    echo (string) ($data["city"] ?? "");',
            ],
            'dontTaintTheValueAWholeArrayHeldUnderAKeyAssignedSinceThenOnceWrapped' => [
                'code' => '<?php // --taint-analysis
                    $data = $_GET;
                    $data["city"] = htmlspecialchars((string) $data["city"], ENT_QUOTES);
                    $wrapped = ["data" => $data];
                    echo $wrapped["data"]["city"];',
            ],
            'dontTaintTheValueWholeArraysMappedHeldUnderAKeyAssigned' => [
                'code' => '<?php // --taint-analysis
                    $items = array_map(
                        function (array $item): array {
                            $item["city"] = htmlspecialchars((string) $item["city"], ENT_QUOTES);
                            return $item;
                        },
                        (array) $_GET["items"],
                    );
                    foreach ($items as $item) {
                        echo $item["city"];
                    }',
            ],
            'dontTaintTheValueWholeArraysIteratedOverHeldUnderAKeyAssigned' => [
                'code' => '<?php // --taint-analysis
                    $items = [];
                    foreach ((array) $_GET["items"] as $item) {
                        $item = (array) $item;
                        $item["city"] = htmlspecialchars((string) $item["city"], ENT_QUOTES);
                        $items[] = $item;
                    }
                    foreach ($items as $item) {
                        echo $item["city"];
                    }',
            ],
            'dontTaintArrayItemsOverwrittenByAForeachByReference' => [
                'code' => '<?php // --taint-analysis
                    $values = ["key" => ""];
                    foreach ($values as &$value) {
                        $value = (string) $_GET["value"];
                        $value = "literal";
                    }
                    unset($value);
                    echo $values["key"];',
            ],
            'dontTaintArrayIteratedOverByValue' => [
                'code' => '<?php // --taint-analysis
                    $values = ["key" => ""];
                    foreach ($values as $value) {
                        $value = (string) $_GET["value"];
                    }
                    echo $values["key"];',
            ],
            'dontTaintByRefParamOverwrittenWithLiteral' => [
                'code' => '<?php // --taint-analysis
                    function reset_value(string &$value): void {
                        $value = "literal";
                    }

                    $value = (string) $_GET["value"];
                    reset_value($value);
                    echo $value;',
            ],
            'dontTaintByRefParamWrittenAfterItIsUnset' => [
                'code' => '<?php // --taint-analysis
                    function read_value(string &$value): void {
                        unset($value);
                        $value = (string) $_GET["value"];
                    }

                    $value = "literal";
                    read_value($value);
                    echo $value;',
            ],
            'dontTaintOtherArrayItemThanTheOnePassedByRef' => [
                'code' => '<?php // --taint-analysis
                    function read_value(string &$value): void {
                        $value = (string) $_GET["value"];
                    }

                    $values = ["read" => "", "other" => ""];
                    read_value($values["read"]);
                    echo $values["other"];',
            ],
            'dontTaintACurlOptionArrayEntryNotChoosingTheDestinationNorWrittenIntoTheRequest' => [
                'code' => '<?php // --taint-analysis
                    $value = (string) $_GET["value"];
                    $options = [CURLOPT_URL => "https://example.com/", CURLOPT_POSTFIELDS => $value];
                    $options[CURLOPT_PRIVATE] = $value;
                    curl_setopt_array(curl_init(), $options);',
            ],
            'dontTaintSsrfInACurlOptionNotChoosingTheDestination' => [
                'code' => '<?php // --taint-analysis
                    $curl = curl_init("https://example.com/");
                    curl_setopt($curl, CURLOPT_POSTFIELDS, (string) $_GET["body"]);
                    curl_setopt($curl, \\CURLOPT_PRIVATE, (string) $_GET["private"]);',
            ],
            'dontTaintAnImmutableObjectBuiltFromAnotherCallsArgument' => [
                'code' => '<?php // --taint-analysis
                    /** @psalm-immutable */
                    final class Url {
                        /** @psalm-flow ($url) -> return */
                        public function __construct(string $url) {}
                    }

                    /** @psalm-taint-sink ssrf $url */
                    function fetch(Url $url): void {}

                    $tainted = new Url((string) $_GET["url"]);
                    fetch(new Url("https://example.com/"));',
            ],
            'dontTaintAContainerWithWhatAnotherOneHolds' => [
                'code' => '<?php // --taint-analysis
                    $tainted = new SplQueue();
                    $tainted->enqueue((string) $_GET["a"]);
                    $safe = new SplQueue();
                    $safe->enqueue("safe");
                    echo (string) $safe->dequeue();',
            ],
            'dontTaintTheOtherOffsetsOfAContainer' => [
                'code' => '<?php // --taint-analysis
                    $container = new ArrayObject();
                    $container->offsetSet("a", (string) $_GET["a"]);
                    $container->offsetSet("b", "safe");
                    echo (string) $container->offsetGet("b");',
            ],
            'dontTaintAnObjectBuiltFromAnotherCallsArgument' => [
                'code' => '<?php // --taint-analysis
                    final class Url {
                        /**
                         * @psalm-flow ($url) -> return
                         * @psalm-taint-specialize
                         */
                        public function __construct(string $url) {}
                    }

                    /** @psalm-taint-sink ssrf $url */
                    function fetch(Url $url): void {}

                    $tainted = new Url((string) $_GET["url"]);
                    fetch(new Url("https://example.com/"));',
            ],
            'firstClassCallableOfAStaticMethod' => [
                'code' => '<?php // --taint-analysis
                    final class Formatter {
                        public static function format(string $value): string {
                            return trim($value);
                        }
                    }

                    $format = Formatter::format(...);
                    echo $format("safe");',
            ],
            'dontTaintTheOverridesOfSiblingsThroughACallOfTheParentMethod' => [
                'code' => '<?php // --taint-analysis
                    abstract class Mapper {
                        public function findBy(array $query): void {}
                    }

                    final class ModerationMapper extends Mapper {
                        public function findBy(array $query): void {
                            parent::findBy($query);
                        }
                    }

                    final class ArticleMapper extends Mapper {
                        public function findBy(array $query): void {
                            echo (string) $query["title"];
                        }
                    }

                    (new ModerationMapper())->findBy($_GET);',
            ],
            'dontTaintAnOverrideThroughACallOnASiblingClass' => [
                'code' => '<?php // --taint-analysis
                    abstract class Mapper {
                        public function find(string $id): string {
                            return $id;
                        }
                    }

                    final class CommentMapper extends Mapper {
                        public function find(string $id): string {
                            echo $id;
                            return $id;
                        }
                    }

                    final class TownMapper extends Mapper {}

                    function findTown(TownMapper $mapper): void {
                        $mapper->find((string) $_GET["id"]);
                    }

                    function findAny(Mapper $mapper, string $id): void {
                        $mapper->find($id);
                    }',
            ],
            'dontTaintAnOverrideThroughACallOnASiblingOfAnIntermediateClass' => [
                'code' => '<?php // --taint-analysis
                    abstract class Mapper {
                        public function find(string $id): string {
                            return $id;
                        }
                    }

                    abstract class SiteMapper extends Mapper {}

                    final class TownMapper extends SiteMapper {
                        public function find(string $id): string {
                            return parent::find($id);
                        }
                    }

                    final class CommentMapper extends SiteMapper {
                        public function find(string $id): string {
                            echo $id;
                            return $id;
                        }
                    }

                    function findTown(TownMapper $mapper): void {
                        $mapper->find((string) $_GET["id"]);
                    }

                    function findAny(SiteMapper $mapper, string $id): void {
                        $mapper->find($id);
                    }',
            ],
            'dontTaintAStaticOverrideThroughACallNamingAnIntermediateClass' => [
                'code' => '<?php // --taint-analysis
                    abstract class Mapper {
                        public static function create(string $id): string {
                            return $id;
                        }
                    }

                    abstract class SiteMapper extends Mapper {}

                    final class CommentMapper extends SiteMapper {
                        public static function create(string $id): string {
                            echo $id;
                            return $id;
                        }
                    }

                    function createSite(): void {
                        SiteMapper::create((string) $_GET["id"]);
                    }

                    function createAny(SiteMapper $mapper, string $id): void {
                        $mapper::create($id);
                    }',
            ],
            'firstClassCallableOfTaintPropagatingFunction' => [
                'code' => '<?php
                    function f(string $s): array {
                        return array_map(trim(...), explode("|", $s));
                    }',
            ],
            'firstClassCallableOfPregReplace' => [
                'code' => '<?php
                    function f(array $patterns, array $replacements, array $subjects): array {
                        return array_map(preg_replace(...), $patterns, $replacements, $subjects);
                    }',
              ],
            'ambiguousFirstClassCallableWithoutTaintNotReported' => [
                'code' => '<?php
                    function ambig_plain_a(): string { return "a"; }
                    function ambig_plain_b(int $x = 0): string { return (string) $x; }

                    $f = rand(0, 1) ? ambig_plain_a(...) : ambig_plain_b(...);
                    echo $f();',
            ],
            'sanitizedArrayValueNotReported' => [
                'code' => '<?php
                    $arr = [];
                    $arr["evil"] = (string) $_GET["x"];
                    $arr["safe"] = htmlspecialchars((string) $_GET["y"], ENT_QUOTES);

                    echo $arr["safe"];',
            ],
            'noTaintNamedArgumentToSafeParameter' => [
                'code' => '<?php // --taint-analysis
                    /** @psalm-taint-sink html $dangerous */
                    function mySink(string $safe, string $dangerous) : void {}

                    $tainted = (string) $_GET["x"];
                    mySink(safe: $tainted, dangerous: "ok");',
            ],
            'untaintedRecursiveFunction' => [
                'code' => '<?php
                    function f(string $s, int $depth): string {
                        if ($depth > 0) {
                            return f($s, $depth - 1);
                        }

                        return $s;
                    }

                    echo f("safe", (int) $_GET["depth"]);',
            ],
            'untaintedMutualRecursionWithSanitizer' => [
                'code' => '<?php
                    function a(string $s): string {
                        if (rand(0, 1)) {
                            return b($s);
                        }

                        return (string) (int) $s;
                    }

                    function b(string $s): string {
                        if (rand(0, 1)) {
                            return a($s);
                        }

                        return (string) (int) $s;
                    }

                    echo a((string) $_GET["a"]);',
            ],
            'firstClassCallableOfTaintPropagatingFunction2' => [
                'code' => '<?php
                    // CallLike::getArgs() asserts !isFirstClassCallable(); a taint-propagating
                    // function referenced as a first-class callable must not reach it.
                    $fn = strtolower(...);
                    echo $fn("safe");',
            ],
            'firstClassCallableOfMultiArgTaintPropagatingFunction' => [
                'code' => '<?php
                    $fn = str_replace(...);
                    echo $fn("a", "b", "safe");',
            ],
            'taintedInputInCreatedArrayNotEchoed' => [
                'code' => '<?php
                    $name = $_GET["name"] ?? "unknown";
                    $id = (int) $_GET["id"];

                    $data = ["name" => $name, "id" => $id];

                    echo "<h1>" . htmlentities($data["name"], \ENT_QUOTES) . "</h1>";
                    echo "<p>" . $data["id"] . "</p>";',
            ],
            'taintedInputInAssignedArrayNotEchoed' => [
                'code' => '<?php
                    $name = $_GET["name"] ?? "unknown";
                    $id = (int) $_GET["id"];

                    $data = [];
                    $data["name"] = $name;
                    $data["id"] = $id;

                    echo "<h1>" . htmlentities($data["name"], \ENT_QUOTES) . "</h1>";
                    echo "<p>" . $data["id"] . "</p>";',
            ],
            'taintedInputDirectlySuppressed' => [
                'code' => '<?php
                    class A {
                        public function deleteUser(PDO $pdo) : void {
                            /** @psalm-taint-escape sql */
                            $userId = (string) $_GET["user_id"];
                            $pdo->exec("delete from users where user_id = " . $userId);
                        }
                    }',
            ],
            'taintedInputDirectlySuppressedWithOtherUse' => [
                'code' => '<?php
                    class A {
                        public function deleteUser(PDOWrapper $pdo) : void {
                            /**
                             * @psalm-taint-escape sql
                             */
                            $userId = (string) $_GET["user_id"];
                            $pdo->exec("delete from users where user_id = " . $userId);
                        }

                        public function deleteUserSafer(PDOWrapper $pdo) : void {
                            $userId = $this->getSafeId();
                            $pdo->exec("delete from users where user_id = " . $userId);
                        }

                        public function getSafeId() : string {
                            return "5";
                        }
                    }

                    class PDOWrapper {
                        /**
                         * @psalm-taint-sink sql $sql
                         */
                        public function exec(string $sql) : void {}
                    }',
            ],
            'llmPromptWithSafeInput' => [
                'code' => '<?php
                    class LlmAgent {
                        /**
                         * @psalm-taint-sink llm_prompt $prompt
                         */
                        public function prompt(string $prompt): string {
                            return "";
                        }
                    }

                    $agent = new LlmAgent();
                    $agent->prompt("Summarize this document");',
            ],
            'llmPromptEscaped' => [
                'code' => '<?php
                    class LlmAgent {
                        /** @psalm-taint-sink llm_prompt $prompt */
                        public function prompt(string $prompt): string {
                            return "";
                        }
                    }

                    /** @psalm-taint-escape llm_prompt */
                    function sanitize_for_llm(string $input): string {
                        return $input;
                    }

                    $agent = new LlmAgent();
                    $agent->prompt(sanitize_for_llm((string) $_GET["question"]));',
            ],
            'nosqlSinkNotTaintedByString' => [
                'code' => '<?php
                    /** @psalm-taint-sink nosql $filter */
                    function query($filter): void {}

                    // a plain string can never be a NoSQL query, so this is safe
                    query((string) $_GET["username"]);',
            ],
            'nosqlSinkNotTaintedByScalarNarrowing' => [
                'code' => '<?php
                    /** @psalm-taint-sink nosql $filter */
                    function query($filter): void {}

                    /** @psalm-taint-source input */
                    function input(): mixed {
                        return null;
                    }

                    /**
                     * @psalm-taint-source input
                     * @return array-key
                     */
                    function inputKey(): int|string {
                        return 0;
                    }

                    // scalar, array-key and numeric values can never be a NoSQL query document
                    $scalar = input();
                    if (is_scalar($scalar)) {
                        query(["name" => $scalar]);
                    }
                    query(["name" => inputKey()]);
                    $number = input();
                    if (is_numeric($number)) {
                        query(["name" => $number]);
                    }',
            ],
            'nosqlSinkNotTaintedByScalarNarrowingWhereItsTypeMixesWithAnObject' => [
                'code' => '<?php
                    final class Regex {
                        public function __construct(public string $pattern) {}
                    }

                    /** @psalm-taint-sink nosql $filter */
                    function query(array $filter): void {}

                    /** @psalm-taint-source input */
                    function input(): mixed {
                        return null;
                    }

                    // the value narrowed to a scalar holds no NoSQL query document, whatever its item mixes with
                    $value = input();
                    if (is_scalar($value)) {
                        query(["name" => rand(0, 1) ? ["\$regex" => new Regex((string) $value)] : $value]);
                    }',
            ],
            'nosqlSinkNotTaintedByIntCast' => [
                'code' => '<?php
                    /** @psalm-taint-sink nosql $filter */
                    function query($filter): void {}

                    // an int can never be a NoSQL query document
                    query((int) $_GET["username"]);',
            ],
            'nosqlSinkNotTaintedByFloatCast' => [
                'code' => '<?php
                    /** @psalm-taint-sink nosql $filter */
                    function query($filter): void {}

                    query((float) $_GET["username"]);',
            ],
            'nosqlSinkNotTaintedByBoolCast' => [
                'code' => '<?php
                    /** @psalm-taint-sink nosql $filter */
                    function query($filter): void {}

                    query((bool) $_GET["username"]);',
            ],
            'nosqlSinkNotTaintedByTheEqualityOperandOfAField' => [
                'code' => '<?php
                    final class Collection {
                        /** @psalm-taint-sink nosql $filter */
                        public function find(array $filter): void {}
                    }

                    /** @psalm-taint-sink nosql $filter */
                    function query(array $filter): void {}

                    final class Users {
                        private const NAME = "name";

                        public static function search(Collection $users): void {
                            // MongoDB compares the field with what $eq is given as it is, operators and regexes included
                            $users->find(["name" => [\'$eq\' => $_GET["name"]]]);
                            $users->find([self::NAME => [\'$eq\' => $_GET["name"]], "age" => [\'$gt\' => 18]]);
                            query(["name" => [\'$eq\' => $_GET["name"]]]);
                        }
                    }',
            ],
            'nosqlFilterEscapedByIntCast' => [
                'code' => '<?php
                    function getUser(): MongoDB\Driver\Query {
                        // casting the value to int inside the filter means it can no
                        // longer be an injected operator like ["$ne" => null]
                        return new MongoDB\Driver\Query(["age" => (int) $_GET["age"]]);
                    }',
            ],
            'nosqlFilterEscapedByFloatCast' => [
                'code' => '<?php
                    function getUser(): MongoDB\Driver\Query {
                        return new MongoDB\Driver\Query(["lat" => (float) $_GET["lat"]]);
                    }',
            ],
            'nosqlFilterEscapedByBoolCast' => [
                'code' => '<?php
                    function getUser(): MongoDB\Driver\Query {
                        return new MongoDB\Driver\Query(["active" => (bool) $_GET["active"]]);
                    }',
            ],
            'sqlSinkNotTaintedByIntCast' => [
                'code' => '<?php
                    /** @psalm-taint-sink sql $q */
                    function query($q): void {}

                    // an int can never carry a SQL injection
                    query((int) $_GET["id"]);',
            ],
            'sqlSinkNotTaintedByFloatCast' => [
                'code' => '<?php
                    /** @psalm-taint-sink sql $q */
                    function query($q): void {}

                    query((float) $_GET["id"]);',
            ],
            'htmlSinkNotTaintedByBoolCast' => [
                'code' => '<?php
                    echo (bool) $_GET["flag"];',
            ],
            'nosqlFilterEscapedByStringCast' => [
                'code' => '<?php
                    function getUser(): MongoDB\Driver\Query {
                        // casting to string forces a literal match: the value can no
                        // longer be an injected operator like ["$ne" => null]
                        return new MongoDB\Driver\Query(["username" => (string) $_GET["username"]]);
                    }',
            ],
            'nosqlFilterEscapedBySanitizer' => [
                'code' => '<?php
                    /**
                     * Forces every filter value to a scalar so an attacker cannot inject
                     * query operators such as ["$ne" => null] through array-valued input.
                     *
                     * @param array<string, mixed> $filter
                     * @return array<string, string>
                     * @psalm-taint-escape nosql
                     */
                    function sanitize_mongo_filter(array $filter): array {
                        $safe = [];
                        foreach ($filter as $field => $value) {
                            if (is_array($value)) {
                                throw new InvalidArgumentException("Filter values must be scalar");
                            }
                            $safe[$field] = (string) $value;
                        }
                        return $safe;
                    }

                    function getUser(): MongoDB\Driver\Query {
                        $filter = sanitize_mongo_filter(["username" => $_GET["username"]]);
                        return new MongoDB\Driver\Query($filter);
                    }',
            ],
            'nosqlSinkNotTaintedBySourceReturningString' => [
                'code' => '<?php
                    /** @psalm-taint-sink nosql $filter */
                    function query($filter): void {}

                    final class Request {
                        /** @psalm-taint-source input */
                        public function getString(string $name): string {
                            return "";
                        }

                        /** @psalm-taint-source input */
                        public static function getStaticString(string $name): ?string {
                            return null;
                        }
                    }

                    // a source declared to return a string can never return a NoSQL query
                    query(["username" => (new Request())->getString("username")]);
                    query(["username" => Request::getStaticString("username")]);',
            ],
            'nosqlSinkNotTaintedByFunctionReturningString' => [
                'code' => '<?php
                    /** @psalm-taint-sink nosql $filter */
                    function query($filter): void {}

                    function first(array $values): ?string {
                        /** @var string|null */
                        return $values[0] ?? null;
                    }

                    // PHP enforces the native return type: the result is never an array
                    query(["name" => first((array) $_GET["names"])]);',
            ],
            'flowFromThisOnlyFromItsOwnReceiver' => [
                'code' => '<?php
                    interface Message {
                        /**
                         * @psalm-taint-specialize
                         * @psalm-flow ($this) -> return
                         */
                        public function getBody(): string;
                    }

                    /** @psalm-taint-source input */
                    function fetch(): Message {
                        throw new RuntimeException();
                    }

                    function build(): Message {
                        throw new RuntimeException();
                    }

                    $fetched = fetch();
                    $built = build();
                    $fetched->getBody();
                    echo $built->getBody();',
            ],
            'noFlowFromThisWithoutAnnotation' => [
                'code' => '<?php
                    interface Message {
                        public function getBody(): string;
                    }

                    /** @psalm-taint-source input */
                    function fetch(): Message {
                        throw new RuntimeException();
                    }

                    echo fetch()->getBody();',
            ],
            'flowFromThisKeepsWhatTheReturnTypeHolds' => [
                'code' => '<?php
                    interface Message {
                        /** @psalm-flow ($this) -> return */
                        public function getStatusCode(): int;
                    }

                    /** @psalm-taint-source input */
                    function fetch(): Message {
                        throw new RuntimeException();
                    }

                    echo fetch()->getStatusCode();',
            ],
            'nosqlSinkNotTaintedByDecodedSecret' => [
                'code' => '<?php
                    /** @psalm-taint-sink nosql $filter */
                    function query(mixed $filter): void {}

                    /** @psalm-taint-source system_secret */
                    function secret(): string {
                        return "";
                    }

                    // only decoded user input becomes a NoSQL query again
                    query(json_decode(secret(), true));',
            ],
            'nosqlSinkNotTaintedByDecodedLiteral' => [
                'code' => '<?php
                    /** @psalm-taint-sink nosql $filter */
                    function query(mixed $filter): void {}

                    query(json_decode("{\\"name\\": \\"x\\"}", true));',
            ],
            'nosqlSinkNotTaintedByFlowIntoStringReturn' => [
                'code' => '<?php
                    /** @psalm-taint-sink nosql $filter */
                    function query($filter): void {}

                    /** @psalm-flow ($value) -> return */
                    function describe(mixed $value): string {
                        return "";
                    }

                    // what flows into a string return is still a string
                    query(["name" => describe($_GET["name"])]);',
            ],
            'nullableTemplateArgumentKeepsItsTaints' => [
                'code' => '<?php
                    /**
                     * @template T as ?string
                     * @param T $value
                     */
                    function forward(?string $value): void {
                        consume($value);
                    }

                    function consume(?string $value): void {}

                    forward($_GET["name"] ?? null);',
            ],
            'flowFromCallableResultOnlyIntoItsOwnCall' => [
                'code' => '<?php
                    interface Cache {
                        /**
                         * @template T
                         * @param callable(): T $compute
                         * @return T
                         * @psalm-taint-specialize
                         * @psalm-flow ($compute()) -> return
                         */
                        public function get(callable $compute): mixed;
                    }

                    function store(Cache $cache): void {
                        $cache->get(fn(): string => (string) $_GET["x"]);
                    }

                    function show(Cache $cache): void {
                        echo $cache->get(fn(): string => "safe");
                    }',
            ],
            'noFlowFromTheResultOfACallableNotInTheFlow' => [
                'code' => '<?php
                    interface Cache {
                        /**
                         * @psalm-flow ($compute()) -> return
                         */
                        public function get(callable $compute, callable $onMiss): string;
                    }

                    function show(Cache $cache): void {
                        echo $cache->get(fn(): string => "safe", fn(): string => (string) $_GET["x"]);
                    }',
            ],
            'noFlowFromCallableResultWithoutAnnotation' => [
                'code' => '<?php
                    interface Cache {
                        /**
                         * @template T
                         * @param callable(): T $compute
                         * @return T
                         */
                        public function get(callable $compute): mixed;
                    }

                    function show(Cache $cache): void {
                        echo $cache->get(fn(): string => (string) $_GET["x"]);
                    }',
            ],
            'htmlSinkNotTaintedBySourceReturningInt' => [
                'code' => '<?php
                    final class Request {
                        /** @psalm-taint-source input */
                        public function getInt(string $name): int {
                            return 0;
                        }
                    }

                    // an int can only carry the taints a number can
                    echo "<b>" . (new Request())->getInt("id") . "</b>";',
            ],
            'taintedInputToParamButSafe' => [
                'code' => '<?php
                    class A {
                        public function getUserId(PDO $pdo) : void {
                            $this->deleteUser(
                                $pdo,
                                $this->getAppendedUserId((string) $_GET["user_id"])
                            );
                        }

                        public function getAppendedUserId(string $user_id) : string {
                            return "aaa" . $user_id;
                        }

                        public function deleteUser(PDO $pdo, string $userId) : void {
                            $userId2 = strlen($userId);
                            $pdo->exec("delete from users where user_id = " . $userId2);
                        }
                    }',
            ],
            'ValidatedInputFromParam' => [
                'code' => '<?php
                    /**
                     * @psalm-assert-untainted $userId
                     */
                    function validateUserId(string $userId) : void {
                        if (!is_numeric($userId)) {
                            throw new \Exception("bad");
                        }
                    }

                    class A {
                        public function getUserId() : string {
                            return (string) $_GET["user_id"];
                        }

                        public function doDelete(PDO $pdo) : void {
                            $userId = $this->getUserId();
                            validateUserId($userId);
                            $this->deleteUser($pdo, $userId);
                        }

                        public function deleteUser(PDO $pdo, string $userId) : void {
                            $pdo->exec("delete from users where user_id = " . $userId);
                        }
                    }',
            ],
            'untaintedInputAfterIntCast' => [
                'code' => '<?php
                    class A {
                        public function getUserId() : int {
                            return (int) $_GET["user_id"];
                        }

                        public function getAppendedUserId() : string {
                            return "aaaa" . $this->getUserId();
                        }

                        public function deleteUser(PDO $pdo) : void {
                            $userId = $this->getAppendedUserId();
                            $pdo->exec("delete from users where user_id = " . $userId);
                        }
                    }',
            ],
            'specializedCoreFunctionCall' => [
                'code' => '<?php
                    $a = (string) ($data["user_id"] ?? "");

                    echo print_r([], true);

                    $b = print_r($a, true);',
            ],
            'untaintedInputViaStaticFunctionWithSafePath' => [
                'code' => '<?php
                    class Utils {
                        /**
                         * @psalm-pure
                         */
                        public static function shorten(string $str) : string {
                            return $str;
                        }
                    }

                    class A {
                        public function foo() : void {
                            echo(htmlentities(Utils::shorten((string) $_GET["user_id"]), \ENT_QUOTES));
                        }

                        public function bar() : void {
                            echo(Utils::shorten("hello"));
                        }
                    }',
            ],
            'taintHtmlEntities' => [
                'code' => '<?php
                    function foo() : void {
                        $a = htmlentities((string) $_GET["bad"], \ENT_QUOTES);
                        echo $a;
                    }',
            ],
            'jsonEncodeWithHexTagAndAmpEscapesHtml' => [
                'code' => '<?php
                    /** @psalm-taint-sink html $html */
                    function html(string $html): void {}

                    html(json_encode($_GET["x"], JSON_HEX_TAG | JSON_HEX_AMP | JSON_THROW_ON_ERROR));
                    html(json_encode(["k" => $_GET["x"]], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
                    html(json_encode(flags: JSON_HEX_TAG | JSON_HEX_AMP | JSON_THROW_ON_ERROR, value: $_GET["x"]));',
            ],
            'jsonEncodeQualifiedOrImportedWithHexTagAndAmpEscapesHtml' => [
                'code' => '<?php
                    namespace Foo;

                    use function json_encode as encode;

                    /** @psalm-taint-sink html $html */
                    function html(string $html): void {}

                    html(\json_encode($_GET["x"], \JSON_HEX_TAG | \JSON_HEX_AMP | \JSON_THROW_ON_ERROR));
                    html(json_encode($_GET["x"], \JSON_HEX_TAG | \JSON_HEX_AMP | \JSON_THROW_ON_ERROR));
                    html(encode($_GET["x"], \JSON_HEX_TAG | \JSON_HEX_AMP | \JSON_THROW_ON_ERROR));',
            ],
            'taintFilterVar' => [
                'code' => '<?php
                    /** @psalm-taint-sink input_except_sleep $value */
                    function taintSink(mixed $value): void {}

                    taintSink(filter_var($_GET["bad"], FILTER_VALIDATE_INT));
                    taintSink(filter_var($_GET["bad"], FILTER_VALIDATE_BOOLEAN));
                    taintSink(filter_var($_GET["bad"], FILTER_VALIDATE_FLOAT));
                    taintSink(filter_var($_GET["bad"], FILTER_SANITIZE_NUMBER_INT));
                    taintSink(filter_var($_GET["bad"], FILTER_SANITIZE_NUMBER_FLOAT));
                ',
            ],
            'taintLdapEscape' => [
                'code' => '<?php
                    $ds = ldap_connect(\'example.com\');
                    $dn = \'o=Psalm, c=US\';
                    $filter = ldap_escape($_GET[\'filter\']);
                    ldap_search($ds, $dn, $filter, []);',
            ],
            'taintOnStrReplaceCallRemovedInFunction' => [
                'code' => '<?php
                    class U {
                        /**
                         * @psalm-pure
                         * @psalm-taint-escape html
                         * @psalm-taint-escape has_quotes
                         */
                        public static function shorten(string $s) : string {
                            return str_replace("foo", "bar", $s);
                        }
                    }

                    class V {}

                    class O1 {
                        public string $s;

                        public function __construct() {
                            $this->s = (string) $_GET["FOO"];
                        }
                    }

                    class V1 extends V {
                        public function foo(O1 $o) : void {
                            echo U::shorten($o->s);
                        }
                    }',
            ],
            'taintOnPregReplaceCallRemovedInFunction' => [
                'code' => '<?php
                    class U {
                        /**
                         * @psalm-pure
                         */
                        public static function shorten(string $s) : string {
                            return preg_replace("/[^_a-z\/\.A-Z0-9]/", "bar", $s);
                        }
                    }

                    class V {}

                    class O1 {
                        public string $s;

                        public function __construct() {
                            $this->s = (string) $_GET["FOO"];
                        }
                    }

                    class V1 extends V {
                        public function foo(O1 $o) : void {
                            echo U::shorten($o->s);
                        }
                    }',
            ],
            'taintOnStrReplaceCallRemovedInline' => [
                'code' => '<?php
                    class V {}

                    class O1 {
                        public string $s;

                        public function __construct() {
                            $this->s = (string) $_GET["FOO"];
                        }
                    }

                    class V1 extends V {
                        public function foo(O1 $o) : void {
                            /**
                             * @psalm-taint-escape html
                             * @psalm-taint-escape has_quotes
                             */
                            $a = str_replace("foo", "bar", $o->s);
                            echo $a;
                        }
                    }',
            ],
            'NoTaintsOnSimilarPureCall' => [
                'code' => '<?php
                    class U {
                        /** @psalm-pure */
                        public static function shorten(string $s) : string {
                            return substr($s, 0, 15);
                        }

                        /** @psalm-pure */
                        public static function escape(string $s) : string {
                            return htmlentities($s, \ENT_QUOTES);
                        }
                    }

                    class O1 {
                        public string $s;

                        public function __construct(string $s) {
                            $this->s = $s;
                        }
                    }

                    class O2 {
                        public string $t;

                        public function __construct() {
                            $this->t = (string) $_GET["FOO"];
                        }
                    }

                    class V1 {
                        public function foo() : void {
                            $o = new O1((string) $_GET["FOO"]);
                            echo U::escape(U::shorten($o->s));
                        }
                    }

                    class V2 {
                        public function foo(O2 $o) : void {
                            echo U::shorten(U::escape($o->t));
                        }
                    }',
            ],
            'taintPropertyPassingObjectWithDifferentValue' => [
                'code' => '<?phps
                    /** @psalm-immutable */
                    class User {
                        public string $id;
                        public $name = "Luke";

                        public function __construct(string $userId) {
                            $this->id = $userId;
                        }
                    }

                    class UserUpdater {
                        public static function doDelete(PDO $pdo, User $user) : void {
                            self::deleteUser($pdo, $user->name);
                        }

                        public static function deleteUser(PDO $pdo, string $userId) : void {
                            $pdo->exec("delete from users where user_id = " . $userId);
                        }
                    }

                    $userObj = new User((string) $_GET["user_id"]);
                    UserUpdater::doDelete(new PDO("t"), $userObj);',
            ],
            'taintPropertyWithoutPassingObject' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    class User {
                        public string $id;

                        public function __construct(string $userId) {
                            $this->id = $userId;
                        }

                        public function withId(string $userId) : static {
                            $new = clone $this;
                            $new->id = $userId;
                            return $new;
                        }
                    }

                    function echoId(User $u2) : void {
                        echo $u2->id;
                    }

                    $u = new User("5");
                    echoId($u);
                    $v = $u->withId($_GET["user_id"]);',
            ],
            'specializeStaticMethod' => [
                'code' => '<?php
                    StringUtility::foo($_GET["c"]);

                    class StringUtility {
                        /**
                         * @psalm-taint-specialize
                         */
                        public static function foo(string $str) : string
                        {
                            return $str;
                        }

                        /**
                         * @psalm-taint-specialize
                         */
                        public static function slugify(string $url) : string {
                            return self::foo($url);
                        }
                    }

                    echo StringUtility::slugify("hello");',
            ],
            'taintFreeNestedArray' => [
                'code' => '<?php
                    $a = [];
                    $a[] = ["a" => $_GET["name"], "b" => "foo"];

                    foreach ($a as $m) {
                        echo $m["b"];
                    }',
            ],
            'taintFreeNestedArrayWithOffsetAccessedExplicitly' => [
                'code' => '<?php
                    $a = [];
                    $a[] = ["a" => $_GET["name"], "b" => "foo"];

                    echo $a[0]["b"];',
            ],
            'dontTaintSpecializedInstanceProperty' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    class StringHolder {
                        public $x;

                        public function __construct(string $x) {
                            $this->x = $x;
                        }
                    }

                    $a = new StringHolder("a");
                    $b = new StringHolder($_GET["x"]);

                    echo $a->x;',
            ],
            'dontReportCallingAClosureBuiltFromInput' => [
                'code' => '<?php
                    /** @var mixed $value */
                    $value = $_GET["value"];
                    if ($value instanceof Closure) {
                        $value();
                    }

                    $closure = fn(): string => (string) $_GET["value"];
                    $closure();',
            ],
            'dontTaintMethodCallOnNewSpecializedInstanceWithOtherInstances' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    final class Tag {
                        public function __construct(private string $name, private string $content) {}

                        public function render(): string {
                            return "<" . $this->name . ">" . $this->content;
                        }

                        public function getName(): string {
                            return $this->name;
                        }
                    }

                    $tainted = new Tag("a", (string) $_GET["x"]);
                    $html = (new Tag("b", (string) $_GET["y"]))->render();
                    echo $tainted->getName();
                    echo (new Tag("c", (string) $_GET["z"]))->getName();
                    echo (new Tag("d", "safe"))->render();',
            ],
            'dontTaintValidatedValueStoredInProperty' => [
                'code' => '<?php
                    final class Organization {
                        public string $city = "";
                    }

                    /** @psalm-assert-if-true literal-string $city */
                    function isKnownCity(string $city): bool {
                        return in_array($city, ["moscow", "spb"], true);
                    }

                    $organization = new Organization();
                    $city = (string) $_GET["city"];
                    if (isKnownCity($city)) {
                        $organization->city = $city;
                    }
                    echo $organization->city;',
            ],
            'dontTaintAQueryWithAStringItem' => [
                'code' => '<?php // --taint-analysis
                    /** @psalm-taint-sink nosql $query */
                    function find(array $query): void {}

                    function findOthersInCity(object $id): void {
                        $city = $_GET["city"];
                        if (is_string($city)) {
                            find(["city" => $city, "_id" => [\'$ne\' => $id]]);
                        }
                    }',
            ],
            'dontTaintValidatedValueOrItsReplacement' => [
                'code' => '<?php
                    /** @psalm-assert-if-true literal-string $city */
                    function isKnownCity(string $city): bool {
                        return in_array($city, ["moscow", "spb"], true);
                    }

                    function getDefaultCity(): string {
                        return (string) getenv("DEFAULT_CITY");
                    }

                    $city = (string) $_GET["city"];
                    if (!isKnownCity($city)) {
                        $city = getDefaultCity();
                    }
                    echo $city;',
            ],
            'dontTaintValidatedValueAddedToArray' => [
                'code' => '<?php
                    /** @psalm-assert-if-true literal-string $city */
                    function isKnownCity(string $city): bool {
                        return in_array($city, ["moscow", "spb"], true);
                    }

                    $city = (string) $_GET["city"];
                    if (!isKnownCity($city)) {
                        throw new RuntimeException("Unknown city");
                    }

                    /** @var list<string> $cities */
                    $cities = [];
                    $cities[] = $city;
                    echo implode(",", $cities);',
            ],
            'dontTaintTheOtherColumnsOfTheRowsArrayColumnIsGiven' => [
                'code' => '<?php
                    /** @param list<array{name: string, color: string}> $rows */
                    function printColors(array $rows): void {
                        foreach (array_column($rows, "color") as $color) {
                            echo $color;
                        }

                        foreach (array_column($rows, "color", "color") as $key => $_) {
                            echo $key;
                        }

                        foreach (array_column($rows, "name") as $key => $_) {
                            echo $key;
                        }
                    }

                    printColors([["name" => (string) $_GET["name"], "color" => "red"]]);',
            ],
            'dontTaintTheSubjectOfAMatchWithoutDefaultPastIt' => [
                'code' => '<?php
                    $type = (string) $_GET["type"];
                    $id = match ($type) {
                        "organization" => 1,
                        "prof" => 2,
                    };
                    echo $type;',
            ],
            'dontTaintAValueAssertedToBeAKeyOfAConstant' => [
                'code' => '<?php
                    final class OwnerTypes {
                        public const ALLOWED = ["organization" => true, "prof" => true];
                    }

                    /** @psalm-assert key-of<OwnerTypes::ALLOWED> $type */
                    function assertOwnerType(string $type): void {
                        if (!array_key_exists($type, OwnerTypes::ALLOWED)) {
                            throw new InvalidArgumentException("unknown owner type");
                        }
                    }

                    $type = (string) $_GET["type"];
                    assertOwnerType($type);
                    echo $type;',
            ],
            'dontTaintLiteralStringType' => [
                'code' => '<?php
                    /** @var "asc"|"desc" $direction */
                    $direction = $_GET["direction"];
                    echo $direction;',
            ],
            'dontTaintListOfInts' => [
                'code' => '<?php
                    /** @var list<int> $ids */
                    $ids = $_GET["ids"];
                    echo implode(",", $ids);',
            ],
            'dontTaintReturnedArrayShapeOfFloats' => [
                'code' => '<?php
                    /** @return array{lat: float, lon: float} */
                    function getCoordinates(): array {
                        /** @var array{lat: float, lon: float} */
                        $coordinates = $_GET["coordinates"];
                        return $coordinates;
                    }

                    echo implode(",", getCoordinates());',
            ],
            'dontTaintCallsOfImpureBuiltinFromOtherCalls' => [
                'code' => '<?php
                    /** @var array<string> $tainted */
                    $tainted = $_GET["x"];
                    $first = reset($tainted);
                    $safe = ["safe"];
                    echo reset($safe);
                    $date = new DateTime();
                    $formatted = $date->format((string) $_GET["format"]);
                    echo $date->format("Y");',
            ],
            'rawUrlEncodeEscapesHtml' => [
                'code' => '<?php
                    echo rawurlencode((string) $_GET["x"]);
                    echo http_build_query(["x" => $_GET["x"]]);
                    header("Location: /?q=" . urlencode((string) $_GET["q"]));
                    header("Location: /?" . http_build_query(["q" => $_GET["q"]]));',
            ],
            'escapeShellArgEscapesShell' => [
                'code' => '<?php
                    exec("ls " . escapeshellarg((string) $_GET["x"]));',
            ],
            'dontTaintOtherCallsOfABuiltinStaticMethod' => [
                'code' => '<?php
                    $tainted = Normalizer::normalize((string) $_GET["x"]);
                    echo (string) Normalizer::normalize("safe");',
            ],
            'dontTaintWhatAnArrayMapClosureReturnsWithoutItsParam' => [
                'code' => '<?php
                    /** @psalm-suppress MissingClosureParamType */
                    echo implode(",", array_map(fn($s) => "safe", $_GET["a"]));',
            ],
            'dontTaintPromotedPropertyOfOtherInstance' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    final class Holder {
                        public function __construct(public string $value) {}
                    }

                    $tainted = new Holder((string) $_GET["x"]);
                    $safe = new Holder("safe");
                    echo $safe->value;',
            ],
            'dontReportOutputOfPrintRAndVarExportReturningIt' => [
                'code' => '<?php
                    $exported = var_export($_GET["x"], true);
                    $printed = print_r($_GET["x"], true);
                    $named = print_r(value: $_GET["x"], return: true);',
            ],
            'dontTaintAClosureMadeFromAClosure' => [
                'code' => '<?php
                    $v = (string) $_GET["v"];
                    $a = Closure::fromCallable("strlen");
                    $b = Closure::fromCallable(function () use ($v): string { return $v; });
                    $c = Closure::fromCallable(fn(): string => $v);
                    $a("a");
                    $b();
                    $c();',
            ],
            'dontTaintServerEntriesOfTheServer' => [
                'code' => '<?php
                    include ($_SERVER["DOCUMENT_ROOT"] ?? "") . "/config.php";
                    echo $_SERVER["SERVER_PROTOCOL"] ?? "";
                    echo $_SERVER["REDIRECT_STATUS"] ?? "";',
            ],
            'dontTaintAParameterNamedArgv' => [
                'code' => '<?php
                    function first(array $argv): void {
                        echo (string) $argv[0];
                    }',
            ],
            'dontTaintUploadedFileTemporaryName' => [
                'code' => '<?php
                    move_uploaded_file($_FILES["upload"]["tmp_name"], "/tmp/upload");',
            ],
            'exceptionsAreNotSpecialized' => [
                'code' => '<?php
                    final class MyException extends Exception {
                        private static int $count = 0;

                        public function setMessage(string $message): void {
                            $this->message = $message;
                            self::$count++;
                        }
                    }',
            ],
            'dontTaintSpecializedInstanceStoredInAnotherWithOtherInstances' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    final class A {
                        public function __construct(public string $x) {}

                        public function getX(): string {
                            return $this->x;
                        }
                    }

                    /** @psalm-taint-specialize */
                    final class B {
                        public function __construct(public A $a) {}

                        public function getX(): string {
                            return $this->a->getX();
                        }
                    }

                    $b1 = new B(new A("safe"));
                    $b2 = new B(new A((string) $_GET["x"]));
                    $a = new A("safe");
                    $b3 = new B($a);
                    $other = new A((string) $_GET["y"]);

                    echo $b1->getX();
                    echo $b1->a->x;
                    echo $b1->a->getX();
                    echo $b3->getX();
                    echo $b3->a->x;
                    echo $a->getX();',
            ],
            'dontTaintSpecializedInstancePropertyOfChildClass' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    class StringHolder {
                        public string $x = "";
                    }

                    /** @psalm-taint-specialize */
                    final class ChildStringHolder extends StringHolder {
                        public function __construct(string $x) {
                            $this->x = $x;
                        }
                    }

                    $a = new ChildStringHolder("a");
                    $b = new ChildStringHolder($_GET["x"]);

                    echo $a->x;',
            ],
            'dontTaintSpecializedCallWithWhatAnotherPassesThroughItDirectly' => [
                // what the calls leave in the static property doesn't leak into what they return
                'code' => '<?php
                    final class Memo { public static string $last = ""; }

                    /** @psalm-taint-specialize */
                    function remember(string $v): string {
                        Memo::$last = $v;
                        return $v;
                    }

                    remember((string) $_GET["x"]);
                    echo remember("safe");',
            ],
            'dontTaintWithAStaticVariableCounter' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    function countCalls(string $label): string {
                        /** @var int */
                        static $count = 0;
                        $count++;
                        return $label . (string) $count;
                    }

                    countCalls((string) $_GET["x"]);
                    echo countCalls("safe");',
            ],
            'dontTaintSpecializedCallWithWhatAnotherLeavesInAStaticVariable' => [
                // what the calls leave in the static variable doesn't leak into what they return
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    function remember(string $v): string {
                        /** @var string */
                        static $last = "";
                        $last = $v;
                        return $v;
                    }

                    remember((string) $_GET["x"]);
                    echo remember("safe");',
            ],
            'dontTaintSpecializedInstanceWithWhatItsMethodReturns' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    final class Box {
                        public function take(string $s): string {
                            return $s;
                        }
                    }

                    $a = new Box();
                    $unused = $a->take($_GET["a"]);
                    echo $a->take("safe");',
            ],
            'dontTaintOtherInstanceThroughInheritedMethod' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    class StringHolder {
                        protected string $value = "";

                        public function __construct(string $value) {
                            $this->value = $value;
                        }

                        public function getValue(): string {
                            return $this->value;
                        }
                    }

                    /** @psalm-taint-specialize */
                    final class ChildStringHolder extends StringHolder {}

                    $tainted = new ChildStringHolder((string) $_GET["x"]);
                    $safe = new ChildStringHolder("safe");
                    echo $safe->getValue();',
            ],
            'dontTaintSpecializedCallsForAnonymousInstance' => [
                'code' => '<?php

                    class StringRenderer {
                        /** @psalm-taint-specialize */
                        public function render(string $x) {
                            return $x;
                        }
                    }

                    $notEchoed = (new StringRenderer())->render($_GET["untrusted"]);
                    echo (new StringRenderer())->render("a");',
            ],
            'dontTaintSpecializedCallsForStubMadeInstance' => [
                'code' => '<?php

                    class StringRenderer {
                        /** @psalm-taint-specialize */
                        public function render(string $x) {
                            return $x;
                        }
                    }

                    /** @psalm-suppress InvalidReturnType */
                    function stub(): StringRenderer { }

                    $notEchoed = stub()->render($_GET["untrusted"]);
                    echo stub()->render("a");',
            ],
            'suppressTaintedInput' => [
                'code' => '<?php
                    function unsafe() {
                        /**
                         * @psalm-suppress TaintedInput
                         */
                        echo $_GET["x"];
                    }',
            ],
            'suppressTaintedAssignment' => [
                'code' => '<?php
                    $b = $_GET["x"];

                    /**
                     * @psalm-suppress TaintedInput
                     */
                    $a = $b;


                    echo $a;',
            ],
            'dontPropagateTaintToChildConstructor' => [
                'code' => '<?php
                    class A {
                        public function __construct(string $a) {}
                    }

                    class B extends A {
                        public function __construct(string $a) {
                            echo $a;
                        }
                    }

                    new A($_GET["foo"]);',
            ],
            'dontTaintThroughChildConstructorWhenMethodOverridden' => [
                'code' => '<?php //--taint-analysis
                    class A {
                        private $taint;

                        public function __construct($taint) {
                            $this->taint = $taint;
                        }

                        public function getTaint() : string {
                            return $this->taint;
                        }
                    }

                    class B extends A {
                        public function __construct($taint) {}
                    }

                    $b = new B($_GET["bar"]);
                    echo $b->getTaint();',
            ],
            'immutableClassTrackInputThroughMethod' => [
                'code' => '<?php
                    /**
                     * @psalm-immutable
                     */
                    class A {
                        private string $taint = "";

                        public function __construct(string $taint) {
                            $this->taint = $taint;
                        }

                        public function getTaint() : string {
                            return $this->taint;
                        }
                    }

                    $b = new A($_GET["bar"]);
                    $a = new A("bar");
                    echo $a->getTaint();',
            ],
            'literalStringCannotCarryTaint' => [
                'code' => '<?php
                    $file = $_GET["foo"];

                    if ($file !== "") {
                        /**
                         * @psalm-taint-escape input
                         */
                        $file = basename($file);
                    }

                    echo $file;',
            ],
            'strTrNotTainted' => [
                'code' => '<?php
                $input = strtr(\'data\', \'data\', \'data\');
                setcookie($input, \'value\');',
            ],
            'conditionallyEscapedTaintPassedTrue' => [
                'code' => '<?php
                    /**
                     * @psalm-taint-escape ($escape is true ? "html"|"has_quotes" : null)
                     */
                    function foo(string $string, bool $escape = true): string {
                        if ($escape) {
                            $string = htmlspecialchars($string);
                        }

                        return $string;
                    }
                    /** @psalm-suppress PossiblyInvalidArgument */
                    echo foo($_GET["foo"], true);
                    /** @psalm-suppress PossiblyInvalidArgument */
                    echo foo($_GET["foo"]);',
            ],
            'NoTaintForInt' => [
                'code' => '<?php // --taint-analysis

                    function foo(int $value): void {
                        echo $value;
                    }

                    /** @psalm-suppress InvalidScalarArgument */
                    foo($_GET["foo"]);

                    function bar(): int {
                        return $_GET["foo"];
                    }

                    echo bar();',
            ],
            'conditionallyEscapedTaintPassedTrueStaticCall' => [
                'code' => '<?php
                    class U {
                        /**
                         * @psalm-taint-escape ($escape is true ? "html"|"has_quotes" : null)
                         */
                        public static function foo(string $string, bool $escape = true): string {
                            if ($escape) {
                                $string = htmlspecialchars($string);
                            }

                            return $string;
                        }
                    }

                    echo U::foo($_GET["foo"], true);
                    echo U::foo($_GET["foo"]);',
            ],
            'valuesOfAnArrayWhoseKeysAreTaintedAreNotTainted' => [
                // The taint is in a key: a fetch of a value doesn't take it.
                'code' => '<?php
                    /** @param array<string, string> $arr */
                    function takesArray(array $arr): void {
                        echo $arr["city"] ?? "";
                    }

                    takesArray([(string)$_GET["bad"] => "good"]);',
            ],
            'valuesOfAPropertyWhoseKeysAreTaintedAreNotTainted' => [
                'code' => '<?php
                    final class Version {
                        /** @var array<string, string> */
                        public array $data = [];
                    }

                    function save(Version $version, string $field_id): void {
                        $version->data = ["val_" . $field_id => "good"];
                    }

                    function show(Version $version): void {
                        echo $version->data["comment_count"] ?? "";
                    }

                    save(new Version(), (string)$_GET["field_id"]);',
            ],
            'valuesOfAnArrayIteratedOverWhoseKeysAreTaintedAreNotTainted' => [
                'code' => '<?php
                    $arr = [(string)$_GET["bad"] => "good"];
                    foreach ($arr as $value) {
                        echo $value;
                    }',
            ],
            'keysAreNotTainted' => [
                'code' => '<?php
                    function takesArray(array $arr): void {
                        foreach ($arr as $key => $_) {
                            echo $key;
                        }
                    }

                    takesArray(["good" => $_GET["bad"]]);',
            ],
            'resultOfComparisonIsNotTainted' => [
                'code' => '<?php
                    $input = $_GET["foo"];
                    $var = $input === "x";
                    var_dump($var);',
            ],
            'resultOfPlusIsNotTainted' => [
                'code' => '<?php
                    $input = is_numeric( $_GET["foo"] ) ? $_GET["foo"] : "";
                    $var = $input + 1;
                    var_dump($var);',
            ],
            'NoTaintForIntTypeHintUsingAnnotatedSink' => [
                'code' => '<?php // --taint-analysis
                    function fetch(int $id): string
                    {
                        return query("SELECT * FROM table WHERE id=" . $id);
                    }
                    /**
                     * @return string
                     * @psalm-taint-sink sql $sql
                     * @psalm-taint-specialize
                     */
                    function query(string $sql) { return ""; }
                    $value = $_GET["value"];
                    $result = fetch($value);',
            ],
            'NoTaintForIntTypeCastUsingAnnotatedSink' => [
                'code' => '<?php // --taint-analysis
                    /** @psalm-suppress MissingParamType */
                    function fetch($id): string
                    {
                        return query("SELECT * FROM table WHERE id=" . (int)$id);
                    }
                    /**
                     * @return string
                     * @psalm-taint-sink sql $sql
                     * @psalm-taint-specialize
                     */
                    function query(string $sql) {}
                    $value = $_GET["value"];
                    $result = fetch($value);',
            ],
            'dontTaintArrayWithDifferentOffsetUpdated' => [
                'code' => '<?php
                    function foo(): void {
                        $foo = [
                            "a" => [["c" => "hello"]],
                            "b" => [],
                        ];

                        $foo["b"][] = [
                            "c" => $_GET["bad"],
                        ];

                        bar($foo["a"]);
                    }

                    function bar(array $arr): void {
                        foreach ($arr as $s) {
                            echo $s["c"];
                        }
                    }',
            ],
            'urlencode' => [
                'code' => '<?php
                    echo urlencode($_GET["bad"]);
                ',
            ],
            'mysqliEscapeFunctions' => [
                'code' => '<?php
                    $mysqli = new mysqli();

                    $a = $mysqli->escape_string($_GET["a"]);
                    $b = mysqli_escape_string($mysqli, $_GET["b"]);
                    $c = $mysqli->real_escape_string($_GET["c"]);
                    $d = mysqli_real_escape_string($mysqli, $_GET["d"]);

                    $mysqli->query("$a$b$c$d");',
            ],
            'querySimpleXMLElement' => [
                'code' => '<?php
                    /**
                     * @psalm-taint-escape xpath
                     */
                    function my_escaping_function_for_xpath(string $input) : string {};

                    function queryExpression(SimpleXMLElement $xml) : array|false|null {
                        $expression = $_GET["expression"];
                        $expression = my_escaping_function_for_xpath($expression);
                        return $xml->xpath($expression);
                    }',
            ],
            'escapeSeconds' => [
                'code' => '<?php
                    /**
                     * @psalm-taint-escape sleep
                     */
                    function my_escaping_function_for_seconds(mixed $input) : int {};

                    $seconds = my_escaping_function_for_seconds($_GET["seconds"]);
                    sleep($seconds);',
            ],
            'pseudoMethodOfInterface' => [
                'code' => '<?php
                    /** @method string label(string $s) */
                    interface Labeller {}

                    function f(Labeller $l): string {
                        return $l->label("a");
                    }',
            ],
            'specializeInferredPureFunction' => [
                'code' => '<?php
                    function id(string $s): string {
                        return $s;
                    }

                    $notEchoed = id($_GET["a"]);
                    echo id("safe");',
            ],
            'specializeInferredPureFunctionCallingInferredPureFunction' => [
                'code' => '<?php
                    function wrap(string $s): string {
                        return id($s);
                    }

                    function id(string $s): string {
                        return $s;
                    }

                    $notEchoed = wrap($_GET["a"]);
                    echo wrap("safe");',
            ],
            'specializeInferredPureRecursiveFunction' => [
                'code' => '<?php
                    function repeat(string $s, int $n): string {
                        return $n > 0 ? repeat($s, $n - 1) : $s;
                    }

                    $notEchoed = repeat($_GET["a"], 3);
                    echo repeat("safe", 3);',
            ],
            'specializeInferredPureCallableString' => [
                'code' => '<?php
                    function id(string $s): string {
                        return $s;
                    }

                    $f = "id";
                    $notEchoed = $f($_GET["a"]);
                    echo $f("safe");',
            ],
            'specializeInferredPureMethodsThatCannotBeOverridden' => [
                'code' => '<?php
                    final class FinalClass {
                        public function method(string $s): string {
                            return $s;
                        }

                        public static function staticMethod(string $s): string {
                            return $s;
                        }
                    }

                    class NonFinalClass {
                        final public function finalMethod(string $s): string {
                            return $s;
                        }
                    }

                    $a = new FinalClass();
                    $notEchoed = $a->method($_GET["a"]);
                    echo $a->method("safe");

                    $notEchoed = FinalClass::staticMethod($_GET["b"]);
                    echo FinalClass::staticMethod("safe");

                    $b = new NonFinalClass();
                    $notEchoed = $b->finalMethod($_GET["c"]);
                    echo $b->finalMethod("safe");',
            ],
            'narrowingToALiteralKeepsInferredTypes' => [
                'code' => '<?php
                    /**
                     * @param non-empty-list<string> $l
                     * @return non-empty-list<string>
                     */
                    function f(array $l): array {
                        foreach ($l as $o => $v) {
                            if ($o === 0) {
                                $v .= "a";
                            }
                            $l[$o] = $v;
                        }
                        return $l;
                    }',
            ],
            'taintThroughSpreadKeepsKeys' => [
                'code' => '<?php
                    $a = [$_GET["a"], "safe"];
                    $b = ["safe", ...$a];
                    echo (string) $b[0];
                    echo (string) $b[2];',
            ],
            'writingToTheTerminalIsNotAnHtmlSink' => [
                'code' => '<?php
                    fwrite(STDOUT, (string) $_GET["a"]);',
            ],
            'taintFreeGlobalOfAnotherName' => [
                'code' => '<?php
                    function stash(): void {
                        $GLOBALS["tainted"] = (string) $_GET["a"];
                    }

                    function bind(): void {
                        global $bound;
                        $bound = (string) $_GET["b"];
                    }

                    function show(): void {
                        global $safe;
                        echo (string) $safe;
                        echo (string) $GLOBALS["other"];
                    }',
            ],
            'taintFreeSanitizedGlobal' => [
                'code' => '<?php
                    function stash(): void {
                        $GLOBALS["value"] = htmlspecialchars((string) $_GET["a"], ENT_QUOTES);
                    }

                    function bind(): void {
                        global $bound;
                        $bound = htmlspecialchars((string) $_GET["b"], ENT_QUOTES);
                    }

                    function show(): void {
                        global $value;
                        echo (string) $value;
                        echo (string) $GLOBALS["bound"];
                    }',
            ],
            'taintFreeOtherKeyOfAGlobalArray' => [
                'code' => '<?php
                    function stash(): void {
                        $GLOBALS["config"] = ["safe" => "ok", "tainted" => (string) $_GET["a"]];
                    }

                    function show(): void {
                        echo $GLOBALS["config"]["safe"];
                    }',
            ],
            'taintFreeNamesOfTheGlobals' => [
                'code' => '<?php
                    function stash(): void {
                        $GLOBALS["value"] = (string) $_GET["a"];
                    }

                    function show(): void {
                        foreach ($GLOBALS as $name => $_) {
                            echo $name;
                        }
                    }',
            ],
            'taintFreeTopLevelVariableOfAnotherName' => [
                'code' => '<?php
                    $tainted = (string) $_GET["a"];

                    function show(): void {
                        global $safe;
                        echo (string) $safe;
                        echo (string) $GLOBALS["other"];
                    }',
            ],
            'taintFreeLocalVariableWithTheNameOfAGlobal' => [
                'code' => '<?php
                    function compute(): string {
                        $value = (string) $_GET["a"];
                        return $value;
                    }

                    function show(): void {
                        global $value;
                        echo (string) $value;
                    }',
            ],
            'taintFreeTopLevelVariableSetByAFunctionThroughAnother' => [
                'code' => '<?php
                    function stash(): void {
                        global $value;
                        $value = "safe";
                    }

                    function stashLater(): void {
                        stash();
                    }

                    $value = "safe";
                    $other = (string) $_GET["a"];
                    stashLater();
                    echo (string) $value;',
            ],
            'taintFreeLocalVariableAfterACallOfAMethodBindingTheGlobal' => [
                'code' => '<?php
                    class Stash {
                        public static function stash(): void {
                            global $value;
                            $value = (string) $_GET["a"];
                        }
                    }

                    function show(): void {
                        $value = "safe";
                        Stash::stash();
                        echo (string) $value;
                    }',
            ],
            'taintFreeLocalVariableAfterACallOfAFunctionBindingTheGlobal' => [
                'code' => '<?php
                    function stash(): void {
                        global $value;
                        $value = (string) $_GET["a"];
                    }

                    function show(): void {
                        $value = "safe";
                        stash();
                        echo (string) $value;
                    }',
            ],
            'taintFreeTopLevelVariableSanitizedBeforeItIsRead' => [
                'code' => '<?php
                    $value = (string) $_GET["a"];
                    $value = htmlspecialchars($value, ENT_QUOTES);
                    echo $value;',
            ],
            'taintFreeByRefParamOverwrittenBeforeReturning' => [
                'code' => '<?php
                    function fill(string &$out): void {
                        $out = (string) $_GET["a"];
                        $out = "safe";
                    }

                    function show(): void {
                        $value = "";
                        fill($value);
                        echo $value;
                    }',
            ],
            'getoptOptionCastToInt' => [
                'code' => '<?php
                    $options = getopt("n:");
                    echo (int) ($options["n"] ?? 0);',
            ],
            'specializedCallDoesNotReturnIntoSpecializedFunctionPassedAsCallable' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    function wrap(string $s): string {
                        return "<b>" . $s . "</b>";
                    }
                    function run(callable $callback): string {
                        return (string) $callback("a");
                    }
                    wrap((string) $_GET["x"]);
                    echo run(wrap(...));',
            ],
            'specializedFunctionPassedAsCallableDoesNotReturnIntoSpecializedCall' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    function wrap(string $s): string {
                        return "<b>" . $s . "</b>";
                    }
                    function run(callable $callback): string {
                        return (string) $callback((string) $_GET["x"]);
                    }
                    run(wrap(...));
                    echo wrap("a");',
            ],
            'specializedCallsOfAnOverrideDoNotShareTheirByRefParams' => [
                'code' => '<?php // --taint-analysis
                    class Wrapper {
                        public function wrap(string &$value): void {}
                    }

                    final class BoldWrapper extends Wrapper {
                        /** @psalm-taint-specialize */
                        #[Override]
                        public function wrap(string &$value): void {
                            $value = "<b>" . $value . "</b>";
                        }
                    }

                    function wrapBoth(): string {
                        $wrapper = new BoldWrapper();
                        $tainted = (string) $_GET["value"];
                        $wrapper->wrap($tainted);
                        $safe = "safe";
                        $wrapper->wrap($safe);
                        echo $safe;

                        return $tainted;
                    }',
            ],
            'specializedCallOfAnOverrideDoesNotFillTheByRefParamsOfCallsOfTheOverriddenMethod' => [
                'code' => '<?php // --taint-analysis
                    class Wrapper {
                        public function wrap(string &$value): void {
                            $value = "";
                        }
                    }

                    final class BoldWrapper extends Wrapper {
                        /** @psalm-taint-specialize */
                        #[Override]
                        public function wrap(string &$value): void {
                            $value = "<b>" . $value . "</b>";
                        }
                    }

                    function wrapBoth(Wrapper $wrapper): void {
                        $tainted = (string) $_GET["value"];
                        (new BoldWrapper())->wrap($tainted);
                        $safe = "safe";
                        $wrapper->wrap($safe);
                        echo $safe;
                    }',
            ],
            'dontTaintTheResultOfACallOnAClassInheritingTheMethodWithTheReturnOfASiblingOverride' => [
                'code' => '<?php
                    class Named {
                        public function name(): string { return "safe"; }
                    }
                    final class Tainted extends Named {
                        public function name(): string { return (string) $_GET["name"]; }
                    }
                    final class Inheriting extends Named {}

                    function show(Inheriting $named): void {
                        echo $named->name();
                    }',
            ],
            'dontTaintWhatAnInheritedBodyReturnsOfAnotherObjectThroughAnOverrideSpecializedByInstance' => [
                'code' => '<?php // --taint-analysis
                    /** @psalm-immutable */
                    class Base {
                        public function __construct(public string $value) {}

                        public function me(): self {
                            return $this->pick();
                        }

                        protected function pick(): self {
                            return new self("literal");
                        }
                    }

                    /** @psalm-immutable */
                    final class Child extends Base {
                        #[Override]
                        protected function pick(): self {
                            return $this;
                        }
                    }

                    $tainted = new Child((string) $_GET["value"]);
                    $clean = new Child("clean");
                    $x = $tainted->me();
                    echo $clean->me()->value;',
            ],
            'dontTaintWhatAnOverrideSpecializedByInstanceReturnsOfAnotherObjectThroughAVirtualCall' => [
                'code' => '<?php // --taint-analysis
                    /** @psalm-immutable */
                    class Base {
                        public function __construct(public string $value) {}

                        public function get(): string {
                            return "literal";
                        }
                    }

                    /** @psalm-immutable */
                    final class Child extends Base {
                        #[Override]
                        public function get(): string {
                            return $this->value;
                        }
                    }

                    function show(Base $base): string {
                        return $base->get();
                    }

                    show(new Child((string) $_GET["value"]));
                    echo show(new Child("clean"));',
            ],
            'dontTaintTheResultOfAParentCallWithTheReturnOfASiblingOverride' => [
                'code' => '<?php
                    class Named {
                        public function name(): string { return "safe"; }
                    }
                    final class Tainted extends Named {
                        public function name(): string { return (string) $_GET["name"]; }
                    }
                    final class Shown extends Named {
                        public function show(): void {
                            echo parent::name();
                        }
                    }',
            ],
            'dontTaintTheResultOfACallOnAnInterfaceWithWhatASpecializedCallOfTheImplementationReturns' => [
                'code' => '<?php
                    interface Identity {
                        public function get(string $value): string;
                    }
                    final class Implementation implements Identity {
                        public function get(string $value): string { return $value; }
                    }

                    function run(Implementation $identity): string {
                        return $identity->get((string) $_GET["value"]);
                    }
                    function show(Identity $identity, string $value): void {
                        echo $identity->get($value);
                    }',
            ],
            'conditionalSinkOfAnEscapingMode' => [
                'code' => '<?php
                    /** @psalm-taint-sink ($mode is 0|1|2 ? null : \'html\') $content */
                    function render(string $content, int $mode = 0): void {}

                    final class Component {
                        /**
                         * @param array{render_way?: int, ...} $options
                         * @psalm-taint-sink ($options is array{render_way?: 0|1|2, ...} ? null : \'html\') $content
                         */
                        public function __construct(string $content, array $options = []) {}

                        /** @psalm-taint-sink ($mode is 0|1|2 ? null : \'html\') $content */
                        public function show(string $content, int $mode): void {}

                        /** @psalm-taint-sink ($mode is 0|1|2 ? null : \'html\') $content */
                        public static function format(string $content, int $mode): void {}
                    }

                    $input = (string) $_GET["x"];
                    render($input, 1);
                    render($input);
                    new Component($input, ["render_way" => 2, "class" => "title"]);
                    new Component($input);
                    (new Component("safe"))->show($input, 0);
                    Component::format($input, 1);
                    $render = render(...);
                    $render($input, 1);
                    $closure =
                        /** @psalm-taint-sink ($mode is 0|1|2 ? null : \'html\') $content */
                        function (string $content, int $mode = 0): void {};
                    $closure($input, 2);',
            ],
            'lateBoundKeyedStoreKeepsTheStoresOfUnrelatedClassesApart' => [
                'code' => '<?php
                    abstract class Model {
                        /** @psalm-flow ($value) -> static::$temp[$key] */
                        public function setTemp(string $key, string $value): void {}

                        /** @psalm-flow static::$temp[$key] -> return */
                        public function getTemp(string $key): string {
                            return "";
                        }
                    }

                    final class Article extends Model {}
                    final class Comment extends Model {}

                    function save(Article $article): void {
                        $article->setTemp("k", (string) $_GET["x"]);
                    }

                    function show(Comment $comment): void {
                        echo $comment->getTemp("k");
                    }',
            ],
            'propertySetByNameDoesNotTaintTheOtherProperties' => [
                'code' => '<?php
                    final class Model {
                        public string $title = "";
                        public string $body = "";

                        /** @psalm-flow ($value) -> $this->$name */
                        public function set(string $name, string $value): void {}

                        /** @psalm-flow $this->$name -> return */
                        public function get(string $name): string {
                            return "";
                        }
                    }

                    function save(Model $model): void {
                        $model->set("title", (string) $_GET["x"]);
                    }

                    function show(Model $model): void {
                        echo $model->body;
                        echo $model->get("body");
                    }',
            ],
            'propertySetUnderANameNotKnownStaysInTheClassOfTheObject' => [
                'code' => '<?php
                    abstract class Bag {
                        /** @psalm-flow ($value) -> $this->$name */
                        public function set(string $name, string $value): void {}

                        /** @psalm-flow $this->$name -> return */
                        public function get(string $name): string {
                            return "";
                        }
                    }

                    final class Query extends Bag {}

                    final class Options extends Bag {}

                    function save(Options $options, string $name): void {
                        $options->set($name, (string) $_GET["x"]);
                    }

                    function show(Query $query): void {
                        echo $query->get("other");
                    }',
            ],
            'propertySetByNameOfAnObjectSpecializedStaysInIt' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    final class Model {
                        /** @psalm-flow ($value) -> $this->$name */
                        public function set(string $name, string $value): void {}

                        /** @psalm-flow $this->$name -> return */
                        public function get(string $name): string {
                            return "";
                        }
                    }

                    $tainted = new Model();
                    $tainted->set("title", (string) $_GET["x"]);
                    $other = new Model();
                    $other->set("title", "safe");
                    echo $other->get("title");
                    echo $tainted->get("body");',
            ],
            'propertiesSetByKeyDoNotTaintTheOtherProperties' => [
                'code' => '<?php
                    final class Model {
                        public string $title = "";
                        public string $body = "";

                        /** @psalm-flow ($values) -> $this->{key} */
                        public function setProperties(array $values): void {}

                        /**
                         * @return array<string, string>
                         * @psalm-flow $this->{key} -> return[key]
                         */
                        public function toArray(): array {
                            return [];
                        }

                        /** @psalm-flow $this->$name -> return */
                        public function get(string $name): string {
                            return "";
                        }
                    }

                    function save(Model $model): void {
                        $model->setProperties(["title" => (string) $_GET["x"], "body" => "safe"]);
                    }

                    function show(Model $model): void {
                        echo $model->body;
                        echo $model->toArray()["body"];
                        echo $model->get("body");
                    }',
            ],
            'propertiesReturnedByKeyKeepTheOtherKeysApart' => [
                'code' => '<?php
                    final class Model {
                        public string $title = "";
                        public string $body = "";

                        /** @psalm-flow ($values) -> $this->{key} */
                        public function setProperties(array $values): void {}

                        /**
                         * @return array<string, string>
                         * @psalm-flow $this->{key} -> return[key]
                         */
                        public function toArray(): array {
                            return [];
                        }
                    }

                    function save(Model $model): void {
                        $model->title = (string) $_GET["x"];
                        $model->setProperties(["undeclared" => (string) $_GET["x"]]);
                    }

                    function show(Model $model): void {
                        echo $model->toArray()["body"];
                    }',
            ],
            'propertiesSetByNameInACopyLoopKeepTheirKeys' => [
                'code' => '<?php
                    /**
                     * @property string $label
                     * @property string $size
                     */
                    final class Options {
                        public function __get(string $name): mixed {
                            return null;
                        }

                        /** @psalm-flow ($value) -> $this->$name */
                        public function set(string $name, string $value): void {}
                    }

                    function fillOptions(Options $options): void {
                        foreach (["label" => (string) $_GET["x"], "size" => "m"] as $name => $value) {
                            $options->set($name, $value);
                        }

                        echo $options->size;
                    }',
            ],
            'propertyBagOfAnObjectTrackedPerInstanceKeepsObjectsApart' => [
                'code' => '<?php
                    /**
                     * @psalm-taint-property-bag $properties
                     * @psalm-taint-specialize
                     */
                    abstract class Bag {
                        /** @var array<string, mixed> */
                        protected array $properties = [];
                    }

                    /** @psalm-taint-specialize */
                    final class Model extends Bag {
                        public function __construct(string $title) {
                            $this->properties["title"] = $title;
                        }

                        public function getTitle(): string {
                            return (string) $this->properties["title"];
                        }
                    }

                    $tainted = new Model((string) $_GET["x"]);
                    $clean = new Model("safe");
                    echo $clean->getTitle();',
            ],
            'propertyBagElementsUnderNestedIntKeysKeepTheirKeysApart' => [
                'code' => '<?php
                    /** @psalm-taint-property-bag $properties */
                    abstract class Bag {
                        /** @var array<string, mixed> */
                        protected array $properties = [];

                        public function __get(string $name): mixed {
                            return null;
                        }
                    }

                    /** @property list<string> $tags */
                    final class Model extends Bag {
                        public function setTags(string $tag): void {
                            $this->properties["tags"][0] = $tag;
                            $this->properties["tags"][1] = "safe";
                        }
                    }

                    function save(Model $model): void {
                        $model->setTags((string) $_GET["x"]);
                    }

                    function show(Model $model): void {
                        echo $model->tags[1];
                    }',
            ],
            'propertyBagElementsKeepTheOtherPropertiesApart' => [
                'code' => '<?php
                    /** @psalm-taint-property-bag $properties */
                    abstract class Bag {
                        /** @var array<string, mixed> */
                        protected array $properties = [];

                        public function __get(string $name): mixed {
                            return null;
                        }

                        public function __set(string $name, mixed $value): void {}
                    }

                    /**
                     * @property string $title
                     * @property string $body
                     */
                    final class Model extends Bag {
                        public function setTitle(string $title): void {
                            $this->properties["title"] = $title;
                        }

                        public function getBody(): string {
                            return (string) $this->properties["body"];
                        }
                    }

                    function save(Model $model): void {
                        $model->setTitle((string) $_GET["x"]);
                    }

                    function show(Model $model): void {
                        echo $model->body;
                        echo $model->getBody();
                    }',
            ],
            'elementsOfANamedPropertyKeepTheirKeysApart' => [
                'code' => '<?php
                    final class Request {
                        /** @var array<string, string> */
                        private array $vars = [];

                        /** @psalm-flow ($value) -> $this->vars[$var] */
                        public function set(string $var, string $value): void {}

                        /** @psalm-flow $this->vars[$var] -> return */
                        public function get(string $var): string {
                            return "";
                        }

                        /**
                         * @return array<string, string>
                         * @psalm-flow $this->vars -> return
                         */
                        public function toArray(): array {
                            return [];
                        }
                    }

                    function save(Request $request): void {
                        $request->set("name", (string) $_GET["x"]);
                    }

                    function show(Request $request): void {
                        echo $request->get("other");
                        echo $request->toArray()["other"];
                    }',
            ],
            'namedPropertySetByTheConstructorOfAnotherObjectWithItsTaintsPerInstance' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    final class Request {
                        /** @var array<string, string> */
                        private array $vars = [];

                        /**
                         * @param array<string, string> $vars
                         * @psalm-flow ($vars) -> $this->vars
                         */
                        public function __construct(array $vars) {}

                        /** @psalm-flow ($value) -> $this->vars[$var] */
                        public function set(string $var, string $value): void {}

                        /** @psalm-flow $this->vars[$var] -> return */
                        public function get(string $var): string {
                            return "";
                        }
                    }

                    $tainted = new Request(["name" => (string) $_GET["x"]]);
                    $safe = new Request(["name" => "safe"]);
                    $safe->set("other", "safe");
                    echo $safe->get("name");
                    echo $safe->get("other");
                    echo $tainted->get("other");',
            ],
            'propertiesSetByKeyByTheConstructorDoNotTaintTheOtherProperties' => [
                'code' => '<?php
                    final class Model {
                        public string $title = "";
                        public string $body = "";

                        /**
                         * @param array<string, string> $values
                         * @psalm-flow ($values) -> $this->{key}
                         */
                        public function __construct(array $values) {}
                    }

                    new Model(["title" => (string) $_GET["x"], "body" => "safe"]);

                    function show(Model $model): void {
                        echo $model->body;
                    }',
            ],
            'cliOnlyCodePrintsToATerminal' => [
                'code' => '<?php
                    /** @psalm-cli-only */
                    function import(): void {
                        $file = (string) $_GET["file"];
                        echo $file;
                        print $file;
                        printf("%s", $file);
                        vprintf("%s", [$file]);
                        var_dump($file);
                        print_r($file);
                        var_export($file, false);
                        array_map(function (string $line): void {
                            echo $line;
                        }, [$file]);
                    }

                    /** @psalm-cli-only */
                    final class ImportCommand {
                        public function run(): void {
                            echo (string) $_GET["file"];
                        }
                    }',
            ],
        ];
    }

    /**
     * @return array<string, array{code: string, error_message: string, php_version?: string}>
     * @psalm-pure
     */
    public static function providerInvalidCodeParse(): array
    {
        return [
            'taintSourceUnderAKeyOfTheReturnOfAFunction' => [
                'code' => '<?php // --taint-analysis
                    /**
                     * @psalm-taint-source input return[\'original\']
                     * @return array{original: string, path: string}
                     */
                    function upload(): array {
                        return ["original" => "a.txt", "path" => "/tmp/a"];
                    }

                    echo upload()["original"];',
                'error_message' => 'TaintedHtml',
            ],
            'taintSourceUnderNestedKeysOfTheReturnOfAMethod' => [
                'code' => '<?php // --taint-analysis
                    final class Uploader {
                        /**
                         * @psalm-taint-source input return["file"]["name"]
                         * @return array{file: array{name: string, size: int}, path: string}
                         */
                        public function handle(): array {
                            return ["file" => ["name" => "a.txt", "size" => 1], "path" => "/tmp/a"];
                        }
                    }

                    $file = (new Uploader())->handle()["file"];
                    echo $file["name"];',
                'error_message' => 'TaintedHtml',
            ],
            'taintSourceUnderAKeyOfTheReturnOfAStaticMethod' => [
                'code' => '<?php // --taint-analysis
                    final class Uploader {
                        /**
                         * @psalm-taint-source input return[0]
                         * @return array{string, string}
                         */
                        public static function pair(): array {
                            return ["a", "b"];
                        }
                    }

                    echo Uploader::pair()[0];',
                'error_message' => 'TaintedHtml',
            ],
            'taintSourceUnderAKeyOfTheReturnTaintsTheWholeArrayItIsIn' => [
                'code' => '<?php // --taint-analysis
                    /**
                     * @psalm-taint-source input return[\'original\']
                     * @return array{original: string, path: string}
                     */
                    function upload(): array {
                        return ["original" => "a.txt", "path" => "/tmp/a"];
                    }

                    echo implode(",", upload());',
                'error_message' => 'TaintedHtml',
            ],
            'taintSourceWithKeysOfTheReturnThatCantBeRead' => [
                'code' => '<?php // --taint-analysis
                    /**
                     * @psalm-taint-source input return[original]
                     */
                    function upload(): string {
                        return "a.txt";
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'keysFetchInASpecializedCallWhereManyKeyedArraysConvergeTakesATaintedKey' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    function getKeys(array $p): string {
                        $out = "";
                        foreach ($p["wrapper"] as $k => $_) {
                            $out .= (string) $k;
                        }
                        return $out;
                    }

                    function relay(array $row): void {
                        echo getKeys($row);
                    }

                    relay(["wrapper" => ["a" => (string)($_GET["a"] ?? "")]]);
                    relay(["wrapper" => ["b" => (string)($_GET["b"] ?? "")]]);
                    relay(["wrapper" => ["c" => (string)($_GET["c"] ?? "")]]);
                    relay(["wrapper" => ["d" => (string)($_GET["d"] ?? "")]]);
                    relay(["wrapper" => ["e" => (string)($_GET["e"] ?? "")]]);
                    relay(["wrapper" => ["f" => (string)($_GET["f"] ?? "")]]);
                    relay(["wrapper" => ["g" => (string)($_GET["g"] ?? "")]]);
                    relay(["wrapper" => ["h" => (string)($_GET["h"] ?? "")]]);
                    relay(["wrapper" => ["i" => (string)($_GET["i"] ?? "")]]);
                    relay(["wrapper" => ["j" => (string)($_GET["j"] ?? "")]]);
                    relay(["a" => ["name" => (string)($_GET["a"] ?? "")]]);
                    relay(["b" => ["name" => (string)($_GET["b"] ?? "")]]);
                    relay(["c" => ["name" => (string)($_GET["c"] ?? "")]]);
                    relay(["wrapper" => [(string)($_GET["key"] ?? "") => "x"]]);',
                'error_message' => 'TaintedHtml',
            ],
            'aSpecializedFunctionThatOnlyReadsSharedStateReturnsItToAllItsCalls' => [
                'code' => '<?php // --taint-analysis
                    final class Settings {
                        public static string $value = "";

                        public static function get(string $suffix): string {
                            return self::$value . $suffix;
                        }
                    }

                    function store(): void {
                        Settings::$value = (string) $_GET["value"];
                    }

                    store();
                    echo Settings::get("a");',
                'error_message' => 'TaintedHtml',
            ],
            'taintGlobalsItemCopiedFromTheSameKeyOfAnotherArray' => [
                'code' => '<?php
                    function show(): void {
                        echo (string) ($GLOBALS["name"] ?? "");
                    }

                    /** @param array<string, string> $data */
                    function copyOne(array $data, string $k): void {
                        $GLOBALS[$k] = $data[$k];
                    }

                    copyOne(["name" => (string) $_GET["n"]], "name");
                    show();',
                'error_message' => 'TaintedHtml',
            ],
            'fetchOfTheKeyOfAForeachCopyOfTheElements' => [
                'code' => '<?php // --taint-analysis
                    /** @param array<string, string> $data */
                    function show(array $data): void {
                        $copy = [];
                        foreach ($data as $key => $value) {
                            $copy[$key] = $value;
                        }
                        echo $copy["name_tail"] ?? "";
                    }

                    $data = [];
                    $data["name_tail"] = (string)($_GET["name_tail"] ?? "");
                    show($data);',
                'error_message' => 'TaintedHtml',
            ],
            'fetchOfAnotherKeyOfAForeachAssignmentOfAnotherValue' => [
                'code' => '<?php // --taint-analysis
                    /** @param array<string, string> $data */
                    function show(array $data): void {
                        $copy = [];
                        foreach ($data as $key => $value) {
                            $copy[$key] = $value . (string)($_GET["suffix"] ?? "");
                        }
                        echo $copy["city"] ?? "";
                    }

                    show(["city" => "Paris"]);',
                'error_message' => 'TaintedHtml',
            ],
            'fetchOfAnotherKeyOfAForeachAssignmentUnderAnotherKey' => [
                'code' => '<?php // --taint-analysis
                    /** @param array<string, string> $data */
                    function show(array $data): void {
                        $copy = [];
                        foreach ($data as $key => $value) {
                            $other_key = strtolower($key);
                            $copy[$other_key] = $value;
                        }
                        echo $copy["city"] ?? "";
                    }

                    $data = [];
                    $data["City"] = (string)($_GET["city"] ?? "");
                    show($data);',
                'error_message' => 'TaintedHtml',
            ],
            'fetchOfAnotherKeyOfAForeachAssignmentOfAReassignedValue' => [
                'code' => '<?php // --taint-analysis
                    /** @param array<string, string> $data */
                    function show(array $data): void {
                        $copy = [];
                        foreach ($data as $key => $value) {
                            $value = trim($value . (string)($_GET["suffix"] ?? ""));
                            $copy[$key] = $value;
                        }
                        echo $copy["city"] ?? "";
                    }

                    show(["city" => "Paris"]);',
                'error_message' => 'TaintedHtml',
            ],
            'fetchOfAnotherKeyOfAForeachAssignmentOfAValueReassignedInABranch' => [
                'code' => '<?php // --taint-analysis
                    /** @param array<string, string> $data */
                    function show(array $data): void {
                        $copy = [];
                        foreach ($data as $key => $value) {
                            if (rand(0, 1)) {
                                $value = (string)($_GET["value"] ?? "");
                            }
                            $copy[$key] = $value;
                        }
                        echo $copy["city"] ?? "";
                    }

                    $data = [];
                    $data["name_tail"] = "";
                    show($data);',
                'error_message' => 'TaintedHtml',
            ],
            'fetchOfTheKeyOfACopyOfTheElementsOfAnotherArrayUnderTheirKey' => [
                'code' => '<?php // --taint-analysis
                    /**
                     * @param array<string, string> $old
                     * @param array<string, string> $data
                     */
                    function show(array $old, array $data): void {
                        $diff = [];
                        foreach ($old as $key => $value) {
                            if (array_key_exists($key, $data) && $data[$key] !== $value) {
                                $diff[$key] = $data[$key];
                            }
                        }
                        echo $diff["name_tail"] ?? "";
                    }

                    $data = [];
                    $data["name_tail"] = (string)($_GET["name_tail"] ?? "");
                    show(["name_tail" => ""], $data);',
                'error_message' => 'TaintedHtml',
            ],
            'fetchOfAnotherKeyOfACopyOfTheElementsOfAnotherArrayUnderAnotherKey' => [
                'code' => '<?php // --taint-analysis
                    /**
                     * @param array<string, string> $old
                     * @param array<string, string> $data
                     */
                    function show(array $old, array $data): void {
                        $diff = [];
                        foreach ($old as $key => $value) {
                            if (array_key_exists($key, $data) && $data[$key] !== $value) {
                                $other_key = strtoupper($key);
                                $diff[$key] = $data[$other_key];
                            }
                        }
                        echo $diff["city"] ?? "";
                    }

                    $data = ["city" => ""];
                    $data["CITY"] = (string)($_GET["city"] ?? "");
                    show(["city" => "Paris"], $data);',
                'error_message' => 'TaintedHtml',
            ],
            'fetchOfAnotherKeyOfAnAssignmentOfAnotherValueThanAnElement' => [
                'code' => '<?php // --taint-analysis
                    /**
                     * @param array<string, string> $old
                     * @param array<string, string> $data
                     */
                    function show(array $old, array $data): void {
                        $diff = [];
                        foreach ($old as $key => $value) {
                            if (array_key_exists($key, $data) && $data[$key] !== $value) {
                                $diff[$key] = $data[$key] . (string)($_GET["suffix"] ?? "");
                            }
                        }
                        echo $diff["city"] ?? "";
                    }

                    show(["city" => "Paris"], ["city" => ""]);',
                'error_message' => 'TaintedHtml',
            ],
            'fetchOfAnotherKeyOfAnObjectAssignedTheElementsUnderTheirKey' => [
                // An object puts what is assigned to it where its offsetSet() does: here under another key.
                'code' => '<?php // --taint-analysis
                    /** @implements ArrayAccess<string, string> */
                    final class CaseInsensitiveMap implements ArrayAccess {
                        /** @var array<string, string> */
                        private array $values = [];

                        public function offsetExists(mixed $offset): bool {
                            return isset($this->values[strtolower($offset)]);
                        }

                        public function offsetGet(mixed $offset): string {
                            return $this->values[strtolower($offset)] ?? "";
                        }

                        public function offsetSet(mixed $offset, mixed $value): void {
                            $this->values[strtolower((string) $offset)] = $value;
                        }

                        public function offsetUnset(mixed $offset): void {
                            unset($this->values[strtolower($offset)]);
                        }
                    }

                    /** @param array<string, string> $data */
                    function show(array $data): void {
                        $map = new CaseInsensitiveMap();
                        foreach ($data as $key => $value) {
                            $map[$key] = $value;
                        }
                        echo $map["city"];
                    }

                    $data = [];
                    $data["City"] = (string)($_GET["city"] ?? "");
                    show($data);',
                'error_message' => 'TaintedHtml',
            ],
            'fetchOfAnotherKeyOfAMixedValueAssignedTheElementsUnderTheirKey' => [
                // A mixed value may be an object putting what is assigned to it under another key.
                'code' => '<?php // --taint-analysis
                    /** @implements ArrayAccess<string, string> */
                    final class CaseInsensitiveMap implements ArrayAccess {
                        /** @var array<string, string> */
                        private array $values = [];

                        public function offsetExists(mixed $offset): bool {
                            return isset($this->values[strtolower($offset)]);
                        }

                        public function offsetGet(mixed $offset): string {
                            return $this->values[strtolower($offset)] ?? "";
                        }

                        public function offsetSet(mixed $offset, mixed $value): void {
                            $this->values[strtolower((string) $offset)] = $value;
                        }

                        public function offsetUnset(mixed $offset): void {
                            unset($this->values[strtolower($offset)]);
                        }
                    }

                    /**
                     * @param mixed $map
                     * @param array<string, string> $data
                     */
                    function fill($map, array $data): void {
                        foreach ($data as $key => $value) {
                            $map[$key] = $value;
                        }
                        echo (string) $map["city"];
                    }

                    fill(new CaseInsensitiveMap(), ["City" => (string) ($_GET["city"] ?? "")]);',
                'error_message' => 'TaintedHtml',
            ],
            'fetchOfTheKeyOfAForeachTransformOfTheElements' => [
                'code' => '<?php // --taint-analysis
                    /** @param array<string, string> $data */
                    function show(array $data): void {
                        $result = [];
                        foreach ($data as $key => $value) {
                            $result[$key] = strtolower($value);
                        }
                        echo $result["name_tail"] ?? "";
                    }

                    $data = [];
                    $data["name_tail"] = (string)($_GET["name_tail"] ?? "");
                    $data["city"] = "Moscow";
                    show($data);',
                'error_message' => 'TaintedHtml',
            ],
            'fetchOfAnotherKeyOfAForeachTransformUnderAnotherKey' => [
                'code' => '<?php // --taint-analysis
                    /** @param array<string, string> $data */
                    function show(array $data): void {
                        $result = [];
                        foreach ($data as $key => $value) {
                            $other_key = strtolower($key);
                            $result[$other_key] = strtolower($value);
                        }
                        echo $result["city"] ?? "";
                    }

                    $data = [];
                    $data["name_tail"] = (string)($_GET["name_tail"] ?? "");
                    $data["city"] = "Moscow";
                    show($data);',
                'error_message' => 'TaintedHtml',
            ],
            'fetchOfAnotherKeyOfAForeachTransformUnderAReassignedKey' => [
                'code' => '<?php // --taint-analysis
                    /** @param array<string, string> $data */
                    function show(array $data): void {
                        $result = [];
                        foreach ($data as $key => $value) {
                            $key = strtolower($key);
                            $result[$key] = strtolower($value);
                        }
                        echo $result["city"] ?? "";
                    }

                    $data = [];
                    $data["name_tail"] = (string)($_GET["name_tail"] ?? "");
                    $data["city"] = "Moscow";
                    show($data);',
                'error_message' => 'TaintedHtml',
            ],
            'fetchOfAnotherKeyOfAForeachTransformOfThePreviousElement' => [
                'code' => '<?php // --taint-analysis
                    /** @param array<string, string> $data */
                    function show(array $data): void {
                        $result = [];
                        foreach ($data as $key => $value) {
                            $result[$key] = strtolower($previous ?? "");
                            $previous = $value;
                        }
                        echo $result["city"] ?? "";
                    }

                    $data = [];
                    $data["name_tail"] = (string)($_GET["name_tail"] ?? "");
                    $data["city"] = "Moscow";
                    show($data);',
                'error_message' => 'TaintedHtml',
            ],
            'fetchOfAnotherKeyOfAnObjectAssignedTransformsOfTheElementsUnderTheirKey' => [
                // An object puts what is assigned to it where its offsetSet() does: here under another key.
                'code' => '<?php // --taint-analysis
                    /** @implements ArrayAccess<string, string> */
                    final class CaseInsensitiveMap implements ArrayAccess {
                        /** @var array<string, string> */
                        private array $values = [];

                        public function offsetExists(mixed $offset): bool {
                            return isset($this->values[strtolower($offset)]);
                        }

                        public function offsetGet(mixed $offset): string {
                            return $this->values[strtolower($offset)] ?? "";
                        }

                        public function offsetSet(mixed $offset, mixed $value): void {
                            $this->values[strtolower((string) $offset)] = $value;
                        }

                        public function offsetUnset(mixed $offset): void {
                            unset($this->values[strtolower($offset)]);
                        }
                    }

                    /** @param array<string, string> $data */
                    function show(array $data): void {
                        $map = new CaseInsensitiveMap();
                        foreach ($data as $key => $value) {
                            $map[$key] = trim($value);
                        }
                        echo $map["city"];
                    }

                    $data = [];
                    $data["City"] = (string)($_GET["city"] ?? "");
                    show($data);',
                'error_message' => 'TaintedHtml',
            ],
            'fetchOfAnotherKeyOfAMixedValueAssignedTransformsOfTheElementsUnderTheirKey' => [
                // A mixed value may be an object putting what is assigned to it under another key.
                'code' => '<?php // --taint-analysis
                    /** @implements ArrayAccess<string, string> */
                    final class CaseInsensitiveMap implements ArrayAccess {
                        /** @var array<string, string> */
                        private array $values = [];

                        public function offsetExists(mixed $offset): bool {
                            return isset($this->values[strtolower($offset)]);
                        }

                        public function offsetGet(mixed $offset): string {
                            return $this->values[strtolower($offset)] ?? "";
                        }

                        public function offsetSet(mixed $offset, mixed $value): void {
                            $this->values[strtolower((string) $offset)] = $value;
                        }

                        public function offsetUnset(mixed $offset): void {
                            unset($this->values[strtolower($offset)]);
                        }
                    }

                    /**
                     * @param mixed $map
                     * @param array<string, string> $data
                     */
                    function fill($map, array $data): void {
                        foreach ($data as $key => $value) {
                            $map[$key] = trim($value);
                        }
                        echo (string) $map["city"];
                    }

                    fill(new CaseInsensitiveMap(), ["City" => (string) ($_GET["city"] ?? "")]);',
                'error_message' => 'TaintedHtml',
            ],
            'fetchOfTheKeyOfAnAssignmentOfAnElementWhoseFetchTakesMoreThanTheArrayHolds' => [
                'code' => '<?php
                    $key = "type";
                    $file = [];
                    $file[$key] = $_FILES["upload"][$key];
                    echo $file["type"];',
                'error_message' => 'TaintedHtml',
            ],
            'fetchOfAKeyStartingLikeTheAssignedKey' => [
                'code' => '<?php // --taint-analysis
                    /** @param array<string, string> $data */
                    function show(array $data, int $id): void {
                        echo $data["na{$id}"] ?? "";
                    }

                    $data = [];
                    $data["name_tail"] = (string)($_GET["name_tail"] ?? "");
                    show($data, 1);',
                'error_message' => 'TaintedHtml',
            ],
            'fetchOfAKeyAfterAnAssignmentToAKeyItStartsWith' => [
                'code' => '<?php // --taint-analysis
                    /** @param array<string, string> $data */
                    function show(array $data, int $id): void {
                        $data["ci{$id}"] = (string)($_GET["value"] ?? "");
                        echo $data["city"] ?? "";
                    }

                    show([], 1);',
                'error_message' => 'TaintedHtml',
            ],
            'fetchOfAKeyWhoseStartStartsWithTheStartOfTheAssignedKey' => [
                'code' => '<?php // --taint-analysis
                    /** @param array<string, string> $data */
                    function show(array $data, int $id): void {
                        $data["na" . $id] = (string)($_GET["value"] ?? "");
                        echo $data["name" . $id] ?? "";
                    }

                    show([], 1);',
                'error_message' => 'TaintedHtml',
            ],
            'nosqlSinkTaintedByArrayNarrowing' => [
                'code' => '<?php
                    /** @psalm-taint-sink nosql $filter */
                    function query(array $filter): void {}

                    /** @psalm-taint-source input */
                    function input(): mixed {
                        return null;
                    }

                    $value = input();
                    if (is_array($value)) {
                        query(rand(0, 1) ? ["name" => "x"] : $value);
                    }',
                'error_message' => 'TaintedNosql',
            ],
            'htmlSinkTaintedByStringNarrowingDroppingNosql' => [
                'code' => '<?php
                    /** @psalm-taint-source input */
                    function input(): mixed {
                        return null;
                    }

                    // a string can\'t carry a NoSQL query document, but it can carry HTML
                    $value = input();
                    if (is_string($value)) {
                        echo $value;
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'fetchUnderAKeyThatIsOneOfAFewLiteralsTakesWhatIsUnderEach' => [
                'code' => '<?php
                    function show(array $options): void {
                        foreach (["mode", "ids"] as $key) {
                            echo (string) $options[$key];
                        }
                    }
                    show(["ids" => $_GET["ids"], "other" => "a"]);',
                'error_message' => 'TaintedHtml',
            ],
            'fetchUnderAKeyThatIsOneOfAFewLiteralsWithAQuoteAndABarTakesWhatIsUnderEach' => [
                'code' => '<?php
                    function show(array $options): void {
                        foreach (["mode", "x\'|\'y"] as $key) {
                            echo (string) $options[$key];
                        }
                    }
                    show(["x\'|\'y" => $_GET["ids"], "other" => "a"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintAssignmentUnderOneOfLiteralKeys' => [
                'code' => '<?php
                    function show(string $key): void {
                        $array = [];

                        if ($key === "a" || $key === "b") {
                            $array[$key] = (string) $_GET["value"];
                        }

                        echo (string) ($array["b"] ?? "");
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintValueOfAnArrayGivenToAValueSink' => [
                'code' => '<?php // --taint-analysis
                    final class Loader {
                        /** @psalm-taint-sink eval $templates[*] */
                        public function __construct(private array $templates) {}
                    }

                    new Loader(["main" => (string) ($_GET["template"] ?? "")]);',
                'error_message' => 'TaintedEval',
            ],
            'taintArrayGivenToAValueSink' => [
                'code' => '<?php // --taint-analysis
                    /** @psalm-taint-sink html $values[*] */
                    function showValues(array $values): void {}

                    showValues($_GET);',
                'error_message' => 'TaintedHtml',
            ],
            'taintValueOfAnArrayGivenToAValueSinkAfterAKey' => [
                'code' => '<?php // --taint-analysis
                    /** @psalm-taint-sink html $values[*] */
                    function showValues(array $values): void {}

                    $input = (string) ($_GET["input"] ?? "");
                    showValues([$input => "value"]);
                    showValues(["value" => $input]);',
                'error_message' => 'TaintedHtml',
            ],
            'keyOfAnArrayPassedToASpecializedFunctionFromOneOfManyCalls' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    function render(array $attr): string {
                        $html = [];
                        foreach ($attr as $name => $value) {
                            $html[] = $name . "=\"" . htmlspecialchars((string) $value, ENT_QUOTES) . "\"";
                        }
                        return implode(" ", $html);
                    }

                    /** @psalm-taint-specialize */
                    function tag(array $attr): string {
                        return "<a " . render($attr) . ">";
                    }
' . implode('', array_map(
                    static fn(int $i): string => 'echo tag(["k' . $i . '" => (string) $_GET["q"]]);' . "\n",
                    range(1, 1100),
                )) . '
                    echo tag([(string) $_GET["q"] => "x"]);',
                'error_message' => 'TaintedHtml',
            ],
            'keyFetchInACallOfACallMadeWhereTheFlowsObservedTwoOpenAssignmentsTakesKeys' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    function render(array $attr): string {
                        $html = [];
                        foreach ($attr as $name => $value) {
                            $html[] = $name . "=\"" . htmlspecialchars((string) $value, ENT_QUOTES) . "\"";
                        }
                        return implode(" ", $html);
                    }

                    /** @psalm-taint-specialize */
                    function decorate(array $attr): string {
                        return "<a " . render($attr) . ">";
                    }

                    /** @psalm-taint-specialize */
                    function anchor(array $opts): string {
                        return decorate($opts["wrap"]["attr"]);
                    }

                    echo anchor(["wrap" => ["attr" => [(string) $_GET["q"] => "x"]]]);',
                'error_message' => 'TaintedHtml',
            ],
            'keyFetchInACallMadeWhereTheFlowsObservedTwoOpenAssignmentsTakesKeys' => [
                'code' => '<?php // --taint-analysis
                    /** @psalm-taint-specialize */
                    function prepare(array $attr): array {
                        foreach ($attr as $value) {
                            if (is_array($value)) {
                                foreach ($value as $key => $_) {
                                    $attr["keys"] = $key;
                                }
                            }
                        }
                        return $attr;
                    }

                    /** @psalm-taint-specialize */
                    function render(array $attr): string {
                        $html = "";
                        foreach ($attr as $name => $_) {
                            $html .= $name . " ";
                        }
                        return $html;
                    }

                    /** @psalm-taint-specialize */
                    function tag(array $holder): string {
                        return render(prepare($holder["attr"]));
                    }

                    echo tag(["attr" => [(string) $_GET["p"] => "x"]]);',
                'error_message' => 'TaintedHtml',
            ],
            'keyFetchTwoCallsDownTakesTheKeysOfAKeyFetchedOneCallDown' => [
                'code' => '<?php // --taint-analysis
                    /** @psalm-taint-specialize */
                    function keys(array $attr): string {
                        $out = "";
                        foreach ($attr as $k => $_) {
                            $out .= $k;
                        }
                        return $out;
                    }

                    /** @psalm-taint-specialize */
                    function render(array $data): string {
                        return keys($data["attr"]);
                    }

                    echo render(["attr" => [(string) $_GET["p"] => 1]]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintAPathResolvedFromGlobalsHoldingTheArguments' => [
                'code' => '<?php
                    function path(string $name): string {
                        return isset($GLOBALS[$name]) && is_string($GLOBALS[$name]) ? $GLOBALS[$name] : "";
                    }

                    function args(): array {
                        global $argv;
                        return (array) $argv;
                    }

                    echo count(args());
                    unlink(path("file"));',
                'error_message' => 'TaintedFile',
            ],
            'taintSinkWithoutParameter' => [
                'code' => '<?php
                    /** @psalm-taint-sink html */
                    function show(string $text): void {}',
                'error_message' => 'expects 2 arguments',
            ],
            'taintSinkOfAParameterThatDoesNotExist' => [
                'code' => '<?php
                    /** @psalm-taint-sink html $txt */
                    function show(string $text): void {}',
                'error_message' => 'names $txt, which is not a parameter',
            ],
            'assertUntaintedOfAParameterThatDoesNotExist' => [
                'code' => '<?php
                    /** @psalm-assert-untainted $txt */
                    function check(string $text): void {}',
                'error_message' => 'names $txt, which is not a parameter',
            ],
            'flowIntoNeitherTheReturnNorAKeyedStore' => [
                'code' => '<?php
                    /** @psalm-flow ($text) -(html)-> $other */
                    function wrap(string $text, string $other): string {
                        return $text . $other;
                    }',
                'error_message' => 'is neither',
            ],
            'keyedStoreWriteUnderAKeyThatIsNotAParameter' => [
                'code' => '<?php
                    /** @psalm-flow ($value) -> Store::$data[key] */
                    function store_set(string $key, string $value): void {}',
                'error_message' => 'is neither',
            ],
            'flowFromWhatACallableThatIsNotAParameterReturns' => [
                'code' => '<?php
                    /** @psalm-flow ($fn()) -> return */
                    function call(callable $callback): string {
                        return "";
                    }',
                'error_message' => 'names $fn, which is not a parameter',
            ],
            'proxyFlowIntoNeitherTheReturnNorNothing' => [
                'code' => '<?php
                    /** @psalm-flow proxy exec($cmd) -> $out */
                    function run(string $cmd, string $out): void {}',
                'error_message' => 'is neither',
            ],
            'keyedStoreReadUnderAParameterThatDoesNotExist' => [
                'code' => '<?php
                    /** @psalm-flow Store::$data[$k] -> return */
                    function store_get(string $key): string {
                        return $key;
                    }',
                'error_message' => 'names $k, which is not a parameter',
            ],
            'keyedStoreReadUnderAKeyThatIsNotAParameter' => [
                'code' => '<?php
                    /** @psalm-flow Store::$data[key] -> return */
                    function store_get(string $key): string {
                        return $key;
                    }',
                'error_message' => 'is neither',
            ],
            'taintedThroughAKeyedStoreOfAFullyQualifiedClassFromAnotherNamespace' => [
                'code' => '<?php
                    namespace App\Writer {
                        final class Writer {
                            /** @psalm-flow ($value) -> \Cache::$data[$key] */
                            public static function set(string $key, string $value): void {}
                        }
                    }

                    namespace App\Reader {
                        final class Reader {
                            /**
                             * @param list<string> $keys
                             * @return list<string>
                             * @psalm-flow \Cache::$data[$keys[*]] -> return
                             */
                            public static function mGet(array $keys): array {
                                return [];
                            }
                        }
                    }

                    namespace {
                        App\Writer\Writer::set("a", (string) $_GET["x"]);
                        echo implode(",", App\Reader\Reader::mGet(["a"]));
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'flowFromAParameterThatDoesNotExist' => [
                'code' => '<?php
                    /** @psalm-flow ($txt) -> return */
                    function wrap(string $text): string {
                        return "<b>" . $text . "</b>";
                    }',
                'error_message' => 'names $txt, which is not a parameter',
            ],
            'proxyFlowFromAParameterThatDoesNotExist' => [
                'code' => '<?php
                    /** @psalm-flow proxy exec($cmd) */
                    function run(string $command): void {}',
                'error_message' => 'names $cmd, which is not a parameter',
            ],
            'keyedStoreFlowWhoseKeyIsNotAParameter' => [
                'code' => '<?php
                    /** @psalm-flow Store::$data[$k] -> return */
                    function store_get(string $key): string {
                        return "";
                    }',
                'error_message' => 'names $k, which is not a parameter',
            ],
            'flowOfNoKnownForm' => [
                'code' => '<?php
                    /** @psalm-flow $text to return */
                    function wrap(string $text): string {
                        return "<b>" . $text . "</b>";
                    }',
                'error_message' => 'is neither',
            ],
            'taintGlobalsItemSetInOneFunctionAndReadInAnother' => [
                'code' => '<?php
                    function stash(): void {
                        $GLOBALS["value"] = (string) $_GET["a"];
                    }

                    function show(): void {
                        echo (string) $GLOBALS["value"];
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintGlobalVariableSetInOneFunctionAndReadInAnother' => [
                'code' => '<?php
                    function stash(): void {
                        global $value;
                        $value = (string) $_GET["a"];
                    }

                    function show(): void {
                        global $value;
                        echo (string) $value;
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintGlobalVariableSetThroughGlobalsAndReadAsGlobal' => [
                'code' => '<?php
                    function stash(): void {
                        $GLOBALS["value"] = (string) $_GET["a"];
                    }

                    function show(): void {
                        global $value;
                        echo (string) $value;
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintGlobalVariableSetAsGlobalAndReadThroughGlobals' => [
                'code' => '<?php
                    function stash(): void {
                        global $value;
                        $value = (string) $_GET["a"];
                    }

                    function show(): void {
                        echo (string) $GLOBALS["value"];
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintGlobalVariableAppendedTo' => [
                'code' => '<?php
                    function stash(): void {
                        global $values;
                        $values[] = (string) $_GET["a"];
                    }

                    function show(): void {
                        global $values;
                        foreach ((array) $values as $value) {
                            echo (string) $value;
                        }
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintNestedGlobalsItem' => [
                'code' => '<?php
                    function stash(): void {
                        $GLOBALS["config"]["value"] = (string) $_GET["a"];
                    }

                    function show(): void {
                        echo (string) $GLOBALS["config"]["value"];
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintGlobalsItemSetByDynamicName' => [
                'code' => '<?php
                    function stash(string $name): void {
                        $GLOBALS[$name] = (string) $_GET["a"];
                    }

                    function show(): void {
                        echo (string) $GLOBALS["value"];
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintGlobalsItemReadByDynamicName' => [
                'code' => '<?php
                    function stash(): void {
                        $GLOBALS["value"] = (string) $_GET["a"];
                    }

                    function show(string $name): void {
                        echo (string) $GLOBALS[$name];
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintGlobalsIteratedOver' => [
                'code' => '<?php
                    function stash(): void {
                        global $value;
                        $value = (string) $_GET["a"];
                    }

                    function show(): void {
                        foreach ($GLOBALS as $global) {
                            echo (string) $global;
                        }
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintGlobalsCopied' => [
                'code' => '<?php
                    function stash(): void {
                        $GLOBALS["value"] = (string) $_GET["a"];
                    }

                    function show(): void {
                        $globals = $GLOBALS;
                        echo (string) $globals["value"];
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintGlobalsExtracted' => [
                'code' => '<?php
                    function stash(): void {
                        $GLOBALS["value"] = (string) $_GET["a"];
                    }

                    function show(): void {
                        extract($GLOBALS);
                    }',
                'error_message' => 'TaintedExtract',
            ],
            'taintGlobalsItemMemoizedByAFunction' => [
                'code' => '<?php
                    function previous(string $value): string {
                        $previous = (string) ($GLOBALS["previous"] ?? "");
                        $GLOBALS["previous"] = $value;
                        return $previous;
                    }

                    previous((string) $_GET["a"]);
                    echo previous("safe");',
                'error_message' => 'TaintedHtml',
            ],
            'taintGlobalsItemSetInSpecializedCallAndReadInAnother' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    function stash(string $value): void {
                        $GLOBALS["value"] = $value;
                    }

                    /** @psalm-taint-specialize */
                    function show(): string {
                        return (string) $GLOBALS["value"];
                    }

                    stash("safe");
                    stash((string) $_GET["a"]);
                    echo show();',
                'error_message' => 'TaintedHtml',
            ],
            'taintGlobalVariableSetInSpecializedCallAndReadInAnother' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    function stash(string $value): void {
                        global $stashed;
                        $stashed = $value;
                    }

                    /** @psalm-taint-specialize */
                    function show(): string {
                        global $stashed;
                        return (string) $stashed;
                    }

                    stash("safe");
                    stash((string) $_GET["a"]);
                    echo show();',
                'error_message' => 'TaintedHtml',
            ],
            'taintTopLevelVariableReadAsGlobal' => [
                'code' => '<?php
                    $value = (string) $_GET["a"];

                    function show(): void {
                        global $value;
                        echo (string) $value;
                    }

                    show();',
                'error_message' => 'TaintedHtml',
            ],
            'taintTopLevelVariableReadThroughGlobals' => [
                'code' => '<?php
                    $value = (string) $_GET["a"];

                    function show(): void {
                        echo (string) $GLOBALS["value"];
                    }

                    show();',
                'error_message' => 'TaintedHtml',
            ],
            'taintTopLevelArrayItemReadAsGlobal' => [
                'code' => '<?php
                    $config = [];
                    $config["value"] = (string) $_GET["a"];

                    function show(): void {
                        global $config;
                        echo (string) $config["value"];
                    }

                    show();',
                'error_message' => 'TaintedHtml',
            ],
            'taintTopLevelVariableAssignedInABranch' => [
                'code' => '<?php
                    if (rand(0, 1)) {
                        $value = (string) $_GET["a"];
                    }

                    function show(): void {
                        global $value;
                        echo (string) $value;
                    }

                    show();',
                'error_message' => 'TaintedHtml',
            ],
            'taintTopLevelVariableOverwrittenAfterACall' => [
                'code' => '<?php
                    function show(): void {
                        global $value;
                        echo (string) $value;
                    }

                    $value = (string) $_GET["a"];
                    show();
                    $value = "safe";',
                'error_message' => 'TaintedHtml',
            ],
            'taintTopLevelVariableSetByAFunction' => [
                'code' => '<?php
                    function stash(): void {
                        global $value;
                        $value = (string) $_GET["a"];
                    }

                    stash();
                    echo (string) $value;',
                'error_message' => 'TaintedHtml',
            ],
            'taintTopLevelVariableSetByAStaticMethod' => [
                'code' => '<?php
                    class Stash {
                        public static function stash(): void {
                            global $value;
                            $value = (string) $_GET["a"];
                        }
                    }

                    $value = "safe";
                    Stash::stash();
                    echo (string) $value;',
                'error_message' => 'TaintedHtml',
            ],
            'taintTopLevelVariableSetByAnInstanceMethod' => [
                'code' => '<?php
                    class Stash {
                        public function stash(): void {
                            global $value;
                            $value = (string) $_GET["a"];
                        }
                    }

                    $value = "safe";
                    (new Stash())->stash();
                    echo (string) $value;',
                'error_message' => 'TaintedHtml',
            ],
            'taintTopLevelVariableSetThroughGlobalsByAFunction' => [
                'code' => '<?php
                    function stash(): void {
                        $GLOBALS["value"] = (string) $_GET["a"];
                    }

                    $value = "safe";
                    stash();
                    echo (string) $value;',
                'error_message' => 'TaintedHtml',
            ],
            'taintTopLevelVariableSetByAFunctionThroughAnother' => [
                'code' => '<?php
                    function stash(): void {
                        global $value;
                        $value = (string) $_GET["a"];
                    }

                    function stashLater(): void {
                        stash();
                    }

                    $value = "safe";
                    stashLater();
                    echo (string) $value;',
                'error_message' => 'TaintedHtml',
            ],
            'taintTopLevelVariableSetByAMethodThroughAnother' => [
                'code' => '<?php
                    namespace Ns;

                    class Stash {
                        public static function stash(): void {
                            $GLOBALS["value"] = (string) $_GET["a"];
                        }

                        public function stashLater(): void {
                            self::stash();
                        }

                        public function stashLaterStill(): void {
                            $this->stashLater();
                        }
                    }

                    $value = "safe";
                    (new Stash())->stashLaterStill();
                    echo (string) $value;',
                'error_message' => 'TaintedHtml',
            ],
            'taintTopLevelVariableSetByAFunctionThroughANamespacedOne' => [
                'code' => '<?php
                    namespace Ns;

                    function stash(): void {
                        global $value;
                        $value = (string) $_GET["a"];
                    }

                    function stashLater(): void {
                        stash();
                    }

                    $value = "safe";
                    \Ns\stashLater();
                    echo (string) $value;',
                'error_message' => 'TaintedHtml',
            ],
            'taintGlobalVariableOverwrittenAfterACall' => [
                'code' => '<?php
                    function show(): void {
                        global $value;
                        echo (string) $value;
                    }

                    function stash(): void {
                        global $value;
                        $value = (string) $_GET["a"];
                        show();
                        $value = "safe";
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintGlobalVariableChangedByACall' => [
                'code' => '<?php
                    function stash(): void {
                        global $value;
                        $value = (string) $_GET["a"];
                    }

                    function show(): void {
                        global $value;
                        $value = "safe";
                        stash();
                        echo (string) $value;
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintGlobalVariableAppendedToThenReset' => [
                'code' => '<?php
                    function show(): void {
                        global $values;
                        foreach ((array) $values as $value) {
                            echo (string) $value;
                        }
                    }

                    function stash(): void {
                        global $values;
                        $values[] = (string) $_GET["a"];
                        show();
                        $values = [];
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'conditionalSinkOfARawMode' => [
                'code' => '<?php
                    /** @psalm-taint-sink ($mode is 0|1|2 ? null : \'html\') $content */
                    function render(string $content, int $mode = 0): void {}

                    render((string) $_GET["x"], 3);',
                'error_message' => 'TaintedHtml',
            ],
            'conditionalSinkOfAModeNotKnown' => [
                'code' => '<?php
                    /** @psalm-taint-sink ($mode is 0|1|2 ? null : \'html\') $content */
                    function render(string $content, int $mode = 0): void {}

                    function show(int $mode): void {
                        render((string) $_GET["x"], $mode);
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'conditionalSinkOfARawDefaultMode' => [
                'code' => '<?php
                    /** @psalm-taint-sink ($mode is 0|1|2 ? null : \'html\') $content */
                    function render(string $content, int $mode = 3): void {}

                    render((string) $_GET["x"]);',
                'error_message' => 'TaintedHtml',
            ],
            'conditionalSinkOfAMethod' => [
                'code' => '<?php
                    final class Component {
                        /** @psalm-taint-sink ($mode is 0|1|2 ? null : \'html\') $content */
                        public function show(string $content, int $mode): void {}
                    }

                    (new Component())->show((string) $_GET["x"], 3);',
                'error_message' => 'TaintedHtml',
            ],
            'conditionalSinkOfAStaticMethod' => [
                'code' => '<?php
                    final class Component {
                        /** @psalm-taint-sink ($mode is 0|1|2 ? null : \'html\') $content */
                        public static function format(string $content, int $mode): void {}
                    }

                    Component::format((string) $_GET["x"], 3);',
                'error_message' => 'TaintedHtml',
            ],
            'conditionalSinkOfAKeyOfTheOptionsOfAConstructor' => [
                'code' => '<?php
                    final class Component {
                        /**
                         * @param array{render_way?: int, ...} $options
                         * @psalm-taint-sink ($options is array{render_way?: 0|1|2, ...} ? null : \'html\') $content
                         */
                        public function __construct(string $content, array $options = []) {}
                    }

                    new Component((string) $_GET["x"], ["render_way" => 3]);',
                'error_message' => 'TaintedHtml',
            ],
            'conditionalSinkOfAFirstClassCallable' => [
                'code' => '<?php
                    /** @psalm-taint-sink ($mode is 0|1|2 ? null : \'html\') $content */
                    function render(string $content, int $mode = 0): void {}

                    $render = render(...);
                    $render((string) $_GET["x"], 3);',
                'error_message' => 'TaintedHtml',
            ],
            'conditionalSinkOfAClosure' => [
                'code' => '<?php
                    $render =
                        /** @psalm-taint-sink ($mode is 0|1|2 ? null : \'html\') $content */
                        function (string $content, int $mode = 0): void {};

                    $render((string) $_GET["x"], 3);',
                'error_message' => 'TaintedHtml',
            ],
            'conditionalSinkOfAParameterThatDoesNotExist' => [
                'code' => '<?php
                    /** @psalm-taint-sink ($mode is 0 ? null : \'html\') $text */
                    function render(string $content, int $mode): void {}',
                'error_message' => 'names $text, which is not a parameter',
            ],
            'conditionalSinkThatIsNotAConditional' => [
                'code' => '<?php
                    /** @psalm-taint-sink (string) $content */
                    function render(string $content): void {}',
                'error_message' => 'must be a conditional',
            ],
            'propertySetByNameIsReadByAFetchOfIt' => [
                'code' => '<?php
                    /** @property string $title */
                    abstract class Model {
                        abstract public function __get(string $name): mixed;

                        /** @psalm-flow ($value) -> $this->$name */
                        abstract public function set(string $name, string $value): void;
                    }

                    function save(Model $model): void {
                        $model->set("title", (string) $_GET["x"]);
                    }

                    function show(Model $model): void {
                        echo $model->title;
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'propertyAssignedIsReturnedByAGetterByName' => [
                'code' => '<?php
                    final class Model {
                        public string $title = "";

                        /** @psalm-flow $this->$name -> return */
                        public function get(string $name): string {
                            return "";
                        }
                    }

                    function save(Model $model): void {
                        $model->title = (string) $_GET["x"];
                    }

                    function show(Model $model): void {
                        echo $model->get("title");
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'propertySetByNameThroughAParentClassIsReadThroughTheSubclassWithItsTag' => [
                'code' => '<?php
                    abstract class Model {
                        abstract public function __get(string $name): mixed;

                        /** @psalm-flow ($value) -> $this->$name */
                        abstract public function set(string $name, string $value): void;
                    }

                    /** @property string $title */
                    abstract class Article extends Model {}

                    function save(Model $model): void {
                        $model->set("title", (string) $_GET["x"]);
                    }

                    function show(Article $article): void {
                        echo $article->title;
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'propertySetThroughASubclassIsReturnedByAGetterOfTheParentClass' => [
                'code' => '<?php
                    class Model {
                        public string $title = "";

                        /** @psalm-flow $this->$name -> return */
                        public function get(string $name): string {
                            return "";
                        }
                    }

                    final class Article extends Model {}

                    function save(Article $article): void {
                        $article->title = (string) $_GET["x"];
                    }

                    function show(Model $model): void {
                        echo $model->get("title");
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'propertySetUnderANameNotKnownIsEachProperty' => [
                'code' => '<?php
                    final class Model {
                        public string $title = "";

                        /** @psalm-flow ($value) -> $this->$name */
                        public function set(string $name, string $value): void {}
                    }

                    function save(Model $model, string $name): void {
                        $model->set($name, (string) $_GET["x"]);
                    }

                    function show(Model $model): void {
                        echo $model->title;
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'propertySetUnderANameNotKnownIsReadByAGetterOfAnUndeclaredName' => [
                'code' => '<?php
                    final class Model {
                        /** @psalm-flow ($value) -> $this->$name */
                        public function set(string $name, string $value): void {}

                        /** @psalm-flow $this->$name -> return */
                        public function get(string $name): string {
                            return "";
                        }
                    }

                    function save(Model $model, string $name): void {
                        $model->set($name, (string) $_GET["x"]);
                    }

                    function show(Model $model): void {
                        echo $model->get("anything");
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'propertySetUnderANameNotKnownThroughAParentTypeIsReadThroughASubclass' => [
                'code' => '<?php
                    abstract class Bag {
                        /** @psalm-flow ($value) -> $this->$name */
                        public function set(string $name, string $value): void {}

                        /** @psalm-flow $this->$name -> return */
                        public function get(string $name): string {
                            return "";
                        }
                    }

                    final class Query extends Bag {}

                    function save(Bag $bag, string $name): void {
                        $bag->set($name, (string) $_GET["x"]);
                    }

                    function show(Query $query): void {
                        echo $query->get("other");
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'propertySetUnderANameNotKnownThroughASubclassIsReadThroughAnInterface' => [
                'code' => '<?php
                    interface PropertyObject {
                        /** @psalm-flow ($value) -> $this->$name */
                        public function set(string $name, string $value): void;

                        /** @psalm-flow $this->$name -> return */
                        public function get(string $name): string;
                    }

                    abstract class Bag implements PropertyObject {
                        /** @psalm-flow ($value) -> $this->$name */
                        public function set(string $name, string $value): void {}

                        /** @psalm-flow $this->$name -> return */
                        public function get(string $name): string {
                            return "";
                        }
                    }

                    final class Query extends Bag {}

                    function save(Query $query, string $name): void {
                        $query->set($name, (string) $_GET["x"]);
                    }

                    function show(PropertyObject $object): void {
                        echo $object->get("other");
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'propertySetUnderAKnownStartIsThePropertiesStartingWithIt' => [
                'code' => '<?php
                    final class Model {
                        public string $meta_title = "";

                        /** @psalm-flow ($value) -> $this->$name */
                        public function set(string $name, string $value): void {}
                    }

                    function save(Model $model, string $suffix): void {
                        $model->set("meta_" . $suffix, (string) $_GET["x"]);
                    }

                    function show(Model $model): void {
                        echo $model->meta_title;
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'propertiesAreAllReturnedByAGetterOfAny' => [
                'code' => '<?php
                    final class Model {
                        public string $title = "";

                        /**
                         * @return array<string, string>
                         * @psalm-flow $this->* -> return
                         */
                        public function toArray(): array {
                            return [];
                        }
                    }

                    function save(Model $model): void {
                        $model->title = (string) $_GET["x"];
                    }

                    function show(Model $model): void {
                        echo implode(",", $model->toArray());
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'propertySetByNameThroughParentIsReadByAFetchOfIt' => [
                'code' => '<?php
                    class Model {
                        public string $title = "";

                        /** @psalm-flow ($value) -> $this->$name */
                        public function set(string $name, string $value): void {}
                    }

                    final class Article extends Model {
                        public function save(): void {
                            parent::set("title", (string) $_GET["x"]);
                        }
                    }

                    function show(Article $article): void {
                        echo $article->title;
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'propertySetByNameThroughParentInASpecializedSubclassIsReadByAFetchOfIt' => [
                'code' => '<?php // --taint-analysis
                    class Model {
                        public string $title = "";

                        /**
                         * @psalm-flow ($value) -> $this->$name
                         * @psalm-capabilities write-this-props
                         */
                        public function set(string $name, string $value): void {}
                    }

                    /**
                     * @psalm-taint-specialize
                     * @psalm-suppress MutableDependency
                     */
                    final class Article extends Model {
                        public function __construct(string $title) {
                            parent::set("title", $title);
                        }
                    }

                    $article = new Article((string) $_GET["x"]);
                    echo $article->title;',
                'error_message' => 'TaintedHtml',
            ],
            'propertySetByNameOfAnObjectSpecializedIsReturnedByItsGetter' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    final class Model {
                        /** @psalm-flow ($value) -> $this->$name */
                        public function set(string $name, string $value): void {}

                        /** @psalm-flow $this->$name -> return */
                        public function get(string $name): string {
                            return "";
                        }
                    }

                    $model = new Model();
                    $model->set("title", (string) $_GET["x"]);
                    echo $model->get("title");',
                'error_message' => 'TaintedHtml',
            ],
            'propertyFlowOfAFunction' => [
                'code' => '<?php
                    /** @psalm-flow ($value) -> $this->$name */
                    function set(string $name, string $value): void {}',
                'error_message' => 'names a property of $this',
            ],
            'propertyFlowOfAStaticMethod' => [
                'code' => '<?php
                    final class Model {
                        /** @psalm-flow $this->$name -> return */
                        public static function get(string $name): string {
                            return "";
                        }
                    }',
                'error_message' => 'names a property of $this',
            ],
            'propertyFlowNamedByAParameterThatDoesNotExist' => [
                'code' => '<?php
                    final class Model {
                        /** @psalm-flow ($value) -> $this->$key */
                        public function set(string $name, string $value): void {}
                    }',
                'error_message' => 'names $key, which is not a parameter',
            ],
            'propertyFlowFromWhatACallableReturns' => [
                'code' => '<?php
                    final class Model {
                        /** @psalm-flow ($compute()) -> $this->$name */
                        public function set(string $name, callable $compute): void {}
                    }',
                'error_message' => 'is neither',
            ],
            'propertySetByKeyIsReadByAFetchOfIt' => [
                'code' => '<?php
                    final class Model {
                        public string $title = "";
                        public string $body = "";

                        /** @psalm-flow ($values) -> $this->{key} */
                        public function setProperties(array $values): void {}

                        /**
                         * @return array<string, string>
                         * @psalm-flow $this->{key} -> return[key]
                         */
                        public function toArray(): array {
                            return [];
                        }
                    }

                    function save(Model $model): void {
                        $model->setProperties(["title" => (string) $_GET["x"], "body" => "safe"]);
                    }

                    function show(Model $model): void {
                        echo $model->title;
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'propertiesSetFromAnArrayOfKeysNotKnownAreEachProperty' => [
                'code' => '<?php
                    final class Model {
                        public string $title = "";
                        public string $body = "";

                        /** @psalm-flow ($values) -> $this->{key} */
                        public function setProperties(array $values): void {}

                        /**
                         * @return array<string, string>
                         * @psalm-flow $this->{key} -> return[key]
                         */
                        public function toArray(): array {
                            return [];
                        }
                    }

                    function save(Model $model): void {
                        $model->setProperties($_GET);
                    }

                    function show(Model $model): void {
                        echo $model->body;
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'propertyReturnedByKeyIsReadUnderItsKey' => [
                'code' => '<?php
                    final class Model {
                        public string $title = "";
                        public string $body = "";

                        /** @psalm-flow ($values) -> $this->{key} */
                        public function setProperties(array $values): void {}

                        /**
                         * @return array<string, string>
                         * @psalm-flow $this->{key} -> return[key]
                         */
                        public function toArray(): array {
                            return [];
                        }
                    }

                    function save(Model $model): void {
                        $model->title = (string) $_GET["x"];
                    }

                    function show(Model $model): void {
                        echo $model->toArray()["title"];
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'propertiesReturnedByKeyAreEachReadUnderAKeyNotKnown' => [
                'code' => '<?php
                    final class Model {
                        public string $title = "";
                        public string $body = "";

                        /** @psalm-flow ($values) -> $this->{key} */
                        public function setProperties(array $values): void {}

                        /**
                         * @return array<string, string>
                         * @psalm-flow $this->{key} -> return[key]
                         */
                        public function toArray(): array {
                            return [];
                        }
                    }

                    function save(Model $model): void {
                        $model->title = (string) $_GET["x"];
                    }

                    function show(Model $model, string $key): void {
                        echo $model->toArray()[$key];
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'propertySetByKeyNamingNoPropertyIsReturnedUnderIt' => [
                'code' => '<?php
                    final class Model {
                        public string $title = "";
                        public string $body = "";

                        /** @psalm-flow ($values) -> $this->{key} */
                        public function setProperties(array $values): void {}

                        /**
                         * @return array<string, string>
                         * @psalm-flow $this->{key} -> return[key]
                         */
                        public function toArray(): array {
                            return [];
                        }
                    }

                    function save(Model $model): void {
                        $model->setProperties(["undeclared" => (string) $_GET["x"]]);
                    }

                    function show(Model $model): void {
                        echo $model->toArray()["undeclared"];
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'propertySetByANameOfNoPropertyIsReturnedUnderIt' => [
                'code' => '<?php
                    final class Model {
                        public string $title = "";
                        public string $body = "";

                        /** @psalm-flow ($values) -> $this->{key} */
                        public function setProperties(array $values): void {}

                        /**
                         * @return array<string, string>
                         * @psalm-flow $this->{key} -> return[key]
                         */
                        public function toArray(): array {
                            return [];
                        }

                        /** @psalm-flow ($value) -> $this->$name */
                        public function set(string $name, string $value): void {}
                    }

                    function save(Model $model): void {
                        $model->set("undeclared", (string) $_GET["x"]);
                    }

                    function show(Model $model): void {
                        echo $model->toArray()["undeclared"];
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'propertiesSetByKeyThroughAParentClassAreReadThroughTheSubclassWithItsTag' => [
                'code' => '<?php
                    abstract class Model {
                        abstract public function __get(string $name): mixed;

                        /** @psalm-flow ($values) -> $this->{key} */
                        abstract public function setProperties(array $values): void;
                    }

                    /** @property string $title */
                    abstract class Article extends Model {}

                    function save(Model $model): void {
                        $model->setProperties(["title" => (string) $_GET["x"]]);
                    }

                    function show(Article $article): void {
                        echo $article->title;
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'propertySetThroughASubclassIsReturnedByKeyByTheParentClass' => [
                'code' => '<?php
                    class Model {
                        public string $title = "";

                        /**
                         * @return array<string, string>
                         * @psalm-flow $this->{key} -> return[key]
                         */
                        public function toArray(): array {
                            return [];
                        }
                    }

                    final class Article extends Model {}

                    function save(Article $article): void {
                        $article->title = (string) $_GET["x"];
                    }

                    function show(Model $model): void {
                        echo $model->toArray()["title"];
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'propertiesReturnedByKeyOfAnObjectSpecialized' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    final class Model {
                        /** @psalm-flow ($values) -> $this->{key} */
                        public function setProperties(array $values): void {}

                        /**
                         * @return array<string, string>
                         * @psalm-flow $this->{key} -> return[key]
                         */
                        public function toArray(): array {
                            return [];
                        }
                    }

                    $model = new Model();
                    $model->setProperties(["title" => (string) $_GET["x"]]);
                    echo $model->toArray()["title"];',
                'error_message' => 'TaintedHtml',
            ],
            'propertiesReadByKeyIntoAReturnWithoutKey' => [
                'code' => '<?php
                    final class Model {
                        /** @psalm-flow $this->{key} -> return */
                        public function toArray(): array {
                            return [];
                        }
                    }',
                'error_message' => 'is neither',
            ],
            'propertiesSetByKeyWithAPathType' => [
                'code' => '<?php
                    final class Model {
                        /** @psalm-flow ($values) -(arrayvalue-fetch)-> $this->{key} */
                        public function setProperties(array $values): void {}
                    }',
                'error_message' => 'is neither',
            ],
            'propertySetByNameInACopyLoop' => [
                'code' => '<?php
                    /**
                     * @property string $label
                     * @property string $size
                     */
                    final class Options {
                        public function __get(string $name): mixed {
                            return null;
                        }

                        /** @psalm-flow ($value) -> $this->$name */
                        public function set(string $name, string $value): void {}
                    }

                    function fillOptions(Options $options): void {
                        foreach (["label" => (string) $_GET["x"], "size" => "m"] as $name => $value) {
                            $options->set($name, $value);
                        }

                        echo $options->label;
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'propertyConstructedByNameInACopyLoopMayBeAnyOfThem' => [
                'code' => '<?php
                    /**
                     * @property string $label
                     * @property string $size
                     */
                    final class Options {
                        /** @psalm-flow ($value) -> $this->$name */
                        public function __construct(string $name, string $value) {}

                        public function __get(string $name): mixed {
                            return null;
                        }
                    }

                    foreach (["label" => (string) $_GET["x"], "size" => "m"] as $name => $value) {
                        $options = new Options($name, $value);
                        echo $options->size;
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'propertySetByNameInALoopOfAnotherValueMayBeAnyOfThem' => [
                'code' => '<?php
                    /**
                     * @property string $label
                     * @property string $size
                     */
                    final class Options {
                        public function __get(string $name): mixed {
                            return null;
                        }

                        /** @psalm-flow ($value) -> $this->$name */
                        public function set(string $name, string $value): void {}
                    }

                    function fillOptions(Options $options): void {
                        foreach (["label" => (string) $_GET["x"], "size" => "m"] as $name => $value) {
                            $options->set($name, trim($value));
                        }

                        echo $options->size;
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'propertyBagElementSetIsTheProperty' => [
                'code' => '<?php
                    /** @psalm-taint-property-bag $properties */
                    abstract class Bag {
                        /** @var array<string, mixed> */
                        protected array $properties = [];

                        public function __get(string $name): mixed {
                            return null;
                        }

                        public function __set(string $name, mixed $value): void {}
                    }

                    /** @property string $title */
                    final class Model extends Bag {
                        public function setTitle(string $title): void {
                            $this->properties["title"] = $title;
                        }
                    }

                    function save(Model $model): void {
                        $model->setTitle((string) $_GET["x"]);
                    }

                    function show(Model $model): void {
                        echo $model->title;
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'propertyBagElementReadIsTheProperty' => [
                'code' => '<?php
                    /** @psalm-taint-property-bag $properties */
                    abstract class Bag {
                        /** @var array<string, mixed> */
                        protected array $properties = [];

                        public function __get(string $name): mixed {
                            return null;
                        }

                        public function __set(string $name, mixed $value): void {}
                    }

                    /** @property string $title */
                    final class Model extends Bag {
                        public function getTitle(): string {
                            return (string) $this->properties["title"];
                        }
                    }

                    function save(Model $model): void {
                        $model->title = (string) $_GET["x"];
                    }

                    function show(Model $model): void {
                        echo $model->getTitle();
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'propertyBagElementSetUnderNestedKeys' => [
                'code' => '<?php
                    /** @psalm-taint-property-bag $properties */
                    abstract class Bag {
                        /** @var array<string, mixed> */
                        protected array $properties = [];

                        public function __get(string $name): mixed {
                            return null;
                        }

                        public function __set(string $name, mixed $value): void {}
                    }

                    /** @property array<string, string> $tags */
                    final class Model extends Bag {
                        public function setTag(string $tag): void {
                            $this->properties["tags"]["main"] = $tag;
                        }
                    }

                    function save(Model $model): void {
                        $model->setTag((string) $_GET["x"]);
                    }

                    function show(Model $model): void {
                        echo $model->tags["main"];
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'propertyBagElementReadSetThroughAReceiverFlow' => [
                'code' => '<?php
                    /** @psalm-taint-property-bag $properties */
                    abstract class Bag {
                        /** @var array<string, mixed> */
                        protected array $properties = [];

                        /** @psalm-flow ($value) -> $this->$name */
                        public function set(string $name, string $value): void {}
                    }

                    /** @property string $title */
                    final class Model extends Bag {
                        public function getTitle(): string {
                            return (string) $this->properties["title"];
                        }
                    }

                    function save(Model $model): void {
                        $model->set("title", (string) $_GET["x"]);
                    }

                    function show(Model $model): void {
                        echo $model->getTitle();
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'propertyBagOfAnObjectTrackedPerInstance' => [
                'code' => '<?php
                    /**
                     * @psalm-taint-property-bag $properties
                     * @psalm-taint-specialize
                     */
                    abstract class Bag {
                        /** @var array<string, mixed> */
                        protected array $properties = [];
                    }

                    /** @psalm-taint-specialize */
                    final class Model extends Bag {
                        public function __construct(string $title) {
                            $this->properties["title"] = $title;
                        }

                        public function getTitle(): string {
                            return (string) $this->properties["title"];
                        }
                    }

                    $model = new Model((string) $_GET["x"]);
                    echo $model->getTitle();',
                'error_message' => 'TaintedHtml',
            ],
            'propertyBagElementUnderANestedIntKey' => [
                'code' => '<?php
                    /** @psalm-taint-property-bag $properties */
                    abstract class Bag {
                        /** @var array<string, mixed> */
                        protected array $properties = [];

                        public function __get(string $name): mixed {
                            return null;
                        }
                    }

                    /** @property list<string> $tags */
                    final class Model extends Bag {
                        public function setTags(string $tag): void {
                            $this->properties["tags"][0] = $tag;
                            $this->properties["tags"][1] = "safe";
                        }
                    }

                    function save(Model $model): void {
                        $model->setTags((string) $_GET["x"]);
                    }

                    function show(Model $model): void {
                        echo $model->tags[0];
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'propertyBagThatIsNotAProperty' => [
                'code' => '<?php
                    /** @psalm-taint-property-bag properties */
                    abstract class Bag {}',
                'error_message' => 'InvalidDocblock',
            ],
            'elementOfANamedPropertySetAndReturned' => [
                'code' => '<?php
                    final class Request {
                        /** @var array<string, string> */
                        private array $vars = [];

                        /** @psalm-flow ($value) -> $this->vars[$var] */
                        public function set(string $var, string $value): void {}

                        /** @psalm-flow $this->vars[$var] -> return */
                        public function get(string $var): string {
                            return "";
                        }
                    }

                    function save(Request $request): void {
                        $request->set("name", (string) $_GET["x"]);
                    }

                    function show(Request $request): void {
                        echo $request->get("name");
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'elementOfANamedPropertySetUnderAnyKey' => [
                'code' => '<?php
                    final class Request {
                        /** @var array<string, string> */
                        private array $vars = [];

                        /** @psalm-flow ($value) -> $this->vars[*] */
                        public function add(string $value): void {}

                        /** @psalm-flow $this->vars[$var] -> return */
                        public function get(string $var): string {
                            return "";
                        }
                    }

                    function save(Request $request): void {
                        $request->add((string) $_GET["x"]);
                    }

                    function show(Request $request): void {
                        echo $request->get("name");
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'namedPropertyReturnedWithItsKeys' => [
                'code' => '<?php
                    final class Request {
                        /** @var array<string, string> */
                        private array $vars = [];

                        /** @psalm-flow ($value) -> $this->vars[$var] */
                        public function set(string $var, string $value): void {}

                        /**
                         * @return array<string, string>
                         * @psalm-flow $this->vars -> return
                         */
                        public function toArray(): array {
                            return [];
                        }
                    }

                    function save(Request $request): void {
                        $request->set("name", (string) $_GET["x"]);
                    }

                    function show(Request $request): void {
                        echo $request->toArray()["name"];
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'namedPropertySetAsADeclaredProperty' => [
                'code' => '<?php
                    final class Model {
                        public string $title = "";

                        /** @psalm-flow ($value) -> $this->title */
                        public function setTitle(string $value): void {}
                    }

                    function save(Model $model): void {
                        $model->setTitle((string) $_GET["x"]);
                    }

                    function show(Model $model): void {
                        echo $model->title;
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'namedPropertySetByTheConstructorWithItsTaintsPerInstance' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    final class Request {
                        /** @var array<string, string> */
                        private array $vars = [];

                        /**
                         * @param array<string, string> $vars
                         * @psalm-flow ($vars) -> $this->vars
                         */
                        public function __construct(array $vars) {}

                        /** @psalm-flow $this->vars[$var] -> return */
                        public function get(string $var): string {
                            return "";
                        }
                    }

                    $request = new Request(["name" => (string) $_GET["x"]]);
                    echo $request->get("name");',
                'error_message' => 'TaintedHtml',
            ],
            'elementOfANamedPropertySetWithItsTaintsPerInstance' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    final class Request {
                        /** @var array<string, string> */
                        private array $vars = [];

                        /** @psalm-flow ($value) -> $this->vars[$var] */
                        public function set(string $var, string $value): void {}

                        /** @psalm-flow $this->vars[$var] -> return */
                        public function get(string $var): string {
                            return "";
                        }
                    }

                    $request = new Request();
                    $request->set("name", (string) $_GET["x"]);
                    echo $request->get("name");',
                'error_message' => 'TaintedHtml',
            ],
            'propertiesSetByKeyByTheConstructor' => [
                'code' => '<?php
                    final class Model {
                        public string $title = "";

                        /**
                         * @param array<string, string> $values
                         * @psalm-flow ($values) -> $this->{key}
                         */
                        public function __construct(array $values) {}
                    }

                    new Model(["title" => (string) $_GET["x"]]);

                    function show(Model $model): void {
                        echo $model->title;
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'elementOfANamedPropertyWithAPathType' => [
                'code' => '<?php
                    final class Request {
                        /** @psalm-flow ($value) -(html)-> $this->vars[$var] */
                        public function set(string $var, string $value): void {}
                    }',
                'error_message' => 'is neither',
            ],
            'elementOfANamedPropertyUnderAKeyThatIsNotAParameter' => [
                'code' => '<?php
                    final class Request {
                        /** @psalm-flow $this->vars[$name] -> return */
                        public function get(string $var): string {
                            return "";
                        }
                    }',
                'error_message' => 'names $name, which is not a parameter',
            ],
            'cliOnlyCodeReturnsWhatPrintRWouldPrint' => [
                'code' => '<?php
                    /** @psalm-cli-only */
                    function dump(): string {
                        return print_r((string) $_GET["x"], true);
                    }

                    echo dump();',
                'error_message' => 'TaintedHtml',
            ],
            'cliOnlyCodeStillPrintsSecretsToTheTerminal' => [
                'code' => '<?php
                    /** @psalm-taint-source system_secret */
                    function systemSecret(): string { return ""; }

                    /** @psalm-cli-only */
                    function show(): void {
                        echo systemSecret();
                    }',
                'error_message' => 'TaintedSystemSecret',
            ],
            'cliOnlyCodeKeepsTheTaintsOfItsValuesForTheOtherSinks' => [
                'code' => '<?php
                    /** @psalm-cli-only */
                    function run(): void {
                        exec((string) $_GET["cmd"]);
                    }',
                'error_message' => 'TaintedShell',
            ],
            'codeCalledFromCliOnlyCodeIsNotCliOnly' => [
                'code' => '<?php
                    function show(string $text): void {
                        echo $text;
                    }

                    /** @psalm-cli-only */
                    function run(): void {
                        show((string) $_GET["x"]);
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'nosqlSinkTaintedByAnEqualityOperandInExpr' => [
                'code' => '<?php
                    final class Collection {
                        /** @psalm-taint-sink nosql $filter */
                        public function find(array $filter): void {}
                    }

                    function search(Collection $users): void {
                        // in $expr, an operand like "$name" is a field path
                        $users->find([\'$expr\' => [\'$eq\' => [\'$name\', $_GET["name"]]]]);
                    }',
                'error_message' => 'TaintedNosql',
            ],
            'nosqlSinkTaintedByAnEqualityOperandBelowTheFieldDocument' => [
                'code' => '<?php
                    final class Collection {
                        /** @psalm-taint-sink nosql $filter */
                        public function find(array $filter): void {}
                    }

                    function search(Collection $users): void {
                        $users->find(["name" => [\'$not\' => [\'$eq\' => $_GET["name"]]]]);
                    }',
                'error_message' => 'TaintedNosql',
            ],
            'nosqlSinkTaintedByAnotherOperatorOfTheFieldDocument' => [
                'code' => '<?php
                    final class Collection {
                        /** @psalm-taint-sink nosql $filter */
                        public function find(array $filter): void {}
                    }

                    function search(Collection $users): void {
                        $users->find(["name" => [\'$eq\' => "a", \'$in\' => $_GET["names"]]]);
                    }',
                'error_message' => 'TaintedNosql',
            ],
            'nosqlSinkTaintedByAnEqualityOperandOfAFieldNotLiteral' => [
                'code' => '<?php
                    final class Collection {
                        /** @psalm-taint-sink nosql $filter */
                        public function find(array $filter): void {}
                    }

                    function search(Collection $users, string $field): void {
                        $users->find([$field => [\'$eq\' => $_GET["name"]]]);
                    }',
                'error_message' => 'TaintedNosql',
            ],
            'nosqlSinkTaintedByAnEqualityOperandAnOverrideMayTakeAnywhere' => [
                'code' => '<?php
                    class Collection {
                        /** @psalm-taint-sink nosql $filter */
                        public function find(array $filter): void {}

                        /** @psalm-taint-sink nosql $pipeline */
                        public function aggregate(array $pipeline): void {}
                    }

                    final class ExprCollection extends Collection {
                        #[Override]
                        public function find(array $filter): void {
                            $this->aggregate([[\'$match\' => [\'$expr\' => $filter]]]);
                        }
                    }

                    function search(Collection $users): void {
                        $users->find(["name" => [\'$eq\' => $_GET["name"]]]);
                    }',
                'error_message' => 'TaintedNosql',
            ],
            'keyedStoreExactKey' => [
                'code' => '<?php
                    /** @psalm-flow ($value) -> Store::$data[$key] */
                    function store_set(string $key, string $value): void {}

                    /** @psalm-flow Store::$data[$key] -> return */
                    function store_get(string $key): string {}

                    store_set("a", (string) $_GET["x"]);
                    echo store_get("a");',
                'error_message' => 'TaintedHtml',
            ],
            'keyedStoreWriteUnderAKnownStart' => [
                'code' => '<?php
                    /** @psalm-flow ($value) -> Store::$data[$key] */
                    function store_set(string $key, string $value): void {}

                    /** @psalm-flow Store::$data[$key] -> return */
                    function store_get(string $key): string {}

                    function write(string $id): void {
                        store_set("lock_" . $id, (string) $_GET["x"]);
                    }

                    echo store_get("lock_1");',
                'error_message' => 'TaintedHtml',
            ],
            'keyedStoreReadUnderAKnownStart' => [
                'code' => '<?php
                    /** @psalm-flow ($value) -> Store::$data[$key] */
                    function store_set(string $key, string $value): void {}

                    /** @psalm-flow Store::$data[$key] -> return */
                    function store_get(string $key): string {}

                    store_set("lock_1", (string) $_GET["x"]);

                    function read(string $id): void {
                        echo store_get("lock_" . $id);
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'keyedStoreUnknownKeyReadsEveryKey' => [
                'code' => '<?php
                    /** @psalm-flow ($value) -> Store::$data[$key] */
                    function store_set(string $key, string $value): void {}

                    /** @psalm-flow Store::$data[$key] -> return */
                    function store_get(string $key): string {}

                    store_set("a", (string) $_GET["x"]);

                    function read(string $key): void {
                        echo store_get($key);
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'keyedStoreKeyStartFromSprintfThroughAVariable' => [
                'code' => '<?php
                    /** @psalm-flow ($value) -> Store::$data[$key] */
                    function store_set(string $key, string $value): void {}

                    /** @psalm-flow Store::$data[$key] -> return */
                    function store_get(string $key): string {}

                    function write(string $id): void {
                        $key = sprintf("spr_%s", $id);
                        store_set($key, (string) $_GET["x"]);
                    }

                    echo store_get("spr_1");',
                'error_message' => 'TaintedHtml',
            ],
            'keyedStoreKeyStartFromImplode' => [
                'code' => '<?php
                    /** @psalm-flow ($value) -> Store::$data[$key] */
                    function store_set(string $key, string $value): void {}

                    /** @psalm-flow Store::$data[$key] -> return */
                    function store_get(string $key): string {}

                    function write(string $id): void {
                        store_set(implode("_", ["call", "back", $id]), (string) $_GET["x"]);
                    }

                    echo store_get("call_back_1");',
                'error_message' => 'TaintedHtml',
            ],
            'keyedStoreKeyStartOfAClosureUse' => [
                'code' => '<?php
                    /** @psalm-flow ($value) -> Store::$data[$key] */
                    function store_set(string $key, string $value): void {}

                    /** @psalm-flow Store::$data[$key] -> return */
                    function store_get(string $key): string {}

                    store_set("lock_1", (string) $_GET["x"]);

                    function read(string $id): void {
                        $key = "lock_" . $id;
                        $read = function () use ($key): void {
                            echo store_get($key);
                        };
                        $read();
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'keyedStoreKeyStartFromAConstructorArgument' => [
                'code' => '<?php
                    /** @psalm-flow ($value) -> Store::$data[$key] */
                    function store_set(string $key, string $value): void {}

                    /** @psalm-flow Store::$data[$key] -> return */
                    function store_get(string $key): string {}

                    final class Prefixed {
                        public function __construct(private string $prefix) {}

                        public function write(string $id, string $value): void {
                            store_set($this->prefix . $id, $value);
                        }
                    }

                    final class Writer {
                        public static function write(): void {
                            (new Prefixed("pfx_"))->write((string) $_GET["id"], (string) $_GET["x"]);
                        }
                    }

                    echo store_get("pfx_9");',
                'error_message' => 'TaintedHtml',
            ],
            'keyedStoreListOfKeys' => [
                'code' => '<?php
                    /** @psalm-flow ($value) -> Store::$data[$key] */
                    function store_set(string $key, string $value): void {}

                    /** @psalm-flow Store::$data[$key] -> return */
                    function store_get(string $key): string {}

                    /** @psalm-flow Store::$data[$keys[*]] -> return */
                    function store_get_many(array $keys): string {}

                    store_set("a", (string) $_GET["x"]);
                    echo store_get_many(["b", "a"]);',
                'error_message' => 'TaintedHtml',
            ],
            'keyedStoreKeyStartsOfAppendedItemsAreWhatAllStartWith' => [
                'code' => '<?php
                    /** @psalm-flow ($value) -> Store::$data[$key] */
                    function store_set(string $key, string $value): void {}

                    /** @psalm-flow Store::$data[$keys[*]] -> return */
                    function store_get_many(array $keys): string {}

                    store_set("other", (string) $_GET["x"]);

                    /** @param list<string> $ids */
                    function read(array $ids, string $key): void {
                        $keys = [];
                        foreach ($ids as $id) {
                            $keys[] = "data_" . $id;
                        }
                        $keys[] = $key;
                        echo store_get_many($keys);
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'keyedStoreKeyStartsOfAppendedItemsReachTheirKeys' => [
                'code' => '<?php
                    /** @psalm-flow ($value) -> Store::$data[$key] */
                    function store_set(string $key, string $value): void {}

                    /** @psalm-flow Store::$data[$keys[*]] -> return */
                    function store_get_many(array $keys): string {}

                    store_set("data_1", (string) $_GET["x"]);

                    function read(string $id): void {
                        $keys = [];
                        $keys[] = "data_" . $id;
                        echo store_get_many($keys);
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'keyedStoreKeyStartsOfAListChangedByReferenceAreUnknown' => [
                'code' => '<?php
                    /** @psalm-flow ($value) -> Store::$data[$key] */
                    function store_set(string $key, string $value): void {}

                    /** @psalm-flow Store::$data[$keys[*]] -> return */
                    function store_get_many(array $keys): string {}

                    store_set("other", (string) $_GET["x"]);

                    function read(string $id, string $key): void {
                        $keys = [];
                        $keys[] = "data_" . $id;
                        array_push($keys, $key);
                        echo store_get_many($keys);
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'keyedStoreKeyStartOfAFunctionReturnReachesItsKeys' => [
                'code' => '<?php
                    /** @psalm-flow ($value) -> Store::$data[$key] */
                    function store_set(string $key, string $value): void {}

                    /** @psalm-flow Store::$data[$key] -> return */
                    function store_get(string $key): string {}

                    function data_key(string $id): string {
                        return "data_" . $id;
                    }

                    store_set("data_1", (string) $_GET["x"]);

                    function read(string $id): void {
                        echo store_get(data_key($id));
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'keyedStoreKeyStartsOfAListAnOverridableMethodReturnsAreUnknown' => [
                'code' => '<?php
                    /** @psalm-flow ($value) -> Store::$data[$key] */
                    function store_set(string $key, string $value): void {}

                    /** @psalm-flow Store::$data[$keys[*]] -> return */
                    function store_get_many(array $keys): string {}

                    store_set("other", (string) $_GET["x"]);

                    class Reader {
                        /** @param list<string> $ids */
                        public function read(array $ids): void {
                            echo store_get_many($this->keys($ids));
                        }

                        /**
                         * @param list<string> $ids
                         * @return list<string>
                         */
                        protected function keys(array $ids): array {
                            $keys = [];
                            foreach ($ids as $id) {
                                $keys[] = "data_" . $id;
                            }
                            return $keys;
                        }
                    }

                    final class AnyKeys extends Reader {
                        #[Override]
                        protected function keys(array $ids): array {
                            return $ids;
                        }
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'keyedStoreKeyStartsOfItemsAnOverridableMethodReturnsAreUnknown' => [
                'code' => '<?php
                    /** @psalm-flow ($value) -> Store::$data[$key] */
                    function store_set(string $key, string $value): void {}

                    /** @psalm-flow Store::$data[$keys[*]] -> return */
                    function store_get_many(array $keys): string {}

                    store_set("other", (string) $_GET["x"]);

                    class Reader {
                        /** @param list<string> $ids */
                        public function read(array $ids): void {
                            echo store_get_many($this->keys($ids));
                        }

                        /**
                         * @param list<string> $ids
                         * @return list<string>
                         */
                        private function keys(array $ids): array {
                            $keys = [];
                            foreach ($ids as $id) {
                                $keys[] = $this->key($id);
                            }
                            return $keys;
                        }

                        protected function key(string $id): string {
                            return "data_" . $id;
                        }
                    }

                    final class AnyKey extends Reader {
                        #[Override]
                        protected function key(string $id): string {
                            return $id;
                        }
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'keyedStoreAnyKey' => [
                'code' => '<?php
                    /** @psalm-flow ($value) -> Store::$data[$key] */
                    function store_set(string $key, string $value): void {}

                    /** @psalm-flow Store::$data[$key] -> return */
                    function store_get(string $key): string {}

                    /** @psalm-flow ($values) -> Store::$data[*] */
                    function store_set_many(array $values): void {}

                    store_set_many(["a" => (string) $_GET["x"]]);
                    echo store_get("b");',
                'error_message' => 'TaintedHtml',
            ],
            'lateBoundKeyedStoreWrittenThroughASubclassIsReadThroughItsParent' => [
                'code' => '<?php
                    abstract class Model {
                        /** @psalm-flow ($value) -> static::$temp[$key] */
                        public function setTemp(string $key, string $value): void {}

                        /** @psalm-flow static::$temp[$key] -> return */
                        public function getTemp(string $key): string {
                            return "";
                        }
                    }

                    final class Article extends Model {}

                    function save(Article $article): void {
                        $article->setTemp("k", (string) $_GET["x"]);
                    }

                    function show(Model $model): void {
                        echo $model->getTemp("k");
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'lateBoundKeyedStoreWrittenThroughAParentIsReadThroughASubclass' => [
                'code' => '<?php
                    abstract class Model {
                        /** @psalm-flow ($value) -> static::$temp[$key] */
                        public function setTemp(string $key, string $value): void {}

                        /** @psalm-flow static::$temp[$key] -> return */
                        public function getTemp(string $key): string {
                            return "";
                        }
                    }

                    final class Article extends Model {}

                    function save(Model $model): void {
                        $model->setTemp("k", (string) $_GET["x"]);
                    }

                    function show(Article $article): void {
                        echo $article->getTemp("k");
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'lateBoundKeyedStoreWrittenThroughAParentIsReadThroughAnIndirectSubclass' => [
                'code' => '<?php
                    abstract class Model {
                        /** @psalm-flow ($value) -> static::$temp[$key] */
                        public function setTemp(string $key, string $value): void {}

                        /** @psalm-flow static::$temp[$key] -> return */
                        public function getTemp(string $key): string {
                            return "";
                        }
                    }

                    class Post extends Model {}

                    class Article extends Post {}

                    final class News extends Article {}

                    function save(Model $model): void {
                        $model->setTemp("k", (string) $_GET["x"]);
                    }

                    function show(News $news): void {
                        echo $news->getTemp("k");
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'lateBoundKeyedStoreWrittenThroughAClassIsReadThroughAnInterfaceOfASubclass' => [
                'code' => '<?php
                    abstract class Model {
                        /** @psalm-flow ($value) -> static::$temp[$key] */
                        public function setTemp(string $key, string $value): void {}

                        /** @psalm-flow static::$temp[$key] -> return */
                        public function getTemp(string $key): string {
                            return "";
                        }
                    }

                    interface Tagged {
                        /** @psalm-flow static::$temp[$key] -> return */
                        public function getTemp(string $key): string;
                    }

                    final class Article extends Model implements Tagged {}

                    function save(Model $model): void {
                        $model->setTemp("k", (string) $_GET["x"]);
                    }

                    function show(Tagged $tagged): void {
                        echo $tagged->getTemp("k");
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'lateBoundKeyedStoreOfStaticMethods' => [
                'code' => '<?php
                    abstract class Registry {
                        /** @psalm-flow ($value) -> static::$data[$key] */
                        public static function set(string $key, string $value): void {}

                        /** @psalm-flow static::$data[$key] -> return */
                        public static function get(string $key): string {
                            return "";
                        }
                    }

                    final class Settings extends Registry {}

                    Settings::set("k", (string) $_GET["x"]);
                    echo Registry::get("k");',
                'error_message' => 'TaintedHtml',
            ],
            'keyedStoreOfMethods' => [
                'code' => '<?php
                    /** @psalm-flow ($value) -> Store::$data[$key] */
                    function store_set(string $key, string $value): void {}

                    /** @psalm-flow Store::$data[$key] -> return */
                    function store_get(string $key): string {}

                    final class Cache {
                        /** @psalm-flow ($value) -> self::$data[$key] */
                        public static function set(string $key, string $value): void {}

                        /** @psalm-flow self::$data[$key] -> return */
                        public function get(string $key): string {}
                    }

                    Cache::set("a", (string) $_GET["x"]);
                    echo (new Cache())->get("a");',
                'error_message' => 'TaintedHtml',
            ],
            'keyedStoreOfPhpredis' => [
                'code' => '<?php
                    /** @psalm-flow ($value) -> Store::$data[$key] */
                    function store_set(string $key, string $value): void {}

                    /** @psalm-flow Store::$data[$key] -> return */
                    function store_get(string $key): string {}

                    function write(Redis $redis, string $id): void {
                        $redis->set("session_" . $id, (string) $_GET["x"]);
                    }

                    function read(Redis $redis): void {
                        echo (string) $redis->get("session_1");
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'keyedStoreOfACallableResult' => [
                'code' => '<?php
                    interface Cache {
                        /**
                         * @template T
                         * @param callable(): T $compute
                         * @return T
                         * @psalm-taint-specialize
                         * @psalm-flow ($compute()) -> Cache::$data[$key]
                         * @psalm-flow Cache::$data[$key] -> return
                         * @psalm-flow ($compute()) -> return
                         */
                        public function get(string $key, callable $compute): mixed;
                    }

                    function warm(Cache $cache): void {
                        $cache->get("a", fn(): string => (string) $_GET["x"]);
                    }

                    function show(Cache $cache, string $fallback): void {
                        echo $cache->get("a", fn(): string => $fallback);
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'keyedStoreOfApcu' => [
                'code' => '<?php
                    /** @psalm-flow ($value) -> Store::$data[$key] */
                    function store_set(string $key, string $value): void {}

                    /** @psalm-flow Store::$data[$key] -> return */
                    function store_get(string $key): string {}

                    apcu_store("a", (string) $_GET["x"]);
                    echo (string) apcu_fetch("a");',
                'error_message' => 'TaintedHtml',
            ],
            'keysOfAnArrayOfArraysHoldTheKeysOfTheInnerArrayOnceFetched' => [
                'code' => '<?php
                    $outer = [];
                    $outer[] = [$_GET["key"] => 1];
                    foreach ($outer as $inner) {
                        foreach ($inner as $key => $_) {
                            echo $key;
                        }
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'concatenationOfAStringMadeOfTheElementsOfAnArray' => [
                'code' => '<?php
                    $values = [$_GET["a"]];
                    echo "<b>" . implode(",", $values);',
                'error_message' => 'TaintedHtml',
            ],
            'concatenationOfAnElementFetchedFromAnArray' => [
                'code' => '<?php
                    $values = ["key" => $_GET["a"]];
                    echo "<b>" . $values["key"];',
                'error_message' => 'TaintedHtml',
            ],
            'castOfAValueWhoseTypeMayBeAnArrayAfterItsElementIsFetched' => [
                'code' => '<?php
                    /** @param array<string, mixed> $values */
                    function show(array $values): void {
                        foreach ($values as $value) {
                            echo "<b>" . (string) $value;
                        }
                    }

                    show(["key" => $_GET["a"]]);',
                'error_message' => 'TaintedHtml',
            ],
            'castOfAMixedValueThatIsItselfTheTaintInsideASpecializedFunction' => [
                // a value that may be an array may also be the tainted string itself: only a taint that sits in
                // an element of it is not in its conversion
                'code' => '<?php
                    function toString(mixed $value): string {
                        return (string) $value;
                    }

                    /** @psalm-taint-specialize */
                    function show(mixed $value): string {
                        return toString($value);
                    }

                    echo show($_GET["a"]);',
                'error_message' => 'TaintedHtml',
            ],
            'concatenationOfATemplatedValueFetchedFromAnArrayInsideASpecializedFunction' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @param T $value
                     */
                    function prefix($value): string {
                        return "a{$value}";
                    }

                    /**
                     * @psalm-taint-specialize
                     * @param array<string, string|list<string>> $values
                     */
                    function show(array $values, string $key): string {
                        return prefix($values[$key]);
                    }

                    echo show(["key" => $_GET["a"]], "key");',
                'error_message' => 'TaintedHtml',
            ],
            'taintWhatIsSentToAGenerator' => [
                'code' => '<?php // --taint-analysis
                    function values(): Generator {
                        $sent = yield 1;
                        echo $sent;
                    }

                    $values = values();
                    $values->current();
                    $values->send((string) $_GET["value"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintWhatIsSentToAGeneratorDelegatingToAnother' => [
                'code' => '<?php // --taint-analysis
                    function inner(): Generator {
                        $sent = yield 1;
                        echo $sent;
                    }

                    function outer(): Generator {
                        yield from inner();
                    }

                    $values = outer();
                    $values->current();
                    $values->send((string) $_GET["value"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintWhatIsSentToAGeneratorMethod' => [
                'code' => '<?php // --taint-analysis
                    final class Rows {
                        public function values(): Generator {
                            $sent = yield 1;
                            echo $sent;
                        }
                    }

                    $values = (new Rows())->values();
                    $values->current();
                    $values->send((string) $_GET["value"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintWhatASpecializedGeneratorIsSent' => [
                'code' => '<?php // --taint-analysis
                    /** @psalm-taint-specialize */
                    function relay(): Generator {
                        $sent = yield "";
                        yield $sent;
                    }

                    $tainted = relay();
                    $tainted->current();
                    $tainted->send((string) $_GET["value"]);
                    echo $tainted->current();',
                'error_message' => 'TaintedHtml',
            ],
            'taintWhatIsSentToAGeneratorSentToItselfThroughAnotherGenerator' => [
                'code' => '<?php // --taint-analysis
                    function receiver(): Generator {
                        $received = yield 1;
                        echo $received;
                    }

                    function relay(): Generator {
                        $sent = yield 1;
                        $own = relay();
                        /** @var Generator $target */
                        $target = rand() ? $sent : $own;
                        // what is sent here reaches `$sent`, so it may be a generator too: the one of `receiver()`
                        $target->send(rand() ? receiver() : (string) $_GET["value"]);
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintWhatAGeneratorYields' => [
                'code' => '<?php // --taint-analysis
                    /** @return Generator<int, string> */
                    function values(): Generator {
                        yield (string) $_GET["value"];
                    }

                    foreach (values() as $value) {
                        echo $value;
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheKeyAGeneratorYields' => [
                'code' => '<?php // --taint-analysis
                    /** @return Generator<string, int> */
                    function values(): Generator {
                        yield (string) $_GET["value"] => 1;
                    }

                    foreach (values() as $key => $_) {
                        echo $key;
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintWhatAGeneratorYieldsFrom' => [
                'code' => '<?php // --taint-analysis
                    /** @return Generator<int, string> */
                    function values(): Generator {
                        yield from [(string) $_GET["value"]];
                    }

                    foreach (values() as $value) {
                        echo $value;
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheCurrentValueOfAGenerator' => [
                'code' => '<?php // --taint-analysis
                    /** @return Generator<int, string> */
                    function values(): Generator {
                        yield (string) $_GET["value"];
                    }

                    echo values()->current();',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheArrayOfAGenerator' => [
                'code' => '<?php // --taint-analysis
                    /** @return Generator<int, string> */
                    function values(): Generator {
                        yield (string) $_GET["value"];
                    }

                    echo iterator_to_array(values())[0];',
                'error_message' => 'TaintedHtml',
            ],
            'taintWhatATraversableParamGives' => [
                'code' => '<?php // --taint-analysis
                    /** @return Generator<int, string> */
                    function values(): Generator {
                        yield (string) $_GET["value"];
                    }

                    /** @param Traversable<int, string> $values */
                    function show(Traversable $values): void {
                        foreach ($values as $value) {
                            echo $value;
                        }
                    }

                    show(values());',
                'error_message' => 'TaintedHtml',
            ],
            'taintWhatATemplateTraversableParamGives' => [
                'code' => '<?php // --taint-analysis
                    /** @return Generator<int, string> */
                    function values(): Generator {
                        yield (string) $_GET["value"];
                    }

                    /**
                     * @template T of Traversable<int, string>
                     * @param T $values
                     */
                    function show(Traversable $values): void {
                        foreach ($values as $value) {
                            echo $value;
                        }
                    }

                    show(values());',
                'error_message' => 'TaintedHtml',
            ],
            'taintSsrfAfterTwoSlashesOrABackslash' => [
                'code' => '<?php // --taint-analysis
                    /** @psalm-taint-sink ssrf $uri */
                    function request(string $uri): void {}

                    $value = (string) $_GET["value"];
                    request("//" . $value);
                    request("/\\\\" . $value);
                    request("/" . $value);',
                'error_message' => 'TaintedSSRF',
            ],
            'taintUrlComponentInTheQueryOfAUrl' => [
                'code' => '<?php // --taint-analysis
                    file_get_contents("https://api.example.com/search?q=" . (string) $_GET["value"]);',
                'error_message' => 'TaintedUrlComponent',
            ],
            'taintUrlComponentInThePathOfAUrl' => [
                'code' => '<?php // --taint-analysis
                    $value = (string) $_GET["value"];
                    file_get_contents("https://api.example.com/items/{$value}/details");',
                'error_message' => 'TaintedUrlComponent',
            ],
            'taintUrlComponentInTheFragmentOfAFormattedUrl' => [
                'code' => '<?php // --taint-analysis
                    curl_init(sprintf("https://example.com/#%s", (string) $_GET["value"]));',
                'error_message' => 'TaintedUrlComponent',
            ],
            'taintUrlPathInThePathOfAUrlAfterEncodingIt' => [
                'code' => '<?php // --taint-analysis
                    $id = rawurlencode((string) $_GET["id"]);
                    file_get_contents("https://api.example.com/users/" . $id . "/profile");',
                'error_message' => 'TaintedUrlPath',
            ],
            'taintUrlPathBeforeTheQueryOfAUrl' => [
                'code' => '<?php // --taint-analysis
                    curl_init("https://api.example.com/users/" . urlencode((string) $_GET["id"]) . "?a=b");',
                'error_message' => 'TaintedUrlPath',
            ],
            'taintUrlComponentFormattedAsACharacter' => [
                'code' => '<?php // --taint-analysis
                    file_get_contents(sprintf("https://example.com/%d%c", 1, (string) $_GET["value"]));',
                'error_message' => 'TaintedUrlComponent',
            ],
            'taintHtmlFormattedAsAStringAndANumber' => [
                'code' => '<?php // --taint-analysis
                    $value = (string) $_GET["value"];
                    echo sprintf("%1\$d %1\$s", $value);',
                'error_message' => 'TaintedHtml',
            ],
            'taintHtmlPrintedByPrintfAsAString' => [
                'code' => '<?php // --taint-analysis
                    printf("%s", (string) $_GET["x"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintHtmlPrintedByPrintfAsAStringAndANumber' => [
                'code' => '<?php // --taint-analysis
                    printf("%1\$d %1\$s", (string) $_GET["x"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintHtmlPrintedByPrintfAsAStringAfterANumber' => [
                'code' => '<?php // --taint-analysis
                    printf("%d %s", 1, (string) $_GET["x"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintHtmlInTheValuesOfVprintfWithAStringConversion' => [
                'code' => '<?php // --taint-analysis
                    vprintf("%d %s", [(string) $_GET["x"], "a"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintHtmlFormattedByVsprintfAsAString' => [
                'code' => '<?php // --taint-analysis
                    echo vsprintf("%s", [(string) $_GET["x"]]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintHtmlInTheValuesOfVsprintfWithAStringConversion' => [
                'code' => '<?php // --taint-analysis
                    echo vsprintf("%d %s", [(string) $_GET["x"], "a"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintSsrfAfterRawurlencode' => [
                'code' => '<?php // --taint-analysis
                    curl_init(rawurlencode((string) $_GET["host"]));',
                'error_message' => 'TaintedSSRF',
            ],
            'taintSsrfInHttpBuildQuery' => [
                'code' => '<?php // --taint-analysis
                    file_get_contents(http_build_query(["q" => (string) $_GET["value"]]));',
                'error_message' => 'TaintedSSRF',
            ],
            'taintSsrfInFopen' => [
                'code' => '<?php // --taint-analysis
                    fopen((string) $_GET["url"], "r");',
                'error_message' => 'TaintedSSRF',
            ],
            'taintSsrfInSimplexmlLoadFile' => [
                'code' => '<?php // --taint-analysis
                    simplexml_load_file((string) $_GET["url"]);',
                'error_message' => 'TaintedSSRF',
            ],
            'taintSsrfInDomDocumentLoad' => [
                'code' => '<?php // --taint-analysis
                    (new DOMDocument())->load((string) $_GET["url"]);',
                'error_message' => 'TaintedSSRF',
            ],
            'taintSsrfInSoapClient' => [
                'code' => '<?php // --taint-analysis
                    new SoapClient((string) $_GET["url"]);',
                'error_message' => 'TaintedSSRF',
            ],
            'taintSsrfInFsockopen' => [
                'code' => '<?php // --taint-analysis
                    fsockopen((string) $_GET["host"]);',
                'error_message' => 'TaintedSSRF',
            ],
            'taintSsrfAfterTheStartOfAStreamWrapper' => [
                'code' => '<?php // --taint-analysis
                    file_get_contents("compress.zlib://https://" . (string) $_GET["value"]);',
                'error_message' => 'TaintedSSRF',
            ],
            'taintSsrfAfterTheStartOfAStreamWrapperWithAPath' => [
                'code' => '<?php // --taint-analysis
                    /** @psalm-taint-sink ssrf $url */
                    function fetch(string $url): void {}

                    fetch("php://filter/resource=" . (string) $_GET["value"]);',
                'error_message' => 'TaintedSSRF',
            ],
            'taintFileAfterAFormatEscapingThePercentOfItsScheme' => [
                'code' => '<?php // --taint-analysis
                    /** @psalm-taint-sink file $path */
                    function read(string $path): void {}

                    read(sprintf("ht%%tps://api.example.com/%s", (string) $_GET["value"]));',
                'error_message' => 'TaintedFile',
            ],
            'taintFileAfterTheStartOfAUrlWithoutScheme' => [
                'code' => '<?php // --taint-analysis
                    file_get_contents("//api.example.com/" . (string) $_GET["value"]);',
                'error_message' => 'TaintedFile',
            ],
            'taintSsrfAfterOneOfTheStartsOfAUrlNotEndingItsHost' => [
                'code' => '<?php
                    $origin = rand(0, 1) ? "https://example.com/" : "https://example.org";
                    file_get_contents($origin . (string) $_GET["path"]);',
                'error_message' => 'TaintedSSRF',
            ],
            'taintSsrfAfterAUrlStartNotEndingItsHost' => [
                'code' => '<?php // --taint-analysis
                    file_get_contents("https://api.example.com" . (string) $_GET["value"]);',
                'error_message' => 'TaintedSSRF',
            ],
            'taintSsrfInTheHostOfAFormattedUrl' => [
                'code' => '<?php // --taint-analysis
                    file_get_contents(sprintf("https://%s/items", (string) $_GET["value"]));',
                'error_message' => 'TaintedSSRF',
            ],
            'taintHtmlAfterTheStartOfAUrl' => [
                'code' => '<?php // --taint-analysis
                    echo "https://example.com/" . (string) $_GET["value"];',
                'error_message' => 'TaintedHtml',
            ],
            'taintThePropertiesOfAnObjectWithWhatAnInheritedMethodSetsOnIt' => [
                'code' => '<?php // --taint-analysis
                    abstract class Provider {
                        /** @var array<string, string> */
                        protected array $options = [];

                        /** @param array<string, string> $options */
                        public function __construct(array $options) {
                            $this->setOptions($options);
                        }

                        /** @param array<string, string> $options */
                        public function setOptions(array $options): void {
                            foreach ($options as $key => $value) {
                                $this->setOption($key, $value);
                            }
                        }

                        public function setOption(string $key, string $value): void {
                            $this->options[$key] = $value;
                        }
                    }

                    final class Activity extends Provider {
                        public function printIds(): void {
                            echo $this->options["ids"] ?? "";
                        }
                    }

                    (new Activity(["ids" => (string) $_GET["ids"]]))->printIds();',
                'error_message' => 'TaintedHtml',
            ],
            'taintAPropertyAnInheritedMethodSetsBeforeACallWithAnUntaintedArgument' => [
                // the calls of an inherited method share the path into its body for the class: the untainted
                // argument of a later call removes nothing from what an earlier one passed
                'code' => '<?php // --taint-analysis
                    class Base {
                        /** @var array<string, mixed> */
                        protected array $options = [];

                        public function setOption(string $key, mixed $value): void {
                            $this->options[$key] = $value;
                        }

                        /** @return array<string, mixed> */
                        public function getOptions(): array {
                            return $this->options;
                        }
                    }

                    class Mail extends Base {}

                    function send(Mail $mail): void {
                        $mail->setOption("k", (string) $_GET["value"]);
                        $mail->setOption("greeting", "hello");
                        echo (string) $mail->getOptions()["k"];
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintThePropertiesOfAnObjectWithWhatAParentConstructorSetsOnIt' => [
                'code' => '<?php // --taint-analysis
                    class Field {
                        /** @var array<string, string> */
                        protected array $opts;

                        /** @param array<string, string> $opts */
                        public function __construct(array $opts) {
                            $this->opts = $opts;
                        }
                    }

                    final class Button extends Field {
                        /** @param array<string, string> $opts */
                        public function __construct(array $opts) {
                            parent::__construct($opts);
                        }

                        public function render(): void {
                            echo $this->opts["label"] ?? "";
                        }
                    }

                    (new Button(["label" => (string) $_GET["label"]]))->render();',
                'error_message' => 'TaintedHtml',
            ],
            'taintThePropertiesOfAnObjectWithWhatAnAncestorConstructorCalledByNameSetsOnIt' => [
                'code' => '<?php // --taint-analysis
                    class Field {
                        /** @var array<string, string> */
                        protected array $opts;

                        /** @param array<string, string> $opts */
                        public function __construct(array $opts) {
                            $this->opts = $opts;
                        }
                    }

                    class Select extends Field {}

                    final class MultiSelect extends Select {
                        /** @param array<string, string> $opts */
                        public function __construct(array $opts) {
                            Field::__construct($opts);
                        }

                        public function render(): void {
                            echo $this->opts["label"] ?? "";
                        }
                    }

                    (new MultiSelect(["label" => (string) $_GET["label"]]))->render();',
                'error_message' => 'TaintedHtml',
            ],
            'taintWhatAnInheritedMethodReturnsOfItsObject' => [
                'code' => '<?php // --taint-analysis
                    class Model {
                        protected string $name = "";

                        public function setName(string $name): void {
                            $this->name = $name;
                        }

                        public function getName(): string {
                            return $this->name;
                        }
                    }

                    final class Admin extends Model {}

                    $admin = new Admin();
                    $admin->setName((string) $_GET["name"]);
                    echo $admin->getName();',
                'error_message' => 'TaintedHtml',
            ],
            'taintThePropertyOfASubclassWithWhatAParentMethodSets' => [
                'code' => '<?php // --taint-analysis
                    class Model {
                        protected string $name = "";

                        public function setName(string $name): void {
                            $this->name = $name;
                        }
                    }

                    final class Admin extends Model {
                        public function printName(): void {
                            echo $this->name;
                        }
                    }

                    $admin = new Admin();
                    $admin->setName((string) $_GET["name"]);
                    $admin->printName();',
                'error_message' => 'TaintedHtml',
            ],
            'taintThePropertyASubclassDeclaresAgainWithWhatAParentMethodSets' => [
                'code' => '<?php // --taint-analysis
                    abstract class Provider {
                        protected array $options = [];

                        public function setOption(string $key, string $value): void {
                            $this->options[$key] = $value;
                        }
                    }

                    final class Activity extends Provider {
                        protected array $options = ["ids" => ""];

                        public function printIds(): void {
                            echo (string) $this->options["ids"];
                        }
                    }

                    $activity = new Activity();
                    $activity->setOption("ids", (string) $_GET["ids"]);
                    $activity->printIds();',
                'error_message' => 'TaintedHtml',
            ],
            'taintThePropertyOfAParentClassWithWhatIsSetThroughASubclassOfASubclass' => [
                'code' => '<?php // --taint-analysis
                    class Model {
                        protected string $name = "";
                    }

                    class Admin extends Model {
                        public function printName(): void {
                            echo $this->name;
                        }
                    }

                    final class SuperAdmin extends Admin {
                        public function setName(string $name): void {
                            $this->name = $name;
                        }
                    }

                    $admin = new SuperAdmin();
                    $admin->setName((string) $_GET["name"]);
                    $admin->printName();',
                'error_message' => 'TaintedHtml',
            ],
            'taintAQueryWithAnArrayItem' => [
                'code' => '<?php // --taint-analysis
                    /** @psalm-taint-sink nosql $query */
                    function find(array $query): void {}

                    find(["city" => $_GET["city"]]);',
                'error_message' => 'TaintedNosql',
            ],
            'variableDefinedByNarrowingInABranchKeepsItsTaints' => [
                'code' => '<?php
                    function render(array $names): string {
                        foreach ($names as $name) {
                            $first = $name;
                            break;
                        }
                        if (empty($first)) {
                            $first = "none";
                        }

                        return $first;
                    }

                    echo render($_GET["names"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintPropertySetInSpecializedCallAndReadInAnother' => [
                'code' => '<?php
                    final class Holder {
                        private string $value = "";

                        /** @psalm-taint-specialize */
                        public function set(string $value): void {
                            $this->value = $value;
                        }

                        /** @psalm-taint-specialize */
                        public function get(): string {
                            return $this->value;
                        }
                    }

                    $holder = new Holder();
                    $holder->set((string) $_GET["value"]);
                    echo $holder->get();',
                'error_message' => 'TaintedHtml',
            ],
            'taintArrayPropertySetInSpecializedCallAndReadInAnother' => [
                'code' => '<?php
                    final class Holder {
                        /** @var array<string, string> */
                        private array $data = [];

                        /** @psalm-taint-specialize */
                        public function set(string $key, string $value): void {
                            $this->data[$key] = $value;
                        }

                        /** @psalm-taint-specialize */
                        public function get(string $key): string {
                            return $this->data[$key] ?? "";
                        }
                    }

                    $holder = new Holder();
                    $holder->set("a", (string) $_GET["value"]);
                    echo $holder->get("a");',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheKeysOfAFlippedArrayWithItsValues' => [
                'code' => '<?php // --taint-analysis
                    $flipped = array_flip(["key" => (string) $_GET["value"]]);
                    foreach ($flipped as $key => $_) {
                        echo $key;
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheElementABuiltinReturns' => [
                'code' => '<?php // --taint-analysis
                    $files = [["name" => (string) $_GET["name"], "tmp_name" => "/tmp/upload"]];
                    echo array_shift($files)["name"];',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheKeyABuiltinReturns' => [
                'code' => '<?php // --taint-analysis
                    $values = [(string) $_GET["key"] => 1];
                    echo (string) array_key_last($values);',
                'error_message' => 'TaintedHtml',
            ],
            'taintHtmlThroughSerialize' => [
                'code' => '<?php // --taint-analysis
                    echo serialize((string) $_GET["value"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintJsonEncodedArrayCastAsMixed' => [
                'code' => '<?php
                    function encodedAsMixed(): mixed {
                        $a = [];
                        $a[] = (string) $_GET["x"];
                        return json_encode($a);
                    }

                    echo (string) encodedAsMixed();',
                'error_message' => 'TaintedHtml',
            ],
            'taintSerializedArrayConcatenatedAsMixed' => [
                'code' => '<?php
                    function serializedAsMixed(): mixed {
                        $a = [];
                        $a["k"] = (string) $_GET["x"];
                        return serialize($a);
                    }

                    /** @psalm-suppress MixedOperand */
                    echo "<b>" . serializedAsMixed();',
                'error_message' => 'TaintedHtml',
            ],
            'taintPrintedNestedArrayCastAsMixed' => [
                'code' => '<?php
                    function printedAsMixed(): mixed {
                        $a = [];
                        $a["x"]["y"] = (string) $_GET["x"];
                        return print_r($a, true);
                    }

                    echo (string) printedAsMixed();',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheKeyOfAnUnserializedArrayPutInAnotherArray' => [
                'code' => '<?php
                    $a = [];
                    $a["k"] = (string) $_GET["x"];
                    $wrapper = ["payload" => serialize($a)];
                    $b = unserialize($wrapper["payload"]);
                    echo (string) $b["k"];',
                'error_message' => 'TaintedHtml',
            ],
            'taintVsprintfValuesCastAsMixed' => [
                'code' => '<?php
                    function formattedAsMixed(): mixed {
                        $a = [];
                        $a[] = (string) $_GET["x"];
                        return vsprintf("%s", $a);
                    }

                    echo (string) formattedAsMixed();',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheItemDestructuredFromAnUnshapedArray' => [
                'code' => '<?php // --taint-analysis
                    function getComment(): array {
                        return ["author" => (string) $_GET["author"], "text" => "safe"];
                    }

                    ["author" => $author, "text" => $text] = getComment();
                    echo $author;',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheItemDestructuredByAnIntegerKey' => [
                'code' => '<?php // --taint-analysis
                    function getComment(): array {
                        return [1 => (string) $_GET["author"], 2 => "safe"];
                    }

                    [1 => $author, 2 => $text] = getComment();
                    echo $author;',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheItemDestructuredFromAShapeReturnedByACall' => [
                'code' => '<?php // --taint-analysis
                    /** @return array{0: string, 1: string} */
                    function getComment(): array {
                        return [(string) $_GET["author"], "safe"];
                    }

                    [$author, $text] = getComment();
                    echo $author;',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheItemDestructuredFromTheFallbackOfAShape' => [
                'code' => '<?php // --taint-analysis
                    /** @return array{author: string, ...<string, string>} */
                    function getComment(): array {
                        return ["author" => "safe", "text" => (string) $_GET["text"]];
                    }

                    ["author" => $author, "text" => $text] = getComment();
                    echo $text;',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheItemDestructuredInAForeach' => [
                'code' => '<?php // --taint-analysis
                    function getComment(): array {
                        return ["author" => (string) $_GET["author"], "text" => "safe"];
                    }

                    foreach (["first" => getComment()] as ["author" => $author, "text" => $text]) {
                        echo $author;
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheItemDestructuredInAForeachOverAList' => [
                'code' => '<?php // --taint-analysis
                    function getComment(): array {
                        return ["author" => (string) $_GET["author"], "text" => "safe"];
                    }

                    foreach ([getComment()] as ["author" => $author, "text" => $text]) {
                        echo $author;
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheItemOfANestedDestructuring' => [
                'code' => '<?php // --taint-analysis
                    function getComment(): array {
                        return ["author" => (string) $_GET["author"], "text" => "safe"];
                    }

                    [["author" => $author, "text" => $text]] = [getComment()];
                    echo $author;',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheItemOfANestedDestructuringOfAnUnshapedArray' => [
                'code' => '<?php // --taint-analysis
                    function getComment(): array {
                        return ["author" => (string) $_GET["author"], "text" => "safe"];
                    }

                    /** @return non-empty-list<array> */
                    function getComments(): array {
                        return [getComment()];
                    }

                    [["author" => $author, "text" => $text]] = getComments();
                    echo $author;',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheItemOfANestedDestructuringThroughArrayValues' => [
                'code' => '<?php // --taint-analysis
                    function getComment(): array {
                        return ["author" => (string) $_GET["author"], "text" => "safe"];
                    }

                    [["author" => $author, "text" => $text]] = array_values([getComment()]);
                    echo $author;',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheStringConversionOfAnObjectWhoseToStringReturnsItsTaint' => [
                'code' => '<?php // --taint-analysis
                    /** @psalm-taint-specialize */
                    final class Tag {
                        private string $content;
                        public function __construct(string $content) {
                            $this->content = $content;
                        }
                        public function __toString(): string {
                            return $this->content;
                        }
                    }
                    echo (string) new Tag((string) $_GET["x"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheEchoOfAnObjectWhoseToStringReturnsItsTaint' => [
                'code' => '<?php // --taint-analysis
                    /** @psalm-taint-specialize */
                    final class Tag {
                        private string $content;
                        public function __construct(string $content) {
                            $this->content = $content;
                        }
                        public function __toString(): string {
                            return $this->content;
                        }
                    }
                    $tag = new Tag((string) $_GET["x"]);
                    echo $tag;',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheInterpolationOfAnObjectWhoseToStringReturnsItsTaint' => [
                'code' => '<?php // --taint-analysis
                    /** @psalm-taint-specialize */
                    final class Tag {
                        private string $content;
                        public function __construct(string $content) {
                            $this->content = $content;
                        }
                        public function __toString(): string {
                            return $this->content;
                        }
                    }
                    $tag = new Tag((string) $_GET["x"]);
                    echo "<p>{$tag}</p>";',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheConcatenationOfAnObjectWhoseToStringReturnsATaintedProperty' => [
                'code' => '<?php // --taint-analysis
                    final class Tag {
                        public string $content = "";
                        public function __toString(): string {
                            return $this->content;
                        }
                    }

                    $tag = new Tag();
                    $tag->content = (string) $_GET["x"];
                    echo "<p>" . $tag . "</p>";',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheStringPartOfAStringConversionOfAnObjectOrAString' => [
                'code' => '<?php // --taint-analysis
                    /** @psalm-taint-specialize */
                    final class Tag {
                        private string $content;
                        public function __construct(string $content) {
                            $this->content = $content;
                        }
                        public function __toString(): string {
                            return htmlspecialchars($this->content);
                        }
                    }
                    $value = rand(0, 1) ? new Tag("safe") : (string) $_GET["x"];
                    echo (string) $value;',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheOtherValuesOfAForeachTransformAfterAKeyIsAssigned' => [
                'code' => '<?php
                    /** @param array<string, string> $input */
                    function show(array $input): void {
                        $input["zip"] = (string) $_GET["zip"];
                        $output = [];
                        foreach ($input as $key => $value) {
                            $output[$key] = trim($value);
                        }
                        $output["city"] = "msk";
                        echo $output["zip"];
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheOtherValuesOfAnArrayIteratedOverAfterAKeyIsUnset' => [
                'code' => '<?php
                    $data = ["city" => "msk", "zip" => (string) $_GET["zip"]];
                    unset($data["city"]);
                    foreach ($data as $value) {
                        echo $value;
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheOtherValuesOfAnArrayGivenToAFunctionAssigningAKey' => [
                'code' => '<?php
                    function zip(array $data): string {
                        $data["city"] = "msk";
                        return (string) $data["zip"];
                    }
                    echo zip(["zip" => (string) $_GET["zip"]]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheOtherValuesOfAnArrayAfterAKeyIsAssigned' => [
                'code' => '<?php
                    $data = ["city" => "msk", "zip" => (string) $_GET["zip"]];
                    $data["city"] = "spb";
                    echo $data["zip"];',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheOtherValuesOfAWholeArrayNotHavingAKey' => [
                'code' => '<?php
                    $data = $_GET;
                    if (!array_key_exists("city", $data)) {
                        echo (string) $data["zip"];
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheValueOfAKeyAnArrayMayHaveAfterSettingItWhereItIsMissing' => [
                'code' => '<?php
                    $a = rand() ? ["k" => $_GET["v"]] : [];
                    if (!array_key_exists("k", $a)) {
                        $a["k"] = "untainted";
                    }
                    echo $a["k"];',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheValueOfAKeyOfAWholeArrayMergedBackAfterACheckOfIt' => [
                'code' => '<?php
                    $data = $_GET;
                    $hasCity = array_key_exists("city", $data) ? 1 : 0;
                    echo (string) ($data["city"] ?? "");',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheKeysOfAWholeArrayCheckedAgainOverManyStatements' => [
                'code' => '<?php
                    function f(array $a): void {
                        if (isset($a["x"])) {
                            return;
                        }
                        $r1 = array_map("strval", is_array($a["k1"] ?? null) ? $a["k1"] : []);
                        $r2 = array_map("strval", is_array($a["k2"] ?? null) ? $a["k2"] : []);
                        $r3 = array_map("strval", is_array($a["k3"] ?? null) ? $a["k3"] : []);
                        $r4 = array_map("strval", is_array($a["k4"] ?? null) ? $a["k4"] : []);
                        $r5 = array_map("strval", is_array($a["k5"] ?? null) ? $a["k5"] : []);
                        $r6 = array_map("strval", is_array($a["k6"] ?? null) ? $a["k6"] : []);
                        echo $r6[0];
                    }

                    f($_GET);',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheOtherValuesOfAWholeArrayIteratedOverNotHavingSetAKey' => [
                'code' => '<?php
                    $data = $_GET;
                    if (!isset($data["city"])) {
                        foreach ($data as $value) {
                            echo (string) $value;
                        }
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheOtherValuesOfAnArrayAfterAKeyIsUnset' => [
                'code' => '<?php
                    $data = ["city" => "msk", "zip" => (string) $_GET["zip"]];
                    unset($data["city"]);
                    echo $data["zip"];',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheOtherValuesOfANestedArrayAfterAKeyIsAssigned' => [
                'code' => '<?php
                    $data = ["address" => ["city" => "msk", "zip" => (string) $_GET["zip"]]];
                    $data["address"]["city"] = "spb";
                    echo $data["address"]["zip"];',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheOtherValuesOfAnArrayIteratedOverAfterAKeyIsAssigned' => [
                'code' => '<?php
                    $data = ["city" => "msk", "zip" => (string) $_GET["zip"]];
                    $data["city"] = "spb";
                    foreach ($data as $value) {
                        echo $value;
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheValueOfAKeyAssignedOnlyOnSomeBranch' => [
                'code' => '<?php
                    $data = ["city" => (string) $_GET["city"]];
                    if (rand(0, 1)) {
                        $data["city"] = "msk";
                    }
                    echo $data["city"];',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheValueOfAKeyAfterAnAssignmentUnderAKeyNotKnown' => [
                'code' => '<?php
                    function show(string $key): void {
                        $data = ["city" => (string) $_GET["city"]];
                        $data[$key] = "msk";
                        echo $data["city"];
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheValueOfAnArrayAccessOffsetAssignedAgain' => [
                'code' => '<?php
                    function show(ArrayAccess $data): void {
                        $data["city"] = (string) $_GET["city"];
                        $data["city"] = "msk";
                        echo (string) $data["city"];
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheOtherValuesOfWholeArraysMappedAfterAKeyIsAssigned' => [
                'code' => '<?php
                    $items = array_map(
                        function (array $item): array {
                            $item["city"] = htmlspecialchars((string) $item["city"]);
                            return $item;
                        },
                        (array) $_GET["items"],
                    );
                    foreach ($items as $item) {
                        echo (string) $item["zip"];
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheOtherValuesOfWholeArraysIteratedOverAfterAKeyIsAssigned' => [
                'code' => '<?php
                    $items = [];
                    foreach ((array) $_GET["items"] as $item) {
                        $item = (array) $item;
                        $item["city"] = htmlspecialchars((string) $item["city"]);
                        $items[] = $item;
                    }
                    foreach ($items as $item) {
                        echo (string) $item["zip"];
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheValueUnderAnAssignedKeyOfAnotherValueOfAWholeArray' => [
                'code' => '<?php
                    $data = $_GET;
                    $data["city"] = "msk";
                    $zip = (array) $data["zip"];
                    echo (string) $zip["city"];',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheKeysOfAWholeArrayAfterAKeyIsAssigned' => [
                'code' => '<?php
                    $data = $_GET;
                    $data["city"] = "msk";
                    foreach ($data as $key => $_) {
                        echo $key;
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheValuesOfAWholeArrayIteratedOverAfterAKeyIsAssigned' => [
                'code' => '<?php
                    $data = $_GET;
                    $data["city"] = "msk";
                    foreach ($data as $value) {
                        echo (string) $value;
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheOtherValuesOfAWholeArrayWrappedAfterAKeyIsAssigned' => [
                'code' => '<?php
                    $data = $_GET;
                    $data["city"] = "msk";
                    $wrapped = ["data" => $data];
                    echo (string) $wrapped["data"]["zip"];',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheValueOfAWholeArrayKeyAssignedOnlyOnSomeBranch' => [
                'code' => '<?php
                    $data = $_GET;
                    if (rand(0, 1)) {
                        $data["city"] = htmlspecialchars((string) $data["city"]);
                    }
                    echo (string) $data["city"];',
                'error_message' => 'TaintedHtml',
            ],
            'taintAWholeArrayAfterAKeyIsAssigned' => [
                'code' => '<?php
                    $data = $_GET;
                    $data["city"] = "msk";
                    echo (string) $data["zip"];',
                'error_message' => 'TaintedHtml',
            ],
            'taintArrayItemWrittenByAForeachByReference' => [
                'code' => '<?php // --taint-analysis
                    $values = ["key" => ""];
                    foreach ($values as &$value) {
                        $value = (string) $_GET["value"];
                    }
                    unset($value);
                    echo $values["key"];',
                'error_message' => 'TaintedHtml',
            ],
            'taintNestedArrayItemWrittenByAForeachByReference' => [
                'code' => '<?php // --taint-analysis
                    $rows = [["name" => ""]];
                    foreach ($rows as &$row) {
                        $row["name"] = (string) $_GET["name"];
                    }
                    unset($row);
                    echo $rows[0]["name"];',
                'error_message' => 'TaintedHtml',
            ],
            'taintFlowReachingANodeThroughManySpecializedCallEntries' => [
                // Each call enters keep() with an entry of its own, arriving a round later than the one before:
                // past a few entries, Store::$value is widened, and the last flow must still be found
                'code' => '<?php
                    final class Store {
                        /** @var array<string, string> */
                        public static array $value = [];
                    }

                    /**
                     * @param array<string, string> $s
                     * @return array<string, string>
                     * @psalm-taint-specialize
                     */
                    function keep(array $s): array {
                        Store::$value = $s;
                        return Store::$value;
                    }

                    $t0 = (string) ($_GET["x"] ?? "");
                    $t1 = $t0;
                    $t2 = $t1;
                    $t3 = $t2;
                    $t4 = $t3;
                    $t5 = $t4;
                    $t6 = $t5;
                    $t7 = $t6;
                    $t8 = $t7;
                    $t9 = $t8;
                    $t10 = $t9;
                    $t11 = $t10;
                    $t12 = $t11;
                    $t13 = $t12;
                    $t14 = $t13;
                    $t15 = $t14;
                    $t16 = $t15;
                    $t17 = $t16;
                    $t18 = $t17;
                    $t19 = $t18;
                    $t20 = $t19;
                    keep(["k1" => $t1]);
                    keep(["k2" => $t2]);
                    keep(["k3" => $t3]);
                    keep(["k4" => $t4]);
                    keep(["k5" => $t5]);
                    keep(["k6" => $t6]);
                    keep(["k7" => $t7]);
                    keep(["k8" => $t8]);
                    keep(["k9" => $t9]);
                    keep(["k10" => $t10]);
                    keep(["k11" => $t11]);
                    keep(["k12" => $t12]);
                    keep(["k13" => $t13]);
                    keep(["k14" => $t14]);
                    keep(["k15" => $t15]);
                    keep(["k16" => $t16]);
                    keep(["k17" => $t17]);
                    keep(["k18" => $t18]);
                    keep(["k19" => $t19]);
                    exec(keep(["k20" => $t20])["k20"]);',
                'error_message' => 'TaintedShell',
            ],
            'taintFlowReachingANodeWithManyKeysOfAnArrayPutBackUnderEachInALoop' => [
                // the flows of the 100 keys reaching render() share the states of its nodes, widened past the keys they
                // were assigned under: the echo of "k100" is reported, through the flow of the first key that reached
                // the widened state, not necessarily that of "k100"
                'code' => '<?php // --taint-analysis
                    function render(array $form): array {
                        foreach ($form as $key => $element) {
                            $form[$key] = $element;
                        }

                        return $form;
                    }

                    $form = [];
' . implode('', array_map(
                    static fn(int $i): string => 'if (rand(0, 1)) { $form = ["k' . $i . '" => (string) $_GET["q"]]; }' . "\n",
                    range(1, 100),
                )) . '
                    $form = render($form);
                    echo $form["k100"];',
                'error_message' => 'TaintedHtml',
            ],
            'taintArrayItemWrittenByAForeachByReferenceBeforeABreak' => [
                'code' => '<?php // --taint-analysis
                    $values = ["key" => ""];
                    foreach ($values as &$value) {
                        if (rand(0, 1)) {
                            $value = (string) $_GET["value"];
                            break;
                        }
                    }
                    unset($value);
                    echo $values["key"];',
                'error_message' => 'TaintedHtml',
            ],
            'taintVariadicByRefParamWrittenByAForeachByReference' => [
                'code' => '<?php // --taint-analysis
                    function read_values(string &...$values): void {
                        foreach ($values as &$value) {
                            $value = (string) $_GET["value"];
                        }
                    }

                    $value = "";
                    read_values($value);
                    echo $value;',
                'error_message' => 'TaintedHtml',
            ],
            'taintPropertyItemWrittenByAForeachByReference' => [
                'code' => '<?php // --taint-analysis
                    final class Rows {
                        /** @var array<string, string> */
                        private array $rows = ["key" => ""];

                        public function read(): void {
                            foreach ($this->rows as &$row) {
                                $row = (string) $_GET["value"];
                            }
                            unset($row);
                        }

                        public function show(): void {
                            echo $this->rows["key"];
                        }
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintPregMatchMatches' => [
                'code' => '<?php // --taint-analysis
                    preg_match("/id=(\\w+)/", (string) $_GET["value"], $matches);
                    echo $matches[1];',
                'error_message' => 'TaintedHtml',
            ],
            'taintParseStrResult' => [
                'code' => '<?php // --taint-analysis
                    parse_str((string) $_GET["value"], $result);
                    echo (string) $result["key"];',
                'error_message' => 'TaintedHtml',
            ],
            'taintArrayUnshiftedValue' => [
                'code' => '<?php // --taint-analysis
                    $values = [];
                    array_unshift($values, (string) $_GET["value"]);
                    echo $values[0];',
                'error_message' => 'TaintedHtml',
            ],
            'taintArraySplicedValue' => [
                'code' => '<?php // --taint-analysis
                    $values = ["literal"];
                    array_splice($values, 0, 0, [(string) $_GET["value"]]);
                    echo $values[0];',
                'error_message' => 'TaintedHtml',
            ],
            'taintByRefParam' => [
                'code' => '<?php // --taint-analysis
                    function read_value(string &$value): void {
                        $value = (string) $_GET["value"];
                    }

                    $value = "";
                    read_value($value);
                    echo $value;',
                'error_message' => 'TaintedHtml',
            ],
            'taintByRefParamAtAnEarlyReturn' => [
                'code' => '<?php // --taint-analysis
                    function read_value(?string &$value, bool $read): void {
                        if ($read) {
                            $value = (string) $_GET["value"];
                            return;
                        }
                        $value = "literal";
                    }

                    $value = null;
                    read_value($value, true);
                    echo (string) $value;',
                'error_message' => 'TaintedHtml',
            ],
            'taintByRefParamKeptByTheFunction' => [
                'code' => '<?php // --taint-analysis
                    function trim_value(string &$value): void {
                        $value = trim($value);
                    }

                    $value = (string) $_GET["value"];
                    trim_value($value);
                    echo $value;',
                'error_message' => 'TaintedHtml',
            ],
            'taintByRefParamOfAnImplementation' => [
                'code' => '<?php // --taint-analysis
                    interface Reader {
                        public function read(array &$rows): void;
                    }

                    final class GetReader implements Reader {
                        public function read(array &$rows): void {
                            $rows[] = (string) $_GET["value"];
                        }
                    }

                    function show(Reader $reader): void {
                        $rows = [];
                        $reader->read($rows);
                        echo (string) $rows[0];
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintValuePassedByRefToAnAbstractMethod' => [
                'code' => '<?php // --taint-analysis
                    abstract class Normalizer {
                        abstract public function normalize(string &$value): void;
                    }

                    function show(Normalizer $normalizer): void {
                        $value = (string) $_GET["value"];
                        $normalizer->normalize($value);
                        echo $value;
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintValuePassedByRefToATraitMethod' => [
                'code' => '<?php // --taint-analysis
                    trait Normalizes {
                        public function normalize(string &$value): void {}
                    }

                    final class ValueNormalizer {
                        use Normalizes;
                    }

                    $value = (string) $_GET["value"];
                    (new ValueNormalizer())->normalize($value);
                    echo $value;',
                'error_message' => 'TaintedHtml',
            ],
            'taintValuePassedByRefToAnAliasedTraitMethod' => [
                'code' => '<?php // --taint-analysis
                    trait Normalizes {
                        public function normalize(string &$value): void {}
                    }

                    final class ValueNormalizer {
                        use Normalizes { normalize as clean; }
                    }

                    $value = (string) $_GET["value"];
                    (new ValueNormalizer())->clean($value);
                    echo $value;',
                'error_message' => 'TaintedHtml',
            ],
            'taintByRefParamOfAnOverrideOfATraitMethod' => [
                'code' => '<?php // --taint-analysis
                    trait Reads {
                        public function read(string &$value): void {
                            $value = "literal";
                        }
                    }

                    class Reader {
                        use Reads;
                    }

                    final class GetReader extends Reader {
                        #[Override]
                        public function read(string &$value): void {
                            $value = (string) $_GET["value"];
                        }
                    }

                    function show(Reader $reader): void {
                        $value = "";
                        $reader->read($value);
                        echo $value;
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintReturnOfATraitMethod' => [
                'code' => '<?php // --taint-analysis
                    trait Passes {
                        public function pass(string $s): string {
                            return $s;
                        }
                    }

                    final class Passer {
                        use Passes;
                    }

                    echo (new Passer())->pass((string) $_GET["x"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintReturnOfATraitMethodCalledOnASubclassOfItsUser' => [
                'code' => '<?php // --taint-analysis
                    trait Passes {
                        public function pass(string $s): string {
                            return $s;
                        }
                    }

                    class Passer {
                        use Passes;
                    }

                    final class ChildPasser extends Passer {}

                    echo (new ChildPasser())->pass((string) $_GET["x"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintReturnOfATraitMethodCalledFromItsUser' => [
                'code' => '<?php // --taint-analysis
                    trait Passes {
                        public function pass(string $s): string {
                            return $s;
                        }
                    }

                    final class Passer {
                        use Passes;

                        public function own(string $s): string {
                            return $this->pass($s);
                        }
                    }

                    echo (new Passer())->own((string) $_GET["x"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintPropertyWrittenAndReadByTraitMethods' => [
                'code' => '<?php // --taint-analysis
                    trait Stores {
                        private string $value = "";

                        public function set(string $value): void {
                            $this->value = $value;
                        }

                        public function get(): string {
                            return $this->value;
                        }
                    }

                    final class Store {
                        use Stores;
                    }

                    $store = new Store();
                    $store->set((string) $_GET["x"]);
                    echo $store->get();',
                'error_message' => 'TaintedHtml',
            ],
            'taintReturnOfAStaticTraitMethod' => [
                'code' => '<?php // --taint-analysis
                    trait Passes {
                        public static function pass(string $s): string {
                            return $s;
                        }
                    }

                    final class Passer {
                        use Passes;
                    }

                    echo Passer::pass((string) $_GET["x"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintReturnOfAStaticTraitMethodCalledThroughSelf' => [
                'code' => '<?php // --taint-analysis
                    trait Passes {
                        public static function pass(string $s): string {
                            return $s;
                        }
                    }

                    final class Passer {
                        use Passes;

                        public static function own(string $s): string {
                            return self::pass($s);
                        }
                    }

                    echo Passer::own((string) $_GET["x"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintReturnOfATraitMethodThroughAnAbstractMethodTheUserImplements' => [
                'code' => '<?php // --taint-analysis
                    trait Wraps {
                        abstract public function inner(string $s): string;

                        public function outer(string $s): string {
                            return $this->inner($s);
                        }
                    }

                    final class Wrapper {
                        use Wraps;

                        public function inner(string $s): string {
                            return $s;
                        }
                    }

                    echo (new Wrapper())->outer((string) $_GET["x"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintReturnOfAnAliasedTraitMethod' => [
                'code' => '<?php // --taint-analysis
                    trait Passes {
                        public function pass(string $s): string {
                            return $s;
                        }
                    }

                    final class Passer {
                        use Passes { pass as protected passed; }

                        public function own(string $s): string {
                            return $this->passed($s);
                        }
                    }

                    echo (new Passer())->own((string) $_GET["x"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintReturnOfAMethodOfATraitUsedByATrait' => [
                'code' => '<?php // --taint-analysis
                    trait Passes {
                        public function pass(string $s): string {
                            return $s;
                        }
                    }

                    trait PassesToo {
                        use Passes;
                    }

                    final class Passer {
                        use PassesToo;
                    }

                    echo (new Passer())->pass((string) $_GET["x"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintReturnOfASpecializedTraitMethod' => [
                'code' => '<?php // --taint-analysis
                    trait Passes {
                        /** @psalm-taint-specialize */
                        public function pass(string $s): string {
                            return $s;
                        }
                    }

                    final class Passer {
                        use Passes;
                    }

                    $passer = new Passer();
                    echo $passer->pass((string) $_GET["x"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintReturnOfATraitMethodCallingACallableParam' => [
                'code' => '<?php // --taint-analysis
                    trait Calls {
                        /** @param callable(string): string $f */
                        public function call(callable $f, string $s): string {
                            return $f($s);
                        }
                    }

                    final class Caller {
                        use Calls;
                    }

                    echo (new Caller())->call(fn(string $x): string => $x, (string) $_GET["x"]);',
                'error_message' => 'TaintedHtml',
            ],
            'complementListingAComplementIsInvalid' => [
                'code' => '<?php // --taint-analysis
                    /** @psalm-taint-source ~(sql|~html) */
                    function externalInput(): string { return "x"; }

                    echo externalInput();',
                'error_message' => 'InvalidDocblock',
            ],
            'flowFromTheClosureResultOfAnInterfaceMethod' => [
                'code' => '<?php
                    interface Cache {
                        /**
                         * @template T
                         * @param callable(): T $compute
                         * @return T
                         * @psalm-flow ($compute()) -> return
                         */
                        public function get(string $key, callable $compute): mixed;
                    }

                    function show(Cache $cache): void {
                        echo $cache->get("key", fn(): string => (string) $_GET["x"]);
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'flowFromTheClosureResultOfAnAbstractStaticMethod' => [
                'code' => '<?php
                    abstract class Computer {
                        /**
                         * @param callable(): string $compute
                         * @psalm-flow ($compute()) -> return
                         */
                        abstract public static function compute(callable $compute): string;

                        public static function show(): void {
                            echo static::compute(function (): string {
                                return (string) $_GET["x"];
                            });
                        }
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'flowFromTheResultOfANamedFunctionGivenAsCallable' => [
                'code' => '<?php
                    /** @psalm-flow ($key, $compute()) -> return */
                    function remember(string $key, callable $compute): string {
                        return $key;
                    }

                    function compute(): string {
                        return (string) $_GET["x"];
                    }

                    echo remember("key", compute(...));',
                'error_message' => 'TaintedHtml',
            ],
            'taintYieldedByATraitGenerator' => [
                'code' => '<?php // --taint-analysis
                    trait Generates {
                        /** @return Generator<int, string> */
                        public function generate(string $s): Generator {
                            yield $s;
                        }
                    }

                    final class Generating {
                        use Generates;
                    }

                    foreach ((new Generating())->generate((string) $_GET["x"]) as $value) {
                        echo $value;
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintByRefParamOfASpecializedOverride' => [
                'code' => '<?php // --taint-analysis
                    class Wrapper {
                        public function wrap(string &$value): void {}
                    }

                    final class BoldWrapper extends Wrapper {
                        /** @psalm-taint-specialize */
                        #[Override]
                        public function wrap(string &$value): void {
                            $value = "<b>" . $value . "</b>";
                        }
                    }

                    $value = (string) $_GET["value"];
                    (new BoldWrapper())->wrap($value);
                    echo $value;',
                'error_message' => 'TaintedHtml',
            ],
            'taintByRefParamOfASpecializedOverrideThroughACallOfTheOverriddenMethod' => [
                'code' => '<?php // --taint-analysis
                    class Reader {
                        public function read(string &$value): void {
                            $value = "literal";
                        }
                    }

                    final class GetReader extends Reader {
                        /** @psalm-taint-specialize */
                        #[Override]
                        public function read(string &$value): void {
                            $value = (string) $_GET["value"];
                        }
                    }

                    function show(Reader $reader): void {
                        $value = "";
                        $reader->read($value);
                        echo $value;
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintPropertyPromotedByATraitConstructorOfAnImmutableClass' => [
                'code' => '<?php // --taint-analysis
                    /** @psalm-immutable */
                    trait HasValue {
                        public function __construct(public readonly string $value) {}
                    }

                    /** @psalm-immutable */
                    final class Value {
                        use HasValue;
                    }

                    $value = new Value((string) $_GET["x"]);
                    echo $value->value;',
                'error_message' => 'TaintedHtml',
                'php_version' => '8.1',
            ],
            'taintValuePassedByRefToAFunctionUnsettingIt' => [
                'code' => '<?php // --taint-analysis
                    function forget(string &$value): void {
                        unset($value);
                    }

                    $value = (string) $_GET["value"];
                    forget($value);
                    echo $value;',
                'error_message' => 'TaintedHtml',
            ],
            'taintByRefParamWrittenBeforeItIsUnset' => [
                'code' => '<?php // --taint-analysis
                    function read_value(string &$value): void {
                        $value = (string) $_GET["value"];
                        unset($value);
                        $value = "literal";
                    }

                    $value = "";
                    read_value($value);
                    echo $value;',
                'error_message' => 'TaintedHtml',
            ],
            'taintValuePassedByRefToAFunctionRebindingIt' => [
                'code' => '<?php // --taint-analysis
                    function rebind(string &$value): void {
                        $literal = "literal";
                        $value = &$literal;
                    }

                    $value = (string) $_GET["value"];
                    rebind($value);
                    echo $value;',
                'error_message' => 'TaintedHtml',
            ],
            'taintValuePassedByRefToAFunctionRebindingItToAStaticVariable' => [
                'code' => '<?php // --taint-analysis
                    function rebind(string &$value): void {
                        static $value = "literal";
                    }

                    $value = (string) $_GET["value"];
                    rebind($value);
                    echo $value;',
                'error_message' => 'TaintedHtml',
            ],
            'taintValuePassedByRefToAFunctionRebindingItToAGlobal' => [
                'code' => '<?php // --taint-analysis
                    function rebind(string &$value): void {
                        global $value;
                    }

                    $input = (string) $_GET["value"];
                    rebind($input);
                    echo $input;',
                'error_message' => 'TaintedHtml',
            ],
            'taintByRefParamOfAClosure' => [
                'code' => '<?php // --taint-analysis
                    $read_value = function (string &$value): void {
                        $value = (string) $_GET["value"];
                    };

                    $value = "";
                    $read_value($value);
                    echo $value;',
                'error_message' => 'TaintedHtml',
            ],
            'taintByRefParamOfAnArrowFunction' => [
                'code' => '<?php // --taint-analysis
                    $read_value = fn (string &$value): string => $value = (string) $_GET["value"];

                    $value = "";
                    $read_value($value);
                    echo $value;',
                'error_message' => 'TaintedHtml',
            ],
            'taintByRefParamOfAFirstClassCallable' => [
                'code' => '<?php // --taint-analysis
                    function read_value(string &$value): void {
                        $value = (string) $_GET["value"];
                    }

                    $read_value = read_value(...);
                    $value = "";
                    $read_value($value);
                    echo $value;',
                'error_message' => 'TaintedHtml',
            ],
            'taintArrayItemPassedByRef' => [
                'code' => '<?php // --taint-analysis
                    function read_value(string &$value): void {
                        $value = (string) $_GET["value"];
                    }

                    $values = ["read" => ""];
                    read_value($values["read"]);
                    echo $values["read"];',
                'error_message' => 'TaintedHtml',
            ],
            'taintStaticPropertyPassedByRef' => [
                'code' => '<?php // --taint-analysis
                    function read_value(string &$value): void {
                        $value = (string) $_GET["value"];
                    }

                    final class Values {
                        public static string $value = "";
                    }

                    read_value(Values::$value);
                    echo Values::$value;',
                'error_message' => 'TaintedHtml',
            ],
            'taintHeaderInTheCurlHttpHeaderOption' => [
                'code' => '<?php // --taint-analysis
                    $curl = curl_init("https://example.com/");
                    curl_setopt($curl, CURLOPT_HTTPHEADER, ["X-Value: " . (string) $_GET["header"]]);',
                'error_message' => 'TaintedHeader',
            ],
            'taintHeaderInTheCurlCustomRequestOption' => [
                'code' => '<?php // --taint-analysis
                    $curl = curl_init("https://example.com/");
                    curl_setopt($curl, \\CURLOPT_CUSTOMREQUEST, (string) $_GET["method"]);',
                'error_message' => 'TaintedHeader',
            ],
            'taintSsrfInTheCurlDefaultProtocolOption' => [
                'code' => '<?php // --taint-analysis
                    curl_setopt(curl_init(), CURLOPT_DEFAULT_PROTOCOL, (string) $_GET["protocol"]);',
                'error_message' => 'TaintedSSRF',
            ],
            'taintHeaderInTheCurlQuoteOption' => [
                'code' => '<?php // --taint-analysis
                    curl_setopt(curl_init(), CURLOPT_QUOTE, ["DELE " . (string) $_GET["file"]]);',
                'error_message' => 'TaintedHeader',
            ],
            'taintFileInTheCurlCookieJarOption' => [
                'code' => '<?php // --taint-analysis
                    curl_setopt(curl_init(), CURLOPT_COOKIEJAR, (string) $_GET["jar"]);',
                'error_message' => 'TaintedFile',
            ],
            'taintSleepInTheCurlMaxRecvSpeedOption' => [
                'code' => '<?php // --taint-analysis
                    curl_setopt(curl_init(), CURLOPT_MAX_RECV_SPEED_LARGE, (int) $_GET["speed"]);',
                'error_message' => 'TaintedSleep',
            ],
            'taintCallableInTheCurlWriteFunctionOption' => [
                'code' => '<?php // --taint-analysis
                    curl_setopt(curl_init(), CURLOPT_WRITEFUNCTION, (string) $_GET["callback"]);',
                'error_message' => 'TaintedCallable',
            ],
            'taintFileInTheCurlCookieFileOptionOfAnArray' => [
                'code' => '<?php // --taint-analysis
                    curl_setopt_array(curl_init(), [CURLOPT_COOKIEFILE => (string) $_GET["file"]]);',
                'error_message' => 'TaintedFile',
            ],
            'taintFileInACurlFile' => [
                'code' => '<?php // --taint-analysis
                    $file = new CURLFile((string) $_GET["path"]);
                    curl_setopt(curl_init(), CURLOPT_POSTFIELDS, ["file" => $file]);',
                'error_message' => 'TaintedFile',
            ],
            'taintFileInCurlFileCreate' => [
                'code' => '<?php // --taint-analysis
                    $file = curl_file_create((string) $_GET["path"]);
                    curl_setopt(curl_init(), CURLOPT_POSTFIELDS, ["file" => $file]);',
                'error_message' => 'TaintedFile',
            ],
            'taintHeaderInTheCurlHttpHeaderOptionOfAnArray' => [
                'code' => '<?php // --taint-analysis
                    $options = [CURLOPT_URL => "https://example.com/"];
                    $options[CURLOPT_HTTPHEADER] = ["X-Value: " . (string) $_GET["header"]];
                    curl_setopt_array(curl_init(), $options);',
                'error_message' => 'TaintedHeader',
            ],
            'taintAnImmutableObjectThroughTheFlowOfItsConstructor' => [
                'code' => '<?php // --taint-analysis
                    /** @psalm-immutable */
                    final class Url {
                        /** @psalm-flow ($url) -> return */
                        public function __construct(string $url) {}
                    }

                    /** @psalm-taint-sink ssrf $url */
                    function fetch(Url $url): void {}

                    fetch(new Url((string) $_GET["url"]));',
                'error_message' => 'TaintedSSRF',
            ],
            'taintAnObjectThroughTheFlowOfItsConstructorFromANamedArgument' => [
                'code' => '<?php // --taint-analysis
                    final class Url {
                        /** @psalm-flow ($url) -> return */
                        public function __construct(int $port = 80, string $url = "") {}
                    }

                    /** @psalm-taint-sink ssrf $url */
                    function fetch(Url $url): void {}

                    fetch(new Url(url: (string) $_GET["url"]));',
                'error_message' => 'TaintedSSRF',
            ],
            'taintAnObjectThroughTheFlowOfItsConstructorFromAVariadicArgument' => [
                'code' => '<?php // --taint-analysis
                    final class Url {
                        /** @psalm-flow ($parts) -> return */
                        public function __construct(string ...$parts) {}
                    }

                    /** @psalm-taint-sink ssrf $url */
                    function fetch(Url $url): void {}

                    fetch(new Url("https:", "", (string) $_GET["host"]));',
                'error_message' => 'TaintedSSRF',
            ],
            'taintedThroughTheMethodsOfAContainer' => [
                'code' => '<?php // --taint-analysis
                    $queue = new SplQueue();
                    $queue->enqueue((string) $_GET["a"]);
                    echo (string) $queue->dequeue();',
                'error_message' => 'TaintedHtml',
            ],
            'taintedThroughWhatAContainerIsBuiltFrom' => [
                'code' => '<?php // --taint-analysis
                    $iterator = new LimitIterator(new ArrayIterator([(string) $_GET["a"]]), 0, 1);
                    echo (string) $iterator->current();',
                'error_message' => 'TaintedHtml',
            ],
            'taintedThroughAContainerHeldInAProperty' => [
                'code' => '<?php // --taint-analysis
                    final class Registry {
                        /** @var SplObjectStorage<object, string> */
                        private SplObjectStorage $names;

                        public function __construct() {
                            $this->names = new SplObjectStorage();
                        }

                        public function add(object $object, string $name): void {
                            $this->names->attach($object, $name);
                        }

                        public function show(object $object): void {
                            echo $this->names->offsetGet($object);
                        }
                    }

                    $registry = new Registry();
                    $object = new stdClass();
                    $registry->add($object, (string) $_GET["a"]);
                    $registry->show($object);',
                'error_message' => 'TaintedHtml',
            ],
            'taintedThroughWhatIsWrittenToAFileObject' => [
                'code' => '<?php // --taint-analysis
                    $file = new SplTempFileObject();
                    $file->fwrite((string) $_GET["a"]);
                    $file->rewind();
                    echo (string) $file->fgets();',
                'error_message' => 'TaintedHtml',
            ],
            'taintAnObjectThroughTheFlowOfItsConstructor' => [
                'code' => '<?php // --taint-analysis
                    final class Url {
                        /** @psalm-flow ($url) -> return */
                        public function __construct(string $url) {}
                    }

                    /** @psalm-taint-sink ssrf $url */
                    function fetch(Url $url): void {}

                    fetch(new Url((string) $_GET["url"]));',
                'error_message' => 'TaintedSSRF',
            ],
            'taintArrayValueWrappedDeeperInALoop' => [
                'code' => '<?php // --taint-analysis
                    $value = (string) $_GET["value"];
                    for ($i = 0; $i < 10; $i++) {
                        $value = ["key" => $value];
                    }

                    echo (string) $value["key"]["key"]["key"];',
                'error_message' => 'TaintedHtml',
            ],
            'taintArrayValueAfterAnotherKeyReachedTheSameNode' => [
                'code' => '<?php // --taint-analysis
                    function show(array $task): void {
                        echo (string) $task["name"];
                    }

                    function relay(array $task): void {
                        show($task);
                    }

                    relay(["other" => (string) $_GET["other"]]);
                    relay(["name" => (string) $_GET["name"]]);',
                'error_message' => 'TaintedHtml',
            ],
            'specializedFetchOfOneKeyWhereManyKeyedArraysConvergeTakesItsValue' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    function getName(array $p): string { return (string) ($p["name"] ?? ""); }

                    function relay(array $row): void {
                        echo getName($row);
                    }

                    relay(["a" => (string)($_GET["a"] ?? "")]);
                    relay(["b" => (string)($_GET["b"] ?? "")]);
                    relay(["c" => (string)($_GET["c"] ?? "")]);
                    relay(["d" => (string)($_GET["d"] ?? "")]);
                    relay(["e" => (string)($_GET["e"] ?? "")]);
                    relay(["f" => (string)($_GET["f"] ?? "")]);
                    relay(["g" => (string)($_GET["g"] ?? "")]);
                    relay(["h" => (string)($_GET["h"] ?? "")]);
                    relay(["i" => (string)($_GET["i"] ?? "")]);
                    relay(["j" => (string)($_GET["j"] ?? "")]);
                    relay(["k" => (string)($_GET["k"] ?? "")]);
                    relay(["l" => (string)($_GET["l"] ?? "")]);
                    relay(["m" => (string)($_GET["m"] ?? "")]);
                    relay(["name" => (string)($_GET["name"] ?? "")]);
                ',
                'error_message' => 'TaintedHtml',
            ],
            'fetchOfAKeyOfAWrapperWhereManyDifferentlyWrappedArraysConvergeTakesItsValue' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    function getName(array $p): string { return (string) ($p["wrapper"]["name"] ?? ""); }

                    function relay(array $row): void {
                        echo getName($row);
                    }

                    relay(["wrapper" => ["a" => (string)($_GET["a"] ?? "")]]);
                    relay(["wrapper" => ["b" => (string)($_GET["b"] ?? "")]]);
                    relay(["wrapper" => ["c" => (string)($_GET["c"] ?? "")]]);
                    relay(["wrapper" => ["d" => (string)($_GET["d"] ?? "")]]);
                    relay(["wrapper" => ["e" => (string)($_GET["e"] ?? "")]]);
                    relay(["wrapper" => ["f" => (string)($_GET["f"] ?? "")]]);
                    relay(["wrapper" => ["g" => (string)($_GET["g"] ?? "")]]);
                    relay(["wrapper" => ["h" => (string)($_GET["h"] ?? "")]]);
                    relay(["wrapper" => ["i" => (string)($_GET["i"] ?? "")]]);
                    relay(["wrapper" => ["j" => (string)($_GET["j"] ?? "")]]);
                    relay(["a" => ["name" => (string)($_GET["a"] ?? "")]]);
                    relay(["b" => ["name" => (string)($_GET["b"] ?? "")]]);
                    relay(["c" => ["name" => (string)($_GET["c"] ?? "")]]);
                    relay(["wrapper" => ["name" => (string)($_GET["name"] ?? "")]]);
                ',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheOverridesThroughAVirtualCall' => [
                'code' => '<?php // --taint-analysis
                    abstract class Mapper {
                        public function find(array $query): void {
                            $this->findBy($query);
                        }

                        public function findBy(array $query): void {}
                    }

                    final class ArticleMapper extends Mapper {
                        public function findBy(array $query): void {
                            echo (string) $query["title"];
                        }
                    }

                    (new ArticleMapper())->find($_GET);',
                'error_message' => 'TaintedHtml',
            ],
            'taintAnOverrideThroughACallOnTheDeclaringClass' => [
                'code' => '<?php // --taint-analysis
                    abstract class Mapper {
                        public function find(string $id): string {
                            return $id;
                        }
                    }

                    final class CommentMapper extends Mapper {
                        public function find(string $id): string {
                            echo $id;
                            return $id;
                        }
                    }

                    function findAny(Mapper $mapper): void {
                        $mapper->find((string) $_GET["id"]);
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintAnOverrideThroughACallOnAnIntermediateClass' => [
                'code' => '<?php // --taint-analysis
                    abstract class Mapper {
                        public function find(string $id): string {
                            return $id;
                        }
                    }

                    abstract class SiteMapper extends Mapper {}

                    final class TownMapper extends SiteMapper {
                        public function find(string $id): string {
                            return parent::find($id);
                        }
                    }

                    final class CommentMapper extends SiteMapper {
                        public function find(string $id): string {
                            echo $id;
                            return $id;
                        }
                    }

                    function findAny(SiteMapper $mapper): void {
                        $mapper->find((string) $_GET["id"]);
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheInheritedMethodThroughACallOnAnIntermediateClass' => [
                'code' => '<?php // --taint-analysis
                    abstract class Mapper {
                        public function find(string $id): string {
                            echo $id;
                            return $id;
                        }
                    }

                    abstract class SiteMapper extends Mapper {}

                    final class CommentMapper extends SiteMapper {
                        public function find(string $id): string {
                            return $id;
                        }
                    }

                    function findAny(SiteMapper $mapper): void {
                        $mapper->find((string) $_GET["id"]);
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintWhatAnInheritedBodyReturnsOfTheObjectThroughAnOverrideSpecializedByInstance' => [
                'code' => '<?php // --taint-analysis
                    /** @psalm-immutable */
                    class Base {
                        public function __construct(public string $value) {}

                        public function me(): self {
                            return $this->pick();
                        }

                        protected function pick(): self {
                            return new self("literal");
                        }
                    }

                    /** @psalm-immutable */
                    final class Child extends Base {
                        #[Override]
                        protected function pick(): self {
                            return $this;
                        }
                    }

                    $child = new Child((string) $_GET["value"]);
                    echo $child->me()->value;',
                'error_message' => 'TaintedHtml',
            ],
            'taintWhatAnInheritedBodyReturnsOfAnObjectHeldByTheObjectThroughAnOverrideSpecializedByInstance' => [
                'code' => '<?php // --taint-analysis
                    /** @psalm-immutable */
                    final class Inner {
                        public function __construct(private string $value) {}

                        public function getValue(): string {
                            return $this->value;
                        }
                    }

                    /** @psalm-immutable */
                    class Base {
                        public function __construct(protected Inner $inner) {}

                        public function getInner(): Inner {
                            return $this->pick();
                        }

                        protected function pick(): Inner {
                            return new Inner("literal");
                        }
                    }

                    /** @psalm-immutable */
                    final class Child extends Base {
                        #[Override]
                        protected function pick(): Inner {
                            return $this->inner;
                        }
                    }

                    $child = new Child(new Inner((string) $_GET["value"]));
                    echo $child->getInner()->getValue();',
                'error_message' => 'TaintedHtml',
            ],
            'taintWhatAnOverrideSpecializedByInstanceReturnsOfTheObjectThroughAVirtualCall' => [
                'code' => '<?php // --taint-analysis
                    /** @psalm-immutable */
                    class Base {
                        public function __construct(public string $value) {}

                        public function get(): string {
                            return "literal";
                        }
                    }

                    /** @psalm-immutable */
                    final class Child extends Base {
                        #[Override]
                        public function get(): string {
                            return $this->value;
                        }
                    }

                    function show(Base $base): void {
                        echo $base->get();
                    }

                    show(new Child((string) $_GET["value"]));',
                'error_message' => 'TaintedHtml',
            ],
            'taintWhatAPureOverrideReturnsOfItsArgumentThroughAVirtualCallOfAPureMethod' => [
                'code' => '<?php // --taint-analysis
                    class Base {
                        /** @psalm-pure */
                        public function get(string $value): string {
                            return "literal";
                        }
                    }

                    final class Child extends Base {
                        /** @psalm-pure */
                        #[Override]
                        public function get(string $value): string {
                            return $value;
                        }
                    }

                    function show(Base $base): void {
                        echo $base->get((string) $_GET["value"]);
                    }

                    show(new Child());',
                'error_message' => 'TaintedHtml',
            ],
            'taintAStaticOverrideThroughACallOnAnIntermediateClass' => [
                'code' => '<?php // --taint-analysis
                    abstract class Mapper {
                        public static function create(string $id): string {
                            return $id;
                        }
                    }

                    abstract class SiteMapper extends Mapper {}

                    final class CommentMapper extends SiteMapper {
                        public static function create(string $id): string {
                            echo $id;
                            return $id;
                        }
                    }

                    function createAny(SiteMapper $mapper): void {
                        $mapper::create((string) $_GET["id"]);
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheDeclaredMethodThroughACallOnAClassInheritingIt' => [
                'code' => '<?php // --taint-analysis
                    abstract class Mapper {
                        public function find(string $id): string {
                            echo $id;
                            return $id;
                        }
                    }

                    final class CommentMapper extends Mapper {}

                    abstract class OtherMapper extends Mapper {
                        public function find(string $id): string {
                            return $id;
                        }
                    }

                    function findComment(CommentMapper $mapper): void {
                        $mapper->find((string) $_GET["id"]);
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintedNamedArgumentToSinkParameter' => [
                'code' => '<?php // --taint-analysis
                    /** @psalm-taint-sink html $dangerous */
                    function mySink(string $safe, string $dangerous) : void {}

                    $tainted = (string) $_GET["x"];
                    mySink(dangerous: $tainted, safe: "ok");',
                'error_message' => 'TaintedHtml',
            ],
            'taintedNamedArgumentThroughMethodToSink' => [
                'code' => '<?php // --taint-analysis
                    class A {
                        public function process(string $safe, string $dangerous) : void {
                            echo $dangerous;
                        }
                    }

                    $tainted = (string) $_GET["x"];
                    (new A)->process(dangerous: $tainted, safe: "ok");',
                'error_message' => 'TaintedHtml',
            ],
            'taintedInputThroughRecursiveFunction' => [
                'code' => '<?php
                    function f(string $s, int $depth): string {
                        if ($depth > 0) {
                            return f($s, $depth - 1);
                        }

                        return $s;
                    }

                    echo f((string) $_GET["a"], 3);',
                'error_message' => 'TaintedHtml',
            ],
            'taintedInputThroughMutualRecursion' => [
                'code' => '<?php
                    function a(string $s): string {
                        if (rand(0, 1)) {
                            return b($s);
                        }

                        return $s;
                    }

                    function b(string $s): string {
                        if (rand(0, 1)) {
                            return a($s);
                        }

                        return $s;
                    }

                    echo a((string) $_GET["a"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintedInputThroughRecursiveMethod' => [
                'code' => '<?php
                    final class A {
                        public function f(string $s, int $depth): string {
                            if ($depth > 0) {
                                return $this->f($s, $depth - 1);
                            }

                            return $s;
                        }
                    }

                    echo (new A)->f((string) $_GET["a"], 3);',
                'error_message' => 'TaintedHtml',
            ],
            'taintedInputThroughRecursiveSpecializedMethod' => [
                'code' => '<?php
                    final class A {
                        /** @psalm-taint-specialize */
                        public function f(string $s, int $depth): string {
                            if ($depth > 0) {
                                return $this->f($s, $depth - 1);
                            }

                            return $s;
                        }
                    }

                    echo (new A)->f((string) $_GET["a"], 3);',
                'error_message' => 'TaintedHtml',
            ],
            'taintedInputThroughLoopReassignment' => [
                'code' => '<?php
                    function wrap(string $s): string {
                        return "<" . $s . ">";
                    }

                    $s = (string) $_GET["a"];

                    for ($i = 0; $i < 10; $i++) {
                        $s = wrap($s);
                    }

                    echo $s;',
                'error_message' => 'TaintedHtml',
            ],
            'taintedInputThroughRecursivePropertyCycle' => [
                'code' => '<?php
                    final class Node {
                        public ?Node $next = null;
                        public string $value = "";
                    }

                    $first = new Node();
                    $first->value = (string) $_GET["a"];
                    $second = new Node();
                    $second->next = $first;
                    $first->next = $second;

                    $node = $first;

                    while (rand(0, 1)) {
                        $node = $node->next ?? $node;
                    }

                    echo $node->value;',
                'error_message' => 'TaintedHtml',
            ],
            'taintedLlmPromptFromUserInput' => [
                'code' => '<?php
                    class LlmAgent {
                        /** @psalm-taint-sink llm_prompt $prompt */
                        public function prompt(string $prompt): string {
                            return "";
                        }
                    }

                    $agent = new LlmAgent();
                    $agent->prompt((string) $_GET["question"]);',
                'error_message' => 'TaintedLlmPrompt',
            ],
            'taintedLlmPromptFromConcatenatedInput' => [
                'code' => '<?php
                    class LlmAgent {
                        /** @psalm-taint-sink llm_prompt $prompt */
                        public function prompt(string $prompt): string {
                            return "";
                        }
                    }

                    $agent = new LlmAgent();
                    $agent->prompt("Tell me about " . (string) $_GET["topic"]);',
                'error_message' => 'TaintedLlmPrompt',
            ],
            'taintedLlmPromptThroughFunction' => [
                'code' => '<?php
                    class LlmAgent {
                        /** @psalm-taint-sink llm_prompt $prompt */
                        public function prompt(string $prompt): string {
                            return "";
                        }
                    }

                    function buildPrompt(string $userInput): string {
                        return "Tell me about " . $userInput;
                    }

                    $agent = new LlmAgent();
                    $agent->prompt(buildPrompt((string) $_GET["topic"]));',
                'error_message' => 'TaintedLlmPrompt',
            ],
            'taintedInputFromPhpInputViaFileGetContents' => [
                'code' => '<?php
                    echo file_get_contents("php://input");',
                'error_message' => 'TaintedHtml',
            ],
            'taintedInputFromPhpStdinViaFileGetContents' => [
                'code' => '<?php
                    echo file_get_contents("php://stdin");',
                'error_message' => 'TaintedHtml',
            ],
            'taintedInputFromPhpInputViaFopenAndFread' => [
                'code' => '<?php
                    $fp = fopen("php://input", "r");
                    if ($fp !== false) {
                        echo fread($fp, 1024);
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintedInputFromStdinConstant' => [
                'code' => '<?php
                    echo fgets(STDIN);',
                'error_message' => 'TaintedHtml',
            ],
            'taintedInputFromStdinViaStreamGetContents' => [
                'code' => '<?php
                    echo stream_get_contents(STDIN);',
                'error_message' => 'TaintedHtml',
            ],
            'taintedHtmlWrittenToPhpOutputWithFilePutContents' => [
                'code' => '<?php
                    file_put_contents("php://output", (string) $_GET["x"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintedHtmlWrittenToPhpStdoutWithFilePutContents' => [
                'code' => '<?php
                    file_put_contents("php://stdout", (string) $_GET["x"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintedHtmlWrittenToAPhpOutputStream' => [
                'code' => '<?php
                    /** @psalm-suppress PossiblyFalseArgument */
                    fwrite(fopen("php://output", "w"), (string) $_GET["x"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintedHtmlWrittenToAPhpStdoutStream' => [
                'code' => '<?php
                    /** @psalm-suppress PossiblyFalseArgument */
                    fwrite(fopen("php://stdout", "w"), (string) $_GET["x"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintedTextWithQuotesWrittenToAPhpOutputStream' => [
                'code' => '<?php
                    /** @psalm-suppress PossiblyFalseArgument */
                    fwrite(fopen("php://output", "w"), strip_tags((string) $_GET["x"]));',
                'error_message' => 'TaintedTextWithQuotes',
            ],
            'taintedHtmlWrittenToAPhpOutputStreamHeldByAVariable' => [
                'code' => '<?php
                    $stream = fopen("php://output", "w");
                    if ($stream !== false) {
                        $output = $stream;
                        fputs($output, "<p>");
                        fputcsv($output, [(string) $_GET["x"]]);
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintedHtmlFormattedToAPhpOutputStream' => [
                'code' => '<?php
                    $stream = fopen("php://output", "w");
                    if ($stream !== false) {
                        fprintf($stream, "<p>%s</p>", (string) $_GET["x"]);
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintedHtmlFormattedAsAStringByFprintfToAPhpOutputStream' => [
                'code' => '<?php
                    $out = fopen("php://output", "w");
                    if ($out !== false) {
                        fprintf($out, "%s", (string) $_GET["x"]);
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintedHtmlFormattedAsAStringByVfprintfToAPhpOutputStream' => [
                'code' => '<?php
                    $out = fopen("php://output", "w");
                    if ($out !== false) {
                        vfprintf($out, "%s items", [(string) $_GET["x"]]);
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintedInputCopiedToAPhpOutputStream' => [
                'code' => '<?php
                    $input = fopen("php://input", "r");
                    $output = fopen("php://output", "w");
                    if ($input !== false && $output !== false) {
                        stream_copy_to_stream($input, $output);
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintedHtmlWrittenToAPhpOutputSplFileObject' => [
                'code' => '<?php
                    $file = new SplFileObject("php://output", "w");
                    $file->fwrite("<p>");
                    $file->fwrite((string) $_GET["x"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintedInputFromSocketRead' => [
                'code' => '<?php
                    /** @param resource $socket */
                    function readFrom($socket): void {
                        echo (string) socket_read($socket, 1024);
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintedInputFromSocketRecvByReference' => [
                'code' => '<?php
                    /** @param resource $socket */
                    function readFrom($socket): void {
                        socket_recv($socket, $data, 1024, 0);
                        echo (string) $data;
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintedInputFromSocketRecvfromNamedArgument' => [
                'code' => '<?php
                    /** @param resource $socket */
                    function readFrom($socket): void {
                        socket_recvfrom($socket, length: 1024, flags: 0, address: $address, data: $data);
                        echo (string) $data;
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintedInputFromFsockopenStream' => [
                'code' => '<?php
                    $fp = fsockopen("example.com", 80);
                    if ($fp !== false) {
                        echo (string) fgets($fp);
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintedInputFromStreamSocketClient' => [
                'code' => '<?php
                    $fp = stream_socket_client("tcp://example.com:80");
                    if ($fp !== false) {
                        echo (string) stream_get_contents($fp);
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintedInputFromCurlExec' => [
                'code' => '<?php
                    $ch = curl_init("https://example.com");
                    echo (string) curl_exec($ch);',
                'error_message' => 'TaintedHtml',
            ],
            'taintedInputFromSocketReadFirstClassCallable' => [
                'code' => '<?php
                    /** @param resource $socket */
                    function readFrom($socket): void {
                        $f = socket_read(...);
                        echo (string) $f($socket, 1024);
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintedInputFromFirstClassCallable' => [
                'code' => '<?php
                    $f = file_get_contents(...);
                    echo $f("php://input");',
                'error_message' => 'TaintedHtml',
            ],
            'taintedInputFromFirstClassCallableStreamRead' => [
                'code' => '<?php
                    $f = fgets(...);
                    echo $f(STDIN);',
                'error_message' => 'TaintedHtml',
            ],
            'flowFromThisToReturn' => [
                'code' => '<?php
                    interface Message {
                        /** @psalm-flow ($this) -> return */
                        public function getBody(): string;
                    }

                    /** @psalm-taint-source input */
                    function fetch(): Message {
                        throw new RuntimeException();
                    }

                    $message = fetch();
                    echo $message->getBody();',
                'error_message' => 'TaintedHtml',
            ],
            'flowFromThisToReturnOfSpecializedCall' => [
                'code' => '<?php
                    interface Message {
                        /**
                         * @psalm-taint-specialize
                         * @psalm-flow ($this) -> return
                         */
                        public function getBody(): string;
                    }

                    /** @psalm-taint-source input */
                    function fetch(): Message {
                        throw new RuntimeException();
                    }

                    echo fetch()->getBody();',
                'error_message' => 'TaintedHtml',
            ],
            'flowFromThisThroughChainedCalls' => [
                'code' => '<?php
                    interface Stream {
                        /**
                         * @psalm-taint-specialize
                         * @psalm-flow ($this) -> return
                         */
                        public function getContents(): string;
                    }

                    interface Message {
                        /**
                         * @psalm-taint-specialize
                         * @psalm-flow ($this) -> return
                         */
                        public function getBody(): Stream;
                    }

                    /** @psalm-taint-source input */
                    function fetch(): Message {
                        throw new RuntimeException();
                    }

                    $message = fetch();
                    echo $message->getBody()->getContents();',
                'error_message' => 'TaintedHtml',
            ],
            'taintedInputFromFirstClassCallableExplicitSource' => [
                'code' => '<?php
                    /**
                     * @psalm-taint-source input
                     */
                    function getName(): string {
                        return "";
                    }

                    $f = getName(...);
                    echo $f();',
                'error_message' => 'TaintedHtml',
            ],
            'taintedInputFromFirstClassCallableFlow' => [
                'code' => '<?php
                    /**
                     * @psalm-flow ($r) -> return
                     */
                    function some_stub(string $r): string { return ""; }

                    $f = some_stub(...);
                    echo $f((string) $_GET["untrusted"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintedInputFromFirstClassCallableFlowUserFunction' => [
                'code' => '<?php
                    function echoback(string $in): string {
                        return $in;
                    }

                    $f = echoback(...);
                    echo $f((string) $_GET["untrusted"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintedInputToFirstClassCallableSink' => [
                'code' => '<?php
                    /**
                     * @psalm-taint-sink html $in
                     */
                    function my_sink(string $in): void {}

                    $f = my_sink(...);
                    $f((string) $_GET["untrusted"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintedInputFromAmbiguousFirstClassCallableSourceFirstBranch' => [
                'code' => '<?php
                    /**
                     * @psalm-taint-source input
                     */
                    function ambig_source(): string {
                        return "";
                    }

                    function ambig_plain(int $x = 0): string {
                        return (string) $x;
                    }

                    $f = rand(0, 1) ? ambig_source(...) : ambig_plain(...);
                    echo $f();',
                'error_message' => 'TaintedHtml',
            ],
            'taintedInputFromAmbiguousFirstClassCallableSourceSecondBranch' => [
                'code' => '<?php
                    /**
                     * @psalm-taint-source input
                     */
                    function ambig_source(): string {
                        return "";
                    }

                    function ambig_plain(int $x = 0): string {
                        return (string) $x;
                    }

                    $f = rand(0, 1) ? ambig_plain(...) : ambig_source(...);
                    echo $f();',
                'error_message' => 'TaintedHtml',
            ],
            'taintedInputThroughAmbiguousFirstClassCallableFlow' => [
                'code' => '<?php
                    /**
                     * @psalm-flow ($r) -> return
                     */
                    function ambig_flow(string $r): string { return ""; }

                    function ambig_other(string $r = ""): string { return ""; }

                    $f = rand(0, 1) ? ambig_flow(...) : ambig_other(...);
                    echo $f((string) $_GET["untrusted"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintedInputToAmbiguousFirstClassCallableSink' => [
                'code' => '<?php
                    /**
                     * @psalm-taint-sink html $in
                     */
                    function ambig_html_sink(string $in): void {}

                    function ambig_no_sink(string $in, int $extra = 0): void {}

                    $f = rand(0, 1) ? ambig_html_sink(...) : ambig_no_sink(...);
                    $f((string) $_GET["untrusted"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintedInputFromMethodReturnTypeSimple' => [
                'code' => '<?php
                    class A {
                        public function getUserId() : string {
                            return (string) $_GET["user_id"];
                        }

                        public function getAppendedUserId() : string {
                            return "aaaa" . $this->getUserId();
                        }

                        public function deleteUser(PDO $pdo) : void {
                            $userId = $this->getAppendedUserId();
                            $pdo->exec("delete from users where user_id = " . $userId);
                        }
                    }',
                'error_message' => 'TaintedSql',
            ],
            'taintTheColumnArrayColumnTakesOfTheRows' => [
                'code' => '<?php
                    /** @param list<array{name: string, color: string}> $rows */
                    function printNames(array $rows): void {
                        foreach (array_column($rows, "name") as $name) {
                            echo $name;
                        }
                    }

                    printNames([["name" => (string) $_GET["name"], "color" => "red"]]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheIndexColumnArrayColumnTakesOfTheRows' => [
                'code' => '<?php
                    /** @param list<array{name: string, color: string}> $rows */
                    function printNames(array $rows): void {
                        foreach (array_column($rows, "color", "name") as $name => $_) {
                            echo $name;
                        }
                    }

                    printNames([["name" => (string) $_GET["name"], "color" => "red"]]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheWholeRowsArrayColumnTakesWithoutAColumnKey' => [
                'code' => '<?php
                    /** @param list<array{name: string, color: string}> $rows */
                    function printNames(array $rows): void {
                        foreach (array_column($rows, null) as $row) {
                            echo $row["name"];
                        }
                    }

                    printNames([["name" => (string) $_GET["name"], "color" => "red"]]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheSubjectOfAMatchWithADefaultArmPastIt' => [
                'code' => '<?php
                    $type = (string) $_GET["type"];
                    $id = match ($type) {
                        "organization" => 1,
                        default => 2,
                    };
                    echo $type;',
                'error_message' => 'TaintedHtml',
            ],
            'taintedHtmlFromSourceReturningString' => [
                'code' => '<?php
                    final class Request {
                        /** @psalm-taint-source input */
                        public function getString(string $name): string {
                            return "";
                        }
                    }

                    // only the taints a string cannot hold are left out
                    echo (new Request())->getString("name");',
                'error_message' => 'TaintedHtml',
            ],
            'taintedHtmlFromFunctionReturningString' => [
                'code' => '<?php
                    function first(array $values): string {
                        /** @var string */
                        return $values[0];
                    }

                    echo first((array) $_GET["names"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintedNosqlKeepsTaintWithoutScalarNarrowing' => [
                'code' => '<?php
                    /** @psalm-taint-sink nosql $filter */
                    function query($filter): void {}

                    /** @psalm-taint-source input */
                    function input(): mixed {
                        return null;
                    }

                    $value = input();
                    if (!is_string($value)) {
                        query(["name" => $value]);
                    }',
                'error_message' => 'TaintedNosql',
            ],
            'taintedNosqlFromFunctionReturningArray' => [
                'code' => '<?php
                    /** @psalm-taint-sink nosql $filter */
                    function query($filter): void {}

                    function filterOf(array $values): array {
                        /** @var array */
                        return $values["filter"];
                    }

                    query(["filter" => filterOf((array) $_GET["q"])]);',
                'error_message' => 'TaintedNosql',
            ],
            'taintedHeaderInMail' => [
                'code' => '<?php
                    mail("admin@example.com", "Report", "body", "From: " . $_GET["from"]);',
                'error_message' => 'TaintedHeader',
            ],
            'taintedShellInMailParameters' => [
                'code' => '<?php
                    mail("admin@example.com", "Report", "body", "", "-f" . $_GET["from"]);',
                'error_message' => 'TaintedShell',
            ],
            'taintedSsrfInImageCreateFromJpeg' => [
                'code' => '<?php
                    imagecreatefromjpeg((string) $_GET["path"]);',
                'error_message' => 'TaintedSSRF',
            ],
            'taintedFileInPharDataExtractTo' => [
                'code' => '<?php
                    $archive = new PharData("/tmp/a.tar"); $archive->extractTo((string) $_GET["dir"]);',
                'error_message' => 'TaintedFile',
            ],
            'taintedSsrfInDomDocumentLoad' => [
                'code' => '<?php
                    (new DOMDocument())->load((string) $_GET["url"]);',
                'error_message' => 'TaintedSSRF',
            ],
            'taintedSsrfInSimplexmlLoadFile' => [
                'code' => '<?php
                    simplexml_load_file((string) $_GET["url"]);',
                'error_message' => 'TaintedSSRF',
            ],
            'taintedSsrfInMd5File' => [
                'code' => '<?php
                    md5_file((string) $_GET["path"]);',
                'error_message' => 'TaintedSSRF',
            ],
            'taintedSsrfInSoapClientWithCastWsdl' => [
                'code' => '<?php
                    new SoapClient((string) $_GET["wsdl"]);',
                'error_message' => 'TaintedSSRF',
            ],
            'taintedHtmlFromDnsRecord' => [
                'code' => '<?php
                    $records = dns_get_record("example.com", DNS_TXT); echo (string) ($records[0]["txt"] ?? "");',
                'error_message' => 'TaintedHtml',
            ],
            'taintedHtmlFromReverseDns' => [
                'code' => '<?php
                    echo (string) gethostbyaddr("127.0.0.1");',
                'error_message' => 'TaintedHtml',
            ],
            'taintedHtmlFromGetopt' => [
                'code' => '<?php
                    $options = getopt("a:");
                    echo (string) ($options["a"] ?? "");',
                'error_message' => 'TaintedHtml',
            ],
            'taintedFileInPharAddFile' => [
                'code' => '<?php
                    $phar = new Phar("/tmp/a.phar");
                    $phar->addFile((string) $_GET["path"]);',
                'error_message' => 'TaintedFile',
            ],
            'taintedSsrfInCurlSetoptArray' => [
                'code' => '<?php
                    $ch = curl_init();
                    curl_setopt_array($ch, [CURLOPT_PROXY => $_GET["proxy"]]);',
                'error_message' => 'TaintedSSRF',
            ],
            'taintedSsrfInFsockopen' => [
                'code' => '<?php
                    fsockopen($_GET["host"], 80);',
                'error_message' => 'TaintedSSRF',
            ],
            'taintedSsrfInStreamSocketClient' => [
                'code' => '<?php
                    stream_socket_client($_GET["address"]);',
                'error_message' => 'TaintedSSRF',
            ],
            'taintedSsrfInFopen' => [
                'code' => '<?php
                    fopen($_GET["url"], "r");',
                'error_message' => 'TaintedSSRF',
            ],
            'taintedSsrfInSoapClient' => [
                'code' => '<?php
                    new SoapClient($_GET["wsdl"]);',
                'error_message' => 'TaintedSSRF',
            ],
            'taintedCookieInSetrawcookie' => [
                'code' => '<?php
                    setrawcookie("session", $_GET["value"]);',
                'error_message' => 'TaintedCookie',
            ],
            'taintedSqlInSqlite3' => [
                'code' => '<?php
                    $db = new SQLite3("app.db");
                    $db->exec("DELETE FROM users WHERE name = \'" . $_GET["name"] . "\'");',
                'error_message' => 'TaintedSql',
            ],
            'taintedFileInGlob' => [
                'code' => '<?php
                    // glob() is declared by a stub, which must keep its taint sinks
                    glob($_GET["pattern"]);',
                'error_message' => 'TaintedFile',
            ],
            'taintedNosqlFromMongoQuery' => [
                'code' => '<?php
                    function getUser() : MongoDB\Driver\Query {
                        $filter = ["username" => $_GET["username"]];
                        return new MongoDB\Driver\Query($filter);
                    }',
                'error_message' => 'TaintedNosql',
            ],
            'taintedNosqlFromMongoBulkWrite' => [
                'code' => '<?php
                    function deleteUser(MongoDB\Driver\BulkWrite $bulk) : void {
                        $bulk->delete(["username" => $_GET["username"]]);
                    }',
                'error_message' => 'TaintedNosql',
            ],
            'taintedNosqlFromWholeUserControlledFilter' => [
                'code' => '<?php
                    function getUser() : MongoDB\Driver\Query {
                        // $_GET["filter"] may be an array like ["username" => ["$ne" => null]]
                        return new MongoDB\Driver\Query((array) $_GET["filter"]);
                    }',
                'error_message' => 'TaintedNosql',
            ],
            'taintedNosqlFromJsonDecodedString' => [
                'code' => '<?php
                    /** @psalm-taint-sink nosql $filter */
                    function query(mixed $filter): void {}

                    // the string cast removes the nosql taint, but the decoded value can be ["$ne" => null] again
                    $json = (string) $_GET["filter"];
                    query(json_decode($json, true));',
                'error_message' => 'TaintedNosql',
            ],
            'taintedNosqlFromJsonDecodedStringParameter' => [
                'code' => '<?php
                    /** @psalm-taint-sink nosql $filter */
                    function query(mixed $filter): void {}

                    function search(string $json): void {
                        query(["name" => json_decode($json)]);
                    }

                    search((string) $_POST["json"]);',
                'error_message' => 'TaintedNosql',
            ],
            'taintedNosqlFromParseStr' => [
                'code' => '<?php
                    /** @psalm-taint-sink nosql $filter */
                    function query(mixed $filter): void {}

                    // "name[$ne]=" gives ["name" => ["$ne" => ""]]
                    parse_str($_SERVER["QUERY_STRING"] ?? "", $params);
                    query($params);',
                'error_message' => 'TaintedNosql',
            ],
            'taintedNosqlFromParseIniString' => [
                'code' => '<?php
                    /** @psalm-taint-sink nosql $filter */
                    function query(mixed $filter): void {}

                    query(parse_ini_string((string) $_POST["settings"], true));',
                'error_message' => 'TaintedNosql',
            ],
            'taintedInputFromFunctionReturnType' => [
                'code' => '<?php
                    function getName() : string {
                        return $_GET["name"] ?? "unknown";
                    }

                    echo getName();',
                'error_message' => 'TaintedHtml - src' . DIRECTORY_SEPARATOR . 'somefile.php:6:21 - Detected tainted HTML in path: $_GET (src' . DIRECTORY_SEPARATOR . 'somefile.php:3:32) -> $_GET[\'name\'] (src' . DIRECTORY_SEPARATOR . 'somefile.php:3:32) -> coalesce (src' . DIRECTORY_SEPARATOR . 'somefile.php:3:32) -> getName (src' . DIRECTORY_SEPARATOR . 'somefile.php:2:42) -> call to echo (src' . DIRECTORY_SEPARATOR . 'somefile.php:6:26) -> builtin echo#1',
            ],
            'taintedInputFromExplicitTaintSource' => [
                'code' => '<?php
                    /**
                     * @psalm-taint-source input
                     */
                    function getName() : string {
                        return "";
                    }

                    echo getName();',
                'error_message' => 'TaintedHtml',
            ],
            'taintedInputFromExplicitTaintSourceStaticMethod' => [
                'code' => '<?php
                    class Request {
                        /**
                         * @psalm-taint-source input
                         */
                        public static function getName() : string {
                            return "";
                        }
                    }


                    echo Request::getName();',
                'error_message' => 'TaintedHtml',
            ],
            'taintedInputFromGetArray' => [
                'code' => '<?php
                    function getName(array $data) : string {
                        return $data["name"] ?? "unknown";
                    }

                    $name = getName($_GET);

                    echo $name;',
                'error_message' => 'TaintedHtml',
            ],
            'taintedInputFromReturnToInclude' => [
                'code' => '<?php
                    $a = (string) $_GET["file"];
                    $b = "hello" . $a;
                    include str_replace("a", "b", $b);',
                'error_message' => 'TaintedInclude',
            ],
            'taintedInputFromReturnToEval' => [
                'code' => '<?php
                    $a = $_GET["file"];
                    eval("<?php" . $a);',
                'error_message' => 'TaintedEval',
            ],
            'taintedInputFromReturnTypeToEcho' => [
                'code' => '<?php
                    class A {
                        public function getUserId() : string {
                            return (string) $_GET["user_id"];
                        }

                        public function getAppendedUserId() : string {
                            return "aaaa" . $this->getUserId();
                        }

                        public function deleteUser(PDO $pdo) : void {
                            $userId = $this->getAppendedUserId();
                            echo $userId;
                        }
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintedInputInCreatedArrayIsEchoed' => [
                'code' => '<?php
                    $name = $_GET["name"] ?? "unknown";

                    $data = ["name" => $name];

                    echo "<h1>" . $data["name"] . "</h1>";',
                'error_message' => 'TaintedHtml',
            ],
            'testTaintedInputInAssignedArrayIsEchoed' => [
                'code' => '<?php
                    $name = $_GET["name"] ?? "unknown";

                    $data = [];
                    $data["name"] = $name;

                    echo "<h1>" . $data["name"] . "</h1>";',
                'error_message' => 'TaintedHtml',
            ],
            'taintedInputDirectly' => [
                'code' => '<?php
                    class A {
                        public function deleteUser(PDO $pdo) : void {
                            $userId = (string) $_GET["user_id"];
                            $pdo->exec("delete from users where user_id = " . $userId);
                        }
                    }',
                'error_message' => 'TaintedSql',
            ],
            'taintedInputFromReturnTypeWithBranch' => [
                'code' => '<?php
                    class A {
                        public function getUserId() : string {
                            return (string) $_GET["user_id"];
                        }

                        public function getAppendedUserId() : string {
                            $userId = $this->getUserId();

                            if (rand(0, 1)) {
                                $userId .= "aaa";
                            } else {
                                $userId .= "bb";
                            }

                            return $userId;
                        }

                        public function deleteUser(PDO $pdo) : void {
                            $userId = $this->getAppendedUserId();
                            $pdo->exec("delete from users where user_id = " . $userId);
                        }
                    }',
                'error_message' => 'TaintedSql',
            ],
            'sinkAnnotation' => [
                'code' => '<?php
                    class A {
                        public function getUserId() : string {
                            return (string) $_GET["user_id"];
                        }

                        public function getAppendedUserId() : string {
                            return "aaaa" . $this->getUserId();
                        }

                        public function deleteUser(PDOWrapper $pdo) : void {
                            $userId = $this->getAppendedUserId();
                            $pdo->exec("delete from users where user_id = " . $userId);
                        }
                    }

                    class PDOWrapper {
                        /**
                         * @psalm-taint-sink sql $sql
                         */
                        public function exec(string $sql) : void {}
                    }',
                'error_message' => 'TaintedSql',
            ],
            'taintedInputFromParam' => [
                'code' => '<?php
                    class A {
                        public function getUserId() : string {
                            return (string) $_GET["user_id"];
                        }

                        public function getAppendedUserId() : string {
                            return "aaaa" . $this->getUserId();
                        }

                        public function doDelete(PDO $pdo) : void {
                            $userId = $this->getAppendedUserId();
                            $this->deleteUser($pdo, $userId);
                        }

                        public function deleteUser(PDO $pdo, string $userId) : void {
                            $pdo->exec("delete from users where user_id = " . $userId);
                        }
                    }',
                'error_message' => 'TaintedSql - src' . DIRECTORY_SEPARATOR . 'somefile.php:17:40 - Detected tainted SQL in path: $_GET (src' . DIRECTORY_SEPARATOR . 'somefile.php:4:45) -> $_GET[\'user_id\'] (src' . DIRECTORY_SEPARATOR . 'somefile.php:4:45) -> string-cast (src' . DIRECTORY_SEPARATOR . 'somefile.php:4:45) -> A::getUserId (src' . DIRECTORY_SEPARATOR . 'somefile.php:3:55) -> concat (src' . DIRECTORY_SEPARATOR . 'somefile.php:8:36) -> A::getAppendedUserId (src' . DIRECTORY_SEPARATOR . 'somefile.php:7:63) -> $userId (src' . DIRECTORY_SEPARATOR . 'somefile.php:12:29) -> call to A::deleteUser (src' . DIRECTORY_SEPARATOR . 'somefile.php:13:53) -> A::deleteUser#2 (src' . DIRECTORY_SEPARATOR . 'somefile.php:16:62) -> $userId (src' . DIRECTORY_SEPARATOR . 'somefile.php:16:69) -> call to PDO::exec (src' . DIRECTORY_SEPARATOR . 'somefile.php:17:40) -> PDO::exec#1',
            ],
            'taintedInputToParam' => [
                'code' => '<?php
                    class A {
                        public function getUserId(PDO $pdo) : void {
                            $this->deleteUser(
                                $pdo,
                                $this->getAppendedUserId((string) $_GET["user_id"])
                            );
                        }

                        public function getAppendedUserId(string $user_id) : string {
                            return "aaa" . $user_id;
                        }

                        public function deleteUser(PDO $pdo, string $userId) : void {
                            $pdo->exec("delete from users where user_id = " . $userId);
                        }
                    }',
                'error_message' => 'TaintedSql',
            ],
            'taintedInputToParamAfterAssignment' => [
                'code' => '<?php
                    class A {
                        public function getUserId(PDO $pdo) : void {
                            $this->deleteUser(
                                $pdo,
                                $this->getAppendedUserId((string) $_GET["user_id"])
                            );
                        }

                        public function getAppendedUserId(string $user_id) : string {
                            return "aaa" . $user_id;
                        }

                        public function deleteUser(PDO $pdo, string $userId) : void {
                            $userId2 = $userId;
                            $pdo->exec("delete from users where user_id = " . $userId2);
                        }
                    }',
                'error_message' => 'TaintedSql',
            ],
            'taintedInputToParamAlternatePath' => [
                'code' => '<?php
                    class A {
                        public function getUserId(PDO $pdo) : void {
                            $this->deleteUser(
                                $pdo,
                                self::doFoo(),
                                $this->getAppendedUserId((string) $_GET["user_id"])
                            );
                        }

                        public function getAppendedUserId(string $user_id) : string {
                            return "aaa" . $user_id;
                        }

                        public static function doFoo() : string {
                            return "hello";
                        }

                        public function deleteUser(PDO $pdo, string $userId, string $userId2) : void {
                            $pdo->exec("delete from users where user_id = " . $userId);

                            if (rand(0, 1)) {
                                $pdo->exec("delete from users where user_id = " . $userId2);
                            }
                        }
                    }',
                'error_message' => 'TaintedSql - src' . DIRECTORY_SEPARATOR . 'somefile.php:23:44 - Detected tainted SQL in path: $_GET (src' . DIRECTORY_SEPARATOR . 'somefile.php:7:67) -> $_GET[\'user_id\'] (src' . DIRECTORY_SEPARATOR . 'somefile.php:7:67) -> string-cast (src' . DIRECTORY_SEPARATOR . 'somefile.php:7:67) -> call to A::getAppendedUserId (src' . DIRECTORY_SEPARATOR . 'somefile.php:7:58) -> A::getAppendedUserId#1 (src' . DIRECTORY_SEPARATOR . 'somefile.php:11:59) -> $user_id (src' . DIRECTORY_SEPARATOR . 'somefile.php:11:66) -> concat (src' . DIRECTORY_SEPARATOR . 'somefile.php:12:36) -> A::getAppendedUserId (src' . DIRECTORY_SEPARATOR . 'somefile.php:11:78) -> call to A::deleteUser (src' . DIRECTORY_SEPARATOR . 'somefile.php:7:33) -> A::deleteUser#3 (src' . DIRECTORY_SEPARATOR . 'somefile.php:19:78) -> $userId2 (src' . DIRECTORY_SEPARATOR . 'somefile.php:19:85) -> call to PDO::exec (src' . DIRECTORY_SEPARATOR . 'somefile.php:23:44) -> PDO::exec#1',
            ],
            'taintedInParentLoader' => [
                'code' => '<?php
                    abstract class A {
                        abstract public static function loadPartial(string $sink) : void;

                        public static function loadFull(string $sink) : void {
                            static::loadPartial($sink);
                        }
                    }

                    function getPdo() : PDO {
                        return new PDO("connectionstring");
                    }

                    class AChild extends A {
                        public static function loadPartial(string $sink) : void {
                            getPdo()->exec("select * from foo where bar = " . $sink);
                        }
                    }

                    class AGrandChild extends AChild {}

                    class C {
                        public function foo(string $user_id) : void {
                            AGrandChild::loadFull($user_id);
                        }
                    }

                    (new C)->foo((string) $_GET["user_id"]);',
                'error_message' => 'TaintedSql - src' . DIRECTORY_SEPARATOR . 'somefile.php:16:44 - Detected tainted SQL in path: $_GET (src' . DIRECTORY_SEPARATOR . 'somefile.php:28:43) -> $_GET[\'user_id\'] (src' . DIRECTORY_SEPARATOR . 'somefile.php:28:43) -> string-cast (src' . DIRECTORY_SEPARATOR . 'somefile.php:28:43) -> call to C::foo (src' . DIRECTORY_SEPARATOR . 'somefile.php:28:34) -> C::foo#1 (src' . DIRECTORY_SEPARATOR . 'somefile.php:23:45) -> $user_id (src' . DIRECTORY_SEPARATOR . 'somefile.php:23:52) -> call to AGrandChild::loadFull (src' . DIRECTORY_SEPARATOR . 'somefile.php:24:51) -> A::loadFull#1 (src' . DIRECTORY_SEPARATOR . 'somefile.php:5:57) -> $sink (src' . DIRECTORY_SEPARATOR . 'somefile.php:5:64) -> call to A::loadPartial (src' . DIRECTORY_SEPARATOR . 'somefile.php:6:49) -> dispatch of A::loadPartial#1 -> AChild::loadPartial#1 (src' . DIRECTORY_SEPARATOR . 'somefile.php:15:60) -> $sink (src' . DIRECTORY_SEPARATOR . 'somefile.php:15:67) -> call to PDO::exec (src' . DIRECTORY_SEPARATOR . 'somefile.php:16:44) -> PDO::exec#1',
            ],
            'taintedInputFromProperty' => [
                'code' => '<?php
                    class A {
                        public string $userId;

                        public function __construct() {
                            $this->userId = (string) $_GET["user_id"];
                        }

                        public function getAppendedUserId() : string {
                            return "aaaa" . $this->userId;
                        }

                        public function doDelete(PDO $pdo) : void {
                            $userId = $this->getAppendedUserId();
                            $this->deleteUser($pdo, $userId);
                        }

                        public function deleteUser(PDO $pdo, string $userId) : void {
                            $pdo->exec("delete from users where user_id = " . $userId);
                        }
                    }',
                'error_message' => 'TaintedSql',
            ],
            'taintedInputFromPropertyViaMixin' => [
                'code' => '<?php
                    class A {
                        public string $userId;

                        public function __construct() {
                            $this->userId = (string) $_GET["user_id"];
                        }
                    }

                    /** @mixin A */
                    class B {
                        private A $a;

                        public function __construct(A $a) {
                            $this->a = $a;
                        }

                        public function __get(string $name) {
                            return $this->a->$name;
                        }
                    }

                    class C {
                        private B $b;

                        public function __construct(B $b) {
                            $this->b = $b;
                        }

                        public function getAppendedUserId() : string {
                            return "aaaa" . $this->b->userId;
                        }

                        public function doDelete(PDO $pdo) : void {
                            $userId = $this->getAppendedUserId();
                            $this->deleteUser($pdo, $userId);
                        }

                        public function deleteUser(PDO $pdo, string $userId) : void {
                            $pdo->exec("delete from users where user_id = " . $userId);
                        }
                    }',
                'error_message' => 'TaintedSql',
            ],
            'taintedInputViaStaticFunction' => [
                'code' => '<?php
                    class Utils {
                        public static function shorten(string $str) : string {
                            return $str;
                        }
                    }

                    class A {
                        public function foo() : void {
                            echo(Utils::shorten((string) $_GET["user_id"]));
                        }
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintedInputViaPureStaticFunction' => [
                'code' => '<?php
                    class Utils {
                        /**
                         * @psalm-pure
                         */
                        public static function shorten(string $str) : string {
                            return substr($str, 0, 100);
                        }
                    }

                    class A {
                        public function foo() : void {
                            echo(Utils::shorten((string) $_GET["user_id"]));
                        }
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'untaintedInputViaStaticFunctionWithoutSafePath' => [
                'code' => '<?php
                    class Utils {
                        /**
                         * @psalm-pure
                         */
                        public static function shorten(string $str) : string {
                            return $str;
                        }
                    }

                    class A {
                        public function foo() : void {
                            echo(Utils::shorten((string) $_GET["user_id"]));
                        }

                        public function bar() : void {
                            echo(Utils::shorten("hello"));
                        }
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintedInputFromMagicProperty' => [
                'code' => '<?php
                    /**
                     * @property string $userId
                     */
                    class A {
                        /** @var array<string, string> */
                        private $vars = [];

                        public function __get(string $s) : string {
                            return $this->vars[$s];
                        }

                        public function __set(string $s, string $t) {
                            $this->vars[$s] = $t;
                        }
                    }

                    function getAppendedUserId() : void {
                        $a = new A();
                        $a->userId = (string) $_GET["user_id"];
                        echo $a->userId;
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintOverMixed' => [
                'code' => '<?php
                    /**
                     * @psalm-suppress MixedAssignment
                     * @psalm-suppress MixedArgument
                     */
                    function foo() : void {
                        $a = $_GET["bad"];
                        echo $a;
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintStrConversion' => [
                'code' => '<?php
                    function foo() : void {
                        /** @psalm-suppress PossiblyInvalidCast */
                        $a = strtoupper(strtolower((string) $_GET["bad"]));
                        echo $a;
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintIntoExec' => [
                'code' => '<?php
                    function foo() : void {
                        $a = (string) $_GET["bad"];
                        exec($a);
                    }',
                'error_message' => 'TaintedShell',
            ],
            'taintIntoExecMultipleConcat' => [
                'code' => '<?php
                    function foo() : void {
                        $a = "9" . "a" . "b" . "c" . ((string) $_GET["bad"]) . "d" . "e" . "f";
                        exec($a);
                    }',
                'error_message' => 'TaintedShell',
            ],
            'taintIntoNestedArrayUnnestedSeparately' => [
                'code' => '<?php
                    function foo() : void {
                        $a = [[(string) $_GET["bad"]]];
                        exec($a[0][0]);
                    }',
                'error_message' => 'TaintedShell',
            ],
            'taintIntoArrayAndThenOutAgain' => [
                'code' => '<?php
                    class C {
                        public static function foo() : array {
                            $a = (string) $_GET["bad"];
                            return [$a];
                        }

                        public static function bar() {
                            exec(self::foo()[0]);
                        }
                    }',
                'error_message' => 'TaintedShell',
            ],
            'taintAppendedToArray' => [
                'code' => '<?php
                    class C {
                        public static function foo() : array {
                            $a = [];
                            $a[] = (string) $_GET["bad"];
                            return $a;
                        }

                        public static function bar() {
                            exec(self::foo()[0]);
                        }
                    }',
                'error_message' => 'TaintedShell',
            ],
            'taintOnSubstrCall' => [
                'code' => '<?php
                    class U {
                        /** @psalm-pure */
                        public static function shorten(string $s) : string {
                            return substr($s, 0, 15);
                        }
                    }

                    class V {}

                    class O1 {
                        public string $s;

                        public function __construct() {
                            $this->s = (string) $_GET["FOO"];
                        }
                    }

                    class V1 extends V {
                        public function foo(O1 $o) : void {
                            echo U::shorten($o->s);
                        }
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintOnStrReplaceCallSimple' => [
                'code' => '<?php
                    class U {
                        /** @psalm-pure */
                        public static function shorten(string $s) : string {
                            return str_replace("foo", "bar", $s);
                        }
                    }

                    class V {}

                    class O1 {
                        public string $s;

                        public function __construct() {
                            $this->s = (string) $_GET["FOO"];
                        }
                    }

                    class V1 extends V {
                        public function foo(O1 $o) : void {
                            echo U::shorten($o->s);
                        }
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintOnPregReplaceCall' => [
                'code' => '<?php
                    class U {
                        /** @psalm-pure */
                        public static function shorten(string $s) : string {
                            return preg_replace("/foo/", "bar", $s);
                        }
                    }

                    class V {}

                    class O1 {
                        public string $s;

                        public function __construct() {
                            $this->s = (string) $_GET["FOO"];
                        }
                    }

                    class V1 extends V {
                        public function foo(O1 $o) : void {
                            echo U::shorten($o->s);
                        }
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'IndirectGetAssignment' => [
                'code' => '<?php
                    class InputFilter {
                        public string $name;

                        public function __construct(string $name) {
                            $this->name = $name;
                        }

                        /**
                         * @psalm-taint-specialize
                         */
                        public function getArg(string $method, string $type)
                        {
                            $arg = null;

                            switch ($method) {
                                case "post":
                                    if (isset($_POST[$this->name])) {
                                        $arg = $_POST[$this->name];
                                    }
                                    break;

                                case "get":
                                    if (isset($_GET[$this->name])) {
                                        $arg = $_GET[$this->name];
                                    }
                                    break;
                            }

                            return $this->filterInput($type, $arg);
                        }

                        protected function filterInput(string $type, $arg)
                        {
                            // input is null
                            if ($arg === null) {
                                return null;
                            }

                            // set to null if sanitize clears arg
                            if ($arg === "") {
                                $arg = null;
                            }

                            // type casting
                            if ($arg !== null) {
                                $arg = $this->typeCastInput($type, $arg);
                            }

                            return $arg;
                        }

                        protected function typeCastInput(string $type, $arg) {
                            if ($type === "string") {
                                return (string) $arg;
                            }

                            return null;
                        }
                    }

                    echo (new InputFilter("hello"))->getArg("get", "string");',
                'error_message' => 'TaintedHtml',
            ],
            'taintPropertyPassingObject' => [
                'code' => '<?php
                    /** @psalm-immutable */
                    class User {
                        public string $id;

                        public function __construct(string $userId) {
                            $this->id = $userId;
                        }
                    }

                    class UserUpdater {
                        public static function doDelete(PDO $pdo, User $user) : void {
                            self::deleteUser($pdo, $user->id);
                        }

                        public static function deleteUser(PDO $pdo, string $userId) : void {
                            $pdo->exec("delete from users where user_id = " . $userId);
                        }
                    }

                    $userObj = new User((string) $_GET["user_id"]);
                    UserUpdater::doDelete(new PDO("test"), $userObj);',
                'error_message' => 'TaintedSql',
            ],
            'taintPropertyPassingObjectSettingValueLater' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    class User {
                        public string $id;

                        public function __construct(string $userId) {
                            $this->id = $userId;
                        }

                        public function withId(string $userId) : static {
                            $new = clone $this;
                            $new->id = $userId;
                            return $new;
                        }
                    }

                    function echoId(User $u2) : void {
                        echo $u2->id;
                    }

                    $u = new User("5");
                    $u = $u->withId($_GET["user_id"]);
                    echoId($u);',
                'error_message' => 'TaintedHtml',
            ],
            'taintSpecializedInstancePropertySetByChildClass' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    class StringHolder {
                        public string $x = "";
                    }

                    /** @psalm-taint-specialize */
                    final class ChildStringHolder extends StringHolder {
                        public function __construct(string $x) {
                            $this->x = $x;
                        }
                    }

                    function echoX(StringHolder $holder): void {
                        echo $holder->x;
                    }

                    $holder = new ChildStringHolder($_GET["x"]);
                    echoX($holder);',
                'error_message' => 'TaintedHtml',
            ],
            'taintMethodCallOnNewSpecializedInstance' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    final class Tag {
                        public function __construct(private string $name, private string $content) {}

                        public function render(): string {
                            return "<" . $this->name . ">" . $this->content;
                        }
                    }

                    echo (new Tag("a", (string) $_GET["x"]))->render();',
                'error_message' => 'TaintedHtml',
            ],
            'taintMethodCallOnNewInstance' => [
                'code' => '<?php
                    final class Tag {
                        public function __construct(private string $name, private string $content) {}

                        public function render(): string {
                            return "<" . $this->name . ">" . $this->content;
                        }
                    }

                    echo (new Tag("a", (string) $_GET["x"]))->render();',
                'error_message' => 'TaintedHtml',
            ],
            'taintMethodCallOnSpecializedInstanceInVariableNextToANewOne' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    final class Tag {
                        public function __construct(private string $name, private string $content) {}

                        public function render(): string {
                            return "<" . $this->name . ">" . $this->content;
                        }
                    }

                    $html = (new Tag("a", "safe"))->render();
                    $tag = new Tag("b", (string) $_GET["x"]);
                    echo $tag->render();',
                'error_message' => 'TaintedHtml',
            ],
            'taintThroughInheritedMethodOfSpecializedClass' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    class StringHolder {
                        protected string $value = "";

                        public function __construct(string $value) {
                            $this->value = $value;
                        }

                        public function getValue(): string {
                            return $this->value;
                        }
                    }

                    /** @psalm-taint-specialize */
                    final class ChildStringHolder extends StringHolder {}

                    $tainted = new ChildStringHolder((string) $_GET["x"]);
                    echo $tainted->getValue();',
                'error_message' => 'TaintedHtml',
            ],
            'ImplodeExplode' => [
                'code' => '<?php
                    $a = $_GET["name"];
                    $b = explode(" ", $a);
                    $c = implode(" ", $b);
                    echo $c;',
                'error_message' => 'TaintedHtml',
            ],
            'ImplodeIndirect' => [
                'code' => '<?php
                    /** @var array $unsafe */
                    $unsafe = $_GET[\'unsafe\'];
                    echo implode(" ", $unsafe);',
                'error_message' => 'TaintedHtml',
            ],
            'taintImplodedArrayCastAsMixed' => [
                'code' => '<?php
                    function joinedAsMixed(): mixed {
                        $parts = [];
                        $parts[] = (string) $_GET["x"];
                        return implode(" ", $parts);
                    }

                    echo (string) joinedAsMixed();',
                'error_message' => 'TaintedHtml',
            ],
            'taintJoinedArrayConcatenatedAsMixed' => [
                'code' => '<?php
                    function joinedAsMixed(): mixed {
                        $parts = [];
                        $parts[] = (string) $_GET["x"];
                        return join(" ", $parts);
                    }

                    /** @psalm-suppress MixedOperand */
                    echo "<b>" . joinedAsMixed();',
                'error_message' => 'TaintedHtml',
            ],
            'taintThroughPregReplaceCallback' => [
                'code' => '<?php
                    $a = $_GET["bad"];

                    $b = preg_replace_callback(
                        \'/foo/\',
                        function (array $matches) : string {
                            return $matches[1];
                        },
                        $a
                    );

                    echo $b;',
                'error_message' => 'TaintedHtml',
            ],
            'taintedFunctionWithNoTypes' => [
                'code' => '<?php
                    function rawinput() {
                        return $_GET[\'rawinput\'];
                    }

                    echo rawinput();',
                'error_message' => 'TaintedHtml',
            ],
            'taintedStaticCallWithNoTypes' => [
                'code' => '<?php
                    class A {
                        public static function rawinput() {
                            return $_GET[\'rawinput\'];
                        }
                    }

                    echo A::rawinput();',
                'error_message' => 'TaintedHtml',
            ],
            'taintedInstanceCallWithNoTypes' => [
                'code' => '<?php
                    class A {
                        public function rawinput() {
                            return $_GET[\'rawinput\'];
                        }
                    }

                    echo (new A())->rawinput();',
                'error_message' => 'TaintedHtml',
            ],
            'taintStringObtainedUsingStrval' => [
                'code' => '<?php
                    $unsafe = strval($_GET[\'unsafe\']);
                    echo $unsafe;',
                'error_message' => 'TaintedHtml',
            ],
            'taintStringObtainedUsingSprintf' => [
                'code' => '<?php
                    $unsafe = sprintf("%s", strval($_GET[\'unsafe\']));
                    echo $unsafe;',
                'error_message' => 'TaintedHtml',
            ],
            'encapsulatedString' => [
                'code' => '<?php
                    $unsafe = $_GET[\'unsafe\'];
                    echo "$unsafe";',
                'error_message' => 'TaintedHtml',
            ],
            'encapsulatedToStringMagic' => [
                'code' => '<?php
                    class MyClass {
                        public function __toString() {
                            return $_GET["blah"];
                        }
                    }
                    $unsafe = new MyClass();
                    echo "unsafe: $unsafe";',
                'error_message' => 'TaintedHtml',
            ],
            'castToStringMagic' => [
                'code' => '<?php
                    class MyClass {
                        public function __toString() {
                            return $_GET["blah"];
                        }
                    }
                    $unsafe = new MyClass();
                    echo $unsafe;',
                'error_message' => 'TaintedHtml',
            ],
            'castToStringViaArgument' => [
                'code' => '<?php
                    class MyClass {
                        public function __toString() {
                            return $_GET["blah"];
                        }
                    }

                    function doesEcho(string $s): void {
                        echo $s;
                    }

                    $unsafe = new MyClass();

                    doesEcho($unsafe);',
                'error_message' => 'TaintedHtml',
            ],
            'toStringTaintInSubclass' => [
                'code' => '<?php // --taint-analysis
                    class TaintedBaseClass {
                        /** @psalm-taint-source input */
                        public function __toString() {
                            return "x";
                        }
                    }
                    class TaintedSubclass extends TaintedBaseClass {}
                    $x = new TaintedSubclass();
                    echo "Caught: $x\n";',
                'error_message' => 'TaintedHtml',
            ],
            'implicitToStringMagic' => [
                'code' => '<?php
                    class MyClass {
                        public function __toString() {
                            return $_GET["blah"];
                        }
                    }
                    $unsafe = new MyClass();
                    echo $unsafe;',
                'error_message' => 'TaintedHtml',
            ],
            'namespacedFunction' => [
                'code' => '<?php
                    namespace ns;

                    function identity(string $s) : string {
                        return $s;
                    }

                    echo identity($_GET[\'userinput\']);',
                'error_message' => 'TaintedHtml',
            ],
            'print' => [
                'code' => '<?php
                    print($_GET["name"]);',
                'error_message' => 'TaintedHtml - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:21 - Detected tainted HTML in path: $_GET (src' . DIRECTORY_SEPARATOR . 'somefile.php:2:27) -> $_GET[\'name\'] (src' . DIRECTORY_SEPARATOR . 'somefile.php:2:27) -> call to print (src' . DIRECTORY_SEPARATOR . 'somefile.php:2:27) -> builtin print#1',
            ],
            'printf' => [
                'code' => '<?php
                    printf($_GET["name"]);',
                'error_message' => 'TaintedHtml - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:28 - Detected tainted HTML in path: $_GET (src' . DIRECTORY_SEPARATOR . 'somefile.php:2:28) -> $_GET[\'name\'] (src' . DIRECTORY_SEPARATOR . 'somefile.php:2:28) -> call to printf (src' . DIRECTORY_SEPARATOR . 'somefile.php:2:28) -> printf#1',
            ],
            'print_r' => [
                'code' => '<?php
                    print_r($_GET["name"]);',
                'error_message' => 'TaintedHtml - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:29 - Detected tainted HTML in path: $_GET (src' . DIRECTORY_SEPARATOR . 'somefile.php:2:29) -> $_GET[\'name\'] (src' . DIRECTORY_SEPARATOR . 'somefile.php:2:29) -> call to print_r (src' . DIRECTORY_SEPARATOR . 'somefile.php:2:29) -> print_r#1',
            ],
            'var_dump' => [
                'code' => '<?php
                    var_dump($_GET["name"]);',
                'error_message' => 'TaintedHtml - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:30 - Detected tainted HTML in path: $_GET (src' . DIRECTORY_SEPARATOR . 'somefile.php:2:30) -> $_GET[\'name\'] (src' . DIRECTORY_SEPARATOR . 'somefile.php:2:30) -> call to var_dump (src' . DIRECTORY_SEPARATOR . 'somefile.php:2:30) -> var_dump#1',
            ],
            'var_export' => [
                'code' => '<?php
                    var_export($_GET["name"]);',
                'error_message' => 'TaintedHtml - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:32 - Detected tainted HTML in path: $_GET (src' . DIRECTORY_SEPARATOR . 'somefile.php:2:32) -> $_GET[\'name\'] (src' . DIRECTORY_SEPARATOR . 'somefile.php:2:32) -> call to var_export (src' . DIRECTORY_SEPARATOR . 'somefile.php:2:32) -> var_export#1',
            ],
            'unpackArgs' => [
                'code' => '<?php
                    function test(...$args) {
                        echo $args[0];
                    }

                    /**
                     * @psalm-taint-source input
                     */
                    function getQueryParam() {}

                    // cannot use $_GET, see #8477
                    $foo = getQueryParam();
                    test(...$foo);',
                'error_message' => 'TaintedHtml',
            ],
            'foreachArg' => [
                'code' => '<?php
                    $a = $_GET["bad"];

                    foreach ($a as $arg) {
                        echo $arg;
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'magicPropertyType' => [
                'code' => '<?php
                    class Magic {
                        private $params = [];

                        public function __get(string $a) {
                            return $this->params[$a];
                        }

                        public function __set(string $a, $value) {
                            $this->params[$a] = $value;
                        }
                    }

                    $m = new Magic();
                    $m->taint = $_GET["input"];
                    echo $m->taint;',
                'error_message' => 'TaintedHtml',
            ],
            'taintNestedArrayWithOffsetAccessedInForeach' => [
                'code' => '<?php
                    $a = [];
                    $a[0] = ["a" => $_GET["name"], "b" => "foo"];

                    foreach ($a as $m) {
                        echo $m["a"];
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintNestedArrayWithOffsetAccessedExplicitly' => [
                'code' => '<?php
                    $a = [];
                    $a[] = ["a" => $_GET["name"], "b" => "foo"];

                    echo $a[0]["a"];',
                'error_message' => 'TaintedHtml',
            ],
            'taintThroughArrayMapExplicitClosure' => [
                'code' => '<?php
                    $get = array_map(function($str) { return trim($str);}, $_GET);
                    echo $get["test"];',
                'error_message' => 'TaintedHtml',
            ],
            'taintArrayWithStringKeys' => [
                'code' => '<?php
                    /** @var array<string, int> $counts */
                    $counts = $_GET["counts"];
                    echo implode(",", array_keys($counts));
                    foreach ($counts as $key => $count) {
                        echo $key;
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintThroughArrayMapExplicitTypedClosure' => [
                'code' => '<?php
                    $get = array_map(function(string $str) : string { return trim($str);}, $_GET);
                    echo $get["test"];',
                'error_message' => 'TaintedHtml',
            ],
            'taintThroughArrayMapExplicitArrowFunction' => [
                'code' => '<?php
                    $get = array_map(fn($str) => trim($str), $_GET);
                    echo $get["test"];',
                'error_message' => 'TaintedHtml',
            ],
            'taintThroughCallMapOnlyFunction' => [
                'code' => '<?php
                    echo mb_substr($_GET["x"], 1);',
                'error_message' => 'TaintedHtml',
            ],
            'taintThroughCallMapOnlyFunctionPath' => [
                'code' => '<?php
                    echo basename((string) $_GET["x"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintThroughCallMapOnlyFunctionWithParamsProvider' => [
                'code' => '<?php
                    /** @var array<string, string> $a */
                    $a = $_GET["a"];
                    echo implode(",", array_filter($a));',
                'error_message' => 'TaintedHtml',
            ],
            'taintThroughCallMapOnlyMethod' => [
                'code' => '<?php
                    echo (new DateTime())->format((string) $_GET["format"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintThroughStubbedArrayFunction' => [
                'code' => '<?php
                    /** @var array<string, string> $a */
                    $a = $_GET["a"];
                    echo array_values($a)[0];',
                'error_message' => 'TaintedHtml',
            ],
            'taintThroughStubbedVariadicArrayFunction' => [
                'code' => '<?php
                    /** @var array<string, string> $a */
                    $a = $_GET["a"];
                    echo implode(",", array_merge(["safe"], $a));',
                'error_message' => 'TaintedHtml',
            ],
            'taintThroughArrayKeys' => [
                'code' => '<?php
                    echo implode(",", array_keys($_GET));',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheKeysOfTheElementsArrayMapReturns' => [
                'code' => '<?php
                    $links = array_map(
                        fn(string $title): array => ["title" => $title, "url" => "/"],
                        ["a" => (string) $_GET["title"]],
                    );
                    foreach ($links as $link) {
                        echo $link["title"];
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheKeyOfTheElementArrayMapReturns' => [
                'code' => '<?php
                    $query = ["tail" => (string) $_GET["tail"], "city" => "msk"];
                    $params = array_map(fn(string $value): string => $value, $query);
                    echo $params["tail"];',
                'error_message' => 'TaintedHtml',
            ],
            'taintWhatAnArrayMapClosureKeepsByReferenceUnderTheNextKey' => [
                'code' => '<?php
                    $query = ["tail" => (string) $_GET["tail"], "city" => "msk"];
                    $previous = "";
                    $params = array_map(
                        static function (string $value) use (&$previous): string {
                            $result = $previous;
                            $previous = $value;
                            return $result;
                        },
                        $query,
                    );
                    echo $params["city"];',
                'error_message' => 'TaintedHtml',
            ],
            'taintWhatAnArrayMapClosureKeepsInACapturedArrayAccessObjectUnderTheNextKey' => [
                'code' => '<?php
                    $query = ["tail" => (string) $_GET["tail"], "city" => "msk"];
                    $seen = new ArrayObject();
                    $params = array_map(
                        static function (string $value) use ($seen): string {
                            $seen[] = $value;
                            return $seen[0] ?? "";
                        },
                        $query,
                    );
                    echo $params["city"];',
                'error_message' => 'TaintedHtml',
            ],
            'taintWhatAnArrayMapClosureKeepsThroughMethodsOfACapturedObjectUnderTheNextKey' => [
                'code' => '<?php
                    $query = ["tail" => (string) $_GET["tail"], "city" => "msk"];
                    $seen = new ArrayObject();
                    $params = array_map(
                        static function (string $value) use ($seen): string {
                            $seen->append($value);
                            return $seen->offsetGet(0);
                        },
                        $query,
                    );
                    echo $params["city"];',
                'error_message' => 'TaintedHtml',
            ],
            'taintWhatAnArrayMapArrowFunctionKeepsInACapturedObjectUnderTheNextKey' => [
                'code' => '<?php
                    $query = ["tail" => (string) $_GET["tail"], "city" => "msk"];
                    $seen = new ArrayObject();
                    $params = array_map(fn(string $value): string => [$seen->append($value), $seen[0]][1], $query);
                    echo $params["city"];',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheElementsOfAnArrayMapOverTwoArraysUnderAnyKey' => [
                'code' => '<?php
                    $query = ["tail" => (string) $_GET["tail"], "city" => "msk"];
                    $pairs = array_map(fn(string $a, string $b): string => $a . $b, $query, $query);
                    echo $pairs[1];',
                'error_message' => 'TaintedHtml',
            ],
            'taintThroughArrayMapClosureIntoWholeArray' => [
                'code' => '<?php
                    /** @var array<string, string> $a */
                    $a = $_GET["a"];
                    echo implode(",", array_map(fn($str) => $str, $a));',
                'error_message' => 'TaintedHtml',
            ],
            'taintHtmlThroughShellEscape' => [
                'code' => '<?php
                    echo escapeshellarg((string) $_GET["x"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintShellThroughEscapeShellCmd' => [
                'code' => '<?php
                    exec(escapeshellcmd("curl " . (string) $_GET["x"]));',
                'error_message' => 'TaintedShell',
            ],
            'taintThroughBuiltinStaticMethod' => [
                'code' => '<?php
                    echo (string) Normalizer::normalize((string) $_GET["x"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintThroughNamedArgumentsOfCallMapOnlyFunction' => [
                'code' => '<?php
                    echo mb_substr(start: 1, string: (string) $_GET["x"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintThroughNamedArgumentOfAParameterPastTheArguments' => [
                'code' => '<?php
                    echo http_build_query(data: ["a" => "b"], arg_separator: (string) $_GET["x"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintHeaderThroughTheNumericPrefixOfHttpBuildQuery' => [
                'code' => '<?php
                    header("X: " . http_build_query([1 => "a"], (string) $_GET["x"]));',
                'error_message' => 'TaintedHeader',
            ],
            'taintWhatStrtokReturnsFromTheStringOfAnEarlierCall' => [
                'code' => '<?php
                    strtok((string) $_GET["x"], ",");
                    echo (string) strtok(",");',
                'error_message' => 'TaintedHtml',
            ],
            'taintThroughArrayMapUntypedClosureOverMixedArray' => [
                'code' => '<?php
                    /** @psalm-suppress MissingClosureParamType, MissingClosureReturnType */
                    echo implode(",", array_map(fn($s) => $s, $_GET["a"]));',
                'error_message' => 'TaintedHtml',
            ],
            'taintThroughArrayMapUntypedClosureIntoItsElements' => [
                'code' => '<?php
                    /** @psalm-suppress MissingClosureParamType, MissingClosureReturnType */
                    foreach (array_map(fn($s) => $s, $_GET["a"]) as $value) {
                        echo (string) $value;
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintPromotedProperty' => [
                'code' => '<?php
                    final class Holder {
                        public function __construct(private string $value) {}

                        public function getValue(): string {
                            return $this->value;
                        }
                    }

                    echo (new Holder((string) $_GET["x"]))->getValue();',
                'error_message' => 'TaintedHtml',
            ],
            'taintPublicPromotedPropertyOfSpecializedClass' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    final class Holder {
                        public function __construct(public string $value) {}
                    }

                    $holder = new Holder((string) $_GET["x"]);
                    echo $holder->value;',
                'error_message' => 'TaintedHtml',
            ],
            'taintCallingAFunctionNamedByInput' => [
                'code' => '<?php
                    $name = (string) $_GET["function"];
                    $name();',
                'error_message' => 'TaintedCallable',
            ],
            'taintCallingAnOffsetOverwrittenThroughAnUnknownKey' => [
                'code' => '<?php
                    /** @param array<string, string> $a */
                    function f(array $a, string $k): void {
                        $a["x"] = function (): void {};
                        $a[$k] = (string) $_GET["v"];
                        $a["x"]();
                    }',
                'error_message' => 'TaintedCallable',
            ],
            'taintCallingAnUnknownOffsetOverwrittenThroughALiteralKey' => [
                'code' => '<?php
                    /** @param array<string, string> $a */
                    function f(array $a, string $k): void {
                        $a[$k] = function (): void {};
                        $a["x"] = (string) $_GET["v"];
                        $a[$k]();
                    }',
                'error_message' => 'TaintedCallable',
            ],
            'taintCallingANestedOffsetOverwrittenThroughAnUnknownKey' => [
                'code' => '<?php
                    /** @param array<string, array<string, string>> $a */
                    function f(array $a, string $k): void {
                        $a["x"]["y"] = function (): void {};
                        $a["x"][$k] = (string) $_GET["v"];
                        $a["x"]["y"]();
                    }',
                'error_message' => 'TaintedCallable',
            ],
            'taintCallingASuperGlobalOffsetOverwrittenThroughAnUnknownKey' => [
                'code' => '<?php
                    $_GET["x"] = function (): void {};
                    $_GET[(string) $_POST["k"]] = $_POST["v"];
                    $_GET["x"]();',
                'error_message' => 'TaintedCallable',
            ],
            'taintPrintRReturningItsOutput' => [
                'code' => '<?php
                    echo print_r($_GET["x"], true);',
                'error_message' => 'TaintedHtml',
            ],
            'taintVarExportReturningItsOutput' => [
                'code' => '<?php
                    echo var_export($_GET["x"], true);',
                'error_message' => 'TaintedHtml',
            ],
            'taintPrintROutput' => [
                'code' => '<?php
                    print_r($_GET["x"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintClosureFromCallable' => [
                'code' => '<?php
                    $name = $_GET["f"];
                    if (is_callable($name)) {
                        $closure = Closure::fromCallable($name);
                        $closure();
                    }',
                'error_message' => 'TaintedCallable',
            ],
            'taintServerRequestHeader' => [
                'code' => '<?php
                    echo $_SERVER["HTTP_USER_AGENT"] ?? "";',
                'error_message' => 'TaintedHtml',
            ],
            'taintServerRequestMethod' => [
                'code' => '<?php
                    echo $_SERVER["REQUEST_METHOD"] ?? "";',
                'error_message' => 'TaintedHtml',
            ],
            'taintServerRedirectedRequestHeader' => [
                'code' => '<?php
                    echo $_SERVER["REDIRECT_REDIRECT_HTTP_USER_AGENT"] ?? "";',
                'error_message' => 'TaintedHtml',
            ],
            'taintServerArgvFromQueryString' => [
                'code' => '<?php
                    foreach ($_SERVER["argv"] ?? [] as $arg) {
                        echo $arg;
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintArgvAtFileScope' => [
                'code' => '<?php
                    echo $argv[1] ?? "";',
                'error_message' => 'TaintedHtml',
            ],
            'taintArgvBoundWithGlobal' => [
                'code' => '<?php
                    function run(): void {
                        global $argv;
                        echo $argv[1] ?? "";
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintArgvReadThroughGlobals' => [
                'code' => '<?php
                    function run(): void {
                        echo (string) ($GLOBALS["argv"][1] ?? "");
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintServerPhpSelf' => [
                'code' => '<?php
                    echo $_SERVER["PHP_SELF"] ?? "";',
                'error_message' => 'TaintedHtml',
            ],
            'taintServerDynamicKey' => [
                'code' => '<?php
                    /** @var string $key */
                    $key = $GLOBALS["key"];
                    echo (string) ($_SERVER[$key] ?? "");',
                'error_message' => 'TaintedHtml',
            ],
            'taintUploadedFileName' => [
                'code' => '<?php
                    echo $_FILES["upload"]["name"];',
                'error_message' => 'TaintedHtml',
            ],
            'taintUploadedFileNameThroughVariable' => [
                'code' => '<?php
                    foreach ($_FILES as $file) {
                        echo $file["name"];
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintUploadedFileType' => [
                'code' => '<?php
                    echo $_FILES["upload"]["type"];',
                'error_message' => 'TaintedHtml',
            ],
            'taintThroughArrayMapImplicitFunctionCall' => [
                'code' => '<?php
                    $a = ["test" => $_GET["name"]];
                    $get = array_map("trim", $a);
                    echo $get["test"];',
                'error_message' => 'TaintedHtml',
            ],
            'taintFilterVarCallback' => [
                'code' => '<?php
                    $get = filter_var($_GET, FILTER_CALLBACK, ["options" => "trim"]);

                    echo $get["test"];',
                'error_message' => 'TaintedHtml',
            ],
            'taintAfterReconciledType' => [
                'code' => '<?php
                    $input = $_GET[\'input\'];
                    if (is_string($input)) {
                        echo "$input";
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintExit' => [
                'code' => '<?php
                    if (rand(0, 1)) {
                        /** @psalm-suppress PossiblyInvalidArgument */
                        exit($_GET[\'a\']);
                    } else {
                        /** @psalm-suppress PossiblyInvalidArgument */
                        die($_GET[\'b\']);
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintSpecializedMethod' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    class Unsafe {
                        public function isUnsafe() {
                            return $_GET["unsafe"];
                        }
                    }
                    $a = new Unsafe();
                    echo $a->isUnsafe();',
                'error_message' => 'TaintedHtml',
            ],
            'taintInferredPureFunction' => [
                'code' => '<?php
                    function wrap(string $s): string {
                        return id($s);
                    }

                    function id(string $s): string {
                        return $s;
                    }

                    $notEchoed = wrap("safe");
                    echo wrap($_GET["a"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintNestedCallsOfPureFunction' => [
                'code' => '<?php
                    /** @psalm-pure */
                    function id(string $s): string {
                        return $s;
                    }

                    echo id(id($_GET["a"]));',
                'error_message' => 'TaintedHtml',
            ],
            'taintSuccessiveCallsOfPureFunction' => [
                'code' => '<?php
                    /** @psalm-pure */
                    function id(string $s): string {
                        return $s;
                    }

                    $a = id($_GET["a"]);
                    $b = id($a);
                    echo $b;',
                'error_message' => 'TaintedHtml',
            ],
            'taintNestedCallsOfInferredPureFunction' => [
                'code' => '<?php
                    function id(string $s): string {
                        return $s;
                    }

                    echo id(id($_GET["a"]));',
                'error_message' => 'TaintedHtml',
            ],
            'taintNestedCallsOfPureFunctionCallingPureFunction' => [
                'code' => '<?php
                    /** @psalm-pure */
                    function wrap(string $s): string {
                        return id($s);
                    }

                    /** @psalm-pure */
                    function id(string $s): string {
                        return $s;
                    }

                    echo wrap(wrap($_GET["a"]));',
                'error_message' => 'TaintedHtml',
            ],
            'taintSuccessiveCallsOfInferredPureFunction' => [
                'code' => '<?php
                    function id(string $s): string {
                        return $s;
                    }

                    $a = id($_GET["a"]);
                    $b = id($a);
                    echo $b;',
                'error_message' => 'TaintedHtml',
            ],
            'taintNestedCallsOfInferredPureFunctionCallingInferredPureFunction' => [
                'code' => '<?php
                    function wrap(string $s): string {
                        return id($s);
                    }

                    function id(string $s): string {
                        return $s;
                    }

                    echo wrap(wrap($_GET["a"]));',
                'error_message' => 'TaintedHtml',
            ],
            'taintNestedCallsOfInferredPureFinalClassMethod' => [
                'code' => '<?php
                    final class Str {
                        public function id(string $s): string {
                            return $s;
                        }
                    }

                    $str = new Str();
                    echo $str->id($str->id($_GET["a"]));',
                'error_message' => 'TaintedHtml',
            ],
            'taintNestedCallsOfInferredPureStaticMethod' => [
                'code' => '<?php
                    final class Str {
                        public static function id(string $s): string {
                            return $s;
                        }
                    }

                    echo Str::id(Str::id($_GET["a"]));',
                'error_message' => 'TaintedHtml',
            ],
            'taintNestedCallsOfInheritedInferredPureFinalMethod' => [
                'code' => '<?php
                    class Base {
                        final public function id(string $s): string {
                            return $s;
                        }
                    }

                    class Child extends Base {}

                    $c = new Child();
                    echo $c->id($c->id($_GET["a"]));',
                'error_message' => 'TaintedHtml',
            ],
            'taintNestedCallsOfInferredPureFunctionThroughCallableString' => [
                'code' => '<?php
                    function id(string $s): string {
                        return $s;
                    }

                    $f = "id";
                    echo $f($f($_GET["a"]));',
                'error_message' => 'TaintedHtml',
            ],
            'taintNestedCallsOfMutuallyRecursiveInferredPureFunctions' => [
                'code' => '<?php
                    function even(string $s, int $n): string {
                        return $n > 0 ? odd($s, $n - 1) : $s;
                    }

                    function odd(string $s, int $n): string {
                        return $n > 0 ? even($s, $n - 1) : $s;
                    }

                    echo even(even($_GET["a"], 2), 2);',
                'error_message' => 'TaintedHtml',
            ],
            'taintNestedCallsOfTaintSpecializedFunction' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    function spec(string $s): string {
                        return $s;
                    }

                    echo spec(spec($_GET["a"]));',
                'error_message' => 'TaintedHtml',
            ],
            'taintLateArrayThroughSpecializedFunction' => [
                'code' => '<?php
                    /** @psalm-pure */
                    function getV(array $a): mixed {
                        return $a["v"];
                    }

                    $notEchoed = getV(["k" => $_GET["a"]]);
                    $b1 = $_GET["b"];
                    $b2 = $b1;
                    $b3 = $b2;
                    echo (string) getV(["v" => $b3]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintThroughNestedFetchOfMixedArrayInSpecializedFunction' => [
                'code' => '<?php
                    /** @psalm-pure */
                    function get2(array $a): mixed {
                        return $a["a"]["b"];
                    }

                    echo (string) get2(["a" => ["b" => $_GET["a"]]]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintThroughSpreadAlongOtherItems' => [
                'code' => '<?php
                    $a = [$_GET["a"]];
                    $b = ["safe", ...$a];
                    echo (string) $b[1];',
                'error_message' => 'TaintedHtml',
            ],
            'taintThroughSpreadOfArrayWithoutTaintedItems' => [
                'code' => '<?php
                    /** @param list<string> $a */
                    function spread(array $a): void {
                        $b = [...$a];
                        echo $b[0];
                    }

                    /** @psalm-suppress MixedArgumentTypeCoercion */
                    spread($_GET["a"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintThroughSpreadOfCallResult' => [
                'code' => '<?php
                    $b = ["safe", ...explode(",", (string) $_GET["a"])];
                    echo $b[1];',
                'error_message' => 'TaintedHtml',
            ],
            'taintThroughForeachOverSpread' => [
                'code' => '<?php
                    foreach ([...explode(",", (string) $_GET["a"])] as $v) {
                        echo $v;
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintThroughReferenceToArrayItem' => [
                'code' => '<?php
                    $a = ["k" => "safe"];
                    $r = &$a["k"];
                    $r = (string) $_GET["a"];
                    echo $a["k"];',
                'error_message' => 'TaintedHtml',
            ],
            'taintThroughReferenceToNestedArrayItem' => [
                'code' => '<?php
                    $a = ["k" => ["j" => "safe"]];
                    $r = &$a["k"]["j"];
                    $r = (string) $_GET["a"];
                    echo $a["k"]["j"];',
                'error_message' => 'TaintedHtml',
            ],
            'taintThroughReferenceToItemOfParam' => [
                'code' => '<?php
                    function f(array $a): void {
                        $r = &$a["k"];
                        $r = (string) $_GET["a"];
                        echo (string) $a["k"];
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintThroughReferenceToItemOfStaticVariable' => [
                'code' => '<?php
                    function f(): void {
                        static $a = ["k" => "safe"];
                        $r = &$a["k"];
                        $r = (string) $_GET["a"];
                        echo $a["k"];
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintThroughReferenceToItemOfGlobalVariable' => [
                'code' => '<?php
                    function f(): void {
                        global $a;
                        $a = ["k" => "safe"];
                        $r = &$a["k"];
                        $r = (string) $_GET["a"];
                        echo $a["k"];
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintThroughObjectCast' => [
                'code' => '<?php
                    $o = (object) $_GET;
                    echo (string) json_encode($o);',
                'error_message' => 'TaintedHtml',
            ],
            'taintNestedCallsOnAnotherInstanceOfSpecializedClass' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    final class Box {
                        public function take(string $s): string {
                            return $s;
                        }
                    }

                    $a = new Box();
                    $b = new Box();
                    $notEchoed = $a->take($_GET["a"]);
                    echo $b->take($b->take($_GET["b"]));',
                'error_message' => 'TaintedHtml',
            ],
            'taintArrayNestedDeeperThanFourLevelsInSpecializedFunction' => [
                'code' => '<?php
                    /** @psalm-pure */
                    function get5(array $a): mixed {
                        return $a["a"]["b"]["c"]["d"]["e"];
                    }

                    $notEchoed = get5(["a" => ["b" => ["c" => ["d" => ["x" => $_GET["a"]]]]]]);
                    echo (string) get5(["a" => ["b" => ["c" => ["d" => ["e" => $_GET["b"]]]]]]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintThroughRecursionWrappingItsArgumentDeeperOnEveryCall' => [
                'code' => '<?php
                    /** @psalm-pure */
                    function wrap(mixed $a, int $n): mixed {
                        return $n > 0 ? wrap(["k" => $a], $n - 1) : $a;
                    }

                    echo (string) wrap($_GET["a"], 3);',
                'error_message' => 'TaintedHtml',
            ],
            'taintThroughRecursionFetchingDeeperThanFourLevels' => [
                // A recursive entry is keyed on its 4 innermost open array assignments: the deeper recursive call shares the walk of the shallower one
                'code' => '<?php
                    /** @psalm-pure */
                    function dig(mixed $a, int $n): mixed {
                        if ($n > 0) {
                            return dig(["w" => $a], $n - 1);
                        }
                        return is_array($a) ? $a["w"]["w"]["w"]["w"]["w"]["x"] : null;
                    }

                    echo (string) dig(["x" => $_GET["a"]], 5);',
                'error_message' => 'TaintedHtml',
            ],
            'taintThroughRecursionDifferingDeeperThanFourLevels' => [
                // A recursive entry is keyed on its 4 innermost open array assignments: the second call shares the walk of the first, wrapped around another key
                'code' => '<?php
                    /** @psalm-pure */
                    function dig(mixed $a, int $n): mixed {
                        if ($n > 0) {
                            return dig(["w" => $a], $n - 1);
                        }
                        return is_array($a) ? $a["w"]["w"]["w"]["w"]["x"] : null;
                    }

                    $notEchoed = dig(["y" => $_GET["a"]], 4);
                    echo (string) dig(["x" => $_GET["b"]], 4);',
                'error_message' => 'TaintedHtml',
            ],
            'SKIPPED-taintLateArrayThroughImpureFunction' => [
                // The visited guard ignores open array assignments: a later flow with another array shape is pruned at a node shared with an earlier one
                'code' => '<?php
                    function pass(array $a): array {
                        $GLOBALS["n"] = 1;
                        return $a;
                    }

                    $notEchoed = pass(["k" => $_GET["a"]]);
                    $b1 = $_GET["b"];
                    $b2 = $b1;
                    $b3 = $b2;
                    $y = pass(["v" => $b3]);
                    echo (string) $y["v"];',
                'error_message' => 'TaintedHtml',
            ],
            'SKIPPED-taintLateArrayThroughStaticProperty' => [
                // The visited guard ignores open array assignments: a later flow with another array shape is pruned at a node shared with an earlier one
                'code' => '<?php
                    final class C {
                        /** @var array<string, mixed> */
                        public static array $p = [];
                    }

                    C::$p = ["k" => $_GET["a"]];
                    $b1 = $_GET["b"];
                    $b2 = $b1;
                    $b3 = $b2;
                    C::$p = ["v" => $b3];
                    echo (string) C::$p["v"];',
                'error_message' => 'TaintedHtml',
            ],
            'SKIPPED-taintLateArrayThroughInstanceProperty' => [
                // The visited guard ignores open array assignments: a later flow with another array shape is pruned at a node shared with an earlier one
                'code' => '<?php
                    final class C {
                        /** @var array<string, mixed> */
                        public array $p = [];
                    }

                    $c = new C();
                    $c->p = ["k" => $_GET["a"]];
                    $b1 = $_GET["b"];
                    $b2 = $b1;
                    $b3 = $b2;
                    $c->p = ["v" => $b3];
                    echo (string) $c->p["v"];',
                'error_message' => 'TaintedHtml',
            ],
            'specializedInstanceCannotChange' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    final class Box {
                        private string $last = "";

                        public function take(string $s): string {
                            $this->last = $s;
                            return $s;
                        }

                        public function last(): string {
                            return $this->last;
                        }
                    }

                    $a = new Box();
                    $unused = $a->take($_GET["a"]);
                    echo $a->last();',
                'error_message' => 'InaccessibleProperty',
            ],
            'specializedInstanceCannotChangeThroughPrivateMutation' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    final class Box {
                        /**
                         * @psalm-readonly-allow-private-mutation
                         */
                        public string $last = "";

                        public function take(string $s): void {
                            $this->last = $s;
                        }
                    }',
                'error_message' => 'InaccessibleProperty',
            ],
            'specializedInstanceCannotBeChangedOutsideOfIt' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    final class Holder {
                        public string $x = "";
                    }

                    $a = new Holder();
                    $b = $a;
                    $b->x = $_GET["x"];
                    echo $a->x;',
                'error_message' => 'InaccessibleProperty',
            ],
            'taintPropertyOfObjectStoredInSpecializedInstanceChangedLater' => [
                'code' => '<?php
                    final class A {
                        public string $x = "";
                    }

                    /** @psalm-taint-specialize */
                    final class B {
                        public function __construct(public A $a) {}
                    }

                    $a = new A();
                    $b = new B($a);
                    $a->x = (string) $_GET["x"];
                    echo $b->a->x;',
                'error_message' => 'TaintedHtml',
            ],
            'taintPropertyOfObjectStoredInSpecializedInstanceChangedLaterThroughMethod' => [
                'code' => '<?php
                    final class A {
                        public string $x = "";
                    }

                    /** @psalm-taint-specialize */
                    final class B {
                        public function __construct(private A $a) {}

                        public function getX(): string {
                            return $this->a->x;
                        }
                    }

                    $a = new A();
                    $b = new B($a);
                    $a->x = (string) $_GET["x"];
                    echo $b->getX();',
                'error_message' => 'TaintedHtml',
            ],
            'specializedInstanceStoredInAnotherCannotBeChanged' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    final class A {
                        public string $x = "";
                    }

                    /** @psalm-taint-specialize */
                    final class B {
                        public function __construct(public A $a) {}
                    }

                    $a = new A();
                    $b = new B($a);
                    $a->x = (string) $_GET["x"];
                    echo $b->a->x;',
                'error_message' => 'InaccessibleProperty',
            ],
            'taintSpecializedInstanceStoredInAnotherThroughMethods' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    final class A {
                        public function __construct(public string $x) {}

                        public function getX(): string {
                            return $this->x;
                        }
                    }

                    /** @psalm-taint-specialize */
                    final class B {
                        public function __construct(public A $a) {}

                        public function getX(): string {
                            return $this->a->getX();
                        }
                    }

                    $a = new A((string) $_GET["x"]);
                    $b = new B($a);
                    echo $b->getX();',
                'error_message' => 'TaintedHtml',
            ],
            'taintSpecializedInstanceStoredInAnotherThroughProperties' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    final class A {
                        public function __construct(public string $x) {}

                        public function getX(): string {
                            return $this->x;
                        }
                    }

                    /** @psalm-taint-specialize */
                    final class B {
                        public function __construct(public A $a) {}

                        public function getX(): string {
                            return $this->a->getX();
                        }
                    }

                    $a = new A((string) $_GET["x"]);
                    $b = new B($a);
                    echo $b->a->x;',
                'error_message' => 'TaintedHtml',
            ],
            'taintSpecializedInstanceStoredInAnotherAmongOthers' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    final class A {
                        public function __construct(public string $x) {}

                        public function getX(): string {
                            return $this->x;
                        }
                    }

                    /** @psalm-taint-specialize */
                    final class B {
                        public function __construct(public A $a) {}

                        public function getX(): string {
                            return $this->a->getX();
                        }
                    }

                    $b1 = new B(new A("safe"));
                    $b2 = new B(new A((string) $_GET["x"]));
                    echo $b2->getX();',
                'error_message' => 'TaintedHtml',
            ],
            'specializedInstanceStoredInAnotherCannotBeChangedByItsMethod' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    final class A {
                        public string $x = "";

                        public function setX(string $x): void {
                            $this->x = $x;
                        }

                        public function getX(): string {
                            return $this->x;
                        }
                    }

                    /** @psalm-taint-specialize */
                    final class B {
                        public function __construct(private A $a) {}

                        public function getX(): string {
                            return $this->a->getX();
                        }
                    }

                    $a = new A();
                    $b = new B($a);
                    $a->setX((string) $_GET["x"]);
                    echo $b->getX();',
                'error_message' => 'InaccessibleProperty',
            ],
            'specializedInstanceCannotChangeAnotherObject' => [
                'code' => '<?php
                    final class Registry {
                        public string $v = "";
                    }

                    /** @psalm-taint-specialize */
                    final class Child {
                        public function __construct(private Registry $r) {}

                        public function remember(string $s): void {
                            $this->r->v = $s;
                        }
                    }',
                'error_message' => 'ImpurePropertyAssignment',
            ],
            'specializedInstanceCannotChangeGlobalState' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    final class Child {
                        private static string $last = "";

                        public function remember(string $s): void {
                            self::$last = $s;
                        }
                    }',
                'error_message' => 'ImpureStaticProperty',
            ],
            'childOfSpecializedClassMustBeSpecialized' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    class Base {}

                    final class Child extends Base {}',
                'error_message' => 'ImmutableDependency',
            ],
            'childOfSpecializedClassIsSpecializedWithoutTheAnnotation' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    class Base {}

                    /** @psalm-suppress ImmutableDependency */
                    final class Child extends Base {
                        private static string $last = "";

                        public function remember(string $s): void {
                            self::$last = $s;
                        }

                        public function recall(): string {
                            return self::$last;
                        }
                    }',
                'error_message' => 'ImpureStaticProperty',
            ],
            'dontSpecializeImpureFunction' => [
                'code' => '<?php
                    function remember(string $s): string {
                        $GLOBALS["last"] = $s;
                        return $s;
                    }

                    $notEchoed = remember($_GET["a"]);
                    echo remember("safe");',
                'error_message' => 'TaintedHtml',
            ],
            'dontSpecializeImpureFunctionCalledFromSpecializedFunction' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    function wrap(string $s): string {
                        return remember($s);
                    }

                    function remember(string $s): string {
                        $GLOBALS["last"] = $s;
                        return $s;
                    }

                    $notEchoed = remember($_GET["a"]);
                    echo wrap("safe");',
                'error_message' => 'TaintedHtml',
            ],
            'dontSpecializeInferredPureOverridableMethod' => [
                'code' => '<?php
                    class A {
                        public function id(string $s): string {
                            return $s;
                        }
                    }

                    $a = new A();
                    $notEchoed = $a->id($_GET["a"]);
                    echo $a->id("safe");',
                'error_message' => 'TaintedHtml',
            ],
            'taintSpecializedMethodForAnonymousInstance' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    class Unsafe {
                        public function isUnsafe() {
                            return $_GET["unsafe"];
                        }
                    }
                    echo (new Unsafe())->isUnsafe();',
                'error_message' => 'TaintedHtml',
            ],
            'taintSpecializedMethodForStubMadeInstance' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    class Unsafe {
                        public function isUnsafe() {
                            return $_GET["unsafe"];
                        }
                    }

                    /** @psalm-suppress InvalidReturnType */
                    function stub(): Unsafe { }

                    /** @psalm-suppress MixedArgument */
                    echo stub()->isUnsafe();',
                'error_message' => 'TaintedHtml',
            ],
            'doTaintSpecializedInstanceProperty' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    class StringHolder {
                        public $x;

                        public function __construct(string $x) {
                            $this->x = $x;
                        }
                    }

                    $b = new StringHolder($_GET["x"]);

                    echo $b->x;',
                'error_message' => 'TaintedHtml',
            ],
            'taintUnserialize' => [
                'code' => '<?php
                    $cb = unserialize($_POST[\'x\']);',
                'error_message' => 'TaintedUnserialize',
            ],
            'taintCreateFunction' => [
                'code' => '<?php
                    $cb = create_function(\'$a\', $_GET[\'x\']);',
                'error_message' => 'TaintedEval',
                'php_version' => '7.0',
            ],
            'taintException' => [
                'code' => '<?php
                    $e = new Exception();
                    echo $e;',
                'error_message' => 'TaintedHtml',
            ],
            'taintError' => [
                'code' => '<?php
                    function foo() {}
                    try {
                        foo();
                    } catch (TypeError $e) {
                        echo "Caught: {$e->getTraceAsString()}\n";
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintThrowable' => [
                'code' => '<?php
                    function foo() {}
                    try {
                        foo();
                    } catch (Throwable $e) {
                        echo "Caught: $e";  // TODO: ("Caught" . $e) does not work.
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintReturnedArray' => [
                'code' => '<?php
                    function processParams(array $params) : array {
                        if (isset($params["foo"])) {
                            return $params;
                        }

                        return [];
                    }

                    $params = processParams($_GET);

                    echo $params["foo"];',
                'error_message' => 'TaintedHtml',
            ],
            'taintFlow' => [
                'code' => '<?php
                    /**
                     * @psalm-flow ($r) -> return
                     */
                    function some_stub(string $r): string { return ""; }

                    $r = $_GET["untrusted"];

                    echo some_stub($r);',
                'error_message' => 'TaintedHtml',
            ],
            'taintFlowProxy' => [
                'code' => '<?php
                    /**
                     * @psalm-taint-sink callable $in
                     */
                    function dummy_taint_sink(string $in): void {}

                    /**
                     * @psalm-flow proxy dummy_taint_sink($r)
                     */
                    function some_stub(string $r): string {}

                    $r = $_GET["untrusted"];

                    some_stub($r);',
                'error_message' => 'TaintedCallable',
            ],
            'taintFlowProxyAndReturn' => [
                'code' => '<?php
                    function dummy_taintable(string $in): string {
                        return $in;
                    }

                    /**
                     * @psalm-flow proxy dummy_taintable($r) -> return
                     */
                    function some_stub(string $r): string {}

                    $r = $_GET["untrusted"];

                    echo some_stub($r);',
                'error_message' => 'TaintedHtml',
            ],
            'taintFlowMethodProxyAndReturn' => [
                'code' => '<?php
                    class dummy {
                        public function taintable(string $in): string {
                            return $in;
                        }
                    }

                    /**
                     * @psalm-flow proxy dummy::taintable($r) -> return
                     */
                    function some_stub(string $r): string {}

                    $r = $_GET["untrusted"];

                    echo some_stub($r);',
                'error_message' => 'TaintedHtml',
            ],
            'taintPopen' => [
                'code' => '<?php
                    /** @psalm-suppress PossiblyInvalidCast */
                    $cb = popen($_POST[\'x\'], \'r\');',
                'error_message' => 'TaintedShell',
            ],
            'taintProcOpen' => [
                'code' => '<?php
                    $arr = [];
                    $cb = proc_open($_POST[\'x\'], [], $arr);',
                'error_message' => 'TaintedShell',
            ],
            'taintedCurlInit' => [
                'code' => '<?php
                    $ch = curl_init($_GET[\'url\']);',
                'error_message' => 'TaintedSSRF',
            ],
            'taintedCurlSetOpt' => [
                'code' => '<?php
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $_GET[\'url\']);',
                'error_message' => 'TaintedSSRF',
            ],
            'taintThroughChildConstructorWithoutMethodOverride' => [
                'code' => '<?php //--taint-analysis
                    class A {
                        private $taint;

                        public function __construct($taint) {
                            $this->taint = $taint;
                        }

                        public function getTaint() : string {
                            return $this->taint;
                        }
                    }

                    class B extends A {}


                    $b = new B($_GET["bar"]);
                    echo $b->getTaint();',
                'error_message' => 'TaintedHtml',
            ],
            'taintThroughChildConstructorCallingParentMethod' => [
                'code' => '<?php //--taint-analysis
                    class A {
                        private $taint;

                        public function __construct($taint) {
                            $this->taint = $taint;
                        }

                        public function getTaint() : string {
                            return $this->taint;
                        }
                    }

                    class B extends A {}

                    class C extends B {}

                    $c = new C($_GET["bar"]);

                    function foo(B $b) {
                        echo $b->getTaint();
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintThroughChildConstructorCallingGrandParentMethod' => [
                'code' => '<?php //--taint-analysis
                    class A {
                        private $taint;

                        public function __construct($taint) {
                            $this->taint = $taint;
                        }

                        public function getTaint() : string {
                            return $this->taint;
                        }
                    }

                    class B extends A {}

                    class C extends B {}

                    $c = new C($_GET["bar"]);

                    function foo(A $a) {
                        echo $a->getTaint();
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintThroughChildConstructorWhenMethodOverriddenWithParentConstructorCall' => [
                'code' => '<?php //--taint-analysis
                    class A {
                        private $taint;

                        public function __construct($taint) {
                            $this->taint = $taint;
                        }

                        public function getTaint() : string {
                            return $this->taint;
                        }
                    }

                    class B extends A {
                        public function __construct($taint) {
                            parent::__construct($taint);
                        }
                    }

                    $b = new B($_GET["bar"]);
                    echo $b->getTaint();',
                'error_message' => 'TaintedHtml',
            ],
            'taintedLdapSearch' => [
                'code' => '<?php
                    $ds = ldap_connect(\'example.com\');
                    $dn = \'o=Psalm, c=US\';
                    $filter = $_GET[\'filter\'];
                    ldap_search($ds, $dn, $filter, []);',
                'error_message' => 'TaintedLdap',
            ],
            'taintedFile' => [
                'code' => '<?php
                file_put_contents($_GET[\'taint\'], "data");',
            'error_message' => 'TaintedFile',
            ],
            'taintedHeader' => [
                'code' => '<?php
                header($_GET[\'taint\']);',
            'error_message' => 'TaintedHeader',
            ],
            'taintedCookie' => [
                'code' => '<?php
                setcookie($_GET[\'taint\'], \'value\');',
            'error_message' => 'TaintedCookie',
            ],
            'variadicTaintPropagation' => [
                'code' => '<?php

                /**
                 * @psalm-pure
                 *
                 * @param string|int|float $args
                 *
                 * @psalm-flow ($format, $args) -> return
                 */
                function variadic_test(string $format, ...$args) : string {
                    return sprintf($format, ...$args);
                }

                echo variadic_test(\'\', \'\', $_GET[\'taint\'], \'\');',
                'error_message' => 'TaintedHtml',
            ],
            'potentialTaintThroughChildClassSettingProperty' => [
                'code' => '<?php
                    class A {
                        public string $taint = "";

                        public function getTaint() : string {
                            return $this->taint;
                        }
                    }

                    class B extends A {
                        public function __construct(string $taint) {
                            $this->taint = $taint;
                        }
                    }

                    $b = new B($_GET["bar"]);
                    echo $b->getTaint();',
                'error_message' => 'TaintedHtml',
            ],
            'immutableClassTrackInputThroughMethod' => [
                'code' => '<?php
                    /**
                     * @psalm-immutable
                     */
                    class A {
                        private string $taint = "";

                        public function __construct(string $taint) {
                            $this->taint = $taint;
                        }

                        public function getTaint() : string {
                            return $this->taint;
                        }
                    }

                    $a = new A($_GET["bar"]);
                    echo $a->getTaint();',
                'error_message' => 'TaintedHtml',
            ],
            'strTrReturnTypeTaint' => [
                'code' => '<?php
                    $input = strtr(\'data\', $_GET[\'taint\'], \'data\');
                    setcookie($input, \'value\');',
                'error_message' => 'TaintedCookie',
            ],
            'conditionallyEscapedTaintPassedFalse' => [
                'code' => '<?php
                    /**
                     * @psalm-taint-escape ($escape is true ? "html" : null)
                     */
                    function foo(string $string, bool $escape = true): string {
                        if ($escape) {
                            $string = htmlspecialchars($string);
                        }

                        return $string;
                    }

                    echo foo($_GET["foo"], false);',
                'error_message' => 'TaintedHtml',
            ],
            'conditionallyEscapedTaintPassedTrueKeepsTheOtherTaints' => [
                'code' => '<?php
                    /** @psalm-taint-escape ($escape is true ? "html" : null) */
                    function escape(string $s, bool $escape = true): string {
                        return $s;
                    }

                    shell_exec(escape((string) $_GET["x"], true));',
                'error_message' => 'TaintedShell',
            ],
            'conditionallyEscapedTaintPassedTrueToAStaticCallKeepsTheOtherTaints' => [
                'code' => '<?php
                    class U {
                        /** @psalm-taint-escape ($escape is true ? "html" : null) */
                        public static function escape(string $s, bool $escape = true): string {
                            return $s;
                        }
                    }

                    shell_exec(U::escape((string) $_GET["x"], true));',
                'error_message' => 'TaintedShell',
            ],
            'suppressOneCatchAnother' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    function data(array $data, string $key) {
                        return $data[$key];
                    }

                    function get(string $key) {
                        return data($_GET, $key);
                    }

                    function post(string $key) {
                        return data($_POST, $key);
                    }

                    echo get("x");
                    /** @psalm-suppress TaintedInput */
                    echo post("x");',
                'error_message' => 'TaintedHtml',
            ],
            'taintSpecializedTwice' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    function data(array $data, string $key) {
                        return $data[$key];
                    }

                    /** @psalm-taint-specialize */
                    function get(string $key) {
                        return data($_GET, $key);
                    }

                    echo get("x");',
                'error_message' => 'TaintedHtml',
            ],
            'conditionallyEscapedTaintsAll' => [
                'code' => '<?php
                    /** @psalm-taint-escape ($type is "int" ? "html" : null) */
                    function cast(mixed $value, string $type): mixed
                    {
                        if ("int" === $type) {
                            return (int) $value;
                        }
                        return (string) $value;
                    }

                    /** @psalm-taint-specialize */
                    function data(array $data, string $key, string $type) {
                        return cast($data[$key], $type);
                    }

                    // technically a false-positive, but desired behaviour in lieu
                    // of better information
                    echo data($_GET, "x", "int");',
                'error_message' => 'TaintedHtml',
            ],
            'psalmFlowOnInstanceMethod' => [
                'code' => '<?php //--taint-analysis
                    class Wdb {
                        /**
                          * @psalm-pure
                          *
                          * @param string $text
                          * @return string
                          * @psalm-flow ($text) -> return
                          */
                        public function esc_like($text) {
                            return $text;
                        }

                        /**
                          * @param string $query
                          * @return int|bool
                          *
                          * @psalm-taint-sink sql $query
                          */
                        public function query($query){}
                    }

                    $wdb = new Wdb();

                    $order = $wdb->esc_like($_GET["order"]);
                    $res = $wdb->query("SELECT blah FROM tablea ORDER BY ". $order. " DESC");',
                'error_message' => 'TaintedSql',
            ],
            'psalmFlowOnStaticMethod' => [
                'code' => '<?php //--taint-analysis
                    class Wdb {
                        /**
                          * @psalm-pure
                          *
                          * @param string $text
                          * @return string
                          * @psalm-flow ($text) -> return
                          */
                        public static function esc_like($text) {
                            return $text;
                        }

                        /**
                          * @param string $query
                          * @return int|bool
                          *
                          * @psalm-taint-sink sql $query
                          */
                        public static function query($query){}
                    }

                    $order = Wdb::esc_like($_GET["order"]);
                    $res = Wdb::query("SELECT blah FROM tablea ORDER BY ". $order. " DESC");',
                'error_message' => 'TaintedSql',
            ],
            'keysOfAValueOfAnArrayAreTainted' => [
                'code' => '<?php
                    /** @param array<string, array<string, string>> $arr */
                    function takesArray(array $arr): void {
                        foreach ($arr["inner"] as $key => $_) {
                            echo $key;
                        }
                    }

                    takesArray(["inner" => [(string)$_GET["bad"] => "good"]]);',
                'error_message' => 'TaintedHtml',
            ],
            'keysOfAPropertyAreTainted' => [
                'code' => '<?php
                    final class Version {
                        /** @var array<string, string> */
                        public array $data = [];
                    }

                    function save(Version $version, string $field_id): void {
                        $version->data = ["val_" . $field_id => "good"];
                    }

                    function show(Version $version): void {
                        foreach ($version->data as $key => $_) {
                            echo $key;
                        }
                    }

                    save(new Version(), (string)$_GET["field_id"]);',
                'error_message' => 'TaintedHtml',
            ],
            'keysAreTainted' => [
                'code' => '<?php
                    function takesArray(array $arr): void {
                        foreach ($arr as $key => $_) {
                            echo $key;
                        }
                    }

                    takesArray([$_GET["bad"] => "good"]);',
                'error_message' => 'TaintedHtml',
            ],
            'resultOfPlusIsTaintedOnArrays' => [
                'code' => '<?php
                    scope($_GET["foo"]);
                    function scope(array $foo)
                    {
                        $var = $foo + [];
                        var_dump($var);
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintArrayKeyWithExplicitSink' => [
                'code' => '<?php
                    /** @psalm-taint-sink html $values */
                    function doTheMagic(array $values) {}

                    doTheMagic([(string)$_GET["bad"] => "foo"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintThroughReset' => [
                'code' => '<?php
                    function foo(array $arr) : void {
                        if ($arr) {
                            echo reset($arr);
                        }
                    }

                    foo([$_GET["a"]]);',
                'error_message' => 'TaintedHtml',
            ],
            'shellExecBacktick' => [
                'code' => '<?php

                    $input = $_GET["input"];
                    $x = `$input`;
                    ',
                'error_message' => 'TaintedShell',
            ],
            'shellExecBacktickConcat' => [
                'code' => '<?php

                    $input = $_GET["input"];
                    $x = `ls $input`;
                    ',
                'error_message' => 'TaintedShell',
            ],
            'taintExceptionMessage' => [
                'code' => '<?php
                    $x = new Exception($_GET["x"]);
                    echo $x->getMessage();',
                'error_message' => 'TaintedHtml',
            ],
            'taintedArgReturnedByClosureCalledThroughVariable' => [
                'code' => '<?php
                    $f = function (string $s): string {
                        return $s;
                    };
                    echo $f((string) $_GET["x"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintedArgEchoedByClosureCalledThroughVariable' => [
                'code' => '<?php
                    $f = static function (string $s): void {
                        echo $s;
                    };
                    $f((string) $_GET["x"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintedReturnOfClosurePassedToFunctionCallingIt' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @param callable(): T $callback
                     * @return T
                     */
                    function run(callable $callback) {
                        return $callback();
                    }
                    echo run(fn(): string => (string) $_GET["x"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintedReturnOfClosurePassedToMethodCallingIt' => [
                'code' => '<?php
                    final class Locker {
                        /**
                         * @template T
                         * @param Closure(): T $callback
                         * @return T
                         */
                        public static function lock(string $name, Closure $callback) {
                            return $callback();
                        }
                    }
                    echo Locker::lock("a", static function (): string {
                        return (string) $_GET["x"];
                    });',
                'error_message' => 'TaintedHtml',
            ],
            'taintedReturnOfNamedFunctionPassedToFunctionCallingIt' => [
                'code' => '<?php
                    function source(): string {
                        return (string) $_GET["x"];
                    }
                    function run(callable $callback): string {
                        return (string) $callback();
                    }
                    echo run(source(...));',
                'error_message' => 'TaintedHtml',
            ],
            'taintedReturnOfSpecializedCallOfFunctionAlsoPassedAsCallable' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    function wrap(string $s): string {
                        return "<b>" . $s . "</b>";
                    }
                    function run(callable $callback): string {
                        return (string) $callback("a");
                    }
                    run(wrap(...));
                    echo wrap((string) $_GET["x"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintSourceInSpecializedFunctionAlsoPassedAsCallable' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    function withQuery(string $s): string {
                        return $s . (string) $_GET["y"];
                    }
                    function run(callable $callback): string {
                        return (string) $callback("a");
                    }
                    run(withQuery(...));
                    echo withQuery("a");',
                'error_message' => 'TaintedHtml',
            ],
            'taintedArgPassedByFunctionToClosureItIsGiven' => [
                'code' => '<?php
                    function apply(callable $callback, string $value): void {
                        $callback($value);
                    }
                    apply(function (string $s): void {
                        echo $s;
                    }, (string) $_GET["x"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintedReturnOfClosureWithDocblockReturnTypeCalledThroughVariable' => [
                'code' => '<?php
                    $f = /** @return list{0: mixed} */ function () {
                        return [(string) $_GET["x"]];
                    };
                    [$x] = $f();
                    echo (string) $x;',
                'error_message' => 'TaintedHtml',
            ],
            'taintedReturnOfClosurePassedToClosureCallingIt' => [
                'code' => '<?php
                    $run = function (callable $callback): string {
                        return (string) $callback();
                    };
                    echo $run(fn(): string => (string) $_GET["x"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintCaughtExceptionMessage' => [
                'code' => '<?php
                    function foo(): void {}
                    try {
                        foo();
                    } catch (Throwable $e) {
                        echo $e->getMessage();
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintCaughtExceptionDump' => [
                'code' => '<?php
                    function foo(): void {}
                    try {
                        foo();
                    } catch (RuntimeException $e) {
                        echo print_r($e, true);
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheMessageOfTheExceptionAnObjectIdThrowsOnInvalidInput' => [
                'code' => '<?php
                    try {
                        new MongoDB\BSON\ObjectId((string) $_GET["id"]);
                    } catch (MongoDB\Driver\Exception\InvalidArgumentException $e) {
                        // it repeats the input
                        echo $e->getMessage();
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'castToArrayPassTaints' => [
                'code' => '<?php
                    $args = $_POST;

                    $args = (array) $args;

                    pg_query($connection, "SELECT * FROM tableA where key = " .$args["key"]);
                    ',
                'error_message' => 'TaintedSql',
            ],
            'taintSinkWithComments' => [
                'code' => '<?php

                    /**
                     * @psalm-taint-sink html $sink
                     *
                     * Not working
                     */
                    function sinkNotWorking($sink) : string {}

                    echo sinkNotWorking($_GET["taint"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintEscapedInTryMightNotWork' => [
                'code' => '<?php
                    /** @psalm-taint-escape html */
                    function escapeHtml(string $arg): string
                    {
                        return htmlspecialchars($arg);
                    }

                    $tainted = $_GET["foo"];

                    try {
                        $tainted = escapeHtml($tainted);
                    } catch (Throwable $_) {
                    }

                    echo $tainted;
                ',
                'error_message' => 'TaintedHtml',
            ],
            'taintArrayWithOffsetUpdated' => [
                'code' => '<?php
                    function foo() {
                        $foo = [
                            "a" => [["c" => "hello"]],
                            "b" => [],
                        ];

                        $foo["b"][] = [
                            "c" => $_GET["bad"],
                        ];

                        bar($foo["b"]);
                    }

                    function bar(array $arr): void {
                        foreach ($arr as $s) {
                            echo $s["c"];
                        }
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'checkMemoizedStaticMethodCallTaints' => [
                'code' => '<?php
                    class A {
                        private static string $prev = "";

                        public static function getPrevious(string $s): string {
                            $prev = self::$prev;
                            self::$prev = $s;
                            return $prev;
                        }
                    }

                    A::getPrevious($_GET["a"]);
                    echo A::getPrevious("foo");',
                'error_message' => 'TaintedHtml',
            ],
            'taintSpecializedCallReturningWhatAnotherLeftInAKeyedStore' => [
                'code' => '<?php
                    /** @psalm-flow ($value) -> Store::$data[$key] */
                    function store_set(string $key, string $value): void {}

                    /** @psalm-flow Store::$data[$key] -> return */
                    function store_get(string $key): string {}

                    /** @psalm-taint-specialize */
                    function remember(string $v): string {
                        $prev = store_get("k");
                        store_set("k", $v);
                        return $prev;
                    }

                    remember((string) $_GET["x"]);
                    echo remember("safe");',
                'error_message' => 'TaintedHtml',
            ],
            'taintSpecializedCallReturningWhatAnotherLeftInAStaticProperty' => [
                'code' => '<?php
                    final class Memo { public static string $last = ""; }

                    /** @psalm-taint-specialize */
                    function remember(string $v): string {
                        $prev = Memo::$last;
                        Memo::$last = $v;
                        return $prev;
                    }

                    remember((string) $_GET["x"]);
                    echo remember("safe");',
                'error_message' => 'TaintedHtml',
            ],
            'taintSpecializedCallReturningAnArrayItemAnotherLeftInAStaticProperty' => [
                // the second array the call leaves in the static property doesn't share the exit of the first one
                'code' => '<?php
                    final class Memo {
                        /** @var array<string, string> */
                        public static array $last = [];
                    }

                    /** @psalm-taint-specialize */
                    function remember(string $v): string {
                        $prev = Memo::$last;
                        Memo::$last = ["x" => $v];
                        Memo::$last = ["y" => $v];
                        return $prev["y"] ?? "";
                    }

                    remember((string) $_GET["x"]);
                    echo remember("safe");',
                'error_message' => 'TaintedHtml',
            ],
            'taintCallReturningWhatAnotherLeftInAStaticVariable' => [
                'code' => '<?php
                    function remember(string $v): string {
                        /** @var string */
                        static $last = "";
                        $prev = $last;
                        $last = $v;
                        return $prev;
                    }

                    remember((string) $_GET["x"]);
                    echo remember("safe");',
                'error_message' => 'TaintedHtml',
            ],
            'taintMethodCallReturningWhatAnotherLeftInAStaticVariable' => [
                'code' => '<?php
                    final class A {
                        public function remember(string $v): string {
                            /** @var string */
                            static $last = "";
                            $prev = $last;
                            $last = $v;
                            return $prev;
                        }
                    }

                    $a = new A();
                    $a->remember((string) $_GET["x"]);
                    echo $a->remember("safe");',
                'error_message' => 'TaintedHtml',
            ],
            'taintClosureCallReturningWhatAnotherLeftInAStaticVariable' => [
                'code' => '<?php
                    $remember = function (string $v): string {
                        /** @var string */
                        static $last = "";
                        $prev = $last;
                        $last = $v;
                        return $prev;
                    };

                    $remember((string) $_GET["x"]);
                    echo $remember("safe");',
                'error_message' => 'TaintedHtml',
            ],
            'taintCallReturningWhatAnotherLeftInAStaticVariableMemo' => [
                'code' => '<?php
                    function memo(string $k, ?string $v = null): ?string {
                        /** @var array<string, string> */
                        static $cache = [];
                        if ($v !== null) {
                            $cache[$k] = $v;
                            return null;
                        }
                        return $cache[$k] ?? null;
                    }

                    memo("a", (string) $_GET["x"]);
                    echo (string) memo("a");',
                'error_message' => 'TaintedHtml',
            ],
            'taintSpecializedCallReturningWhatAnotherLeftInAStaticVariable' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    function remember(string $v): string {
                        /** @var string */
                        static $last = "";
                        $prev = $last;
                        $last = $v;
                        return $prev;
                    }

                    remember((string) $_GET["x"]);
                    echo remember("safe");',
                'error_message' => 'TaintedHtml',
            ],
            'taintSpecializedCallReturningWhatAnotherLeftInAStaticVariableMemo' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    function memo(string $k, ?string $v = null): ?string {
                        /** @var array<string, string> */
                        static $cache = [];
                        if ($v !== null) {
                            $cache[$k] = $v;
                            return null;
                        }
                        return $cache[$k] ?? null;
                    }

                    memo("a", (string) $_GET["x"]);
                    echo (string) memo("a");',
                'error_message' => 'TaintedHtml',
            ],
            'taintNestedCallReadingWhatTheOuterCallLeftInAStaticVariable' => [
                // the outer call overwrites the static variable before it returns, after the nested call read it
                'code' => '<?php
                    function reenter(?string $v): string {
                        /** @var string */
                        static $s = "";
                        if ($v !== null) {
                            $s = $v;
                            echo reenter(null);
                            $s = "";
                            return "";
                        }
                        return $s;
                    }

                    reenter((string) $_GET["x"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintedNewCall' => [
                'code' => '<?php
                    $a = $_GET["a"];
                    $b = $_GET["b"];
                    new $a($b);',
                'error_message' => 'TaintedCallable',
            ],
            'urlencode' => [
                /**
                 * urlencode() should only prevent html & has_quotes taints
                 * All other taint types should be unaffected.
                 * We arbitrarily chose system() to test this.
                 */
                'code' => '<?php
                    /** @psalm-suppress PossiblyInvalidArgument */
                    system(urlencode($_GET["bad"]));
                ',
                'error_message' => 'TaintedShell',
            ],
            'assertMysqliOnlyEscapesSqlTaints1' => [
                'code' => '<?php
                    $mysqli = new mysqli();
                    echo $mysqli->escape_string($_GET["a"]);',
                'error_message' => 'TaintedHtml',
            ],
            'assertMysqliOnlyEscapesSqlTaints2' => [
                'code' => '<?php
                    $mysqli = new mysqli();
                    echo $mysqli->real_escape_string($_GET["a"]);',
                'error_message' => 'TaintedHtml',
            ],
            'assertMysqliOnlyEscapesSqlTaints3' => [
                'code' => '<?php
                    $mysqli = new mysqli();
                    echo mysqli_escape_string($mysqli, $_GET["a"]);',
                'error_message' => 'TaintedHtml',
            ],
            'assertMysqliOnlyEscapesSqlTaints4' => [
                'code' => '<?php
                    $mysqli = new mysqli();
                    echo mysqli_real_escape_string($mysqli, $_GET["a"]);',
                'error_message' => 'TaintedHtml',
            ],
            'assertDb2OnlyEscapesSqlTaints' => [
                'code' => '<?php
                    echo db2_escape_string($_GET["a"]);',
                'error_message' => 'TaintedHtml',
            ],
            'assertCubridOnlyEscapesSqlTaints' => [
                'code' => '<?php
                    echo cubrid_real_escape_string($_GET["a"]);',
                'error_message' => 'TaintedHtml',
            ],
            'assertSQLiteOnlyEscapesSqlTaints' => [
                'code' => '<?php
                    echo SQLite3::escapeString($_GET["a"]);',
                'error_message' => 'TaintedHtml',
            ],
            'assertPGOnlyEscapesSqlTaints1' => [
                'code' => '<?php
                    echo pg_escape_bytea($_GET["a"]);',
                'error_message' => 'TaintedHtml',
            ],
            'assertPGOnlyEscapesSqlTaints2' => [
                'code' => '<?php
                    echo pg_escape_bytea($conn, $_GET["a"]);',
                'error_message' => 'TaintedHtml',
            ],
            'assertPGOnlyEscapesSqlTaints3' => [
                'code' => '<?php
                    echo pg_escape_identifier($_GET["a"]);',
                'error_message' => 'TaintedHtml',
            ],
            'assertPGOnlyEscapesSqlTaints4' => [
                'code' => '<?php
                    echo pg_escape_identifier($conn, $_GET["a"]);',
                'error_message' => 'TaintedHtml',
            ],
            'assertPGOnlyEscapesSqlTaints5' => [
                'code' => '<?php
                    echo pg_escape_literal($_GET["a"]);',
                'error_message' => 'TaintedHtml',
            ],
            'assertPGOnlyEscapesSqlTaints6' => [
                'code' => '<?php
                    echo pg_escape_literal($conn, $_GET["a"]);',
                'error_message' => 'TaintedHtml',
            ],
            'assertPGOnlyEscapesSqlTaints7' => [
                'code' => '<?php
                    echo pg_escape_string($_GET["a"]);',
                'error_message' => 'TaintedHtml',
            ],
            'assertPGOnlyEscapesSqlTaints8' => [
                'code' => '<?php
                    echo pg_escape_string($conn, $_GET["a"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintedReflectionClass' => [
                'code' => '<?php
                    $name = $_GET["name"];
                    $reflector = new ReflectionClass($name);
                    $reflector->newInstance();',
                'error_message' => 'TaintedCallable',
            ],
            'taintedReflectionFunction' => [
                'code' => '<?php
                    $name = $_GET["name"];
                    $function = new ReflectionFunction($name);
                    $function->invoke();',
                'error_message' => 'TaintedCallable',
            ],
            'querySimpleXMLElement' => [
                'code' => '<?php
                    function queryExpression(SimpleXMLElement $xml) : array|false|null {
                        $expression = $_GET["expression"];
                        return $xml->xpath($expression);
                    }',
                'error_message' => 'TaintedXpath',
            ],
            'queryDOMXPath' => [
                'code' => '<?php
                    function queryExpression(DOMXPath $xpath) : mixed {
                        $expression = $_GET["expression"];
                        return $xpath->query($expression);
                    }',
                'error_message' => 'TaintedXpath',
            ],
            'evaluateDOMXPath' => [
                'code' => '<?php
                    function evaluateExpression(DOMXPath $xpath) : mixed {
                        $expression = $_GET["expression"];
                        return $xpath->evaluate($expression);
                    }',
                'error_message' => 'TaintedXpath',
            ],
            'taintedSleep' => [
                'code' => '<?php
                    sleep($_GET["seconds"]);',
                'error_message' => 'TaintedSleep',
            ],
            'sleepSurvivesIntCast' => [
                'code' => '<?php
                    // a numeric value can still cause a DoS, so the sleep taint must
                    // survive casting to int
                    sleep((int) $_GET["seconds"]);',
                'error_message' => 'TaintedSleep',
            ],
            'sleepSurvivesFloatCast' => [
                'code' => '<?php
                    /** @psalm-taint-sink sleep $s */
                    function mysleep($s): void {}
                    mysleep((float) $_GET["seconds"]);',
                'error_message' => 'TaintedSleep',
            ],
            'sqlSurvivesStringCast' => [
                'code' => '<?php
                    /** @psalm-taint-sink sql $q */
                    function query($q): void {}

                    // a string can still carry a SQL injection, so casting to string
                    // must only strip the array/object-only taints (e.g. nosql)
                    query((string) $_GET["id"]);',
                'error_message' => 'TaintedSql',
            ],
            'taintedUsleep' => [
                'code' => '<?php
                    usleep($_GET["microseconds"]);',
                'error_message' => 'TaintedSleep',
            ],
            'taintedTimeNanosleepSeconds' => [
                'code' => '<?php
                    time_nanosleep($_GET["seconds"], 42);',
                'error_message' => 'TaintedSleep',
            ],
            'taintedTimeNanosleepNanoseconds' => [
                'code' => '<?php
                    time_nanosleep(42, $_GET["nanoseconds"]);',
                'error_message' => 'TaintedSleep',
            ],
            'taintedTimeSleepUntil' => [
                'code' => '<?php
                    time_sleep_until($_GET["timestamp"]);',
                'error_message' => 'TaintedSleep',
            ],
            'taintedExtract' => [
                'code' => '<?php
                    $array = $_GET;
                    extract($array);',
                'error_message' => 'TaintedExtract',
            ],
            'extractPost' => [
                'code' => '<?php
                    extract($_POST);',
                'error_message' => 'TaintedExtract',
            ],
            'TaintForIntTypeCastUsingAnnotatedSink' => [
                'code' => '<?php // --taint-analysis
                    /** @param int $id */
                    function fetch($id): string
                    {
                        return query("SELECT * FROM table WHERE id=" . $id);
                    }
                    /**
                     * @return string
                     * @psalm-taint-sink sql $sql
                     * @psalm-taint-specialize
                     */
                    function query(string $sql) {}
                    $value = $_GET["value"];
                    $result = fetch($value);',
                'error_message' => 'TaintedSql',
            ],
            'TaintForIntSleep' => [
                'code' => '<?php // --taint-analysis
                    function s(int $id): void
                    {
                        sleep($id);
                    }
                    $value = $_GET["value"];
                    s($value);',
                'error_message' => 'TaintedSleep',
            ],
            'taintedExecuteQueryFunction' => [
                'code' => '<?php
                    $userId = $_GET["user_id"];
                    $query = "delete from users where user_id = " . $userId;
                    $link = mysqli_connect("localhost", "my_user", "my_password", "world");
                    $result = mysqli_execute_query($link, $query);',
                'error_message' => 'TaintedSql',
            ],
            'taintedExecuteQueryMethod' => [
                'code' => '<?php
                    $userId = $_GET["user_id"];
                    $query = "delete from users where user_id = " . $userId;
                    $mysqli = new mysqli("localhost", "my_user", "my_password", "world");
                    $result = $mysqli->execute_query($query);',
                'error_message' => 'TaintedSql',
                'php_version' => '8.2',
            ],
            'taintedRegisterShutdownFunction' => [
                'code' => '<?php
                    $foo = $_GET["foo"];
                    register_shutdown_function($foo);',
                'error_message' => 'TaintedCallable',
            ],
            'taintedRegisterTickFunction' => [
                'code' => '<?php
                    $foo = $_GET["foo"];
                    register_tick_function($foo);',
                'error_message' => 'TaintedCallable',
            ],
            'taintedForwardStaticCall' => [
                'code' => '<?php
                    $foo = $_GET["foo"];
                    class B
                    {
                        public static function test($foo) {
                            forward_static_call($foo, "one", "two");
                        }
                    }
                    B::test($foo);',
                'error_message' => 'TaintedCallable',
            ],
            'taintedForwardStaticCallArray' => [
                'code' => '<?php
                    $foo = $_GET["foo"];
                    class B
                    {
                        public static function test($foo) {
                            forward_static_call_array($foo, array("one", "two"));
                        }
                    }
                    B::test($foo);',
                'error_message' => 'TaintedCallable',
            ],
            'taintTheResultOfACallOnAnInterfaceWithTheReturnOfAnImplementation' => [
                'code' => '<?php
                    interface Named {
                        public function name(): string;
                    }
                    final class Tainted implements Named {
                        public function name(): string { return (string) $_GET["name"]; }
                    }

                    function show(Named $named): void {
                        echo $named->name();
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheResultOfACallOnAParentClassWithTheReturnOfAnOverride' => [
                'code' => '<?php
                    class Named {
                        public function name(): string { return "safe"; }
                    }
                    final class Tainted extends Named {
                        public function name(): string { return (string) $_GET["name"]; }
                    }

                    function show(Named $named): void {
                        echo $named->name();
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheResultOfACallOfAnAbstractMethodWithTheReturnOfAnImplementation' => [
                'code' => '<?php
                    abstract class Named {
                        abstract public function name(): string;
                    }
                    final class Tainted extends Named {
                        public function name(): string { return (string) $_GET["name"]; }
                    }

                    function show(Named $named): void {
                        echo $named->name();
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheResultOfAStaticCallOnStaticWithTheReturnOfAnOverride' => [
                'code' => '<?php
                    class Named {
                        public static function name(): string { return "safe"; }

                        public static function show(): void {
                            echo static::name();
                        }
                    }
                    final class Tainted extends Named {
                        public static function name(): string { return (string) $_GET["name"]; }
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintTheResultOfACallOnAnImplementationAlsoCalledOnItsInterface' => [
                'code' => '<?php
                    interface Named {
                        public function name(): string;
                    }
                    final class Tainted implements Named {
                        public function name(): string { return (string) $_GET["name"]; }
                    }

                    function get(Named $named): string {
                        return $named->name();
                    }
                    function show(Tainted $named): void {
                        echo $named->name();
                    }',
                'error_message' => 'TaintedHtml',
            ],
        ];
    }

    /**
     * @param list<string> $expectedIssueTypes
     * @param list<int>|null $expectedSourceLines line of each issue's taint origin, in any order
     * @param bool $findUnusedVariables track unused variables, as the CLI does by default
     */
    #[DataProvider('multipleTaintIssuesAreDetectedDataProvider')]
    #[Test]
    public function multipleTaintIssuesAreDetected(
        string $code,
        array $expectedIssueTypes,
        ?array $expectedSourceLines = null,
        bool $findUnusedVariables = false,
    ): void {
        if (strpos($this->getTestName(), 'SKIPPED-') !== false) {
            $this->markTestSkipped();
        }

        // disables issue exceptions - we need all, not just the first
        $this->testConfig->throw_exception = false;
        $this->project_analyzer->getCodebase()->find_unused_variables = $findUnusedVariables;
        $filePath = self::$src_dir_path . 'somefile.php';
        $this->addFile($filePath, $code);
        $this->project_analyzer->trackTaintedInputs();

        $this->analyzeFile($filePath, new Context(), false);

        $issues = array_values(array_filter(
            IssueBuffer::getIssuesDataForFile($filePath),
            static fn(IssueData $issue): bool => !in_array($issue->type, self::IGNORE, true),
        ));
        $actualIssueTypes = array_map(
            static fn(IssueData $issue): string => $issue->type . '{ ' . trim($issue->snippet) . ' }',
            $issues,
        );
        // The order issues are reported in depends on the resolution rounds their flows take
        self::assertEqualsCanonicalizing($expectedIssueTypes, $actualIssueTypes);

        if ($expectedSourceLines !== null) {
            $actualSourceLines = [];
            foreach ($issues as $issue) {
                $origin = $issue->taint_trace[0] ?? null;
                self::assertInstanceOf(DataFlowNodeData::class, $origin);
                $actualSourceLines[] = $origin->line_from;
            }
            self::assertEqualsCanonicalizing($expectedSourceLines, $actualSourceLines);
        }
    }


    /**
     * @return array<string, array{
     *     code: string,
     *     expectedIssueTypes: list<string>,
     *     expectedSourceLines?: list<int>,
     *     findUnusedVariables?: bool,
     * }>
     * @psalm-pure
     */
    public static function multipleTaintIssuesAreDetectedDataProvider(): array
    {
        return [
            'taintSinkFlow' => [
                'code' => '<?php
                    /**
                     * @param string $value
                     * @return string
                     *
                     * @psalm-flow ($value) -> return
                     * @psalm-taint-sink html $value
                     */
                    function process(string $value): string { return ""; }
                    $data = process((string)($_GET["inject"] ?? ""));
                    exec($data);
                ',
                'expectedIssueTypes' => [
                    'TaintedHtml{ function process(string $value): string { return ""; } }',
                    'TaintedShell{ exec($data); }',
                ],
            ],
            'taintSinkCascade' => [
                'code' => '<?php
                    function triggerHtml(string $value): string
                    {
                        echo $value;
                        return $value;
                    }
                    function triggerShell(string $value): string
                    {
                        exec($value);
                        return $value;
                    }
                    function triggerFile(string $value): string
                    {
                        file_get_contents($value);
                        return $value;
                    }
                    $value = (string)($_GET["inject"] ?? "");
                    $value = triggerHtml($value);
                    $value = triggerShell($value);
                    $value = triggerFile($value);
                ',
                'expectedIssueTypes' => [
                    'TaintedHtml{ echo $value; }',
                    'TaintedTextWithQuotes{ echo $value; }',
                    'TaintedShell{ exec($value); }',
                    'TaintedSSRF{ file_get_contents($value); }',
                    'TaintedFile{ file_get_contents($value); }',
                ],
            ],
            'taintedIncludes' => [
                'code' => '<?php
                    $first = (string)($_GET["first"] ?? "");
                    $second = (string)($_GET["second"] ?? "");
                    /** @psalm-suppress UnresolvableInclude */
                    require $first;
                    /** @psalm-suppress MissingFile */
                    require dirname(__DIR__)."/first.php";
                    /** @psalm-suppress UnresolvableInclude */
                    require $second;
                    /** @psalm-suppress MissingFile */
                    require dirname(__DIR__)."/second.php";
                ',
                'expectedIssueTypes' => [
                    'TaintedInclude{ require $first; }',
                    'TaintedInclude{ require $second; }',
                ],
            ],
            'flowsOfDifferentLengthIntoSharedSink' => [
                // The relayed flow reaches the runCmd() sink a resolution round after the
                // direct one, when the sink has already been visited with the same taints.
                'code' => '<?php
                    /** @psalm-flow ($value) -> return */
                    function relay(string $value): string { return $value; }
                    /** @psalm-taint-sink shell $cmd */
                    function runCmd(string $cmd): void {}

                    runCmd((string)($_GET["direct"] ?? ""));
                    runCmd(relay((string)($_GET["relayed"] ?? "")));
                ',
                'expectedIssueTypes' => [
                    'TaintedShell{ function runCmd(string $cmd): void {} }',
                    'TaintedShell{ function runCmd(string $cmd): void {} }',
                ],
                'expectedSourceLines' => [7, 8],
            ],
            'distinctOriginsThroughSpecializedFunction' => [
                // Both flows enter the sink through the same edge inside wrap().
                'code' => '<?php
                    /** @psalm-taint-sink shell $cmd */
                    function runCmd(string $cmd): void {}
                    /** @psalm-taint-specialize */
                    function wrap(string $s): void { runCmd($s); }

                    wrap((string)($_GET["a"] ?? ""));
                    wrap((string)($_GET["b"] ?? ""));
                ',
                'expectedIssueTypes' => [
                    'TaintedShell{ function runCmd(string $cmd): void {} }',
                    'TaintedShell{ function runCmd(string $cmd): void {} }',
                ],
                'expectedSourceLines' => [7, 8],
            ],
            'lateFlowIntoSinkOfClassLevelSpecializedMethod' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    final class Runner {
                        public function run(string $cmd): void { exec($cmd); }
                    }

                    (new Runner())->run((string) ($_GET["a"] ?? ""));
                    $b1 = (string) ($_GET["b"] ?? "");
                    $b2 = $b1;
                    (new Runner())->run($b2);
                ',
                'expectedIssueTypes' => [
                    'TaintedShell{ public function run(string $cmd): void { exec($cmd); } }',
                    'TaintedShell{ public function run(string $cmd): void { exec($cmd); } }',
                ],
                'expectedSourceLines' => [7, 8],
            ],
            'sameOriginThroughDistinctCallSites' => [
                'code' => '<?php
                    /** @psalm-taint-sink shell $cmd */
                    function runCmd(string $cmd): void {}

                    $x = (string)($_GET["a"] ?? "");
                    runCmd($x);
                    runCmd($x);
                ',
                'expectedIssueTypes' => [
                    'TaintedShell{ function runCmd(string $cmd): void {} }',
                    'TaintedShell{ function runCmd(string $cmd): void {} }',
                ],
            ],
            'sameFlowWithTwoTaintMasksIsReportedOnce' => [
                // escapeHtml() removes the html taint but keeps shell, so the same origin reaches
                // the sink through the same argument with two different taint masks.
                'code' => '<?php
                    /** @psalm-taint-sink shell $cmd */
                    function runCmd(string $cmd): void {}
                    /**
                     * @psalm-flow ($s) -> return
                     * @psalm-taint-escape html
                     */
                    function escapeHtml(string $s): string { return $s; }

                    $x = (string)($_GET["a"] ?? "");
                    runCmd(rand(0, 1) ? $x : escapeHtml($x));
                ',
                'expectedIssueTypes' => [
                    'TaintedShell{ function runCmd(string $cmd): void {} }',
                ],
            ],
            'everyCustomTaintKindOfAFlowIsReported' => [
                'code' => '<?php
                    /**
                     * @psalm-taint-source alpha
                     * @psalm-taint-source beta
                     */
                    function externalInput(): string { return "x"; }
                    /**
                     * @psalm-taint-sink alpha $arg
                     * @psalm-taint-sink beta $arg
                     */
                    function customSink(string $arg): void {}

                    customSink(externalInput());
                ',
                'expectedIssueTypes' => [
                    'TaintedCustom{ function customSink(string $arg): void {} }',
                    'TaintedCustom{ function customSink(string $arg): void {} }',
                ],
            ],
            'complementSourceHasEveryTaintButTheKindsItLeavesOut' => [
                // alpha is only registered after the source: the complement has it all the same
                'code' => '<?php
                    /** @psalm-taint-source ~(header|cookie) */
                    function externalInput(): string { return "x"; }
                    /** @psalm-taint-sink alpha $arg */
                    function customSink(string $arg): void {}
                    /** @psalm-taint-sink header $arg */
                    function headerSink(string $arg): void {}
                    /** @psalm-taint-sink cookie $arg */
                    function cookieSink(string $arg): void {}
                    /** @psalm-taint-sink html $arg */
                    function htmlSink(string $arg): void {}

                    customSink(externalInput());
                    headerSink(externalInput());
                    cookieSink(externalInput());
                    htmlSink(externalInput());
                ',
                'expectedIssueTypes' => [
                    'TaintedCustom{ function customSink(string $arg): void {} }',
                    'TaintedHtml{ function htmlSink(string $arg): void {} }',
                ],
            ],
            'complementSinkAndEscapeLeaveOutTheKindsTheyList' => [
                'code' => '<?php
                    /** @psalm-taint-source sql */
                    function sqlInput(): string { return "x"; }
                    /** @psalm-taint-source html */
                    function htmlInput(): string { return "x"; }
                    /** @psalm-taint-sink ~(sql) $arg */
                    function anyButSql(string $arg): void {}
                    /**
                     * @psalm-flow ($s) -> return
                     * @psalm-taint-escape ~html
                     */
                    function keepHtml(string $s): string { return $s; }
                    /** @psalm-taint-sink html $arg */
                    function htmlSink(string $arg): void {}
                    /** @psalm-taint-sink shell $arg */
                    function shellSink(string $arg): void {}

                    anyButSql(sqlInput());
                    anyButSql(htmlInput());
                    shellSink(keepHtml((string) ($_GET["a"] ?? "")));
                    htmlSink(keepHtml((string) ($_GET["b"] ?? "")));
                ',
                'expectedIssueTypes' => [
                    'TaintedHtml{ function anyButSql(string $arg): void {} }',
                    'TaintedHtml{ function htmlSink(string $arg): void {} }',
                ],
            ],
            'sinkInSpecializedFunctionReachedByLaterCall' => [
                // The relayed call enters the specialized function a round after the direct one, when its body has
                // been walked with the same taints.
                'code' => '<?php
                    /** @psalm-flow ($v) -> return */
                    function relay(string $v): string { return $v; }
                    /** @psalm-flow ($v) -> return */ function relay2(string $v): string { return $v; }
                    /** @psalm-taint-specialize */
                    function execute(string $value): void { exec($value); }

                    final class Runner {
                        /** @psalm-taint-specialize */
                        public static function execute(string $value): void { system($value); }
                    }

                    execute((string)($_GET["first"] ?? ""));
                    execute(relay((string)($_GET["second"] ?? "")));
                    Runner::execute((string)($_GET["first"] ?? ""));
                    Runner::execute(relay2((string)($_GET["second"] ?? "")));
                ',
                'expectedIssueTypes' => [
                    'TaintedShell{ function execute(string $value): void { exec($value); } }',
                    'TaintedShell{ public static function execute(string $value): void { system($value); } }',
                    'TaintedShell{ function execute(string $value): void { exec($value); } }',
                    'TaintedShell{ public static function execute(string $value): void { system($value); } }',
                ],
                'expectedSourceLines' => [13, 14, 15, 16],
            ],
            'returnOfSpecializedFunctionReachedByLaterCall' => [
                'code' => '<?php
                    /** @psalm-flow ($v) -> return */
                    function relay(string $v): string { return $v; }

                    /** @psalm-taint-specialize */
                    function id(string $s): string { return $s; }

                    $a = id((string)($_GET["a"] ?? ""));
                    exec($a);
                    $b = id(relay(relay((string)($_GET["b"] ?? ""))));
                    exec($b);
                    $c = id("safe");
                    exec($c);
                ',
                'expectedIssueTypes' => [
                    'TaintedShell{ exec($a); }',
                    'TaintedShell{ exec($b); }',
                ],
                'expectedSourceLines' => [8, 10],
            ],
            'sinkInSpecializedFunctionReportedOncePerOrigin' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    function run(string $s): void { exec($s); }

                    $input = (string)($_GET["a"] ?? "");
                    run($input);
                    run($input);
                ',
                'expectedIssueTypes' => [
                    'TaintedShell{ function run(string $s): void { exec($s); } }',
                ],
                'expectedSourceLines' => [5],
            ],
            'nestedSpecializedCallsReachedByLaterCall' => [
                'code' => '<?php
                    /** @psalm-flow ($v) -> return */
                    function relay(string $v): string { return $v; }

                    /** @psalm-taint-specialize */
                    function inner(string $s): string {
                        passthru($s);
                        return $s;
                    }

                    /** @psalm-taint-specialize */
                    function outer(string $s): string { return inner($s); }

                    inner((string)($_GET["x"] ?? ""));
                    $a = outer((string)($_GET["a"] ?? ""));
                    exec($a);
                    $b = outer(relay(relay((string)($_GET["b"] ?? ""))));
                    exec($b);
                    $c = outer("safe");
                    exec($c);
                ',
                'expectedIssueTypes' => [
                    'TaintedShell{ passthru($s); }',
                    'TaintedShell{ passthru($s); }',
                    'TaintedShell{ passthru($s); }',
                    'TaintedShell{ exec($a); }',
                    'TaintedShell{ exec($b); }',
                ],
                'expectedSourceLines' => [14, 15, 17, 15, 17],
            ],
            'specializedFunctionThroughSharedHelperReachedByLaterCall' => [
                'code' => '<?php
                    /** @psalm-flow ($v) -> return */
                    function relay(string $v): string { return $v; }

                    function helper(string $x): string { return $x; }

                    /** @psalm-taint-specialize */
                    function f(string $s): string { return helper($s); }

                    $a = f((string)($_GET["a"] ?? ""));
                    exec($a);
                    $b = f(relay(relay((string)($_GET["b"] ?? ""))));
                    exec($b);
                    $c = f("safe");
                    exec($c);
                ',
                'expectedIssueTypes' => [
                    'TaintedShell{ exec($a); }',
                    'TaintedShell{ exec($b); }',
                ],
                'expectedSourceLines' => [10, 12],
            ],
            'sinkInHelperOfSpecializedFunctionReachedByLaterCall' => [
                // The sink is past the specialized function, in an unspecialized one it calls.
                'code' => '<?php
                    /** @psalm-flow ($v) -> return */
                    function relay(string $v): string { return $v; }

                    function helper(string $x): void { exec($x); }

                    /** @psalm-taint-specialize */
                    function wrap(string $s): void { helper($s); }

                    wrap((string)($_GET["a"] ?? ""));
                    wrap(relay((string)($_GET["b"] ?? "")));
                ',
                'expectedIssueTypes' => [
                    'TaintedShell{ function helper(string $x): void { exec($x); } }',
                    'TaintedShell{ function helper(string $x): void { exec($x); } }',
                ],
                'expectedSourceLines' => [10, 11],
            ],
            'recursiveSpecializedFunctionReachedByLaterCall' => [
                'code' => '<?php
                    /** @psalm-flow ($v) -> return */
                    function relay(string $v): string { return $v; }

                    /** @psalm-taint-specialize */
                    function rec(string $s, int $n): string {
                        if ($n > 0) {
                            return rec($s . "x", $n - 1);
                        }
                        exec($s);
                        return $s;
                    }

                    echo rec((string)($_GET["a"] ?? ""), 3);
                    echo rec("safe", 3);
                    echo rec(relay((string)($_GET["b"] ?? "")), 2);
                ',
                'expectedIssueTypes' => [
                    'TaintedShell{ exec($s); }',
                    'TaintedHtml{ echo rec((string)($_GET["a"] ?? ""), 3); }',
                    'TaintedTextWithQuotes{ echo rec((string)($_GET["a"] ?? ""), 3); }',
                    'TaintedShell{ exec($s); }',
                    'TaintedHtml{ echo rec(relay((string)($_GET["b"] ?? "")), 2); }',
                    'TaintedTextWithQuotes{ echo rec(relay((string)($_GET["b"] ?? "")), 2); }',
                ],
                'expectedSourceLines' => [14, 14, 14, 16, 16, 16],
            ],
            'mutuallyRecursiveSpecializedFunctionsReachedByLaterCall' => [
                'code' => '<?php
                    /** @psalm-flow ($v) -> return */
                    function relay(string $v): string { return $v; }

                    /** @psalm-taint-specialize */
                    function ping(string $s, int $n): string { return $n > 0 ? pong($s, $n - 1) : $s; }

                    /** @psalm-taint-specialize */
                    function pong(string $s, int $n): string {
                        system($s);
                        return ping($s . "!", $n);
                    }

                    echo ping((string)($_GET["a"] ?? ""), 3);
                    echo ping("safe", 3);
                    echo pong(relay((string)($_GET["b"] ?? "")), 2);
                ',
                'expectedIssueTypes' => [
                    'TaintedHtml{ echo ping((string)($_GET["a"] ?? ""), 3); }',
                    'TaintedTextWithQuotes{ echo ping((string)($_GET["a"] ?? ""), 3); }',
                    'TaintedShell{ system($s); }',
                    'TaintedShell{ system($s); }',
                    'TaintedHtml{ echo pong(relay((string)($_GET["b"] ?? "")), 2); }',
                    'TaintedTextWithQuotes{ echo pong(relay((string)($_GET["b"] ?? "")), 2); }',
                ],
                'expectedSourceLines' => [14, 14, 14, 16, 16, 16],
            ],
            'specializedCallsThroughStaticPropertyReachedByLaterCall' => [
                // The flow leaves inner() through the static property, not through its return, and outer() returns
                // it: every call of outer() does, as it reads what the others left there. The first flow reaches
                // them all first.
                'code' => '<?php
                    /** @psalm-flow ($v) -> return */
                    function relay(string $v): string { return $v; }

                    final class Store { public static string $value = ""; }

                    /** @psalm-taint-specialize */
                    function inner(string $s): void { Store::$value = $s; }

                    /** @psalm-taint-specialize */
                    function outer(string $s): string {
                        inner($s);
                        return Store::$value;
                    }

                    exec(outer((string)($_GET["a"] ?? "")));
                    exec(outer(relay((string)($_GET["b"] ?? ""))));
                    exec(outer("safe"));
                ',
                'expectedIssueTypes' => [
                    'TaintedShell{ exec(outer((string)($_GET["a"] ?? ""))); }',
                    'TaintedShell{ exec(outer(relay((string)($_GET["b"] ?? "")))); }',
                    'TaintedShell{ exec(outer("safe")); }',
                ],
                'expectedSourceLines' => [16, 16, 16],
            ],
            'arrayReturnedBySpecializedFunctionToLaterCall' => [
                // Array keys stay apart through a specialized function for a call arriving after the first one
                // (each call is relayed through a function of its own, which would merge the flows otherwise).
                'code' => '<?php
                    /** @psalm-flow ($v) -> return */
                    function relay(string $v): string { return $v; }
                    /** @psalm-flow ($v) -> return */ function relay2(string $v): string { return $v; }
                    /** @psalm-taint-specialize */
                    function wrapX(string $s): array { return ["x" => $s]; }

                    /** @psalm-taint-specialize */
                    function getX(array $p): array { return (array) $p["x"]; }

                    $a = wrapX((string)($_GET["a"] ?? ""));
                    exec((string) $a["x"]);
                    exec((string) $a["y"]);
                    $b = wrapX(relay((string)($_GET["b"] ?? "")));
                    exec((string) $b["x"]);
                    exec((string) $b["y"]);

                    $c = getX(["x" => ["y" => (string)($_GET["c"] ?? "")]]);
                    exec((string) $c["y"]);
                    exec((string) $c["z"]);
                    $d = getX(["x" => ["y" => relay2(relay2((string)($_GET["d"] ?? "")))]]);
                    exec((string) $d["y"]);
                    exec((string) $d["z"]);
                ',
                'expectedIssueTypes' => [
                    'TaintedShell{ exec((string) $a["x"]); }',
                    'TaintedShell{ exec((string) $b["x"]); }',
                    'TaintedShell{ exec((string) $d["y"]); }',
                    'TaintedShell{ exec((string) $c["y"]); }',
                ],
                'expectedSourceLines' => [11, 14, 18, 21],
            ],
            'specializedCallsKeepArrayKeysApart' => [
                // What a specialized body does with a flow depends on the array keys it was assigned to.
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    function getX(array $p): string { return (string) $p["x"]; }

                    exec(getX(["x" => (string)($_GET["a"] ?? ""), "y" => "safe"]));
                    exec(getX(["x" => "safe", "y" => (string)($_GET["b"] ?? "")]));
                    exec(getX(["x" => (string)($_GET["c"] ?? ""), "y" => "safe"]));
                ',
                'expectedIssueTypes' => [
                    'TaintedShell{ exec(getX(["x" => (string)($_GET["a"] ?? ""), "y" => "safe"])); }',
                    'TaintedShell{ exec(getX(["x" => (string)($_GET["c"] ?? ""), "y" => "safe"])); }',
                ],
                'expectedSourceLines' => [5, 7],
            ],
            'arrayKeysStayApartWhereManyFlowsConverge' => [
                // Enough differently keyed arrays reach $task for what is reachable from it to be walked once,
                // relative to them: the fetch still only takes the one with the key it fetches.
                'code' => '<?php
                    function show(array $task): void {
                        echo (string) $task["name"];
                    }

                    show(["a" => (string)($_GET["a"] ?? "")]);
                    show(["b" => (string)($_GET["b"] ?? "")]);
                    show(["c" => (string)($_GET["c"] ?? "")]);
                    show(["d" => (string)($_GET["d"] ?? "")]);
                    show(["e" => (string)($_GET["e"] ?? "")]);
                    show(["f" => (string)($_GET["f"] ?? "")]);
                    show(["g" => (string)($_GET["g"] ?? "")]);
                    show(["h" => (string)($_GET["h"] ?? "")]);
                    show(["name" => (string)($_GET["name"] ?? "")]);
                ',
                'expectedIssueTypes' => [
                    'TaintedHtml{ echo (string) $task["name"]; }',
                    'TaintedTextWithQuotes{ echo (string) $task["name"]; }',
                ],
                'expectedSourceLines' => [14, 14],
            ],
            'specializedMethodCallSitesStayApart' => [
                // With unused-variable tracking on, as in the CLI. Fresh instances, so no taint is carried over
                // through the receiver.
                'code' => '<?php
                    class Renderer {
                        /** @psalm-taint-specialize */
                        public function render(string $s): string { return $s; }
                    }

                    class ChildRenderer extends Renderer {}

                    echo (new Renderer())->render((string)($_GET["a"] ?? ""));
                    echo (new Renderer())->render("safe");
                    echo (new ChildRenderer())->render((string)($_GET["b"] ?? ""));
                    echo (new ChildRenderer())->render("safe");
                ',
                'expectedIssueTypes' => [
                    'TaintedHtml{ echo (new Renderer())->render((string)($_GET["a"] ?? "")); }',
                    'TaintedTextWithQuotes{ echo (new Renderer())->render((string)($_GET["a"] ?? "")); }',
                    'TaintedHtml{ echo (new ChildRenderer())->render((string)($_GET["b"] ?? "")); }',
                    'TaintedTextWithQuotes{ echo (new ChildRenderer())->render((string)($_GET["b"] ?? "")); }',
                ],
                'expectedSourceLines' => [9, 9, 11, 11],
                'findUnusedVariables' => true,
            ],
            'inheritedSpecializedMethodReachedByLaterCall' => [
                'code' => '<?php
                    /** @psalm-flow ($v) -> return */
                    function relay(string $v): string { return $v; }

                    class Db {
                        /**
                         * @psalm-taint-sink sql $sql
                         * @psalm-taint-specialize
                         */
                        public function query(string $sql): string { return $sql; }
                    }

                    class ChildDb extends Db {}

                    $db = new ChildDb();
                    $db->query((string)($_GET["a"] ?? ""));
                    echo $db->query(relay((string)($_GET["b"] ?? "")));
                ',
                'expectedIssueTypes' => [
                    'TaintedSql{ public function query(string $sql): string { return $sql; } }',
                    'TaintedSql{ public function query(string $sql): string { return $sql; } }',
                    'TaintedHtml{ echo $db->query(relay((string)($_GET["b"] ?? ""))); }',
                    'TaintedTextWithQuotes{ echo $db->query(relay((string)($_GET["b"] ?? ""))); }',
                ],
                'expectedSourceLines' => [16, 17, 17, 17],
                'findUnusedVariables' => true,
            ],
            'specializedMethodWithFlowAnnotation' => [
                'code' => '<?php
                    class Formatter {
                        /**
                         * @psalm-taint-specialize
                         * @psalm-flow ($s) -> return
                         */
                        public function format(string $s): string { return ""; }
                    }

                    echo (new Formatter())->format((string)($_GET["a"] ?? ""));
                    echo (new Formatter())->format("safe");
                    $formatter = new Formatter();
                    echo $formatter->format((string)($_GET["b"] ?? ""));
                ',
                'expectedIssueTypes' => [
                    'TaintedHtml{ echo (new Formatter())->format((string)($_GET["a"] ?? "")); }',
                    'TaintedTextWithQuotes{ echo (new Formatter())->format((string)($_GET["a"] ?? "")); }',
                    'TaintedHtml{ echo $formatter->format((string)($_GET["b"] ?? "")); }',
                    'TaintedTextWithQuotes{ echo $formatter->format((string)($_GET["b"] ?? "")); }',
                ],
                'expectedSourceLines' => [10, 10, 13, 13],
                'findUnusedVariables' => true,
            ],
            'outputFunctionsAreSinksOfTheKindsOfEcho' => [
                'code' => '<?php
                    printf("%s", (string) $_GET["a"]);
                    vprintf("%s", [(string) $_GET["b"]]);
                    print_r($_GET["c"]);
                    var_dump($_GET["d"]);
                    var_export($_GET["e"]);
                    $printed = print_r($_GET["f"], true);
                    $exported = var_export($_GET["g"], true);
                    debug_zval_dump($_GET["h"]);
                    debug_zval_dump(1, $_GET["i"]);
                ',
                'expectedIssueTypes' => [
                    'TaintedHtml{ printf("%s", (string) $_GET["a"]); }',
                    'TaintedTextWithQuotes{ printf("%s", (string) $_GET["a"]); }',
                    'TaintedHtml{ vprintf("%s", [(string) $_GET["b"]]); }',
                    'TaintedTextWithQuotes{ vprintf("%s", [(string) $_GET["b"]]); }',
                    'TaintedHtml{ print_r($_GET["c"]); }',
                    'TaintedTextWithQuotes{ print_r($_GET["c"]); }',
                    'TaintedHtml{ var_dump($_GET["d"]); }',
                    'TaintedTextWithQuotes{ var_dump($_GET["d"]); }',
                    'TaintedHtml{ var_export($_GET["e"]); }',
                    'TaintedTextWithQuotes{ var_export($_GET["e"]); }',
                    'TaintedHtml{ debug_zval_dump($_GET["h"]); }',
                    'TaintedTextWithQuotes{ debug_zval_dump($_GET["h"]); }',
                    'TaintedHtml{ debug_zval_dump(1, $_GET["i"]); }',
                    'TaintedTextWithQuotes{ debug_zval_dump(1, $_GET["i"]); }',
                ],
            ],
            'outputFunctionsLeakSecretsLikeEcho' => [
                'code' => '<?php
                    /** @psalm-taint-source user_secret */
                    function userSecret(): string { return ""; }
                    /** @psalm-taint-source system_secret */
                    function systemSecret(): string { return ""; }

                    printf("%s", userSecret());
                    vprintf("%s", [systemSecret()]);
                    print_r(userSecret());
                    var_dump(systemSecret());
                    var_export(userSecret());
                    $printed = print_r(systemSecret(), true);
                    debug_zval_dump(systemSecret());
                ',
                'expectedIssueTypes' => [
                    'TaintedUserSecret{ printf("%s", userSecret()); }',
                    'TaintedSystemSecret{ vprintf("%s", [systemSecret()]); }',
                    'TaintedUserSecret{ print_r(userSecret()); }',
                    'TaintedSystemSecret{ var_dump(systemSecret()); }',
                    'TaintedUserSecret{ var_export(userSecret()); }',
                    'TaintedSystemSecret{ debug_zval_dump(systemSecret()); }',
                ],
            ],
        ];
    }
}
