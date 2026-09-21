<?php // phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols, Generic.Files.LineLength.TooLong


declare(strict_types=1);

$platform = match (getenv('PLATFORM')) {
    'ubuntu-24.04-arm' => 'arm64',
    'ubuntu-latest' => 'amd64',
};

$commit = getenv('GITHUB_SHA');
$user = getenv('ACTOR');
$is_tag = str_starts_with(getenv('REF'), 'refs/tags/');
$ref = str_replace(['refs/heads/', 'refs/tags/'], '', getenv('REF'));

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

// Per-platform, branch-independent layer cache on the registry. The expensive
// layers (compiling PHP from source) only depend on the Dockerfile, so they are
// reused across builds; only the Psalm install layers at the end are rebuilt.
$cache = "ghcr.io/$user/psalm:buildcache-$platform";

r("docker buildx build --push . -t ghcr.io/$user/psalm:$ref-$platform --build-arg PSALM_REV=$composer_branch -f bin/docker/Dockerfile --cache-from type=registry,ref=$cache --cache-to type=registry,ref=$cache,mode=max,image-manifest=true,oci-mediatypes=true,ignore-error=true");
