<?php
namespace Psalm\Tests\Provider;

class FakeFileProvider extends \Psalm\Provider\FileProvider
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
    public function getContents($file_path)
    {
        if (isset($this->fake_files[$file_path])) {
            return $this->fake_files[$file_path];
        }

        return parent::getContents($file_path);
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
     * @psalm-suppress InvalidPropertyAssignment because microtime is needed for cache busting
     */
    public function registerFile($file_path, $file_contents)
    {
        $this->fake_files[$file_path] = $file_contents;
        $this->fake_file_times[$file_path] = (float)microtime(true);
    }
}
