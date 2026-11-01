<?php

/**
 * Announces a release of Psalm on Mastodon, X and Telegram.
 *
 * Usage: php bin/ci/announce-release.php [--dry-run] [--force] <tag>
 *
 * Run by .github/workflows/announce-release.yml when a release is published, or dispatched on its tag by
 * bin/ci/auto-release.php. For each platform:
 *  - nothing is posted if its secrets aren't set;
 *  - Mastodon: MASTODON_URL (the instance, e.g. https://phpc.social) and MASTODON_TOKEN (an access token with the
 *    write:statuses scope); the post is idempotent for an hour, so a rerun doesn't post twice;
 *  - X: PSALM_POST_TOKEN, the token of https://psalm-post.daniil.it, which posts it from a home connection (X
 *    flags GitHub's runners as bots, and its API charges per post); it only announces real releases, each once;
 *  - Telegram: TELEGRAM_BOT_TOKEN (from @BotFather) and TELEGRAM_CHAT_ID (the channel, e.g. @psalmphp, where the bot
 *    must be an administrator allowed to post messages).
 *
 * Drafts aren't announced, nor releases published more than a day ago unless --force is given, so that
 * auto-release.php dispatching the announcement on the last tag of each branch doesn't announce old releases.
 *
 * Needs the GitHub CLI, authenticated (GH_TOKEN), and GITHUB_REPOSITORY (default: vimeo/psalm).
 */

declare(strict_types=1);

$args = array_slice($argv, 1);
$dry_run = in_array('--dry-run', $args, true);
$force = in_array('--force', $args, true);
$options = ['--dry-run', '--force'];
$tags = array_values(array_filter($args, static fn(string $arg): bool => !in_array($arg, $options, true)));
if (count($tags) !== 1) {
    fwrite(STDERR, 'Usage: ' . $argv[0] . ' [--dry-run] [--force] <tag>' . PHP_EOL);
    exit(1);
}
$tag = $tags[0];

$repository = getenv('GITHUB_REPOSITORY') ?: 'vimeo/psalm';

/** @param non-empty-list<string> $command */
$run = static function (array $command): string {
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => STDERR], $pipes);
    if ($process === false) {
        throw new RuntimeException('Cannot run ' . implode(' ', $command));
    }

    $output = (string) stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    if (proc_close($process) !== 0) {
        throw new RuntimeException('Failed: ' . implode(' ', $command));
    }

    return $output;
};

/**
 * @param list<string> $headers
 * @return array<string, mixed>
 */
$post = static function (string $url, array $headers, array $body): array {
    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [...$headers, 'Content-Type: application/json', 'User-Agent: psalm-release-bot'],
        CURLOPT_POSTFIELDS => json_encode($body, JSON_THROW_ON_ERROR),
        CURLOPT_RETURNTRANSFER => true,
        // psalm-post.daniil.it loads x.com before posting
        CURLOPT_TIMEOUT => 60,
    ]);
    $response = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    // only the host: Telegram's URL contains the bot token
    $host = (string) parse_url($url, PHP_URL_HOST);
    if (!is_string($response)) {
        throw new RuntimeException("Cannot post to $host: " . curl_error($curl));
    }
    if ($status < 200 || $status >= 300) {
        throw new RuntimeException("Posting to $host failed with HTTP $status: $response");
    }

    return json_decode($response, true, 512, JSON_THROW_ON_ERROR);
};

$release = json_decode(
    $run(['gh', 'release', 'view', $tag, '--repo', $repository, '--json', 'tagName,url,isDraft,publishedAt']),
    true,
    512,
    JSON_THROW_ON_ERROR,
);

if ($release['isDraft']) {
    echo "$tag is a draft, not announcing it" . PHP_EOL;
    exit(0);
}

if (!$force && strtotime($release['publishedAt']) < time() - 86_400) {
    echo "$tag was published on {$release['publishedAt']}, more than a day ago, not announcing it" . PHP_EOL;
    exit(0);
}

$text = "Psalm {$release['tagName']} has been released: {$release['url']} #psalm";
echo $text . PHP_EOL;

$platforms = [
    'Mastodon' => [
        ['MASTODON_URL', 'MASTODON_TOKEN'],
        static fn(string $instance, string $token): string => $post(
            rtrim($instance, '/') . '/api/v1/statuses',
            ["Authorization: Bearer $token", "Idempotency-Key: $repository-release-$tag"],
            ['status' => $text, 'visibility' => 'public'],
        )['url'],
    ],
    'X' => [
        ['PSALM_POST_TOKEN'],
        static fn(string $token): string => $post(
            'https://psalm-post.daniil.it/announce',
            ["Authorization: Bearer $token"],
            ['tag' => $tag, 'force' => $force],
        )['url'],
    ],
    'Telegram' => [
        ['TELEGRAM_BOT_TOKEN', 'TELEGRAM_CHAT_ID'],
        static function (string $token, string $chat_id) use ($post, $text): string {
            $message = $post(
                "https://api.telegram.org/bot$token/sendMessage",
                [],
                ['chat_id' => $chat_id, 'text' => $text],
            )['result'];

            return "https://t.me/{$message['chat']['username']}/{$message['message_id']}";
        },
    ],
];

$failed = false;
foreach ($platforms as $platform => [$secrets, $announce]) {
    $values = array_map(static fn(string $secret): string => (string) getenv($secret), $secrets);
    if (in_array('', $values, true)) {
        echo "::notice::Not announcing $tag on $platform: " . implode(', ', $secrets) . ' must be set' . PHP_EOL;
        continue;
    }

    if ($dry_run) {
        echo "Would announce $tag on $platform" . PHP_EOL;
        continue;
    }

    try {
        echo "Announced $tag on $platform: " . $announce(...$values) . PHP_EOL;
    } catch (Throwable $e) {
        echo "::error::Cannot announce $tag on $platform: {$e->getMessage()}" . PHP_EOL;
        $failed = true;
    }
}

exit($failed ? 1 : 0);
