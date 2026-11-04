<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

use function chmod;
use function dirname;
use function file_put_contents;
use function getenv;
use function sys_get_temp_dir;
use function uniqid;

use const PHP_OS_FAMILY;

final class CallMapGenerationTest extends TestCase
{
    public function testFailedImagePreventsMergingCallmaps(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            self::markTestSkipped('The generation script requires a POSIX shell environment.');
        }

        $path = getenv('PATH');
        self::assertNotFalse($path, 'PATH must be available to run the generation script.');

        $directory = sys_get_temp_dir() . '/psalm-callmap-test-' . uniqid();
        $repository = $directory . '/repository with spaces';
        $commands = $directory . '/commands';
        $filesystem = new Filesystem();
        $filesystem->mkdir([$repository . '/bin/stubs', $commands]);

        try {
            $filesystem->copy(
                dirname(__DIR__, 2) . '/bin/stubs/gen_callmap.sh',
                $repository . '/bin/stubs/gen_callmap.sh',
            );
            file_put_contents($commands . '/php', <<<'SH'
                #!/bin/sh
                case "$1" in
                    */build_docker.php) printf '7.4 8.0 8.4\n' ;;
                    */gen_callmap.php)
                        [ "$PWD" = "$EXPECTED_ROOT" ] || exit 2
                        touch "$MERGE_MARKER"
                        ;;
                    *) exit 1 ;;
                esac
                SH);
            file_put_contents($commands . '/docker', <<<'SH'
                #!/bin/sh
                case "$*" in
                    *internal_stubs_8.0*) exit 1 ;;
                    *) sleep 0.1; exit 0 ;;
                esac
                SH);
            chmod($commands . '/php', 0755);
            chmod($commands . '/docker', 0755);

            $process = new Process(
                ['bash', 'bin/stubs/gen_callmap.sh'],
                $repository,
                [
                    'PATH' => $commands . ':' . $path,
                    'MERGE_MARKER' => $directory . '/merged',
                    'EXPECTED_ROOT' => $repository,
                ],
                timeout: 10,
            );
            $process->run();

            self::assertSame(1, $process->getExitCode(), $process->getErrorOutput());
            self::assertFileDoesNotExist($directory . '/merged');

            file_put_contents($commands . '/docker', "#!/bin/sh\nexit 0\n");
            $process = new Process(
                ['bash', $repository . '/bin/stubs/gen_callmap.sh'],
                $directory,
                [
                    'PATH' => $commands . ':' . $path,
                    'MERGE_MARKER' => $directory . '/merged',
                    'EXPECTED_ROOT' => $repository,
                ],
                timeout: 10,
            );
            $process->run();

            self::assertSame(0, $process->getExitCode(), $process->getErrorOutput());
            self::assertFileExists($directory . '/merged');
        } finally {
            $filesystem->remove($directory);
        }
    }
}
