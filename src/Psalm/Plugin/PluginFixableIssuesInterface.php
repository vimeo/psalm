<?php

declare(strict_types=1);

namespace Psalm\Plugin;

use Psalm\Issue\CodeIssue;
use SimpleXMLElement;

/**
 * A plugin that fixes some of its issues when Psalter is asked to: Psalter accepts them in `--issues`, fixes them
 * with `--issues=all` and lists them with `--list-supported-issues`. The plugin's handlers add their file
 * replacements when Codebase::shouldFixIssue() is true.
 *
 * @api
 * @psalm-mutable
 */
interface PluginFixableIssuesInterface extends PluginInterface
{
    /**
     * Only Psalter calls it, before the plugin is invoked (and without invoking it for `--list-supported-issues`),
     * so the issues can't depend on what the plugin's `__invoke()` does.
     *
     * @return list<class-string<CodeIssue>>
     */
    public function getFixableIssues(?SimpleXMLElement $config = null): array;
}
