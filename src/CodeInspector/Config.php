<?php

namespace CodeInspector;

use CodeInspector\Config\FileFilter;
use SimpleXMLElement;

class Config
{
    protected static $_config;

    public $stop_on_error = true;
    public $use_docblock_return_type = false;

    protected $inspect_files;

    protected $base_dir;

    protected $file_extensions = ['php'];

    protected $filetype_handlers = [];

    protected $issue_handlers = [];

    protected $mock_classes = [];

    /**
     * CodeInspector plugins
     * @var array<Plugin>
     */
    protected $plugins = [];

    private function __construct()
    {
        self::$_config = $this;
    }

    public function loadFromXML($file_name)
    {
        $file_contents = file_get_contents($file_name);

        $this->base_dir = dirname($file_name) . '/';

        $config_xml = new SimpleXMLElement($file_contents);

        if (isset($config_xml['stopOnError'])) {
            $this->stop_on_error = $config_xml['stopOnError'] === 'true' || $config_xml['stopOnError'] === '1';
        }

        if (isset($config_xml['useDocblockReturnType'])) {
            $this->use_docblock_return_type = (bool) $config_xml['useDocblockReturnType'];
        }

        if (isset($config_xml->inspectFiles)) {
            $this->inspect_files = FileFilter::loadFromXML($config_xml->inspectFiles, true);
        }

        if (isset($config_xml->fileExtensions)) {
            $this->file_extensions = [];

            $this->loadFileExtensions($config_xml->fileExtensions->extension);
        }

        if (isset($config_xml->mockClasses) && isset($config_xml->mockClasses->class)) {
            foreach ($config_xml->mockClasses->class as $mock_class) {
                $this->mock_classes[] = $mock_class['name'];
            }
        }

        // this plugin loading system borrows heavily from etsy/phan
        if (isset($config_xml->plugins) && isset($config_xml->plugins->plugin)) {
            foreach ($config_xml->plugins->plugin as $plugin) {
                $plugin_file_name = $plugin['filename'];

                $path = $this->base_dir . $plugin_file_name;

                if (!file_exists($path)) {
                    throw new \InvalidArgumentException('Cannot find file ' . $path);
                }

                $loaded_plugin = require($path);

                if (!$loaded_plugin) {
                    throw new \InvalidArgumentException('Plugins must return an instance of that plugin at the end of the file - ' . $plugin_file_name . ' does not');
                }

                if (!($loaded_plugin instanceof Plugin)) {
                    throw new \InvalidArgumentException('Plugins must extend \CodeInspector\Plugin - ' . $plugin_file_name . ' does not');
                }

                $this->plugins[] = $loaded_plugin;
            }
        }

        if (isset($config_xml->issueHandler)) {
            foreach ($config_xml->issueHandler->children() as $key => $issue_handler) {
                if (isset($issue_handler->excludeFiles)) {
                    $this->issue_handlers[$key] = FileFilter::loadFromXML($issue_handler->excludeFiles, false);
                }
            }
        }
    }

    /**
     * @return $this
     */
    public static function getInstance()
    {
        if (self::$_config) {
            return self::$_config;
        }

        return new self();
    }

    protected function loadFileExtensions($extensions)
    {
        foreach ($extensions as $extension) {
            $extension_name = preg_replace('/^\.?/', '', $extension['name']);
            $this->file_extensions[] = $extension_name;

            if (isset($extension['filetypeHandler'])) {
                $path = $this->base_dir . $extension['filetypeHandler'];

                if (!file_exists($path)) {
                    throw new Exception\ConfigException('Error parsing config: cannot find file ' . $path);
                }

                $declared_classes = FileChecker::getDeclaredClassesInFile($path);

                if (count($declared_classes) !== 1) {
                    throw new \InvalidArgumentException('Filetype handlers must have exactly one class in the file - ' . $path . ' has ' . count($declared_classes));
                }

                require_once($path);

                if (!is_subclass_of($declared_classes[0], 'CodeInspector\\FileChecker')) {
                    throw new \InvalidArgumentException('Filetype handlers must extend \CodeInspector\FileChecker - ' . $path . ' does not');
                }

                $this->filetype_handlers[$extension_name] = $declared_classes[0];
            }
        }
    }

    public function shortenFileName($file_name)
    {
        return preg_replace('/^' . preg_quote($this->base_dir, '/') . '/', '', $file_name);
    }

    public function excludeIssueInFile($issue_type, $file_name)
    {
        $issue_type = array_pop(explode('\\', $issue_type));
        $file_name = $this->shortenFileName($file_name);

        if (!isset($this->issue_handlers[$issue_type])) {
            return false;
        }

        return !$this->issue_handlers[$issue_type]->allows($file_name);
    }

    public function doesInheritVariables($file_name)
    {
        return false;
    }

    public function getIncludeDirs()
    {
        return $this->inspect_files->getIncludeDirs();
    }

    public function getBaseDir()
    {
        return $this->base_dir;
    }

    public function getFileExtensions()
    {
        return $this->file_extensions;
    }

    public function getFiletypeHandlers()
    {
        return $this->filetype_handlers;
    }

    public function getMockClasses()
    {
        return $this->mock_classes;
    }

    public function getPlugins()
    {
        return $this->plugins;
    }
}
