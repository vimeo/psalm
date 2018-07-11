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

class EnableCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('enable')
            ->setDescription('Enables a named plugin')
            ->addArgument('pluginName', InputArgument::REQUIRED, 'Plugin name (fully qualified class name or composer package name)')
            ->addUsage('vendor/plugin-package-name [-c path/to/psalm.xml]')
            ->addUsage('\'Plugin\Class\Name\' [-c path/to/psalm.xml]');
    }

    protected function execute(InputInterface $i, OutputInterface $o)
    {
        $current_dir = (string) getcwd() . DIRECTORY_SEPARATOR;

        /** @psalm-suppress MixedAssignment */
        $config_file_path = $i->getOption('config');
        assert(null === $config_file_path || is_string($config_file_path));

        $config_file = new ConfigFile($current_dir, $config_file_path);
        $composer_lock = new ComposerLock('composer.lock');
        $plugin_list = new PluginList($config_file, $composer_lock);

        try {
            /** @psalm-suppress MixedAssignment */
            $plugin_name = $i->getArgument('pluginName');
            assert(is_string($plugin_name));

            $plugin_class = $plugin_list->resolvePluginClass($plugin_name);
        } catch (InvalidArgumentException $e) {
            $o->writeLn('<error>Unknown plugin class</error>');
            return 2;
        }

        if ($plugin_list->isEnabled($plugin_class)) {
            $o->writeLn('<error>Plugin already enabled</error>');
            return 3;
        }

        $config_file->addPlugin($plugin_class);

    }
}
