<?php

declare(strict_types=1);

namespace Psalm\Tests\EndToEnd;

use PHPUnit\Framework\TestCase;

final class GlobalOfAConfiguredClassTest extends TestCase
{
    use PsalmRunnerTrait;

    public function testTheClassesOfTheConfiguredGlobalsAreScanned(): void
    {
        $output = $this->runPsalm(
            ['--no-cache'],
            __DIR__ . '/../fixtures/GlobalOfAConfiguredClass',
            true,
        )['STDOUT'];

        $this->assertStringContainsString('InvalidScalarArgument', $output);
        $this->assertStringContainsString('Connection::query', $output);
        $this->assertStringContainsString('Pool::open', $output);
    }
}
