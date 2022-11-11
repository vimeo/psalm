<?php

namespace Psalm\Tests\EndToEnd;

use Symfony\Component\Process\Process;

use function array_merge;

trait PsalmRunnerTrait
{
    /** @var string */
    private $psalm = __DIR__ . '/../../psalm';

    /** @var string */
    private $psalter = __DIR__ . '/../../psalter';

    /**
     * @param list<string> $args
     *
     * @return array{STDOUT: string, STDERR: string, CODE: int|null}
     */
    private function runPsalm(
        array $args,
        string $workingDir,
        bool $shouldFail = false,
        bool $relyOnConfigDir = true
    ): array {
        // As config files all contain `resolveFromConfigFile="true"` Psalm
        // shouldn't need to be run from the same directory that the code being
        // analysed exists in.

        // Windows doesn't read shabangs, so to allow this to work on windows
        // we run `php psalm` rather than just `psalm`.

        if ($relyOnConfigDir) {
            $process = new Process(array_merge(['php', $this->psalm, '-c=' . $workingDir . '/psalm.xml'], $args), null);
        } else {
            $process = new Process(array_merge(['php', $this->psalm], $args), $workingDir);
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
