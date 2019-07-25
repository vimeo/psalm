<?php
require_once __DIR__ . '/command_functions.php';
use PackageVersions\Versions;
use Psalm\Internal\PluginManager\Command\DisableCommand;
use Psalm\Internal\PluginManager\Command\EnableCommand;
use Psalm\Internal\PluginManager\Command\ShowCommand;
use Psalm\Internal\PluginManager\PluginListFactory;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputOption;

$current_dir = (string)getcwd() . DIRECTORY_SEPARATOR;
$vendor_dir = getVendorDir($current_dir);
requireAutoloaders($current_dir, false, $vendor_dir);

$app = new Application('psalm-plugin', Versions::getVersion('vimeo/psalm'));

$psalm_root = dirname(__DIR__) . DIRECTORY_SEPARATOR;

$plugin_list_factory = new PluginListFactory($current_dir, $psalm_root);

$app->addCommands([
    new ShowCommand($plugin_list_factory),
    new EnableCommand($plugin_list_factory),
    new DisableCommand($plugin_list_factory),
]);

$app->getDefinition()->addOption(
    new InputOption('config', 'c', InputOption::VALUE_REQUIRED, 'Path to Psalm config file')
);

$app->setDefaultCommand('show');
$app->run();
