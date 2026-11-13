<?php

declare(strict_types=1);

namespace Psalm\Tests\Cache;

use Psalm\Config;
use Psalm\Internal\Cache;
use Psalm\Internal\Provider\ClassLikeStorageCacheProvider;
use Psalm\Internal\Provider\FileStorageCacheProvider;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\FileStorage;
use Psalm\Storage\PropertyHookStorage;
use Psalm\Storage\PropertyStorage;
use Psalm\Tests\TestCase;
use Psalm\Type;

use function dirname;
use function filemtime;
use function hash;
use function sys_get_temp_dir;
use function uniqid;

use const DIRECTORY_SEPARATOR;

final class PropertyHookStorageCacheTest extends TestCase
{
    public function testStoragesWithoutSetterParameterTypesAreInvalidated(): void
    {
        $cache_directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('psalm-hook-storage-', true);
        $config = Config::loadFromXML(
            __DIR__ . DIRECTORY_SEPARATOR . 'test_base_dir',
            <<<XML
                <?xml version="1.0"?>
                <psalm cacheDirectory="{$cache_directory}">
                    <projectFiles><directory name="src" /></projectFiles>
                </psalm>
                XML,
        );
        $config->array_cache = false;
        $storage_dir = dirname(__DIR__, 2) . '/src/Psalm/Storage/';
        $dependencies = [''];
        foreach (['FileStorage', 'FunctionLikeStorage', 'ClassLikeStorage', 'MethodStorage'] as $class) {
            $dependencies[] = filemtime($storage_dir . $class . '.php');
        }

        $classes = $files = $old_classes = $old_files = null;
        try {
            $path = $config->base_dir . '/src/contract.php';
            $code = '<?php interface Contract { public string $value { set(string|array $value); } }';
            $storage = new ClassLikeStorage('Contract');
            $property = new PropertyStorage();
            $property->hook_set = new PropertyHookStorage(false, false, false, null);
            $storage->properties['value'] = $property;
            $file_storage = new FileStorage($path);

            // Seed the pre-hook-schema cache, including the file entry that would otherwise skip scanning.
            $old_classes = new Cache($config, 'classlike_cache', $dependencies);
            $old_classes->saveItem($path . "\0contract", $storage, hash('xxh128', $code));
            $dependencies[] = filemtime($storage_dir . 'FunctionLikeParameter.php');
            $old_files = new Cache($config, 'file_cache', $dependencies);
            $old_files->saveItem($path, $file_storage, hash('xxh128', $code));
            $old_classes = $old_files = null;

            $classes = new ClassLikeStorageCacheProvider($config, '');
            $files = new FileStorageCacheProvider($config, '');
            self::assertNull($classes->getLatestFromCache('contract', $path, $code));
            self::assertNull($files->getLatestFromCache($path, $code));

            $property->hook_set->param_type = Type::parseString('string|array');
            $classes->writeToCache($storage, $path, $code);
            $files->writeToCache($file_storage, $code);
            unset($classes, $files);

            $classes = new ClassLikeStorageCacheProvider($config, '');
            $files = new FileStorageCacheProvider($config, '');
            self::assertSame(
                'array<array-key, mixed>|string',
                $classes->getLatestFromCache('contract', $path, $code)?->properties['value']->hook_set?->param_type?->getId(),
            );
            self::assertNotNull($files->getLatestFromCache($path, $code));
        } finally {
            unset($classes, $files, $old_classes, $old_files);
            Config::removeCacheDirectory($cache_directory);
        }
    }
}
