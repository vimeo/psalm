<?php

declare(strict_types=1);

namespace Psalm\Internal;

use Composer\Autoload\ClassLoader;

use function array_diff;
use function array_keys;
use function array_unique;
use function array_values;
use function basename;
use function class_exists;
use function dirname;
use function file_get_contents;
use function get_included_files;
use function in_array;
use function is_file;
use function method_exists;
use function preg_grep;
use function str_contains;
use function str_starts_with;

use const DIRECTORY_SEPARATOR;
use const PREG_GREP_INVERT;

/**
 * Include collector
 *
 * Used to execute code that may cause file inclusions, and report what files have been included
 * NOTE: dependencies of this class should be kept at minimum, as it's used before autoloader is
 * registered.
 *
 * @internal
 */
final class IncludeCollector
{
    /**
     * Files Composer generates into its `composer` directory, or ships there to run the autoloader.
     * They hold no code of the analysed project, but are huge on an optimized autoloader
     * (`autoload_static.php` can be 10 MB).
     */
    private const COMPOSER_RUNTIME_FILES = [
        'autoload_classmap.php',
        'autoload_files.php',
        'autoload_namespaces.php',
        'autoload_psr4.php',
        'autoload_real.php',
        'autoload_static.php',
        'ClassLoader.php',
        'installed.php',
        'InstalledVersions.php',
        'platform_check.php',
    ];

    /** @var list<string> */
    private array $included_files = [];

    /**
     * @template T
     * @param callable():T $f
     * @return T
     */
    public function runAndCollect(callable $f)
    {
        $before = get_included_files();
        $ret = $f();
        $after = get_included_files();

        $included = array_diff($after, $before);

        $this->included_files = array_values(array_unique([...$this->included_files, ...$included]));

        return $ret;
    }

    /** @return list<string> */
    public function getIncludedFiles(): array
    {
        return $this->included_files;
    }

    /**
     * Included files worth scanning as autoloaded code: without phar contents, Psalm's own source
     * and the machinery of the Composer autoloader (`vendor/autoload.php`, `vendor/composer/autoload_*.php`, ...).
     *
     * @return list<string>
     */
    public function getFilteredIncludedFiles(): array
    {
        $included_files = preg_grep('@^phar://@', $this->getIncludedFiles(), PREG_GREP_INVERT);

        $composer_dirs = [];
        if (class_exists(ClassLoader::class, false) && method_exists(ClassLoader::class, 'getRegisteredLoaders')) {
            foreach (array_keys(ClassLoader::getRegisteredLoaders()) as $vendor_dir) {
                $composer_dirs[] = $vendor_dir . DIRECTORY_SEPARATOR . 'composer';
            }
        }

        foreach ($included_files as $file) {
            if (basename($file) === 'autoload_real.php' && self::isComposerGeneratedDirectory(dirname($file))) {
                $composer_dirs[] = dirname($file);
            }
        }

        $psalm_dir = dirname(__DIR__) . DIRECTORY_SEPARATOR;

        $filtered_files = [];
        foreach ($included_files as $file) {
            $dir = dirname($file);
            $name = basename($file);

            if (in_array($dir, $composer_dirs, true) && in_array($name, self::COMPOSER_RUNTIME_FILES, true)) {
                continue;
            }

            if ($name === 'autoload.php' && in_array($dir . DIRECTORY_SEPARATOR . 'composer', $composer_dirs, true)) {
                continue;
            }

            if (str_starts_with($file, $psalm_dir)) {
                continue;
            }

            $filtered_files[] = $file;
        }

        return $filtered_files;
    }

    /**
     * Composer copies `ClassLoader.php` next to a generated `autoload_real.php`,
     * which declares a `ComposerAutoloaderInit<hash>` class.
     */
    private static function isComposerGeneratedDirectory(string $dir): bool
    {
        if (!is_file($dir . DIRECTORY_SEPARATOR . 'ClassLoader.php')) {
            return false;
        }

        $autoload_real = file_get_contents($dir . DIRECTORY_SEPARATOR . 'autoload_real.php');

        return $autoload_real !== false && str_contains($autoload_real, 'class ComposerAutoloaderInit');
    }
}
