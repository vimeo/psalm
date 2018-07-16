<?php
require_once __DIR__ . '/' . 'command_functions.php';
use Psalm\Config;
use Psalm\PluginManager\Command\DisableCommand;
use Psalm\PluginManager\Command\EnableCommand;
use Psalm\PluginManager\Command\ShowCommand;
use Psalm\PluginManager\PluginListFactory;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputOption;
use Muglug\PackageVersions\Versions;

$current_dir = (string)getcwd() . DIRECTORY_SEPARATOR;
$vendor_dir = getVendorDir($current_dir);
requireAutoloaders($current_dir, false, $vendor_dir);


$app = new Application('psalm-plugin', (string) Versions::getVersion('vimeo/psalm'));

$plugin_list_factory = new PluginListFactory;
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
