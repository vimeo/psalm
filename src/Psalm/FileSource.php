<?php

namespace Psalm;

interface FileSource
{
    /**
     * @psalm-mutation-free
     */
    public function getFileName(): string;

    /**
     * @psalm-mutation-free
     */
    public function getFilePath(): string;

    /**
     * @psalm-mutation-free
     */
    public function getRootFileName(): string;

    /**
     * @psalm-mutation-free
     */
    public function getRootFilePath(): string;

    /**
     * @psalm-mutation-free
     */
    public function getAliases(): Aliases;
}
