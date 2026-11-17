<?php

declare(strict_types=1);

namespace Psalm\Tests\EndToEnd;

use FilesystemIterator;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Psalm\Overlay\OverlayException;
use Psalm\Overlay\OverlayOptions;
use Psalm\Overlay\OverlaySession;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

use function array_column;
use function array_fill;
use function array_keys;
use function assert;
use function count;
use function defined;
use function dirname;
use function explode;
use function extension_loaded;
use function fclose;
use function file_put_contents;
use function function_exists;
use function getenv;
use function glob;
use function hash_file;
use function implode;
use function is_dir;
use function is_resource;
use function json_decode;
use function json_encode;
use function ksort;
use function microtime;
use function mkdir;
use function preg_replace;
use function proc_close;
use function proc_open;
use function realpath;
use function rmdir;
use function sort;
use function str_contains;
use function str_repeat;
use function str_replace;
use function stream_get_contents;
use function substr;
use function sys_get_temp_dir;
use function tempnam;
use function time;
use function touch;
use function unlink;
use function usleep;
use function var_export;

use const GLOB_ONLYDIR;
use const JSON_THROW_ON_ERROR;
use const PHP_BINARY;

/**
 * `psalm --overlay`, run as a process the way Infection runs it
 */
final class OverlayEndToEndTest extends TestCase
{
    use PsalmRunnerTrait;

    private const CONFIG = <<<'XML'
        <?xml version="1.0"?>
        <psalm errorLevel="1" findUnusedCode="false" runTaintAnalysis="false" cacheDirectory="cache">
            <projectFiles><directory name="src" /></projectFiles>
        </psalm>
        XML;

    private const A = <<<'PHP'
        <?php
        namespace App;

        class A
        {
            public function foo(): int
            {
                return 1;
            }
        }
        PHP;

    private const B = <<<'PHP'
        <?php
        namespace App;

        final class B
        {
            public function useA(A $a): int
            {
                return $a->foo();
            }
        }
        PHP;

    private const USE_FN = '<?php namespace App; final class UseFn { public function f(): int { return helper(1); } }';

    private string $project;

    /** What the server wrote after the last message read */
    private string $server_output = '';

    #[Override]
    public function setUp(): void
    {
        $project = tempnam(sys_get_temp_dir(), 'PsalmOverlayTest_');
        assert($project !== false);
        unlink($project);
        $this->project = $project;
        mkdir($this->project . '/src', 0777, true);
        file_put_contents($this->project . '/composer.json', '{"autoload": {"psr-4": {"App\\\\": "src/"}}}');
        self::newProcess(['composer', 'dump-autoload', '--quiet'], $this->project)->mustRun();
        file_put_contents($this->project . '/psalm.xml', self::CONFIG);
    }

    #[Override]
    public function tearDown(): void
    {
        self::removeDirectory($this->project);
    }

    public function testNewErrorExitsTwoWithTheEnvelope(): void
    {
        $this->writeFiles(['A.php' => self::A, 'B.php' => self::B]);
        $this->recordReferences();

        [$code, $envelope] = $this->overlay(['src/A.php' => str_replace('public function', 'private function', self::A)]);

        $this->assertSame(2, $code);
        $this->assertSame(['protocol', 'new_issues', 'analyzed_files', 'not_checked'], array_keys($envelope));
        $this->assertSame(1, $envelope['protocol']);
        $this->assertSame(['src/A.php', 'src/B.php'], $envelope['analyzed_files']);
        $this->assertSame(['unused_code'], $envelope['not_checked']);
        $this->assertSame([['InaccessibleMethod', 'src/B.php', 'error']], self::summarize($envelope));
    }

    public function testNoNewErrorExitsZero(): void
    {
        $this->writeFiles(['A.php' => self::A, 'B.php' => self::B]);
        $this->recordReferences();

        [$code, $envelope] = $this->overlay(['src/A.php' => "<?php\n// shifted\n" . substr(self::A, 6)]);

        $this->assertSame(0, $code);
        $this->assertSame([], $envelope['new_issues']);
    }

    public function testPreExistingErrorIsNotNew(): void
    {
        $with_error = str_replace('return 1;', 'return "1";', self::A);
        $this->writeFiles(['A.php' => $with_error, 'B.php' => self::B]);
        $this->recordReferences();

        // the line of the issue moves, its snippet changes: neither makes it new
        [$code, $envelope] = $this->overlay([
            'src/A.php' => str_replace('return "1";', "\n        return  \"1\";", $with_error),
        ]);

        $this->assertSame(0, $code);
        $this->assertSame([], $envelope['new_issues']);
    }

    public function testNoForkPathGivesTheSameAnswer(): void
    {
        $this->writeFiles(['A.php' => self::A, 'B.php' => self::B]);
        $this->recordReferences();
        $overlay = ['src/A.php' => str_replace('public function', 'private function', self::A)];

        $this->assertSame(
            $this->overlay($overlay),
            $this->overlay($overlay, ['PSALM_OVERLAY_NO_FORK' => '1']),
        );
    }

    public function testServerAnswersLikeTheCommand(): void
    {
        $this->skipWithoutServer();
        $this->writeFiles(['A.php' => self::A, 'B.php' => self::B]);
        $this->recordReferences();
        $overlay = ['src/A.php' => str_replace('public function', 'private function', self::A)];
        [, $expected] = $this->overlay($overlay);
        $params = ['overlays' => [['path' => 'src/A.php', 'contents' => $overlay['src/A.php']]]];

        $input = new InputStream();
        $server = self::newProcess(
            [PHP_BINARY, $this->psalm, '--overlay-server', '--threads=2', '--no-progress'],
            $this->project,
            self::agentEnvVarsToUnset(),
        );
        $server->setInput($input);
        $server->start();

        $ask = function (array $message) use ($input, $server): array {
            $input->write(json_encode(['jsonrpc' => '2.0', ...$message], JSON_THROW_ON_ERROR) . "\n");

            return $this->readMessage($server);
        };

        $initialized = $ask(['id' => 1, 'method' => 'initialize', 'params' => ['protocol' => 1]]);
        $this->assertSame(1, $initialized['result']['protocol'] ?? null, json_encode($initialized, JSON_THROW_ON_ERROR));

        $this->assertSame($expected, $ask(['id' => 2, 'method' => 'analyze', 'params' => $params])['result'] ?? null);

        // a new snapshot answers from then on, the same way
        $this->assertSame([], $ask(['id' => 3, 'method' => 'projectChanged', 'params' => []])['result'] ?? null);
        $this->assertSame($expected, $ask(['id' => 4, 'method' => 'analyze', 'params' => $params])['result'] ?? null);

        $deleted = $ask(['id' => 5, 'method' => 'analyze', 'params' => [
            'overlays' => [['path' => 'src/A.php', 'deleted' => true]],
        ]]);
        $this->assertSame('unsupported', $deleted['error']['data']['kind'] ?? null);

        // an id still in flight is not taken again
        $analyze = ['id' => 7, 'method' => 'analyze', 'params' => $params];
        $input->write(self::encodeMessage($analyze) . self::encodeMessage($analyze));
        $this->assertSame('invalid_request', $this->readMessage($server)['error']['data']['kind'] ?? null);
        $this->assertSame($expected, $this->readMessage($server)['result'] ?? null);

        $ask(['id' => 6, 'method' => 'shutdown']);
        $input->close();
        $server->wait();
        $this->assertSame(0, $server->getExitCode(), $server->getErrorOutput());
    }

    /**
     * The next message of the server
     *
     * @return array{id?: int|string|null, result?: array<array-key, mixed>, error?: array{data: array{kind: string}}}
     */
    private function readMessage(Process $server): array
    {
        $deadline = microtime(true) + 120.0;
        while (!str_contains($this->server_output, "\n") && microtime(true) < $deadline) {
            $this->server_output .= $server->getIncrementalOutput();
            usleep(10_000);
        }

        [$line, $this->server_output] = explode("\n", $this->server_output, 2) + ['', ''];

        /** @var array{id?: int|string|null, result?: array<array-key, mixed>, error?: array{data: array{kind: string}}} */
        return json_decode($line, true, 512, JSON_THROW_ON_ERROR);
    }

    public function testSessionAnswersLikeTheCommand(): void
    {
        $this->writeFiles(['A.php' => self::A, 'B.php' => self::B]);
        $this->recordReferences();
        $overlay = ['src/A.php' => str_replace('public function', 'private function', self::A)];
        [, $expected] = $this->overlay($overlay);

        $session = OverlaySession::open(new OverlayOptions($this->project, command: [PHP_BINARY, $this->psalm]));

        try {
            $result = $session->analyze($overlay);

            $this->assertTrue($result->hasNewErrors());
            $this->assertSame(
                $expected,
                json_decode(json_encode($result, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR),
            );

            try {
                $session->analyze(['src/A.php' => null]);
                $this->fail('a deleted file is not supported yet');
            } catch (OverlayException $e) {
                $this->assertSame('unsupported', $e->kind);
            }
        } finally {
            $session->close();
        }
    }

    public function testServerWithoutForkAnswersUnsupportedAndTheSessionRunsTheCommand(): void
    {
        $this->writeFiles(['A.php' => self::A, 'B.php' => self::B]);
        $this->recordReferences();
        $overlay = ['src/A.php' => str_replace('public function', 'private function', self::A)];
        [, $expected] = $this->overlay($overlay);
        // a PHP that cannot fork, as on Windows
        $command = [PHP_BINARY, '-d', 'disable_functions=pcntl_fork', $this->psalm];

        [$server, $input] = $this->startServer([...$command, '--overlay-server']);
        $initialized = $this->ask($server, $input, ['id' => 1, 'method' => 'initialize', 'params' => ['protocol' => 1]]);
        $this->assertSame('unsupported', $initialized['error']['data']['kind'] ?? null);
        $this->stopServer($server, $input);

        $session = OverlaySession::open(new OverlayOptions($this->project, command: $command));
        try {
            $this->assertSame(
                $expected,
                json_decode(json_encode($session->analyze($overlay), JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR),
            );
        } finally {
            $session->close();
        }
    }

    public function testSignatureChangeAnalysesTheDependentsTheIndexNames(): void
    {
        $a = '<?php namespace App; class A { public function foo(): int { return 1; } }';
        $this->writeFiles([
            'A.php' => $a,
            'B.php' => '<?php namespace App; final class B { public function f(A $a): int { return $a->foo(); } }',
            'C.php' => '<?php namespace App; final class C { public function f(): int { return 1; } }',
        ]);
        $this->recordReferences();
        $overlay = ['src/A.php' => str_replace('foo(): int { return 1; }', 'foo(): string { return ""; }', $a)];

        [, $envelope] = $this->overlay($overlay);
        // C references nothing of A
        $this->assertSame(['src/A.php', 'src/B.php'], $envelope['analyzed_files']);
        $this->assertSame(
            [['InvalidReturnStatement', 'src/B.php', 'error'], ['InvalidReturnType', 'src/B.php', 'error']],
            self::summarize($envelope),
        );

        // every project file, to compare with
        [, $all_files] = $this->overlay($overlay, ['PSALM_OVERLAY_SIGNATURE_SCOPE' => 'project']);
        $this->assertSame(['src/A.php', 'src/B.php', 'src/C.php'], $all_files['analyzed_files']);
        $this->assertSame($envelope['new_issues'], $all_files['new_issues']);
    }

    public function testVisibilityChangeAnalysesTheReferencesOfTheMember(): void
    {
        $a = '<?php namespace App; class A { public function foo(): int { return 1; } public function bar(): void {} }';
        $this->writeFiles([
            'A.php' => $a,
            'B.php' => '<?php namespace App; final class B { public function f(A $a): int { return $a->foo(); } }',
            'C.php' => '<?php namespace App; final class C { public function f(A $a): void { $a->bar(); } }',
        ]);
        $this->recordReferences();

        $process = self::newProcess(
            [PHP_BINARY, $this->psalm, '--overlays-from=-', '--output-format=json', '--no-progress', '--threads=1'],
            $this->project,
            [...self::agentEnvVarsToUnset(), 'PSALM_OVERLAY_VERIFY' => '1'],
            json_encode(['overlays' => [[
                'path' => 'src/A.php',
                'contents' => str_replace('public function foo', 'private function foo', $a),
            ]]], JSON_THROW_ON_ERROR),
        );
        $process->run();
        /** @var array{analyzed_files: list<string>, new_issues: list<array<string, mixed>>} $envelope */
        $envelope = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);

        // C references A, but not foo()
        $this->assertSame(['src/A.php', 'src/B.php'], $envelope['analyzed_files']);
        $this->assertSame([['InaccessibleMethod', 'src/B.php', 'error']], self::summarize($envelope));
        // the answer every file that references A gives
        $this->assertStringContainsString('"verify":"same"', $process->getErrorOutput());
    }

    public function testCacheIsNotWritten(): void
    {
        $this->writeFiles(['A.php' => self::A, 'B.php' => self::B]);
        $this->recordReferences();
        $before = $this->cacheInventory();

        $this->overlay(['src/A.php' => str_replace('public function', 'private function', self::A)]);

        $this->assertSame($before, $this->cacheInventory());
    }

    public function testPreloadedStubs(): void
    {
        file_put_contents(
            $this->project . '/preload.phpstub',
            '<?php class PreloadBase { public function base(): int { return 1; } }',
        );
        file_put_contents($this->project . '/psalm.xml', str_replace(
            '</psalm>',
            '<stubs><file name="preload.phpstub" preloadClasses="true" /></stubs></psalm>',
            self::CONFIG,
        ));
        $a = '<?php namespace App; final class A extends \PreloadBase { public function f(): int { return $this->base(); } }';
        $this->writeFiles(['A.php' => $a]);
        $this->recordReferences();

        // the snapshot is scanned only, each pass populates it
        [$code, , $stderr] = $this->overlay(
            ['src/A.php' => str_replace('return $this->base();', 'return $this->base() + 1;', $a)],
            ['PSALM_OVERLAY_DEBUG' => '1'],
        );
        $this->assertSame(0, $code);
        $this->assertStringContainsString('"populated_after_scan":0}', $stderr);

        $process = self::newProcess(
            [PHP_BINARY, $this->psalm, '--overlay=preload.phpstub=preload.phpstub', '--output-format=json', '--no-progress'],
            $this->project,
            self::agentEnvVarsToUnset(),
        );
        $process->run();
        $this->assertSame(255, $process->getExitCode());
        $this->assertSame('invalid_overlay', self::decodeError($process->getOutput()));
    }

    public function testConfigInASubdirectory(): void
    {
        mkdir($this->project . '/cfg');
        file_put_contents(
            $this->project . '/cfg/psalm.xml',
            str_replace(['"src"', '"cache"'], ['"../src"', '"../cache"'], self::CONFIG),
        );
        unlink($this->project . '/psalm.xml');
        file_put_contents(
            $this->project . '/composer.json',
            '{"autoload": {"psr-4": {"App\\\\": "src/"}, "files": ["src/functions.php"]}}',
        );
        self::newProcess(['composer', 'dump-autoload', '--quiet'], $this->project)->mustRun();
        $this->writeFiles([
            'functions.php' => '<?php namespace App; function helper(int $i): int { return $i; }',
            'UseFn.php' => '<?php namespace App; final class UseFn { public function f(): int { return helper(1); } }',
        ]);
        // a relative path, as `vendor/bin/psalm` is, to a script on the same drive as the project
        mkdir($this->project . '/vendor/bin', 0777, true);
        file_put_contents($this->project . '/vendor/bin/psalm', '<?php require_once '
            . var_export(Path::join((string) realpath(dirname($this->psalm)), 'src/Psalm/Internal/Cli/Psalm.php'), true)
            . '; \Psalm\Internal\Cli\Psalm::run($argv);');
        $command = [PHP_BINARY, 'vendor/bin/psalm', '-c', 'cfg/psalm.xml', '--no-progress', '--threads=1'];

        $record = self::newProcess([...$command, '--record-references'], $this->project, self::agentEnvVarsToUnset());
        $record->run();
        $this->assertContains($record->getExitCode(), [0, 2], $record->getErrorOutput());

        // a new function signature takes a new process, which runs where this one started
        $params = ['overlays' => [[
            'path' => 'src/functions.php',
            'contents' => '<?php namespace App; function helper(int $i): string { return (string) $i; }',
        ]]];
        $process = self::newProcess(
            [...$command, '--overlays-from=-', '--output-format=json'],
            $this->project,
            self::agentEnvVarsToUnset(),
            json_encode($params, JSON_THROW_ON_ERROR),
        );
        $process->run();
        $this->assertSame(2, $process->getExitCode(), $process->getErrorOutput() . $process->getOutput());
        /** @var array<string, mixed> $envelope */
        $envelope = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(
            ['InvalidReturnStatement', 'InvalidReturnType'],
            array_column(self::summarize($envelope), 0),
        );

        // relative paths name the same files on every transport
        if (self::serverRuns()) {
            [$server, $input] = $this->startServer([...$command, '--overlay-server']);
            $this->ask($server, $input, ['id' => 1, 'method' => 'initialize', 'params' => ['protocol' => 1]]);
            $answer = $this->ask($server, $input, ['id' => 2, 'method' => 'analyze', 'params' => $params]);
            $this->assertSame($envelope, $answer['result'] ?? $answer);
            $this->stopServer($server, $input);
        }
    }

    public function testDeadlineCountsFromTheStart(): void
    {
        $this->writeFiles(['A.php' => self::A, 'B.php' => self::B]);
        $this->recordReferences();

        $process = self::newProcess(
            [PHP_BINARY, $this->psalm, '--overlay=src/A.php=src/B.php', '--overlay-timeout=0.25', '--output-format=json'],
            $this->project,
            self::agentEnvVarsToUnset(),
        );
        $start = microtime(true);
        $process->run();
        $elapsed = microtime(true) - $start;

        $this->assertSame(255, $process->getExitCode());
        $this->assertSame('timeout', self::decodeError($process->getOutput()));
        // the configuration and the scan alone take longer; without pcntl PHP's time limit counts whole seconds
        $this->assertLessThan(self::hasWatchdog() ? 0.25 + 0.5 : 1.0 + 1.0, $elapsed);
    }

    /**
     * Whether the deadline interrupts a run within milliseconds, see OverlayExitGuard
     *
     * @psalm-pure
     */
    private static function hasWatchdog(): bool
    {
        return function_exists('pcntl_fork') && function_exists('pcntl_async_signals') && function_exists('posix_kill');
    }

    /**
     * Where it cannot fork, `--overlay-server` answers every request with `unsupported`, see
     * testServerWithoutForkAnswersUnsupportedAndTheSessionRunsTheCommand()
     */
    private function skipWithoutServer(): void
    {
        if (!self::serverRuns()) {
            $this->markTestSkipped('--overlay-server needs pcntl and posix (Linux or macOS)');
        }
    }

    /**
     * @psalm-pure
     */
    private static function serverRuns(): bool
    {
        return !defined('PHP_WINDOWS_VERSION_MAJOR')
            && extension_loaded('pcntl')
            && extension_loaded('posix')
            && function_exists('pcntl_fork');
    }

    public function testServerAnswersPipelinedRequestsLargerThanThePipes(): void
    {
        $this->skipWithoutServer();
        $methods = '';
        for ($i = 0; $i < 300; $i++) {
            $methods .= "    public function m$i(): int { return $i; }\n";
        }
        $a = "<?php\nnamespace App;\n\nfinal class A\n{\n" . $methods . "}\n";
        $this->writeFiles(['A.php' => $a]);
        $this->recordReferences();

        // each answer has 600 issues, each request a comment of 200 KB
        $overlay = (string) preg_replace('/return (\d+);/', 'return "$1";', $a) . '// ' . str_repeat('x', 200_000);
        $requests = 8;
        [$server, $input] = $this->startServer([PHP_BINARY, $this->psalm, '--overlay-server', '--threads=2']);
        $lines = self::encodeMessage(['id' => 0, 'method' => 'initialize', 'params' => ['protocol' => 1]]);
        for ($id = 1; $id <= $requests; $id++) {
            $lines .= self::encodeMessage(['id' => $id, 'method' => 'analyze', 'params' => [
                'overlays' => [['path' => 'src/A.php', 'contents' => $overlay]],
            ]]);
        }
        $input->write($lines);

        $answered = [];
        for ($i = 0; $i <= $requests; $i++) {
            $message = $this->readMessage($server);
            $answered[(int) ($message['id'] ?? -1)] = count((array) ($message['result']['new_issues'] ?? []));
        }
        ksort($answered);

        $this->assertSame([0, ...array_fill(1, $requests, 600)], $answered);
        $this->stopServer($server, $input);
    }

    public function testServerAnswersWithTheConfigErrorOfTheSnapshot(): void
    {
        $this->skipWithoutServer();
        $this->writeFiles(['A.php' => self::A]);
        file_put_contents($this->project . '/psalm.xml', '<?xml version="1.0"?><psalm><notAnOption /></psalm>');

        [$server, $input] = $this->startServer([PHP_BINARY, $this->psalm, '--overlay-server']);
        $initialized = $this->ask($server, $input, ['id' => 1, 'method' => 'initialize', 'params' => ['protocol' => 1]]);
        $this->assertSame('config', $initialized['error']['data']['kind'] ?? null);

        $analyzed = $this->ask($server, $input, ['id' => 2, 'method' => 'analyze', 'params' => [
            'overlays' => [['path' => 'src/A.php', 'contents' => self::A]],
        ]]);
        $this->assertSame('config', $analyzed['error']['data']['kind'] ?? null);
        $this->stopServer($server, $input);
    }

    public function testServerAnswersConfigForABaselineItCannotRead(): void
    {
        $this->skipWithoutServer();
        $this->writeFiles(['A.php' => self::A, 'B.php' => self::B]);
        $this->recordReferences();

        [$server, $input] = $this->startServer(
            [PHP_BINARY, $this->psalm, '--overlay-server', '--use-baseline=missing.xml'],
        );
        $analyzed = $this->ask($server, $input, ['id' => 1, 'method' => 'analyze', 'params' => [
            'overlays' => [['path' => 'src/A.php', 'contents' => self::A]],
        ]]);

        $this->assertSame('config', $analyzed['error']['data']['kind'] ?? null);
        $this->assertStringContainsString(
            'Could not read the baseline: missing.xml does not exist or is not readable.',
            json_encode($analyzed, JSON_THROW_ON_ERROR),
        );
        $this->stopServer($server, $input);
    }

    #[TestWith(['--clear-cache'])]
    #[TestWith(['--consolidate-cache'])]
    public function testCacheCommandsSayTheyWaitForTheServer(string $option): void
    {
        $this->skipWithoutServer();
        $this->writeFiles(['A.php' => self::A, 'B.php' => self::B]);
        $this->recordReferences();
        [$server, $input] = $this->startServer([PHP_BINARY, $this->psalm, '--overlay-server']);
        $initialized = $this->ask($server, $input, ['id' => 1, 'method' => 'initialize', 'params' => ['protocol' => 1]]);
        $this->assertSame(1, $initialized['result']['protocol'] ?? null);

        $command = self::newProcess([PHP_BINARY, $this->psalm, $option], $this->project, self::agentEnvVarsToUnset());
        $command->start();
        $deadline = microtime(true) + 10.0;
        while (!str_contains($command->getErrorOutput(), 'Waiting for other Psalm processes')
            && $command->isRunning()
            && microtime(true) < $deadline
        ) {
            usleep(10_000);
        }
        $waiting = $command->isRunning();
        $stderr = $command->getErrorOutput();

        $this->stopServer($server, $input);
        $command->wait();

        $this->assertTrue($waiting, 'the snapshot holds the cache until it stops');
        $this->assertStringContainsString(
            'Waiting for other Psalm processes (for example `psalm --overlay-server`) to release the cache',
            $stderr,
        );
        $this->assertSame(0, $command->getExitCode(), $command->getErrorOutput());
    }

    public function testNotCheckedListsWhatTheConfigRuns(): void
    {
        file_put_contents($this->project . '/psalm.xml', str_replace(
            '</psalm>',
            '<issueHandlers><ClassMustBeFinal errorLevel="suppress" /></issueHandlers></psalm>',
            self::CONFIG,
        ));
        $this->writeFiles(['A.php' => self::A, 'B.php' => self::B]);
        $this->recordReferences();

        [, $envelope] = $this->overlay(['src/A.php' => str_replace('public function', 'private function', self::A)]);

        $this->assertSame([], $envelope['not_checked']);
    }

    public function testServerDeadlineCountsWhileTheSnapshotStarts(): void
    {
        $this->skipWithoutServer();
        $this->writeSlowProject();
        $this->recordReferences();

        [$server, $input] = $this->startServer([PHP_BINARY, $this->psalm, '--overlay-server'], 'all');
        $start = microtime(true);
        $answer = $this->ask($server, $input, ['id' => 1, 'method' => 'analyze', 'params' => [
            'overlays' => [['path' => 'src/UseFn.php', 'contents' => self::USE_FN]],
            'timeout_ms' => 100,
        ]]);

        $this->assertSame('timeout', $answer['error']['data']['kind'] ?? null);
        $this->assertLessThan(0.1 + 0.5, microtime(true) - $start);
        $this->stopServer($server, $input);
    }

    public function testServerDeadlineCountsWhileTheRequestWaits(): void
    {
        $this->skipWithoutServer();
        $this->writeSlowProject();
        $this->recordReferences();

        [$server, $input] = $this->startServer([PHP_BINARY, $this->psalm, '--overlay-server', '--threads=1'], 'fresh');
        $this->ask($server, $input, ['id' => 1, 'method' => 'initialize', 'params' => ['protocol' => 1]]);

        // a new function signature takes a fresh process, which starts slowly, and the only thread
        $input->write(self::encodeMessage(['id' => 2, 'method' => 'analyze', 'params' => [
            'overlays' => [['path' => 'src/functions.php', 'contents' => self::slowFunctions('string')]],
        ]]));
        $start = microtime(true);
        $answer = $this->ask($server, $input, ['id' => 3, 'method' => 'analyze', 'params' => [
            'overlays' => [['path' => 'src/UseFn.php', 'contents' => self::USE_FN]],
            'timeout_ms' => 100,
        ]]);

        $this->assertSame(3, $answer['id'] ?? null);
        $this->assertSame('timeout', $answer['error']['data']['kind'] ?? null);
        $this->assertLessThan(0.1 + 0.5, microtime(true) - $start);
        $this->readMessage($server);
        $this->stopServer($server, $input);
    }

    public function testServerAnswersAReusedIdWithItsOwnAnswer(): void
    {
        $this->skipWithoutServer();
        $this->writeSlowProject();
        $this->recordReferences();

        [$server, $input] = $this->startServer([PHP_BINARY, $this->psalm, '--overlay-server'], 'all');
        $timed_out = $this->ask($server, $input, ['id' => 7, 'method' => 'analyze', 'params' => [
            'overlays' => [['path' => 'src/UseFn.php', 'contents' => self::USE_FN]],
            'timeout_ms' => 50,
        ]]);
        $this->assertSame('timeout', $timed_out['error']['data']['kind'] ?? null);

        // the id has its answer: a new request may take it, and the late answer to the first one is not its answer
        $answer = $this->ask($server, $input, ['id' => 7, 'method' => 'analyze', 'params' => [
            'overlays' => [['path' => 'src/UseFn.php', 'contents' => self::USE_FN]],
        ]]);

        $this->assertSame(7, $answer['id'] ?? null);
        $this->assertSame([], $answer['result']['new_issues'] ?? null, json_encode($answer, JSON_THROW_ON_ERROR));
        $this->stopServer($server, $input);
    }

    public function testServerCancellationStopsTheFreshProcess(): void
    {
        $this->skipWithoutServer();
        $this->writeSlowProject();
        $this->recordReferences();

        // the configuration given by its absolute path, so that the processes of the request can be found
        $config = $this->project . '/psalm.xml';
        [$server, $input] = $this->startServer(
            [PHP_BINARY, $this->psalm, '-c', $config, '--overlay-server', '--threads=1'],
            'fresh',
        );
        $this->ask($server, $input, ['id' => 1, 'method' => 'initialize', 'params' => ['protocol' => 1]]);

        $input->write(self::encodeMessage(['id' => 2, 'method' => 'analyze', 'params' => [
            'overlays' => [['path' => 'src/functions.php', 'contents' => self::slowFunctions('string')]],
            'timeout_ms' => 30_000,
        ]]));
        // once the fresh process runs, and is still starting
        $deadline = microtime(true) + 10.0;
        while (!str_contains($this->getProcessesOf($config), '--overlays-from=-') && microtime(true) < $deadline) {
            // the request reaches the server while its output is read
            $this->server_output .= $server->getIncrementalOutput();
            usleep(50_000);
        }
        $this->assertStringContainsString('--overlays-from=-', $this->getProcessesOf($config));
        $cancelled = $this->ask($server, $input, ['method' => '$/cancel', 'params' => ['id' => 2]]);
        $this->assertSame('cancelled', $cancelled['error']['data']['kind'] ?? null);

        usleep(200_000);
        $this->assertStringNotContainsString('--overlays-from=-', $this->getProcessesOf($config));
        $this->stopServer($server, $input);
    }

    /**
     * The command lines of the processes that name the needle
     */
    private function getProcessesOf(string $needle): string
    {
        $ps = self::newProcess(['ps', '-axww', '-o', 'command='], $this->project);
        $ps->mustRun();

        return self::grepLines($ps->getOutput(), $needle);
    }

    public function testAnAnswerThatCannotBeWrittenExits255(): void
    {
        $this->writeFiles(['A.php' => self::A, 'B.php' => self::B]);
        $this->recordReferences();

        $env = getenv();
        foreach (self::agentEnvVarsToUnset() as $name => $_) {
            unset($env[$name]);
        }
        $process = proc_open(
            [PHP_BINARY, $this->psalm, '--overlay=src/A.php=src/A.php', '--output-format=json', '--no-progress'],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            $this->project,
            $env,
        );
        assert(is_resource($process));
        // nobody reads the answer
        fclose($pipes[0]);
        fclose($pipes[1]);
        $stderr = (string) stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $this->assertSame(255, proc_close($process), $stderr);
    }

    /**
     * @psalm-pure
     */
    private static function grepLines(string $text, string $needle): string
    {
        $lines = [];
        foreach (explode("\n", $text) as $line) {
            if (str_contains($line, $needle)) {
                $lines[] = $line;
            }
        }

        return implode("\n", $lines);
    }

    public function testDeadlineStopsTheFreshProcess(): void
    {
        if (!self::hasWatchdog() || getenv('PSALM_OVERLAY_NO_FORK') === '1') {
            $this->markTestSkipped('No fresh process starts where Psalm does not fork: the passes run in the process');
        }

        $this->writeSlowProject();
        $this->recordReferences();

        // the configuration given by its absolute path, so that the processes of this run can be found
        $config = $this->project . '/psalm.xml';
        $process = self::newProcess(
            [PHP_BINARY, $this->psalm, '-c', $config, '--overlays-from=-', '--output-format=json',
                '--overlay-timeout=1.5', '--no-progress', '--threads=1'],
            $this->project,
            [...self::agentEnvVarsToUnset(), 'PSALM_TEST_SLOW' => 'fresh'],
            json_encode(['overlays' => [[
                'path' => 'src/functions.php',
                'contents' => self::slowFunctions('string'),
            ]]], JSON_THROW_ON_ERROR),
        );
        $start = microtime(true);
        $process->run();

        $this->assertSame(255, $process->getExitCode(), $process->getErrorOutput());
        $this->assertJson($process->getOutput(), $process->getOutput() . $process->getErrorOutput());
        $this->assertSame('timeout', self::decodeError($process->getOutput()));
        $this->assertLessThan(1.5 + 0.5, microtime(true) - $start);
        // nothing it started outlives it
        $ps = self::newProcess(['ps', '-axww', '-o', 'command='], $this->project);
        $ps->mustRun();
        $this->assertStringNotContainsString($config, $ps->getOutput());
    }

    /**
     * Composer loads functions.php into every Psalm process: it sleeps in those that PSALM_TEST_SLOW names
     */
    private function writeSlowProject(): void
    {
        file_put_contents(
            $this->project . '/composer.json',
            '{"autoload": {"psr-4": {"App\\\\": "src/"}, "files": ["src/functions.php"]}}',
        );
        self::newProcess(['composer', 'dump-autoload', '--quiet'], $this->project)->mustRun();
        $this->writeFiles(['functions.php' => self::slowFunctions('int'), 'UseFn.php' => self::USE_FN]);
    }

    /**
     * @psalm-pure
     */
    private static function slowFunctions(string $return_type): string
    {
        return '<?php namespace App;
            $slow = getenv("PSALM_TEST_SLOW");
            if ($slow === "all" || ($slow === "fresh" && getenv("PSALM_OVERLAY_FRESH") === "1")) {
                usleep(2_000_000);
            }
            function helper(int $i): ' . $return_type . ' { return ' . ($return_type === 'int' ? '$i' : '(string) $i')
            . '; }';
    }

    public function testAColdCacheIsNotRecorded(): void
    {
        $this->writeFiles(['A.php' => self::A, 'B.php' => self::B]);
        $this->recordReferences();
        // what another version of Psalm finds: no parser cache of its own
        $parser_caches = glob($this->project . '/cache/*/php-parser', GLOB_ONLYDIR);
        $this->assertNotFalse($parser_caches);
        $this->assertCount(1, $parser_caches);
        self::removeDirectory($parser_caches[0]);

        $this->assertSame(
            ['references_not_recorded', 'The cache has nothing for this config and this version of Psalm: run '
                . '`psalm --record-references` with them first'],
            $this->failure(['--overlay=src/A.php=src/A.php']),
        );
    }

    public function testAMissingBaselineIsAConfigFailure(): void
    {
        $this->writeFiles(['A.php' => self::A, 'B.php' => self::B]);
        $this->recordReferences();

        $this->assertSame(
            ['config', 'Could not read the baseline: missing.xml does not exist or is not readable. '
                . 'Create it with --set-baseline=missing.xml'],
            $this->failure(['--overlay=src/A.php=src/A.php', '--use-baseline=missing.xml']),
        );
    }

    /**
     * @param list<string> $arguments
     * @return array{string, string} the kind and the message of the error the command answers with exit code 255
     */
    private function failure(array $arguments): array
    {
        $process = self::newProcess(
            [PHP_BINARY, $this->psalm, '--output-format=json', '--no-progress', '--threads=1', ...$arguments],
            $this->project,
            self::agentEnvVarsToUnset(),
        );
        $process->run();

        $this->assertSame(255, $process->getExitCode(), $process->getErrorOutput() . $process->getOutput());
        /** @var array{error: array{kind: string, message: string}} $answer */
        $answer = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);

        return [$answer['error']['kind'], $answer['error']['message']];
    }

    /**
     * @param list<string> $command
     * @param string|null $slow see writeSlowProject()
     * @return array{Process, InputStream}
     */
    private function startServer(array $command, ?string $slow = null): array
    {
        $input = new InputStream();
        $server = self::newProcess(
            [...$command, '--no-progress'],
            $this->project,
            [...self::agentEnvVarsToUnset(), 'PSALM_TEST_SLOW' => $slow ?? false],
        );
        $server->setInput($input);
        $server->start();

        return [$server, $input];
    }

    /**
     * @param array<string, mixed> $message
     * @return array{id?: int|string|null, result?: array<array-key, mixed>, error?: array{data: array{kind: string}}}
     */
    private function ask(Process $server, InputStream $input, array $message): array
    {
        $input->write(self::encodeMessage($message));

        return $this->readMessage($server);
    }

    private function stopServer(Process $server, InputStream $input): void
    {
        $this->ask($server, $input, ['id' => 'stop', 'method' => 'shutdown']);
        $input->close();
        $server->wait();
        $this->assertSame(0, $server->getExitCode(), $server->getErrorOutput());
    }

    /**
     * @param array<string, mixed> $message
     * @psalm-pure
     */
    private static function encodeMessage(array $message): string
    {
        return json_encode(['jsonrpc' => '2.0', ...$message], JSON_THROW_ON_ERROR) . "\n";
    }

    /**
     * @param list<string> $arguments
     */
    #[DataProvider('provideFailures')]
    public function testFailuresExit255WithTheirKind(string $kind, bool $record, array $arguments): void
    {
        $this->writeFiles(['A.php' => self::A, 'B.php' => self::B]);
        if ($record) {
            $this->recordReferences();
        }
        $cache = $record ? $this->cacheInventory() : [];

        if ($kind === 'references_stale') {
            touch($this->project . '/src/B.php', time() + 10);
            file_put_contents($this->project . '/src/B.php', self::B . "\n");
        }

        $process = self::newProcess(
            [PHP_BINARY, $this->psalm, '--output-format=json', '--no-progress', '--threads=1', ...$arguments],
            $this->project,
            self::agentEnvVarsToUnset(),
            '{"overlays": [{"path": "src/A.php", "deleted": true}]}',
        );
        $process->run();

        $this->assertSame(255, $process->getExitCode(), $process->getErrorOutput());
        // the output is the answer alone
        $this->assertSame(
            $kind,
            self::decodeError($process->getOutput()),
        );
        if ($record) {
            $this->assertSame($cache, $this->cacheInventory());
        }
    }

    /**
     * @return iterable<string, array{string, bool, list<string>}>
     * @psalm-pure
     */
    public static function provideFailures(): iterable
    {
        yield 'no reference index' => ['references_not_recorded', false, ['--overlay=src/A.php=src/A.php']];
        yield 'stale reference index' => ['references_stale', true, ['--overlay=src/A.php=src/A.php']];
        yield 'config error before it loads' => ['config', true, ['-c', 'missing.xml', '--overlay=src/A.php=src/A.php']];
        yield 'unreadable replacement' => ['invalid_overlay', true, ['--overlay=src/A.php=missing.php']];
        yield 'deleted file' => ['unsupported', true, ['--overlays-from=-']];
        yield 'file that does not exist' => ['unsupported', true, ['--overlay=src/New.php=src/A.php']];
        yield 'path argument' => ['invalid_request', true, ['--overlay=src/A.php=src/A.php', 'src']];
        yield 'baseline update' => ['invalid_request', true, ['--overlay=src/A.php=src/A.php', '--update-baseline']];
        yield 'overlay of the config' => ['invalid_overlay', true, ['--overlay=psalm.xml=psalm.xml']];
        yield 'deadline' => ['timeout', true, ['--overlay=src/A.php=src/B.php', '--overlay-timeout=0.001']];
        yield 'global cache removal' => ['invalid_request', true, ['--overlay=src/A.php=src/A.php', '--clear-global-cache']];
        yield 'cache consolidation' => ['invalid_request', true, ['--overlay=src/A.php=src/A.php', '--consolidate-cache']];
    }

    /**
     * The dependents the reference index finds, and the files a change reaches without an edge
     *
     * @param array<string, string> $files
     * @param array<string, string> $overlays
     * @param list<array{string, string, string}> $expected type, file, severity
     * @param bool $whole_project whether the request asks for every project file
     */
    #[DataProvider('provideDependents')]
    public function testIssuesInDependentFiles(
        array $files,
        array $overlays,
        array $expected,
        bool $whole_project = false,
    ): void {
        $this->writeFiles($files);

        if (isset($files['functions.php'])) {
            // as a project declares its functions
            file_put_contents(
                $this->project . '/composer.json',
                '{"autoload": {"psr-4": {"App\\\\": "src/"}, "files": ["src/functions.php"]}}',
            );
            self::newProcess(['composer', 'dump-autoload', '--quiet'], $this->project)->mustRun();
        }

        $this->recordReferences();

        [, $envelope] = $this->overlay($overlays, [], $whole_project);

        $this->assertSame($expected, self::summarize($envelope));
    }

    /**
     * @return iterable<string, array{0: array<string, string>, 1: array<string, string>,
     *     2: list<array{string, string, string}>, 3?: bool}>
     * @psalm-pure
     */
    public static function provideDependents(): iterable
    {
        $a = '<?php namespace App; class A { public function foo(): void {} public const Y = 10; }';

        yield 'caller in a trait' => [
            [
                'A.php' => $a,
                'T.php' => '<?php namespace App; trait T { public function viaTrait(A $a): void { $a->foo(); } }',
                'C.php' => '<?php namespace App; final class C { use T; }',
            ],
            ['src/A.php' => str_replace('public function foo', 'private function foo', $a)],
            [['InaccessibleMethod', 'src/T.php', 'error']],
        ];

        yield 'constant expression of another class' => [
            [
                'A.php' => $a,
                'B.php' => '<?php namespace App; final class B { public const X = A::Y * 2; }',
                'D.php' => '<?php namespace App; final class D { public function f(): bool { return B::X === 20; } }',
            ],
            ['src/A.php' => str_replace('Y = 10', 'Y = 11', $a)],
            [['TypeDoesNotContainType', 'src/D.php', 'error']],
        ];

        yield 'imported type alias' => [
            [
                'A.php' => '<?php namespace App; /** @psalm-type TA = array{a: int} */ final class A {}',
                'Imp.php' => '<?php namespace App;
                    /** @psalm-import-type TA from A */
                    final class Imp {
                        /** @param TA $x */
                        public function f(array $x): int { return $x["a"]; }
                    }',
            ],
            ['src/A.php' => '<?php namespace App; /** @psalm-type TA = array{a: string} */ final class A {}'],
            [['InvalidReturnStatement', 'src/Imp.php', 'error'], ['InvalidReturnType', 'src/Imp.php', 'error']],
        ];

        yield 'function signature' => [
            [
                'functions.php' => '<?php namespace App; function helper(int $i): int { return $i; }',
                'UseFn.php' => '<?php namespace App; final class UseFn { public function f(): int { return helper(1); } }',
            ],
            ['src/functions.php' => '<?php namespace App; function helper(int $i): string { return (string) $i; }'],
            [['InvalidReturnStatement', 'src/UseFn.php', 'error'], ['InvalidReturnType', 'src/UseFn.php', 'error']],
        ];

        yield 'function signature, whole project' => [
            [
                'functions.php' => '<?php namespace App; function helper(int $i): int { return $i; }',
                'UseFn.php' => '<?php namespace App; final class UseFn { public function f(): int { return helper(1); } }',
            ],
            ['src/functions.php' => '<?php namespace App; function helper(int $i): string { return (string) $i; }'],
            [['InvalidReturnStatement', 'src/UseFn.php', 'error'], ['InvalidReturnType', 'src/UseFn.php', 'error']],
            true,
        ];

        // the process has loaded the file as it is on disk: what the overlay removes is not taken from it
        yield 'function removed from an autoloaded file' => [
            [
                'functions.php' => '<?php
                    if (!function_exists("windows_os")) { function windows_os(): bool { return false; } }
                    if (!function_exists("unix_os")) { function unix_os(): bool { return true; } }',
                'UseFn.php' => '<?php namespace App; final class UseFn { public function f(): bool { return windows_os(); } }',
            ],
            ['src/functions.php' => '<?php
                    if (!function_exists("unix_os")) { function unix_os(): bool { return true; } }'],
            [['MixedReturnStatement', 'src/UseFn.php', 'error'], ['UndefinedFunction', 'src/UseFn.php', 'error']],
        ];

        yield 'constant removed from an autoloaded file' => [
            [
                'functions.php' => '<?php namespace App; const LIMIT = 1; function helper(): int { return 1; }',
                'UseFn.php' => '<?php namespace App; final class UseFn { public function f(): int { return LIMIT; } }',
            ],
            ['src/functions.php' => '<?php namespace App; function helper(): int { return 1; }'],
            [['MixedReturnStatement', 'src/UseFn.php', 'error'], ['UndefinedConstant', 'src/UseFn.php', 'error']],
        ];

        yield 'constant with a computed name removed from an autoloaded file' => [
            [
                'functions.php' => '<?php namespace App; define(__NAMESPACE__ . "\\\\LIMIT", 3); function helper(): int { return 1; }',
                'UseFn.php' => '<?php namespace App; final class UseFn { public function f(): int { return LIMIT; } }',
            ],
            ['src/functions.php' => '<?php namespace App; function helper(): int { return 1; }'],
            [['MixedReturnStatement', 'src/UseFn.php', 'error'], ['UndefinedConstant', 'src/UseFn.php', 'error']],
        ];

        yield 'constant with a computed name renamed in an autoloaded file' => [
            [
                'functions.php' => '<?php namespace App; define(__NAMESPACE__ . "\\\\LIMIT", 3); function helper(): int { return 1; }',
                'UseFn.php' => '<?php namespace App; final class UseFn { public function f(): int { return LIMIT; } }',
            ],
            ['src/functions.php' => '<?php namespace App;
                define(__NAMESPACE__ . "\\\\MAXIMUM", 3); function helper(): int { return 1; }'],
            [['MixedReturnStatement', 'src/UseFn.php', 'error'], ['UndefinedConstant', 'src/UseFn.php', 'error']],
        ];

        yield 'constant with a null value removed from an autoloaded file' => [
            [
                'functions.php' => '<?php namespace App; const OPTIONAL_LIMIT = null; function helper(): int { return 1; }',
                'UseFn.php' => '<?php namespace App; final class UseFn { public function f(): ?int { return OPTIONAL_LIMIT; } }',
            ],
            ['src/functions.php' => '<?php namespace App; function helper(): int { return 1; }'],
            [['MixedReturnStatement', 'src/UseFn.php', 'error'], ['UndefinedConstant', 'src/UseFn.php', 'error']],
        ];

        yield 'constant with a null value renamed in an autoloaded file' => [
            [
                'functions.php' => '<?php namespace App; const OPTIONAL_LIMIT = null; function helper(): int { return 1; }',
                'UseFn.php' => '<?php namespace App; final class UseFn { public function f(): ?int { return OPTIONAL_LIMIT; } }',
            ],
            ['src/functions.php' => '<?php namespace App; const OPTIONAL_MAXIMUM = null; function helper(): int { return 1; }'],
            [['MixedReturnStatement', 'src/UseFn.php', 'error'], ['UndefinedConstant', 'src/UseFn.php', 'error']],
        ];

        // the call is to App\define: PHP_OS stays what PHP defines
        yield 'call removed to a function of the project named define' => [
            [
                'functions.php' => '<?php namespace App;
                    function define(string $name, int $value): void {}
                    define("PHP_OS", 99);
                    function helper(): int { return 1; }',
                'UseFn.php' => '<?php namespace App; final class UseFn { public function f(): string { return PHP_OS; } }',
            ],
            ['src/functions.php' => '<?php namespace App;
                    function define(string $name, int $value): void {}
                    function helper(): int { return 1; }'],
            [],
        ];

        yield 'first-class callables in an ordinary and an autoloaded file' => [
            [
                'functions.php' => '<?php namespace App; function helper(string $s): int { return (strlen(...))($s); }',
                'A.php' => '<?php namespace App; final class A { public function f(): int { return (strlen(...))("a"); } }',
            ],
            [
                'src/functions.php' => '<?php namespace App;
                    function helper(string $s): int { return (strlen(...))($s . "x"); }',
                'src/A.php' => '<?php namespace App;
                    final class A { public function f(): int { return (strlen(...))("b"); } }',
            ],
            [],
        ];

        yield 'new declaration resolves an undefined reference' => [
            [
                'A.php' => $a,
                'UsesMissing.php' => '<?php namespace App;
                    final class UsesMissing {
                        /** @psalm-suppress UndefinedClass, MixedInferredReturnType, MixedReturnStatement */
                        public function f(): int { return Missing::bar(); }
                    }',
            ],
            ['src/A.php' => $a . ' final class Missing { public static function bar(): string { return ""; } }'],
            [['InvalidReturnStatement', 'src/UsesMissing.php', 'error'], ['InvalidReturnType', 'src/UsesMissing.php', 'error']],
        ];

        yield 'property set in the parent constructor' => [
            [
                'P.php' => '<?php namespace App;
                    abstract class P {
                        public function __construct() { $this->configure(); }
                        abstract protected function configure(): void;
                    }',
                'Ch.php' => '<?php namespace App;
                    final class Ch extends P {
                        public int $x;
                        protected function configure(): void { $this->x = 1; }
                    }',
            ],
            ['src/P.php' => '<?php namespace App;
                    abstract class P {
                        public function __construct() {}
                        abstract protected function configure(): void;
                    }'],
            [['PropertyNotSetInConstructor', 'src/Ch.php', 'error']],
        ];

        yield 'variable of a file included through another' => [
            [
                'k.php' => '<?php $x = "a";',
                'j.php' => '<?php require __DIR__ . "/k.php";',
                'i.php' => '<?php require __DIR__ . "/j.php"; echo strlen($x);',
            ],
            ['src/k.php' => '<?php $x = [];'],
            [['InvalidArgument', 'src/i.php', 'error']],
        ];
    }

    /** @param array<string, string> $files */
    private function writeFiles(array $files): void
    {
        foreach ($files as $name => $contents) {
            file_put_contents($this->project . '/src/' . $name, $contents);
        }
    }

    private function recordReferences(): void
    {
        $process = self::newProcess(
            [PHP_BINARY, $this->psalm, '--record-references', '--no-progress', '--threads=1'],
            $this->project,
            self::agentEnvVarsToUnset(),
        );
        $process->run();
        $this->assertContains($process->getExitCode(), [0, 2], $process->getErrorOutput());
    }

    /**
     * @param array<string, string> $overlays original path => new contents
     * @param array<string, string|false> $env
     * @return array{int|null, array<string, mixed>, string} exit code, envelope, STDERR
     */
    private function overlay(array $overlays, array $env = [], bool $whole_project = false): array
    {
        $manifest = [];
        foreach ($overlays as $path => $contents) {
            $manifest[] = ['path' => $path, 'contents' => $contents];
        }

        $process = self::newProcess(
            // with assertions, as CI runs Psalm
            [PHP_BINARY, '-d', 'zend.assertions=1', $this->psalm, '--overlays-from=-', '--output-format=json',
                '--no-progress', '--threads=1'],
            $this->project,
            [...self::agentEnvVarsToUnset(), ...$env],
            json_encode(['overlays' => $manifest, 'whole_project' => $whole_project], JSON_THROW_ON_ERROR),
        );
        $process->run();

        $this->assertContains($process->getExitCode(), [0, 2], $process->getErrorOutput() . $process->getOutput());

        /** @var array<string, mixed> $envelope */
        $envelope = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);

        return [$process->getExitCode(), $envelope, $process->getErrorOutput()];
    }

    /**
     * @param array<string, mixed> $envelope
     * @return list<array{string, string, string}>
     */
    private static function summarize(array $envelope): array
    {
        $summary = [];
        /** @var array{type: string, file_name: string, severity: string} $issue */
        foreach ($envelope['new_issues'] as $issue) {
            $summary[] = [$issue['type'], $issue['file_name'], $issue['severity']];
        }
        sort($summary);

        return $summary;
    }

    /** @return array<string, string> */
    private function cacheInventory(): array
    {
        $inventory = [];
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
            $this->project . '/cache',
            FilesystemIterator::SKIP_DOTS,
        ));
        foreach ($files as $file) {
            assert($file instanceof SplFileInfo);
            $inventory[(string) $file] = (string) hash_file('xxh128', (string) $file) . ' ' . $file->getMTime();
        }
        ksort($inventory);

        return $inventory;
    }

    /**
     * @psalm-pure
     */
    private static function decodeError(string $output): ?string
    {
        /** @var array{error?: array{kind: string}} $answer */
        $answer = json_decode($output, true, 512, JSON_THROW_ON_ERROR);

        return $answer['error']['kind'] ?? null;
    }

    private static function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($files as $file) {
            assert($file instanceof SplFileInfo);
            $file->isDir() ? rmdir((string) $file) : unlink((string) $file);
        }
        rmdir($dir);
    }
}
