<?php
namespace Psalm\Tests;

use Prophecy\Prophecy\ObjectProphecy;
use Psalm\Config;
use Psalm\PluginManager\ComposerLock;
use Psalm\PluginManager\ConfigFile;
use Psalm\PluginManager\PluginList;

class PluginManagerTest extends TestCase
{
    /** @var ObjectProphecy */
    private $config_file;
    /** @var ObjectProphecy */
    private $config;
    /** @var ObjectProphecy */
    private $composer_lock;

    public function setUp()
    {
        $this->config = $this->prophesize(Config::class);
        /** @psalm-suppress TooManyArguments willReturn is old-school variadic, see vimeo/psalm#605 */
        $this->config->getPluginClasses()->willReturn([]);

        $this->config_file = $this->prophesize(ConfigFile::class);
        /** @psalm-suppress TooManyArguments willReturn is old-school variadic, see vimeo/psalm#605 */
        $this->config_file->getConfig()->willReturn($this->config->reveal());

        $this->composer_lock = $this->prophesize(ComposerLock::class);
        /** @psalm-suppress TooManyArguments willReturn is old-school variadic, see vimeo/psalm#605 */
        $this->composer_lock->getPlugins()->willReturn([]);
    }

    /**
     * @return void
     * @test
     */
    public function pluginsPresentInConfigAreEnabled()
    {
        /** @psalm-suppress TooManyArguments willReturn is old-school variadic, see vimeo/psalm#605 */
        $this->config->getPluginClasses()->willReturn([
            ['class' => 'a\b\c', 'config' => null],
            ['class' => 'c\d\e', 'config' => null],
        ]);

        $plugin_list = new PluginList($this->config_file->reveal(), $this->composer_lock->reveal());

        $this->assertEquals([
            'a\b\c' => null,
            'c\d\e' => null,
        ], $plugin_list->getEnabled());
    }

    /**
     * @return void
     * @test
     */
    public function pluginsPresentInPackageLockOnlyAreAvailable()
    {
        /** @psalm-suppress TooManyArguments willReturn is old-school variadic, see vimeo/psalm#605 */
        $this->config->getPluginClasses()->willReturn([
            ['class' => 'a\b\c', 'config' => null],
        ]);

        /** @psalm-suppress TooManyArguments willReturn is old-school variadic, see vimeo/psalm#605 */
        $this->composer_lock->getPlugins()->willReturn([
            'vendor/package' => 'a\b\c',
            'another-vendor/another-package' => 'c\d\e',
        ]);

        $plugin_list = new PluginList($this->config_file->reveal(), $this->composer_lock->reveal());

        $this->assertEquals([
            'c\d\e' => 'another-vendor/another-package',
        ], $plugin_list->getAvailable());
    }

    /**
     * @return void
     * @test
     */
    public function pluginsPresentInPackageLockAndConfigHavePluginPackageName()
    {
        /** @psalm-suppress TooManyArguments willReturn is old-school variadic, see vimeo/psalm#605 */
        $this->config->getPluginClasses()->willReturn([
            ['class' => 'a\b\c', 'config' => null],
        ]);

        /** @psalm-suppress TooManyArguments willReturn is old-school variadic, see vimeo/psalm#605 */
        $this->composer_lock->getPlugins()->willReturn([
            'vendor/package' => 'a\b\c',
        ]);

        $plugin_list = new PluginList($this->config_file->reveal(), $this->composer_lock->reveal());

        $this->assertEquals([
            'a\b\c' => 'vendor/package',
        ], $plugin_list->getEnabled());
    }

    /**
     * @return void
     * @test
     */
    public function canFindPluginClassByClassName()
    {
        $plugin_list = new PluginList($this->config_file->reveal(), $this->composer_lock->reveal());
        $this->assertEquals('a\b\c', $plugin_list->resolvePluginClass('a\b\c'));
    }

    /**
     * @return void
     * @test
     */
    public function canFindPluginClassByPackageName()
    {
        /** @psalm-suppress TooManyArguments willReturn is old-school variadic, see vimeo/psalm#605 */
        $this->composer_lock->getPlugins()->willReturn([
            'vendor/package' => 'a\b\c',
        ]);

        $plugin_list = new PluginList($this->config_file->reveal(), $this->composer_lock->reveal());
        $this->assertEquals('a\b\c', $plugin_list->resolvePluginClass('vendor/package'));
    }

    /**
     * @return void
     * @test
     */
    public function enabledPackageIsEnabled()
    {
        /** @psalm-suppress TooManyArguments willReturn is old-school variadic, see vimeo/psalm#605 */
        $this->config->getPluginClasses()->willReturn([
            ['class' => 'a\b\c', 'config' => null],
        ]);

        $plugin_list = new PluginList($this->config_file->reveal(), $this->composer_lock->reveal());

        $this->assertTrue($plugin_list->isEnabled('a\b\c'));
    }

    /**
     * @return void
     * @test
     */
    public function errorsOutWhenTryingToResolveUnknownPlugin()
    {
        $plugin_list = new PluginList($this->config_file->reveal(), $this->composer_lock->reveal());
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/unknown plugin/i');
        $plugin_list->resolvePluginClass('vendor/package');
    }

    /**
     * @return void
     * @test
     */
    public function pluginsAreEnabledInConfigFile()
    {
        $plugin_list = new PluginList($this->config_file->reveal(), $this->composer_lock->reveal());

        $this->config_file->addPlugin('a\b\c')->shouldBeCalled();

        $plugin_list->enable('a\b\c');
    }

    /**
     * @return void
     * @test
     */
    public function pluginsAreDisabledInConfigFile()
    {
        $plugin_list = new PluginList($this->config_file->reveal(), $this->composer_lock->reveal());

        $this->config_file->removePlugin('a\b\c')->shouldBeCalled();

        $plugin_list->disable('a\b\c');
    }
}
