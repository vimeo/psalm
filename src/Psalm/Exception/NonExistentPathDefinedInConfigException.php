<?php

declare(strict_types=1);

namespace Psalm\Exception;

class NonExistentPathDefinedInConfigException extends ConfigException
{

    public final static function fromPath(string $path, $pathType = 'directory'): static
    {

        return new static(
            'Could not resolve ' . $pathType . ' path to ' . $path . PHP_EOL .
            'This path is defined in your config file. Please make sure this ' . $pathType . ' exists.' . PHP_EOL .
            'Or add allowMissingFiles="true" to your config file to ignore non-existent files.'
        );
    }
}
