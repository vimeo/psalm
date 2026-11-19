<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\ExecutionEnvironment;

use Psalm\Internal\ExecutionEnvironment\SystemCommandExecutor;
use Psalm\Tests\TestCase;
use RuntimeException;

use function dirname;
use function escapeshellarg;
use function proc_close;
use function proc_open;
use function stream_get_contents;
use function var_export;

use const PHP_BINARY;

final class SystemCommandExecutorTest extends TestCase
{
    public function testExecuteReturnsTheOutputOfTheCommand(): void
    {
        self::assertSame(
            ['hello'],
            (new SystemCommandExecutor())->execute(escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg("echo 'hello';")),
        );
    }

    public function testExecuteThrowsWhenTheCommandFails(): void
    {
        $this->expectException(RuntimeException::class);

        (new SystemCommandExecutor())->execute(escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg('exit(3);'));
    }

    public function testExecuteDoesNotPassTheStderrOfAFailingCommandOn(): void
    {
        $command = escapeshellarg(PHP_BINARY)
            . ' -r '
            . escapeshellarg("fwrite(STDERR, 'fatal: not a git repository'); exit(128);");

        $direct = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $direct_pipes);
        self::assertIsResource($direct);
        $direct_stderr = (string) stream_get_contents($direct_pipes[2]);
        stream_get_contents($direct_pipes[1]);
        proc_close($direct);
        self::assertSame('fatal: not a git repository', $direct_stderr);

        $script = 'require ' . var_export(dirname(__DIR__, 3) . '/vendor/autoload.php', true) . ';
            try {
                (new Psalm\Internal\ExecutionEnvironment\SystemCommandExecutor())->execute('
                    . var_export($command, true) . ');
            } catch (RuntimeException) {
            }';

        $process = proc_open(
            [PHP_BINARY, '-r', $script],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
        );

        self::assertIsResource($process);

        $stderr = (string) stream_get_contents($pipes[2]);
        stream_get_contents($pipes[1]);
        proc_close($process);

        self::assertSame('', $stderr);
    }
}
