<?php
namespace Psalm\PluginManager;

use Psalm\Config;
use SimpleXmlElement;

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
                // throw
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
        foreach ($config_xml->plugins->pluginClass as $entry) {
            if ((string)$entry['class'] === $plugin_class) {
                unset($entry[0]);
                break;
            }
        }

        $config_xml->asXML($this->path);
    }

    public function addPlugin(string $plugin_class): void
    {
        $config_xml = $this->readXml();
        if (!isset($config_xml->plugins)) {
            $config_xml->addChild('plugins', "\n", self::NS);
        }
        $config_xml->plugins->addChild('pluginClass', '', self::NS)->addAttribute('class', $plugin_class);
        $config_xml->asXML($this->path);
    }

    private function readXml(): SimpleXmlElement
    {
        return new SimpleXmlElement(file_get_contents($this->path));
    }
}
