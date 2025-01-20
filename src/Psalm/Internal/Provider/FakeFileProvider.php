<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider;

use function microtime;
use function str_starts_with;

/**
 * @internal
 */
final class FakeFileProvider extends FileProvider
{
    /**
     * @var array<string, string>
     */
    public array $fake_files = [];

    /**
     * @var array<string, int>
     */
    public array $fake_file_times = [];

    /**
     * @var array<string, true>
     */
    public array $fake_directories = [];

    public function fileExists(string $file_path): bool
    {
        return isset($this->fake_files[$file_path]) || parent::fileExists($file_path);
    }

    public function isDirectory(string $file_path): bool
    {
        return isset($this->fake_directories[$file_path]) || parent::isDirectory($file_path);
    }

    /** @psalm-external-mutation-free */
    public function getContents(string $file_path, bool $go_to_source = false): string
    {
        if (!$go_to_source && isset($this->temp_files[$file_path])) {
            return $this->temp_files[$file_path]['content'];
        }

        return $this->fake_files[$file_path] ?? parent::getContents($file_path);
    }

    public function setContents(string $file_path, string $file_contents): void
    {
        $this->fake_files[$file_path] = $file_contents;
    }

    public function setOpenContents(string $file_path, ?string $file_contents = null): void
    {
        if (isset($this->fake_files[$file_path])) {
            $this->fake_files[$file_path] = $file_contents ?? $this->getContents($file_path, true);
        }
    }

    public function getModifiedTime(string $file_path): int
    {
        return $this->fake_file_times[$file_path] ?? parent::getModifiedTime($file_path);
    }

    public function registerFile(string $file_path, string $file_contents): void
    {
        $this->fake_files[$file_path] = $file_contents;
        $this->fake_file_times[$file_path] = (int)microtime(true);
    }

    public function deleteFile(string $file_path): void
    {
        unset($this->fake_files[$file_path]);
        unset($this->fake_file_times[$file_path]);
    }

    /**
     * @param array<string> $file_extensions
     * @param null|callable(string):bool $filter
     * @return list<string>
     */
    public function getFilesInDir(string $dir_path, array $file_extensions, ?callable $filter = null): array
    {
        $file_paths = parent::getFilesInDir($dir_path, $file_extensions, $filter);

        foreach ($this->fake_files as $file_path => $_) {
            if (str_starts_with($file_path, $dir_path)) {
                $file_paths[] = $file_path;
            }
        }

        return $file_paths;
    }
}
