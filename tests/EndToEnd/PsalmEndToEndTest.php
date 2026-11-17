<?php

declare(strict_types=1);

namespace Psalm\Tests\EndToEnd;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Psalm\Tests\Config\Plugin\ReportableFilePlugin;
use Symfony\Component\Process\Process;

use function assert;
use function basename;
use function file_get_contents;
use function file_put_contents;
use function json_decode;
use function mkdir;
use function preg_replace;
use function sort;
use function str_contains;
use function str_replace;
use function substr_count;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

use const JSON_THROW_ON_ERROR;
use const PHP_BINARY;
use const PHP_EOL;

/**
 * Tests some of the most important use cases of the psalm command,
 * by launching a new process as if invoked by a real user.
 */
final class PsalmEndToEndTest extends PsalmEndToEndTestCase
{
    public function testHelpReturnsMessage(): void
    {
        $this->assertStringContainsString('Usage:', $this->runPsalm(['--help'], self::$tmpDir)['STDOUT']);
    }

    public function testVersionIsPrintedWithoutTheAutoloadersOfTheProject(): void
    {
        $project_dir = tempnam(sys_get_temp_dir(), 'PsalmEndToEndVersion_');
        assert($project_dir !== false);
        unlink($project_dir);
        mkdir($project_dir);

        try {
            file_put_contents($project_dir . '/composer.json', '{"require": {}}');

            $long = self::newProcess([PHP_BINARY, $this->psalm, '--version'], $project_dir, self::agentEnvVarsToUnset());
            $long->run();
            $short = self::newProcess([PHP_BINARY, $this->psalm, '-v'], $project_dir, self::agentEnvVarsToUnset());
            $short->run();
            $language_server = self::newProcess(
                [PHP_BINARY, __DIR__ . '/../../psalm-language-server', '--version'],
                $project_dir,
                self::agentEnvVarsToUnset(),
            );
            $language_server->run();
            $analysis = self::newProcess([PHP_BINARY, $this->psalm], $project_dir, self::agentEnvVarsToUnset());
            $analysis->run();
        } finally {
            self::recursiveRemoveDirectory($project_dir);
        }

        $this->assertSame(0, $long->getExitCode());
        $this->assertStringStartsWith('Psalm ', $long->getOutput());
        $this->assertSame('', $long->getErrorOutput());
        $this->assertSame($long->getOutput(), $short->getOutput());
        $this->assertSame($long->getOutput(), $language_server->getOutput());
        $this->assertSame(1, $analysis->getExitCode());
        $this->assertStringContainsString('Could not find any composer autoloaders', $analysis->getErrorOutput());
    }

    public function testInit(): void
    {
        $this->assertStringContainsString(
            'Created psalm.xml with errorLevel ',
            $this->runPsalmInit()['STDOUT'],
        );
        $this->assertFileExists(self::$tmpDir . '/psalm.xml');
    }

    public function testInitReportsMissingAutoloadDirectoriesWithoutCrashing(): void
    {
        $project_dir = tempnam(sys_get_temp_dir(), 'PsalmEndToEndMissingAutoload_');
        assert($project_dir !== false);
        unlink($project_dir);
        mkdir($project_dir);

        try {
            file_put_contents(
                $project_dir . '/composer.json',
                '{"autoload": {"psr-4": {"App\\\\": "missing/"}}}',
            );
            mkdir($project_dir . '/vendor');
            file_put_contents($project_dir . '/vendor/autoload.php', "<?php\n");

            $process = self::newProcess([PHP_BINARY, $this->psalm, '--init'], $project_dir, self::agentEnvVarsToUnset());
            $process->run();
        } finally {
            self::recursiveRemoveDirectory($project_dir);
        }

        $this->assertSame(1, $process->getExitCode());
        $this->assertStringContainsString(
            'Could not located any PSR-0 or PSR-4-compatible paths',
            $process->getErrorOutput(),
        );
        $this->assertStringNotContainsString('Uncaught', $process->getErrorOutput());
    }

    /**
     * @param list<string> $args
     */
    #[DataProvider('provideReviewInvocations')]
    public function testPsalmReviewReadsTheReportWhereverReviewIsGiven(array $args): void
    {
        $report = self::$tmpDir . '/report.json';
        file_put_contents($report, '[]');

        try {
            $process = self::newProcess([PHP_BINARY, $this->psalm, ...$args], self::$tmpDir, self::agentEnvVarsToUnset());
            $process->run();

            $this->assertSame(0, $process->getExitCode(), $process->getOutput() . $process->getErrorOutput());
        } finally {
            unlink($report);
        }
    }

    /**
     * @return iterable<string, array{list<string>}>
     * @psalm-pure
     */
    public static function provideReviewInvocations(): iterable
    {
        yield 'review first' => [['--review', 'report.json', 'code']];
        yield 'review after the report' => [['report.json', '--review', 'code']];
    }

    public function testPsalm(): void
    {
        $this->runPsalmInit(1);
        $psalmXml = file_get_contents(self::$tmpDir . '/psalm.xml');
        $psalmXml = str_replace('<psalm', '<psalm runTaintAnalysis="false"', (string)$psalmXml);
        file_put_contents(self::$tmpDir . '/psalm.xml', $psalmXml);

        $result = $this->runPsalm([], self::$tmpDir, true);
        $this->assertStringContainsString(
            'Target PHP version: 7.1 (inferred from composer.json)',
            $result['STDERR'],
        );
        $this->assertStringContainsString('InvalidReturnType', $result['STDOUT']);
        $this->assertStringContainsString('InvalidReturnStatement', $result['STDOUT']);
        $this->assertStringContainsString('2 errors', $result['STDOUT']);
        $this->assertSame(2, $result['CODE']);
    }

    // Psalm forks its workers only with these extensions (never on Windows)
    #[RequiresPhpExtension('pcntl')]
    #[RequiresPhpExtension('posix')]
    public function testFilesAPluginMakesReportableAtBootAreReportedByEveryWorker(): void
    {
        $this->runPsalmInit(1);
        file_put_contents(self::$tmpDir . '/psalm.xml', str_replace(
            ['<psalm', '</psalm>'],
            [
                '<psalm runTaintAnalysis="false"',
                '<plugins><pluginClass class="' . ReportableFilePlugin::class . '" /></plugins></psalm>',
            ],
            (string) file_get_contents(self::$tmpDir . '/psalm.xml'),
        ));
        unlink(self::$tmpDir . '/src/FileWithErrors.php');

        // enough files for scanning and analysis to fork worker processes
        for ($i = 0; $i < 520; $i++) {
            file_put_contents(self::$tmpDir . "/src/f$i.php", "<?php\n\nfunction f$i(): void {}\n");
        }

        mkdir(self::$tmpDir . '/compiled');
        file_put_contents(
            self::$tmpDir . '/compiled/view.php',
            "<?php\n\nfunction orderTotal(): int\n{\n    return 'total';\n}\n",
        );
        file_put_contents(
            self::$tmpDir . '/compiled/partial.php',
            "<?php\n\nfunction orderRow(): void {}\n\nfunction orderRow(): void {}\n",
        );

        $process = new Process(
            [
                PHP_BINARY,
                $this->psalm,
                '--no-cache',
                '--debug',
                '--threads=2',
                '--scan-threads=2',
                '--output-format=json',
            ],
            self::$tmpDir,
            self::agentEnvVarsToUnset(),
        );
        $process->setTimeout(self::PROCESS_TIMEOUT);
        $process->run();

        $this->assertStringContainsString('Forking process for scanning', $process->getErrorOutput());

        /** @var list<array{type: string, file_name: string}> $issues */
        $issues = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
        $compiled_issues = [];
        foreach ($issues as $issue) {
            if (str_contains($issue['file_name'], 'compiled')) {
                $compiled_issues[] = basename($issue['file_name']) . ': ' . $issue['type'];
            }
        }
        sort($compiled_issues);

        // DuplicateFunction is raised while scanning, the others while analyzing
        $this->assertSame(
            ['partial.php: DuplicateFunction', 'view.php: InvalidReturnStatement', 'view.php: InvalidReturnType'],
            $compiled_issues,
        );
    }

    public function testParseErrorIsReportedOnEveryRunWithAWarmCache(): void
    {
        $this->runPsalmInit(1);
        file_put_contents(self::$tmpDir . '/src/Clean.php', "<?php\nfunction clean(): void {}\n");
        $broken_file = self::$tmpDir . '/src/Broken.php';
        file_put_contents($broken_file, "<?php\nfunction f() {\n    echo 1\n}\n");

        for ($run = 1; $run <= 3; $run++) {
            $result = $this->runPsalm(['--no-progress'], self::$tmpDir, true);

            $this->assertSame(1, substr_count($result['STDOUT'], 'ParseError'), "run $run");
        }

        file_put_contents($broken_file, "<?php\nfunction f() {\n    echo 1;\n}\n");

        $result = $this->runPsalm(['--no-progress'], self::$tmpDir, true);

        $this->assertSame(0, substr_count($result['STDOUT'], 'ParseError'));
    }

    public function testPsalmWithPHPVersionOverride(): void
    {
        $this->runPsalmInit(1);
        $result = $this->runPsalm(['--php-version=8.0'], self::$tmpDir, true);
        $this->assertStringContainsString(
            'Target PHP version: 8.0 (set by CLI argument)',
            $result['STDERR'],
        );
    }

    public function testPsalmWithPHPVersionFromConfig(): void
    {
        $this->runPsalmInit(1, '7.4');
        $result = $this->runPsalm([], self::$tmpDir, true);
        $this->assertStringContainsString(
            'Target PHP version: 7.4 (set by config file)',
            $result['STDERR'],
        );
    }

    public function testPsalmWithPHP86VersionOverride(): void
    {
        $this->runPsalmInit(1);
        $result = $this->runPsalm(['--php-version=8.6'], self::$tmpDir, true);
        $this->assertStringContainsString(
            'Target PHP version: 8.6 (set by CLI argument)',
            $result['STDERR'],
        );
    }

    public function testPsalmWithPHP86VersionFromConfig(): void
    {
        $this->runPsalmInit(1, '8.6');
        $result = $this->runPsalm([], self::$tmpDir, true);
        $this->assertStringContainsString(
            'Target PHP version: 8.6 (set by config file)',
            $result['STDERR'],
        );
    }

    /*
    public function testPsalmDiff(): void
    {
        copy(__DIR__ . '/../fixtures/DummyProjectWithErrors/diff_composer.lock', self::$tmpDir . '/composer.lock');

        $this->runPsalmInit(1);
        $psalmXml = file_get_contents(self::$tmpDir . '/psalm.xml');
        $psalmXml = str_replace('<psalm', '<psalm runTaintAnalysis="false"', (string)$psalmXml);
        file_put_contents(self::$tmpDir . '/psalm.xml', $psalmXml);

        $result = $this->runPsalm(['--diff', '-m'], self::$tmpDir, true);
        $this->assertStringContainsString('InvalidReturnType', $result['STDOUT']);
        $this->assertStringContainsString('InvalidReturnStatement', $result['STDOUT']);
        $this->assertStringContainsString('2 errors', $result['STDOUT']);
        $this->assertStringContainsString('E', $result['STDERR']);

        $this->assertSame(2, $result['CODE']);

        $result = $this->runPsalm(['--diff', '-m'], self::$tmpDir, true);

        $this->assertStringContainsString('InvalidReturnType', $result['STDOUT']);
        $this->assertStringContainsString('InvalidReturnStatement', $result['STDOUT']);
        $this->assertStringContainsString('2 errors', $result['STDOUT']);
        $this->assertStringNotContainsString('E', $result['STDERR']);

        $this->assertSame(2, $result['CODE']);

        @unlink(self::$tmpDir . '/composer.lock');
    }*/

    public function testLegacyConfigWithoutresolveFromConfigFile(): void
    {
        $this->runPsalmInit(1);
        $psalmXmlContent = file_get_contents(self::$tmpDir . '/psalm.xml');
        assert($psalmXmlContent !== false);
        $count = 0;
        $psalmXmlContent = (string) preg_replace('/resolveFromConfigFile="true"/', 'resolveFromConfigFile="false"', $psalmXmlContent, -1, $count);
        $this->assertEquals(1, $count);

        file_put_contents(self::$tmpDir . '/src/psalm.xml', $psalmXmlContent);

        $process = self::newProcess([PHP_BINARY, $this->psalm, '--config=src/psalm.xml'], self::$tmpDir);
        $process->run();
        $this->assertSame(2, $process->getExitCode());
        $this->assertStringContainsString('InvalidReturnType', $process->getOutput());
    }

    public function testPsalmWithNoProgressDoesNotProduceOutputOnStderr(): void
    {
        $this->runPsalmInit();

        $psalmXml = file_get_contents(self::$tmpDir . '/psalm.xml');
        assert($psalmXml !== false);
        $psalmXml = (string) preg_replace('/findUnusedCode="(true|false)"/', 'runTaintAnalysis="false"', $psalmXml, 1);
        file_put_contents(self::$tmpDir . '/psalm.xml', $psalmXml);

        $result = $this->runPsalm(['--no-progress'], self::$tmpDir);

        $this->assertSame('', $result['STDERR']);
    }

    public function testRefactorAcceptsNoProgressOption(): void
    {
        $process = self::newProcess(
            [
                PHP_BINARY,
                __DIR__ . '/../../psalm-refactor',
                '--no-progress',
                '--help',
            ],
            self::$tmpDir,
            self::agentEnvVarsToUnset(),
        );
        $process->run();

        $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput());
        $this->assertSame('', $process->getErrorOutput());
    }

    /**
     * @param list<string> $args
     */
    #[DataProvider('provideInvalidThreadsArguments')]
    public function testInvalidThreadsIsAUsageError(string $cli, array $args, string $message): void
    {
        $this->runPsalmInit(1);

        $process = self::newProcess(
            [PHP_BINARY, $cli === 'psalter' ? $this->psalter : $this->psalm, '--no-progress', ...$args],
            self::$tmpDir,
            self::agentEnvVarsToUnset(),
        );
        $process->run();

        $this->assertSame(1, $process->getExitCode(), $process->getOutput() . $process->getErrorOutput());
        $this->assertStringContainsString($message, $process->getErrorOutput());
    }

    /**
     * @return iterable<string, array{string, list<string>, string}>
     * @psalm-pure
     */
    public static function provideInvalidThreadsArguments(): iterable
    {
        $message = ' expects a positive integer or "auto"';

        yield 'psalm --threads=abc' => ['psalm', ['--threads=abc'], '--threads' . $message];
        yield 'psalm --threads=0' => ['psalm', ['--threads=0'], '--threads' . $message];
        yield 'psalm --scan-threads=-1' => ['psalm', ['--scan-threads=-1'], '--scan-threads' . $message];
        yield 'psalter --threads=abc' => ['psalter', ['--issues=all', '--threads=abc'], '--threads' . $message];
    }

    /**
     * @param list<string> $args
     */
    #[DataProvider('provideTypeCoverageBelowTheMinimum')]
    public function testTypeCoverageBelowTheMinimumFailsAFullRun(string $attributes, array $args): void
    {
        $process = $this->runPsalmOnAProjectWithTypeCoverage($attributes, [...$args, '--output-format=json']);

        $this->assertSame(2, $process->getExitCode(), $process->getOutput() . $process->getErrorOutput());
        $this->assertSame('Type coverage 77.77% is below the minimum of 95.00%' . PHP_EOL, $process->getErrorOutput());
        $this->assertSame("[]\n", $process->getOutput());
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     * @psalm-pure
     */
    public static function provideTypeCoverageBelowTheMinimum(): iterable
    {
        yield 'option' => ['', ['--min-type-coverage=95']];
        yield 'config' => ['minTypeCoverage="95"', []];
    }

    /**
     * @param list<string> $args
     */
    #[DataProvider('provideTypeCoverageNotChecked')]
    public function testTypeCoverageNotBelowTheMinimumOrNotCheckedPasses(string $attributes, array $args): void
    {
        $process = $this->runPsalmOnAProjectWithTypeCoverage($attributes, ['--output-format=json', ...$args]);

        $this->assertSame(0, $process->getExitCode(), $process->getOutput() . $process->getErrorOutput());
        $this->assertStringNotContainsString('Type coverage', $process->getErrorOutput());
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     * @psalm-pure
     */
    public static function provideTypeCoverageNotChecked(): iterable
    {
        yield 'option overrides config' => ['minTypeCoverage="95"', ['--min-type-coverage=77.77']];
        yield 'partial run with the config attribute' => ['minTypeCoverage="95"', ['src/Untyped.php']];
        yield 'creating the baseline' => ['minTypeCoverage="95"', ['--set-baseline=psalm-baseline.xml']];
    }

    /**
     * @param list<string> $args
     */
    #[DataProvider('provideInvalidMinTypeCoverage')]
    public function testInvalidMinTypeCoverageIsAnError(array $args, string $message): void
    {
        $process = $this->runPsalmOnAProjectWithTypeCoverage('', $args);

        $this->assertSame(1, $process->getExitCode(), $process->getOutput() . $process->getErrorOutput());
        $this->assertSame($message . PHP_EOL, $process->getErrorOutput());
    }

    /**
     * @return iterable<string, array{list<string>, string}>
     * @psalm-pure
     */
    public static function provideInvalidMinTypeCoverage(): iterable
    {
        $message = '--min-type-coverage expects a percentage from 0 to 100 with at most two decimals';

        yield 'three decimals' => [['--min-type-coverage=95.555'], $message];
        yield 'above 100' => [['--min-type-coverage=100.01'], $message];
        yield 'not a number' => [['--min-type-coverage=abc'], $message];
        yield 'repeated' => [
            ['--min-type-coverage=90', '--min-type-coverage=95'],
            'Only one --min-type-coverage can be given',
        ];
        yield 'paths' => [
            ['--min-type-coverage=95', 'src/Untyped.php'],
            'Cannot check the minimum type coverage when checking specific files',
        ];
    }

    /**
     * @param array<string, string|false> $env
     */
    #[DataProvider('provideTaintRunWithASarifReport')]
    public function testTypeCoverageBelowTheMinimumOfATaintRunWithASarifReport(array $env, int $exit_code): void
    {
        $report = self::$tmpDir . '/report.sarif';

        try {
            $process = $this->runPsalmOnAProjectWithTypeCoverage(
                'minTypeCoverage="95"',
                ['--taint-analysis', '--report=' . $report, '--output-format=json'],
                $env,
            );

            $this->assertSame($exit_code, $process->getExitCode(), $process->getOutput() . $process->getErrorOutput());
            $this->assertSame('Type coverage 77.77% is below the minimum of 95.00%' . PHP_EOL, $process->getErrorOutput());
        } finally {
            @unlink($report);
        }
    }

    /**
     * @return iterable<string, array{array<string, string|false>, int}>
     * @psalm-pure
     */
    public static function provideTaintRunWithASarifReport(): iterable
    {
        // like for issues, so that a following step can upload the report
        yield 'in GitHub Actions' => [['GITHUB_WORKFLOW' => 'CI'], 0];
        yield 'elsewhere' => [['GITHUB_WORKFLOW' => false], 2];
    }

    /**
     * @param list<string> $args
     * @param array<string, string|false> $env
     */
    private function runPsalmOnAProjectWithTypeCoverage(string $attributes, array $args, array $env = []): Process
    {
        @unlink(self::$tmpDir . '/src/FileWithErrors.php');
        file_put_contents(
            self::$tmpDir . '/psalm.xml',
            '<?xml version="1.0"?>
            <psalm errorLevel="8" runTaintAnalysis="false" findUnusedCode="false" ' . $attributes . '>
                <projectFiles><directory name="src"/></projectFiles>
            </psalm>',
        );
        // Psalm infers the types of 7 of the 9 expressions: 77.7778%
        file_put_contents(self::$tmpDir . '/src/Typed.php', "<?php\nfunction typed(int \$a): int { \$b = \$a + 1; return \$b * 2; }\n");
        file_put_contents(self::$tmpDir . '/src/Untyped.php', "<?php\nfunction untyped(\$a) { return \$a->foo(\$a->bar); }\n");

        $process = self::newProcess(
            [PHP_BINARY, $this->psalm, '--no-progress', '--no-cache', ...$args],
            self::$tmpDir,
            [...self::agentEnvVarsToUnset(), ...$env],
        );
        $process->run();

        return $process;
    }
}
