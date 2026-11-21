<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use Psalm\Internal\Composer;

use function array_filter;
use function array_keys;
use function array_shift;
use function array_slice;
use function array_values;
use function count;
use function explode;
use function file_get_contents;
use function implode;
use function is_array;
use function is_readable;
use function is_string;
use function json_decode;
use function ltrim;
use function preg_match;
use function preg_quote;
use function preg_split;
use function rtrim;
use function str_ends_with;
use function str_replace;
use function str_starts_with;
use function strlen;
use function strpos;
use function strtolower;
use function substr;
use function trim;

/**
 * The code of a project that its users don't get: what only composer.json's autoload-dev loads, and the paths that
 * .gitattributes leaves out of the package with export-ignore.
 *
 * @internal
 */
final class UnshippedCode
{
    /**
     * @param list<array{string, string}> $dev_namespaces lowercase namespace prefixes, with the directory of each
     * @param list<string> $dev_paths
     * @param list<array{non-empty-string, bool, bool, bool}> $export_ignore_rules the regex of each pattern, whether
     *     it matches the basename, whether it matches only directories, and whether it sets or unsets export-ignore
     */
    private function __construct(
        private readonly string $root,
        private readonly array $dev_namespaces,
        private readonly array $dev_paths,
        private readonly array $export_ignore_rules,
    ) {
    }

    public static function load(string $root): self
    {
        $composer_json_path = Composer::getJsonFilePath($root);
        $composer_json = is_readable($composer_json_path)
            ? (array) json_decode((string) file_get_contents($composer_json_path), true)
            : [];

        $gitattributes_path = $root . '/.gitattributes';
        $gitattributes = is_readable($gitattributes_path) ? (string) file_get_contents($gitattributes_path) : '';

        return self::fromContents($root, $composer_json, $gitattributes);
    }

    /**
     * @psalm-pure
     */
    public static function fromContents(string $root, array $composer_json, string $gitattributes): self
    {
        $dev_namespaces = [];
        $dev_paths = [];

        $autoload_dev = self::arrayAt($composer_json, 'autoload-dev');
        foreach (['psr-4', 'psr-0'] as $standard) {
            $namespaces = self::arrayAt($autoload_dev, $standard);
            foreach (array_keys($namespaces) as $namespace) {
                foreach (self::strings($namespaces[$namespace]) as $dir) {
                    $dev_namespaces[] = [strtolower(ltrim((string) $namespace, '\\')), self::normalizePath($dir)];
                }
            }
        }

        foreach (['classmap', 'files'] as $kind) {
            foreach (self::strings(self::arrayAt($autoload_dev, $kind)) as $path) {
                $dev_paths[] = self::normalizePath($path);
            }
        }

        $export_ignore_rules = [];
        foreach (preg_split('/\R/', $gitattributes) ?: [] as $line) {
            $attributes = preg_split('/\s+/', trim($line)) ?: [];
            $pattern = array_shift($attributes);
            // macro definitions and quoted patterns aren't supported
            if ($pattern === null
                || $pattern === ''
                || $pattern[0] === '#'
                || $pattern[0] === '['
                || $pattern[0] === '"'
            ) {
                continue;
            }

            $export_ignore = null;
            foreach ($attributes as $attribute) {
                if ($attribute === 'export-ignore') {
                    $export_ignore = true;
                } elseif ($attribute === '-export-ignore'
                    || $attribute === '!export-ignore'
                    || str_starts_with($attribute, 'export-ignore=')
                ) {
                    $export_ignore = false;
                }
            }

            if ($export_ignore === null) {
                continue;
            }

            $directory_only = str_ends_with($pattern, '/');
            $pattern = rtrim($pattern, '/');
            $basename = strpos($pattern, '/') === false;
            $pattern = ltrim($pattern, '/');
            if ($pattern === '') {
                continue;
            }

            $export_ignore_rules[] = [self::globToRegex($pattern), $basename, $directory_only, $export_ignore];
        }

        return new self(self::normalizePath($root), $dev_namespaces, $dev_paths, $export_ignore_rules);
    }

    /**
     * @psalm-mutation-free
     */
    public function contains(string $fq_class_name, string $file_path): bool
    {
        $file_path = self::normalizePath($file_path);
        if (!str_starts_with($file_path, $this->root . '/')) {
            return false;
        }

        $relative_path = substr($file_path, strlen($this->root) + 1);

        $fq_class_name_lc = strtolower(ltrim($fq_class_name, '\\'));
        foreach ($this->dev_namespaces as [$namespace, $dir]) {
            if (str_starts_with($fq_class_name_lc, $namespace) && self::isWithin($relative_path, $dir)) {
                return true;
            }
        }

        foreach ($this->dev_paths as $path) {
            if (self::isWithin($relative_path, $path)) {
                return true;
            }
        }

        // git archive leaves out everything in an ignored directory
        $segments = explode('/', $relative_path);
        $segment_count = count($segments);
        for ($i = 1; $i <= $segment_count; $i++) {
            if ($this->isExportIgnored(
                implode('/', array_slice($segments, 0, $i)),
                $segments[$i - 1],
                $i < $segment_count,
            )) {
                return true;
            }
        }

        return false;
    }

    /**
     * @psalm-mutation-free
     */
    private function isExportIgnored(string $path, string $basename, bool $is_directory): bool
    {
        $export_ignore = false;

        foreach ($this->export_ignore_rules as [$regex, $matches_basename, $directory_only, $value]) {
            if ((!$directory_only || $is_directory) && preg_match($regex, $matches_basename ? $basename : $path)) {
                $export_ignore = $value;
            }
        }

        return $export_ignore;
    }

    /**
     * @return array<array-key, mixed>
     * @psalm-pure
     */
    private static function arrayAt(array $array, string $key): array
    {
        return isset($array[$key]) && is_array($array[$key]) ? $array[$key] : [];
    }

    /**
     * @return list<string>
     * @psalm-pure
     */
    private static function strings(mixed $value): array
    {
        if (is_string($value)) {
            return [$value];
        }

        return is_array($value) ? array_values(array_filter($value, 'is_string')) : [];
    }

    /**
     * @psalm-pure
     */
    private static function isWithin(string $relative_path, string $path): bool
    {
        return $path === '' || $relative_path === $path || str_starts_with($relative_path, $path . '/');
    }

    /**
     * @psalm-pure
     */
    private static function normalizePath(string $path): string
    {
        $path = rtrim(str_replace('\\', '/', $path), '/');

        while (str_starts_with($path, './')) {
            $path = substr($path, 2);
        }

        return $path === '.' ? '' : $path;
    }

    /**
     * Translates a pattern of .gitattributes, which follows the rules of .gitignore.
     *
     * @return non-empty-string
     * @psalm-pure
     */
    private static function globToRegex(string $pattern): string
    {
        $regex = '';
        $length = strlen($pattern);

        for ($i = 0; $i < $length; $i++) {
            $char = $pattern[$i];

            if ($char === '*' && ($pattern[$i + 1] ?? '') === '*' && ($i === 0 || $pattern[$i - 1] === '/')) {
                if ($i + 2 === $length) {
                    // a trailing "/**" matches everything inside
                    $regex .= '.*';
                    $i++;
                    continue;
                }

                if ($pattern[$i + 2] === '/') {
                    // "**/" matches any number of directories
                    $regex .= '(?:.*/)?';
                    $i += 2;
                    continue;
                }
            }

            if ($char === '*') {
                $regex .= '[^/]*';
            } elseif ($char === '?') {
                $regex .= '[^/]';
            } elseif ($char === '[' && ($end = strpos($pattern, ']', $i + 2)) !== false) {
                $class = substr($pattern, $i + 1, $end - $i - 1);
                $class = str_replace('#', '\\#', $class);
                $regex .= '[' . ($class[0] === '!' ? '^' . substr($class, 1) : $class) . ']';
                $i = $end;
            } elseif ($char === '\\' && $i + 1 < $length) {
                $regex .= preg_quote($pattern[++$i], '#');
            } else {
                $regex .= preg_quote($char, '#');
            }
        }

        return '#^' . $regex . '$#';
    }
}
