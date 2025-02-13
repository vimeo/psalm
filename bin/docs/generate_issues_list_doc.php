#!/usr/bin/env php
<?php

declare(strict_types=1);

$docs_dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . "docs"
    . DIRECTORY_SEPARATOR . "running_psalm" . DIRECTORY_SEPARATOR;
$issues_index = "{$docs_dir}issues.md";
$issues_dir = "{$docs_dir}issues";

if (!file_exists($issues_dir)) {
    throw new UnexpectedValueException("Issues documentation not found");
}

$issues_list = array_filter(array_map(function (string $issue_file) {
    if ($issue_file === "." || $issue_file === ".." || substr($issue_file, -3) !== ".md") {
        return false;
    }

    $issue = substr($issue_file, 0, strlen($issue_file) - 3);
    return " - [$issue](issues/$issue.md)";
}, scandir($issues_dir)));

usort($issues_list, "strcasecmp");

$issues_md_contents = array_merge(["# Issue types", ""], $issues_list, [""]);
file_put_contents($issues_index, implode("\n", $issues_md_contents));
