<?php

declare(strict_types=1);

namespace Psalm\Tests;

use InvalidArgumentException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
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
    use MockeryPHPUnitIntegration;

    private MockInterface $plugin_list;

    private MockInterface $plugin_list_factory;

    private Application $app;

    public function setUp(): void
    {
        RuntimeCaches::clearAll();
        $this->plugin_list = Mockery::mock(PluginList::class);
        $this->plugin_list_factory = Mockery::mock(PluginListFactory::class);
        $this->plugin_list_factory
            ->allows()->__invoke(Mockery::andAnyOtherArgs())
            ->andReturns($this->plugin_list)
            ->byDefault();

        $this->app = new Application('psalm-plugin', '0.1');
        $this->app->addCommands([
            new ShowCommand($this->plugin_list_factory),
            new EnableCommand($this->plugin_list_factory),
            new DisableCommand($this->plugin_list_factory),
        ]);

        $this->app->getDefinition()->addOption(
            new InputOption('config', 'c', InputOption::VALUE_REQUIRED, 'Path to Psalm config file'),
        );

        $this->app->setDefaultCommand('show');

        $this->plugin_list->allows()->getEnabled()->andReturns([])->byDefault();
        $this->plugin_list->allows()->getAvailable()->andReturns([])->byDefault();
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
        $this->plugin_list->expects()->getEnabled()->andReturns(['a\b\c' => 'vendor/package']);

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
        $this->plugin_list->expects()->getAvailable()->andReturns(['a\b\c' => 'vendor/package']);

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
        $this->plugin_list_factory->expects()->__invoke(Mockery::any(), '/a/b/c')->andReturns($this->plugin_list);

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
        $this->plugin_list
            ->shouldReceive('getAvailable')->andReturn(['a\b\c' => 'vendor/package'])
            ->shouldReceive('getAvailable')->andReturn(['c\d\e' => 'another-vendor/package']);

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
        $this->assertMatchesRegularExpression('/Usage:.*$\s+' . preg_quote($command, '/') . '\b/m', $output);
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
        $this->plugin_list->expects()->resolvePluginClass(Mockery::any())->andThrows(new InvalidArgumentException());

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
        $plugin_class = 'Vendor\Package\PluginClass';
        $this->plugin_list->expects()->resolvePluginClass('vendor/package')->andReturns($plugin_class);
        $this->plugin_list->expects()->isEnabled($plugin_class)->andReturns(true);

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
        $plugin_class = 'Vendor\Package\PluginClass';
        $this->plugin_list->expects()->resolvePluginClass('vendor/package')->andReturns($plugin_class);
        $this->plugin_list->expects()->isEnabled($plugin_class)->andReturns(false);
        $this->plugin_list->expects()->enable($plugin_class);

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
        $this->plugin_list->expects()->resolvePluginClass(Mockery::any())->andThrows(new InvalidArgumentException());

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
        $plugin_class = 'Vendor\Package\PluginClass';
        $this->plugin_list->expects()->resolvePluginClass('vendor/package')->andReturns($plugin_class);
        $this->plugin_list->expects()->isEnabled($plugin_class)->andReturns(false);

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
        $plugin_class = 'Vendor\Package\PluginClass';
        $this->plugin_list->expects()->resolvePluginClass('vendor/package')->andReturns($plugin_class);
        $this->plugin_list->expects()->isEnabled($plugin_class)->andReturns(true);
        $this->plugin_list->expects()->disable($plugin_class);

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
