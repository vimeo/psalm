<?php

namespace Psalm\Tests\Internal;

use PHPUnit\Framework\TestCase;
use Psalm\Internal\CliUtils;

use function realpath;

class CliUtilsTest extends TestCase
{
    private $argv = [];

    protected function setUp(): void
    {
        global $argv;
        $this->argv = $argv;
    }

    protected function tearDown(): void
    {
        global $argv;
        $argv = $this->argv;
    }

    /**
     * @return iterable<array<list<string>>>
     */
    public function provideGetArguments(): iterable
    {
        $psalter = __DIR__ . '/../../psalter';
        yield 'standardCallWithPsalter' => [
            ['--plugin=vendor/vimeo/psalm/examples/plugins/ClassUnqualifier.php', '--dry-run'],
            [$psalter, '--plugin=vendor/vimeo/psalm/examples/plugins/ClassUnqualifier.php', '--dry-run'],
        ];

        yield 'specialCaseWithBinary' => [
            ['--plugin=vendor/vimeo/psalm/examples/plugins/ClassUnqualifier.php', '--dry-run'],
            ['/bin/true', '--plugin=vendor/vimeo/psalm/examples/plugins/ClassUnqualifier.php', '--dry-run'],
        ];

        yield 'specialCaseWhichWouldFail' => [
            ['--plugin=vendor/vimeo/psalm/examples/plugins/ClassUnqualifier.php', '--dry-run'],
            ['/directory-does-not-exist/file-does-not-exist', '--plugin=vendor/vimeo/psalm/examples/plugins/ClassUnqualifier.php', '--dry-run'],
        ];
    }

    /**
     * @dataProvider provideGetArguments
     * @param list<string> $expected
     * @param list<string> $input
     */
    public function testGetArgumentsWillReturnExpectedValue(array $expected, array $input): void
    {
        global $argv;
        $argv = $input;
        $result = CliUtils::getArguments();
        self::assertEquals($expected, $result);
    }

    public function provideGetPathsToCheck(): iterable
    {
        $psalm = __DIR__ . '/../../psalm';
        $dummyProjectDir = realpath(__DIR__ . '/../fixtures/DummyProject');
        yield 'withoutPaths' => [
            null,
            [$psalm, '--plugin=vendor/vimeo/psalm/examples/plugins/ClassUnqualifier.php', '--dry-run'],
        ];

        yield 'withoutPathsAndArguments' => [
            null,
            [$psalm],
        ];

        yield 'withPaths' => [
            [$dummyProjectDir . '/Bar.php', $dummyProjectDir . '/Bat.php'],
            [$psalm, $dummyProjectDir . '/Bar.php', $dummyProjectDir . '/Bat.php'],
        ];

        yield 'withPathsAndArgumentsMixed' => [
            [$dummyProjectDir . '/Bar.php', $dummyProjectDir . '/Bat.php'],
            [$psalm, '--plugin=vendor/vimeo/psalm/examples/plugins/ClassUnqualifier.php', $dummyProjectDir . '/Bar.php', $dummyProjectDir . '/Bat.php'],
        ];

        yield 'withFpathToCurrentDir' => [
            [realpath('.')],
            [$psalm, '-f', '.'],
            ['.']
        ];

        yield 'withFpathToProjectDir' => [
            [$dummyProjectDir],
            [$psalm, '-f', $dummyProjectDir],
            [$dummyProjectDir]
        ];
    }

    /**
     * @dataProvider provideGetPathsToCheck
     * @param list<string>|null $expected
     * @param list<string> $input
     */
    public function testGetPathsToCheckWillReturnExpectedValue(?array $expected, array $input, array $fpaths = []): void
    {
        global $argv;
        $argv = $input;
        $result = CliUtils::getPathsToCheck($fpaths);
        self::assertEquals($expected, $result);
    }
}
