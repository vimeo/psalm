<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal;

use PHPUnit\Framework\TestCase;
use Psalm\Internal\Stubs\CallMapVersionFilter;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

use function chmod;
use function dirname;
use function file_get_contents;
use function file_put_contents;
use function getenv;
use function str_repeat;
use function sys_get_temp_dir;
use function uniqid;

use const PHP_OS_FAMILY;

final class CallMapGenerationTest extends TestCase
{
    public function testPolyfillsDoNotBecomeBuiltins(): void
    {
        require_once dirname(__DIR__, 2) . '/bin/stubs/CallMapVersionFilter.php';

        $filter = new CallMapVersionFilter([
            'array_key_first' => ['standard' => true],
            'random_int' => ['standard' => true, 'random' => true],
        ]);
        $entry = [0 => 'mixed'];
        $map = [
            'array_key_first' => $entry,
            "array_key_first'1" => $entry,
            'random_int' => $entry,
            'swoole_string' => $entry,
            'external_api' => $entry,
        ];
        foreach ([[], ['array_key_first' => 'swoole']] as $owners) {
            foreach (['standard', 'random'] as $random_owner) {
                self::assertSame(
                    ['random_int' => $entry, 'swoole_string' => $entry, 'external_api' => $entry],
                    $filter->filter($map, $owners + ['random_int' => $random_owner, 'external_api' => 'redis']),
                );
            }
        }
        self::assertSame($map, $filter->filter($map, [
            'array_key_first' => 'standard',
            'random_int' => 'random',
        ]));
    }

    public function testFailedImagePreventsMergingCallmaps(): void
    {
        $directory = self::createGenerationSandbox();
        $repository = $directory . '/repository with spaces';
        $env = ['STALE' => '7.4 8.0 8.4', 'STUBS_IMAGE_OWNER' => 'owner'];

        try {
            file_put_contents($directory . '/commands/docker', <<<'SH'
                #!/bin/sh
                echo "$*" >> "$DOCKER_LOG"
                case "$*" in
                    *internal_stubs_8.0-*) exit 1 ;;
                    *) sleep 0.1; exit 0 ;;
                esac
                SH);

            $process = self::generate($directory, $repository, 'bin/stubs/gen_callmap.sh', $env);

            self::assertSame(1, $process->getExitCode(), $process->getErrorOutput());
            self::assertFileDoesNotExist($directory . '/merged');
            // a stamp would mark the callmap of the failed image as generated from the locked inputs
            self::assertFileDoesNotExist($directory . '/recorded');

            file_put_contents($directory . '/commands/docker', "#!/bin/sh\necho \"\$*\" >> \"\$DOCKER_LOG\"\n");
            $process = self::generate($directory, $directory, $repository . '/bin/stubs/gen_callmap.sh', $env);

            self::assertSame(0, $process->getExitCode(), $process->getErrorOutput());
            self::assertFileExists($directory . '/merged');
            self::assertStringEqualsFile($directory . '/recorded', "--record 7.4 8.0 8.4\n");
            self::assertStringContainsString(
                ' ghcr.io/owner/psalm:internal_stubs_8.4-0123456789abcdef php /app/bin/stubs/gen_base_callmap.php 8.4 ext '
                    . str_repeat('0', 40) . "\n",
                (string) file_get_contents($directory . '/docker.log'),
            );
        } finally {
            (new Filesystem())->remove($directory);
        }
    }

    public function testCallmapsMergeWithoutImagesWhenNoneIsStale(): void
    {
        $directory = self::createGenerationSandbox();

        try {
            $process = self::generate(
                $directory,
                $directory,
                $directory . '/repository with spaces/bin/stubs/gen_callmap.sh',
                ['STALE' => '', 'STUBS_IMAGE_OWNER' => false],
            );

            self::assertSame(0, $process->getExitCode(), $process->getErrorOutput());
            self::assertFileExists($directory . '/merged');
            self::assertFileDoesNotExist($directory . '/docker.log');
            self::assertFileDoesNotExist($directory . '/recorded');
        } finally {
            (new Filesystem())->remove($directory);
        }
    }

    public function testStaleCallmapsNeedAnImageOwner(): void
    {
        $directory = self::createGenerationSandbox();

        try {
            $process = self::generate(
                $directory,
                $directory,
                $directory . '/repository with spaces/bin/stubs/gen_callmap.sh',
                ['STALE' => '8.4', 'STUBS_IMAGE_OWNER' => false],
            );

            self::assertSame(1, $process->getExitCode());
            self::assertStringContainsString('STUBS_IMAGE_OWNER', $process->getErrorOutput());
            self::assertFileDoesNotExist($directory . '/merged');
            self::assertFileDoesNotExist($directory . '/docker.log');
        } finally {
            (new Filesystem())->remove($directory);
        }
    }

    /**
     * A copy of gen_callmap.sh in a repository whose path has spaces, with fake php and docker commands: php answers
     * build_docker.php with the versions in $STALE and records what it is asked to, docker logs its arguments.
     */
    private static function createGenerationSandbox(): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            self::markTestSkipped('The generation script requires a POSIX shell environment.');
        }

        $directory = sys_get_temp_dir() . '/psalm-callmap-test-' . uniqid();
        $filesystem = new Filesystem();
        $filesystem->mkdir([$directory . '/repository with spaces/bin/stubs', $directory . '/commands']);
        $filesystem->copy(
            dirname(__DIR__, 2) . '/bin/stubs/gen_callmap.sh',
            $directory . '/repository with spaces/bin/stubs/gen_callmap.sh',
        );
        file_put_contents($directory . '/commands/php', <<<'SH'
            #!/bin/sh
            case "$1 $2" in
                'bin/stubs/build_docker.php --stale-plain') printf '%s\n' "$STALE" ;;
                'bin/stubs/build_docker.php --extensions-plain') printf 'ext\n' ;;
                'bin/stubs/build_docker.php --phpstorm-stubs') printf '%040d\n' 0 ;;
                'bin/stubs/build_docker.php --tag') printf 'internal_stubs_%s-0123456789abcdef\n' "$3" ;;
                'bin/stubs/build_docker.php --record') shift; echo "$*" >> "$RECORD_LOG" ;;
                'bin/stubs/gen_callmap.php ')
                    [ "$PWD" = "$EXPECTED_ROOT" ] || exit 2
                    touch "$MERGE_MARKER"
                    ;;
                *) exit 1 ;;
            esac
            SH);
        file_put_contents($directory . '/commands/docker', "#!/bin/sh\necho \"\$*\" >> \"\$DOCKER_LOG\"\nexit 1\n");
        chmod($directory . '/commands/php', 0755);
        chmod($directory . '/commands/docker', 0755);

        return $directory;
    }

    /** @param array<string, string|false> $env */
    private static function generate(string $directory, string $cwd, string $script, array $env): Process
    {
        $path = getenv('PATH');
        self::assertNotFalse($path, 'PATH must be available to run the generation script.');

        $process = new Process(
            ['bash', $script],
            $cwd,
            $env + [
                'PATH' => $directory . '/commands:' . $path,
                'MERGE_MARKER' => $directory . '/merged',
                'RECORD_LOG' => $directory . '/recorded',
                'DOCKER_LOG' => $directory . '/docker.log',
                'EXPECTED_ROOT' => $directory . '/repository with spaces',
            ],
            timeout: 10,
        );
        $process->run();

        return $process;
    }
}
