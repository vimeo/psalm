<?php

declare(strict_types=1);

namespace Psalm\Tests;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function escapeshellarg;
use function exec;
use function file_get_contents;
use function implode;
use function is_array;
use function is_bool;
use function is_string;
use function json_decode;
use function ksort;
use function preg_match;
use function str_ends_with;
use function strlen;
use function substr;

use const PHP_BINARY;

/**
 * Runs the Hack conformance fixtures (bin/hack-conformance/fixtures/<topic>/*.hack)
 * through the real Hack typechecker (HHVM, via docker) and asserts each verdict
 * matches the fixture's `//// expect:` header, which HackConformanceTranspiledTest
 * (the fixtures transpiled to PHP) asserts of Psalm. Together they check that
 * Psalm and Hack agree on every fixture.
 *
 * The HHVM half skips cleanly when the harness cannot run here — no docker, no
 * daemon, not Linux, or (to avoid a multi-hundred-MB pull in every CI shard)
 * the pinned HHVM image is not already present locally. Pull it once (or run
 * `php bin/hack-conformance/run.php`) to enable the checks.
 *
 * @see bin/hack-conformance/README.md
 */
final class HackConformanceTest extends TestCase
{
    private const HARNESS_DIR = __DIR__ . '/../bin/hack-conformance';

    /**
     * Cached across the data-provider rows: HHVM runs once, all rows assert.
     *
     * @var array{available: bool, reason: string, results: array<string, array{actual: string, output: string}>}|null
     */
    private static ?array $harness = null;

    /**
     * @return iterable<string, array{string, string}>
     */
    public function provideFixtures(): iterable
    {
        $fixtures = [];
        $root = self::HARNESS_DIR . '/fixtures/';
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));

        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            $path = $file->getPathname();
            if (!str_ends_with($path, '.hack')) {
                continue;
            }

            preg_match('#^////\s*expect:\s*(\S+)#m', (string) file_get_contents($path), $expectMatch);

            $name = substr($path, strlen($root));
            $fixtures[$name] = [$name, $expectMatch[1] ?? ''];
        }

        ksort($fixtures);

        return $fixtures;
    }

    /**
     * The transpiled Psalm cases are generated from HHVM's parse trees of the
     * fixtures: they must be regenerated whenever a fixture (or the transpiler)
     * changes. HackConformanceTranspiledTest checks it too, before its cases,
     * but cannot tell when HHVM is unavailable; this check skips instead, so the
     * HHVM CI job (--fail-on-skipped) guarantees it ran.
     */
    public function testTranspiledSuiteIsUpToDate(): void
    {
        $output = [];
        $exitCode = 0;
        exec(
            escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(self::HARNESS_DIR . '/transpile.php') . ' --check 2>&1',
            $output,
            $exitCode,
        );

        /** @var list<string> $output */
        if ($exitCode === 3) {
            $this->markTestSkipped(implode("\n", $output));
        }

        $this->assertSame(0, $exitCode, implode("\n", $output));
    }

    /**
     * @dataProvider provideFixtures
     */
    public function testHhvmAgreesWithFixture(string $fixture, string $expect): void
    {
        // A malformed fixture header must fail loudly rather than be compared as
        // an empty string; check it before the availability skip so it is caught
        // even where the harness itself cannot run.
        $this->assertContains(
            $expect,
            ['no-errors', 'error'],
            "Fixture $fixture is missing a valid `//// expect: no-errors|error` header",
        );

        $harness = self::harness();

        if (!$harness['available']) {
            $this->markTestSkipped($harness['reason']);
        }

        $results = $harness['results'];

        $this->assertArrayHasKey(
            $fixture,
            $results,
            "The harness produced no HHVM result for $fixture",
        );

        $actual = $results[$fixture]['actual'];

        $this->assertContains(
            $actual,
            ['no-errors', 'error'],
            "HHVM produced an unrecognised verdict for $fixture:\n" . $results[$fixture]['output'],
        );

        $this->assertSame(
            $expect,
            $actual,
            "HHVM disagrees with the `//// expect:` header of $fixture, which its transpiled case in "
                . "HackConformanceTranspiledTest asserts of Psalm:\n" . $results[$fixture]['output'],
        );
    }

    /**
     * @return array{available: bool, reason: string, results: array<string, array{actual: string, output: string}>}
     */
    private static function harness(): array
    {
        if (self::$harness !== null) {
            return self::$harness;
        }

        $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(self::HARNESS_DIR . '/run.php') . ' --json 2>/dev/null';
        $lines = [];
        exec($cmd, $lines);

        /** @var list<string> $lines */
        $decoded = json_decode(implode("\n", $lines), true);

        if (!is_array($decoded) || !isset($decoded['available']) || !is_bool($decoded['available'])) {
            return self::$harness = [
                'available' => false,
                'reason' => 'the Hack conformance runner produced no usable output',
                'results' => [],
            ];
        }

        if ($decoded['available'] === false) {
            return self::$harness = [
                'available' => false,
                'reason' => self::stringOr($decoded['reason'] ?? null, 'harness unavailable'),
                'results' => [],
            ];
        }

        $results = [];

        foreach (self::toArray($decoded['results'] ?? null) as $name => $row) {
            if (!is_string($name) || !is_array($row)) {
                continue;
            }

            $results[$name] = [
                'actual' => self::stringOr($row['actual'] ?? null, ''),
                'output' => self::stringOr($row['output'] ?? null, ''),
            ];
        }

        return self::$harness = [
            'available' => true,
            'reason' => '',
            'results' => $results,
        ];
    }

    /**
     * The value if it is a string, otherwise the fallback — used to read a
     * field out of the untyped JSON the runner emits.
     *
     * @psalm-pure
     */
    private static function stringOr(mixed $value, string $default): string
    {
        return is_string($value) ? $value : $default;
    }

    /**
     * @return array<array-key, mixed>
     * @psalm-pure
     */
    private static function toArray(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }
}
