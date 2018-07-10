<?php
namespace Psalm\PluginApi;

interface PluginEntryPointInterface
{
    public function __invoke(RegistrationInterface $api): void;
}
