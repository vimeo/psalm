<?php

declare(strict_types=1);

namespace Psalm\Tests\EndToEnd;

use function file_get_contents;
use function file_put_contents;
use function str_replace;
use function unlink;

/**
 * Tests how the psalm command writes and applies baselines,
 * by launching a new process as if invoked by a real user.
 */
final class PsalmBaselineEndToEndTest extends PsalmEndToEndTestCase
{
    public function testPsalmSetBaseline(): void
    {
        $this->runPsalmInit(1);
        $this->runPsalm(['--set-baseline'], self::$tmpDir, true);

        $this->assertSame(0, $this->runPsalm([], self::$tmpDir)['CODE']);
    }

    public function testPsalmSetBaselineWithArgument(): void
    {
        $this->runPsalmInit(1);
        $this->runPsalm(['--set-baseline=psalm-custom-baseline.xml'], self::$tmpDir, true);

        $this->assertSame(0, $this->runPsalm([], self::$tmpDir)['CODE']);
    }

    public function testBaselineDoesntHideUnsuppressibleIssues(): void
    {
        // another test can leave its baseline, which --set-baseline would apply
        @unlink(self::$tmpDir . '/psalm-baseline.xml');

        $this->runPsalmInit(1);

        // with taint analysis, Psalm doesn't exit with an error code on GitHub
        $psalmXml = (string) file_get_contents(self::$tmpDir . '/psalm.xml');
        file_put_contents(
            self::$tmpDir . '/psalm.xml',
            str_replace('<psalm', '<psalm runTaintAnalysis="false"', $psalmXml),
        );

        $this->runPsalm(['--set-baseline'], self::$tmpDir, true);

        $psalmXml = (string) file_get_contents(self::$tmpDir . '/psalm.xml');
        file_put_contents(
            self::$tmpDir . '/psalm.xml',
            str_replace(
                '</psalm>',
                '<unsuppressibleIssues><issue name="InvalidReturnType" /></unsuppressibleIssues></psalm>',
                $psalmXml,
            ),
        );

        $result = $this->runPsalm([], self::$tmpDir, true);
        $this->assertMatchesRegularExpression('/ERROR\S*: InvalidReturnType/', $result['STDOUT']);
        $this->assertDoesNotMatchRegularExpression('/ERROR\S*: InvalidReturnStatement/', $result['STDOUT']);

        $this->runPsalm(['--update-baseline'], self::$tmpDir, true);
        $baseline = (string) file_get_contents(self::$tmpDir . '/psalm-baseline.xml');
        $this->assertStringNotContainsString('<InvalidReturnType', $baseline);
        $this->assertStringContainsString('<InvalidReturnStatement', $baseline);

        unlink(self::$tmpDir . '/psalm-baseline.xml');
    }

    public function testPsalmSetBaselineWithUseBaselineKeepsConfig(): void
    {
        $this->runPsalmInit(1);
        $this->runPsalm(['--set-baseline'], self::$tmpDir, true);
        $config_before = file_get_contents(self::$tmpDir . '/psalm.xml');
        $other_baseline = self::$tmpDir . '/psalm-other-baseline.xml';

        $this->runPsalm(['--use-baseline=' . $other_baseline, '--set-baseline=' . $other_baseline], self::$tmpDir);

        $this->assertFileExists($other_baseline);
        $this->assertSame($config_before, file_get_contents(self::$tmpDir . '/psalm.xml'));
    }

    public function testPsalmSetBaselineWithoutArgumentWritesUseBaselineFile(): void
    {
        $this->runPsalmInit(1);
        $config_before = file_get_contents(self::$tmpDir . '/psalm.xml');
        $run_baseline = self::$tmpDir . '/psalm-run-baseline.xml';

        $this->runPsalm(['--use-baseline=' . $run_baseline, '--set-baseline'], self::$tmpDir);

        $this->assertFileExists($run_baseline);
        $this->assertSame($config_before, file_get_contents(self::$tmpDir . '/psalm.xml'));
    }
}
