<?php
namespace Psalm;

interface FileSource
{
    public function getFileName(): string;

    public function getFilePath(): string;

    /**
     * @return string
     */
    public function getRootFileName();

    /**
     * @return string
     */
    public function getRootFilePath();

    public function getAliases(): Aliases;
}
