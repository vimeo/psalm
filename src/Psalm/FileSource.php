<?php
namespace Psalm;

interface FileSource
{
    /**
     * @return string
     */
    public function getFileName(): string;

    /**
     * @return string
     */
    public function getFilePath(): string;

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
    public function getAliases(): Aliases;
}
