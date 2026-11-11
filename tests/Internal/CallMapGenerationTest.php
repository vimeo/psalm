<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal;

use PHPUnit\Framework\TestCase;
use Psalm\Internal\Stubs\CallMapVersionFilter;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

use function chmod;
use function dirname;
use function explode;
use function file_get_contents;
use function file_put_contents;
use function getenv;
use function str_repeat;
use function str_replace;
use function substr_count;
use function sys_get_temp_dir;
use function trim;
use function uniqid;

use const FILE_APPEND;
use const PHP_BINARY;
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
            // neither merged nor stamped as generated from the locked inputs
            self::assertFileDoesNotExist($directory . '/calls.log');

            file_put_contents($directory . '/commands/docker', "#!/bin/sh\necho \"\$*\" >> \"\$DOCKER_LOG\"\n");
            $process = self::generate($directory, $directory, $repository . '/bin/stubs/gen_callmap.sh', $env);

            self::assertSame(0, $process->getExitCode(), $process->getErrorOutput());
            self::assertStringEqualsFile(
                $directory . '/calls.log',
                "gen_callmap.php\ngen_callmap.php\n--record 7.0 7.4 8.0 8.4\n",
            );
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
            self::assertStringEqualsFile(
                $directory . '/calls.log',
                "gen_callmap.php\ngen_callmap.php\n--record 7.0 7.4 8.0 8.4\n",
            );
            self::assertFileDoesNotExist($directory . '/docker.log');
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
            self::assertFileDoesNotExist($directory . '/calls.log');
            self::assertFileDoesNotExist($directory . '/docker.log');
        } finally {
            (new Filesystem())->remove($directory);
        }
    }

    public function testGenerationLeavesNoVersionStale(): void
    {
        $directory = self::createRepositoryCopy();
        $repository = $directory . '/repository with spaces';

        try {
            $process = self::generate(
                $directory,
                $directory,
                $repository . '/bin/stubs/gen_callmap.sh',
                ['STUBS_IMAGE_OWNER' => 'owner'],
            );

            self::assertSame(0, $process->getExitCode(), $process->getErrorOutput());
            self::assertSame('', self::staleVersions($repository));
            // one image run per version
            self::assertCount(
                substr_count((string) file_get_contents($directory . '/docker.log'), "\n"),
                explode(' ', trim(self::buildDocker($repository, '--versions-plain')->getOutput())),
            );

            file_put_contents($directory . '/commands/docker', "#!/bin/sh\nexit 1\n");
            $process = self::generate(
                $directory,
                $directory,
                $repository . '/bin/stubs/gen_callmap.sh',
                ['STUBS_IMAGE_OWNER' => false],
            );

            self::assertSame(0, $process->getExitCode(), $process->getErrorOutput());
            self::assertSame('', self::staleVersions($repository));

            $lock = $repository . '/bin/stubs/image_lock.php';
            $locked = (string) file_get_contents($lock);
            file_put_contents($lock, str_replace("'8.4.0@", "'8.4.1@", $locked));
            self::assertSame('8.4', self::staleVersions($repository));
            file_put_contents($lock, $locked);

            file_put_contents($repository . '/dictionaries/autogen/CallMap_83.php', "\n", FILE_APPEND);
            self::assertSame('8.3', self::staleVersions($repository));
        } finally {
            (new Filesystem())->remove($directory);
        }
    }

    public function testInvalidLockValuesAreRejected(): void
    {
        $directory = self::createRepositoryCopy();
        $repository = $directory . '/repository with spaces';
        $lock = $repository . '/bin/stubs/image_lock.php';
        $locked = (string) file_get_contents($lock);
        $base = '8.4.0@sha256:' . str_repeat('d', 64);

        try {
            self::assertSame(0, self::buildDocker($repository, '--stale-plain')->getExitCode());

            foreach ([
                'base image with a trailing newline' => ["'$base'", "\"$base\\n\""],
                'extension with a trailing newline' => ["'pecl@1.0.0'", '"pecl@1.0.0\n"'],
                'extension with a command' => ["'pecl@1.0.0'", "'pecl@1.0.0 && id'"],
                'base image of another version' => ["'$base'", "'8.3.0@sha256:" . str_repeat('d', 64) . "'"],
                'installer branch' => ["'1.0.0@sha256:", "'latest@sha256:"],
                'phpstorm-stubs branch' => ["'" . str_repeat('b', 40) . "'", "'master'"],
            ] as $case => [$search, $replace]) {
                $mutated = str_replace($search, $replace, $locked);
                self::assertNotSame($locked, $mutated, $case);
                file_put_contents($lock, $mutated);

                $process = self::buildDocker($repository, '--stale-plain');

                self::assertSame(1, $process->getExitCode(), $case);
                self::assertStringContainsString('image_lock.php: invalid', $process->getErrorOutput(), $case);
            }
        } finally {
            (new Filesystem())->remove($directory);
        }
    }

    /**
     * A copy of gen_callmap.sh in a repository whose path has spaces, with fake php and docker commands: php answers
     * build_docker.php with the versions in $STALE and logs the merges and stamps, docker logs its arguments.
     */
    private static function createGenerationSandbox(): string
    {
        $directory = self::createDirectories();
        file_put_contents($directory . '/commands/php', <<<'SH'
            #!/bin/sh
            case "$1 $2" in
                'bin/stubs/build_docker.php --stale-plain') printf '%s\n' "$STALE" ;;
                'bin/stubs/build_docker.php --versions-plain') printf '7.0 7.4 8.0 8.4\n' ;;
                'bin/stubs/build_docker.php --extensions-plain') printf 'ext\n' ;;
                'bin/stubs/build_docker.php --phpstorm-stubs') printf '%040d\n' 0 ;;
                'bin/stubs/build_docker.php --tag') printf 'internal_stubs_%s-0123456789abcdef\n' "$3" ;;
                'bin/stubs/build_docker.php --record') shift; echo "$*" >> "$CALLS_LOG" ;;
                'bin/stubs/gen_callmap.php ')
                    [ "$PWD" = "$EXPECTED_ROOT" ] || exit 2
                    echo gen_callmap.php >> "$CALLS_LOG"
                    ;;
                *) exit 1 ;;
            esac
            SH);
        chmod($directory . '/commands/php', 0755);

        return $directory;
    }

    /**
     * A copy of the generation scripts with a lock for every version, made with build_docker.php's own functions. The
     * fake docker writes a reflected callmap, and the fake gen_callmap.php rewrites it like the real one does.
     */
    private static function createRepositoryCopy(): string
    {
        $directory = self::createDirectories();
        $repository = $directory . '/repository with spaces';
        $filesystem = new Filesystem();
        $filesystem->mkdir($repository . '/dictionaries/autogen');
        foreach (['build_docker.php', 'gen_base_callmap.php'] as $script) {
            $filesystem->copy(dirname(__DIR__, 2) . '/bin/stubs/' . $script, $repository . '/bin/stubs/' . $script);
        }
        file_put_contents($repository . '/bin/stubs/image_lock.php', "<?php\n\nreturn ['versions' => []];\n");
        file_put_contents($repository . '/dictionaries/autogen/CallMapInputs.php', "<?php\n\nreturn [];\n");
        file_put_contents($repository . '/bin/stubs/gen_callmap.php', <<<'PHP'
            <?php
            foreach (glob(__DIR__ . '/../../dictionaries/autogen/CallMap_*.php') as $file) {
                $contents = file_get_contents($file);
                if (!str_ends_with($contents, "// merged\n")) {
                    file_put_contents($file, $contents . "// merged\n");
                }
            }
            PHP);
        file_put_contents($directory . '/commands/docker', <<<'SH'
            #!/bin/sh
            echo "$*" >> "$DOCKER_LOG"
            while [ "$#" -gt 0 ] && [ "$1" != /app/bin/stubs/gen_base_callmap.php ]; do shift; done
            [ -n "$2" ] || exit 1
            autogen="$EXPECTED_ROOT/dictionaries/autogen"
            suffix="$(printf '%s' "$2" | tr -d .)"
            printf "<?php return ['reflected' => '%s'];\n" "$2" > "$autogen/CallMap_$suffix.php"
            printf '<?php return [];\n' > "$autogen/CallMapExtensions_$suffix.php"
            SH);

        $process = new Process([PHP_BINARY, '-r', <<<'PHP'
            register_shutdown_function(static function (): void {
                $lock = ['installer' => '1.0.0@sha256:' . str_repeat('a', 64), 'phpstorm-stubs' => str_repeat('b', 40)];
                foreach (VERSIONS as $version) {
                    $extensions = [];
                    foreach (lockedExtensions($version) as $base => $extension) {
                        $pin = explode('-', $extension, 2)[1] ?? '';
                        $extensions[$base] = gitSource($base) !== null
                            ? 'git@' . str_repeat('c', 40) . ':1.0.0'
                            : (preg_match('/^\d/', $pin) ? "pecl@$pin" : 'pecl@1.0.0');
                    }
                    $lock['versions'][$version] = [
                        'php' => "$version.0@sha256:" . str_repeat('d', 64),
                        'extensions' => $extensions,
                    ];
                }
                writeLock($lock);
            });
            $argv = ['build_docker.php', '--versions'];
            require 'bin/stubs/build_docker.php';
            PHP], $repository);
        $process->run();
        self::assertSame(0, $process->getExitCode(), $process->getErrorOutput());

        return $directory;
    }

    private static function createDirectories(): string
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
        file_put_contents($directory . '/commands/docker', "#!/bin/sh\necho \"\$*\" >> \"\$DOCKER_LOG\"\nexit 1\n");
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
                'PATH' => $directory . '/commands:' . dirname(PHP_BINARY) . ':' . $path,
                'CALLS_LOG' => $directory . '/calls.log',
                'DOCKER_LOG' => $directory . '/docker.log',
                'EXPECTED_ROOT' => $directory . '/repository with spaces',
            ],
            timeout: 60,
        );
        $process->run();

        return $process;
    }

    private static function buildDocker(string $repository, string ...$arguments): Process
    {
        $process = new Process([PHP_BINARY, 'bin/stubs/build_docker.php', ...$arguments], $repository, timeout: 60);
        $process->run();

        return $process;
    }

    private static function staleVersions(string $repository): string
    {
        $process = self::buildDocker($repository, '--stale-plain');
        self::assertSame(0, $process->getExitCode(), $process->getErrorOutput());

        return trim($process->getOutput());
    }
}
