<?php

namespace Psalm\Config;

use SimpleXMLElement;

class FileFilter
{
    /** @var array<string> */
    protected $include_dirs = [];

    /** @var array<string> */
    protected $exclude_dirs = [];

    /** @var array<string> */
    protected $include_files = [];

    protected $include_files_lowercase = [];

    /** @var array<string> */
    protected $exclude_files = [];

    protected $exclude_files_lowercase = [];

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
                    $filter->include_files_lowercase[] = strtolower($file['name']);
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
                    $filter->exclude_files[] = (string)$file['name'];
                    $filter->exclude_files_lowercase[] = strtolower($file['name']);
                }
            }
        }

        return $filter;
    }

    protected static function slashify($str)
    {
        return preg_replace('/\/?$/', '/', $str);
    }

    public function allows($file_name, $case_sensitive = false)
    {
        if ($this->inclusive) {
            foreach ($this->include_dirs as $include_dir) {
                if ($case_sensitive) {
                    if (strpos($file_name, $include_dir) === 0) {
                        return true;
                    }
                }
                else {
                    if (stripos($file_name, $include_dir) === 0) {
                        return true;
                    }
                }

            }

            if ($case_sensitive) {
                if (in_array($file_name, $this->include_files)) {
                    return true;
                }
            }
            else {
                if (in_array(strtolower($file_name), $this->include_files_lowercase)) {
                    return true;
                }
            }

            return false;
        }

        // exclusive
        foreach ($this->exclude_dirs as $exclude_dir) {
            if ($case_sensitive) {
                if (strpos($file_name, $exclude_dir) === 0) {
                    return false;
                }
            }
            else {
                if (stripos($file_name, $exclude_dir) === 0) {
                    return false;
                }
            }
        }

        if ($case_sensitive) {
            if (in_array($file_name, $this->exclude_files)) {
                return false;
            }
        }
        else {
            if (in_array(strtolower($file_name), $this->exclude_files_lowercase)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string>
     */
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

    public function makeExclusive()
    {
        $this->inclusive = false;
    }

    public function addExcludeFile($file_name)
    {
        $this->exclude_files[] = $file_name;
    }
}
