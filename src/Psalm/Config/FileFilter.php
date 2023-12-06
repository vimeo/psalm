<?php

namespace Psalm\Config;

use FilesystemIterator;
use Psalm\Exception\ConfigException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SimpleXMLElement;
use Symfony\Component\Filesystem\Path;

use function array_filter;
use function array_map;
use function array_merge;
use function array_shift;
use function count;
use function explode;
use function glob;
use function in_array;
use function is_dir;
use function is_iterable;
use function is_string;
use function preg_match;
use function preg_replace;
use function preg_split;
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
     * @var array<non-empty-string>
     */
    protected $fq_classlike_patterns = [];

    /**
     * @var array<non-empty-string>
     */
    protected $method_ids = [];

    /**
     * @var array<string>
     */
    protected $property_ids = [];

    /**
     * @var array<string>
     */
    protected $class_constant_ids = [];

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
                $resolve_symlinks = (bool) ($directory['resolveSymlinks'] ?? false);
                $declare_strict_types = (bool) ($directory['useStrictTypes'] ?? false);

                if (Path::isAbsolute($directory_path)) {
                    /** @var non-empty-string */
                    $prospective_directory_path = $directory_path;
                } else {
                    $prospective_directory_path = $base_dir . DIRECTORY_SEPARATOR . $directory_path;
                }

                if (strpos($prospective_directory_path, '*') !== false) {
                    // Strip meaningless trailing recursive wildcard like "path/**/" or "path/**"
                    $prospective_directory_path = preg_replace('#(\/\*\*)+\/?$#', '/', $prospective_directory_path);
                    // Split by /**/, allow duplicated wildcards like "path/**/**/path" and any leading dir separator.
                    /** @var non-empty-list<non-empty-string> $path_parts */
                    $path_parts = preg_split('#(\/|\\\)(\*\*\/)+#', $prospective_directory_path);
                    $globs = self::recursiveGlob($path_parts, true);

                    if (empty($globs)) {
                        if ($allow_missing_files) {
                            continue;
                        }

                        throw new ConfigException(
                            'Could not resolve config path to ' . $base_dir
                            . DIRECTORY_SEPARATOR . $directory_path,
                        );
                    }

                    foreach ($globs as $glob_index => $glob_directory_path) {
                        if (!$glob_directory_path) {
                            if ($allow_missing_files) {
                                continue;
                            }

                            throw new ConfigException(
                                'Could not resolve config path to ' . $base_dir
                                . DIRECTORY_SEPARATOR . $directory_path . ':' . $glob_index,
                            );
                        }

                        if ($ignore_type_stats && $filter instanceof ProjectFileFilter) {
                            $filter->ignore_type_stats[$glob_directory_path] = true;
                        }

                        if ($declare_strict_types && $filter instanceof ProjectFileFilter) {
                            $filter->declare_strict_types[$glob_directory_path] = true;
                        }

                        $filter->addDirectory($glob_directory_path);
                    }
                    continue;
                }

                $directory_path = realpath($prospective_directory_path);

                if (!$directory_path) {
                    if ($allow_missing_files) {
                        continue;
                    }

                    throw new ConfigException(
                        'Could not resolve config path to ' . $prospective_directory_path,
                    );
                }

                if (!is_dir($directory_path)) {
                    throw new ConfigException(
                        $base_dir . DIRECTORY_SEPARATOR . $directory_path
                        . ' is not a directory',
                    );
                }

                if ($resolve_symlinks) {
                    /** @var RecursiveDirectoryIterator */
                    $iterator = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($directory_path, FilesystemIterator::SKIP_DOTS),
                    );
                    $iterator->rewind();

                    while ($iterator->valid()) {
                        if ($iterator->isLink()) {
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

                if (Path::isAbsolute($file_path)) {
                    /** @var non-empty-string */
                    $prospective_file_path = $file_path;
                } else {
                    $prospective_file_path = $base_dir . DIRECTORY_SEPARATOR . $file_path;
                }

                if (strpos($prospective_file_path, '*') !== false) {
                    // Split by /**/, allow duplicated wildcards like "path/**/**/path" and any leading dir separator.
                    /** @var non-empty-list<non-empty-string> $path_parts */
                    $path_parts = preg_split('#(\/|\\\)(\*\*\/)+#', $prospective_file_path);
                    $globs = self::recursiveGlob($path_parts, false);

                    if (empty($globs)) {
                        if ($allow_missing_files) {
                            continue;
                        }

                        throw new ConfigException(
                            'Could not resolve config path to ' . $base_dir . DIRECTORY_SEPARATOR .
                            $file_path,
                        );
                    }

                    foreach ($globs as $glob_index => $glob_file_path) {
                        if (!$glob_file_path) {
                            if ($allow_missing_files) {
                                continue;
                            }

                            throw new ConfigException(
                                'Could not resolve config path to ' . $base_dir . DIRECTORY_SEPARATOR .
                                $file_path . ':' . $glob_index,
                            );
                        }
                        $filter->addFile($glob_file_path);
                    }
                    continue;
                }

                $file_path = realpath($prospective_file_path);

                if (!$file_path && !$allow_missing_files) {
                    throw new ConfigException(
                        'Could not resolve config path to ' . $prospective_file_path,
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
                $method_id = $referenced_method['name'] ?? '';
                if (!is_string($method_id)
                    || (!preg_match('/^[^:]+::[^:]+$/', $method_id) && !static::isRegularExpression($method_id))) {
                    throw new ConfigException(
                        'Invalid referencedMethod ' . ((string) $method_id),
                    );
                }

                if ($method_id === '') {
                    continue;
                }

                $filter->method_ids[] = strtolower($method_id);
            }
        }

        if (isset($config['referencedFunction']) && is_iterable($config['referencedFunction'])) {
            /** @var array $referenced_function */
            foreach ($config['referencedFunction'] as $referenced_function) {
                $function_id = $referenced_function['name'] ?? '';
                if (!is_string($function_id)
                    || (!preg_match('/^[a-zA-Z_\x80-\xff](?:[\\\\]?[a-zA-Z0-9_\x80-\xff]+)*$/', $function_id)
                        && !preg_match('/^[^:]+::[^:]+$/', $function_id) // methods are also allowed
                        && !static::isRegularExpression($function_id))) {
                    throw new ConfigException(
                        'Invalid referencedFunction ' . ((string) $function_id),
                    );
                }

                if ($function_id === '') {
                    continue;
                }

                $filter->method_ids[] = strtolower($function_id);
            }
        }

        if (isset($config['referencedProperty']) && is_iterable($config['referencedProperty'])) {
            /** @var array $referenced_property */
            foreach ($config['referencedProperty'] as $referenced_property) {
                $filter->property_ids[] = strtolower((string) ($referenced_property['name'] ?? ''));
            }
        }

        if (isset($config['referencedConstant']) && is_iterable($config['referencedConstant'])) {
            /** @var array $referenced_constant */
            foreach ($config['referencedConstant'] as $referenced_constant) {
                $filter->class_constant_ids[] = strtolower((string) ($referenced_constant['name'] ?? ''));
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
            foreach ($e->directory as $directory) {
                $config['directory'][] = [
                    'name' => (string) $directory['name'],
                    'ignoreTypeStats' => strtolower((string) ($directory['ignoreTypeStats'] ?? '')) === 'true',
                    'resolveSymlinks' => strtolower((string) ($directory['resolveSymlinks'] ?? '')) === 'true',
                    'useStrictTypes' => strtolower((string) ($directory['useStrictTypes'] ?? '')) === 'true',
                ];
            }
        }

        if ($e->file) {
            $config['file'] = [];
            foreach ($e->file as $file) {
                $config['file'][]['name'] = (string) $file['name'];
            }
        }

        if ($e->referencedClass) {
            $config['referencedClass'] = [];
            foreach ($e->referencedClass as $referenced_class) {
                $config['referencedClass'][]['name'] = strtolower((string)$referenced_class['name']);
            }
        }

        if ($e->referencedMethod) {
            $config['referencedMethod'] = [];
            foreach ($e->referencedMethod as $referenced_method) {
                $config['referencedMethod'][]['name'] = (string)$referenced_method['name'];
            }
        }

        if ($e->referencedFunction) {
            $config['referencedFunction'] = [];
            foreach ($e->referencedFunction as $referenced_function) {
                $config['referencedFunction'][]['name'] = strtolower((string)$referenced_function['name']);
            }
        }

        if ($e->referencedProperty) {
            $config['referencedProperty'] = [];
            foreach ($e->referencedProperty as $referenced_property) {
                $config['referencedProperty'][]['name'] = strtolower((string)$referenced_property['name']);
            }
        }

        if ($e->referencedConstant) {
            $config['referencedConstant'] = [];
            foreach ($e->referencedConstant as $referenced_constant) {
                $config['referencedConstant'][]['name'] = strtolower((string)$referenced_constant['name']);
            }
        }

        if ($e->referencedVariable) {
            $config['referencedVariable'] = [];
            foreach ($e->referencedVariable as $referenced_variable) {
                $config['referencedVariable'][]['name'] = strtolower((string)$referenced_variable['name']);
            }
        }

        return self::loadFromArray($config, $base_dir, $inclusive);
    }

    /**
     * @psalm-assert-if-true non-empty-string $string
     */
    private static function isRegularExpression(string $string): bool
    {
        if ($string === '') {
            return false;
        }

        set_error_handler(
            static fn(): bool => true,
            E_WARNING,
        );
        $is_regexp = preg_match($string, '') !== false;
        restore_error_handler();

        return $is_regexp;
    }

    /**
     * @mutation-free
     * @param non-empty-list<non-empty-string> $parts
     * @return array<string|false>
     */
    private static function recursiveGlob(array $parts, bool $only_dir): array
    {
        if (count($parts) < 2) {
            if ($only_dir) {
                $list = glob($parts[0], GLOB_ONLYDIR | GLOB_NOSORT) ?: [];
            } else {
                $list = array_filter(
                    glob($parts[0], GLOB_NOSORT) ?: [],
                    'file_exists',
                );
            }

            return array_map('realpath', $list);
        }

        $first_dir = self::slashify($parts[0]);
        $paths = glob($first_dir . '*', GLOB_ONLYDIR | GLOB_NOSORT);
        $result = [];
        foreach ($paths as $path) {
            $parts[0] = $path;
            $result = array_merge($result, self::recursiveGlob($parts, $only_dir));
        }
        array_shift($parts);
        $parts[0] =  $first_dir . $parts[0];

        return array_merge($result, self::recursiveGlob($parts, $only_dir));
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

    public function allowsClassConstant(string $constant_id): bool
    {
        return in_array(strtolower($constant_id), $this->class_constant_ids, true);
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
