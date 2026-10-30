<?php // phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols, Generic.Files.LineLength.TooLong


declare(strict_types=1);

function r(string $cmd): void
{
    echo "> $cmd\n";
    passthru($cmd, $exit);
    if ($exit) {
        exit($exit);
    }
}

$user = getenv('ACTOR');
$is_tag = str_starts_with(getenv('REF'), 'refs/tags/');
$ref = str_replace(['refs/heads/', 'refs/tags/'], '', getenv('REF'));

// latest is the highest stable version: neither a prerelease nor a release of an older branch
$stable_pattern = '/^\d+\.\d+\.\d+$/';
$is_latest = false;
if ($is_tag && preg_match($stable_pattern, $ref)) {
    exec('git ls-remote --tags --refs origin', $remote_tags, $exit);
    if ($exit) {
        exit($exit);
    }

    $stable_tags = array_filter(
        array_map(static fn(string $line): string => preg_replace('#^.*refs/tags/#', '', $line), $remote_tags),
        static fn(string $tag): bool => (bool) preg_match($stable_pattern, $tag),
    );
    usort($stable_tags, 'version_compare');
    $is_latest = end($stable_tags) === $ref;
}

$ref = escapeshellarg($ref);

r("docker pull ghcr.io/$user/psalm:$ref-arm64 --platform arm64");
r("docker pull ghcr.io/$user/psalm:$ref-amd64 --platform amd64");

r("docker buildx imagetools create -t ghcr.io/$user/psalm:$ref ghcr.io/$user/psalm:$ref-arm64 ghcr.io/$user/psalm:$ref-amd64");

if ($is_latest) {
    r("docker buildx imagetools create -t ghcr.io/$user/psalm:latest ghcr.io/$user/psalm:$ref-arm64 ghcr.io/$user/psalm:$ref-amd64");
}
