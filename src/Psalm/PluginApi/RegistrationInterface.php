<?php
namespace Psalm\PluginApi;

interface RegistrationInterface
{
    /** @return void */
    public function addStubFile(string $file_name);
}
