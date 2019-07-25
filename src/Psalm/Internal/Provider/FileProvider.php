<?php
namespace Psalm\Internal\Provider;

use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function filemtime;
use function in_array;
use function strtolower;

class FileProvider
{
    /**
     * @var array<string, string>
     */
    protected $temp_files = [];

    /**
     * @var array<string, string>
     */
    protected $open_files = [];

    /**
     * @param  string  $file_path
     *
     * @return string
     */
    public function getContents($file_path, bool $go_to_source = false)
    {
        if (!$go_to_source && isset($this->temp_files[strtolower($file_path)])) {
            return $this->temp_files[strtolower($file_path)];
        }

        if (isset($this->open_files[strtolower($file_path)])) {
            return $this->open_files[strtolower($file_path)];
        }

        if (!file_exists($file_path)) {
            throw new \UnexpectedValueException('File ' . $file_path . ' should exist to get contents');
        }

        return (string)file_get_contents($file_path);
    }

    /**
     * @param  string  $file_path
     * @param  string  $file_contents
     *
     * @return void
     */
    public function setContents($file_path, $file_contents)
    {
        if (isset($this->open_files[strtolower($file_path)])) {
            $this->open_files[strtolower($file_path)] = $file_contents;
        }

        if (isset($this->temp_files[strtolower($file_path)])) {
            $this->temp_files[strtolower($file_path)] = $file_contents;
        }

        file_put_contents($file_path, $file_contents);
    }

    /**
     * @param  string  $file_path
     * @param  string  $file_contents
     *
     * @return void
     */
    public function setOpenContents($file_path, $file_contents)
    {
        if (isset($this->open_files[strtolower($file_path)])) {
            $this->open_files[strtolower($file_path)] = $file_contents;
        }
    }

    /**
     * @param  string $file_path
     *
     * @return int
     */
    public function getModifiedTime($file_path)
    {
        if (!file_exists($file_path)) {
            throw new \UnexpectedValueException('File should exist to get modified time');
        }

        return (int)filemtime($file_path);
    }

    /**
     * @return void
     */
    public function addTemporaryFileChanges(string $file_path, string $new_content)
    {
        $this->temp_files[strtolower($file_path)] = $new_content;
    }

    /**
     * @return void
     */
    public function removeTemporaryFileChanges(string $file_path)
    {
        unset($this->temp_files[strtolower($file_path)]);
    }

    /**
     * @return  void
     */
    public function openFile(string $file_path)
    {
        $this->open_files[strtolower($file_path)] = $this->getContents($file_path, true);
    }

    /**
     * @return bool
     */
    public function isOpen(string $file_path)
    {
        return isset($this->temp_files[strtolower($file_path)]) || isset($this->open_files[strtolower($file_path)]);
    }

    /**
     * @return  void
     */
    public function closeFile(string $file_path)
    {
        unset($this->temp_files[strtolower($file_path)], $this->open_files[strtolower($file_path)]);
    }

    /**
     * @param  string $file_path
     *
     * @return bool
     */
    public function fileExists($file_path)
    {
        return file_exists($file_path);
    }

    /**
     * @param string $dir_path
     * @param array<string> $file_extensions
     *
     * @return array<int, string>
     */
    public function getFilesInDir($dir_path, array $file_extensions)
    {
        $file_paths = [];

        /** @var \RecursiveDirectoryIterator */
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir_path));
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
