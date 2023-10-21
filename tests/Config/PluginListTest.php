<?php

declare(strict_types=1);

namespace Psalm\Tests\Config;

use InvalidArgumentException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Psalm\Config;
use Psalm\Internal\PluginManager\ComposerLock;
use Psalm\Internal\PluginManager\ConfigFile;
use Psalm\Internal\PluginManager\PluginList;
use Psalm\Internal\RuntimeCaches;
use Psalm\Tests\TestCase;

/** @group PluginManager */
class PluginListTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private ConfigFile&MockInterface $config_file;

    private Config&MockInterface $config;

    private ComposerLock&MockInterface $composer_lock;

    public function setUp(): void
    {
        RuntimeCaches::clearAll();

        $this->config = Mockery::mock(Config::class);
        $this->config->allows()->getPluginClasses()->andReturns([])->byDefault();

        $this->config_file = Mockery::mock(ConfigFile::class);
        $this->config_file->allows()->getConfig()->andReturns($this->config)->byDefault();

        $this->composer_lock = Mockery::mock(ComposerLock::class);
        $this->composer_lock->allows()->getPlugins()->andReturns([])->byDefault();
    }

    /**
     * @test
     */
    public function pluginsPresentInConfigAreEnabled(): void
    {
        $this->config->expects()->getPluginClasses()->andReturns([
            ['class' => 'a\b\c', 'config' => null],
            ['class' => 'c\d\e', 'config' => null],
        ]);

        $plugin_list = new PluginList($this->config_file, $this->composer_lock);

        $this->assertSame([
            'a\b\c' => null,
            'c\d\e' => null,
        ], $plugin_list->getEnabled());
    }

    /**
     * @test
     */
    public function pluginsPresentInPackageLockOnlyAreAvailable(): void
    {
        $this->config->expects()->getPluginClasses()->andReturns([
            ['class' => 'a\b\c', 'config' => null],
        ]);

        $this->composer_lock->expects()->getPlugins()->andReturns([
            'vendor/package' => 'a\b\c',
            'another-vendor/another-package' => 'c\d\e',
        ]);

        $plugin_list = new PluginList($this->config_file, $this->composer_lock);

        $this->assertSame([
            'c\d\e' => 'another-vendor/another-package',
        ], $plugin_list->getAvailable());
    }

    /**
     * @test
     */
    public function pluginsPresentInPackageLockAndConfigHavePluginPackageName(): void
    {
        $this->config->expects()->getPluginClasses()->andReturns([
            ['class' => 'a\b\c', 'config' => null],
        ]);

        $this->composer_lock->expects()->getPlugins()->andReturns([
            'vendor/package' => 'a\b\c',
        ]);

        $plugin_list = new PluginList($this->config_file, $this->composer_lock);

        $this->assertSame([
            'a\b\c' => 'vendor/package',
        ], $plugin_list->getEnabled());
    }

    /**
     * @test
     */
    public function canFindPluginClassByClassName(): void
    {
        $plugin_list = new PluginList($this->config_file, $this->composer_lock);
        $this->assertSame('a\b\c', $plugin_list->resolvePluginClass('a\b\c'));
    }

    /**
     * @test
     */
    public function canFindPluginClassByPackageName(): void
    {
        $this->composer_lock->expects()->getPlugins()->andReturns([
            'vendor/package' => 'a\b\c',
        ]);

        $plugin_list = new PluginList($this->config_file, $this->composer_lock);
        $this->assertSame('a\b\c', $plugin_list->resolvePluginClass('vendor/package'));
    }

    /**
     * @test
     */
    public function canShowAvailablePluginsWithoutAConfigFile(): void
    {
        $this->composer_lock->expects()->getPlugins()->andReturns([
            'vendor/package' => 'a\b\c',
            'another-vendor/another-package' => 'c\d\e',
        ]);
        $plugin_list = new PluginList(null, $this->composer_lock);

        $this->assertSame([
            'a\b\c' => 'vendor/package',
            'c\d\e' => 'another-vendor/another-package',
        ], $plugin_list->getAvailable());
    }

    /**
     * @test
     */
    public function enabledPackageIsEnabled(): void
    {
        $this->config->expects()->getPluginClasses()->andReturns([
            ['class' => 'a\b\c', 'config' => null],
        ]);

        $plugin_list = new PluginList($this->config_file, $this->composer_lock);

        $this->assertTrue($plugin_list->isEnabled('a\b\c'));
    }

    /**
     * @test
     */
    public function errorsOutWhenTryingToResolveUnknownPlugin(): void
    {
        $plugin_list = new PluginList($this->config_file, $this->composer_lock);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/unknown plugin/i');
        $plugin_list->resolvePluginClass('vendor/package');
    }

    /**
     * @test
     */
    public function pluginsAreEnabledInConfigFile(): void
    {
        $plugin_list = new PluginList($this->config_file, $this->composer_lock);

        $this->config_file->expects()->addPlugin('a\b\c');

        $plugin_list->enable('a\b\c');
    }

    /**
     * @test
     */
    public function pluginsAreDisabledInConfigFile(): void
    {
        $plugin_list = new PluginList($this->config_file, $this->composer_lock);

        $this->config_file->expects()->removePlugin('a\b\c');

        $plugin_list->disable('a\b\c');
    }
}
