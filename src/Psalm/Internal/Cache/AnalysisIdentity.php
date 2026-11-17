<?php

declare(strict_types=1);

namespace Psalm\Internal\Cache;

use Psalm\Config;
use Psalm\Internal\Composer;
use RuntimeException;

use function bin2hex;
use function dirname;
use function error_clear_last;
use function error_get_last;
use function file_exists;
use function file_put_contents;
use function get_loaded_extensions;
use function hash;
use function hash_file;
use function hash_final;
use function hash_init;
use function hash_update;
use function implode;
use function is_dir;
use function json_encode;
use function mkdir;
use function random_bytes;
use function rename;
use function sort;

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
    private const ENGINE_FILES = [
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
        [$php_version_id, $php_version_source] = $config->getAnalysisPhpVersion();

        $extensions = get_loaded_extensions();
        sort($extensions, SORT_STRING);

        $engine = hash_init('xxh128');
        hash_update($engine, PSALM_VERSION . "\0" . PHP_PARSER_VERSION . "\0");
        foreach (self::ENGINE_FILES as $file) {
            hash_update($engine, $file . "\0" . self::hashFile(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . $file));
        }

        $plugins = hash_init('xxh128');
        foreach ($config->getPluginFiles() as $file) {
            hash_update($plugins, $file . "\0" . self::hashFile($file) . "\0");
        }

        return new self([
            'config' => ConfigFingerprint::of($config),
            'php' => $php_version_id . ' ' . $php_version_source,
            'runtime' => PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . ' '
                . hash('xxh128', implode(',', $extensions)),
            'engine' => hash_final($engine),
            'deps' => hash('xxh128', Composer::getLockFile($config->base_dir)),
            'plugins' => hash_final($plugins),
        ]);
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
        return file_exists($path) ? (string) hash_file('xxh128', $path) : 'missing';
    }
}
