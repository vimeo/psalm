<?php
namespace Psalm\Config;

use function array_merge;
use function array_shift;
use function array_unique;
use function count;
use const DIRECTORY_SEPARATOR;
use function dirname;
use function explode;
use function file_exists;
use function file_get_contents;
use function glob;
use function implode;
use function is_array;
use function is_dir;
use function json_decode;
use function preg_replace;
use Psalm\Exception\ConfigCreationException;
use function sort;
use function str_replace;

class Creator
{
    public static function getContents(
        string $current_dir,
        string $suggested_dir = null,
        int $level = 3
    ) : string {
        $replacements = [];

        if ($suggested_dir) {
            if (is_dir($current_dir . DIRECTORY_SEPARATOR . $suggested_dir)) {
                $replacements[] = '<directory name="' . $suggested_dir . '" />';
            } else {
                $bad_dir_path = $current_dir . DIRECTORY_SEPARATOR . $suggested_dir;

                throw new ConfigCreationException(
                    'The given path "' . $bad_dir_path . '" does not appear to be a directory'
                );
            }
        } elseif (is_dir($current_dir . DIRECTORY_SEPARATOR . 'src')) {
            $replacements[] = '<directory name="src" />';
        } else {
            $composer_json_location = $current_dir . DIRECTORY_SEPARATOR . 'composer.json';

            if (!file_exists($composer_json_location)) {
                throw new ConfigCreationException(
                    'Problem during config autodiscovery - could not find composer.json during initialization.'
                );
            }

            /** @psalm-suppress MixedAssignment */
            if (!$composer_json = json_decode(file_get_contents($composer_json_location), true)) {
                throw new ConfigCreationException('Invalid composer.json at ' . $composer_json_location);
            }

            if (!is_array($composer_json)) {
                throw new ConfigCreationException('Invalid composer.json at ' . $composer_json_location);
            }

            $replacements = self::getPsr4Or0Paths($current_dir, $composer_json);

            if (!$replacements) {
                throw new ConfigCreationException(
                    'Could not located any PSR-0 or PSR-4-compatible paths in ' . $composer_json_location
                );
            }
        }

        $template_file_name = dirname(__DIR__, 3) . '/assets/config_levels/' . $level . '.xml';

        if (!file_exists($template_file_name)) {
            throw new ConfigCreationException('Could not open config template ' . $template_file_name);
        }

        $template = (string)file_get_contents($template_file_name);

        $template = str_replace(
            '<directory name="src" />',
            implode("\n        ", $replacements),
            $template
        );

        return $template;
    }

    /**
     * @return string[]
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedOperand
     * @psalm-suppress MixedArgument
     */
    private static function getPsr4Or0Paths(string $current_dir, array $composer_json) : array
    {
        $psr_paths = array_merge(
            $composer_json['autoload']['psr-4'] ?? [],
            $composer_json['autoload']['psr-0'] ?? []
        );

        if (!$psr_paths) {
            return self::guessPhpFileDirs($current_dir);
        }

        $nodes = [];

        /** @var string|string[] $path */
        foreach ($psr_paths as $paths) {
            if (!is_array($paths)) {
                $paths = [$paths];
            }

            /** @var string $path */
            foreach ($paths as $path) {
                if ($path === '') {
                    $nodes = array_merge(
                        $nodes,
                        self::guessPhpFileDirs($current_dir)
                    );

                    continue;
                }

                $path = preg_replace('@[\\\\/]$@', '', $path);

                if ($path !== 'tests') {
                    $nodes[] = '<directory name="' . $path . '" />';
                }
            }
        }

        $nodes = array_unique($nodes);

        sort($nodes);

        return $nodes;
    }

    /**
     * @return string[]
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedOperand
     */
    private static function guessPhpFileDirs(string $current_dir) : array
    {
        $nodes = [];

        /** @var string[] */
        $php_files = array_merge(
            glob($current_dir . DIRECTORY_SEPARATOR . '*.php'),
            glob($current_dir . DIRECTORY_SEPARATOR . '**/*.php'),
            glob($current_dir . DIRECTORY_SEPARATOR . '**/**/*.php')
        );

        foreach ($php_files as $php_file) {
            $php_file = str_replace($current_dir . DIRECTORY_SEPARATOR, '', $php_file);

            $parts = explode(DIRECTORY_SEPARATOR, $php_file);

            if (!$parts[0]) {
                array_shift($parts);
            }

            if ($parts[0] === 'vendor' || $parts[0] === 'tests') {
                continue;
            }

            if (count($parts) === 1) {
                $nodes[] = '<file name="' . $php_file . '" />';
            } else {
                $nodes[] = '<file name="' . $parts[0] . '" />';
            }
        }

        return $nodes;
    }
}
