<?php
namespace Psalm\Config;

use SimpleXMLElement;

class FileFilter
{
    /**
     * @var array<string>
     */
    protected $directories = [];

    /**
     * @var array<string>
     */
    protected $files = [];

    /**
     * @var array<string>
     */
    protected $files_lowercase = [];

    /**
     * @var array<string>
     */
    protected $patterns = [];

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
    public static function loadFromXMLElement(SimpleXMLElement $e, $inclusive)
    {
        $filter = new self($inclusive);

        if ($e->directory) {
            /** @var \SimpleXMLElement $directory */
            foreach ($e->directory as $directory) {
                $filter->addDirectory((string)$directory['name']);
            }
        }

        if ($e->file) {
            /** @var \SimpleXMLElement $file */
            foreach ($e->file as $file) {
                $filter->addFile((string)$file['name']);
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
            foreach ($this->directories as $include_dir) {
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
                if (in_array($file_name, $this->files)) {
                    return true;
                }
            } else {
                if (in_array(strtolower($file_name), $this->files_lowercase)) {
                    return true;
                }
            }

            return false;
        }

        // exclusive
        foreach ($this->directories as $exclude_dir) {
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
            if (in_array($file_name, $this->files)) {
                return false;
            }
        } else {
            if (in_array(strtolower($file_name), $this->files_lowercase)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string>
     */
    public function getDirectories()
    {
        return $this->directories;
    }

    /**
     * @return array
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * @param   string $file_name
     * @return  void
     */
    public function addFile($file_name)
    {
        $this->files[] = $file_name;
        $this->files_lowercase[] = strtolower($file_name);
    }

    /**
     * @param string $dir_name
     * @return void
     */
    public function addDirectory($dir_name)
    {
        $this->directories[] = self::slashify($dir_name);
    }
}
