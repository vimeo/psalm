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
    protected $fq_classlike_names = [];

    /**
     * @var array<string>
     */
    protected $method_ids = [];

    /**
     * @var array<string>
     */
    protected $property_ids = [];

    /**
     * @var array<string>
     */
    protected $files_lowercase = [];

    /**
     * @var bool
     */
    protected $inclusive;

    /**
     * @var array<string, bool>
     */
    protected $ignore_type_stats = [];

    /**
     * @var array<string, bool>
     */
    protected $declare_strict_types = [];

    /**
     * @param  bool             $inclusive
     *
     * @psalm-suppress DocblockTypeContradiction
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
     * @param  string           $base_dir
     * @param  bool             $inclusive
     *
     * @return static
     */
    public static function loadFromXMLElement(
        SimpleXMLElement $e,
        $base_dir,
        $inclusive
    ) {
        $filter = new static($inclusive);

        if ($e->directory) {
            /** @var \SimpleXMLElement $directory */
            foreach ($e->directory as $directory) {
                $directory_path = (string) $directory['name'];
                $ignore_type_stats = strtolower(
                    isset($directory['ignoreTypeStats']) ? (string) $directory['ignoreTypeStats'] : ''
                ) === 'true';
                $declare_strict_types = strtolower(
                    isset($directory['useStrictTypes']) ? (string) $directory['useStrictTypes'] : ''
                ) === 'true';

                if ($directory_path[0] === '/' && DIRECTORY_SEPARATOR === '/') {
                    $prospective_directory_path = $directory_path;
                } else {
                    $prospective_directory_path = $base_dir . DIRECTORY_SEPARATOR . $directory_path;
                }

                if (strpos($prospective_directory_path, '*') !== false) {
                    $globs = array_map(
                        'realpath',
                        array_filter(
                            glob($prospective_directory_path),
                            'is_dir'
                        )
                    );

                    if (empty($globs)) {
                        echo 'Could not resolve config path to ' . $base_dir . DIRECTORY_SEPARATOR .
                            (string)$directory['name'] . PHP_EOL;
                        exit(1);
                    }

                    foreach ($globs as $glob_index => $directory_path) {
                        if (!$directory_path) {
                            echo 'Could not resolve config path to ' . $base_dir . DIRECTORY_SEPARATOR .
                                (string)$directory['name'] . ':' . $glob_index . PHP_EOL;
                            exit(1);
                        }

                        if ($ignore_type_stats && $filter instanceof ProjectFileFilter) {
                            $filter->ignore_type_stats[$directory_path] = true;
                        }

                        if ($declare_strict_types && $filter instanceof ProjectFileFilter) {
                            $filter->declare_strict_types[$directory_path] = true;
                        }

                        $filter->addDirectory($directory_path);
                    }
                    continue;
                }

                $directory_path = realpath($prospective_directory_path);

                if (!$directory_path) {
                    echo 'Could not resolve config path to ' . $base_dir . DIRECTORY_SEPARATOR .
                        (string)$directory['name'] . PHP_EOL;
                    exit(1);
                }

                if ($ignore_type_stats && $filter instanceof ProjectFileFilter) {
                    $filter->ignore_type_stats[$directory_path] = true;
                }

                if ($declare_strict_types && $filter instanceof ProjectFileFilter) {
                    $filter->declare_strict_types[$directory_path] = true;
                }

                $filter->addDirectory($directory_path);
            }
        }

        if ($e->file) {
            /** @var \SimpleXMLElement $file */
            foreach ($e->file as $file) {
                $file_path = (string) $file['name'];

                if ($file_path[0] === '/' && DIRECTORY_SEPARATOR === '/') {
                    $prospective_file_path = $file_path;
                } else {
                    $prospective_file_path = $base_dir . DIRECTORY_SEPARATOR . $file_path;
                }

                if (strpos($prospective_file_path, '*') !== false) {
                    $globs = array_map(
                        'realpath',
                        array_filter(
                            glob($prospective_file_path),
                            'file_exists'
                        )
                    );

                    if (empty($globs)) {
                        echo 'Could not resolve config path to ' . $base_dir . DIRECTORY_SEPARATOR .
                            (string)$file['name'] . PHP_EOL;
                        exit(1);
                    }

                    foreach ($globs as $glob_index => $file_path) {
                        if (!$file_path) {
                            echo 'Could not resolve config path to ' . $base_dir . DIRECTORY_SEPARATOR .
                                (string)$file['name'] . ':' . $glob_index . PHP_EOL;
                            exit(1);
                        }
                        $filter->addFile($file_path);
                    }
                    continue;
                }

                $file_path = realpath($prospective_file_path);

                if (!$file_path) {
                    echo 'Could not resolve config path to ' . $base_dir . DIRECTORY_SEPARATOR .
                        (string)$file['name'] . PHP_EOL;
                    exit(1);
                }

                $filter->addFile($file_path);
            }
        }

        if ($e->referencedClass) {
            /** @var \SimpleXMLElement $referenced_class */
            foreach ($e->referencedClass as $referenced_class) {
                $filter->fq_classlike_names[] = strtolower((string)$referenced_class['name']);
            }
        }

        if ($e->referencedMethod) {
            /** @var \SimpleXMLElement $referenced_method */
            foreach ($e->referencedMethod as $referenced_method) {
                $method_id = (string)$referenced_method['name'];

                if (!preg_match('/^[^:]+::[^:]+$/', $method_id)) {
                    echo 'Invalid referencedMethod ' . $method_id . PHP_EOL;
                    exit(1);
                }

                $filter->method_ids[] = strtolower($method_id);
            }
        }

        if ($e->referencedFunction) {
            /** @var \SimpleXMLElement $referenced_function */
            foreach ($e->referencedFunction as $referenced_function) {
                $filter->method_ids[] = strtolower((string)$referenced_function['name']);
            }
        }

        if ($e->referencedProperty) {
            /** @var \SimpleXMLElement $referenced_property */
            foreach ($e->referencedProperty as $referenced_property) {
                $filter->property_ids[] = strtolower((string)$referenced_property['name']);
            }
        }

        return $filter;
    }

    /**
     * @param  string $str
     *
     * @return string
     */
    protected static function slashify($str)
    {
        return preg_replace('/\/?$/', DIRECTORY_SEPARATOR, $str);
    }

    /**
     * @param  string  $file_name
     * @param  bool $case_sensitive
     *
     * @return bool
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
                if (in_array($file_name, $this->files, true)) {
                    return true;
                }
            } else {
                if (in_array(strtolower($file_name), $this->files_lowercase, true)) {
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
            if (in_array($file_name, $this->files, true)) {
                return false;
            }
        } else {
            if (in_array(strtolower($file_name), $this->files_lowercase, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  string  $fq_classlike_name
     *
     * @return bool
     */
    public function allowsClass($fq_classlike_name)
    {
        return in_array(strtolower($fq_classlike_name), $this->fq_classlike_names);
    }

    /**
     * @param  string  $method_id
     *
     * @return bool
     */
    public function allowsMethod($method_id)
    {
        if (preg_match('/^[^:]+::[^:]+$/', $method_id)) {
            $method_stub = '*::' . explode('::', $method_id)[1];

            if (in_array($method_stub, $this->method_ids)) {
                return true;
            }
        }
        return in_array($method_id, $this->method_ids);
    }

    /**
     * @param  string  $property_id
     *
     * @return bool
     */
    public function allowsProperty($property_id)
    {
        return in_array(strtolower($property_id), $this->property_ids);
    }

    /**
     * @return array<string>
     */
    public function getDirectories()
    {
        return $this->directories;
    }

    /**
     * @return array<string>
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * @param   string $file_name
     *
     * @return  void
     */
    public function addFile($file_name)
    {
        $this->files[] = $file_name;
        $this->files_lowercase[] = strtolower($file_name);
    }

    /**
     * @param string $dir_name
     *
     * @return void
     */
    public function addDirectory($dir_name)
    {
        $this->directories[] = self::slashify($dir_name);
    }
}
