<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use PHPUnit\Framework\TestCase;
use Psalm\Config;

use function dirname;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function rmdir;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

use const DIRECTORY_SEPARATOR;

final class ConfigCommandLinePathsTest extends TestCase
{
    private string $project_dir = '';

    private string $outside_dir = '';

    /** @var list<string> */
    private array $argv_backup = [];

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        global $argv;

        $this->argv_backup = $argv ?? [];

        $root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('psalm_cli_paths_', true);
        $this->project_dir = $root . DIRECTORY_SEPARATOR . 'project';
        $this->outside_dir = $root . DIRECTORY_SEPARATOR . 'outside';

        mkdir($this->project_dir . DIRECTORY_SEPARATOR . 'src', 0777, true);
        mkdir($this->outside_dir, 0777, true);
        file_put_contents($this->outside_dir . DIRECTORY_SEPARATOR . 'a.php', '<?php');
    }

    #[Override]
    protected function tearDown(): void
    {
        global $argv;

        $argv = $this->argv_backup;
        Config::setAddCommandLinePaths(false);

        unlink($this->outside_dir . DIRECTORY_SEPARATOR . 'a.php');
        rmdir($this->outside_dir);
        rmdir($this->project_dir . DIRECTORY_SEPARATOR . 'src');
        rmdir($this->project_dir);
        rmdir(dirname($this->project_dir));

        parent::tearDown();
    }

    public function testALibraryHostWithArgumentsOfItsOwnIsNeitherReadNorExited(): void
    {
        global $argv;

        $argv = ['phpunit', '--test-result-file', '/nonexistent/result.xml'];

        $config = $this->load();

        self::assertTrue(is_dir($this->project_dir));
        self::assertFalse($config->isInProjectDirs($this->outside_dir . DIRECTORY_SEPARATOR . 'a.php'));
    }

    public function testThePathsOfTheCommandLineAreAddedWhileTheCliLoadsTheConfig(): void
    {
        global $argv;

        $argv = ['psalm', $this->outside_dir . DIRECTORY_SEPARATOR . 'a.php'];

        Config::setAddCommandLinePaths(true);

        $config = $this->load();

        self::assertTrue($config->isInProjectDirs($this->outside_dir . DIRECTORY_SEPARATOR . 'a.php'));
    }

    public function testThePathsOfPsalmPluginAreNotReadAsItsArgumentsAreItsOwn(): void
    {
        global $argv;

        $argv = ['/usr/bin/psalm-plugin', $this->outside_dir . DIRECTORY_SEPARATOR . 'a.php'];

        Config::setAddCommandLinePaths(true);

        $config = $this->load();

        self::assertFalse($config->isInProjectDirs($this->outside_dir . DIRECTORY_SEPARATOR . 'a.php'));
    }

    public function testNoArgumentsAddNothing(): void
    {
        global $argv;

        $argv = [];

        Config::setAddCommandLinePaths(true);

        $config = $this->load();

        self::assertFalse($config->isInProjectDirs($this->outside_dir . DIRECTORY_SEPARATOR . 'a.php'));
    }

    private function load(): Config
    {
        return Config::loadFromXML(
            $this->project_dir,
            '<?xml version="1.0"?>
            <psalm errorLevel="1">
                <projectFiles>
                    <directory name="src"/>
                </projectFiles>
            </psalm>',
        );
    }
}
