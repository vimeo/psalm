<?php
namespace Psalm\Config;

use Psalm\Exception\ConfigCreationException;
use Psalm\Internal\Provider;

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

            $replacements = self::getPsr4Paths($current_dir, $composer_json);

            if (!$replacements) {
                throw new ConfigCreationException(
                    'Could not located any PSR-4-compatible paths in ' . $composer_json_location
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
     */
    private static function getPsr4Paths(string $current_dir, array $composer_json) : array
    {
        if (!isset($composer_json['autoload']['psr-4'])) {
            return [];
        }

        $nodes = [];

        /** @var string|string[] $path */
        foreach ($composer_json['autoload']['psr-4'] as $paths) {
            if (!is_array($paths)) {
                $paths = [$paths];
            }

            foreach ($paths as $path) {
                if ($path === '') {
                    /** @var string[] */
                    $php_files = array_merge(
                        glob($current_dir . DIRECTORY_SEPARATOR . '*.php'),
                        glob($current_dir . DIRECTORY_SEPARATOR . '**/*.php'),
                        glob($current_dir . DIRECTORY_SEPARATOR . '**/**/*.php')
                    );

                    foreach ($php_files as $php_file) {
                        $parts = explode(DIRECTORY_SEPARATOR, $php_file);

                        if ($parts[0] === 'vendor') {
                            continue;
                        }

                        if (count($parts) === 1) {
                            $nodes[] = '<file name="' . $php_file . '" />';
                        } else {
                            $nodes[] = '<file name="' . $parts[0] . '" />';
                        }
                    }
                } else {
                    $nodes[] = '<directory name="' . $path . '" />';
                }
            }
        }

        $nodes = array_unique($nodes);

        sort($nodes);

        return $nodes;
    }
}
