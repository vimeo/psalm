<?php

declare(strict_types=1);

namespace Psalm\Tests\Config\Plugin;

use Psalm\Issue\PluginIssue;

final class TestPluginIssue extends PluginIssue
{
    public const ERROR_LEVEL = 2;
}
