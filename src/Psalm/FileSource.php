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
    public function getRootFileName();

    /**
     * @return string
     */
    public function getRootFilePath();

    /**
     * @return Aliases
     */
    public function getAliases();
}
