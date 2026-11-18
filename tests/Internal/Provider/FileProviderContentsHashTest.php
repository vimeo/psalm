<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Provider;

use Override;
use PHPUnit\Framework\TestCase;
use Psalm\Internal\Provider\FakeFileProvider;
use Psalm\Internal\Provider\FileProvider;
use UnexpectedValueException;

use function chmod;
use function file_put_contents;
use function hash;
use function is_readable;
use function mkdir;
use function rmdir;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

use const DIRECTORY_SEPARATOR;

final class FileProviderContentsHashTest extends TestCase
{
    private string $directory;

    private string $path;

    private FileProvider $provider;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'psalm-contents-hash-' . uniqid('', true);
        mkdir($this->directory);
        $this->path = $this->directory . DIRECTORY_SEPARATOR . 'A.php';
        file_put_contents($this->path, 'on disk');
        $this->provider = new FileProvider();
    }

    #[Override]
    protected function tearDown(): void
    {
        $this->provider->closeFile($this->path);
        @chmod($this->path, 0644);
        @unlink($this->path);
        @rmdir($this->directory);

        parent::tearDown();
    }

    public function testHashOfTheContents(): void
    {
        self::assertSame(hash('xxh128', 'on disk'), $this->provider->getContentsHash($this->path));
        self::assertFalse($this->provider->isOpen($this->path));

        $this->provider->getContents($this->path);
        file_put_contents($this->path, 'changed on disk');

        self::assertSame(hash('xxh128', 'on disk'), $this->provider->getContentsHash($this->path));

        $this->provider->addTemporaryFileChanges($this->path, 'in the editor');

        self::assertSame(hash('xxh128', 'in the editor'), $this->provider->getContentsHash($this->path));
    }

    public function testMissingFile(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('should exist to get contents');

        $this->provider->getContentsHash($this->directory . DIRECTORY_SEPARATOR . 'Missing.php');
    }

    public function testDirectory(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('is a directory');

        $this->provider->getContentsHash($this->directory);
    }

    public function testUnreadableFile(): void
    {
        chmod($this->path, 0);

        if (is_readable($this->path)) {
            self::markTestSkipped('The file stays readable (running as root?)');
        }

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('could not be read');

        @$this->provider->getContentsHash($this->path);
    }

    public function testHashOfTheContentsOfAFakeFile(): void
    {
        $provider = new FakeFileProvider();

        self::assertSame(hash('xxh128', 'on disk'), $provider->getContentsHash($this->path));

        $provider->registerFile($this->path, 'fake');

        self::assertSame(hash('xxh128', 'fake'), $provider->getContentsHash($this->path));

        $provider->addTemporaryFileChanges($this->path, 'in the editor');

        self::assertSame(hash('xxh128', 'in the editor'), $provider->getContentsHash($this->path));
    }
}
