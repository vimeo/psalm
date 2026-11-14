<?php

declare(strict_types=1);

namespace Psalm\Tests\EndToEnd;

use Exception;
use Override;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

use function assert;
use function closedir;
use function copy;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function getcwd;
use function glob;
use function is_dir;
use function is_string;
use function mkdir;
use function opendir;
use function readdir;
use function rmdir;
use function str_replace;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

use const DIRECTORY_SEPARATOR;

/**
 * Runs the psalm, psalter and psalm-refactor commands in a copy of the DummyProjectWithErrors fixture.
 *
 * The tests are split over several classes so that ParaTest, which runs a test file per process,
 * can run them in parallel.
 *
 * This is primarily intended to test the code in `psalm`, `src/psalm.php` and related files.
 */
abstract class PsalmEndToEndTestCase extends TestCase
{
    use PsalmRunnerTrait;

    protected static string $tmpDir;

    #[Override]
    public static function setUpBeforeClass(): void
    {
        self::$tmpDir = tempnam(sys_get_temp_dir(), 'PsalmEndToEndTest_');
        assert(self::$tmpDir !== false);
        unlink(self::$tmpDir);
        mkdir(self::$tmpDir);

        $getcwd = getcwd();
        if (!is_string($getcwd)) {
            throw new Exception('Couldn\'t get working directory');
        }

        copy(__DIR__ . '/../fixtures/DummyProjectWithErrors/composer.json', self::$tmpDir . '/composer.json');

        $process = new Process(['composer', 'install', '--no-plugins'], self::$tmpDir, null, null, 120);
        $process->mustRun();
    }

    #[Override]
    public static function tearDownAfterClass(): void
    {
        self::recursiveRemoveDirectory(self::$tmpDir);
        parent::tearDownAfterClass();
    }

    #[Override]
    public function setUp(): void
    {
        mkdir(self::$tmpDir . '/src');

        copy(
            __DIR__ . '/../fixtures/DummyProjectWithErrors/src/FileWithErrors.php',
            self::$tmpDir . '/src/FileWithErrors.php',
        );
        parent::setUp();
    }

    #[Override]
    public function tearDown(): void
    {
        @unlink(self::$tmpDir . '/psalm.xml');
        @unlink(self::$tmpDir . '/CODEOWNERS');

        if (file_exists(self::$tmpDir . '/plugins')) {
            self::recursiveRemoveDirectory(self::$tmpDir . '/plugins');
        }

        $baseline_files = glob(self::$tmpDir . '/*baseline*.xml');
        assert($baseline_files !== false);
        foreach ($baseline_files as $baseline_file) {
            unlink($baseline_file);
        }

        if (file_exists(self::$tmpDir . '/cache')) {
            self::recursiveRemoveDirectory(self::$tmpDir . '/cache');
        }

        if (file_exists(self::$tmpDir . '/src')) {
            self::recursiveRemoveDirectory(self::$tmpDir . '/src');
        }

        parent::tearDown();
    }

    /**
     * @return string the contents of src/A.php, which psalter would change
     */
    protected function writeAFileWithAMissingReturnType(): string
    {
        $this->runPsalmInit();
        $psalmXml = file_get_contents(self::$tmpDir . '/psalm.xml');
        $psalmXml = str_replace('<psalm', '<psalm runTaintAnalysis="false"', (string)$psalmXml);
        file_put_contents(self::$tmpDir . '/psalm.xml', $psalmXml);

        $source = "<?php\n\nfunction a() {\n    return 1;\n}\n";
        file_put_contents(self::$tmpDir . '/src/A.php', $source);

        return $source;
    }

    /**
     * @return array{STDOUT: string, STDERR: string, CODE: int|null}
     */
    protected function runPsalmInit(?int $level = null, ?string $php_version = null): array
    {
        $args = ['--init'];

        if ($level !== null) {
            $args[] = 'src';
            $args[] = (string)$level;
        }

        $ret = $this->runPsalm($args, self::$tmpDir, false, false);

        $psalm_config_contents = file_get_contents(self::$tmpDir . '/psalm.xml');
        assert($psalm_config_contents !== false);
        $psalm_config_contents = str_replace(
            'errorLevel="1"',
            'errorLevel="1" '
            . 'cacheDirectory="' . self::$tmpDir . '/cache" '
            . ($php_version !== null ? ('phpVersion="' . $php_version . '"') : ''),
            $psalm_config_contents,
        );
        file_put_contents(self::$tmpDir . '/psalm.xml', $psalm_config_contents);

        return $ret;
    }

    /** from comment by itay at itgoldman dot com at
     * https://www.php.net/manual/en/function.rmdir.php#117354
     */
    protected static function recursiveRemoveDirectory(string $src): void
    {
        $dir = opendir($src);
        assert($dir !== false);
        while (false !== ($file = readdir($dir))) {
            if (($file !== '.') && ($file !== '..')) {
                $full = $src . DIRECTORY_SEPARATOR . $file;
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
