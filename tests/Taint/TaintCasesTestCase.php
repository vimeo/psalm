<?php

declare(strict_types=1);

namespace Psalm\Tests\Taint;

use PHPUnit\Framework\Attributes\DataProvider;
use Psalm\Config;
use Psalm\Config\IssueHandler;
use Psalm\Context;
use Psalm\Exception\CodeException;
use Psalm\Tests\TaintTest;
use Psalm\Tests\TestCase;

use function preg_quote;
use function str_starts_with;
use function strpos;

/**
 * Runs the cases of TaintTest::providerValidCodeParse() and TaintTest::providerInvalidCodeParse().
 *
 * They take long enough that a single test file would keep one CI core busy long after the others
 * are done, so each subclass runs every SHARDS-th case, starting at its own.
 */
abstract class TaintCasesTestCase extends TestCase
{
    protected const SHARDS = 4;

    /**
     * @return array<string, array{code: string}>
     */
    abstract public static function validCases(): array;

    /**
     * @return array<string, array{code: string, error_message: string, php_version?: string}>
     */
    abstract public static function invalidCases(): array;

    /**
     * The cases at positions $shard, $shard + SHARDS, $shard + 2 * SHARDS, ...
     *
     * @template T
     * @param array<string, T> $cases
     * @return array<string, T>
     * @psalm-pure
     */
    protected static function shard(array $cases, int $shard): array
    {
        $result = [];
        $position = 0;
        foreach ($cases as $name => $case) {
            if ($position % self::SHARDS === $shard) {
                $result[$name] = $case;
            }
            $position++;
        }

        return $result;
    }

    #[DataProvider('validCases')]
    public function testValidCode(string $code): void
    {
        $test_name = $this->getTestName();
        if (strpos($test_name, 'SKIPPED-') !== false) {
            $this->markTestSkipped('Skipped due to a bug.');
        }

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            $code,
        );

        $this->project_analyzer->setPhpVersion('8.0', 'tests');

        $this->project_analyzer->trackTaintedInputs();
        foreach (TaintTest::IGNORE as $issue_name) {
            Config::getInstance()->setCustomErrorLevel($issue_name, Config::REPORT_SUPPRESS);
        }

        $this->project_analyzer->getCodebase()->config->initializePlugins($this->project_analyzer);

        $this->analyzeFile($file_path, new Context(), false);
    }

    #[DataProvider('invalidCases')]
    public function testInvalidCode(string $code, string $error_message, string $php_version = '8.0'): void
    {
        if (strpos($this->getTestName(), 'SKIPPED-') !== false) {
            $this->markTestSkipped();
        }

        $this->expectException(CodeException::class);
        $this->expectExceptionMessageMatches('/\b' . preg_quote($error_message, '/') . '\b/');

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->project_analyzer->setPhpVersion($php_version, 'tests');

        $this->addFile(
            $file_path,
            $code,
        );

        $this->project_analyzer->trackTaintedInputs();
        foreach (TaintTest::IGNORE as $issue_name) {
            Config::getInstance()->setCustomErrorLevel($issue_name, Config::REPORT_SUPPRESS);
        }

        $this->analyzeFile($file_path, new Context(), false);
    }

    /**
     * Taints are resolved the same when unused variables are tracked too, as the CLI does by default.
     */
    #[DataProvider('validCases')]
    public function testValidCodeTrackingUnusedVariables(string $code): void
    {
        $this->trackUnusedVariables();
        $this->testValidCode($code);
    }

    /**
     * Taints are resolved the same when unused variables are tracked too, as the CLI does by default.
     */
    #[DataProvider('invalidCases')]
    public function testInvalidCodeTrackingUnusedVariables(
        string $code,
        string $error_message,
        string $php_version = '8.0',
    ): void {
        $this->trackUnusedVariables();
        $this->testInvalidCode($code, $error_message, $php_version);
    }

    private function trackUnusedVariables(): void
    {
        $this->project_analyzer->getCodebase()->find_unused_variables = true;

        foreach (IssueHandler::getAllIssueTypes() as $issue_name) {
            if (str_starts_with($issue_name, 'Unused') || str_starts_with($issue_name, 'PossiblyUnused')) {
                Config::getInstance()->setCustomErrorLevel($issue_name, Config::REPORT_SUPPRESS);
            }
        }
    }
}
