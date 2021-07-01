<?php

namespace Psalm\Internal\Provider;

use function microtime;
use function strpos;
use function strtolower;

class FakeFileProvider extends FileProvider
{
    /**
     * @var array<string, string>
     */
    public $fake_files = [];

    /**
     * @var array<string, int>
     */
    public $fake_file_times = [];

    public function fileExists(string $file_path): bool
    {
        return isset($this->fake_files[$file_path]) || parent::fileExists($file_path);
    }

    public function getContents(string $file_path, bool $go_to_source = false): string
    {
        if (!$go_to_source && isset($this->temp_files[strtolower($file_path)])) {
            return $this->temp_files[strtolower($file_path)];
        }

        if (isset($this->fake_files[$file_path])) {
            return $this->fake_files[$file_path];
        }

        return parent::getContents($file_path);
    }

    public function setContents(string $file_path, string $file_contents): void
    {
        $this->fake_files[$file_path] = $file_contents;
    }

    public function setOpenContents(string $file_path, string $file_contents): void
    {
        if (isset($this->fake_files[strtolower($file_path)])) {
            $this->fake_files[strtolower($file_path)] = $file_contents;
        }
    }

    public function getModifiedTime(string $file_path): int
    {
        if (isset($this->fake_file_times[$file_path])) {
            return $this->fake_file_times[$file_path];
        }

        return parent::getModifiedTime($file_path);
    }

    /**
     * @psalm-suppress InvalidPropertyAssignmentValue because microtime is needed for cache busting
     */
    public function registerFile(string $file_path, string $file_contents): void
    {
        $this->fake_files[$file_path] = $file_contents;
        $this->fake_file_times[$file_path] = microtime(true);
    }

    /**
     * @param array<string> $file_extensions
     *
     * @return list<string>
     */
    public function getFilesInDir(string $dir_path, array $file_extensions): array
    {
        $file_paths = parent::getFilesInDir($dir_path, $file_extensions);

        foreach ($this->fake_files as $file_path => $_) {
            if (strpos(strtolower($file_path), strtolower($dir_path)) === 0) {
                $file_paths[] = $file_path;
            }
        }

        return $file_paths;
    }
}
