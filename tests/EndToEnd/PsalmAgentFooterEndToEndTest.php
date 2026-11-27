<?php

declare(strict_types=1);

namespace Psalm\Tests\EndToEnd;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;

use function file_get_contents;
use function file_put_contents;
use function str_replace;
use function unlink;

use const PHP_BINARY;

/**
 * The footer of the console and compact reports under an AI coding agent, run as a user would
 */
final class PsalmAgentFooterEndToEndTest extends PsalmEndToEndTestCase
{
    private const DOCS_LINE = "Explain an issue: psalm --explain=<IssueType>, "
        . "or https://psalm.dev/docs/running_psalm/issues/<IssueType>.md\n";

    /**
     * @return array<string, array{string}>
     * @psalm-pure
     */
    public static function formats(): array
    {
        return ['compact' => ['compact'], 'console' => ['console']];
    }

    #[DataProvider('formats')]
    public function testNoIssuesPrintOnlyTheResultLine(string $format): void
    {
        $this->setUpProject("<?php\n\nfunction a(): int {\n    return 1;\n}\n");

        // CI doesn't turn the agent output off
        $process = $this->runAs(['--output-format=' . $format], true, ['CI' => 'true']);

        $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput());
        $this->assertSame("0 issues\n", $process->getOutput());
    }

    public function testACompactFooterListsTheFixableIssuesOnce(): void
    {
        $this->writeAProjectWithAMissingReturnType();

        $process = $this->runAs(['--output-format=compact'], true);

        $this->assertSame(2, $process->getExitCode(), $process->getErrorOutput());
        $this->assertSame(
            "ERROR src/A.php:3:10 MissingReturnType [*]: Method a does not have a return type, expecting 1\n"
            . "1 issue\n"
            . "[*] 1 fixable with --alter --issues=MissingReturnType (add --dry-run to preview)\n"
            . self::DOCS_LINE,
            $process->getOutput(),
        );
    }

    public function testAConsoleFooterReplacesTheLinksAndTheSummaries(): void
    {
        $this->writeAProjectWithAMissingReturnType();

        $process = $this->runAs(['--output-format=console'], true);

        $this->assertSame(2, $process->getExitCode(), $process->getErrorOutput());
        $output = $process->getOutput();
        $this->assertStringContainsString('MissingReturnType [*] - src/A.php:3:10 - ', $output);
        $this->assertStringContainsString(
            "1 issue\n[*] 1 fixable with --alter --issues=MissingReturnType (add --dry-run to preview)\n"
            . self::DOCS_LINE,
            $output,
        );
        $this->assertStringNotContainsString('(see https://psalm.dev/', $output);
        $this->assertStringNotContainsString('------', $output);
        $this->assertStringNotContainsString('errors found', $output);
        $this->assertStringNotContainsString('Checks took', $output);
        $this->assertStringNotContainsString('Psalm was able to infer', $output);
    }

    public function testTheFixableMarkerAndLineFollowTheSuggestionsOption(): void
    {
        $this->writeAProjectWithAMissingReturnType();

        $process = $this->runAs(['--output-format=compact', '--no-suggestions'], true);

        $this->assertSame(
            "ERROR src/A.php:3:10 MissingReturnType: Method a does not have a return type, expecting 1\n"
            . "1 issue\n"
            . self::DOCS_LINE,
            $process->getOutput(),
        );
    }

    public function testHiddenInfoIssuesAreCountedOnTheResultLine(): void
    {
        $this->writeAProjectWithAMissingReturnType();
        $this->reportMissingReturnTypeAsInfo();

        $hidden = $this->runAs(['--output-format=compact', '--show-info=false'], true);

        $this->assertSame(0, $hidden->getExitCode(), $hidden->getErrorOutput());
        $this->assertSame(
            "0 issues, 1 info issue hidden (--show-info=true shows them)\n",
            $hidden->getOutput(),
        );

        $shown = $this->runAs(['--output-format=compact', '--show-info=true'], true);

        $this->assertSame(0, $shown->getExitCode(), $shown->getErrorOutput());
        $this->assertSame(
            "INFO src/A.php:3:10 MissingReturnType [*]: Method a does not have a return type, expecting 1\n"
            . "1 issue\n"
            . "[*] 1 fixable with --alter --issues=MissingReturnType (add --dry-run to preview)\n"
            . self::DOCS_LINE,
            $shown->getOutput(),
        );
    }

    public function testAParameterTypeInferredFromItsCallsIsMarkedFixableAlsoOnAWarmCache(): void
    {
        $this->setUpProject(
            "<?php\n\nfinal class User\n{\n    public function setAge(\$age): void\n    {\n    }\n}\n\n"
            . "function run(User \$user): void\n{\n    \$user->setAge(5);\n}\n",
        );
        $config = file_get_contents(self::$tmpDir . '/psalm.xml');
        // the cache is what makes the second run warm
        $this->assertStringContainsString('cacheDirectory', (string) $config);

        $expected = "ERROR src/A.php:5:28 MissingParamType [*]: Parameter \$age has no provided type\n"
            . "ERROR src/A.php:5:28 UnusedParam: Param #1 is never referenced in this method\n"
            . "2 issues\n"
            . "[*] 1 fixable with --alter --issues=MissingParamType (add --dry-run to preview)\n"
            . self::DOCS_LINE;

        foreach (['cold', 'warm'] as $run) {
            $process = $this->runAs(['--output-format=compact'], true, [], false);

            $this->assertSame(2, $process->getExitCode(), $run . $process->getErrorOutput());
            $this->assertSame($expected, $process->getOutput(), $run);
        }

        // psalter fixes what the footer says it fixes, and the console hint of a run without an agent counts it
        $console = $this->runAs(['--output-format=console'], false, [], false);
        $this->assertStringContainsString('Psalm can automatically fix 1', $console->getOutput());

        $alter = self::newProcess(
            [
                PHP_BINARY,
                $this->psalter,
                '-c=' . self::$tmpDir . '/psalm.xml',
                '--issues=MissingParamType',
                '--dry-run',
            ],
            null,
            [...self::agentEnvVarsToUnset(), 'NO_COLOR' => '1'],
        );
        $alter->run();
        $this->assertStringContainsString('int $age', $alter->getOutput());
    }

    public function testWithoutAnAgentEveryFormatKeepsItsOutput(): void
    {
        $this->writeAProjectWithAMissingReturnType();

        $compact = $this->runAs(['--output-format=compact'], false);

        $this->assertSame(
            "ERROR src/A.php:3:10 MissingReturnType: Method a does not have a return type, expecting 1\n",
            $compact->getOutput(),
        );

        $console = $this->runAs(['--output-format=console'], false);

        $this->assertStringContainsString('ERROR: MissingReturnType - src/A.php:3:10 - ', $console->getOutput());
        $this->assertStringContainsString('(see https://psalm.dev/', $console->getOutput());
        $this->assertStringContainsString('1 errors found', $console->getOutput());
        $this->assertStringContainsString('Psalm can automatically fix 1', $console->getOutput());
        $this->assertStringNotContainsString('[*]', $console->getOutput());
    }

    public function testOtherFormatsAreTheSameUnderAnAgent(): void
    {
        $this->writeAProjectWithAMissingReturnType();

        foreach (['json', 'count', 'emacs'] as $format) {
            $agent = $this->runAs(['--output-format=' . $format], true);
            $plain = $this->runAs(['--output-format=' . $format], false);

            $this->assertSame($plain->getOutput(), $agent->getOutput(), $format);
        }
    }

    private function writeAProjectWithAMissingReturnType(): void
    {
        $this->setUpProject("<?php\n\nfunction a() {\n    return 1;\n}\n");
    }

    private function setUpProject(string $source): void
    {
        $this->runPsalmInit(1);

        $config = file_get_contents(self::$tmpDir . '/psalm.xml');
        file_put_contents(
            self::$tmpDir . '/psalm.xml',
            str_replace('<psalm', '<psalm runTaintAnalysis="false"', (string) $config),
        );
        // the fixture of the base class has errors of its own
        unlink(self::$tmpDir . '/src/FileWithErrors.php');
        file_put_contents(self::$tmpDir . '/src/A.php', $source);
    }

    private function reportMissingReturnTypeAsInfo(): void
    {
        $config = file_get_contents(self::$tmpDir . '/psalm.xml');
        file_put_contents(
            self::$tmpDir . '/psalm.xml',
            str_replace(
                '</psalm>',
                '<issueHandlers><MissingReturnType errorLevel="info"/></issueHandlers></psalm>',
                (string) $config,
            ),
        );
    }

    /**
     * @param list<string> $args
     * @param array<string, string> $env
     */
    private function runAs(array $args, bool $under_agent, array $env = [], bool $no_cache = true): Process
    {
        $process = self::newProcess(
            [
                PHP_BINARY,
                $this->psalm,
                '-c=' . self::$tmpDir . '/psalm.xml',
                ...($no_cache ? ['--no-cache'] : []),
                ...$args,
            ],
            null,
            [
                ...self::agentEnvVarsToUnset(),
                // CI sets FORCE_COLOR, which would colour the output of every run
                'FORCE_COLOR' => false,
                'NO_COLOR' => '1',
                'CI' => false,
                'GITHUB_ACTIONS' => false,
                'GITHUB_WORKFLOW' => false,
                ...($under_agent ? ['CLAUDECODE' => '1'] : []),
                ...$env,
            ],
        );
        $process->run();

        return $process;
    }
}
