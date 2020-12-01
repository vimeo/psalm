<?php

namespace Psalm\Tests\EndToEnd;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class Bug4705Test extends TestCase
{
    use PsalmRunnerTrait;

    public function testNoLeakedTemplate(): void
    {
        $project_root = __DIR__ . '/' . '../fixtures/doctrine-stubs-project/';

        $process = new Process(['composer', 'install'], $project_root);
        $process->mustRun();

        $output = $this->runPsalm(
            ['--no-cache'],
            $project_root,
            true,
            false
        );

        $this->assertStringContainsString('I|null', $output['STDOUT']);
        $this->assertStringNotContainsString(
            'I&(T:fn-doctrine\orm\entitymanagerinterface::getreference as object)',
            $output['STDOUT']
        );
    }
}
