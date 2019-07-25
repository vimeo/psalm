<?php
namespace Psalm\Tests\EndToEnd;

use function array_merge;

use function closedir;
use function copy;
use function getcwd;
use function is_dir;
use function is_string;
use function mkdir;
use function opendir;
use PHPUnit\Framework\TestCase;
use function readdir;
use function rmdir;
use Symfony\Component\Process\Process;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;
use function file_get_contents;
use function file_put_contents;
use function preg_replace;

/**
 * Tests some of the most important use cases of the psalm and psalter commands, by launching a new
 * process as if invoked by a real user.
 *
 * This is primarily intended to test the code in `psalm`, `src/psalm.php` and related files.
 */
class PsalmEndToEndTest extends TestCase
{
    /** @var string */
    private $psalm = __DIR__ . '/../../psalm';

    /** @var string */
    private $psalter = __DIR__ . '/../../psalter';

    /** @var string */
    private static $tmpDir;

    public static function setUpBeforeClass(): void
    {
        self::$tmpDir = tempnam(sys_get_temp_dir(), 'PsalmEndToEndTest_');
        unlink(self::$tmpDir);
        mkdir(self::$tmpDir);

        $getcwd = getcwd();
        if (!is_string($getcwd)) {
            throw new \Exception('Couldn\'t get working directory');
        }

        mkdir(self::$tmpDir . '/src');

        copy(__DIR__ . '/../fixtures/DummyProjectWithErrors/composer.json', self::$tmpDir . '/composer.json');

        (new Process(['composer', 'install'], self::$tmpDir))->mustRun();
    }

    public static function tearDownAfterClass(): void
    {
        self::recursiveRemoveDirectory(self::$tmpDir);
        parent::tearDownAfterClass();
    }

    public function setUp(): void
    {
        @unlink(self::$tmpDir . '/psalm.xml');
        copy(__DIR__ . '/../fixtures/DummyProjectWithErrors/src/FileWithErrors.php', self::$tmpDir . '/src/FileWithErrors.php');
        parent::setUp();
    }

    public function testHelpReturnsMessage(): void
    {
        $this->assertStringContainsString('Usage:', $this->runPsalm(['--help'])['STDOUT']);
    }

    public function testVersion(): void
    {
        $this->assertStringStartsWith('Psalm 3', $this->runPsalm(['--version'], false, false)['STDOUT']);
    }

    public function testInit(): void
    {
        $this->assertStringStartsWith('Config file created', $this->runPsalmInit()['STDOUT']);
        $this->assertFileExists(self::$tmpDir . '/psalm.xml');
    }

    public function testAlter(): void
    {
        $this->runPsalmInit();

        $this->assertStringContainsString(
            'No errors found!',
            $this->runPsalm(['--alter', '--issues=all'], false, true)['STDOUT']
        );

        $this->assertSame(0, $this->runPsalm([])['CODE']);
    }

    public function testPsalter(): void
    {
        $this->runPsalmInit();
        (new Process(['php', $this->psalter, '--alter', '--issues=InvalidReturnType'], self::$tmpDir))->mustRun();
        $this->assertSame(0, $this->runPsalm([])['CODE']);
    }

    public function testPsalm(): void
    {
        $this->runPsalmInit();
        $result = $this->runPsalm([], true);
        $this->assertStringContainsString('InvalidReturnType', $result['STDOUT']);
        $this->assertStringContainsString('InvalidReturnStatement', $result['STDOUT']);
        $this->assertStringContainsString('2 errors', $result['STDOUT']);
        $this->assertSame(1, $result['CODE']);
    }

    public function testLegacyConfigWithoutresolveFromConfigFile(): void
    {
        $this->runPsalmInit();
        $psalmXmlContent = file_get_contents(self::$tmpDir . '/psalm.xml');
        $count = 0;
        $psalmXmlContent = preg_replace('/resolveFromConfigFile="true"/', '', $psalmXmlContent, -1, $count);
        $this->assertEquals(1, $count);

        file_put_contents(self::$tmpDir . '/src/psalm.xml', $psalmXmlContent);

        $process = new Process(['php', $this->psalm, '--config=src/psalm.xml'], self::$tmpDir);
        $process->run();
        $this->assertSame(1, $process->getExitCode());
        $this->assertStringContainsString('InvalidReturnType', $process->getOutput());
    }

    /**
     * @param array<string> $args
     *
     * @return array{STDOUT: string, STDERR: string, CODE: int|null}
     */
    private function runPsalm(array $args, bool $shouldFail = false, bool $relyOnConfigDir = true): array
    {
        // As config files all contain `resolveFromConfigFile="true"` Psalm shouldn't need to be run from the same
        // directory that the code being analysed exists in.

        // Windows doesn't read shabangs, so to allow this to work on windows we run `php psalm` rather than just `psalm`.

        if ($relyOnConfigDir) {
            $process = new Process(array_merge(['php', $this->psalm, '-c=' . self::$tmpDir . '/psalm.xml'], $args), null);
        } else {
            $process = new Process(array_merge(['php', $this->psalm], $args), self::$tmpDir);
        }

        if (!$shouldFail) {
            $process->mustRun();
        } else {
            $process->run();
            $this->assertGreaterThan(0, $process->getExitCode());
        }

        return [
            'STDOUT' => $process->getOutput(),
            'STDERR' => $process->getErrorOutput(),
            'CODE' => $process->getExitCode(),
        ];
    }


    /**
     * @return array{STDOUT: string, STDERR: string, CODE: int|null}
     */
    private function runPsalmInit(): array
    {
        return $this->runPsalm(['--init'], false, false);
    }

    /** from comment by itay at itgoldman dot com at
     * https://www.php.net/manual/en/function.rmdir.php#117354
     */
    private static function recursiveRemoveDirectory(string $src): void
    {
        $dir = opendir($src);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
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
