<?php

namespace Psalm\Config;

use Psalm\Exception\ConfigException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SimpleXMLElement;

use function array_filter;
use function array_map;
use function explode;
use function glob;
use function in_array;
use function is_dir;
use function is_iterable;
use function preg_match;
use function readlink;
use function realpath;
use function restore_error_handler;
use function rtrim;
use function set_error_handler;
use function str_replace;
use function stripos;
use function strpos;
use function strtolower;

use const DIRECTORY_SEPARATOR;
use const E_WARNING;
use const GLOB_NOSORT;
use const GLOB_ONLYDIR;

/**
 * @psalm-consistent-constructor
 */
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
    protected $fq_classlike_patterns = [];

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
    protected $var_names = [];

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

    public function __construct(bool $inclusive)
    {
        $this->inclusive = $inclusive;
    }

    /**
     * @return static
     */
    public static function loadFromArray(
        array $config,
        string $base_dir,
        bool $inclusive
    ) {
        $allow_missing_files = ($config['allowMissingFiles'] ?? false) === true;

        $filter = new static($inclusive);

        if (isset($config['directory']) && is_iterable($config['directory'])) {
            /** @var array $directory */
            foreach ($config['directory'] as $directory) {
                $directory_path = (string) ($directory['name'] ?? '');
                $ignore_type_stats = (bool) ($directory['ignoreTypeStats'] ?? false);
                $declare_strict_types = (bool) ($directory['useStrictTypes'] ?? false);

                if ($directory_path[0] === '/' && DIRECTORY_SEPARATOR === '/') {
                    $prospective_directory_path = $directory_path;
                } else {
                    $prospective_directory_path = $base_dir . DIRECTORY_SEPARATOR . $directory_path;
                }

                if (strpos($prospective_directory_path, '*') !== false) {
                    $globs = array_map(
                        'realpath',
                        glob($prospective_directory_path, GLOB_ONLYDIR)
                    );

                    if (empty($globs)) {
                        if ($allow_missing_files) {
                            continue;
                        }

                        throw new ConfigException(
                            'Could not resolve config path to ' . $base_dir
                            . DIRECTORY_SEPARATOR . $directory_path
                        );
                    }

                    foreach ($globs as $glob_index => $directory_path) {
                        if (!$directory_path) {
                            if ($allow_missing_files) {
                                continue;
                            }

                            throw new ConfigException(
                                'Could not resolve config path to ' . $base_dir
                                . DIRECTORY_SEPARATOR . $directory_path . ':' . $glob_index
                            );
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
                    if ($allow_missing_files) {
                        continue;
                    }

                    throw new ConfigException(
                        'Could not resolve config path to ' . $prospective_directory_path
                    );
                }

                if (!is_dir($directory_path)) {
                    throw new ConfigException(
                        $base_dir . DIRECTORY_SEPARATOR . $directory_path
                        . ' is not a directory'
                    );
                }

                /** @var RecursiveDirectoryIterator */
                $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory_path));
                $iterator->rewind();

                while ($iterator->valid()) {
                    if (!$iterator->isDot() && $iterator->isLink()) {
                        $linked_path = readlink($iterator->getPathname());

                        if (stripos($linked_path, $directory_path) !== 0) {
                            if ($ignore_type_stats && $filter instanceof ProjectFileFilter) {
                                $filter->ignore_type_stats[$directory_path] = true;
                            }

                            if ($declare_strict_types && $filter instanceof ProjectFileFilter) {
                                $filter->declare_strict_types[$directory_path] = true;
                            }

                            if (is_dir($linked_path)) {
                                $filter->addDirectory($linked_path);
                            }
                        }
                    }

                    $iterator->next();
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

        if (isset($config['file']) && is_iterable($config['file'])) {
            /** @var array $file */
            foreach ($config['file'] as $file) {
                $file_path = (string) ($file['name'] ?? '');

                if ($file_path[0] === '/' && DIRECTORY_SEPARATOR === '/') {
                    $prospective_file_path = $file_path;
                } else {
                    $prospective_file_path = $base_dir . DIRECTORY_SEPARATOR . $file_path;
                }

                if (strpos($prospective_file_path, '*') !== false) {
                    $globs = array_map(
                        'realpath',
                        array_filter(
                            glob($prospective_file_path, GLOB_NOSORT),
                            'file_exists'
                        )
                    );

                    if (empty($globs)) {
                        if ($allow_missing_files) {
                            continue;
                        }

                        throw new ConfigException(
                            'Could not resolve config path to ' . $base_dir . DIRECTORY_SEPARATOR .
                            $file_path
                        );
                    }

                    foreach ($globs as $glob_index => $file_path) {
                        if (!$file_path && !$allow_missing_files) {
                            throw new ConfigException(
                                'Could not resolve config path to ' . $base_dir . DIRECTORY_SEPARATOR .
                                $file_path . ':' . $glob_index
                            );
                        }
                        $filter->addFile($file_path);
                    }
                    continue;
                }

                $file_path = realpath($prospective_file_path);

                if (!$file_path && !$allow_missing_files) {
                    throw new ConfigException(
                        'Could not resolve config path to ' . $prospective_file_path
                    );
                }

                $filter->addFile($file_path);
            }
        }

        if (isset($config['referencedClass']) && is_iterable($config['referencedClass'])) {
            /** @var array $referenced_class */
            foreach ($config['referencedClass'] as $referenced_class) {
                $class_name = strtolower((string) ($referenced_class['name'] ?? ''));

                if (strpos($class_name, '*') !== false) {
                    $regex = '/' . str_replace('*', '.*', str_replace('\\', '\\\\', $class_name)) . '/i';
                    $filter->fq_classlike_patterns[] = $regex;
                } else {
                    $filter->fq_classlike_names[] = $class_name;
                }
            }
        }

        if (isset($config['referencedMethod']) && is_iterable($config['referencedMethod'])) {
            /** @var array $referenced_method */
            foreach ($config['referencedMethod'] as $referenced_method) {
                $method_id = (string) ($referenced_method['name'] ?? '');

                if (!preg_match('/^[^:]+::[^:]+$/', $method_id) && !static::isRegularExpression($method_id)) {
                    throw new ConfigException(
                        'Invalid referencedMethod ' . $method_id
                    );
                }

                $filter->method_ids[] = strtolower($method_id);
            }
        }

        if (isset($config['referencedFunction']) && is_iterable($config['referencedFunction'])) {
            /** @var array $referenced_function */
            foreach ($config['referencedFunction'] as $referenced_function) {
                $filter->method_ids[] = strtolower((string) ($referenced_function['name'] ?? ''));
            }
        }

        if (isset($config['referencedProperty']) && is_iterable($config['referencedProperty'])) {
            /** @var array $referenced_property */
            foreach ($config['referencedProperty'] as $referenced_property) {
                $filter->property_ids[] = strtolower((string) ($referenced_property['name'] ?? ''));
            }
        }

        if (isset($config['referencedVariable']) && is_iterable($config['referencedVariable'])) {
            /** @var array $referenced_variable */
            foreach ($config['referencedVariable'] as $referenced_variable) {
                $filter->var_names[] = strtolower((string) ($referenced_variable['name'] ?? ''));
            }
        }

        return $filter;
    }

    /**
     * @return static
     */
    public static function loadFromXMLElement(
        SimpleXMLElement $e,
        string $base_dir,
        bool $inclusive
    ) {
        $config = [];
        $config['allowMissingFiles'] = ((string) $e['allowMissingFiles']) === 'true';

        if ($e->directory) {
            $config['directory'] = [];
            /** @var SimpleXMLElement $directory */
            foreach ($e->directory as $directory) {
                $config['directory'][] = [
                    'name' => (string) $directory['name'],
                    'ignoreTypeStats' => strtolower((string) ($directory['ignoreTypeStats'] ?? '')) === 'true',
                    'useStrictTypes' => strtolower((string) ($directory['useStrictTypes'] ?? '')) === 'true',
                ];
            }
        }

        if ($e->file) {
            $config['file'] = [];
            /** @var SimpleXMLElement $file */
            foreach ($e->file as $file) {
                $config['file'][]['name'] = (string) $file['name'];
            }
        }

        if ($e->referencedClass) {
            $config['referencedClass'] = [];
            /** @var SimpleXMLElement $referenced_class */
            foreach ($e->referencedClass as $referenced_class) {
                $config['referencedClass'][]['name'] = strtolower((string)$referenced_class['name']);
            }
        }

        if ($e->referencedMethod) {
            $config['referencedMethod'] = [];
            /** @var SimpleXMLElement $referenced_method */
            foreach ($e->referencedMethod as $referenced_method) {
                $config['referencedMethod'][]['name'] = (string)$referenced_method['name'];
            }
        }

        if ($e->referencedFunction) {
            $config['referencedFunction'] = [];
            /** @var SimpleXMLElement $referenced_function */
            foreach ($e->referencedFunction as $referenced_function) {
                $config['referencedFunction'][]['name'] = strtolower((string)$referenced_function['name']);
            }
        }

        if ($e->referencedProperty) {
            $config['referencedProperty'] = [];
            /** @var SimpleXMLElement $referenced_property */
            foreach ($e->referencedProperty as $referenced_property) {
                $config['referencedProperty'][]['name'] = strtolower((string)$referenced_property['name']);
            }
        }

        if ($e->referencedVariable) {
            $config['referencedVariable'] = [];

            /** @var SimpleXMLElement $referenced_variable */
            foreach ($e->referencedVariable as $referenced_variable) {
                $config['referencedVariable'][]['name'] = strtolower((string)$referenced_variable['name']);
            }
        }

        return self::loadFromArray($config, $base_dir, $inclusive);
    }

    private static function isRegularExpression(string $string): bool
    {
        set_error_handler(
            function (): bool {
                return false;
            },
            E_WARNING
        );
        $is_regexp = preg_match($string, '') !== false;
        restore_error_handler();

        return $is_regexp;
    }

    /**
     * @psalm-pure
     */
    protected static function slashify(string $str): string
    {
        return rtrim($str, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    public function allows(string $file_name, bool $case_sensitive = false): bool
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

    public function allowsClass(string $fq_classlike_name): bool
    {
        if ($this->fq_classlike_patterns) {
            foreach ($this->fq_classlike_patterns as $pattern) {
                if (preg_match($pattern, $fq_classlike_name)) {
                    return true;
                }
            }
        }

        return in_array(strtolower($fq_classlike_name), $this->fq_classlike_names, true);
    }

    public function allowsMethod(string $method_id): bool
    {
        if (!$this->method_ids) {
            return false;
        }

        if (preg_match('/^[^:]+::[^:]+$/', $method_id)) {
            $method_stub = '*::' . explode('::', $method_id)[1];

            foreach ($this->method_ids as $config_method_id) {
                if ($config_method_id === $method_id) {
                    return true;
                }

                if ($config_method_id === $method_stub) {
                    return true;
                }

                if ($config_method_id[0] === '/' && preg_match($config_method_id, $method_id)) {
                    return true;
                }
            }

            return false;
        }

        return in_array($method_id, $this->method_ids, true);
    }

    public function allowsProperty(string $property_id): bool
    {
        return in_array(strtolower($property_id), $this->property_ids, true);
    }

    public function allowsVariable(string $var_name): bool
    {
        return in_array(strtolower($var_name), $this->var_names, true);
    }

    /**
     * @return array<string>
     */
    public function getDirectories(): array
    {
        return $this->directories;
    }

    /**
     * @return array<string>
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    public function addFile(string $file_name): void
    {
        $this->files[] = $file_name;
        $this->files_lowercase[] = strtolower($file_name);
    }

    public function addDirectory(string $dir_name): void
    {
        $this->directories[] = self::slashify($dir_name);
    }
}
