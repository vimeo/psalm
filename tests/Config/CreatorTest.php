<?php

declare(strict_types=1);

namespace Psalm\Tests\Config;

use Override;
use Psalm\Config\Creator;
use Psalm\Exception\ConfigCreationException;
use Psalm\Tests\TestCase;

use function dirname;
use function file_put_contents;
use function json_encode;
use function mkdir;
use function realpath;
use function rmdir;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

use const DIRECTORY_SEPARATOR;
use const JSON_THROW_ON_ERROR;

final class CreatorTest extends TestCase
{
    #[Override]
    public static function setUpBeforeClass(): void
    {
    }

    #[Override]
    public function setUp(): void
    {
    }

    public function testDiscoverLibDirectory(): void
    {
        $lib_contents = Creator::getContents(
            dirname(__DIR__, 1)
                . DIRECTORY_SEPARATOR . 'fixtures'
                . DIRECTORY_SEPARATOR . 'config_discovery'
                . DIRECTORY_SEPARATOR . 'files_in_lib',
            null,
            1,
            'vendor',
        );

        $this->assertSame('<?xml version="1.0"?>
<psalm
    errorLevel="1"
    resolveFromConfigFile="true"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xmlns="https://getpsalm.org/schema/config"
    xsi:schemaLocation="https://getpsalm.org/schema/config vendor/vimeo/psalm/config.xsd"
    findUnusedBaselineEntry="true"
    findUnusedCode="true"
>
    <projectFiles>
        <directory name="lib" />
        <ignoreFiles>
            <directory name="vendor" />
        </ignoreFiles>
    </projectFiles>
</psalm>
', $lib_contents);
    }

    public function testSkipsMissingAutoloadDirectories(): void
    {
        $contents = Creator::getContents(
            dirname(__DIR__, 1)
                . DIRECTORY_SEPARATOR . 'fixtures'
                . DIRECTORY_SEPARATOR . 'config_discovery'
                . DIRECTORY_SEPARATOR . 'missing_autoload_dirs',
            null,
            1,
            'vendor',
        );

        $this->assertSame('<?xml version="1.0"?>
<psalm
    errorLevel="1"
    resolveFromConfigFile="true"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xmlns="https://getpsalm.org/schema/config"
    xsi:schemaLocation="https://getpsalm.org/schema/config vendor/vimeo/psalm/config.xsd"
    findUnusedBaselineEntry="true"
    findUnusedCode="true"
>
    <projectFiles>
        <directory name="lib" />
        <ignoreFiles>
            <directory name="vendor" />
        </ignoreFiles>
    </projectFiles>
</psalm>
', $contents);
    }

    public function testFailsWhenAllAutoloadDirectoriesAreMissing(): void
    {
        $this->expectException(ConfigCreationException::class);
        $this->expectExceptionMessage('Could not located any PSR-0 or PSR-4-compatible paths');

        Creator::getContents(
            dirname(__DIR__, 1)
                . DIRECTORY_SEPARATOR . 'fixtures'
                . DIRECTORY_SEPARATOR . 'config_discovery'
                . DIRECTORY_SEPARATOR . 'only_missing_autoload_dirs',
            null,
            1,
            'vendor',
        );
    }

    public function testKeepsExistingAbsoluteAutoloadDirectories(): void
    {
        $absolute_lib = realpath(
            dirname(__DIR__, 1)
                . DIRECTORY_SEPARATOR . 'fixtures'
                . DIRECTORY_SEPARATOR . 'config_discovery'
                . DIRECTORY_SEPARATOR . 'files_in_lib'
                . DIRECTORY_SEPARATOR . 'lib',
        );
        $this->assertNotFalse($absolute_lib);

        $project_dir = tempnam(sys_get_temp_dir(), 'PsalmCreatorTest_');
        $this->assertNotFalse($project_dir);
        unlink($project_dir);
        mkdir($project_dir);

        try {
            file_put_contents(
                $project_dir . DIRECTORY_SEPARATOR . 'composer.json',
                json_encode(['autoload' => ['psr-4' => ['Foo\\Bar\\' => $absolute_lib]]], JSON_THROW_ON_ERROR),
            );

            $contents = Creator::getContents($project_dir, null, 1, 'vendor');
        } finally {
            @unlink($project_dir . DIRECTORY_SEPARATOR . 'composer.json');
            @rmdir($project_dir);
        }

        $this->assertStringContainsString('<directory name="' . $absolute_lib . '" />', $contents);
    }
}
