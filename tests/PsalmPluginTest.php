<?php
namespace Psalm\Tests;

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psalm\PluginManager\Command\DisableCommand;
use Psalm\PluginManager\Command\EnableCommand;
use Psalm\PluginManager\Command\ShowCommand;
use Psalm\PluginManager\PluginList;
use Psalm\PluginManager\PluginListFactory;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Tester\CommandTester;

/** @group PluginManager */
class PsalmPluginTest extends TestCase
{
    /** @var ObjectProphecy */
    private $plugin_list;
    /** @var ObjectProphecy */
    private $plugin_list_factory;

    /** @var Application */
    private $app;

    public function setUp()
    {
        $this->plugin_list = $this->prophesize(PluginList::class);
        $this->plugin_list_factory = $this->prophesize(PluginListFactory::class);
        /** @psalm-suppress TooManyArguments */
        $this->plugin_list_factory->__invoke(Argument::any(), Argument::any())->willReturn($this->plugin_list->reveal());

        $this->app = new Application('psalm-plugin', '0.1');
        $this->app->addCommands([
            new ShowCommand($this->plugin_list_factory->reveal()),
            new EnableCommand($this->plugin_list_factory->reveal()),
            new DisableCommand($this->plugin_list_factory->reveal()),
        ]);

        $this->app->getDefinition()->addOption(
            new InputOption('config', 'c', InputOption::VALUE_REQUIRED, 'Path to Psalm config file')
        );

        $this->app->setDefaultCommand('show');

        /** @psalm-suppress TooManyArguments */
        $this->plugin_list->getEnabled()->willReturn([]);
        /** @psalm-suppress TooManyArguments */
        $this->plugin_list->getAvailable()->willReturn([]);
    }

    /**
     * @return void
     * @test
     */
    public function showsNoticesWhenTheresNoPlugins()
    {
        $show_command = new CommandTester($this->app->find('show'));
        $show_command->execute([]);

        $output = $show_command->getDisplay();
        $this->assertContains('No plugins enabled', $output);
        $this->assertContains('No plugins available', $output);
    }

    /**
     * @return void
     * @test
     */
    public function showsEnabledPlugins()
    {
        /** @psalm-suppress TooManyArguments */
        $this->plugin_list->getEnabled()->willReturn(['a\b\c' => 'vendor/package']);

        $show_command = new CommandTester($this->app->find('show'));
        $show_command->execute([]);

        $output = $show_command->getDisplay();
        $this->assertContains('vendor/package', $output);
        $this->assertContains('a\b\c', $output);
    }

    /**
     * @return void
     * @test
     */
    public function showsAvailablePlugins()
    {
        /** @psalm-suppress TooManyArguments */
        $this->plugin_list->getAvailable()->willReturn(['a\b\c' => 'vendor/package']);

        $show_command = new CommandTester($this->app->find('show'));
        $show_command->execute([]);

        $output = $show_command->getDisplay();
        $this->assertContains('vendor/package', $output);
        $this->assertContains('a\b\c', $output);
    }

    /**
     * @return void
     * @test
     */
    public function passesExplicitConfigToPluginListFactory()
    {
        /** @psalm-suppress TooManyArguments */
        $this->plugin_list_factory->__invoke(Argument::any(), '/a/b/c')->willReturn($this->plugin_list->reveal());

        $show_command = new CommandTester($this->app->find('show'));
        $show_command->execute([
            '--config' => '/a/b/c',
        ]);
    }

    /**
     * @return void
     * @test
     */
    public function showsColumnHeaders()
    {
        /** @psalm-suppress TooManyArguments */
        $this->plugin_list->getAvailable()->willReturn(['a\b\c' => 'vendor/package']);
        /** @psalm-suppress TooManyArguments */
        $this->plugin_list->getAvailable()->willReturn(['c\d\e' => 'another-vendor/package']);

        $show_command = new CommandTester($this->app->find('show'));
        $show_command->execute([]);

        $output = $show_command->getDisplay();

        $this->assertContains('Package', $output);
        $this->assertContains('Class', $output);
    }

    /**
     * @return void
     * @dataProvider commands
     * @test
     */
    public function listsCommands(string $command)
    {
        $list_command = new CommandTester($this->app->find('list'));
        $list_command->execute([]);
        $output = $list_command->getDisplay();
        $this->assertContains($command, $output);
    }

    /**
     * @return void
     * @dataProvider commands
     * @test
     */
    public function showsHelpForCommand(string $command)
    {
        $help_command = new CommandTester($this->app->find('help'));
        $help_command->execute(['command_name' => $command]);
        $output = $help_command->getDisplay();
        $this->assertRegExp('/Usage:$\s+' . preg_quote($command, '/') . '\b/m', $output);
    }

    /** @return string[][] */
    public function commands(): array
    {
        return [
            ['show',],
            ['enable',],
            ['disable',],
        ];
    }
}
