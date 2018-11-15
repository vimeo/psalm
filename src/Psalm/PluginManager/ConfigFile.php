<?php
namespace Psalm\PluginManager;

use Psalm\Config;
use DomDocument;
use RuntimeException;

class ConfigFile
{
    const NS = 'https://getpsalm.org/schema/config';
    /** @var string */
    private $path;

    /** @var string */
    private $current_dir;

    /** @var string|null */
    private $psalm_header;

    /** @var int|null */
    private $psalm_tag_end_pos;

    /** @param null|string $explicit_path */
    public function __construct(string $current_dir, $explicit_path)
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

    /** @return void */
    public function removePlugin(string $plugin_class)
    {
        $config_xml = $this->readXml();
        /** @var \DomElement */
        $psalm_root = $config_xml->getElementsByTagName('psalm')[0];
        $plugins_elements = $psalm_root->getElementsByTagName('plugins');
        if (!$plugins_elements->length) {
            // no plugins, nothing to remove
            return;
        }

        /** @var \DomElement */
        $plugins_element = $plugins_elements->item(0);

        $plugin_elements = $plugins_element->getElementsByTagName('pluginClass');

        foreach ($plugin_elements as $plugin_element) {
            if ($plugin_element->getAttribute('class') === $plugin_class) {
                $plugins_element->removeChild($plugin_element);
                break;
            }
        }

        if (!$plugin_elements->length) {
            // avoid breaking old psalm binaries, whose schema did not allow empty plugins
            $psalm_root->removeChild($plugins_element);
        }

        $this->saveXml($config_xml);
    }

    /** @return void */
    public function addPlugin(string $plugin_class)
    {
        $config_xml = $this->readXml();
        /** @var \DomElement */
        $psalm_root = $config_xml->getElementsByTagName('psalm')->item(0);
        $plugins_elements = $psalm_root->getElementsByTagName('plugins');
        if (!$plugins_elements->length) {
            $plugins_element = $config_xml->createElement('plugins');
            $psalm_root->appendChild($plugins_element);
        } else {
            /** @var \DomElement */
            $plugins_element = $plugins_elements->item(0);
        }

        $plugin_class_element = $config_xml->createElement('pluginClass');
        $plugin_class_element->setAttribute('xmlns', self::NS);
        $plugin_class_element->setAttribute('class', $plugin_class);
        $plugins_element->appendChild($plugin_class_element);

        $this->saveXml($config_xml);
    }

    private function readXml(): DomDocument
    {
        $doc = new DomDocument();

        $file_contents = file_get_contents($this->path);

        if (($tag_start = strpos($file_contents, '<psalm')) !== false) {
            $tag_end = strpos($file_contents, '>', $tag_start + 1);

            if ($tag_end !== false) {
                $this->psalm_tag_end_pos = $tag_end;
                $this->psalm_header = substr($file_contents, 0, $tag_end);
            }
        }

        $doc->loadXml($file_contents);
        return $doc;
    }

    /** @return void */
    private function saveXml(DomDocument $config_xml)
    {
        $new_file_contents = $config_xml->saveXML($config_xml);

        if (($tag_start = strpos($new_file_contents, '<psalm')) !== false) {
            $tag_end = strpos($new_file_contents, '>', $tag_start + 1);

            if ($tag_end !== false
                && ($new_file_contents[$tag_end - 1] !== '/')
                && $this->psalm_tag_end_pos
                && $this->psalm_header
            ) {
                $new_file_contents = $this->psalm_header . substr($new_file_contents, $tag_end);
            }
        }

        file_put_contents($this->path, $new_file_contents);
    }
}
