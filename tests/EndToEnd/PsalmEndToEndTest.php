<?php

namespace Psalm\Tests\EndToEnd;

use Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

use function closedir;
use function copy;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function getcwd;
use function is_dir;
use function is_string;
use function mkdir;
use function opendir;
use function preg_replace;
use function readdir;
use function rmdir;
use function str_replace;
use function substr_count;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

/**
 * Tests some of the most important use cases of the psalm and psalter commands, by launching a new
 * process as if invoked by a real user.
 *
 * This is primarily intended to test the code in `psalm`, `src/psalm.php` and related files.
 */
class PsalmEndToEndTest extends TestCase
{
    use PsalmRunnerTrait;

    private static string $tmpDir;

    public static function setUpBeforeClass(): void
    {
        self::$tmpDir = tempnam(sys_get_temp_dir(), 'PsalmEndToEndTest_');
        unlink(self::$tmpDir);
        mkdir(self::$tmpDir);

        $getcwd = getcwd();
        if (!is_string($getcwd)) {
            throw new Exception('Couldn\'t get working directory');
        }

        mkdir(self::$tmpDir . '/src');

        copy(__DIR__ . '/../fixtures/DummyProjectWithErrors/composer.json', self::$tmpDir . '/composer.json');

        $process = new Process(['composer', 'install', '--no-plugins'], self::$tmpDir, null, null, 120);
        $process->mustRun();
    }

    public static function tearDownAfterClass(): void
    {
        self::recursiveRemoveDirectory(self::$tmpDir);
        parent::tearDownAfterClass();
    }

    public function setUp(): void
    {
        @unlink(self::$tmpDir . '/psalm.xml');
        copy(
            __DIR__ . '/../fixtures/DummyProjectWithErrors/src/FileWithErrors.php',
            self::$tmpDir . '/src/FileWithErrors.php'
        );
        parent::setUp();
    }

    public function tearDown(): void
    {
        if (file_exists(self::$tmpDir . '/cache')) {
            self::recursiveRemoveDirectory(self::$tmpDir . '/cache');
        }
        parent::tearDown();
    }

    public function testHelpReturnsMessage(): void
    {
        $this->assertStringContainsString('Usage:', $this->runPsalm(['--help'], self::$tmpDir)['STDOUT']);
    }

    public function testInit(): void
    {
        $this->assertStringStartsWith(
            'Calculating best config level based on project files',
            $this->runPsalmInit()['STDOUT']
        );
        $this->assertFileExists(self::$tmpDir . '/psalm.xml');
    }

    public function testAlter(): void
    {
        $this->runPsalmInit();

        $this->assertStringContainsString(
            'No errors found!',
            $this->runPsalm(['--alter', '--issues=all'], self::$tmpDir, false, true)['STDOUT']
        );

        $this->assertSame(0, $this->runPsalm([], self::$tmpDir)['CODE']);
    }

    public function testPsalter(): void
    {
        $this->runPsalmInit();
        (new Process(['php', $this->psalter, '--alter', '--issues=InvalidReturnType'], self::$tmpDir))->mustRun();
        $this->assertSame(0, $this->runPsalm([], self::$tmpDir)['CODE']);
    }

    public function testPsalm(): void
    {
        $this->runPsalmInit(1);
        $result = $this->runPsalm([], self::$tmpDir, true);
        $this->assertStringContainsString(
            'Target PHP version: 7.1 (inferred from composer.json)',
            $result['STDERR']
        );
        $this->assertStringContainsString('UnusedParam', $result['STDOUT']);
        $this->assertStringContainsString('InvalidReturnType', $result['STDOUT']);
        $this->assertStringContainsString('InvalidReturnStatement', $result['STDOUT']);
        $this->assertStringContainsString('3 errors', $result['STDOUT']);
        $this->assertSame(2, $result['CODE']);
    }

    public function testPsalmWithPHPVersionOverride(): void
    {
        $this->runPsalmInit(1);
        $result = $this->runPsalm(['--php-version=8.0'], self::$tmpDir, true);
        $this->assertStringContainsString(
            'Target PHP version: 8.0 (set by CLI argument)',
            $result['STDERR']
        );
    }

    public function testPsalmWithPHPVersionFromConfig(): void
    {
        $this->runPsalmInit(1, '7.4');
        $result = $this->runPsalm([], self::$tmpDir, true);
        $this->assertStringContainsString(
            'Target PHP version: 7.4 (set by config file)',
            $result['STDERR']
        );
    }

    public function testPsalmDiff(): void
    {
        copy(__DIR__ . '/../fixtures/DummyProjectWithErrors/diff_composer.lock', self::$tmpDir . '/composer.lock');

        $this->runPsalmInit(1);
        $result = $this->runPsalm(['--diff', '-m'], self::$tmpDir, true);
        $this->assertStringContainsString('UnusedParam', $result['STDOUT']);
        $this->assertStringContainsString('InvalidReturnType', $result['STDOUT']);
        $this->assertStringContainsString('InvalidReturnStatement', $result['STDOUT']);
        $this->assertStringContainsString('3 errors', $result['STDOUT']);
        $this->assertStringContainsString('E', $result['STDERR']);

        $this->assertSame(2, $result['CODE']);

        $result = $this->runPsalm(['--diff', '-m'], self::$tmpDir, true);

        $this->assertStringContainsString('UnusedParam', $result['STDOUT']);
        $this->assertStringContainsString('InvalidReturnType', $result['STDOUT']);
        $this->assertStringContainsString('InvalidReturnStatement', $result['STDOUT']);
        $this->assertStringContainsString('3 errors', $result['STDOUT']);
        $this->assertEquals(1, substr_count($result['STDERR'], 'E')); // Should only have 'E' from 'Extensions' in version message

        $this->assertSame(2, $result['CODE']);

        @unlink(self::$tmpDir . '/composer.lock');
    }

    public function testTainting(): void
    {
        $this->runPsalmInit(1);
        $result = $this->runPsalm(['--taint-analysis'], self::$tmpDir, true);

        $this->assertStringContainsString('TaintedHtml', $result['STDOUT']);
        $this->assertStringContainsString('TaintedTextWithQuotes', $result['STDOUT']);
        $this->assertStringContainsString('2 errors', $result['STDOUT']);
        $this->assertSame(2, $result['CODE']);
    }

    public function testTaintingWithoutInit(): void
    {
        $result = $this->runPsalm(['--taint-analysis'], self::$tmpDir, true, false);

        $this->assertStringContainsString('TaintedHtml', $result['STDOUT']);
        $this->assertStringContainsString('TaintedTextWithQuotes', $result['STDOUT']);
        $this->assertStringContainsString('2 errors', $result['STDOUT']);
        $this->assertSame(2, $result['CODE']);
    }

    public function testTaintGraphDumping(): void
    {
        $this->runPsalmInit(1);
        $result = $this->runPsalm(
            [
                '--taint-analysis',
                '--dump-taint-graph='.self::$tmpDir.'/taints.dot',
            ],
            self::$tmpDir,
            true
        );

        $this->assertSame(2, $result['CODE']);
        $this->assertFileEquals(
            __DIR__ . '/../fixtures/expected_taint_graph.dot',
            self::$tmpDir.'/taints.dot'
        );
    }

    public function testLegacyConfigWithoutresolveFromConfigFile(): void
    {
        $this->runPsalmInit(1);
        $psalmXmlContent = file_get_contents(self::$tmpDir . '/psalm.xml');
        $count = 0;
        $psalmXmlContent = preg_replace('/resolveFromConfigFile="true"/', 'resolveFromConfigFile="false"', $psalmXmlContent, -1, $count);
        $this->assertEquals(1, $count);

        file_put_contents(self::$tmpDir . '/src/psalm.xml', $psalmXmlContent);

        $process = new Process(['php', $this->psalm, '--config=src/psalm.xml'], self::$tmpDir);
        $process->run();
        $this->assertSame(2, $process->getExitCode());
        $this->assertStringContainsString('InvalidReturnType', $process->getOutput());
    }

    /**
     * @return array{STDOUT: string, STDERR: string, CODE: int|null}
     */
    private function runPsalmInit(?int $level = null, ?string $php_version = null): array
    {
        $args = ['--init'];

        if ($level) {
            $args[] = 'src';
            $args[] = (string) $level;
        }

        $ret = $this->runPsalm($args, self::$tmpDir, false, false);

        $psalm_config_contents = file_get_contents(self::$tmpDir . '/psalm.xml');
        $psalm_config_contents = str_replace(
            'errorLevel="1"',
            'errorLevel="1" '
            . 'cacheDirectory="' . self::$tmpDir . '/cache" '
            . ($php_version ? ('phpVersion="' . $php_version . '"') : ''),
            $psalm_config_contents
        );
        file_put_contents(self::$tmpDir . '/psalm.xml', $psalm_config_contents);

        return $ret;
    }

    /** from comment by itay at itgoldman dot com at
     * https://www.php.net/manual/en/function.rmdir.php#117354
     */
    private static function recursiveRemoveDirectory(string $src): void
    {
        $dir = opendir($src);
        while (false !== ($file = readdir($dir))) {
            if (($file !== '.') && ($file !== '..')) {
                $full = $src . '/' . $file;
                if (is_dir($full)) {
                    self::recursiveRemoveDirectory($full);
                } else {
                    unlink($full);
                }
            }
        }
        closedir($dir);
        rmdir($src);
    }
}
