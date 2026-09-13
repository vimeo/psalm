<?php

declare(strict_types=1);

namespace Psalm\Plugin;

use SimpleXMLElement;

/**
 * @api
 */
interface PluginFileExtensionsInterface extends PluginInterface
{
    public function processFileExtensions(
        FileExtensionsInterface $fileExtensions,
        ?SimpleXMLElement $config = null,
    ): void;
}
