<?php
namespace Psalm;

interface FileSource
{
    public function getFileName(): string;

    public function getFilePath(): string;

    /**
     * @return string
     */
    public function getRootFileName(): string;

    /**
     * @return string
     */
    public function getRootFilePath(): string;

    public function getAliases(): Aliases;
}
