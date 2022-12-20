<?php

namespace Psalm\Plugin;

interface RegistrationInterface
{
    public function addStubFile(string $file_name): void;

    /**
     * @param class-string $handler
     */
    public function registerHooksFromClass(string $handler): void;
}
