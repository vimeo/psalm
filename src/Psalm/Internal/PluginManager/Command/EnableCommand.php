<?php

declare(strict_types=1);

namespace Psalm\Internal\PluginManager\Command;

use Psalm\Internal\PluginManager\PluginListFactory;

/**
 * @internal
 */
final class EnableCommand extends PluginToggleCommand
{
    public function __construct(PluginListFactory $plugin_list_factory)
    {
        parent::__construct($plugin_list_factory, true);
    }
}
