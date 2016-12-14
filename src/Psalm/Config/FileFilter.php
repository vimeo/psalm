<?php
namespace Psalm\Config;

use SimpleXMLElement;

class FileFilter
{
    /**
     * @var array<string>
     */
    protected $include_dirs = [];

    /**
     * @var array<string>
     */
    protected $exclude_dirs = [];

    /**
     * @var array<string>
     */
    protected $include_files = [];

    /**
     * @var array<string>
     */
    protected $include_files_lowercase = [];

    /**
     * @var array<string>
     */
    protected $exclude_files = [];

    /**
     * @var array<string>
     */
    protected $exclude_files_lowercase = [];

    /**
     * @var array<string>
     */
    protected $include_patterns = [];

    /**
     * @var array<string>
     */
    protected $exclude_patterns = [];

    /**
     * @var bool
     */
    protected $inclusive;

    /**
     * @param  bool             $inclusive
     * @return self
     * @psalm-suppress FailedTypeResolution
     */
    public function __construct($inclusive)
    {
        if (!is_bool($inclusive)) {
            throw new \InvalidArgumentException('Filter arg must be bool');
        }

        $this->inclusive = $inclusive;
    }

    /**
     * @param  SimpleXMLElement $e
     * @param  bool             $inclusive
     * @return self
     */
    public static function loadFromXML(SimpleXMLElement $e, $inclusive)
    {
        $filter = new self($inclusive);

        if ($inclusive) {
            if ($e->directory) {
                /** @var \SimpleXMLElement $directory */
                foreach ($e->directory as $directory) {
                    $filter->addIncludeDirectory((string)$directory['name']);
                }
            }

            if ($e->file) {
                /** @var \SimpleXMLElement $file */
                foreach ($e->file as $file) {
                    $filter->addIncludeFile((string)$file['name']);
                }
            }
        } else {
            if ($e->directory) {
                /** @var \SimpleXMLElement $directory */
                foreach ($e->directory as $directory) {
                    $filter->addExcludeDirectory((string)$directory['name']);
                }
            }

            if ($e->file) {
                /** @var \SimpleXMLElement $file */
                foreach ($e->file as $file) {
                    $filter->addExcludeFile((string)$file['name']);
                }
            }
        }

        return $filter;
    }

    /**
     * @param  string $str
     * @return string
     */
    protected static function slashify($str)
    {
        return preg_replace('/\/?$/', '/', $str);
    }

    /**
     * @param  string  $file_name
     * @param  boolean $case_sensitive
     * @return boolean
     */
    public function allows($file_name, $case_sensitive = false)
    {
        if ($this->inclusive) {
            foreach ($this->include_dirs as $include_dir) {
                if ($case_sensitive) {
                    if (strpos($file_name, $include_dir) === 0) {
                        return true;
                    }
                } else {
                    if (stripos($file_name, $include_dir) === 0) {
                        return true;
                    }
                }
            }

            if ($case_sensitive) {
                if (in_array($file_name, $this->include_files)) {
                    return true;
                }
            } else {
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
            } else {
                if (stripos($file_name, $exclude_dir) === 0) {
                    return false;
                }
            }
        }

        if ($case_sensitive) {
            if (in_array($file_name, $this->exclude_files)) {
                return false;
            }
        } else {
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

    /**
     * @return array
     */
    public function getExcludeDirs()
    {
        return $this->exclude_dirs;
    }

    /**
     * @return array
     */
    public function getIncludeFiles()
    {
        return $this->include_files;
    }

    /**
     * @return array
     */
    public function getExcludeFiles()
    {
        return $this->exclude_files;
    }

    /**
     * @param   string $file_name
     * @return  void
     */
    public function addExcludeFile($file_name)
    {
        if ($this->inclusive !== false) {
            throw new \UnexpectedValueException('Cannot add exclude file when filter is not exclusive');
        }

        $this->exclude_files[] = $file_name;
        $this->exclude_files_lowercase[] = strtolower($file_name);
    }

    /**
     * @param   string $file_name
     * @return  void
     */
    public function addIncludeFile($file_name)
    {
        if ($this->inclusive !== true) {
            throw new \UnexpectedValueException('Cannot add include file when filter is not inclusive');
        }

        $this->include_files[] = $file_name;
        $this->include_files_lowercase[] = strtolower($file_name);
    }

    /**
     * @param string $dir_name
     * @return void
     */
    public function addExcludeDirectory($dir_name)
    {
        $this->exclude_dirs[] = self::slashify($dir_name);
    }

    /**
     * @param string $dir_name
     * @return void
     */
    public function addIncludeDirectory($dir_name)
    {
        $this->include_dirs[] = self::slashify($dir_name);
    }
}
