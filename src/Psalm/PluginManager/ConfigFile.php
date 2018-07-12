<?php
namespace Psalm\PluginManager;

use Psalm\Config;
use SimpleXmlElement;
use RuntimeException;

class ConfigFile
{
    const NS = 'https://getpsalm.org/schema/config';
    /** @var string */
    private $path;

    /** @var string */
    private $current_dir;

    public function __construct(string $current_dir, ?string $explicit_path)
    {
        $this->current_dir = $current_dir;

        if ($explicit_path) {
            $this->path = $explicit_path;
        } else {
            $path = Config::locateConfigFile($current_dir);
            if (!$path) {
                throw new RuntimeException('Cannot find Psalm config');
            }
            $this->path = $path;
        }
    }

    public function getConfig(): Config
    {
        return Config::loadFromXMLFile($this->path, $this->current_dir);
    }

    public function removePlugin(string $plugin_class): void
    {
        $config_xml = $this->readXml();
        if (!isset($config_xml->plugins)) {
            // no plugins, nothing to remove
            return;
        }
        assert($config_xml->plugins instanceof SimpleXmlElement);
        if (!isset($config_xml->plugins->pluginClass)) {
            // no plugin classes, nothing to remove
            return;
        }
        assert($config_xml->plugins->pluginClass instanceof SimpleXmlElement);
        /** @psalm-suppress MixedAssignment */
        foreach ($config_xml->plugins->pluginClass as $entry) {
            assert($entry instanceof SimpleXmlElement);
            if ((string)$entry['class'] === $plugin_class) {
                unset($entry[0]);
                break;
            }
        }
        if (!$config_xml->plugins->children()->count()) {
            // avoid breaking old psalm binaries, whose schema did not allow empty plugins
            unset($config_xml->plugins[0]);
        }

        $config_xml->asXML($this->path);
    }

    public function addPlugin(string $plugin_class): void
    {
        $config_xml = $this->readXml();
        if (!isset($config_xml->plugins)) {
            $config_xml->addChild('plugins', "\n", self::NS);
        }
        assert($config_xml->plugins instanceof SimpleXmlElement);
        $config_xml->plugins->addChild('pluginClass', '', self::NS)->addAttribute('class', $plugin_class);
        $config_xml->asXML($this->path);
    }

    private function readXml(): SimpleXmlElement
    {
        return new SimpleXmlElement(file_get_contents($this->path));
    }
}
