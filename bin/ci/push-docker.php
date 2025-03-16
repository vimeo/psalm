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
$ref = substr(getenv('REF'), strlen('refs/heads/'));
$is_tag = getenv('EVENT_NAME') === 'release';

$ref = escapeshellarg($ref);

r("docker pull ghcr.io/$user/psalm:$ref-arm64 --platform arm64");
r("docker pull ghcr.io/$user/psalm:$ref-amd64 --platform amd64");

r("docker buildx imagetools create -t ghcr.io/$user/psalm:$ref ghcr.io/$user/psalm:$ref-arm64 ghcr.io/$user/psalm:$ref-amd64");

if ($is_tag) {
    r("docker pull ghcr.io/$user/psalm:$ref");
    r("docker tag ghcr.io/$user/psalm:$ref ghcr.io/$user/psalm:latest");
    r("docker push ghcr.io/$user/psalm:latest");
}
