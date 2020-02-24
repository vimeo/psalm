<?php
namespace Psalm\Tests\Functions;

use function Psalm\getMemoryLimitInBytes;
use function ini_set;

class GetMemoryLimitInBytesTest extends \Psalm\Tests\TestCase
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
            [2048, 2048],
            // uppercase units
            ['1K', 1024],
            ['24K', 24576],
            ['1M', 1048576],
            ['24M', 25165824],
            ['1G', 1073741824],
            ['24G', 25769803776],
            // lowercase units
            ['1k', 1024],
            ['24k', 24576],
            ['1m', 1048576],
            ['24m', 25165824],
            ['1g', 1073741824],
            ['24g', 25769803776],
        ];
    }

    /**
     * @dataProvider memoryLimitSettingProvider
     *
     * @param int|string $setting
     * @param int|string $expectedBytes
     *
     * @return void
     */
    public function testGetMemoryLimitInBytes(
        $setting,
        $expectedBytes
    ) {
        ini_set('memory_limit', (string)$setting);
        $this->assertSame($expectedBytes, getMemoryLimitInBytes(), 'Memory limit in bytes does not fit setting');
    }
}
