<?php
namespace Psalm\PluginApi;

interface RegistrationInterface
{
    public function addStubFile(string $file_name): void;
}
