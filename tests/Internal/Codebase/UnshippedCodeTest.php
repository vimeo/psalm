<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Codebase;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psalm\Internal\Codebase\UnshippedCode;

use function array_filter;
use function array_values;

final class UnshippedCodeTest extends TestCase
{
    private const FILES = [
        'bench.php',
        'docs/x/D.php',
        'lib/Fixtures/deep/F.php',
        'lib/Keep.php',
        'lib/src/X.php',
        'src/A.php',
        'src/tests/S.php',
        'tests/a/T.php',
    ];

    /**
     * The expected files are those that git archive keeps with each .gitattributes.
     *
     * @param list<string> $shipped
     */
    #[DataProvider('providerGitattributes')]
    public function testExportIgnore(string $gitattributes, array $shipped): void
    {
        $code = UnshippedCode::fromContents('/root', [], $gitattributes);

        $this->assertSame(
            $shipped,
            array_values(array_filter(
                self::FILES,
                static fn(string $file): bool => !$code->contains('Foo', '/root/' . $file),
            )),
        );
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     *
     * @psalm-pure
     */
    public static function providerGitattributes(): iterable
    {
        yield 'anchored directory' => [
            "/tests/ export-ignore\n",
            ['bench.php', 'docs/x/D.php', 'lib/Fixtures/deep/F.php', 'lib/Keep.php', 'lib/src/X.php', 'src/A.php',
                'src/tests/S.php'],
        ];
        yield 'directory at any depth' => [
            "src/ export-ignore\n",
            ['bench.php', 'docs/x/D.php', 'lib/Fixtures/deep/F.php', 'lib/Keep.php', 'tests/a/T.php'],
        ];
        yield 'name at any depth' => [
            "src export-ignore\n",
            ['bench.php', 'docs/x/D.php', 'lib/Fixtures/deep/F.php', 'lib/Keep.php', 'tests/a/T.php'],
        ];
        yield 'everything inside, but one file' => [
            "/lib/** export-ignore\n/lib/Keep.php -export-ignore\n",
            ['bench.php', 'docs/x/D.php', 'lib/Keep.php', 'src/A.php', 'src/tests/S.php', 'tests/a/T.php'],
        ];
        yield 'a file in an ignored directory' => [
            "/docs export-ignore\n/docs/x/D.php -export-ignore\n",
            ['bench.php', 'lib/Fixtures/deep/F.php', 'lib/Keep.php', 'lib/src/X.php', 'src/A.php', 'src/tests/S.php',
                'tests/a/T.php'],
        ];
        yield 'directory pattern on a file' => [
            "bench.php/ export-ignore\n",
            self::FILES,
        ];
        yield 'value' => [
            "bench.php export-ignore=yes\n",
            self::FILES,
        ];
        yield 'unspecified' => [
            "bench.php export-ignore\nbench.php !export-ignore\n",
            self::FILES,
        ];
        yield 'extension' => [
            "*.php export-ignore\n",
            [],
        ];
        yield 'wildcard directory' => [
            "lib/*/deep export-ignore\n",
            ['bench.php', 'docs/x/D.php', 'lib/Keep.php', 'lib/src/X.php', 'src/A.php', 'src/tests/S.php',
                'tests/a/T.php'],
        ];
        yield 'any directories' => [
            "**/Fixtures export-ignore\n",
            ['bench.php', 'docs/x/D.php', 'lib/Keep.php', 'lib/src/X.php', 'src/A.php', 'src/tests/S.php',
                'tests/a/T.php'],
        ];
        yield 'character class' => [
            "b[a-e]nch.php export-ignore\n# comment export-ignore\n[attr]macro export-ignore\n",
            ['docs/x/D.php', 'lib/Fixtures/deep/F.php', 'lib/Keep.php', 'lib/src/X.php', 'src/A.php',
                'src/tests/S.php', 'tests/a/T.php'],
        ];
    }

    public function testAutoloadDev(): void
    {
        $code = UnshippedCode::fromContents(
            '/root',
            [
                'autoload' => ['psr-4' => ['Lib\\' => 'src/']],
                'autoload-dev' => [
                    'psr-4' => ['Lib\\Tests\\' => ['tests/', './benchmarks'], 'Fallback\\' => ''],
                    'psr-0' => ['Legacy_' => 'legacy-tests/'],
                    'classmap' => ['fixtures/', 'Stub.php'],
                    'files' => ['tests/functions.php'],
                ],
            ],
            '',
        );

        $this->assertTrue($code->contains('Lib\\Tests\\FooTest', '/root/tests/FooTest.php'));
        $this->assertTrue($code->contains('lib\\tests\\Bench', '/root/benchmarks/Bench.php'));
        $this->assertTrue($code->contains('Fallback\\Foo', '/root/src/Foo.php'));
        $this->assertTrue($code->contains('Legacy_FooTest', '/root/legacy-tests/Legacy/FooTest.php'));
        $this->assertTrue($code->contains('Anything', '/root/fixtures/deep/Fixture.php'));
        $this->assertTrue($code->contains('Anything', '/root/Stub.php'));
        $this->assertTrue($code->contains('Anything', '/root/tests/functions.php'));
        $this->assertTrue($code->contains('Lib\\Tests\\FooTest', '\\root\\tests\\FooTest.php'));

        $this->assertFalse($code->contains('Lib\\Foo', '/root/src/Foo.php'));
        // composer only loads the classes of the namespace from the directory
        $this->assertFalse($code->contains('Lib\\Foo', '/root/tests/Foo.php'));
        $this->assertFalse($code->contains('Lib\\Tests\\FooTest', '/root/tests-other/FooTest.php'));
        $this->assertFalse($code->contains('Anything', '/root/Stub.php.dist'));
        $this->assertFalse($code->contains('Lib\\Tests\\FooTest', '/elsewhere/tests/FooTest.php'));
    }
}
