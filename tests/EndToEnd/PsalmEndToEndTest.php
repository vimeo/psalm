<?php


namespace Psalm\Tests\EndToEnd;

use PHPUnit\Framework\TestCase;

use Symfony\Component\Process\Process;

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
    private static $previousWorkingDir;

    /** @var string */
    private static $tmpDir;

    public static function setUpBeforeClass(): void
    {
        self::$tmpDir = tempnam(sys_get_temp_dir(), 'PsalmEndToEndTest_');
        unlink(self::$tmpDir);
        mkdir(self::$tmpDir);

        $getcwd = getcwd();
        if (! is_string($getcwd)) {
            throw new \Exception('Couldn\'t get working directory');
        }
        self::$previousWorkingDir = $getcwd;
        chdir(self::$tmpDir);

        mkdir('src');

        copy(__DIR__ . '/../fixtures/DummyProjectWithErrors/composer.json', 'composer.json');

        (new Process(['composer', 'install']))->mustRun();
    }

    public static function tearDownAfterClass(): void
    {
        chdir(self::$previousWorkingDir);
        self::recursiveRemoveDirectory(self::$tmpDir);
        parent::tearDownAfterClass();
    }

    public function setUp(): void
    {
        @unlink('psalm.xml');
        copy(__DIR__ . '/../fixtures/DummyProjectWithErrors/src/FileWithErrors.php', 'src/FileWithErrors.php');
        parent::setUp();
    }

    public function testHelpReturnsMessage(): void
    {
        $this->assertStringContainsString('Usage:', $this->runPsalm(['--help'])['STDOUT']);
    }

    public function testVersion(): void
    {
        $this->assertStringStartsWith('Psalm 3', $this->runPsalm(['--version'])['STDOUT']);
    }

    public function testInit(): void
    {
        $this->assertStringStartsWith('Config file created', $this->runPsalm(['--init'])['STDOUT']);
        $this->assertFileExists('psalm.xml');
    }

    public function testAlter(): void
    {
        $this->runPsalm(['--init']);

        $this->assertStringContainsString(
            'No errors found!',
            $this->runPsalm(['--alter', '--issues=all'])['STDOUT']
        );

        $this->assertSame(0, $this->runPsalm([])['CODE']);
    }

    public function testPsalter(): void
    {
        $this->runPsalm(['--init']);
        (new Process([$this->psalter, '--alter', '--issues=InvalidReturnType']))->mustRun();
        $this->assertSame(0, $this->runPsalm([])['CODE']);
    }

    public function testPsalm(): void
    {
        $this->runPsalm(['--init']);
        $result = $this->runPsalm([], true);
        $this->assertStringContainsString('InvalidReturnType', $result['STDOUT']);
        $this->assertStringContainsString('InvalidReturnStatement', $result['STDOUT']);
        $this->assertStringContainsString('2 errors', $result['STDOUT']);
        $this->assertSame(1, $result['CODE']);
    }

    /**
     * @param array<string> $args
     * @return array{STDOUT: string, STDERR: string, CODE: int|null}
     */
    private function runPsalm(array $args, bool $shouldFail = false): array
    {
        $process = new Process(array_merge([$this->psalm], $args));

        if (! $shouldFail) {
            $process->mustRun();
        } else {
            $process->run();
            $this->assertGreaterThan(0, $process->getExitCode());
        }

        return [
            'STDOUT' => $process->getOutput(),
            'STDERR' => $process->getErrorOutput(),
            'CODE' => $process->getExitCode()
        ];
    }

    /** from comment by itay at itgoldman dot com at
     * https://www.php.net/manual/en/function.rmdir.php#117354
     */
    private static function recursiveRemoveDirectory(string $src): void
    {
        $dir = opendir($src);
        while (false !== ( $file = readdir($dir))) {
            if (( $file != '.' ) && ( $file != '..' )) {
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
