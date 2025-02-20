<?php // phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols, Generic.Files.LineLength.TooLong


declare(strict_types=1);

$commit = getenv('GITHUB_SHA');
$ref = substr(getenv('REF'), strlen('refs/heads/'));
$is_tag = getenv('EVENT_NAME') === 'release';

echo "Waiting for commit $commit on $ref...".PHP_EOL;

function r(string $cmd): void
{
    echo "> $cmd\n";
    passthru($cmd);
}

$composer_branch = $is_tag ? $ref : "dev-$ref";

$cur = 0;
while (true) {
    $json = json_decode(file_get_contents("https://repo.packagist.org/p/vimeo/psalm.json?v=$cur"), true);
    if ($json["packages"]["vimeo/psalm"][$composer_branch]["source"]["reference"] === $commit) {
        return;
    }
    sleep(1);
    $cur++;
}

passthru("docker build . -t ghcr.io/vimeo/psalm:$branch --build-arg PSALM_REV=$compose_branch -f bin/docker/Dockerfile");
passthru("docker push ghcr.io/vimeo/psalm:$branch");

if ($is_tag) {
    passthru("docker tag ghcr.io/vimeo/psalm:$branch ghcr.io/vimeo/psalm:latest");
    passthru("docker push ghcr.io/vimeo/psalm:latest");
}
