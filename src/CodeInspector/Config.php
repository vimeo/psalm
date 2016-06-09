<?php

namespace CodeInspector;

use CodeInspector\Config\FileFilter;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SimpleXMLElement;

class Config
{
    protected static $_config;

    public $stopOnError = true;
    public $useDocblockReturnType = false;

    protected $errorHandlers;

    protected $inspectFiles;

    protected $fileExtensions = ['php'];

    private function __construct()
    {
        self::$_config = $this;
    }

    public static function loadFromXML($file_contents)
    {
        $config = new self();

        $config_xml = new SimpleXMLElement($file_contents);

        if (isset($config_xml['stopOnError'])) {
            $config->stopOnError = (bool) $config_xml['stopOnError'];
        }

        if (isset($config_xml['useDocblockReturnType'])) {
            $config->stopOnError = (bool) $config_xml['useDocblockReturnType'];
        }

        if (isset($config_xml->inspectFiles)) {
            $config->inspectFiles = new FileFilter($config_xml->inspectFiles);
        }

        if (isset($config_xml->fileExtensions)) {
            $config->fileExtensions = [];
            if ($config_xml->fileExtensions->extension instanceof SimpleXMLElement) {
                $config->fileExtensions[] = preg_replace('/^.?/', '', $config_xml->fileExtensions->extension);
            }
            else {
                foreach ($config_xml->fileExtensions->extension as $extension) {
                    $config->fileExtensions[] = preg_replace('/^.?/', '', $extension);
                }
            }
            $config->inspectFiles = new FileFilter($config_xml->inspectFiles);
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

    public function excludeIssueInFile($issue_type, $file_name)
    {

    }

    public function doesInheritVariables($file_name)
    {
        return false;
    }

    public function getFilesToCheck()
    {
        $files = $this->inspectFiles->getIncludeFiles();

        foreach ($this->inspectFiles->getIncludeFolders() as $folder) {
            /** @var RecursiveDirectoryIterator */
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder));
            $iterator->rewind();

            while ($iterator->valid()) {
                if (!$iterator->isDot()) {
                    if (in_array($iterator->getExtension(), $this->extensions)) {
                        $files[] = $iterator->getRealPath();
                    }
                }

                $iterator->next();
            }
        }

        return $files;
    }
}
