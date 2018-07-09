<?php
require __DIR__ . '/' . 'command_functions.php';
use Psalm\Config;
$help = <<<HELP
Usage:
    psalm-plugin <mode> [plugin-name] [-c <config file>]

Modes:
    'enable': Enables the specified plugin, requires plugin name
    'disable': Disables the specified plugin, requires plugin name
    'list': shows enabled and available plugins

Plugin names:
    Plugins can be referred to by either fully qualified class names or composer package names:
      > psalm-plugin enable 'Plugin\\Class\\Name'
      > psalm-plugin disable plugin-vendor/plugin-package-name

Arguments:
    -c <config file>    path to config file. psalm-plugin will search up the directory tree,
                        starting with the current directory, for 'psalm.xml' or 'psalm.xml.dist'

HELP;
if (empty($_SERVER['argv'][1])) {
    fwrite(STDERR, $help . PHP_EOL);
    exit(0);
}
$mode = $_SERVER['argv'][1];
if (!in_array($mode, ['enable', 'disable', 'list'], true)) {
    fwrite(STDERR, 'Unrecognized mode: ' . $mode . PHP_EOL);
    fwrite(STDERR, $help . PHP_EOL);
    exit(1);
}

// inspect available plugins
$composer_lock = json_decode(file_get_contents('composer.lock'));
$plugin_classes = iterator_to_array(
    (function(array $packages): Generator {
        foreach ($packages as $package) {
            yield $package->name => $package->extra->pluginClass;
        }
    })(
        array_filter(
            array_merge($composer_lock->packages, $composer_lock->{"packages-dev"}),
            function (object $package): bool {
                return isset($package->type)
                    && $package->type === 'psalm-plugin'
                    && isset($package->extra->pluginClass);
            }
        )
    )
);

$current_dir = (string)getcwd() . DIRECTORY_SEPARATOR;
$vendor_dir = getVendorDir($current_dir);
requireAutoloaders($current_dir, false, $vendor_dir);

$config_file_name = findConfigFile($_SERVER['argv'], $current_dir);
if (!$config_file_name || !file_exists($config_file_name)) {
    fwrite(STDERR, 'Psalm config file not found' . PHP_EOL);
    exit(3);
}
$config = Config::loadFromXMLFile($config_file_name, $current_dir);

// inspect enabled plugins
$enabled = $config->getPluginClasses();

if ($mode === 'list') {
    echo "Enabled: " . (join(',', $enabled) ?: 'none') . PHP_EOL;
    echo "Available: " . (join(',', array_diff($plugin_classes, $enabled)) ?: 'none') . PHP_EOL;
} else {
    if (!isset($_SERVER['argv'][2])) {
        fwrite(STDERR, 'Plugin class name missing' . PHP_EOL);
        fwrite(STDERR, $help . PHP_EOL);
        exit(1);
    }
    try {
        $plugin_class = resolvePluginClass($_SERVER['argv'][2], $plugin_classes);
    } catch (\InvalidArgumentException $e) {
        fwrite(STDERR, 'Unknown plugin class' . PHP_EOL);
        exit(2);
    }

    if ($mode === 'enable') {
        if (in_array($plugin_class, $enabled, true)) {
            fwrite(STDERR, 'Plugin already enabled' . PHP_EOL);
            exit(3);
        }

        $ns = 'https://getpsalm.org/schema/config';

        // todo: get filename from config to reuse search logic
        $config_xml = new \SimpleXmlElement(file_get_contents($config_file_name));
        if (!isset($config_xml->plugins)) {
            $config_xml->addChild('plugins', "\n", $ns);
        }
        $config_xml->plugins->addChild('pluginClass', '', $ns)->addAttribute('class', $plugin_class);
        $config_xml->asXML($config_file_name);
    }

    if ($mode === 'disable') {
        if (!in_array($plugin_class, $enabled, true)) {
            fwrite(STDERR, 'Plugin already disabled' . PHP_EOL);
            exit(3);
        }

        // todo: get filename from config to reuse search logic
        $config_xml = new \SimpleXmlElement(file_get_contents($config_file_name));
        foreach ($config_xml->plugins->pluginClass as $entry) {
            if ((string)$entry['class'] === $plugin_class) {
                unset($entry[0]);
                break;
            }
        }

        $config_xml->asXML($config_file_name);
    }
}

function resolvePluginClass(string $class_or_package, array $plugin_classes): string
{
    if (false !== strpos($class_or_package, '/') && isset($plugin_classes[$class_or_package])) {
        return $plugin_classes[$class_or_package];
    } else if (in_array($class_or_package, $plugin_classes, true)){
        return $class_or_package;
    }
    throw new \InvalidArgumentException('Unknown plugin: ', $class_or_package);
}

function findConfigFile(array $argv, string $current_dir): string
{
    $config_arg_offset = array_search('-c', $argv, true);
    if (false !== $config_arg_offset) {
        if (!isset($argv[$config_arg_offset + 1])) {
            fwrite(STDERR, '-c requires a file path' . PHP_EOL);
            exit(1);
        }
        return $argv[$config_arg_offset + 1];
    } else {
        return Config::locateConfigFile($current_dir);
    }
}
