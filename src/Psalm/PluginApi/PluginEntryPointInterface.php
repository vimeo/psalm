<?php
namespace Psalm\PluginApi;

interface PluginEntryPointInterface
{
    /** @return void */
    public function __invoke(RegistrationInterface $api);
}
