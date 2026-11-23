<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Fork;

use Override;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

use function dirname;
use function extension_loaded;
use function trim;
use function var_export;

use const PHP_BINARY;

final class PsalmRestarterTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        if (!extension_loaded('Zend OPcache')) {
            $this->markTestSkipped('opcache is required to exercise the opcache settings check');
        }
    }

    /** @return iterable<string,array{string,bool}> */
    public function provideJitValues(): iterable
    {
        yield 'bare off' => ['off', false];
        yield 'quoted off' => ['"off"', false];
        yield 'quoted uppercase OFF' => ['"OFF"', false];
        yield 'disable' => ['disable', false];
        yield '0' => ['0', false];
        yield 'quoted no' => ['"no"', false];
        yield 'quoted false' => ['"false"', false];
        yield 'tracing' => ['tracing', true];
        yield 'function' => ['function', true];
    }

    /**
     * @dataProvider provideJitValues
     */
    public function testRequiresRestartOnlyWhenJitIsOn(string $jit, bool $expectedRestart): void
    {
        $autoload = var_export(dirname(__DIR__, 3) . '/vendor/autoload.php', true);
        $code = "require $autoload;"
            . ' echo (fn(): bool => $this->requiresRestart(false))->call(new \Psalm\Internal\Fork\PsalmRestarter(\'PSALM\'))'
            . " ? 'restart' : 'no restart';";

        $process = new Process([
            PHP_BINARY,
            '-dopcache.enable=1',
            '-dopcache.enable_cli=1',
            '-dopcache.validate_timestamps=0',
            '-dopcache.file_update_protection=0',
            '-dopcache.max_accelerated_files=1000000',
            '-dopcache.interned_strings_buffer=64',
            '-dopcache.optimization_level=0x7FFEBFFF',
            '-dopcache.preload=',
            '-dopcache.log_verbosity_level=0',
            '-dopcache.save_comments=1',
            '-dopcache.restrict_api=',
            '-dopcache.memory_consumption=512',
            '-dopcache.jit=' . $jit,
            '-r',
            $code,
        ]);
        $process->mustRun();

        $this->assertSame($expectedRestart ? 'restart' : 'no restart', trim($process->getOutput()));
    }
}
