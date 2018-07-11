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
    /** @psalm-suppress UnusedMethod */
    protected function configure(): void
    {
        $this
            ->setName('show')
            ->setDescription('Lists enabled and available plugins')
            ->addUsage('[-c path/to/psalm.xml]');
    }

    /** @psalm-suppress UnusedMethod */
    protected function execute(InputInterface $i, OutputInterface $o)
    {
        $current_dir = (string) getcwd() . DIRECTORY_SEPARATOR;

        /** @psalm-suppress MixedAssignment */
        $config_file_path = $i->getOption('config');
        assert(null === $config_file_path || is_string($config_file_path));

        $config_file = new ConfigFile($current_dir, $config_file_path);
        $composer_lock = new ComposerLock('composer.lock');
        $plugin_list = new PluginList($config_file, $composer_lock);

        $available = array_keys($plugin_list->getAvailable());
        $enabled = array_keys($plugin_list->getEnabled());

        echo "Enabled: " . (join(',', $enabled) ?: 'none') . PHP_EOL;
        echo "Available: " . (join(',', $available) ?: 'none') . PHP_EOL;
    }
}
