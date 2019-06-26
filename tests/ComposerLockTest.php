<?php
namespace Psalm\Tests;

use Psalm\Internal\PluginManager\ComposerLock;
use function json_encode;

/** @group PluginManager */
class ComposerLockTest extends TestCase
{
    /**
     * @return void
     * @test
     */
    public function pluginIsPackageOfTypePsalmPlugin()
    {
        $lock = new ComposerLock([$this->jsonFile((object)[])]);
        $this->assertTrue($lock->isPlugin($this->pluginEntry('vendor/package', 'Some\Class')));
        // counterexamples

        $this->assertFalse($lock->isPlugin([]), 'Non-package should not be considered a plugin');

        $this->assertFalse($lock->isPlugin([
            'name' => 'vendor/package',
            'type' => 'library',
        ]), 'Non-plugin should not be considered a plugin');

        $this->assertFalse($lock->isPlugin([
            'name' => 'vendor/package',
            'type' => 'psalm-plugin',
        ]), 'Invalid plugin should not be considered a plugin');
    }

    /**
     * @return void
     * @test
     */
    public function seesNonDevPlugins()
    {
        $lock = new ComposerLock([$this->jsonFile((object)[
            'packages' => [
                (object)$this->pluginEntry('vendor/package', 'Vendor\Package\PluginClass')
            ],
            'packages-dev' => [],
        ])]);

        $plugins = $lock->getPlugins();
        $this->assertArrayHasKey('vendor/package', $plugins);
        $this->assertSame('Vendor\Package\PluginClass', $plugins['vendor/package']);
    }

    /**
     * @return void
     * @test
     */
    public function seesDevPlugins()
    {
        $lock = new ComposerLock([$this->jsonFile((object)[
            'packages' => [],
            'packages-dev' => [
                (object) $this->pluginEntry('vendor/package', 'Vendor\Package\PluginClass')
            ],
        ])]);

        $plugins = $lock->getPlugins();
        $this->assertArrayHasKey('vendor/package', $plugins);
        $this->assertSame('Vendor\Package\PluginClass', $plugins['vendor/package']);
    }

    /**
     * @return void
     * @test
     */
    public function skipsNonPlugins()
    {
        $nonPlugin = (object)[
            'name' => 'vendor/package',
            'type' => 'library',
        ];

        $lock = new ComposerLock([$this->jsonFile((object)[
            'packages' => [$nonPlugin],
            'packages-dev' => [$nonPlugin],
        ])]);
        $this->assertEmpty($lock->getPlugins());
    }

    /**
     * @return void
     * @test
     */
    public function failsOnInvalidJson()
    {
        $lock = new ComposerLock(['data:application/json,[']);

        $this->expectException(\RuntimeException::class);
        $lock->getPlugins();
    }

    /**
     * @return void
     * @test
     */
    public function failsOnNonObjectJson()
    {
        $lock = new ComposerLock(['data:application/json,null']);

        $this->expectException(\RuntimeException::class);
        $lock->getPlugins();
    }

    /**
     * @return void
     * @test
     */
    public function failsOnMissingPackagesEntry()
    {
        $noPackagesFile = $this->jsonFile((object)[
            'packages-dev' => [],
        ]);
        $lock = new ComposerLock([$noPackagesFile]);
        $this->expectException(\RuntimeException::class);
        $lock->getPlugins();
    }

    /**
     * @return void
     * @test
     */
    public function failsOnMissingPackagesDevEntry()
    {
        $noPackagesDevFile = $this->jsonFile((object)[
            'packages' => [],
        ]);
        $lock = new ComposerLock([$noPackagesDevFile]);
        $this->expectException(\RuntimeException::class);
        $lock->getPlugins();
    }

    /** @test */
    public function mergesMultipleComposerLockFiles(): void
    {
        $lock = new ComposerLock([
            $this->jsonFile([
                'packages' => [
                    (object) $this->pluginEntry('vendor/packageA', 'Vendor\PackageA\PluginClass')
                ],
                'packages-dev' => [
                    (object) $this->pluginEntry('vendor/packageB', 'Vendor\PackageB\PluginClass')
                ],
            ]),
            $this->jsonFile([
                'packages' => [
                    (object) $this->pluginEntry('vendor/packageC', 'Vendor\PackageC\PluginClass')
                ],
                'packages-dev' => [
                    (object) $this->pluginEntry('vendor/packageD', 'Vendor\PackageD\PluginClass')
                ],
            ]),
        ]);

        $this->assertEquals(
            [
                'vendor/packageA' => 'Vendor\PackageA\PluginClass',
                'vendor/packageB' => 'Vendor\PackageB\PluginClass',
                'vendor/packageC' => 'Vendor\PackageC\PluginClass',
                'vendor/packageD' => 'Vendor\PackageD\PluginClass',
            ],
            $lock->getPlugins()
        );
    }

    private function pluginEntry(string $package_name, string $package_class): array
    {
        return [
            'name' => $package_name,
            'type' => 'psalm-plugin',
            'extra' => [
                'psalm' => [
                    'pluginClass' => $package_class,
                ],
            ],
        ];
    }

    /** @param mixed $data */
    private function jsonFile($data): string
    {
        return 'data:application/json,' . json_encode($data);
    }
}
