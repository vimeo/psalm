<?php
namespace Psalm\Plugin;

interface RegistrationInterface
{
    /** @return void */
    public function addStubFile(string $file_name);

    /**
     * @return void
     */
    public function registerHooksFromClass(string $handler);
}
