<?php
namespace Psalm\Tests\Config;

use Psalm\Config;
use Psalm\Internal\PluginManager\ConfigFile;
use Psalm\Internal\RuntimeCaches;

use function file_get_contents;
use function file_put_contents;
use function getcwd;
use function sys_get_temp_dir;
use function tempnam;
use function trim;
use function unlink;

use const PHP_EOL;

/** @group PluginManager */
class ConfigFileTest extends \Psalm\Tests\TestCase
{
    /** @var string */
    private $file_path;

    public function setUp() : void
    {
        RuntimeCaches::clearAll();
        $this->file_path = tempnam(sys_get_temp_dir(), 'psalm-test-config');
    }

    public function tearDown() : void
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
            </psalm>' . PHP_EOL
        );

        $config_file = new ConfigFile((string)getcwd(), $this->file_path);
        $config_file->addPlugin('a\b\c');

        $this->assertTrue(static::compareContentWithTemplateAndTrailingLineEnding(
            '<?xml version="1.0" encoding="UTF-8"?>
            <psalm
                name="bar"
            >
                <plugins><pluginClass xmlns="' . ConfigFile::NS . '" class="a\b\c"/></plugins>
            </psalm>',
            file_get_contents($this->file_path)
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
            <psalm></psalm>' . PHP_EOL
        );

        $config_file = new ConfigFile((string)getcwd(), $this->file_path);
        $config_file->addPlugin('a\b\c');

        $this->assertTrue(static::compareContentWithTemplateAndTrailingLineEnding(
            '<?xml version="1.0"?>
            <psalm><plugins><pluginClass xmlns="' . ConfigFile::NS . '" class="a\b\c"/></plugins></psalm>',
            file_get_contents($this->file_path)
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

        $this->assertSame(
            $noPlugins,
            file_get_contents($this->file_path)
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

        $this->assertXmlStringEqualsXmlString(
            $noPlugins,
            file_get_contents($this->file_path)
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

        $this->assertXmlStringEqualsXmlString(
            $noPlugins,
            file_get_contents($this->file_path)
        );
    }

    /**
     * @test
     */
    public function removeKillsSpecifiedPluginWithOneRemaining(): void
    {
        $noPlugins = trim('
            <?xml version="1.0"?>
            <psalm
                totallyTyped="false"
            >
                <plugins>
                    <pluginClass class="d\e\f"/>
                </plugins>
            </psalm>
        ');

        $abcEnabled = trim('
            <?xml version="1.0"?>
            <psalm
                totallyTyped="false"
            >
                <plugins>
                    <pluginClass class="a\b\c"/>
                    <pluginClass class="d\e\f"/>
                </plugins>
            </psalm>
        ');

        file_put_contents($this->file_path, $abcEnabled);

        $config_file = new ConfigFile((string)getcwd(), $this->file_path);
        $config_file->removePlugin('a\b\c');

        $this->assertXmlStringEqualsXmlString(
            $noPlugins,
            file_get_contents($this->file_path)
        );
    }

    /**
     * @param string $expected_template
     * @param string $contents
     *
     *
     * @psalm-pure
     */
    protected static function compareContentWithTemplateAndTrailingLineEnding($expected_template, $contents): bool
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
