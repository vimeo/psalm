<?php // phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols, Generic.Files.LineLength.TooLong


declare(strict_types=1);

use Amp\Process\Process;

use function Amp\ByteStream\getStderr;
use function Amp\ByteStream\getStdout;
use function Amp\ByteStream\pipe;
use function Amp\async;

require 'vendor/autoload.php';

$commit = getenv('GITHUB_SHA');
$ref = substr(getenv('REF'), strlen('refs/heads/'));
$is_tag = getenv('EVENT_NAME') === 'release';

echo "Waiting for commit $commit on $ref...".PHP_EOL;

function r(string $cmd): void
{
    getStderr()->write("> $cmd\n");
    $cmd = Process::start($cmd);
    async(pipe(...), $cmd->getStdout(), getStdout())->ignore();
    async(pipe(...), $cmd->getStderr(), getStderr())->ignore();
    $cmd->join();
}

$composer_branch = $is_tag ? $ref : "dev-$ref";
$dev = $is_tag ? '' : '~dev';

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

$is_tag = true;
$composer_branch = '^6';
$ref = '6.x';

$ref = escapeshellarg($ref);
$composer_branch = escapeshellarg($composer_branch);

passthru("docker buildx build --platform linux/amd64,linux/arm64/v8 . -t ghcr.io/vimeo/psalm:$ref --build-arg PSALM_REV=$composer_branch -f bin/docker/Dockerfile");
passthru("docker push ghcr.io/vimeo/psalm:$ref");

if ($is_tag) {
    passthru("docker tag ghcr.io/vimeo/psalm:$ref ghcr.io/vimeo/psalm:latest");
    passthru("docker push ghcr.io/vimeo/psalm:latest");
}
