<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Codebase;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psalm\Internal\Codebase\Scanner;

/**
 * Forking pays for the files without a cached storage: more than 512 files, more than 256 of them without
 * one, and more of those (times 8) than of the files with one.
 */
final class ScannerPoolSizeTest extends TestCase
{
    /**
     * Max pool size, files, files without a cached storage, pool
     *
     * @return iterable<string, array{positive-int, int<0, max>, int<0, max>, positive-int}>
     * @psalm-pure
     */
    public static function provideScanPoolSizes(): iterable
    {
        yield 'no files' => [8, 0, 0, 1];

        // files
        yield '512 files without a storage' => [8, 512, 512, 1];
        yield '513 files without a storage' => [8, 513, 513, 3];
        yield '513 files, 257 without a storage' => [8, 513, 257, 2];

        // files without a cached storage
        yield '256 of 513 files without a storage' => [8, 513, 256, 1];
        yield '256 of 2000 files without a storage' => [8, 2000, 256, 1];
        yield '257 of 2000 files without a storage' => [8, 2000, 257, 2];
        yield '512 of 2000 files without a storage' => [8, 2000, 512, 2];
        yield '513 of 2000 files without a storage' => [8, 2000, 513, 3];

        // times 8 as many files without a cached storage as with one
        yield '300 without a storage, 2399 with one' => [8, 2699, 300, 2];
        yield '300 without a storage, 2400 with one' => [8, 2700, 300, 1];
        yield '300 without a storage, 2401 with one' => [8, 2701, 300, 1];
        yield 'all with a storage' => [8, 5000, 0, 1];

        // one process per 256 files without a cached storage, up to the pool size
        yield '1024 without a storage' => [8, 1024, 1024, 4];
        yield '1025 without a storage' => [8, 1025, 1025, 5];
        yield '1000 without a storage, pool of 3' => [3, 1000, 1000, 3];
        yield '5000 without a storage, pool of 2' => [2, 5000, 5000, 2];
        yield '1000 without a storage, pool of 1' => [1, 1000, 1000, 1];
    }

    /**
     * @param positive-int $max_pool_size
     * @param int<0, max> $file_count
     * @param int<0, max> $uncached_count
     * @param positive-int $expected
     */
    #[DataProvider('provideScanPoolSizes')]
    public function testGetScanPoolSize(int $max_pool_size, int $file_count, int $uncached_count, int $expected): void
    {
        $this->assertSame($expected, Scanner::getScanPoolSize($max_pool_size, $file_count, $uncached_count));
    }
}
