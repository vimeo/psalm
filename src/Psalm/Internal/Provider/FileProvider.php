<?php
namespace Psalm\Internal\Provider;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use UnexpectedValueException;

use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function filemtime;
use function in_array;
use function is_dir;
use function strtolower;

class FileProvider
{
    /**
     * @var array<lowercase-string, string>
     */
    protected $temp_files = [];

    /**
     * @var array<lowercase-string, string>
     */
    protected $open_files = [];

    public function getContents(string $file_path, bool $go_to_source = false): string
    {
        $file_path_lc = strtolower($file_path);
        if (!$go_to_source && isset($this->temp_files[$file_path_lc])) {
            return $this->temp_files[$file_path_lc];
        }

        if (isset($this->open_files[$file_path_lc])) {
            return $this->open_files[$file_path_lc];
        }

        if (!file_exists($file_path)) {
            throw new UnexpectedValueException('File ' . $file_path . ' should exist to get contents');
        }

        if (is_dir($file_path)) {
            throw new UnexpectedValueException('File ' . $file_path . ' is a directory');
        }

        return (string)file_get_contents($file_path);
    }

    public function setContents(string $file_path, string $file_contents): void
    {
        $file_path_lc = strtolower($file_path);
        if (isset($this->open_files[$file_path_lc])) {
            $this->open_files[$file_path_lc] = $file_contents;
        }

        if (isset($this->temp_files[$file_path_lc])) {
            $this->temp_files[$file_path_lc] = $file_contents;
        }

        file_put_contents($file_path, $file_contents);
    }

    public function setOpenContents(string $file_path, string $file_contents): void
    {
        $file_path_lc = strtolower($file_path);
        if (isset($this->open_files[$file_path_lc])) {
            $this->open_files[$file_path_lc] = $file_contents;
        }
    }

    public function getModifiedTime(string $file_path): int
    {
        if (!file_exists($file_path)) {
            throw new UnexpectedValueException('File should exist to get modified time');
        }

        return (int)filemtime($file_path);
    }

    public function addTemporaryFileChanges(string $file_path, string $new_content): void
    {
        $this->temp_files[strtolower($file_path)] = $new_content;
    }

    public function removeTemporaryFileChanges(string $file_path): void
    {
        unset($this->temp_files[strtolower($file_path)]);
    }

    public function openFile(string $file_path): void
    {
        $this->open_files[strtolower($file_path)] = $this->getContents($file_path, true);
    }

    public function isOpen(string $file_path): bool
    {
        $file_path_lc = strtolower($file_path);
        return isset($this->temp_files[$file_path_lc]) || isset($this->open_files[$file_path_lc]);
    }

    public function closeFile(string $file_path): void
    {
        $file_path_lc = strtolower($file_path);
        unset($this->temp_files[$file_path_lc], $this->open_files[$file_path_lc]);
    }

    public function fileExists(string $file_path): bool
    {
        return file_exists($file_path);
    }

    /**
     * @param array<string> $file_extensions
     *
     * @return list<string>
     */
    public function getFilesInDir(string $dir_path, array $file_extensions): array
    {
        $file_paths = [];

        /** @var RecursiveDirectoryIterator */
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir_path));
        $iterator->rewind();

        while ($iterator->valid()) {
            if (!$iterator->isDot()) {
                $extension = $iterator->getExtension();
                if (in_array($extension, $file_extensions, true)) {
                    $file_paths[] = (string)$iterator->getRealPath();
                }
            }

            $iterator->next();
        }

        return $file_paths;
    }
}
