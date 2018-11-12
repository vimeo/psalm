<?php
namespace Psalm\Tests\Internal\Provider;

class FakeFileProvider extends \Psalm\Internal\Provider\FileProvider
{
    /**
     * @var array<string, string>
     */
    public $fake_files = [];

    /**
     * @var array<string, int>
     */
    public $fake_file_times = [];

    /**
     * @param  string $file_path
     *
     * @return bool
     */
    public function fileExists($file_path)
    {
        return isset($this->fake_files[$file_path]) || parent::fileExists($file_path);
    }

    /**
     * @param  string $file_path
     *
     * @return string
     */
    public function getContents($file_path, bool $go_to_source = false)
    {
        if (!$go_to_source && isset($this->temp_files[strtolower($file_path)])) {
            return $this->temp_files[strtolower($file_path)];
        }

        if (isset($this->fake_files[$file_path])) {
            return $this->fake_files[$file_path];
        }

        return parent::getContents($file_path);
    }

    /**
     * @param  string  $file_path
     * @param  string  $file_contents
     *
     * @return void
     */
    public function setContents($file_path, $file_contents)
    {
        $this->fake_files[$file_path] = $file_contents;
    }

    /**
     * @param  string $file_path
     *
     * @return int
     */
    public function getModifiedTime($file_path)
    {
        if (isset($this->fake_file_times[$file_path])) {
            return $this->fake_file_times[$file_path];
        }

        return parent::getModifiedTime($file_path);
    }

    /**
     * @param  string $file_path
     * @param  string $file_contents
     *
     * @return void
     * @psalm-suppress InvalidPropertyAssignmentValue because microtime is needed for cache busting
     */
    public function registerFile($file_path, $file_contents)
    {
        $this->fake_files[$file_path] = $file_contents;
        $this->fake_file_times[$file_path] = microtime(true);
    }

    /**
     * @param string $dir_path
     * @param array<string> $file_extensions
     *
     * @return array<int, string>
     */
    public function getFilesInDir($dir_path, array $file_extensions)
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
