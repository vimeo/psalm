<?php
namespace Psalm\Tests\Functions;

use function getMemoryLimitInBytes;
use function ini_set;
use function ini_get;

class GetMemoryLimitInBytesTest extends \Psalm\Tests\TestCase
{
    /**
     * @var string
     */
    private $previousLimit;

    public function setUp(): void
    {
        require_once 'src/command_functions.php';
        $this->previousLimit = (string)ini_get('memory_limit');
        parent::setUp();
    }

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
        $this->assertSame($expectedBytes, \Psalm\getMemoryLimitInBytes(), 'Memory limit in bytes does not fit setting');
    }

    public function tearDown(): void
    {
        ini_set('memory_limit', $this->previousLimit);
        parent::tearDown();
    }
}
