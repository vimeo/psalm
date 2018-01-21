<?php
namespace Psalm;

interface FileSource
{
    /**
     * @return string
     */
    public function getFileName();

    /**
     * @return string
     */
    public function getFilePath();

    /**
     * @return string
     */
    public function getCheckedFileName();

    /**
     * @return string
     */
    public function getCheckedFilePath();
}
