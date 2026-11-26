<?php

declare(strict_types=1);

namespace Psalm\Tests\EndToEnd;

use PHPUnit\Framework\Attributes\DataProvider;

use function copy;

use const PHP_BINARY;

/**
 * Tests the taint analysis of the psalm command,
 * by launching a new process as if invoked by a real user.
 */
final class PsalmTaintAnalysisEndToEndTest extends PsalmEndToEndTestCase
{
    public function testTainting(): void
    {
        $this->runPsalmInit(1);
        $result = $this->runPsalm(['--taint-analysis'], self::$tmpDir, true);

        $this->assertStringContainsString('TaintedHtml', $result['STDOUT']);
        $this->assertStringContainsString('TaintedTextWithQuotes', $result['STDOUT']);
        $this->assertStringContainsString('4 errors', $result['STDOUT']);
        $this->assertStringContainsString('Psalm can automatically fix', $result['STDOUT']);
        $this->assertStringContainsString('--alter --issues=InvalidReturnType', $result['STDOUT']);
        $this->assertSame(2, $result['CODE']);
    }

    /**
     * A partial taint analysis deep-scans the file declaring a method a class named by the files it analyzes
     * inherits, whose body it analyzes again for that class: the classes the body names, like Other here, are
     * scanned, and what the body passes from its argument to its result is found.
     */
    public function testPartialTaintAnalysisOfACallOfAnInheritedMethod(): void
    {
        foreach (['Base', 'Other', 'Child', 'Use'] as $name) {
            copy(
                __DIR__ . '/../fixtures/PartialRunOfAnInheritedMethod/src/' . $name . '.php',
                self::$tmpDir . '/src/' . $name . '.php',
            );
        }

        $this->runPsalmInit(1);

        $result = $this->runPartialTaintAnalysis(['src/Use.php']);
        $this->assertStringNotContainsString('Could not get class storage', $result);
        $this->assertStringContainsString('TaintedHtml', $result);

        $result = $this->runPartialTaintAnalysis(['src/Use.php', 'src/Child.php']);
        $this->assertStringContainsString('TaintedHtml', $result);
    }

    public function testTaintingWithoutInit(): void
    {
        $result = $this->runPsalm(['--taint-analysis'], self::$tmpDir, true, false);

        $this->assertStringContainsString('TaintedHtml', $result['STDOUT']);
        $this->assertStringContainsString('TaintedTextWithQuotes', $result['STDOUT']);
        $this->assertStringContainsString('4 errors', $result['STDOUT']);
        $this->assertSame(2, $result['CODE']);
    }

    public function testNoTaintAnalysisSkipsTheTaintAnalysisTheConfigTurnsOn(): void
    {
        $this->runPsalmInit(1);

        $result = $this->runPsalm([], self::$tmpDir, true);
        $this->assertStringContainsString('TaintedHtml', $result['STDOUT']);
        $this->assertStringContainsString('4 errors', $result['STDOUT']);

        $result = $this->runPsalm(['--no-taint-analysis'], self::$tmpDir, true);
        $this->assertStringNotContainsString('Tainted', $result['STDOUT']);
        $this->assertStringContainsString('2 errors', $result['STDOUT']);
    }

    /**
     * @param list<string> $args
     */
    #[DataProvider('provideOptionsThatNeedTaintAnalysis')]
    public function testNoTaintAnalysisCannotBeCombinedWithAnOptionThatNeedsTaintAnalysis(array $args): void
    {
        $this->runPsalmInit(1);

        $process = self::newProcess(
            [PHP_BINARY, $this->psalm, '--no-progress', '--no-taint-analysis', ...$args],
            self::$tmpDir,
            self::agentEnvVarsToUnset(),
        );
        $process->run();

        $this->assertSame(1, $process->getExitCode(), $process->getOutput() . $process->getErrorOutput());
        $this->assertStringContainsString(
            '--no-taint-analysis cannot be combined with --taint-analysis, --track-tainted-input,'
                . ' --security-analysis or --dump-taint-graph',
            $process->getErrorOutput(),
        );
    }

    /**
     * @return iterable<string, array{list<string>}>
     * @psalm-pure
     */
    public static function provideOptionsThatNeedTaintAnalysis(): iterable
    {
        yield '--taint-analysis' => [['--taint-analysis']];
        yield '--track-tainted-input' => [['--track-tainted-input']];
        yield '--security-analysis' => [['--security-analysis']];
        yield '--dump-taint-graph' => [['--dump-taint-graph=taints.dot']];
    }

    public function testTaintGraphDumping(): void
    {
        $this->runPsalmInit(1);
        $result = $this->runPsalm(
            [
                '--taint-analysis',
                '--dump-taint-graph=' . self::$tmpDir . '/taints.dot',
            ],
            self::$tmpDir,
            true,
        );

        $this->assertSame(2, $result['CODE']);
        $this->assertFileEquals(
            __DIR__ . '/../fixtures/expected_taint_graph.dot',
            self::$tmpDir . '/taints.dot',
        );
    }

    /**
     * What a taint analysis of the files $files of the project prints
     *
     * @param list<string> $files
     */
    private function runPartialTaintAnalysis(array $files): string
    {
        $process = self::newProcess(
            [
                PHP_BINARY,
                $this->psalm,
                '--output-format=console',
                '--monochrome',
                '--no-progress',
                '--no-cache',
                '--taint-analysis',
                ...$files,
            ],
            self::$tmpDir,
            self::agentEnvVarsToUnset(),
        );
        $process->run();

        return $process->getOutput() . $process->getErrorOutput();
    }
}
