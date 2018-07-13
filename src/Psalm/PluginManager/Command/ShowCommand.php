<?php
namespace Psalm\PluginManager\Command;

use Psalm\PluginManager\ComposerLock;
use Psalm\PluginManager\ConfigFile;
use Psalm\PluginManager\PluginList;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ShowCommand extends Command
{
    /**
     * @return void
     * @psalm-suppress UnusedMethod
     */
    protected function configure()
    {
        $this
            ->setName('show')
            ->setDescription('Lists enabled and available plugins')
            ->addUsage('[-c path/to/psalm.xml]');
    }

    /**
     * @return null|int
     * @psalm-suppress UnusedMethod
     */
    protected function execute(InputInterface $i, OutputInterface $o)
    {
        $io = new SymfonyStyle($i, $o);
        $current_dir = (string) getcwd() . DIRECTORY_SEPARATOR;

        /** @psalm-suppress MixedAssignment */
        $config_file_path = $i->getOption('config');
        assert(null === $config_file_path || is_string($config_file_path));

        $config_file = new ConfigFile($current_dir, $config_file_path);
        $composer_lock = new ComposerLock('composer.lock');
        $plugin_list = new PluginList($config_file, $composer_lock);

        $enabled = $plugin_list->getEnabled();
        $available = $plugin_list->getAvailable();

        $formatRow =
            /** @param null|string $package */
            function (string $class, $package): array {
                return [$package, $class];
            }
        ;

        $io->section('Enabled');
        if (count($enabled)) {
            $io->table(
                ['Package', 'Class'],
                array_map(
                    $formatRow,
                    array_keys($enabled),
                    array_values($enabled)
                )
            );
        } else {
            $io->note('No plugins enabled');
        }

        $io->section('Available');
        if (count($available)) {
            $io->table(
                ['Package', 'Class'],
                array_map(
                    $formatRow,
                    array_keys($available),
                    array_values($available)
                )
            );
        } else {
            $io->note('No plugins available');
        }
    }
}
