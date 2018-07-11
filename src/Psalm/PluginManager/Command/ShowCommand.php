<?php
namespace Psalm\PluginManager\Command;

use Psalm\PluginManager\ComposerLock;
use Psalm\PluginManager\ConfigFile;
use Psalm\PluginManager\PluginList;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ShowCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('show')
            ->setDescription('Lists enabled and available plugins')
            ->addUsage('[-c path/to/psalm.xml]');
    }

    protected function execute(InputInterface $i, OutputInterface $o)
    {
        $current_dir = (string) getcwd() . DIRECTORY_SEPARATOR;

        $config_file = new ConfigFile($current_dir, $i->getOption('config'));
        $composer_lock = new ComposerLock('composer.lock');
        $plugin_list = new PluginList($config_file, $composer_lock);

        $available = $plugin_list->getAvailable();
        $enabled = $plugin_list->getEnabled();

        echo "Enabled: " . (join(',', $enabled) ?: 'none') . PHP_EOL;
        echo "Available: " . (join(',', $available) ?: 'none') . PHP_EOL;
    }
}
