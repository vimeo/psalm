<?php

declare(strict_types=1);

namespace Psalm\Tests\Config;

use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Psalm\Config\Creator;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\Tests\TestCase;

use function dirname;

use const DIRECTORY_SEPARATOR;

final class CreatorTest extends TestCase
{
    /**
     * @psalm-mutation-free
     */
    #[Override]
    public static function setUpBeforeClass(): void
    {
    }

    /**
     * @psalm-mutation-free
     */
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

    #[DataProvider('providerIssuePaths')]
    public function testLevelUsesProjectRelativeDirectoryNames(
        string $file_path,
        string $file_name,
        int $expected_level,
    ): void {
        $issue = new IssueData(
            IssueData::SEVERITY_ERROR,
            1,
            1,
            'MixedAssignment',
            'Unable to determine the type of $value',
            $file_name,
            $file_path,
            '',
            '',
            0,
            0,
            0,
            0,
            1,
            1,
            error_level: 1,
        );

        $this->assertSame($expected_level, Creator::getLevel([$issue], 1));
    }

    /**
     * @return array<string, array{string, string, int}>
     * @psalm-pure
     */
    public static function providerIssuePaths(): array
    {
        return [
            'plain project' => ['/tmp/app/src/File.php', 'src/File.php', 2],
            'vendor in project name' => ['/tmp/vendor-app/src/File.php', 'src/File.php', 2],
            'stub in project name' => ['/tmp/stubhub-app/src/File.php', 'src/File.php', 2],
            'project under vendor directory' => ['/tmp/vendor/app/src/File.php', 'src/File.php', 2],
            'project under stubs directory' => ['/tmp/stubs/app/src/File.php', 'src/File.php', 2],
            'vendor in source directory name' => ['/tmp/app/src/vendor-tools/File.php', 'src/vendor-tools/File.php', 2],
            'vendor suffix in source directory' => ['/tmp/app/src/myvendor/File.php', 'src/myvendor/File.php', 2],
            'stub in source filename' => ['/tmp/app/src/stub.php', 'src/stub.php', 2],
            'vendor directory' => ['/tmp/app/vendor/lib/File.php', 'vendor/lib/File.php', 1],
            'nested vendor directory' => ['/tmp/app/lib/vendor/File.php', 'lib/vendor/File.php', 1],
            'stub directory' => ['/tmp/app/stub/File.php', 'stub/File.php', 1],
            'stubs directory' => ['/tmp/app/stubs/File.php', 'stubs/File.php', 1],
            'relative vendor path' => ['vendor/lib/File.php', 'vendor/lib/File.php', 1],
            'relative stub path' => ['stub/File.php', 'stub/File.php', 1],
            'Windows project name' => ['C:\\vendor-app\\src\\File.php', 'src\\File.php', 2],
            'Windows vendor directory' => ['C:\\app\\vendor\\lib\\File.php', 'vendor\\lib\\File.php', 1],
        ];
    }
}
