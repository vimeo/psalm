<?php

declare(strict_types=1);

namespace Psalm\Tests\Config;

use Psalm\Config;
use Psalm\Exception\ConfigException;
use Psalm\Internal\PluginManager\ConfigFile;
use Psalm\Internal\RuntimeCaches;
use Psalm\Tests\TestCase;

use function assert;
use function file_get_contents;
use function file_put_contents;
use function getcwd;
use function sys_get_temp_dir;
use function tempnam;
use function trim;
use function unlink;

use const PHP_EOL;

/** @group PluginManager */
class ConfigFileTest extends TestCase
{
    private string $file_path;

    public function setUp(): void
    {
        RuntimeCaches::clearAll();
        $temp_name = tempnam(sys_get_temp_dir(), 'psalm-test-config');
        assert($temp_name !== false);
        $this->file_path = $temp_name;
    }

    public function tearDown(): void
    {
        @unlink($this->file_path);
    }

    /**
     * @test
     */
    public function canCreateConfigObject(): void
    {
        file_put_contents($this->file_path, trim('
            <?xml version="1.0"?>
            <psalm></psalm>
        '));

        $config_file = new ConfigFile((string)getcwd(), $this->file_path);
        $this->assertInstanceOf(Config::class, $config_file->getConfig());
    }

    /**
     * @test
     */
    public function addCanAddPluginClassToExistingPluginsNode(): void
    {
        file_put_contents(
            $this->file_path,
            '<?xml version="1.0" encoding="UTF-8"?>
            <psalm
                name="bar"
            >
                <plugins></plugins>
            </psalm>' . PHP_EOL,
        );

        $config_file = new ConfigFile((string)getcwd(), $this->file_path);
        $config_file->addPlugin('a\b\c');
        $file_contents = file_get_contents($this->file_path);
        assert($file_contents !== false);

        $this->assertTrue(static::compareContentWithTemplateAndTrailingLineEnding(
            '<?xml version="1.0" encoding="UTF-8"?>
            <psalm
                name="bar"
            >
                <plugins><pluginClass xmlns="' . Config::CONFIG_NAMESPACE . '" class="a\b\c"/></plugins>
            </psalm>',
            $file_contents,
        ));
    }

    /**
     * @test
     */
    public function addCanCreateMissingPluginsNode(): void
    {
        file_put_contents(
            $this->file_path,
            '<?xml version="1.0"?>
            <psalm></psalm>' . PHP_EOL,
        );

        $config_file = new ConfigFile((string)getcwd(), $this->file_path);
        $config_file->addPlugin('a\b\c');
        $file_contents = file_get_contents($this->file_path);
        assert($file_contents !== false);

        $this->assertTrue(static::compareContentWithTemplateAndTrailingLineEnding(
            '<?xml version="1.0"?>
            <psalm><plugins><pluginClass xmlns="' . Config::CONFIG_NAMESPACE . '" class="a\b\c"/></plugins></psalm>',
            $file_contents,
        ));
    }

    /**
     * @test
     */
    public function removeDoesNothingWhenThereIsNoPluginsNode(): void
    {
        $noPlugins = '<?xml version="1.0"?>
            <psalm></psalm>' . PHP_EOL;

        file_put_contents($this->file_path, $noPlugins);

        $config_file = new ConfigFile((string)getcwd(), $this->file_path);
        $config_file->removePlugin('a\b\c');
        $file_contents = file_get_contents($this->file_path);
        assert($file_contents !== false);

        $this->assertSame(
            $noPlugins,
            $file_contents,
        );
    }

    /**
     * @test
     */
    public function removeKillsEmptyPluginsNode(): void
    {
        $noPlugins = '<?xml version="1.0" encoding="UTF-8"?>
            <psalm/>' . PHP_EOL;

        $emptyPlugins = trim('
            <?xml version="1.0"?>
            <psalm><plugins></plugins></psalm>
        ');

        file_put_contents($this->file_path, $emptyPlugins);

        $config_file = new ConfigFile((string)getcwd(), $this->file_path);
        $config_file->removePlugin('a\b\c');
        $file_contents = file_get_contents($this->file_path);
        assert($file_contents !== false);

        $this->assertXmlStringEqualsXmlString(
            $noPlugins,
            $file_contents,
        );
    }

    /**
     * @test
     */
    public function removeKillsSpecifiedPlugin(): void
    {
        $noPlugins = trim('
            <?xml version="1.0"?>
            <psalm/>
        ');

        $abcEnabled = trim('
            <?xml version="1.0"?>
            <psalm><plugins><pluginClass class="a\b\c"/></plugins></psalm>
        ');

        file_put_contents($this->file_path, $abcEnabled);

        $config_file = new ConfigFile((string)getcwd(), $this->file_path);
        $config_file->removePlugin('a\b\c');
        $file_contents = file_get_contents($this->file_path);
        assert($file_contents !== false);

        $this->assertXmlStringEqualsXmlString(
            $noPlugins,
            $file_contents,
        );
    }

    /**
     * @test
     */
    public function removeKillsSpecifiedPluginWithOneRemaining(): void
    {
        $noPlugins = trim('
            <?xml version="1.0"?>
            <psalm>
                <plugins>
                    <pluginClass class="d\e\f"/>
                </plugins>
            </psalm>
        ');

        $abcEnabled = trim('
            <?xml version="1.0"?>
            <psalm>
                <plugins>
                    <pluginClass class="a\b\c"/>
                    <pluginClass class="d\e\f"/>
                </plugins>
            </psalm>
        ');

        file_put_contents($this->file_path, $abcEnabled);

        $config_file = new ConfigFile((string)getcwd(), $this->file_path);
        $config_file->removePlugin('a\b\c');
        $file_contents = file_get_contents($this->file_path);
        assert($file_contents !== false);

        $this->assertXmlStringEqualsXmlString(
            $noPlugins,
            $file_contents,
        );
    }

    public function testEnableExtensions(): void
    {
        file_put_contents($this->file_path, trim('
            <?xml version="1.0"?>
            <psalm>
                <enableExtensions>
                    <extension name="mysqli"/>
                    <extension name="pdo"/>
                </enableExtensions>
            </psalm>
        '));

        $config_file = new ConfigFile((string)getcwd(), $this->file_path);
        $config = $config_file->getConfig();

        $this->assertTrue($config->php_extensions["mysqli"]);
        $this->assertTrue($config->php_extensions["pdo"]);
    }

    public function testDisableExtensions(): void
    {
        file_put_contents($this->file_path, trim('
            <?xml version="1.0"?>
            <psalm>
                <enableExtensions>
                    <extension name="mysqli"/>
                    <extension name="pdo"/>
                </enableExtensions>
                <disableExtensions>
                    <extension name="mysqli"/>
                    <extension name="pdo"/>
                </disableExtensions>
            </psalm>
        '));

        $config_file = new ConfigFile((string)getcwd(), $this->file_path);
        $config = $config_file->getConfig();

        $this->assertFalse($config->php_extensions["mysqli"]);
        $this->assertFalse($config->php_extensions["pdo"]);
    }

    public function testInvalidExtension(): void
    {
        $this->expectException(ConfigException::class);

        file_put_contents($this->file_path, trim('
            <?xml version="1.0"?>
            <psalm>
                <enableExtensions>
                    <extension name="NotARealExtension"/>
                </enableExtensions>
            </psalm>
        '));

        (new ConfigFile((string)getcwd(), $this->file_path))->getConfig();
    }

    /**
     * @psalm-pure
     */
    protected static function compareContentWithTemplateAndTrailingLineEnding(string $expected_template, string $contents): bool
    {
        $passed = false;

        foreach ([PHP_EOL, "\n", "\r", "\r\n"] as $eol) {
            if (!$passed && $contents === ($expected_template . $eol)) {
                $passed = true;
            }
        }

        return $passed;
    }
}
