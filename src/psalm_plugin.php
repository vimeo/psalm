<?php
require __DIR__ . '/' . 'command_functions.php';
use Psalm\Config;
$help = <<<HELP
Usage:
    psalm-plugin <mode> [Plugin\Class\\Name]
Modes:
    enable: Enables the specified plugin, requires plugin class name
    disable: Disables the specified plugin, requires plugin class name
    list: shows enabled and available plugins
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
$plugin_classes = array_map(
    function(object $package): string {
        return $package->extra->pluginClass;
    },
    array_filter(
        array_merge($composer_lock->packages, $composer_lock->{"packages-dev"}),
        function (object $package): bool {
            return isset($package->type)
                && $package->type === 'psalm-plugin'
                && isset($package->extra->pluginClass);
        }
    )
);

$current_dir = (string)getcwd() . DIRECTORY_SEPARATOR;
$vendor_dir = getVendorDir($current_dir);
requireAutoloaders($current_dir, false, $vendor_dir);

// inspect enabled plugins
$enabled = Config::getConfigForPath($current_dir, $current_dir, '')->getPluginClasses();

if ($mode === 'list') {
    echo "Enabled: " . (join(',', $enabled) ?: 'none') . PHP_EOL;
    echo "Available: " . (join(',', array_diff($plugin_classes, $enabled)) ?: 'none') . PHP_EOL;
} else {
    if (!isset($_SERVER['argv'][2])) {
        fwrite(STDERR, 'Plugin class name missing' . PHP_EOL);
        fwrite(STDERR, $help . PHP_EOL);
        exit(1);
    }
    $plugin_class = $_SERVER['argv'][2];
    if (!in_array($plugin_class, $plugin_classes, true)) {
        fwrite(STDERR, 'Unknown plugin class' . PHP_EOL);
        exit(2);
    }

    $config_file_name = Config::locateConfigFile($current_dir);
    if (!$config_file_name) {
        fwrite(STDERR, 'Psalm config file not found' . PHP_EOL);
        exit(3);
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
