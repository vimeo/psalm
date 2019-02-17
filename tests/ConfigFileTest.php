<?php
namespace Psalm\Tests;

use Psalm\Internal\PluginManager\ConfigFile;
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
     * @return void
     * @test
     */
    public function addCanCreateMissingPluginsNode()
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
     * @return void
     * @test
     */
    public function removeDoesNothingWhenThereIsNoPluginsNode()
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
     * @return void
     * @test
     */
    public function removeKillsEmptyPluginsNode()
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
     * @return void
     * @test
     */
    public function removeKillsSpecifiedPlugin()
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
     * @return void
     * @test
     */
    public function removeKillsSpecifiedPluginWithOneRemaining()
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
    * @return bool
    */
    protected static function compareContentWithTemplateAndTrailingLineEnding($expected_template, $contents)
    {
        $passed = false;

        foreach ([PHP_EOL, "\n", "\r", "\r\n"] as $eol) {
            if (! $passed && $contents === ($expected_template . $eol)) {
                $passed = true;
            }
        }

        return $passed;
    }
}
