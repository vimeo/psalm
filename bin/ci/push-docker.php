<?php // phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols, Generic.Files.LineLength.TooLong


declare(strict_types=1);

$user = getenv('ACTOR');
$ref = substr(getenv('REF'), strlen('refs/heads/'));
$is_tag = getenv('EVENT_NAME') === 'release';

$ref = escapeshellarg($ref);

passthru("docker pull ghcr.io/$user/psalm:$ref-arm64");
passthru("docker pull ghcr.io/$user/psalm:$ref-amd64");

passthru("docker buildx imagetools create -t ghcr.io/$user/psalm:$ref ghcr.io/$user/psalm:$ref-arm64 ghcr.io/$user/psalm:$ref-amd64");
passthru("docker push ghcr.io/$user/psalm:$ref");

if ($is_tag || true) {
    passthru("docker tag ghcr.io/$user/psalm:$ref ghcr.io/$user/psalm:latest");
    passthru("docker push ghcr.io/$user/psalm:latest");
}
