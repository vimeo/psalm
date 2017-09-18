<?php
namespace Psalm\Provider;

class FileProvider
{
    /**
     * @param  string  $file_path
     *
     * @return string
     */
    public function getContents($file_path)
    {
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
        file_put_contents($file_path, $file_contents);
    }

    /**
     * @param  string $file_path
     *
     * @return int
     */
    public function getModifiedTime($file_path)
    {
        return (int)filemtime($file_path);
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
}
