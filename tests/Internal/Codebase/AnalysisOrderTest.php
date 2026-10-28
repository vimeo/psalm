<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Codebase;

use Closure;
use PHPUnit\Framework\Attributes\DataProvider;
use Psalm\Internal\Codebase\AnalysisOrder;
use Psalm\Tests\TestCase;

use function array_keys;

final class AnalysisOrderTest extends TestCase
{
    /**
     * @param array<string, int> $sizes
     * @return Closure(string): int
     * @psalm-pure
     */
    private static function sizes(array $sizes): Closure
    {
        return static fn(string $path): int => $sizes[$path];
    }

    /**
     * @param list<string> $paths
     * @return array<string, string>
     * @psalm-pure
     */
    private static function files(array $paths): array
    {
        $files = [];
        foreach ($paths as $path) {
            $files[$path] = $path;
        }

        return $files;
    }

    /**
     * @param list<string> $paths
     * @param array<string, float> $timings
     * @param array<string, int> $sizes
     * @param list<string> $expected
     */
    #[DataProvider('provideOrders')]
    public function testLongestFirst(array $paths, array $timings, array $sizes, array $expected): void
    {
        $ordered = AnalysisOrder::longestFirst(self::files($paths), $timings, self::sizes($sizes));

        self::assertSame($expected, array_keys($ordered));
    }

    /**
     * @return iterable<string, array{list<string>, array<string, float>, array<string, int>, list<string>}>
     * @psalm-pure
     */
    public static function provideOrders(): iterable
    {
        yield 'recorded timings decide, not size' => [
            ['/a', '/b', '/c'],
            ['/a' => 0.5, '/b' => 7.0, '/c' => 2.0],
            ['/a' => 9000, '/b' => 10, '/c' => 10],
            ['/b', '/c', '/a'],
        ];

        yield 'size decides without any history' => [
            ['/a', '/b', '/c'],
            [],
            ['/a' => 10, '/b' => 300, '/c' => 20],
            ['/b', '/c', '/a'],
        ];

        yield 'ties are broken by path' => [
            ['/c', '/a', '/b'],
            ['/a' => 1.0, '/b' => 1.0, '/c' => 1.0],
            ['/a' => 1, '/b' => 1, '/c' => 1],
            ['/a', '/b', '/c'],
        ];

        yield 'equal size without history is ordered by path' => [
            ['/c', '/a', '/b'],
            [],
            ['/a' => 5, '/b' => 5, '/c' => 5],
            ['/a', '/b', '/c'],
        ];

        // /known costs 2 s for 100 bytes, so the 1000-byte /big is expected to cost 20 s and outranks it,
        // while the 50-byte /small is expected to cost 1 s and comes after it.
        yield 'size without history is converted using the observed seconds per byte' => [
            ['/known', '/small', '/big'],
            ['/known' => 2.0],
            ['/known' => 100, '/small' => 50, '/big' => 1000],
            ['/big', '/known', '/small'],
        ];

        yield 'a slow known file outranks a smaller file without history' => [
            ['/known', '/new'],
            ['/known' => 30.0],
            ['/known' => 100, '/new' => 50],
            ['/known', '/new'],
        ];

        yield 'timings of files not being analysed are ignored' => [
            ['/a', '/b'],
            ['/gone' => 99.0, '/a' => 1.0],
            ['/a' => 10, '/b' => 100],
            ['/b', '/a'],
        ];

        yield 'unknown files are compared by size when history has zero cost' => [
            ['/known', '/small', '/big'],
            ['/known' => 0.0],
            ['/known' => 10, '/small' => 1, '/big' => 5],
            ['/big', '/small', '/known'],
        ];

        yield 'nothing to analyse' => [[], [], [], []];
    }

    public function testKeepsPathsAsKeysAndValues(): void
    {
        $files = ['/a' => '/a', '/b' => '/b'];

        $ordered = AnalysisOrder::longestFirst($files, ['/b' => 3.0], self::sizes(['/a' => 1, '/b' => 10]));

        self::assertSame(['/b' => '/b', '/a' => '/a'], $ordered);
        self::assertSame(['/a' => '/a', '/b' => '/b'], $files);
    }

    public function testSizesAreNotNeededWhenEveryFileHasHistory(): void
    {
        $ordered = AnalysisOrder::longestFirst(
            self::files(['/a', '/b']),
            ['/a' => 1.0, '/b' => 2.0],
            static function (string $path): int {
                self::fail('size of ' . $path . ' was requested although every file has history');
            },
        );

        self::assertSame(['/b', '/a'], array_keys($ordered));
    }
}
