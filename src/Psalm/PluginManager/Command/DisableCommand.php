<?php
namespace Psalm\PluginManager\Command;

use Psalm\PluginManager\ComposerLock;
use Psalm\PluginManager\ConfigFile;
use Psalm\PluginManager\PluginList;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use InvalidArgumentException;

class DisableCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('disable')
            ->setDescription('Disables a named plugin')
            ->addArgument('pluginName', InputArgument::REQUIRED, 'Plugin name (fully qualified class name or composer package name)')
            ->addUsage('vendor/plugin-package-name [-c path/to/psalm.xml]')
            ->addUsage('\'Plugin\Class\Name\' [-c path/to/psalm.xml]');
    }

    protected function execute(InputInterface $i, OutputInterface $o)
    {
        $current_dir = (string) getcwd() . DIRECTORY_SEPARATOR;

        $config_file = new ConfigFile($current_dir, $i->getOption('config'));
        $composer_lock = new ComposerLock('composer.lock');
        $plugin_list = new PluginList($config_file, $composer_lock);

        try {
            $plugin_class = $plugin_list->resolvePluginClass($i->getArgument('pluginName'));
        } catch (InvalidArgumentException $e) {
            $o->writeLn('<error>Unknown plugin class</error>');
            return 2;
        }

        if (!$plugin_list->isEnabled($plugin_class)) {
            $o->writeLn('<error>Plugin already disabled</error>');
            return 3;
        }

        $config_file->removePlugin($plugin_class);
    }
}
