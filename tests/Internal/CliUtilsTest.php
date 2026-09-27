<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal;

use Fiber;
use Override;
use PHPUnit\Framework\TestCase;
use PhpParser\ParserFactory;
use Psalm\Internal\CliUtils;
use Psalm\Internal\Fork\PhpSerializer;

use function function_exists;
use function ini_get;
use function ini_set;
use function realpath;
use function str_repeat;

use const DIRECTORY_SEPARATOR;

final class CliUtilsTest extends TestCase
{
    /**
     * @var list<string>
     */
    private array $argv = [];

    #[Override]
    protected function setUp(): void
    {
        global $argv;
        $this->argv = $argv;
    }

    #[Override]
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

    /** @return iterable<string,list{0: list<string>|null, 1: list<string>, 2?: list<string>}> */
    public function provideGetPathsToCheck(): iterable
    {
        $psalm = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'psalm';
        $dummyProjectDir = (string)realpath(
            __DIR__
            . DIRECTORY_SEPARATOR . '..'
            . DIRECTORY_SEPARATOR . 'fixtures'
            . DIRECTORY_SEPARATOR . 'DummyProject',
        );
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
            [
                $dummyProjectDir . DIRECTORY_SEPARATOR . 'Bar.php',
                $dummyProjectDir . DIRECTORY_SEPARATOR . 'Bat.php',
            ],
            [
                $psalm,
                $dummyProjectDir . DIRECTORY_SEPARATOR . 'Bar.php',
                $dummyProjectDir . DIRECTORY_SEPARATOR . 'Bat.php',
            ],
        ];

        yield 'withPathsAndArgumentsMixed' => [
            [
                $dummyProjectDir . DIRECTORY_SEPARATOR . 'Bar.php',
                $dummyProjectDir . DIRECTORY_SEPARATOR . 'Bat.php',
            ],
            [
                $psalm,
                '--plugin=vendor/vimeo/psalm/examples/plugins/ClassUnqualifier.php',
                $dummyProjectDir . DIRECTORY_SEPARATOR . 'Bar.php',
                $dummyProjectDir . DIRECTORY_SEPARATOR . 'Bat.php',
            ],
        ];

        yield 'withFpathToCurrentDir' => [
            [$currentDir],
            [$psalm, '-f', '.'],
            ['.'],
        ];

        yield 'withFpathToProjectDir' => [
            [$dummyProjectDir],
            [$psalm, '-f', $dummyProjectDir],
            [$dummyProjectDir],
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

    public function testEnsureFiberStackSizeKeepsALargerConfiguredValue(): void
    {
        $previous = (string) ini_get('fiber.stack_size');

        try {
            ini_set('fiber.stack_size', '64M');
            CliUtils::ensureFiberStackSize();
            self::assertSame('64M', ini_get('fiber.stack_size'));

            if (function_exists('ini_parse_quantity')) {
                ini_set('fiber.stack_size', '0x4000000');
                CliUtils::ensureFiberStackSize();
                self::assertSame('0x4000000', ini_get('fiber.stack_size'));
            }

            ini_set('fiber.stack_size', '1M');
            CliUtils::ensureFiberStackSize();
            self::assertSame((string) CliUtils::MINIMUM_FIBER_STACK_SIZE, ini_get('fiber.stack_size'));
        } finally {
            ini_set('fiber.stack_size', $previous);
        }
    }

    /**
     * Deeply nested ASTs are serialized into the parser cache from inside an event loop fiber,
     * which aborts the process once the fiber stack is exhausted, and are read back from there.
     *
     * @see https://github.com/vimeo/psalm/issues/11967
     */
    public function testDeeplyNestedAstSurvivesACacheRoundTripInsideAFiber(): void
    {
        $previous = (string) ini_get('fiber.stack_size');
        $previous_max_depth = (string) ini_get('unserialize_max_depth');

        try {
            CliUtils::ensureFiberStackSize();
            ini_set('unserialize_max_depth', '4096');

            // deep enough to exceed both the default fiber stack and the default unserialize_max_depth
            $code = '<?php return ' . str_repeat('array(', 1500) . '1' . str_repeat(')', 1500) . ';';
            $stmts = (new ParserFactory())->createForHostVersion()->parse($code);
            self::assertNotNull($stmts);

            $serializer = new PhpSerializer();
            $fiber = new Fiber(static fn(): mixed => $serializer->unserialize($serializer->serialize($stmts)));
            $fiber->start();

            $result = $fiber->getReturn();
            self::assertIsArray($result);
            self::assertCount(1, $result);
        } finally {
            ini_set('fiber.stack_size', $previous);
            ini_set('unserialize_max_depth', $previous_max_depth);
        }
    }
}
