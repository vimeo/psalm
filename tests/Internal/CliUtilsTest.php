<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal;

use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psalm\Internal\CliUtils;
use Psalm\Tests\EndToEnd\PsalmRunnerTrait;

use function array_keys;
use function getenv;
use function putenv;
use function realpath;
use function stream_isatty;

use const DIRECTORY_SEPARATOR;
use const STDOUT;

final class CliUtilsTest extends TestCase
{
    use PsalmRunnerTrait;

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

    /**
     * @return iterable<string,array{list<string>,list<string>}>
     * @psalm-pure
     */
    public static function provideGetArguments(): iterable
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
     * @param list<string> $expected
     * @param list<string> $_input
     */
    #[DataProvider('provideGetArguments')]
    public function testGetArgumentsWillReturnExpectedValue(array $expected, array $_input): void
    {
        global $argv;
        $argv = $_input;
        $result = CliUtils::getArguments();
        self::assertEquals($expected, $result);
    }

    /** @return iterable<string,list{0: list<string>|null, 1: list<string>, 2?: list<string>}> */
    public static function provideGetPathsToCheck(): iterable
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

        yield 'withTheValueOfAnOptionAfterASpace' => [
            null,
            [$psalm, '--output-format', 'compact', '--threads', '4'],
        ];

        yield 'withTheValuesOfRefactorOptionsAfterASpace' => [
            null,
            [$psalm, '--move', 'Ns\A::foo', '--into', 'Ns\B', '--rename', 'Ns\A::bar', '--to', 'Ns\A::baz'],
        ];

        yield 'withTheValuesOfRefactorOptionsAfterAnEqualsSign' => [
            null,
            [$psalm, '--move=Ns\A::foo', '--into=Ns\B'],
        ];

        yield 'withTheValueOfAnOptionAfterASpaceAndPaths' => [
            [$dummyProjectDir . DIRECTORY_SEPARATOR . 'Bar.php'],
            [$psalm, '--memory-limit', '2G', $dummyProjectDir . DIRECTORY_SEPARATOR . 'Bar.php', '--report', 'x.json'],
        ];

        yield 'withPathAfterAnOptionWithAnOptionalValue' => [
            [$dummyProjectDir . DIRECTORY_SEPARATOR . 'Bar.php'],
            [$psalm, '--show-snippet=false', $dummyProjectDir . DIRECTORY_SEPARATOR . 'Bar.php'],
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
     * @param list<string>|null $expected
     * @param list<string> $_input
     */
    #[DataProvider('provideGetPathsToCheck')]
    public function testGetPathsToCheckWillReturnExpectedValue(?array $expected, array $_input, array $fpaths = []): void
    {
        global $argv;
        $argv = $_input;
        $result = CliUtils::getPathsToCheck($fpaths);
        self::assertEquals($expected, $result);
    }

    public function testUseColorWithoutAnyEnvironmentHintFollowsTheTtyRule(): void
    {
        $this->withEnv([], static function (): void {
            self::assertTrue(CliUtils::useColor([], false));
            self::assertSame(stream_isatty(STDOUT), CliUtils::useColor([], true));
        });
    }

    public function testMonochromeFlagBeatsForceColor(): void
    {
        $this->withEnv(['FORCE_COLOR' => '1'], static function (): void {
            self::assertFalse(CliUtils::useColor(['m' => false], false));
            self::assertFalse(CliUtils::useColor(['m' => false], true));
        });
    }

    public function testForceColorBeatsEveryAutomaticOptOut(): void
    {
        $this->withEnv(
            ['FORCE_COLOR' => '1', 'NO_COLOR' => '1', 'AI_AGENT' => '1', 'TERM' => 'dumb'],
            static function (): void {
                self::assertTrue(CliUtils::useColor([], false));
                self::assertTrue(CliUtils::useColor([], true));
            },
        );
    }

    public function testEmptyForceColorIsIgnored(): void
    {
        $this->withEnv(['FORCE_COLOR' => '', 'NO_COLOR' => '1'], static function (): void {
            self::assertFalse(CliUtils::useColor([], false));
        });
    }

    public function testNoColorDisablesColor(): void
    {
        $this->withEnv(['NO_COLOR' => '1'], static function (): void {
            self::assertFalse(CliUtils::useColor([], false));
        });
        $this->withEnv(['NO_COLOR' => ''], static function (): void {
            self::assertTrue(CliUtils::useColor([], false));
        });
    }

    public function testAiAgentDisablesColor(): void
    {
        $this->withEnv(['AI_AGENT' => '1'], static function (): void {
            self::assertFalse(CliUtils::useColor([], false));
        });
    }

    public function testDumbTerminalDisablesColor(): void
    {
        $this->withEnv(['TERM' => 'dumb'], static function (): void {
            self::assertFalse(CliUtils::useColor([], false));
            self::assertFalse(CliUtils::useColor([], true));
        });
    }

    public function testWithEnvRestoresTheInheritedEnvironment(): void
    {
        $original = getenv('FORCE_COLOR');
        putenv('FORCE_COLOR=inherited-value');

        try {
            $this->withEnv(['FORCE_COLOR' => '1'], static function (): void {
                self::assertSame('1', getenv('FORCE_COLOR'));
            });

            self::assertSame('inherited-value', getenv('FORCE_COLOR'));
        } finally {
            putenv($original === false ? 'FORCE_COLOR' : 'FORCE_COLOR=' . $original);
        }
    }

    /**
     * Runs $test with a clean color/agent environment plus $env, then restores the previous one.
     *
     * @param array<string, string> $env
     * @param callable(): void $test
     */
    private function withEnv(array $env, callable $test): void
    {
        $names = array_keys(self::agentEnvVarsToUnset());
        $names[] = 'TERM';

        $saved = [];
        foreach ($names as $name) {
            $saved[$name] = getenv($name);
            putenv($name);
        }

        try {
            foreach ($env as $name => $value) {
                putenv($name . '=' . $value);
            }

            $test();
        } finally {
            foreach ($saved as $name => $value) {
                putenv($value === false ? $name : $name . '=' . $value);
            }
        }
    }
}
