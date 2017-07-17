<?php
namespace Psalm\Provider;

use PhpParser;
use Psalm\Checker\ProjectChecker;

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

    /**
     * @param  string  $file_path
     *
     * @return bool
     */
    public function hasFileChanged($file_path)
    {
        return $this->getModifiedTime($file_path) > CacheProvider::getLastGoodRun();
    }
}
