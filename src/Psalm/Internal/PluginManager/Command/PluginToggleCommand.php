<?php

declare(strict_types=1);

namespace Psalm\Internal\PluginManager\Command;

use InvalidArgumentException;
use Override;
use Psalm\Internal\PluginManager\PluginListFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function getcwd;

/**
 * @internal
 */
abstract class PluginToggleCommand extends Command
{
    public function __construct(
        private readonly PluginListFactory $plugin_list_factory,
        private readonly bool $enable,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this
            ->setName($this->enable ? 'enable' : 'disable')
            ->setDescription($this->enable ? 'Enables a named plugin' : 'Disables a named plugin')
            ->addArgument(
                'pluginName',
                InputArgument::REQUIRED,
                'Plugin name (fully qualified class name or composer package name)',
            )
            ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Path to Psalm config file')
            ->addUsage('vendor/plugin-package-name [-c path/to/psalm.xml]');
        $this->addUsage('\'Plugin\Class\Name\' [-c path/to/psalm.xml]');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $current_dir = (string) getcwd();

        $config_file_path = $input->getOption('config');

        $plugin_list = ($this->plugin_list_factory)($current_dir, $config_file_path);

        $plugin_name = $input->getArgument('pluginName');

        try {
            $plugin_class = $plugin_list->resolvePluginClass($plugin_name);
        } catch (InvalidArgumentException) {
            $io->error('Unknown plugin class ' . $plugin_name);

            return 2;
        }

        if ($plugin_list->isEnabled($plugin_class) === $this->enable) {
            $io->note($this->enable ? 'Plugin already enabled' : 'Plugin already disabled');

            return 3;
        }

        if ($this->enable) {
            $plugin_list->enable($plugin_class);
            $io->success('Plugin enabled');
        } else {
            $plugin_list->disable($plugin_class);
            $io->success('Plugin disabled');
        }

        return 0;
    }
}
