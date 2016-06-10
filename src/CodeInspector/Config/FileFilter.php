<?php

namespace CodeInspector\Config;

use SimpleXMLElement;

class FileFilter
{
    /** @var array<string> */
    protected $include_dirs = [];

    /** @var array<string> */
    protected $exclude_dirs = [];

    /** @var array<string> */
    protected $include_files = [];

    /** @var array<string> */
    protected $exclude_files = [];

    /** @var array<string> */
    protected $include_patterns = [];

    /** @var array<string> */
    protected $exclude_patterns = [];

    /** @var bool */
    protected $inclusive;

    public function __construct() {

    }

    public static function loadFromXML(SimpleXMLElement $e, $inclusive)
    {
        $filter = new self();

        if ($inclusive) {
            $filter->inclusive = true;

            if ($e->directory) {
                foreach ($e->directory as $directory) {
                    $filter->include_dirs[] = self::slashify($directory['name']);
                }
            }

            if ($e->file) {
                foreach ($e->file as $file) {
                    $filter->include_files[] = $file['name'];
                }
            }
        }
        else {
            if ($e->directory) {
                foreach ($e->directory as $directory) {
                    $filter->exclude_dirs[] = self::slashify($directory['name']);
                }
            }

            if ($e->file) {
                foreach ($e->file as $file) {
                    $filter->exclude_files[] = $file['name'];
                }
            }
        }

        return $filter;
    }

    protected static function slashify($str)
    {
        return preg_replace('/\/?$/', '/', $str);
    }

    public function allows($file_name)
    {
        if ($this->inclusive) {
            foreach ($this->include_dirs as $include_dir) {
                if (strpos($file_name, $include_dir) === 0) {
                    return true;
                }
            }

            if (in_array($file_name, $this->include_files)) {
                return true;
            }

            return false;
        }

        // exclusive
        foreach ($this->exclude_dirs as $exclude_dir) {
            if (strpos($file_name, $exclude_dir) === 0) {
                return false;
            }

            if (in_array($file_name, $this->exclude_files)) {
                return false;
            }
        }

        return true;
    }

    public function getIncludeDirs()
    {
        return $this->include_dirs;
    }

    public function getExcludeDirs()
    {
        return $this->exclude_dirs;
    }

    public function getIncludeFiles()
    {
        return $this->include_files;
    }

    public function getExcludeFiles()
    {
        return $this->exclude_files;
    }
}
