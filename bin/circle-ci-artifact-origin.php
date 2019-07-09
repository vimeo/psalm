#!/usr/bin/env php
<?php

/**
 * Outputs the HTTP origin at which any artifacts will be available for a Circle CI build, given the build number and
 * Github repo name.
 *
 * Does not output a line ending, since the output will need to be concatenated with a path to a specific artifact.
 */

$repo_name = $argv[1];
$build_number = (int)($argv[2]);

if (! preg_match("|^[a-z]+/[a-z]+$|", $repo_name)) {
    throw new Exception('unexpected character in repository name');
}

$opts = [
    'http' =>[
        // Github would return 403 error without the following:
        'header'=> "User-Agent: vimeo/psalm - circle-ci-artifact-origin.php\r\n",
    ]
];
$context = stream_context_create($opts);

$repo_api_url = 'https://api.github.com/repos/' . $repo_name;
$repository_details = json_decode(file_get_contents($repo_api_url, false, $context), true);
$repository_id = (int)$repository_details['id'];

printf("https://%d-%d-gh.circle-artifacts.com", $build_number, $repository_id);
