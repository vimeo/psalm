#!/usr/bin/env php
<?php

$commit = getenv('GITHUB_SHA');
$branch = trim(shell_exec("git rev-parse --abbrev-ref HEAD"));
$tag = trim(shell_exec("git tag --points-at HEAD"));

echo "Waiting for commit $commit on branch $branch (tag $tag)...".PHP_EOL;

$branch = $tag ? $tag : "dev-$branch";

$cur = 0;
while (true) {
    $json = json_decode(file_get_contents("https://repo.packagist.org/p/vimeo/psalm.json?v=$cur"), true);
    if ($json["packages"]["vimeo/psalm"][$branch]["source"]["reference"] === $commit) {
        return;
    }
    sleep(1);
    $cur++;
}
