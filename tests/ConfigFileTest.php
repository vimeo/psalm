<?php
namespace Psalm\Tests;

use DOMDocument;
use Psalm\PluginManager\ConfigFile;
use Psalm\Config;

/** @group PluginManager */
class ConfigFileTest extends TestCase
{
    /** @var string */
    private $file_path;

    /** @return void */
    public function setUp()
    {
        $this->file_path = tempnam(sys_get_temp_dir(), 'psalm-test-config');
    }

    /** @return void */
    public function tearDown()
    {
        @unlink($this->file_path);
    }

    /**
     * @return void
     * @test
     */
    public function canCreateConfigObject()
    {
        file_put_contents($this->file_path, trim('
            <?xml version="1.0"?>
            <psalm></psalm>
        '));

        $config_file = new ConfigFile((string)getcwd(), $this->file_path);
        $this->assertInstanceOf(Config::class, $config_file->getConfig());
    }

    /**
     * @return void
     * @test
     */
    public function addCanAddPluginClassToExistingPluginsNode()
    {
        file_put_contents($this->file_path, trim('
            <?xml version="1.0"?>
            <psalm>
                <plugins></plugins>
            </psalm>
        '));

        $config_file = new ConfigFile((string)getcwd(), $this->file_path);
        $config_file->addPlugin('a\b\c');

        $this->assertXmlStringEqualsXmlString(
            '<?xml version="1.0"?><psalm><plugins><pluginClass xmlns="' . ConfigFile::NS . '" class="a\b\c"/></plugins></psalm>',
            file_get_contents($this->file_path)
        );
    }

    /**
     * @return void
     * @test
     */
    public function addCanCreateMissingPluginsNode()
    {
        file_put_contents($this->file_path, trim('
            <?xml version="1.0"?>
            <psalm></psalm>
        '));

        $config_file = new ConfigFile((string)getcwd(), $this->file_path);
        $config_file->addPlugin('a\b\c');

        $this->assertXmlStringEqualsXmlString(
            '<?xml version="1.0"?><psalm><plugins xmlns="' . ConfigFile::NS . '"><pluginClass class="a\b\c"/></plugins></psalm>',
            file_get_contents($this->file_path)
        );
    }

    /**
     * @return void
     * @test
     */
    public function removeDoesNothingWhenThereIsNoPluginsNode()
    {
        $noPlugins = trim('
            <?xml version="1.0"?>
            <psalm></psalm>
        ');
        file_put_contents($this->file_path, $noPlugins);

        $config_file = new ConfigFile((string)getcwd(), $this->file_path);
        $config_file->removePlugin('a\b\c');

        $this->assertXmlStringEqualsXmlString(
            $noPlugins,
            file_get_contents($this->file_path)
        );
    }

    /**
     * @return void
     * @test
     */
    public function removeKillsEmptyPluginsNode()
    {
        $noPlugins = trim('
            <?xml version="1.0"?>
            <psalm></psalm>
        ');

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
     * @return void
     * @test
     */
    public function removeKillsSpecifiedPlugin()
    {
        $noPlugins = trim('
            <?xml version="1.0"?>
            <psalm></psalm>
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
}
