<?php // phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols, Generic.Files.LineLength.TooLong


declare(strict_types=1);

$platform = match (getenv('PLATFORM')) {
    'ubuntu-24.04-arm' => 'arm64',
    'ubuntu-latest' => 'amd64',
};

$commit = getenv('GITHUB_SHA');
$user = getenv('ACTOR');
$ref = substr(getenv('REF'), strlen('refs/heads/'));
$is_tag = getenv('EVENT_NAME') === 'release';

echo "Waiting for commit $commit on $ref...".PHP_EOL;

function r(string $cmd): void
{
    echo "> $cmd\n";
    passthru($cmd, $exit);
    if ($exit) {
        exit($exit);
    }
}

$composer_branch = $is_tag ? $ref : "$ref-dev";
if ($composer_branch === 'master-dev') {
    $composer_branch = 'dev-master';
}
$dev = $is_tag ? '' : '~dev';

if ($is_tag) {
    $cur = 0;
    while (true) {
        $json = json_decode(file_get_contents("https://repo.packagist.org/p2/vimeo/psalm$dev.json?v=$cur"), true)["packages"]["vimeo/psalm"];
        foreach ($json as $v) {
            if ($v['version'] === $composer_branch) {
                if ($v['source']['reference'] === $commit) {
                    break 2;
                }
                break;
            }
        }
        sleep(1);
        $cur++;
    }
}

$ref = escapeshellarg($ref);
$composer_branch = escapeshellarg($composer_branch);
$platform = escapeshellarg($platform);

r("docker buildx build --push . -t ghcr.io/$user/psalm:$ref-$platform --build-arg PSALM_REV=$composer_branch -f bin/docker/Dockerfile");
