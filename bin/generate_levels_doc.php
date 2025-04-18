<?php

declare(strict_types=1);

use Psalm\Config\IssueHandler;
use Psalm\Issue\CodeIssue;

require_once(dirname(__DIR__) . '/vendor/autoload.php');

$issue_types = IssueHandler::getAllIssueTypes();

$grouped_issues = [];

foreach ($issue_types as $issue_type) {
    $issue_class = 'Psalm\\Issue\\' . $issue_type;

    if (!class_exists($issue_class) || !is_a($issue_class, CodeIssue::class, true)) {
        throw new Exception($issue_class . ' is not a Codeissue');
    }

    /** @var int */
    $issue_level = $issue_class::ERROR_LEVEL;

    $grouped_issues[$issue_level][] = $issue_type;
}

foreach ($grouped_issues as &$i) {
    sort($i);
} unset($i);

$result = "<!-- begin list -->\n## Always treated as errors\n\n";

foreach ($grouped_issues[-1] as $issue_type) {
    $result .= ' - [' . $issue_type . '](issues/' . $issue_type . '.md)' . "\n";
}

$result .= "## Errors that only appear at level 1\n\n";

foreach ($grouped_issues[1] as $issue_type) {
    $result .= ' - [' . $issue_type . '](issues/' . $issue_type . '.md)' . "\n";
}

$result .= "\n";

foreach ([2, 3, 4, 5, 6, 7] as $level) {
    $result .= '## Errors ignored at level ' . ($level + 1) . ($level < 7 ? ' and higher' : '') . "\n\n";

    $result .= 'These issues are treated as errors at level ' . $level . ' and below.' . "\n\n";

    foreach ($grouped_issues[$level] as $issue_type) {
        $result .= ' - [' . $issue_type . '](issues/' . $issue_type . '.md)' . "\n";
    }

    $result .= "\n";
}

$result .= "## Feature-specific errors\n\n";

foreach ($grouped_issues[-2] as $issue_type) {
    $result .= ' - [' . $issue_type . '](issues/' . $issue_type . '.md)' . "\n";
}

$f = dirname(__DIR__).'/docs/running_psalm/error_levels.md';
$content = file_get_contents($f);

$content = explode('<!-- begin list -->', $content)[0].$result;

file_put_contents($f, $content);
