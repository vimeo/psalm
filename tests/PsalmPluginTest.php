<?php

namespace Psalm\Tests;

use InvalidArgumentException;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psalm\Internal\PluginManager\Command\DisableCommand;
use Psalm\Internal\PluginManager\Command\EnableCommand;
use Psalm\Internal\PluginManager\Command\ShowCommand;
use Psalm\Internal\PluginManager\PluginList;
use Psalm\Internal\PluginManager\PluginListFactory;
use Psalm\Internal\RuntimeCaches;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Tester\CommandTester;

use function preg_quote;

/** @group PluginManager */
class PsalmPluginTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy */
    private $plugin_list;

    /** @var ObjectProphecy */
    private $plugin_list_factory;

    /** @var Application */
    private $app;

    public function setUp(): void
    {
        RuntimeCaches::clearAll();
        $this->plugin_list = $this->prophesize(PluginList::class);
        $this->plugin_list_factory = $this->prophesize(PluginListFactory::class);
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

        $this->plugin_list->getEnabled()->willReturn([]);
        $this->plugin_list->getAvailable()->willReturn([]);
    }

    /**
     * @test
     */
    public function showsNoticesWhenTheresNoPlugins(): void
    {
        $show_command = new CommandTester($this->app->find('show'));
        $show_command->execute([]);

        $output = $show_command->getDisplay();
        $this->assertStringContainsString('No plugins enabled', $output);
        $this->assertStringContainsString('No plugins available', $output);
    }

    /**
     * @test
     */
    public function showsEnabledPlugins(): void
    {
        $this->plugin_list->getEnabled()->willReturn(['a\b\c' => 'vendor/package']);

        $show_command = new CommandTester($this->app->find('show'));
        $show_command->execute([]);

        $output = $show_command->getDisplay();
        $this->assertStringContainsString('vendor/package', $output);
        $this->assertStringContainsString('a\b\c', $output);
    }

    /**
     * @test
     */
    public function showsAvailablePlugins(): void
    {
        $this->plugin_list->getAvailable()->willReturn(['a\b\c' => 'vendor/package']);

        $show_command = new CommandTester($this->app->find('show'));
        $show_command->execute([]);

        $output = $show_command->getDisplay();
        $this->assertStringContainsString('vendor/package', $output);
        $this->assertStringContainsString('a\b\c', $output);
    }

    /**
     * @test
     */
    public function passesExplicitConfigToPluginListFactory(): void
    {
        $this->plugin_list_factory->__invoke(Argument::any(), '/a/b/c')->willReturn($this->plugin_list->reveal());

        $show_command = new CommandTester($this->app->find('show'));
        $show_command->execute([
            '--config' => '/a/b/c',
        ]);
    }

    /**
     * @test
     */
    public function showsColumnHeaders(): void
    {
        $this->plugin_list->getAvailable()->willReturn(['a\b\c' => 'vendor/package']);
        $this->plugin_list->getAvailable()->willReturn(['c\d\e' => 'another-vendor/package']);

        $show_command = new CommandTester($this->app->find('show'));
        $show_command->execute([]);

        $output = $show_command->getDisplay();

        $this->assertStringContainsString('Package', $output);
        $this->assertStringContainsString('Class', $output);
    }

    /**
     * @dataProvider commands
     * @test
     */
    public function listsCommands(string $command): void
    {
        $list_command = new CommandTester($this->app->find('list'));
        $list_command->execute([]);
        $output = $list_command->getDisplay();
        $this->assertStringContainsString($command, $output);
    }

    /**
     * @dataProvider commands
     * @test
     */
    public function showsHelpForCommand(string $command): void
    {
        $help_command = new CommandTester($this->app->find('help'));
        $help_command->execute(['command_name' => $command]);
        $output = $help_command->getDisplay();
        $this->assertRegExp('/Usage:.*$\s+' . preg_quote($command, '/') . '\b/m', $output);
    }

    /**
     * @test
     */
    public function requiresPluginNameToEnable(): void
    {
        $enable_command = new CommandTester($this->app->find('enable'));
        $this->expectExceptionMessage('missing: "pluginName"');
        $enable_command->execute([]);
    }

    /**
     * @test
     */
    public function enableComplainsWhenPassedUnresolvablePlugin(): void
    {
        $this->plugin_list->resolvePluginClass(Argument::any())->willThrow(new InvalidArgumentException);

        $enable_command = new CommandTester($this->app->find('enable'));
        $enable_command->execute(['pluginName' => 'vendor/package']);

        $output = $enable_command->getDisplay();

        $this->assertStringContainsString('ERROR', $output);
        $this->assertStringContainsString('Unknown plugin', $output);
        $this->assertNotSame(0, $enable_command->getStatusCode());
    }

    /**
     * @test
     */
    public function enableComplainsWhenPassedAlreadyEnabledPlugin(): void
    {
        $this->plugin_list->resolvePluginClass('vendor/package')->will(
            function (array $_args, ObjectProphecy $plugin_list): string {
                        $plugin_list->isEnabled('Vendor\Package\PluginClass')->willReturn(true);

                return 'Vendor\Package\PluginClass';
            }
        );

        $enable_command = new CommandTester($this->app->find('enable'));
        $enable_command->execute(['pluginName' => 'vendor/package']);

        $output = $enable_command->getDisplay();
        $this->assertStringContainsString('Plugin already enabled', $output);
        $this->assertNotSame(0, $enable_command->getStatusCode());
    }

    /**
     * @test
     */
    public function enableReportsSuccessWhenItEnablesPlugin(): void
    {
        $this->plugin_list->resolvePluginClass('vendor/package')->will(
            function (array $_args, ObjectProphecy $plugin_list): string {
                $plugin_class = 'Vendor\Package\PluginClass';
                        $plugin_list->isEnabled($plugin_class)->willReturn(false);
                        $plugin_list->enable($plugin_class)->shouldBeCalled();

                return $plugin_class;
            }
        );

        $enable_command = new CommandTester($this->app->find('enable'));
        $enable_command->execute(['pluginName' => 'vendor/package']);

        $output = $enable_command->getDisplay();
        $this->assertStringContainsString('Plugin enabled', $output);
        $this->assertSame(0, $enable_command->getStatusCode());
    }

    /**
     * @test
     */
    public function requiresPluginNameToDisable(): void
    {
        $disable_command = new CommandTester($this->app->find('disable'));
        $this->expectExceptionMessage('missing: "pluginName"');
        $disable_command->execute([]);
    }

    /**
     * @test
     */
    public function disableComplainsWhenPassedUnresolvablePlugin(): void
    {
        $this->plugin_list->resolvePluginClass(Argument::any())->willThrow(new InvalidArgumentException);

        $disable_command = new CommandTester($this->app->find('disable'));
        $disable_command->execute(['pluginName' => 'vendor/package']);

        $output = $disable_command->getDisplay();

        $this->assertStringContainsString('ERROR', $output);
        $this->assertStringContainsString('Unknown plugin', $output);
        $this->assertNotSame(0, $disable_command->getStatusCode());
    }

    /**
     * @test
     */
    public function disableComplainsWhenPassedNotEnabledPlugin(): void
    {
        $this->plugin_list->resolvePluginClass('vendor/package')->will(
            function (array $_args, ObjectProphecy $plugin_list): string {
                        $plugin_list->isEnabled('Vendor\Package\PluginClass')->willReturn(false);

                return 'Vendor\Package\PluginClass';
            }
        );

        $disable_command = new CommandTester($this->app->find('disable'));
        $disable_command->execute(['pluginName' => 'vendor/package']);

        $output = $disable_command->getDisplay();
        $this->assertStringContainsString('Plugin already disabled', $output);
        $this->assertNotSame(0, $disable_command->getStatusCode());
    }

    /**
     * @test
     */
    public function disableReportsSuccessWhenItDisablesPlugin(): void
    {
        $this->plugin_list->resolvePluginClass('vendor/package')->will(
            function (array $_args, ObjectProphecy $plugin_list): string {
                $plugin_class = 'Vendor\Package\PluginClass';
                        $plugin_list->isEnabled($plugin_class)->willReturn(true);
                        $plugin_list->disable($plugin_class)->shouldBeCalled();

                return $plugin_class;
            }
        );

        $disable_command = new CommandTester($this->app->find('disable'));
        $disable_command->execute(['pluginName' => 'vendor/package']);

        $output = $disable_command->getDisplay();
        $this->assertStringContainsString('Plugin disabled', $output);
        $this->assertSame(0, $disable_command->getStatusCode());
    }

    /** @return string[][] */
    public function commands(): array
    {
        return [
            ['show'],
            ['enable'],
            ['disable'],
        ];
    }
}
