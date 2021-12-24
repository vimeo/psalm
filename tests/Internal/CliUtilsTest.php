<?php

namespace Psalm\Tests\Internal;

use PHPUnit\Framework\TestCase;
use Psalm\Internal\CliUtils;

use function realpath;

class CliUtilsTest extends TestCase
{
    /**
     * @var array<int, string>
     */
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

    /** @return iterable<string,array{list<string>,list<string>}> */
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
     * @param list<string> $_input
     */
    public function testGetArgumentsWillReturnExpectedValue(array $expected, array $_input): void
    {
        global $argv;
        $argv = $_input;
        $result = CliUtils::getArguments();
        self::assertEquals($expected, $result);
    }

    /** @return iterable<string,array{list<string>|null,list<string>,fpaths?:list<string>}> */
    public function provideGetPathsToCheck(): iterable
    {
        $psalm = __DIR__ . '/../../psalm';
        $dummyProjectDir = (string)realpath(__DIR__ . '/../fixtures/DummyProject');
        $currentDir = (string)realpath('.');

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
            [$currentDir],
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
     * @param list<string> $_input
     */
    public function testGetPathsToCheckWillReturnExpectedValue(?array $expected, array $_input, array $fpaths = []): void
    {
        global $argv;
        $argv = $_input;
        $result = CliUtils::getPathsToCheck($fpaths);
        self::assertEquals($expected, $result);
    }
}
