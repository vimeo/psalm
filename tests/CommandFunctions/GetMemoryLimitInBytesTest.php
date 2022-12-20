<?php

namespace Psalm\Tests\CommandFunctions;

use Psalm\Internal\CliUtils;
use Psalm\Tests\TestCase;

class GetMemoryLimitInBytesTest extends TestCase
{
    /**
     * @return array<int,array<string|int>>
     */
    public function memoryLimitSettingProvider(): array
    {
        return [
            // unlimited
            [-1, -1],
            // byte values
            [1, 1],
            [512, 512],
            [2_048, 2_048],
            // uppercase units
            ['1K', 1_024],
            ['24K', 24_576],
            ['1M', 1_048_576],
            ['24M', 25_165_824],
            ['1G', 1_073_741_824],
            ['24G', 25_769_803_776],
            // lowercase units
            ['1k', 1_024],
            ['24k', 24_576],
            ['1m', 1_048_576],
            ['24m', 25_165_824],
            ['1g', 1_073_741_824],
            ['24g', 25_769_803_776],
        ];
    }

    /**
     * @dataProvider memoryLimitSettingProvider
     * @param int|string $setting
     * @param int|string $expectedBytes
     */
    public function testGetMemoryLimitInBytes(
        $setting,
        $expectedBytes
    ): void {
        $this->assertSame(
            $expectedBytes,
            CliUtils::convertMemoryLimitToBytes((string)$setting),
            'Memory limit in bytes does not fit setting',
        );
    }
}
