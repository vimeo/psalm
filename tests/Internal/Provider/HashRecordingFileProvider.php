<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Provider;

use Override;
use Psalm\Internal\Provider\FileProvider;

use function hash;

/**
 * Files that exist with the given contents, as far as fileExists() and getContentsHash() tell, which records the files
 * whose contents it hashes
 */
final class HashRecordingFileProvider extends FileProvider
{
    /** @var list<string> */
    public array $hashed = [];

    /**
     * @param array<string, string> $files
     */
    public function __construct(private readonly array $files)
    {
    }

    #[Override]
    public function fileExists(string $file_path): bool
    {
        return isset($this->files[$file_path]) || parent::fileExists($file_path);
    }

    #[Override]
    public function getContentsHash(string $file_path): string
    {
        $this->hashed[] = $file_path;

        return isset($this->files[$file_path])
            ? hash('xxh128', $this->files[$file_path])
            : parent::getContentsHash($file_path);
    }
}
