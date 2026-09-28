<?php

declare(strict_types=1);

namespace Psalm\Tests;

use function basename;
use function escapeshellarg;
use function exec;
use function file_get_contents;
use function glob;
use function implode;
use function is_array;
use function is_bool;
use function is_string;
use function json_decode;
use function preg_match;
use function trim;

/**
 * Runs the Hack conformance fixtures (bin/hack-conformance/fixtures/*.hack)
 * through the real Hack typechecker (HHVM, via docker) and asserts each
 * verdict matches the `//// expect:` header of the linked Psalm test.
 *
 * This makes the type-variable feature's Hack-parity part of the unit suite.
 * It skips cleanly when the harness cannot run here — no docker, no daemon, not
 * Linux, or (to avoid a multi-hundred-MB pull in every CI shard) the pinned HHVM
 * image is not already present locally. Pull it once (or run
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
     * @return iterable<string, array{string, string, string}>
     */
    public function provideFixtures(): iterable
    {
        $paths = glob(self::HARNESS_DIR . '/fixtures/*.hack');

        foreach ($paths === false ? [] : $paths as $path) {
            $src = (string) file_get_contents($path);
            preg_match('#^////\s*expect:\s*(\S+)#m', $src, $expectMatch);
            preg_match('#^////\s*psalm-test:\s*(.+)$#m', $src, $testMatch);

            $name = basename($path);
            yield $name => [
                $name,
                $expectMatch[1] ?? '',
                trim($testMatch[1] ?? '(unlinked)'),
            ];
        }
    }

    /**
     * @dataProvider provideFixtures
     */
    public function testHhvmAgreesWithPsalmTest(string $fixture, string $expect, string $psalmTest): void
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
            "HHVM disagrees with $psalmTest for $fixture:\n" . $results[$fixture]['output'],
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

        $cmd = 'php ' . escapeshellarg(self::HARNESS_DIR . '/run.php') . ' --json 2>/dev/null';
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
