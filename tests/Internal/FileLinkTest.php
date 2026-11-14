<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psalm\Internal\FileLink;

final class FileLinkTest extends TestCase
{
    /**
     * @return iterable<string,array{string,string,int,string}>
     * @psalm-pure
     */
    public static function providerBuild(): iterable
    {
        yield 'posix path' => ['', '/project/src/Foo.php', 12, 'file:///project/src/Foo.php#L12'];
        yield 'spaces and special characters' => [
            '',
            '/my project/a#b?c/Foo.php',
            3,
            'file:///my%20project/a%23b%3Fc/Foo.php#L3',
        ];
        yield 'non-ASCII' => ['', '/проект/Foo.php', 1, 'file:///%D0%BF%D1%80%D0%BE%D0%B5%D0%BA%D1%82/Foo.php#L1'];
        yield 'windows drive path' => ['', 'C:\\x\\Foo.php', 7, 'file:///C:/x/Foo.php#L7'];
        yield 'windows drive path with spaces' => [
            '',
            'D:\\My Project\\Foo.php',
            7,
            'file:///D:/My%20Project/Foo.php#L7',
        ];
        yield 'windows drive path with forward slashes' => ['', 'C:/x/Foo.php', 7, 'file:///C:/x/Foo.php#L7'];
        yield 'custom format gets the raw path' => [
            'phpstorm://open?file=%f&line=%l',
            '/my project/Foo.php',
            5,
            'phpstorm://open?file=/my project/Foo.php&line=5',
        ];
        yield 'custom format with a Windows path' => [
            'vscode://file/%f:%l',
            'C:\\x\\Foo.php',
            5,
            'vscode://file/C:\\x\\Foo.php:5',
        ];
    }

    #[DataProvider('providerBuild')]
    public function testBuild(string $format, string $file_path, int $line, string $expected): void
    {
        $this->assertSame($expected, FileLink::build($format, $file_path, $line));
    }

    public function testHyperlinkWrapsTextInOsc8(): void
    {
        $link = FileLink::create('/a b/Foo.php', 2);

        $this->assertSame(
            "\e]8;;" . $link . "\e\\text\e]8;;\e\\",
            FileLink::hyperlink('text', '/a b/Foo.php', 2),
        );
    }
}
