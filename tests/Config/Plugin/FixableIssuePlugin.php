<?php

declare(strict_types=1);

namespace Psalm\Tests\Config\Plugin;

use Override;
use Psalm\Issue\CodeIssue;
use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\PluginFixableIssuesInterface;
use Psalm\PluginRegistrationSocket;
use SimpleXMLElement;

use function class_exists;

/**
 * Reports LegacyCall on legacy_call() and, when Psalter is asked to fix it, renames the call to modern_call()
 */
final class FixableIssuePlugin implements PluginEntryPointInterface, PluginFixableIssuesInterface
{
    #[Override]
    public function __invoke(PluginRegistrationSocket $registration, ?SimpleXMLElement $config = null): void
    {
        class_exists(LegacyCallFixer::class);
        $registration->registerHooksFromClass(LegacyCallFixer::class);
    }

    /**
     * LegacyCall, unless the plugin config lists other classes as `<fixableIssue>`
     */
    #[Override]
    public function getFixableIssues(?SimpleXMLElement $config = null): array
    {
        if (!isset($config->fixableIssue)) {
            return [LegacyCall::class];
        }

        $fixable_issues = [];
        foreach ($config->fixableIssue as $fixable_issue) {
            // unchecked, as a test configures a class that isn't an issue
            /** @var class-string<CodeIssue> $fixable_issue_class */
            $fixable_issue_class = (string) $fixable_issue;
            $fixable_issues[] = $fixable_issue_class;
        }

        return $fixable_issues;
    }
}
