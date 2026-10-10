<?php

declare(strict_types=1);

namespace Psalm\Tests\Progress;

use Override;
use Psalm\Progress\Phase;
use Psalm\Tests\TestCase;

use function count;
use function getenv;
use function mb_strlen;
use function preg_match_all;
use function putenv;
use function str_contains;
use function str_repeat;
use function str_replace;
use function usleep;

use const PHP_EOL;

final class ProgressTest extends TestCase
{
    /** @var array<string, string|false> */
    private array $original_env = [];

    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        foreach (['COLUMNS', 'TERM', 'LC_ALL'] as $variable) {
            $this->original_env[$variable] = getenv($variable);
        }

        putenv('LC_ALL=en_US.UTF-8');
        putenv('TERM=xterm-256color');
        putenv('COLUMNS=200');
    }

    /**
     * @psalm-suppress ImmutableDependency the environment is restored
     */
    #[Override]
    public function tearDown(): void
    {
        foreach ($this->original_env as $variable => $value) {
            putenv($value === false ? $variable : "$variable=$value");
        }

        parent::tearDown();
    }

    public function testGridOverviewEndsTheLineOfTheMarkers(): void
    {
        $progress = $this->longProgress(in_ci: false);

        $progress->startPhase(Phase::ANALYSIS);
        $progress->expand(3);
        $progress->taskDone(2);
        $progress->taskDone(2);
        $progress->taskDone(2);
        $progress->finish();

        $this->assertStringStartsWith(
            'Analyzing files...' . PHP_EOL . 'EEE 3 / 3 (100%)' . PHP_EOL . '✓ Analysis',
            $progress->output,
        );
    }

    public function testThreadsAreShownOnlyForPhasesThatForked(): void
    {
        $progress = $this->longProgress(in_ci: true);

        // the pool size a phase may use is not what it ran with
        $progress->startPhase(Phase::SCAN, 16);
        $progress->expand(1);
        $progress->taskDone(0);

        $progress->startPhase(Phase::ANALYSIS, 16);
        $progress->setThreads(4);
        $progress->setThreads(2);
        $progress->expand(1);
        $progress->taskDone(0);
        $progress->finish();

        $rows = str_replace("\r", '', $progress->output);
        $this->assertMatchesRegularExpression('/^. Scan +1 file +\d+\.\ds$/mu', $rows);
        $this->assertMatchesRegularExpression('/^. Analysis +1 file +\d+\.\ds  4 threads$/mu', $rows);
    }

    public function testQuickPhasesAndFilesVisitedByTheAlterPhaseAreNotShown(): void
    {
        $progress = $this->longProgress(in_ci: true);

        $progress->startPhase(Phase::TAINT_GRAPH_RESOLUTION);
        $progress->startPhase(Phase::FINISHING);
        $progress->startPhase(Phase::ALTERING);
        $progress->expand(3);
        $progress->taskDone(0);
        $progress->taskDone(0);
        $progress->taskDone(0);
        $progress->finish();

        $this->assertStringNotContainsString('Taint graph', $progress->output);
        $this->assertStringNotContainsString('Finishing', $progress->output);
        $this->assertMatchesRegularExpression('/^. Alter +\d+\.\ds$/mu', $progress->output);
    }

    public function testRelayedWorkerOutputIsSetApartFromTheRows(): void
    {
        $progress = $this->longProgress(in_ci: true);

        $progress->relayWorkerOutput('');
        $progress->relayWorkerOutput('Warning: slow' . PHP_EOL . PHP_EOL);
        $progress->finish();

        // one blank line before and after, and finish() doesn't add another
        $this->assertSame(PHP_EOL . 'Warning: slow' . PHP_EOL . PHP_EOL, $progress->output);
    }

    public function testStatusLineIsDrawnWithinTheTerminalWidth(): void
    {
        putenv('COLUMNS=40');

        $progress = $this->defaultProgress();
        $progress->startPhase(Phase::ANALYSIS);
        $progress->setThreads(16);
        $progress->expand(12_629);
        usleep(110_000);
        $progress->taskDone(0);
        $progress->finish();

        $frames = $this->frames($progress->output);
        $this->assertNotEmpty($frames);

        foreach ($frames as $frame) {
            $this->assertLessThanOrEqual(39, mb_strlen($frame), $frame);
        }

        $this->assertStringStartsWith('Analyzing files', $frames[0]);
    }

    public function testStatusLineDropsTheBarThenTheThreadsAsTheTerminalNarrows(): void
    {
        $widths = [200 => [true, '16 threads'], 70 => [false, '16 threads'], 50 => [false, null]];

        foreach ($widths as $columns => [$bar, $threads]) {
            putenv("COLUMNS=$columns");

            $progress = $this->defaultProgress();
            $progress->startPhase(Phase::ANALYSIS);
            $progress->setThreads(16);
            $progress->expand(12_629);
            $progress->taskDone(0);
            usleep(110_000);
            $progress->taskDone(0);
            $progress->taskDone(0);
            $progress->finish();

            $last_frame = $this->lastFrame($progress->output);

            $this->assertLessThan($columns, mb_strlen($last_frame));
            $this->assertSame($bar, str_contains($last_frame, '░'), "$columns columns: $last_frame");
            $this->assertSame($threads !== null, str_contains($last_frame, 'threads'), "$columns columns: $last_frame");
        }
    }

    public function testScanStatusSeparatesTheCounterFromTheLabel(): void
    {
        $progress = $this->defaultProgress();
        $progress->startPhase(Phase::SCAN);
        $progress->expand(10);
        usleep(110_000);
        $progress->taskDone(0);
        $progress->finish();

        $this->assertSame('Scanning files · 1 / 10 files · 0s', $this->lastFrame($progress->output));
    }

    public function testDumbTerminalGetsNoEscapeSequences(): void
    {
        putenv('TERM=dumb');

        $progress = $this->defaultProgress();
        $progress->startPhase(Phase::ANALYSIS);
        $progress->expand(2);
        usleep(110_000);
        $progress->taskDone(0);
        $progress->finish();

        $this->assertStringNotContainsString("\e", $progress->output);
        $this->assertStringContainsString('Analyzing files', $progress->output);
    }

    public function testAsciiBarDoesNotLookLikeTheSeparator(): void
    {
        putenv('LC_ALL=C');

        $progress = $this->defaultProgress();
        $progress->startPhase(Phase::ANALYSIS);
        $progress->expand(12_629);
        usleep(110_000);
        $progress->taskDone(0);
        $progress->finish();

        $this->assertStringContainsString(str_repeat('.', 20), $progress->output);
        $this->assertStringNotContainsString(str_repeat('-', 20), $progress->output);
    }

    /**
     * @return list<string> the status lines, as they look on screen
     * @psalm-pure
     */
    private function frames(string $output): array
    {
        preg_match_all('/\r([^\r\n\e]+?)(?:\e\[K| *(?=\r|$))/u', $output, $matches);

        return $matches[1];
    }

    private function lastFrame(string $output): string
    {
        $frames = $this->frames($output);
        $this->assertNotEmpty($frames);

        return $frames[count($frames) - 1];
    }

    /**
     * @psalm-pure
     */
    private function longProgress(bool $in_ci): CapturingLongProgress
    {
        return new CapturingLongProgress(true, true, $in_ci);
    }

    /**
     * @psalm-pure
     */
    private function defaultProgress(): CapturingDefaultProgress
    {
        return new CapturingDefaultProgress();
    }
}
