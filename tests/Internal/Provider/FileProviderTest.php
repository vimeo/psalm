<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Provider;

use Psalm\Internal\Provider\FileProvider;
use Psalm\Tests\TestCase;

use function file_put_contents;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

final class FileProviderTest extends TestCase
{
    public function testSizeOfFileOnDiskIsItsLengthInBytes(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'psalm-size');
        self::assertNotFalse($path);

        try {
            file_put_contents($path, '<?php // é');

            self::assertSame(11, (new FileProvider())->getSize($path));
        } finally {
            unlink($path);
        }
    }

    public function testSizeOfMissingFileIsZero(): void
    {
        self::assertSame(0, (new FileProvider())->getSize('/does/not/exist.php'));
    }

    public function testSizeOfFileWithTemporaryChangesIsThatOfTheChangedContents(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'psalm-size');
        self::assertNotFalse($path);

        try {
            file_put_contents($path, 'short');

            $provider = new FileProvider();
            $provider->addTemporaryFileChanges($path, 'a much longer unsaved content');

            self::assertSame(29, $provider->getSize($path));
        } finally {
            unlink($path);
        }
    }
}
