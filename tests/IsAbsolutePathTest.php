<?php
namespace Psalm\Tests;

class IsAbsolutePathTest extends TestCase
{
    /**
     * @param string $path
     * @param bool $expected
     *
     * @return void
     *
     * @dataProvider providerForTestIsAbsolutePath
     */
    public function testIsAbsolutePath($path, $expected)
    {
        require_once __DIR__.'/../src/command_functions.php';

        self::assertSame($expected, isAbsolutePath($path));
    }

    /**
     * @return array<int, array{0:string, 1:bool}>
     */
    public function providerForTestIsAbsolutePath()
    {
        return [
            ['/path/to/something', true],
            ['/path/to/something/file.php', true],
            ['relative/path/to/something', false],
            ['relative/path/to/something/file.php', false],
            ['c:/path/to/something', true],
            ['file://c:/path/to/something', true],
            ['zlib://c:/path/to/something', true],
        ];
    }
}
