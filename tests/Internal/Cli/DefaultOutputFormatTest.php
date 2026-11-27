<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Cli;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psalm\Internal\Cli\Psalm;
use Psalm\Report;
use Psalm\Tests\EndToEnd\PsalmRunnerTrait;

use function array_keys;
use function getenv;
use function putenv;

final class DefaultOutputFormatTest extends TestCase
{
    use PsalmRunnerTrait;

    /**
     * @param array<string, string> $env
     * @param Report::TYPE_* $expected
     */
    #[DataProvider('provideEnvironments')]
    public function testDefaultOutputFormat(array $env, string $expected): void
    {
        $names = [...array_keys(self::agentEnvVarsToUnset()), 'TERMINAL_EMULATOR', 'GITHUB_ACTIONS'];

        $saved = [];
        foreach ($names as $name) {
            $saved[$name] = getenv($name);
            putenv($name);
        }

        try {
            foreach ($env as $name => $value) {
                putenv($name . '=' . $value);
            }

            $this->assertSame($expected, Psalm::findDefaultOutputFormat());
        } finally {
            foreach ($saved as $name => $value) {
                putenv($value === false ? $name : $name . '=' . $value);
            }
        }
    }

    /**
     * @return iterable<string, array{array<string, string>, Report::TYPE_*}>
     * @psalm-pure
     */
    public static function provideEnvironments(): iterable
    {
        yield 'nothing' => [[], Report::TYPE_CONSOLE];
        yield 'PhpStorm' => [['TERMINAL_EMULATOR' => 'JetBrains-JediTerm'], Report::TYPE_PHP_STORM];
        yield 'GitHub Actions' => [['GITHUB_ACTIONS' => 'true'], Report::TYPE_GITHUB_ACTIONS];
        yield 'agent' => [['CLAUDECODE' => '1'], Report::TYPE_COMPACT];
        yield 'agent in PhpStorm' => [
            ['CLAUDECODE' => '1', 'TERMINAL_EMULATOR' => 'JetBrains-JediTerm'],
            Report::TYPE_COMPACT,
        ];
        yield 'agent in GitHub Actions' => [
            ['CLAUDECODE' => '1', 'GITHUB_ACTIONS' => 'true'],
            Report::TYPE_COMPACT,
        ];
    }
}
