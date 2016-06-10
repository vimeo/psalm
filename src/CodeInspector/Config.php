<?php

namespace CodeInspector;

use CodeInspector\Config\FileFilter;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SimpleXMLElement;

class Config
{
    protected static $_config;

    public $stop_on_error = true;
    public $use_docblock_return_type = false;

    protected $inspect_files;

    protected $base_dir;

    protected $file_extensions = ['php'];

    protected $issue_handlers = [];

    protected $mock_classes = [];

    private function __construct()
    {
        self::$_config = $this;
    }

    public static function loadFromXML($file_name)
    {
        $config = new self();

        $file_contents = file_get_contents($file_name);

        $config->base_dir = dirname($file_name) . '/';

        $config_xml = new SimpleXMLElement($file_contents);

        if (isset($config_xml['stopOnError'])) {
            $config->stop_on_error = $config_xml['stopOnError'] === 'true' || $config_xml['stopOnError'] === '1';
        }

        if (isset($config_xml['useDocblockReturnType'])) {
            $config->use_docblock_return_type = (bool) $config_xml['useDocblockReturnType'];
        }

        if (isset($config_xml->inspectFiles)) {
            $config->inspect_files = FileFilter::loadFromXML($config_xml->inspectFiles, true);
        }

        if (isset($config_xml->fileExtensions)) {
            $config->file_extensions = [];

            foreach ($config_xml->fileExtensions->extension as $extension) {
                $config->file_extensions[] = preg_replace('/^\.?/', '', $extension['name']);
            }
        }

        if (isset($config_xml->mockClasses) && isset($config_xml->mockClasses->class)) {
            foreach ($config_xml->mockClasses->class as $mock_class) {
                $config->mock_classes[] = $mock_class['name'];
            }
        }

        if (isset($config_xml->issueHandler)) {
            foreach ($config_xml->issueHandler->children() as $key => $issue_handler) {
                if (isset($issue_handler->excludeFiles)) {
                    $config->issue_handlers[$key] = FileFilter::loadFromXML($issue_handler->excludeFiles, false);
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

    public function getFilesToCheck()
    {
        $files = $this->inspect_files->getIncludeFiles();

        foreach ($this->inspect_files->getIncludeDirs() as $dir) {
            /** @var RecursiveDirectoryIterator */
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->base_dir . '/' . $dir));
            $iterator->rewind();

            while ($iterator->valid()) {
                if (!$iterator->isDot()) {
                    if (in_array($iterator->getExtension(), $this->file_extensions)) {
                        $files[] = $iterator->getRealPath();
                    }
                }

                $iterator->next();
            }
        }

        return $files;
    }

    public function getMockClasses()
    {
        return $this->mock_classes;
    }
}
