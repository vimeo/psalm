<?php

declare(strict_types=1);

namespace Psalm\Internal\Cache;

use FilesystemIterator;
use Psalm\Config;
use Psalm\Internal\Cache;
use Psalm\Internal\Composer;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use Symfony\Component\Filesystem\Path;

use function array_any;
use function array_filter;
use function array_map;
use function array_unique;
use function array_values;
use function bin2hex;
use function dirname;
use function error_clear_last;
use function error_get_last;
use function explode;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function get_loaded_extensions;
use function glob;
use function hash;
use function hash_file;
use function hash_final;
use function hash_init;
use function hash_update;
use function implode;
use function ini_get;
use function is_array;
use function is_dir;
use function is_file;
use function is_string;
use function json_decode;
use function json_encode;
use function ksort;
use function mkdir;
use function random_bytes;
use function realpath;
use function rename;
use function sort;
use function str_starts_with;
use function strtolower;
use function trim;

use const DIRECTORY_SEPARATOR;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const PHP_MAJOR_VERSION;
use const PHP_MINOR_VERSION;
use const SORT_STRING;

/**
 * What the contents of a cache depend on. A cache made under one identity is never read under another: every cache
 * directory is in the directory of its identity.
 *
 * @internal
 */
final class AnalysisIdentity
{
    /** The file that describes the components of an identity, in its directory */
    public const FILE_NAME = 'identity.json';

    /** Files of Psalm that the format of cached storages and statements depends on, relative to src/Psalm */
    public const ENGINE_FILES = [
        'Storage/FileStorage.php',
        'Storage/FunctionLikeStorage.php',
        'Storage/ClassLikeStorage.php',
        'Storage/MethodStorage.php',
        'Storage/PropertyStorage.php',
        'Storage/PropertyHookStorage.php',
        'Storage/EnumCaseStorage.php',
        'Storage/FunctionLikeParameter.php',
        'Internal/Provider/StatementsProvider.php',
    ];

    /** Directories of dependencies inside a plugin directory, which are not the plugin's own code */
    private const SKIPPED_PLUGIN_DIRECTORIES = ['vendor' => true, 'node_modules' => true];

    /**
     * @param array{
     *     config: string,
     *     php: string,
     *     runtime: string,
     *     engine: string,
     *     deps: string,
     *     plugins: string,
     * } $components
     */
    private function __construct(
        public readonly array $components,
    ) {
    }

    public static function of(Config $config): self
    {
        $extensions = get_loaded_extensions();
        sort($extensions, SORT_STRING);

        $serializer = $config->getCacheSerializer();
        $engine = hash_init('xxh128');
        hash_update($engine, PSALM_VERSION . "\0" . PHP_PARSER_VERSION . "\0" . Cache::LAYOUT_VERSION . "\0");
        hash_update($engine, $serializer->serialize($serializer) . "\0");
        foreach (self::ENGINE_FILES as $file) {
            hash_update($engine, $file . "\0" . self::hashFile(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . $file));
        }

        $plugins = hash_init('xxh128');
        foreach (self::getPluginSources($config) as $file) {
            hash_update($plugins, $file . "\0" . self::hashFile($file) . "\0");
        }

        $lock_file = Composer::getLockFilePath($config->base_dir);

        return new self([
            'config' => ConfigFingerprint::of($config),
            'php' => (string) $config->getAnalysisPhpVersion()[0],
            'runtime' => PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . ' ' . hash('xxh128', implode("\0", [
                implode(',', $extensions),
                self::iniList('disable_functions'),
                self::iniList('disable_classes'),
            ])),
            'engine' => hash_final($engine),
            'deps' => is_file($lock_file) ? self::hashFile($lock_file) : 'none',
            'plugins' => hash_final($plugins),
        ]);
    }

    /**
     * The directories of $cache_directory that hold caches of identities with the engine of $this, so that the
     * current Psalm can read their records
     *
     * @return list<string>
     */
    public function getDirectoriesWithTheSameEngine(string $cache_directory): array
    {
        $directories = [];
        $files = glob($cache_directory . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . self::FILE_NAME);
        foreach ($files === false ? [] : $files as $file) {
            $contents = is_file($file) ? file_get_contents($file) : false;
            /** @var mixed $identity */
            $identity = $contents !== false ? json_decode($contents, true) : null;
            if (is_array($identity)
                && is_array($identity['components'] ?? null)
                && ($identity['components']['engine'] ?? null) === $this->components['engine']
            ) {
                $directories[] = dirname($file);
            }
        }
        sort($directories);

        return $directories;
    }

    public function getFingerprint(): string
    {
        $context = hash_init('xxh128');
        foreach ($this->components as $name => $value) {
            hash_update($context, $name . "\0" . $value . "\0");
        }

        return hash_final($context);
    }

    /**
     * Creates the directory of this identity in $cache_directory, with the file that describes it
     *
     * @return string the directory
     */
    public function createDirectory(string $cache_directory): string
    {
        $fingerprint = $this->getFingerprint();
        $directory = $cache_directory . DIRECTORY_SEPARATOR . $fingerprint;
        $file = $directory . DIRECTORY_SEPARATOR . self::FILE_NAME;
        if (file_exists($file)) {
            return $directory;
        }

        error_clear_last();
        // Another process may create the directory between the check and mkdir() (#4483)
        if (!is_dir($directory) && !@mkdir($directory, 0777, true) && !is_dir($directory)) {
            $reason = error_get_last()['message'] ?? null;
            throw new RuntimeException(
                'Failed to create ' . $directory . ' cache directory' . ($reason !== null ? ": $reason" : ''),
            );
        }

        // written whole, then renamed, so that a reader sees no part of it
        $temporary = $file . '.' . bin2hex(random_bytes(8)) . '.tmp';
        file_put_contents($temporary, json_encode(
            ['fingerprint' => $fingerprint, 'components' => $this->components],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        ) . "\n");
        rename($temporary, $file);

        return $directory;
    }

    private static function hashFile(string $path): string
    {
        return is_file($path) ? (string) hash_file('xxh128', $path) : 'missing';
    }

    /** A comma-separated ini setting, normalised: the functions or classes it names, sorted */
    private static function iniList(string $name): string
    {
        $names = array_filter(array_map(
            static fn(string $name): string => strtolower(trim($name)),
            explode(',', (string) ini_get($name)),
        ), static fn(string $name): bool => $name !== '');
        $names = array_unique($names);
        sort($names, SORT_STRING);

        return implode(',', $names);
    }

    /**
     * The files of the plugins: each entry file, and for a plugin in a directory of its own inside the project, every
     * PHP file of that directory too, as its hooks may be in other files (dependency and hidden directories aside).
     * A plugin directory that is the project root, is inside the vendor directory, or is or contains a directory of
     * project files counts with its entry file only.
     *
     * @return list<string>
     */
    private static function getPluginSources(Config $config): array
    {
        $base_dir = realpath($config->base_dir);
        $vendor_dir = realpath(Path::makeAbsolute(self::getVendorDirectory($config->base_dir), $config->base_dir));
        $project_dirs = [];
        foreach ($config->getProjectDirectories() as $project_dir) {
            $project_dir = realpath($project_dir);
            if ($project_dir !== false) {
                $project_dirs[] = $project_dir;
            }
        }

        $sources = [];
        foreach ($config->getPluginFiles() as $file) {
            $directory = realpath(dirname($file));
            $own_directory = $base_dir !== false
                && $directory !== false
                && Path::isBasePath($base_dir, $directory)
                && $directory !== $base_dir
                && ($vendor_dir === false || !Path::isBasePath($vendor_dir, $directory))
                && !array_any(
                    $project_dirs,
                    static fn(string $project_dir): bool => Path::isBasePath($directory, $project_dir),
                );

            $sources[$file] = $file;
            if (!$own_directory) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveCallbackFilterIterator(
                    new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
                    static fn(SplFileInfo|string $entry): bool => !$entry instanceof SplFileInfo
                        || !$entry->isDir()
                        || !isset(self::SKIPPED_PLUGIN_DIRECTORIES[$entry->getFilename()])
                            && !str_starts_with($entry->getFilename(), '.'),
                ),
                RecursiveIteratorIterator::LEAVES_ONLY,
                // a directory the process cannot read is left out, not a reason to stop
                RecursiveIteratorIterator::CATCH_GET_CHILD,
            );
            /** @var SplFileInfo $source */
            foreach ($iterator as $source) {
                if ($source->isFile() && $source->getExtension() === 'php') {
                    $sources[$source->getPathname()] = $source->getPathname();
                }
            }
        }
        ksort($sources, SORT_STRING);

        return array_values($sources);
    }

    private static function getVendorDirectory(string $base_dir): string
    {
        // a missing file is not an error: error handlers see a warning even when it is suppressed
        $composer_json_path = Composer::getJsonFilePath($base_dir);
        $composer_json = is_file($composer_json_path) ? file_get_contents($composer_json_path) : false;
        /** @var mixed $composer */
        $composer = $composer_json !== false ? json_decode($composer_json, true) : null;
        /** @var mixed $vendor_dir */
        $vendor_dir = is_array($composer) && is_array($composer['config'] ?? null)
            ? ($composer['config']['vendor-dir'] ?? null)
            : null;

        return is_string($vendor_dir) ? $vendor_dir : 'vendor';
    }
}
