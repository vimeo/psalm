<?php

declare(strict_types=1);

namespace Psalm\Tests\EndToEnd;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;

use function basename;
use function dirname;
use function file_get_contents;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function preg_match_all;
use function str_replace;
use function strtolower;
use function substr_count;

use const PHP_BINARY;

/**
 * Tests the dry runs of the psalter command, and how it selects the files to change,
 * by launching a new process as if invoked by a real user.
 */
final class PsalterDryRunEndToEndTest extends PsalmEndToEndTestCase
{
    public function testPsalterDryRunPrintsEachChangedFileOnceForOverlappingPaths(): void
    {
        $this->runPsalmInit();
        $psalmXml = file_get_contents(self::$tmpDir . '/psalm.xml');
        $psalmXml = str_replace('<psalm', '<psalm runTaintAnalysis="false"', (string)$psalmXml);
        file_put_contents(self::$tmpDir . '/psalm.xml', $psalmXml);
        file_put_contents(self::$tmpDir . '/src/A.php', "<?php\n\nfunction a() {\n    return 1;\n}\n");
        file_put_contents(self::$tmpDir . '/src/B.php', "<?php\n\nfunction b() {\n    return 2;\n}\n");

        $process = self::newProcess(
            [
                PHP_BINARY,
                $this->psalter,
                '--dry-run',
                '--no-cache',
                '--issues=MissingReturnType',
                './src',
                'src/A.php',
                'src/B.php',
                'src/../src/A.php',
            ],
            self::$tmpDir,
            self::agentEnvVarsToUnset(),
        );
        $process->run();
        $this->assertSame(2, $process->getExitCode(), $process->getOutput() . $process->getErrorOutput());
        $output = $process->getOutput();

        $this->assertSame(1, preg_match_all('/^\d\) src\/A\.php:3$/m', $output), $output);
        $this->assertSame(1, preg_match_all('/^\d\) src\/B\.php:3$/m', $output), $output);
        $this->assertSame(1, substr_count($output, '--- a/src/A.php'), $output);
        $this->assertSame(1, substr_count($output, '--- a/src/B.php'), $output);
        $this->assertStringContainsString("\n       2 files would be changed (dry run)       \n", $output);
        $this->assertStringNotContainsString("\e", $output);
    }

    public function testPsalterDryRunNumbersFilesInSortedOrderRegardlessOfArgumentOrder(): void
    {
        $this->runPsalmInit();
        $psalmXml = file_get_contents(self::$tmpDir . '/psalm.xml');
        $psalmXml = str_replace('<psalm', '<psalm runTaintAnalysis="false"', (string)$psalmXml);
        file_put_contents(self::$tmpDir . '/psalm.xml', $psalmXml);
        file_put_contents(self::$tmpDir . '/src/A.php', "<?php\n\nfunction a() {\n    return 1;\n}\n");
        file_put_contents(self::$tmpDir . '/src/B.php', "<?php\n\nfunction b() {\n    return 2;\n}\n");

        $process = self::newProcess(
            [
                PHP_BINARY,
                $this->psalter,
                '--dry-run',
                '--no-cache',
                '--issues=MissingReturnType',
                'src/B.php',
                'src',
                'src/A.php',
            ],
            self::$tmpDir,
            self::agentEnvVarsToUnset(),
        );
        $process->run();
        $this->assertSame(2, $process->getExitCode(), $process->getOutput() . $process->getErrorOutput());
        $output = $process->getOutput();

        $this->assertStringContainsString("1) src/A.php:3\n", $output);
        $this->assertStringContainsString("2) src/B.php:3\n", $output);
    }

    public function testPsalterDryRunExitsWithCode2AndLeavesFilesUnchangedWhenFilesWouldChange(): void
    {
        $this->runPsalmInit();
        $psalmXml = file_get_contents(self::$tmpDir . '/psalm.xml');
        $psalmXml = str_replace('<psalm', '<psalm runTaintAnalysis="false"', (string)$psalmXml);
        file_put_contents(self::$tmpDir . '/psalm.xml', $psalmXml);
        $source = "<?php\n\nfunction a() {\n    return 1;\n}\n";
        file_put_contents(self::$tmpDir . '/src/A.php', $source);

        $process = self::newProcess(
            [PHP_BINARY, $this->psalter, '--dry-run', '--no-cache', '--issues=MissingReturnType', 'src'],
            self::$tmpDir,
            self::agentEnvVarsToUnset(),
        );
        $process->run();

        $this->assertSame(2, $process->getExitCode(), $process->getOutput() . $process->getErrorOutput());
        $this->assertStringContainsString('would be changed (dry run)', $process->getOutput());
        $this->assertSame($source, file_get_contents(self::$tmpDir . '/src/A.php'));
    }

    public function testPsalmAlterDryRunExitsWithCode2WhenFilesWouldChange(): void
    {
        $this->runPsalmInit();
        $psalmXml = file_get_contents(self::$tmpDir . '/psalm.xml');
        $psalmXml = str_replace('<psalm', '<psalm runTaintAnalysis="false"', (string)$psalmXml);
        file_put_contents(self::$tmpDir . '/psalm.xml', $psalmXml);
        file_put_contents(self::$tmpDir . '/src/A.php', "<?php\n\nfunction a() {\n    return 1;\n}\n");

        // runPsalm() asserts the exit code is 2 when $shouldFail is true
        $this->runPsalm(
            ['--alter', '--dry-run', '--no-cache', '--issues=MissingReturnType', 'src'],
            self::$tmpDir,
            true,
            false,
        );
    }

    /**
     * @param list<string> $args
     */
    #[DataProvider('provideOptionsThatFollowAPath')]
    public function testPsalterReadsTheOptionsThatFollowAPath(array $args): void
    {
        $source = $this->writeAFileWithAMissingReturnType();

        $process = self::newProcess(
            [PHP_BINARY, $this->psalter, ...$args],
            self::$tmpDir,
            self::agentEnvVarsToUnset(),
        );
        $process->run();

        $this->assertSame(2, $process->getExitCode(), $process->getOutput() . $process->getErrorOutput());
        $this->assertStringContainsString('would be changed (dry run)', $process->getOutput());
        $this->assertSame($source, file_get_contents(self::$tmpDir . '/src/A.php'));
    }

    /**
     * @return iterable<string, array{list<string>}>
     * @psalm-pure
     */
    public static function provideOptionsThatFollowAPath(): iterable
    {
        yield 'a flag' => [['--no-cache', '--issues=MissingReturnType', 'src/A.php', '--dry-run']];
        yield 'a value' => [['--no-cache', 'src/A.php', '--issues=MissingReturnType', '--dry-run']];
        yield 'a value in a separate argument' => [
            ['--no-cache', 'src/A.php', '--issues', 'MissingReturnType', '--dry-run'],
        ];
    }

    /**
     * @param list<string> $file_args
     */
    #[DataProvider('provideFileOptionsWithAValueThatLooksLikeAnOption')]
    public function testPsalterNeverWidensAnExplicitFileSelectionToTheWholeProject(
        array $file_args,
        string $expected_error,
    ): void {
        $source = $this->writeAFileWithAMissingReturnType();

        $process = self::newProcess(
            [PHP_BINARY, $this->psalter, '--no-cache', '--issues=MissingReturnType', ...$file_args],
            self::$tmpDir,
            self::agentEnvVarsToUnset(),
        );
        $process->run();

        $this->assertSame(1, $process->getExitCode(), $process->getOutput() . $process->getErrorOutput());
        $this->assertStringContainsString($expected_error, $process->getErrorOutput());
        $this->assertSame($source, file_get_contents(self::$tmpDir . '/src/A.php'));
    }

    /**
     * @return iterable<string, array{list<string>, string}>
     * @psalm-pure
     */
    public static function provideFileOptionsWithAValueThatLooksLikeAnOption(): iterable
    {
        yield 'a value after an equals sign' => [['-f=-target.php'], 'Invalid usage'];
        yield 'a value in a separate argument' => [['-f', '-target.php'], 'Invalid usage'];
        yield 'a value that is a short option' => [['-f', '-m'], 'Invalid usage'];
        yield 'zero' => [['-f', '0'], 'Cannot locate 0'];
        yield 'an empty string' => [['-f', ''], 'Cannot locate'];
    }

    public function testPsalterDryRunExitsWithCode0WhenNothingWouldChange(): void
    {
        $this->runPsalmInit();
        $psalmXml = file_get_contents(self::$tmpDir . '/psalm.xml');
        $psalmXml = str_replace('<psalm', '<psalm runTaintAnalysis="false"', (string)$psalmXml);
        file_put_contents(self::$tmpDir . '/psalm.xml', $psalmXml);
        file_put_contents(self::$tmpDir . '/src/A.php', "<?php\n\nfunction a(): int {\n    return 1;\n}\n");

        $process = self::newProcess(
            [PHP_BINARY, $this->psalter, '--dry-run', '--no-cache', '--issues=MissingReturnType', 'src'],
            self::$tmpDir,
            self::agentEnvVarsToUnset(),
        );
        $process->run();

        $this->assertSame(0, $process->getExitCode(), $process->getOutput() . $process->getErrorOutput());
        $this->assertStringContainsString('No changes', $process->getOutput());
    }

    public function testPsalterAcceptsTheLongMonochromeOptionAndItOverridesForceColor(): void
    {
        $this->runPsalmInit();
        $psalmXml = file_get_contents(self::$tmpDir . '/psalm.xml');
        $psalmXml = str_replace('<psalm', '<psalm runTaintAnalysis="false"', (string)$psalmXml);
        file_put_contents(self::$tmpDir . '/psalm.xml', $psalmXml);
        file_put_contents(self::$tmpDir . '/src/A.php', "<?php\n\nfunction a() {\n    return 1;\n}\n");

        $process = self::newProcess(
            [PHP_BINARY, $this->psalter, '--monochrome', '--dry-run', '--no-cache', '--issues=MissingReturnType', 'src'],
            self::$tmpDir,
            ['FORCE_COLOR' => '1'] + self::agentEnvVarsToUnset(),
        );
        $process->run();

        $this->assertStringNotContainsString('Unrecognised argument', $process->getErrorOutput());
        $this->assertSame(2, $process->getExitCode(), $process->getOutput() . $process->getErrorOutput());
        $this->assertStringContainsString('+function a(): int {', $process->getOutput());
        $this->assertStringNotContainsString("\e", $process->getOutput());
    }

    /**
     * @return iterable<string, array{list<string>}>
     * @psalm-pure
     */
    public static function psalterFileOptionForms(): iterable
    {
        yield 'long option with equals sign' => [['--file=src/A.php']];
        yield 'long option with separate value' => [['--file', 'src/A.php']];
        yield 'short option' => [['-f', 'src/A.php']];
    }

    /** @param list<string> $file_args */
    #[DataProvider('psalterFileOptionForms')]
    public function testPsalterFileOptionRestrictsTheChangedFiles(array $file_args): void
    {
        $this->prepareProjectWithUntypedFunctions(['src/A.php', 'src/B.php']);

        $process = $this->runPsalterDryRun(self::$tmpDir, $file_args);

        $this->assertSame(2, $process->getExitCode(), $process->getOutput() . $process->getErrorOutput());
        $this->assertStringContainsString("1) src/A.php:3\n", $process->getOutput());
        $this->assertStringNotContainsString('src/B.php', $process->getOutput());
        $this->assertStringContainsString("\n       1 file would be changed (dry run)       \n", $process->getOutput());
    }

    /**
     * @return iterable<string, array{bool}>
     * @psalm-pure
     */
    public static function pluginPathForms(): iterable
    {
        yield 'relative path' => [false];
        yield 'absolute path' => [true];
    }

    #[DataProvider('pluginPathForms')]
    public function testPsalterLoadsAPluginGivenByPath(bool $absolute): void
    {
        $this->prepareProjectWithUntypedFunctions(['src/A.php']);
        mkdir(self::$tmpDir . '/plugins');
        file_put_contents(
            self::$tmpDir . '/plugins/plugin.php',
            "<?php\n\nnamespace PsalmEndToEndTest;\n\n"
            . "file_put_contents(__DIR__ . '/loaded.txt', 'loaded');\n\n"
            . "final class MinimalPlugin\n{\n}\n",
        );

        $process = $this->runPsalterDryRun(
            self::$tmpDir,
            ['--plugin=' . ($absolute ? self::$tmpDir . '/' : '') . 'plugins/plugin.php'],
        );

        $this->assertStringNotContainsString('Cannot find plugin file', $process->getErrorOutput());
        $this->assertSame(2, $process->getExitCode(), $process->getOutput() . $process->getErrorOutput());
        $this->assertFileExists(self::$tmpDir . '/plugins/loaded.txt');
    }

    public function testPsalterFindsTheCodeownersFileInTheProjectRootGivenByTheRootOption(): void
    {
        $this->prepareProjectWithUntypedFunctions(['src/A.php', 'src/B.php']);
        $psalm_xml = (string) file_get_contents(self::$tmpDir . '/psalm.xml');
        file_put_contents(
            self::$tmpDir . '/psalm.xml',
            str_replace('resolveFromConfigFile="true"', 'resolveFromConfigFile="false"', $psalm_xml),
        );
        file_put_contents(self::$tmpDir . '/CODEOWNERS', "/src/A.php @alice\n/src/B.php @bob\n");

        $process = $this->runPsalterDryRun(
            dirname(self::$tmpDir),
            ['-r', self::$tmpDir, '-c', self::$tmpDir . '/psalm.xml', '--codeowner=@bob'],
        );

        $this->assertSame(2, $process->getExitCode(), $process->getOutput() . $process->getErrorOutput());
        $this->assertStringContainsString("1) src/B.php:3\n", $process->getOutput());
        $this->assertStringNotContainsString('src/A.php', $process->getOutput());
    }

    public function testPsalterCodeownerOnlyChangesTheOwnedFilesInsideTheGivenPaths(): void
    {
        $this->prepareProjectWithUntypedFunctions(['src/A.php', 'src/sub/B.php']);
        file_put_contents(self::$tmpDir . '/CODEOWNERS', "/src/A.php @alice\n/src/sub/B.php @alice\n");

        $process = $this->runPsalterDryRun(self::$tmpDir, ['--codeowner=@alice', 'src/sub']);

        $this->assertSame(2, $process->getExitCode(), $process->getOutput() . $process->getErrorOutput());
        $this->assertStringContainsString("1) src/sub/B.php:3\n", $process->getOutput());
        $this->assertStringNotContainsString('src/A.php', $process->getOutput());
    }

    public function testPsalterCodeownerFailsWhenNoOwnedFileIsInsideTheGivenPaths(): void
    {
        $this->prepareProjectWithUntypedFunctions(['src/A.php', 'src/sub/B.php']);
        file_put_contents(self::$tmpDir . '/CODEOWNERS', "/src/A.php @alice\n/src/sub/B.php @bob\n");

        $process = $this->runPsalterDryRun(self::$tmpDir, ['--codeowner=@alice', 'src/sub']);

        $this->assertSame(1, $process->getExitCode(), $process->getOutput() . $process->getErrorOutput());
        $this->assertStringContainsString(
            'No PHP file in the given paths is owned by the requested --codeowner',
            $process->getErrorOutput(),
        );
        $this->assertStringNotContainsString('src/A.php', $process->getOutput());
    }

    /**
     * Creates `psalm.xml` and one function without a return type in each of the given files
     *
     * @param list<string> $files paths relative to the project root, in `src`
     */
    private function prepareProjectWithUntypedFunctions(array $files): void
    {
        $this->runPsalmInit();
        $psalm_xml = (string) file_get_contents(self::$tmpDir . '/psalm.xml');
        file_put_contents(
            self::$tmpDir . '/psalm.xml',
            str_replace('<psalm', '<psalm runTaintAnalysis="false"', $psalm_xml),
        );

        foreach ($files as $file) {
            if (!is_dir(dirname(self::$tmpDir . '/' . $file))) {
                mkdir(dirname(self::$tmpDir . '/' . $file), 0777, true);
            }

            $function_name = strtolower(basename($file, '.php'));
            file_put_contents(
                self::$tmpDir . '/' . $file,
                "<?php\n\nfunction $function_name() {\n    return 1;\n}\n",
            );
        }
    }

    /** @param list<string> $args */
    private function runPsalterDryRun(string $cwd, array $args): Process
    {
        $process = self::newProcess(
            [
                PHP_BINARY,
                $this->psalter,
                '--dry-run',
                '--no-diff',
                '--no-cache',
                '--issues=MissingReturnType',
                ...$args,
            ],
            $cwd,
            self::agentEnvVarsToUnset(),
        );
        $process->run();

        return $process;
    }
}
