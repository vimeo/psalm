<?php

declare(strict_types=1);

namespace Psalm\Tests\EndToEnd;

use PHPUnit\Framework\Attributes\DataProvider;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Symfony\Component\Process\Process;

use function file_get_contents;
use function file_put_contents;
use function implode;
use function str_contains;
use function str_replace;
use function strpos;
use function substr_count;
use function unlink;

use const PHP_BINARY;
use const PHP_EOL;

/**
 * Tests the psalter and psalm-refactor commands, and psalm --alter,
 * by launching a new process as if invoked by a real user.
 */
final class PsalterEndToEndTest extends PsalmEndToEndTestCase
{
    public function testAlter(): void
    {
        $this->runPsalmInit();

        $psalmXml = file_get_contents(self::$tmpDir . '/psalm.xml');
        $psalmXml = str_replace('<psalm', '<psalm runTaintAnalysis="false"', (string)$psalmXml);
        file_put_contents(self::$tmpDir . '/psalm.xml', $psalmXml);

        $this->assertStringContainsString(
            "\n        1 file changed        \n",
            $this->runPsalm(['--alter', '--issues=all'], self::$tmpDir, false, true)['STDOUT'],
        );

        $this->assertSame(0, $this->runPsalm([], self::$tmpDir)['CODE']);
    }

    public function testPsalter(): void
    {
        $this->runPsalmInit();
        $psalmXml = file_get_contents(self::$tmpDir . '/psalm.xml');
        $psalmXml = str_replace('<psalm', '<psalm runTaintAnalysis="false"', (string)$psalmXml);
        file_put_contents(self::$tmpDir . '/psalm.xml', $psalmXml);

        (self::newProcess([PHP_BINARY, $this->psalter, '--alter', '--issues=InvalidReturnType'], self::$tmpDir))->mustRun();
        $this->assertSame(0, $this->runPsalm([], self::$tmpDir)['CODE']);
    }

    public function testPsalterAddsPurityAnnotationsWithoutTheConfigFlag(): void
    {
        $this->runPsalmInit();
        $psalmXml = file_get_contents(self::$tmpDir . '/psalm.xml');
        $psalmXml = str_replace('<psalm', '<psalm runTaintAnalysis="false"', (string)$psalmXml);
        file_put_contents(self::$tmpDir . '/psalm.xml', $psalmXml);
        file_put_contents(
            self::$tmpDir . '/src/Slug.php',
            "<?php\n\nfunction slug(string \$text): string\n{\n    return strtolower(trim(\$text));\n}\n",
        );

        (self::newProcess([PHP_BINARY, $this->psalter, '--alter', '--issues=MissingPureAnnotation'], self::$tmpDir))->mustRun();

        $this->assertStringContainsString('@psalm-pure', (string) file_get_contents(self::$tmpDir . '/src/Slug.php'));
    }

    /**
     * @param list<string> $args
     */
    #[DataProvider('provideRefactorMoveInvocations')]
    public function testRefactorMovesAMethodIntoAnotherClass(bool $viaPsalm, array $args): void
    {
        $this->runPsalmInit();
        $psalmXml = file_get_contents(self::$tmpDir . '/psalm.xml');
        $psalmXml = str_replace('<psalm', '<psalm runTaintAnalysis="false"', (string)$psalmXml);
        file_put_contents(self::$tmpDir . '/psalm.xml', $psalmXml);
        file_put_contents(
            self::$tmpDir . '/src/Moves.php',
            "<?php\n\nnamespace Ns;\n\nclass A\n{\n    public static function foo(): void\n    {\n    }\n}\n\n"
            . "class B\n{\n}\n",
        );

        $process = self::newProcess(
            [PHP_BINARY, $viaPsalm ? $this->psalm : $this->psalmRefactor, ...$args],
            self::$tmpDir,
            self::agentEnvVarsToUnset(),
        );
        $process->run();

        $this->assertStringNotContainsString('Cannot locate', $process->getErrorOutput());
        $this->assertStringNotContainsString('No --move or --rename arguments supplied', $process->getErrorOutput());

        $contents = (string) file_get_contents(self::$tmpDir . '/src/Moves.php');
        $this->assertSame(1, substr_count($contents, 'function foo'), $contents);
        $this->assertGreaterThan(
            (int) strpos($contents, 'class B'),
            (int) strpos($contents, 'function foo'),
            'foo should now be declared in B: ' . $contents . $process->getErrorOutput(),
        );
    }

    /**
     * @return iterable<string, array{bool, list<string>}>
     * @psalm-pure
     */
    public static function provideRefactorMoveInvocations(): iterable
    {
        yield 'psalm-refactor with separate values' => [
            false,
            ['--no-cache', '--move', 'Ns\A::foo', '--into', 'Ns\B'],
        ];

        yield 'psalm-refactor with values after an equals sign' => [
            false,
            ['--monochrome', '--move=Ns\A::foo', '--into=Ns\B'],
        ];

        yield 'psalm --refactor' => [
            true,
            ['--refactor', '--move', 'Ns\A::foo', '--into', 'Ns\B'],
        ];
    }

    public function testPsalmForwardsToPsalterWhenAlterFollowsAPath(): void
    {
        $source = $this->writeAFileWithAMissingReturnType();

        // runPsalm() asserts the exit code is 2 when $shouldFail is true
        $result = $this->runPsalm(
            ['src/A.php', '--alter', '--issues=MissingReturnType', '--dry-run', '--no-cache'],
            self::$tmpDir,
            true,
            false,
        );

        $this->assertStringContainsString('+function a(): int {', $result['STDOUT']);
        $this->assertSame($source, file_get_contents(self::$tmpDir . '/src/A.php'));
    }

    /**
     * @param list<string> $args
     */
    #[DataProvider('provideCommandsWithAnUnrecognisedShortOption')]
    public function testAnUnrecognisedShortOptionIsRejected(string $script, array $args): void
    {
        $this->runPsalmInit();

        $process = self::newProcess([PHP_BINARY, $script, ...$args], self::$tmpDir, self::agentEnvVarsToUnset());
        $process->run();

        $this->assertSame(1, $process->getExitCode(), $process->getOutput() . $process->getErrorOutput());
        $this->assertStringContainsString('Unrecognised argument "-z"', $process->getErrorOutput());
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     * @psalm-pure
     */
    public static function provideCommandsWithAnUnrecognisedShortOption(): iterable
    {
        $issues = ['--issues=MissingReturnType', '--dry-run', '-z'];

        yield 'psalter' => [__DIR__ . '/../../psalter', $issues];
        yield 'psalm --alter' => [__DIR__ . '/../../psalm', ['--alter', ...$issues]];
        yield 'psalm-refactor' => [__DIR__ . '/../../psalm-refactor', ['--move', 'A::b', '--into', 'C', '-z']];
    }

    public function testPsalterRealRunThatChangesFilesExitsWithCode0(): void
    {
        $process = $this->runPsalterOnAFileWithAMissingReturnType([]);

        $this->assertSame(0, $process->getExitCode());
    }

    public function testPsalterAppliesAFixOnceWhenPathsOverlap(): void
    {
        $this->runPsalmInit();
        $psalmXml = file_get_contents(self::$tmpDir . '/psalm.xml');
        $psalmXml = str_replace('<psalm', '<psalm runTaintAnalysis="false"', (string)$psalmXml);
        file_put_contents(self::$tmpDir . '/psalm.xml', $psalmXml);
        file_put_contents(self::$tmpDir . '/src/A.php', "<?php\n\nfunction a() {\n    return 1;\n}\n");
        file_put_contents(self::$tmpDir . '/src/B.php', "<?php\n\nfunction b() {\n    return 2;\n}\n");

        $process = self::newProcess(
            [PHP_BINARY, $this->psalter, '--no-cache', '--issues=MissingReturnType', 'src', 'src/A.php'],
            self::$tmpDir,
            self::agentEnvVarsToUnset(),
        );
        $process->mustRun();

        $this->assertSame(
            "<?php\n\nfunction a(): int {\n    return 1;\n}\n",
            file_get_contents(self::$tmpDir . '/src/A.php'),
        );
        $this->assertSame(
            "<?php\n\nfunction b(): int {\n    return 2;\n}\n",
            file_get_contents(self::$tmpDir . '/src/B.php'),
        );
        $this->assertStringContainsString("\n       2 files changed        \n", $process->getOutput());
    }

    public function testPsalterPrintsTheDiffOfARealRun(): void
    {
        $process = $this->runPsalterOnAFileWithAMissingReturnType([]);
        $output = $process->getOutput();

        $this->assertStringNotContainsString('Altered', $process->getErrorOutput());
        $this->assertStringContainsString("1) src/A.php:3\n", $output);
        $this->assertStringContainsString("\n+function a(): int {\n", $output);
        $this->assertStringContainsString("\n        1 file changed        \n", $output);
    }

    /**
     * @param array<string, string> $env
     */
    #[DataProvider('provideColorEnvironments')]
    public function testPsalterColorsPipedOutputOnlyWhenForced(array $env, bool $expect_color): void
    {
        $output = $this->runPsalterOnAFileWithAMissingReturnType([], $env)->getOutput();

        $this->assertSame($expect_color, str_contains($output, "\e["));
    }

    /**
     * @return iterable<string, array{array<string, string>, bool}>
     * @psalm-pure
     */
    public static function provideColorEnvironments(): iterable
    {
        yield 'piped' => [[], false];
        yield 'FORCE_COLOR' => [['FORCE_COLOR' => '1'], true];
    }

    public function testPsalterNoDiffListsTheChangedFilesWithoutTheDiffAndStillWritesThem(): void
    {
        $process = $this->runPsalterOnAFileWithAMissingReturnType(['--no-diff']);
        $output = $process->getOutput();

        $this->assertStringContainsString('Altered src/A.php', $process->getErrorOutput());

        $this->assertStringStartsWith("1) src/A.php:3\n------------------------------\n", $output);
        $this->assertStringNotContainsString('@@', $output);
        $this->assertStringNotContainsString('+function a()', $output);
        $this->assertStringNotContainsString('-function a()', $output);
        $this->assertStringContainsString("\n        1 file changed        \n", $output);
    }

    /**
     * @param list<string> $extra_args
     * @param array<string, string> $env
     */
    private function runPsalterOnAFileWithAMissingReturnType(array $extra_args, array $env = []): Process
    {
        $this->runPsalmInit();
        $psalmXml = file_get_contents(self::$tmpDir . '/psalm.xml');
        $psalmXml = str_replace('<psalm', '<psalm runTaintAnalysis="false"', (string)$psalmXml);
        file_put_contents(self::$tmpDir . '/psalm.xml', $psalmXml);
        file_put_contents(self::$tmpDir . '/src/A.php', "<?php\n\nfunction a() {\n    return 1;\n}\n");

        $process = self::newProcess(
            [PHP_BINARY, $this->psalter, '--no-cache', '--issues=MissingReturnType', ...$extra_args, 'src'],
            self::$tmpDir,
            $env + self::agentEnvVarsToUnset(),
        );
        $process->mustRun();

        $this->assertSame(
            "<?php\n\nfunction a(): int {\n    return 1;\n}\n",
            file_get_contents(self::$tmpDir . '/src/A.php'),
        );

        return $process;
    }

    public function testPsalterFixesTheIssuesAPluginDeclares(): void
    {
        $this->runPsalmInit(1);
        $psalm_xml = (string) file_get_contents(self::$tmpDir . '/psalm.xml');
        file_put_contents(self::$tmpDir . '/psalm.xml', str_replace(
            ['<psalm', '</psalm>'],
            [
                '<psalm runTaintAnalysis="false"',
                '<plugins><pluginClass class="Psalm\Tests\Config\Plugin\FixableIssuePlugin" /></plugins></psalm>',
            ],
            $psalm_xml,
        ));
        unlink(self::$tmpDir . '/src/FileWithErrors.php');
        file_put_contents(
            self::$tmpDir . '/src/functions.php',
            "<?php\n\nfunction legacy_call(): int\n{\n    return 1;\n}\n\nfunction modern_call(): int\n{\n    return 2;\n}\n",
        );

        $list = self::newProcess([PHP_BINARY, $this->psalter, '--list-supported-issues'], self::$tmpDir);
        $list->mustRun();
        $this->assertSame(
            implode(',', [...ProjectAnalyzer::getSupportedIssuesToFix(), 'LegacyCall']) . PHP_EOL,
            $list->getOutput(),
        );

        foreach (['a', 'b', 'c'] as $file) {
            file_put_contents(
                self::$tmpDir . '/src/' . $file . '.php',
                "<?php\n\nfunction $file(): int\n{\n    return legacy_call();\n}\n",
            );
        }

        (self::newProcess([PHP_BINARY, $this->psalter, '--issues=LegacyCall', '--threads=2'], self::$tmpDir))->mustRun();

        foreach (['a', 'b', 'c'] as $file) {
            $this->assertStringContainsString(
                'return modern_call();',
                (string) file_get_contents(self::$tmpDir . '/src/' . $file . '.php'),
            );
        }
    }
}
