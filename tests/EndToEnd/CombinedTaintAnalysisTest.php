<?php

declare(strict_types=1);

namespace Psalm\Tests\EndToEnd;

use JsonException;
use Override;
use PHPUnit\Framework\TestCase;
use Psalm\Internal\Codebase\CombinedTaintPass;
use RuntimeException;
use Symfony\Component\Process\Process;

use function array_filter;
use function array_map;
use function array_merge;
use function array_values;
use function assert;
use function explode;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function glob;
use function implode;
use function is_array;
use function is_dir;
use function json_decode;
use function json_encode;
use function mkdir;
use function posix_kill;
use function preg_match;
use function rmdir;
use function sort;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function sys_get_temp_dir;
use function tempnam;
use function trim;
use function unlink;
use function usleep;

use const GLOB_BRACE;
use const JSON_THROW_ON_ERROR;
use const PHP_BINARY;
use const SIGKILL;
use const SIGTERM;

/**
 * Runs the psalm binary with --combined-taint-analysis and checks that the one run reports
 * what a normal run and a --taint-analysis run report between them.
 *
 * Most assertions share the runs made once in setUpBeforeClass(), to keep the number of
 * Psalm processes down.
 *
 * @requires extension pcntl
 * @requires extension posix
 */
final class CombinedTaintAnalysisTest extends TestCase
{
    private const CONFIG = <<<'XML'
        <?xml version="1.0"?>
        <psalm
            errorLevel="1"
            resolveFromConfigFile="true"
            cacheDirectory="cache"
            findUnusedVariablesAndParams="true"
            findUnusedPsalmSuppress="true"
            findUnusedBaselineEntry="false"
            findUnusedCode="false"
            ATTRIBUTES
        >
            <projectFiles>
                <directory name="src" />
            </projectFiles>
            EXTRA
        </psalm>
        XML;

    /** Plugins that add a taint sink and a taint source once the codebase is populated. */
    private const PLUGINS = <<<'XML'
        <plugins>
            <plugin filename="SinkPlugin.php" />
            <plugin filename="SourcePlugin.php" />
        </plugins>
        <issueHandlers>
            <!-- only the taint pass emits TaintedSql -->
            <TaintedSql>
                <errorLevel type="suppress"><directory name="src" /></errorLevel>
            </TaintedSql>
            <!-- nothing emits TaintedCookie in src/Query0.php -->
            <TaintedCookie>
                <errorLevel type="suppress"><file name="src/Query0.php" /></errorLevel>
            </TaintedCookie>
        </issueHandlers>
        XML;

    private const SINK_PLUGIN = <<<'PHP'
        <?php
        namespace Acme\Plugin;

        use Psalm\CodeLocation;
        use Psalm\Plugin\EventHandler\AfterCodebasePopulatedInterface;
        use Psalm\Plugin\EventHandler\Event\AfterCodebasePopulatedEvent;
        use Psalm\Type\TaintKindGroup;

        final class SinkPlugin implements AfterCodebasePopulatedInterface
        {
            public static function afterCodebasePopulated(AfterCodebasePopulatedEvent $event): void
            {
                $file = __DIR__ . '/src/Sink.php';
                $event->getCodebase()->addTaintSink(
                    'acme\runcommand#1',
                    TaintKindGroup::ALL_INPUT,
                    new CodeLocation\Raw((string) file_get_contents($file), $file, 'src/Sink.php', 0, 5),
                );
            }
        }
        PHP;

    private const SOURCE_PLUGIN = <<<'PHP'
        <?php
        namespace Acme\Plugin;

        use Psalm\Plugin\EventHandler\AfterCodebasePopulatedInterface;
        use Psalm\Plugin\EventHandler\Event\AfterCodebasePopulatedEvent;
        use Psalm\Type\TaintKindGroup;

        final class SourcePlugin implements AfterCodebasePopulatedInterface
        {
            public static function afterCodebasePopulated(AfterCodebasePopulatedEvent $event): void
            {
                $codebase = $event->getCodebase();
                $file = __DIR__ . '/src/Source.php';
                try {
                    $storage = $codebase->functions->getStorage(null, 'acme\readinput', $file, $file);
                } catch (\InvalidArgumentException) {
                    return; // not scanned when Psalm checks other paths only
                }
                if ($storage->return_type !== null) {
                    $storage->return_type = $codebase->addTaintSource(
                        $storage->return_type,
                        'acme-plugin-source',
                        TaintKindGroup::ALL_INPUT,
                    );
                }
            }
        }
        PHP;

    private const SINK_FILE = <<<'PHP'
        <?php
        namespace Acme;

        function runCommand(string $command): string
        {
            return $command;
        }

        runCommand(\is_string($_GET['c']) ? $_GET['c'] : '');
        PHP;

    private const SOURCE_FILE = <<<'PHP'
        <?php
        namespace Acme;

        function readInput(): string
        {
            return 'constant';
        }

        echo readInput();
        PHP;

    private const TAINTED_AND_UNUSED = <<<'PHP'
        <?php
        namespace Acme;

        function echoesInput(): void
        {
            $unused = 1;
            echo \is_string($_GET['name']) ? $_GET['name'] : '';
        }

        function echoesSuppressedInput(): void
        {
            /** @psalm-suppress TaintedInput */
            echo \is_string($_GET['other']) ? $_GET['other'] : '';
        }

        /** @psalm-suppress UnusedVariable */
        function suppressions(string $s): string
        {
            $used_suppression = 1;
            /** @psalm-suppress InvalidArgument */
            $unused_suppression = $s;
            return $unused_suppression;
        }
        PHP;

    private static string $root;

    private static string $shared;

    /** @var array<string, array{issues: list<string>, code: int|null, stdout: string}> */
    private static array $runs = [];

    private string $dir;

    #[Override]
    public static function setUpBeforeClass(): void
    {
        $root = tempnam(sys_get_temp_dir(), 'PsalmCombinedTaint_');
        assert($root !== false);
        unlink($root);
        mkdir($root);
        self::$root = $root;

        self::$shared = self::makeProject('shared', '', self::PLUGINS);
        self::writeSource(self::$shared, 'Tainted.php', self::TAINTED_AND_UNUSED);
        self::writeSource(self::$shared, 'Sink.php', self::SINK_FILE);
        self::writeSource(self::$shared, 'Source.php', self::SOURCE_FILE);
        // DuplicateClass is reported while scanning, before analysis, so this checks that
        // combined mode keeps the issues raised before the taint pass forks off.
        foreach (['DuplicateA.php', 'DuplicateB.php'] as $file) {
            self::writeSource(self::$shared, $file, "<?php\nnamespace Acme;\n\nfinal class Duplicate {}\n");
        }
        file_put_contents(self::$shared . '/SinkPlugin.php', self::SINK_PLUGIN);
        file_put_contents(self::$shared . '/SourcePlugin.php', self::SOURCE_PLUGIN);
        for ($i = 0; $i < 4; $i++) {
            self::writeSource(self::$shared, "Query$i.php", <<<PHP
                <?php
                namespace Acme;

                final class Query$i
                {
                    public function run(\\PDO \$pdo, string \$unused_param$i): void
                    {
                        \$pdo->query((string) \$_GET['q$i']);
                    }
                }
                PHP);
        }

        $paths = ['src/Tainted.php', 'src/Sink.php', 'src/Query0.php'];
        $runs = [
            'normal' => ['--no-cache', '--threads=2'],
            'taint' => ['--no-cache', '--threads=2', '--taint-analysis'],
            'combined' => ['--no-cache', '--threads=2', '--combined-taint-analysis'],
            'combined_one_thread' => ['--no-cache', '--threads=1', '--combined-taint-analysis'],
            'combined_cold_cache' => ['--threads=2', '--combined-taint-analysis'],
            'combined_warm_cache' => ['--threads=2', '--combined-taint-analysis'],
            'normal_paths' => ['--no-cache', ...$paths],
            'taint_paths' => ['--no-cache', '--taint-analysis', ...$paths],
            'combined_paths' => ['--no-cache', '--combined-taint-analysis', ...$paths],
        ];
        foreach ($runs as $name => $args) {
            self::$runs[$name] = self::runJson(self::$shared, $args);
        }

        $github = self::runPsalm(self::$shared, ['--no-cache', '--combined-taint-analysis', '--output-format=github']);
        self::$runs['combined_github'] = ['issues' => [], 'code' => $github['CODE'], 'stdout' => $github['STDOUT']];
    }

    #[Override]
    public static function tearDownAfterClass(): void
    {
        self::removeDirectory(self::$root);
        parent::tearDownAfterClass();
    }

    #[Override]
    public function setUp(): void
    {
        parent::setUp();
        $this->dir = self::makeProject($this->getName(), '', '');
    }

    public function testReportsTaintedAndOtherIssuesInOneRun(): void
    {
        $combined = self::$runs['combined'];
        $this->assertSame(2, $combined['code']);
        $this->assertNotEmpty(self::ofKind($combined['issues'], true));
        $this->assertNotEmpty(self::ofKind($combined['issues'], false));
        $this->assertContains('src/Tainted.php:7:10:TaintedHtml:Detected tainted HTML:error', $combined['issues']);
        $this->assertContains(
            'src/Tainted.php:6:5:UnusedVariable:$unused is never referenced or the value is not used:error',
            $combined['issues'],
        );
    }

    public function testDefaultRunIsUnchanged(): void
    {
        $this->assertSame([], self::ofKind(self::$runs['normal']['issues'], true));
        // see testIssueHandlerSuppressionsUsedByTheTaintPassCount for the one difference
        $this->assertSame(
            self::without(self::$runs['normal']['issues'], self::unusedHandler('TaintedSql')),
            self::ofKind(self::$runs['combined']['issues'], false),
        );
    }

    public function testEqualsNormalRunPlusTaintRun(): void
    {
        $this->assertNotSame(
            [],
            array_filter(self::$runs['normal']['issues'], static fn(string $i): bool => str_contains($i, ':DuplicateClass:')),
            'the fixture needs an issue that is reported while scanning',
        );
        // Unused issue handler suppressions are reported once per run, from that run's own counts:
        // both stock runs call the TaintedCookie handler unused, and the normal run also calls the
        // TaintedSql handler unused, though the taint pass uses it.
        $taint = self::without(self::$runs['taint']['issues'], self::unusedHandler('TaintedCookie'));
        $expected = self::sorted(array_merge(self::$runs['normal']['issues'], $taint));
        $expected = self::without($expected, self::unusedHandler('TaintedSql'));

        $this->assertNotSame([], self::$runs['taint']['issues']);
        foreach (['combined', 'combined_one_thread', 'combined_cold_cache', 'combined_warm_cache'] as $name) {
            $this->assertSame($expected, self::$runs[$name]['issues'], $name);
        }

        // Everything a Tainted* issue carries, traces included, makes it through the result file.
        $this->assertSame(
            self::taintedIssueObjects(self::$runs['taint']['stdout']),
            self::taintedIssueObjects(self::$runs['combined']['stdout']),
        );
    }

    public function testCheckPathsEqualsNormalRunPlusTaintRun(): void
    {
        $expected = self::sorted(array_merge(self::$runs['normal_paths']['issues'], self::$runs['taint_paths']['issues']));

        $this->assertNotSame([], self::$runs['taint_paths']['issues']);
        $this->assertSame($expected, self::$runs['combined_paths']['issues']);
    }

    public function testPluginTaintSourcesAndSinksReachTheTaintPass(): void
    {
        foreach (['taint', 'combined'] as $name) {
            $issues = self::$runs[$name]['issues'];
            $this->assertContains('src/Sink.php:1:1:TaintedShell:Detected tainted shell code:error', $issues, $name);
            $this->assertContains('src/Source.php:9:6:TaintedHtml:Detected tainted HTML:error', $issues, $name);
        }
    }

    public function testIssueHandlerSuppressionsUsedByTheTaintPassCount(): void
    {
        $normal = self::$runs['normal']['issues'];
        $combined = self::$runs['combined']['issues'];

        // the normal run never emits Tainted* issues, so it calls both handlers unused
        $this->assertContains(self::unusedHandler('TaintedSql'), $normal);
        $this->assertContains(self::unusedHandler('TaintedCookie'), $normal);
        // the taint pass used the TaintedSql handler; nothing used the TaintedCookie one
        $this->assertNotContains(self::unusedHandler('TaintedSql'), $combined);
        $this->assertContains(self::unusedHandler('TaintedCookie'), $combined);
        $this->assertSame([], array_values(array_filter(
            $combined,
            static fn(string $issue): bool => str_contains($issue, ':TaintedSql:'),
        )));
    }

    public function testSuppressionsInCombinedMode(): void
    {
        $issues = self::$runs['combined']['issues'];
        $at = static fn(string $prefix): array => array_values(array_filter(
            $issues,
            static fn(string $issue): bool => str_starts_with($issue, $prefix),
        ));

        // the TaintedInput suppression applies and is not reported as unused
        $this->assertSame([], $at('src/Tainted.php:13:'));
        $this->assertSame([], $at('src/Tainted.php:12:'));
        // the function's UnusedVariable suppression is used, because unused-variable tracking still runs
        $this->assertSame([], $at('src/Tainted.php:16:'));
        $this->assertSame([], $at('src/Tainted.php:19:'));
        // a suppression nothing uses is still reported
        $this->assertSame(
            ['src/Tainted.php:20:25:UnusedPsalmSuppress:This suppression is never used:error'],
            $at('src/Tainted.php:20:'),
        );
    }

    public function testGithubOutputIncludesBothKinds(): void
    {
        $github = self::$runs['combined_github'];
        $this->assertSame(2, $github['code']);
        $this->assertStringContainsString('title=TaintedHtml', $github['stdout']);
        $this->assertStringContainsString('title=UnusedVariable', $github['stdout']);
    }

    public function testConfigAttributeAndFlags(): void
    {
        self::writeConfig($this->dir, 'runCombinedTaintAnalysis="true"', '');
        self::writeSource($this->dir, 'Tainted.php', self::TAINTED_AND_UNUSED);

        $from_config = self::runJson($this->dir, ['--no-cache'])['issues'];
        $this->assertNotSame([], self::ofKind($from_config, true));
        $this->assertNotSame([], self::ofKind($from_config, false));

        $turned_off = self::runJson($this->dir, ['--no-cache', '--no-combined-taint-analysis'])['issues'];
        $this->assertSame([], self::ofKind($turned_off, true));
        $this->assertSame(self::ofKind($from_config, false), $turned_off);

        // --taint-analysis still means a taint-only run
        $taint_only = self::runJson($this->dir, ['--no-cache', '--taint-analysis'])['issues'];
        $this->assertSame(self::ofKind($from_config, true), $taint_only);
    }

    public function testUnsupportedFromConfigWarnsAndFromFlagFails(): void
    {
        self::writeConfig($this->dir, 'runCombinedTaintAnalysis="true"', '');
        self::writeSource($this->dir, 'Tainted.php', self::TAINTED_AND_UNUSED);
        // An unwritable temporary directory makes combined mode unsupported, like a missing pcntl.
        $unsupported = ['TMPDIR' => $this->dir . '/missing'];
        $args = ['--no-cache', '--threads=1', '--output-format=json'];

        $from_config = self::runPsalm($this->dir, $args, $unsupported);
        $this->assertStringContainsString('Running the normal analysis only', $from_config['STDERR']);
        $this->assertStringNotContainsString('"type":"Tainted', $from_config['STDOUT']);
        $this->assertStringContainsString('"type":"UnusedVariable"', $from_config['STDOUT']);

        $from_flag = self::runPsalm($this->dir, [...$args, '--combined-taint-analysis'], $unsupported);
        $this->assertSame(1, $from_flag['CODE']);
        $this->assertStringContainsString('is not writable', $from_flag['STDERR']);
    }

    public function testExitCodeWhenOnlyTaintedIssuesRemain(): void
    {
        self::writeSource($this->dir, 'OnlyTainted.php', <<<'PHP'
            <?php
            namespace Acme;

            function echoesInput(): void
            {
                echo \is_string($_GET['name']) ? $_GET['name'] : '';
            }
            PHP);

        $combined = self::runJson($this->dir, ['--no-cache', '--combined-taint-analysis']);
        $this->assertSame([
            'src/OnlyTainted.php:6:10:TaintedHtml:Detected tainted HTML:error',
            'src/OnlyTainted.php:6:10:TaintedTextWithQuotes:Detected tainted text with possible quotes:error',
        ], $combined['issues']);
        $this->assertSame(2, $combined['code']);
    }

    public function testExitCodeWhenOnlyOtherIssuesRemain(): void
    {
        self::writeSource($this->dir, 'OnlyUnused.php', <<<'PHP'
            <?php
            namespace Acme;

            function unused(): void
            {
                $unused = 1;
            }
            PHP);

        $combined = self::runJson($this->dir, ['--no-cache', '--combined-taint-analysis']);
        $this->assertSame(
            ['src/OnlyUnused.php:6:5:UnusedVariable:$unused is never referenced or the value is not used:error'],
            $combined['issues'],
        );
        $this->assertSame(2, $combined['code']);
    }

    public function testExitCodeIsZeroWhenClean(): void
    {
        self::writeSource($this->dir, 'Clean.php', <<<'PHP'
            <?php
            namespace Acme;

            function clean(string $s): string
            {
                return $s;
            }
            PHP);

        $combined = self::runJson($this->dir, ['--no-cache', '--combined-taint-analysis']);
        $this->assertSame([], $combined['issues']);
        $this->assertSame(0, $combined['code']);
    }

    public function testBaselineKeepsBothKinds(): void
    {
        self::writeSource($this->dir, 'Tainted.php', self::TAINTED_AND_UNUSED);
        $baseline = $this->dir . '/psalm-baseline.xml';

        self::runPsalm($this->dir, ['--no-cache', '--combined-taint-analysis', '--set-baseline']);
        $contents = (string) file_get_contents($baseline);
        $this->assertStringContainsString('<TaintedHtml>', $contents);
        $this->assertStringContainsString('<UnusedVariable>', $contents);

        $after_set = self::runJson($this->dir, ['--no-cache', '--combined-taint-analysis']);
        $this->assertSame([], $after_set['issues']);
        $this->assertSame(0, $after_set['code']);

        // fix the unused variable; --update-baseline drops its entry and keeps the taint entries
        self::writeSource($this->dir, 'Tainted.php', str_replace('$unused = 1;', '', self::TAINTED_AND_UNUSED));
        self::runPsalm($this->dir, ['--no-cache', '--combined-taint-analysis', '--update-baseline']);
        $contents = (string) file_get_contents($baseline);
        $this->assertStringContainsString('<TaintedHtml>', $contents);
        $this->assertStringContainsString('<TaintedTextWithQuotes>', $contents);
        $this->assertStringNotContainsString('$unused = 1', $contents);

        $after_update = self::runJson($this->dir, ['--no-cache', '--combined-taint-analysis']);
        $this->assertSame([], $after_update['issues']);
        $this->assertSame(0, $after_update['code']);
    }

    public function testBaselineFromNormalRunDoesNotHideTaintedIssues(): void
    {
        self::writeSource($this->dir, 'Tainted.php', self::TAINTED_AND_UNUSED);

        self::runPsalm($this->dir, ['--no-cache', '--set-baseline']);
        $combined = self::runJson($this->dir, ['--no-cache', '--combined-taint-analysis']);
        $this->assertSame([
            'src/Tainted.php:7:10:TaintedHtml:Detected tainted HTML:error',
            'src/Tainted.php:7:10:TaintedTextWithQuotes:Detected tainted text with possible quotes:error',
        ], $combined['issues']);
        $this->assertSame(2, $combined['code']);
    }

    public function testFailedTaintPassEndsTheRunWithAClearError(): void
    {
        self::writeSource($this->dir, 'Tainted.php', self::TAINTED_AND_UNUSED);

        foreach (['crash' => 'was killed by signal 6', 'garbage' => 'finished without sending complete'] as $mode => $why) {
            $result = self::runPsalm(
                $this->dir,
                ['--no-cache', '--combined-taint-analysis'],
                [CombinedTaintPass::FAILURE_ENV => $mode],
            );

            $this->assertSame(1, $result['CODE'], $mode);
            $this->assertStringContainsString('Psalm could not finish the combined taint analysis', $result['STDERR']);
            $this->assertStringContainsString($why, $result['STDERR'], $mode);
            $this->assertStringNotContainsString('Stack trace', $result['STDERR'], $mode);
            $this->assertSame([], self::resultFiles($this->dir), $mode);
        }
    }

    /**
     * "stall" never checks for its parent, so after SIGKILL only the watchdog can stop it.
     *
     * @requires OS Linux
     */
    public function testKillingTheParentStopsTheTaintPassAndRemovesItsFile(): void
    {
        self::writeSource($this->dir, 'Tainted.php', self::TAINTED_AND_UNUSED);

        foreach ([['hang', SIGTERM], ['hang', SIGKILL], ['stall', SIGKILL]] as [$mode, $signal]) {
            $process = self::psalmProcess(
                $this->dir,
                ['--no-cache', '--combined-taint-analysis'],
                [CombinedTaintPass::FAILURE_ENV => $mode],
            );
            $process->start();

            $result_file = self::waitFor(fn(): ?string => self::resultFiles($this->dir)[0] ?? null);
            $this->assertNotNull($result_file, 'the taint pass did not start');
            $this->assertSame(1, preg_match('/psalm-taint-pass-(\d+)-/', $result_file, $matches));
            $parent_pid = (int) $matches[1];
            $child_pids = self::childPids($parent_pid);
            $this->assertNotSame([], $child_pids, 'could not find the taint pass process');
            // The watchdog is the taint pass's own child; wait until it is there, then stop it too.
            $watchdog_pids = self::waitFor(static function () use ($child_pids): ?array {
                $pids = array_merge(...array_map(self::childPids(...), $child_pids));
                return $pids === [] ? null : $pids;
            });
            $this->assertNotNull($watchdog_pids, 'the watchdog did not start');
            $child_pids = array_merge($child_pids, $watchdog_pids);

            posix_kill($parent_pid, $signal);

            $this->assertTrue(
                self::waitFor(fn(): ?bool => self::resultFiles($this->dir) === [] ? true : null) ?? false,
                "the result file is left after signal $signal ($mode)",
            );
            foreach ($child_pids as $child_pid) {
                $this->assertTrue(
                    self::waitFor(static fn(): ?bool => posix_kill($child_pid, 0) ? null : true) ?? false,
                    "the taint pass is still running after signal $signal ($mode)",
                );
            }

            $process->wait();
        }
    }

    public function testDumpTaintGraphIsRefused(): void
    {
        self::writeSource($this->dir, 'Tainted.php', self::TAINTED_AND_UNUSED);

        $result = self::runPsalm(
            $this->dir,
            ['--no-cache', '--combined-taint-analysis', '--dump-taint-graph=' . $this->dir . '/taints.dot'],
        );

        $this->assertSame(1, $result['CODE']);
        $this->assertStringContainsString('--dump-taint-graph requires --taint-analysis', $result['STDERR']);
        $this->assertFileDoesNotExist($this->dir . '/taints.dot');
    }

    public function testRunsWithoutAConfigFileLikeTaintAnalysis(): void
    {
        unlink($this->dir . '/psalm.xml');
        self::writeSource($this->dir, 'Tainted.php', self::TAINTED_AND_UNUSED);

        $process = new Process(
            [PHP_BINARY, __DIR__ . '/../../psalm', '--no-cache', '--no-progress', '--output-format=json',
                '--combined-taint-analysis'],
            $this->dir,
            self::env([]),
        );
        $process->run();

        $this->assertSame(2, $process->getExitCode(), $process->getErrorOutput());
        $this->assertStringContainsString('"type":"TaintedHtml"', $process->getOutput());
    }

    /**
     * @return list<string> the Tainted* issues of a JSON report, every field, one JSON string each
     */
    private static function taintedIssueObjects(string $json): array
    {
        /** @var list<array{type: string}> $issues */
        $issues = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $tainted = [];
        foreach ($issues as $issue) {
            if (str_starts_with($issue['type'], 'Tainted')) {
                $tainted[] = json_encode($issue, JSON_THROW_ON_ERROR);
            }
        }
        sort($tainted);

        return $tainted;
    }

    /** @return list<int> */
    private static function childPids(int $pid): array
    {
        $children = (string) @file_get_contents("/proc/$pid/task/$pid/children");

        return array_values(array_map('intval', array_filter(explode(' ', trim($children)))));
    }

    private static function unusedHandler(string $type): string
    {
        $where = $type === 'TaintedSql' ? '/src/' : '/src/Query0.php';
        return self::$shared . '/psalm.xml:0:0:UnusedIssueHandlerSuppression:Suppressed issue type "' . $type
            . '" for ' . $where . ' was not thrown.:error';
    }

    /**
     * @param list<string> $issues
     * @return list<string>
     */
    private static function ofKind(array $issues, bool $tainted): array
    {
        return array_values(array_filter(
            $issues,
            static fn(string $issue): bool => str_contains($issue, ':Tainted') === $tainted,
        ));
    }

    /**
     * @param list<string> $issues
     * @return list<string>
     */
    private static function without(array $issues, string $issue): array
    {
        return array_values(array_filter($issues, static fn(string $i): bool => $i !== $issue));
    }

    /**
     * @param list<string> $issues
     * @return list<string>
     */
    private static function sorted(array $issues): array
    {
        sort($issues);
        return $issues;
    }

    /**
     * @template T
     * @param callable():(T|null) $check
     * @return T|null
     */
    private static function waitFor(callable $check): mixed
    {
        for ($i = 0; $i < 200; $i++) {
            $value = $check();
            if ($value !== null) {
                return $value;
            }
            usleep(50_000);
        }
        return null;
    }

    /**
     * @return list<string>
     */
    private static function resultFiles(string $dir): array
    {
        $files = glob($dir . '/tmp/psalm-taint-pass-*');
        return $files === false ? [] : $files;
    }

    private static function makeProject(string $name, string $attributes, string $extra): string
    {
        $dir = self::$root . '/' . str_replace(['\\', ' ', '"'], '_', $name);
        mkdir($dir);
        mkdir($dir . '/src');
        mkdir($dir . '/tmp');
        self::writeConfig($dir, $attributes, $extra);
        return $dir;
    }

    private static function writeConfig(string $dir, string $attributes, string $extra): void
    {
        file_put_contents(
            $dir . '/psalm.xml',
            str_replace(['ATTRIBUTES', 'EXTRA'], [$attributes, $extra], self::CONFIG),
        );
    }

    private static function writeSource(string $dir, string $name, string $contents): void
    {
        file_put_contents($dir . '/src/' . $name, $contents);
    }

    /**
     * @param array<string, string> $env
     * @return array<string, string|false>
     */
    private static function env(array $env): array
    {
        return array_merge(
            ['NO_COLOR' => false, 'GITHUB_ACTIONS' => false, 'GITHUB_WORKFLOW' => false, 'CLAUDECODE' => false],
            $env,
        );
    }

    /**
     * @param list<string> $args
     * @param array<string, string> $env
     */
    private static function psalmProcess(string $dir, array $args, array $env = []): Process
    {
        return new Process(
            array_merge([PHP_BINARY, __DIR__ . '/../../psalm', '-c=' . $dir . '/psalm.xml', '--no-progress'], $args),
            $dir,
            // the taint pass's result file goes to the project's own tmp/, so tests can look for it
            self::env(array_merge(['TMPDIR' => $dir . '/tmp'], $env)),
        );
    }

    /**
     * @param list<string> $args
     * @param array<string, string> $env
     * @return array{STDOUT: string, STDERR: string, CODE: int|null}
     */
    private static function runPsalm(string $dir, array $args, array $env = []): array
    {
        $process = self::psalmProcess($dir, $args, $env);
        $process->run();

        return [
            'STDOUT' => $process->getOutput(),
            'STDERR' => $process->getErrorOutput(),
            'CODE' => $process->getExitCode(),
        ];
    }

    /**
     * @param list<string> $args
     * @return array{issues: list<string>, code: int|null, stdout: string}
     *     issues as "file:line:column:type:message:severity", sorted
     */
    private static function runJson(string $dir, array $args): array
    {
        $result = self::runPsalm($dir, array_merge(['--output-format=json'], $args));
        try {
            /** @var mixed $decoded */
            $decoded = json_decode($result['STDOUT'], true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException('Psalm did not print JSON: ' . $result['STDERR'] . $result['STDOUT'], 0, $e);
        }
        assert(is_array($decoded), $result['STDERR']);

        $issues = array_map(
            static function (mixed $issue): string {
                assert(is_array($issue));
                /** @var array{file_name: string, line_from: int, column_from: int, type: string,
                 *     message: string, severity: string} $issue */
                return implode(':', [
                    $issue['file_name'],
                    $issue['line_from'],
                    $issue['column_from'],
                    $issue['type'],
                    $issue['message'],
                    $issue['severity'],
                ]);
            },
            $decoded,
        );

        return ['issues' => self::sorted(array_values($issues)), 'code' => $result['CODE'], 'stdout' => $result['STDOUT']];
    }

    private static function removeDirectory(string $dir): void
    {
        $entries = glob($dir . '/{,.}[!.,!..]*', GLOB_BRACE);
        foreach ($entries === false ? [] : $entries as $entry) {
            is_dir($entry) ? self::removeDirectory($entry) : unlink($entry);
        }
        if (file_exists($dir)) {
            rmdir($dir);
        }
    }
}
