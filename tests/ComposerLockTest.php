<?php
namespace Psalm\Tests;

use Psalm\Internal\PluginManager\ComposerLock;

/** @group PluginManager */
class ComposerLockTest extends TestCase
{
    /**
     * @return void
     * @test
     */
    public function pluginIsPackageOfTypePsalmPlugin()
    {
        $lock = new ComposerLock($this->jsonFile((object)[]));
        $this->assertTrue($lock->isPlugin([
            'name' => 'vendor/package',
            'type' => 'psalm-plugin',
            'extra' => [
                'psalm' => [
                    'pluginClass' => 'Some\Class',
                ]
            ]
        ]));

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
        $lock = new ComposerLock($this->jsonFile((object)[
            'packages' => [
                (object)[
                    'name' => 'vendor/package',
                    'type' => 'psalm-plugin',
                    'extra' => (object)[
                        'psalm' => (object) [
                            'pluginClass' => 'Vendor\Package\PluginClass',
                        ]
                    ],
                ],
            ],
            'packages-dev' => [],
        ]));

        $plugins = $lock->getPlugins();
        $this->assertArrayHasKey('vendor/package', $plugins);
        $this->assertEquals('Vendor\Package\PluginClass', $plugins['vendor/package']);
    }

    /**
     * @return void
     * @test
     */
    public function seesDevPlugins()
    {
        $lock = new ComposerLock($this->jsonFile((object)[
            'packages' => [],
            'packages-dev' => [
                (object)[
                    'name' => 'vendor/package',
                    'type' => 'psalm-plugin',
                    'extra' => (object)[
                        'psalm' => (object)[
                            'pluginClass' => 'Vendor\Package\PluginClass',
                        ]
                    ],
                ],
            ],
        ]));

        $plugins = $lock->getPlugins();
        $this->assertArrayHasKey('vendor/package', $plugins);
        $this->assertEquals('Vendor\Package\PluginClass', $plugins['vendor/package']);
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

        $lock = new ComposerLock($this->jsonFile((object)[
            'packages' => [ $nonPlugin ],
            'packages-dev' => [ $nonPlugin ],
        ]));
        $this->assertEmpty($lock->getPlugins());
    }

    /**
     * @return void
     * @test
     */
    public function failsOnInvalidJson()
    {
        $lock = new ComposerLock('data:application/json,[');

        $this->expectException(\RuntimeException::class);
        $lock->getPlugins();
    }

    /**
     * @return void
     * @test
     */
    public function failsOnNonObjectJson()
    {
        $lock = new ComposerLock('data:application/json,null');

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
        $lock = new ComposerLock($noPackagesFile);
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
        $lock = new ComposerLock($noPackagesDevFile);
        $this->expectException(\RuntimeException::class);
        $lock->getPlugins();
    }

    /** @param mixed $data */
    private function jsonFile($data): string
    {
        return 'data:application/json,' . json_encode($data);
    }
}
