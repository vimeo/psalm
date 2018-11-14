<?php
namespace Psalm\PluginManager\Command;

use Psalm\PluginManager\PluginList;
use Psalm\PluginManager\PluginListFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use InvalidArgumentException;
use Symfony\Component\Console\Style\SymfonyStyle;

class DisableCommand extends Command
{
    /** @var PluginListFactory */
    private $plugin_list_factory;

    public function __construct(PluginListFactory $plugin_list_factory)
    {
        $this->plugin_list_factory = $plugin_list_factory;
        parent::__construct();
    }

    /**
     * @psalm-suppress UnusedMethod
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('disable')
            ->setDescription('Disables a named plugin')
            ->addArgument(
                'pluginName',
                InputArgument::REQUIRED,
                'Plugin name (fully qualified class name or composer package name)'
            )
            ->addUsage('vendor/plugin-package-name [-c path/to/psalm.xml]');
        $this->addUsage('\'Plugin\Class\Name\' [-c path/to/psalm.xml]');
    }

    /**
     * @psalm-suppress UnusedMethod
     * @return null|int
     */
    protected function execute(InputInterface $i, OutputInterface $o)
    {
        $io = new SymfonyStyle($i, $o);

        $current_dir = (string) getcwd() . DIRECTORY_SEPARATOR;

        /** @var string|string[]|bool|null */
        $config_file_path = $i->getOption('config');
        if ($config_file_path !== null && !is_string($config_file_path)) {
            throw new \UnexpectedValueException('Config file path should be a string');
        }

        $plugin_list = ($this->plugin_list_factory)($current_dir, $config_file_path);

        $plugin_name = null;

        try {
            /** @psalm-suppress MixedAssignment */
            $plugin_name = $i->getArgument('pluginName');
            assert(is_string($plugin_name));

            $plugin_class = $plugin_list->resolvePluginClass($plugin_name);
        } catch (InvalidArgumentException $e) {
            $io->error('Unknown plugin class ' . $plugin_name);
            return 2;
        }

        if (!$plugin_list->isEnabled($plugin_class)) {
            $io->note('Plugin already disabled');
            return 3;
        }

        $plugin_list->disable($plugin_class);
        $io->success('Plugin disabled');
    }
}
