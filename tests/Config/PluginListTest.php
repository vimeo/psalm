<?php
namespace Psalm\Tests\Config;

use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psalm\Config;
use Psalm\Internal\PluginManager\ComposerLock;
use Psalm\Internal\PluginManager\ConfigFile;
use Psalm\Internal\PluginManager\PluginList;
use Psalm\Internal\RuntimeCaches;

/** @group PluginManager */
class PluginListTest extends \Psalm\Tests\TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<ConfigFile> */
    private $config_file;

    /** @var ObjectProphecy<Config> */
    private $config;

    /** @var ObjectProphecy<ComposerLock> */
    private $composer_lock;

    public function setUp() : void
    {
        RuntimeCaches::clearAll();

        $this->config = $this->prophesize(Config::class);
        $this->config->getPluginClasses()->willReturn([]);

        $this->config_file = $this->prophesize(ConfigFile::class);
        $this->config_file->getConfig()->willReturn($this->config->reveal());

        $this->composer_lock = $this->prophesize(ComposerLock::class);
        $this->composer_lock->getPlugins()->willReturn([]);
    }

    /**
     * @test
     */
    public function pluginsPresentInConfigAreEnabled(): void
    {
        $this->config->getPluginClasses()->willReturn([
            ['class' => 'a\b\c', 'config' => null],
            ['class' => 'c\d\e', 'config' => null],
        ]);

        $plugin_list = new PluginList($this->config_file->reveal(), $this->composer_lock->reveal());

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
        $this->config->getPluginClasses()->willReturn([
            ['class' => 'a\b\c', 'config' => null],
        ]);

        $this->composer_lock->getPlugins()->willReturn([
            'vendor/package' => 'a\b\c',
            'another-vendor/another-package' => 'c\d\e',
        ]);

        $plugin_list = new PluginList($this->config_file->reveal(), $this->composer_lock->reveal());

        $this->assertSame([
            'c\d\e' => 'another-vendor/another-package',
        ], $plugin_list->getAvailable());
    }

    /**
     * @test
     */
    public function pluginsPresentInPackageLockAndConfigHavePluginPackageName(): void
    {
        $this->config->getPluginClasses()->willReturn([
            ['class' => 'a\b\c', 'config' => null],
        ]);

        $this->composer_lock->getPlugins()->willReturn([
            'vendor/package' => 'a\b\c',
        ]);

        $plugin_list = new PluginList($this->config_file->reveal(), $this->composer_lock->reveal());

        $this->assertSame([
            'a\b\c' => 'vendor/package',
        ], $plugin_list->getEnabled());
    }

    /**
     * @test
     */
    public function canFindPluginClassByClassName(): void
    {
        $plugin_list = new PluginList($this->config_file->reveal(), $this->composer_lock->reveal());
        $this->assertSame('a\b\c', $plugin_list->resolvePluginClass('a\b\c'));
    }

    /**
     * @test
     */
    public function canFindPluginClassByPackageName(): void
    {
        $this->composer_lock->getPlugins()->willReturn([
            'vendor/package' => 'a\b\c',
        ]);

        $plugin_list = new PluginList($this->config_file->reveal(), $this->composer_lock->reveal());
        $this->assertSame('a\b\c', $plugin_list->resolvePluginClass('vendor/package'));
    }

    /**
     * @test
     */
    public function canShowAvailablePluginsWithoutAConfigFile(): void
    {
        $this->composer_lock->getPlugins()->willReturn([
            'vendor/package' => 'a\b\c',
            'another-vendor/another-package' => 'c\d\e',
        ]);
        $plugin_list = new PluginList(null, $this->composer_lock->reveal());

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
        $this->config->getPluginClasses()->willReturn([
            ['class' => 'a\b\c', 'config' => null],
        ]);

        $plugin_list = new PluginList($this->config_file->reveal(), $this->composer_lock->reveal());

        $this->assertTrue($plugin_list->isEnabled('a\b\c'));
    }

    /**
     * @test
     */
    public function errorsOutWhenTryingToResolveUnknownPlugin(): void
    {
        $plugin_list = new PluginList($this->config_file->reveal(), $this->composer_lock->reveal());
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/unknown plugin/i');
        $plugin_list->resolvePluginClass('vendor/package');
    }

    /**
     * @test
     */
    public function pluginsAreEnabledInConfigFile(): void
    {
        $plugin_list = new PluginList($this->config_file->reveal(), $this->composer_lock->reveal());

        $this->config_file->addPlugin('a\b\c')->shouldBeCalled();

        $plugin_list->enable('a\b\c');
    }

    /**
     * @test
     */
    public function pluginsAreDisabledInConfigFile(): void
    {
        $plugin_list = new PluginList($this->config_file->reveal(), $this->composer_lock->reveal());

        $this->config_file->removePlugin('a\b\c')->shouldBeCalled();

        $plugin_list->disable('a\b\c');
    }
}
