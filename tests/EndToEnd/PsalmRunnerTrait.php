<?php

declare(strict_types=1);

namespace Psalm\Tests\EndToEnd;

use Symfony\Component\Process\Process;

use function array_merge;
use function array_unshift;
use function in_array;

use const PHP_BINARY;

trait PsalmRunnerTrait
{
    private string $psalm = __DIR__ . '/../../psalm';

    private string $psalter = __DIR__ . '/../../psalter';

    /**
     * Agent-harness environment variables that, when present in the developer
     * shell, would otherwise auto-switch Psalm to VoidProgress / monochrome.
     * Passing each as `false` to Symfony Process unsets it for the child
     * process, giving tests a deterministic default-output run.
     *
     * Kept in sync with CliUtils::runningUnderAiAgent().
     *
     * @return array<string, false>
     */
    private static function agentEnvVarsToUnset(): array
    {
        return [
            'CLAUDECODE' => false,
            'CLAUDE_CODE' => false,
            'CURSOR_AGENT' => false,
            'CURSOR_TRACE_ID' => false,
            'GEMINI_CLI' => false,
            'CODEX_SANDBOX' => false,
            'CODEX_THREAD_ID' => false,
            'AUGMENT_AGENT' => false,
            'CLINE_ACTIVE' => false,
            'OPENCODE_CLIENT' => false,
            'OPENCODE' => false,
            'AMP_CURRENT_THREAD_ID' => false,
            'TRAE_AI_SHELL_ID' => false,
            'COPILOT_CLI' => false,
            'ANTIGRAVITY_AGENT' => false,
            'PI_CODING_AGENT' => false,
            'REPL_ID' => false,
            'AI_AGENT' => false,
            'AGENT' => false,
            'NO_COLOR' => false,
        ];
    }

    /**
     * @param list<string> $args
     * @return array{STDOUT: string, STDERR: string, CODE: int|null}
     */
    private function runPsalm(
        array $args,
        string $workingDir,
        bool $shouldFail = false,
        bool $relyOnConfigDir = true,
    ): array {
        // Ensure CI agnostic output
        if (!in_array('--init', $args, true) && !in_array('--alter', $args, true)) {
            array_unshift($args, '--output-format=console');
        }

        // As config files all contain `resolveFromConfigFile="true"` Psalm
        // shouldn't need to be run from the same directory that the code being
        // analysed exists in.

        // Windows doesn't read shabangs, so to allow this to work on windows
        // we run `php psalm` rather than just `psalm`.

        if ($relyOnConfigDir) {
            $process = new Process(
                array_merge([PHP_BINARY, $this->psalm, '-c=' . $workingDir . '/psalm.xml'], $args),
                null,
                self::agentEnvVarsToUnset(),
            );
        } else {
            $process = new Process(
                array_merge([PHP_BINARY, $this->psalm], $args),
                $workingDir,
                self::agentEnvVarsToUnset(),
            );
        }

        if (!$shouldFail) {
            $process->mustRun();
        } else {
            $process->run();
            $this->assertEquals(2, $process->getExitCode(), 'Expected Psalm to report errors');
        }

        return [
            'STDOUT' => $process->getOutput(),
            'STDERR' => $process->getErrorOutput(),
            'CODE' => $process->getExitCode(),
        ];
    }
}
