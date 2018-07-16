<?php
namespace Psalm\PluginManager\Command;

use Psalm\PluginManager\PluginList;
use Psalm\PluginManager\PluginListFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ShowCommand extends Command
{
    /** @var PluginListFactory */
    private $plugin_list_factory;

    public function __construct(PluginListFactory $plugin_list_factory)
    {
        $this->plugin_list_factory = $plugin_list_factory;
        parent::__construct();
    }

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

        $plugin_list = ($this->plugin_list_factory)($current_dir, $config_file_path);

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
