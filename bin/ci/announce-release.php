<?php

/**
 * Announces a release of Psalm on Mastodon, X, Telegram and Discord.
 *
 * Usage: php bin/ci/announce-release.php [--dry-run] [--force] [--only=<platform>,...] <tag>
 *
 * Run by .github/workflows/announce-release.yml when a release is published, or dispatched on its tag by
 * bin/ci/auto-release.php. For each platform:
 *  - nothing is posted if its secrets aren't set;
 *  - Mastodon: MASTODON_URL (the instance, e.g. https://phpc.social) and MASTODON_TOKEN (an access token with the
 *    write:statuses scope); the post is idempotent for an hour, so a rerun doesn't post twice;
 *  - X: PSALM_POST_TOKEN, the token of https://psalm-post.daniil.it, which posts it from a home connection (X
 *    flags GitHub's runners as bots, and its API charges per post); it only announces real releases, each once;
 *  - Telegram: TELEGRAM_BOT_TOKEN (from @BotFather) and TELEGRAM_CHAT_ID (the channel, e.g. @psalmphp, where the bot
 *    must be an administrator allowed to post messages);
 *  - Discord: DISCORD_BOT_TOKEN (of a bot application) and DISCORD_CHANNEL (<server id>/<channel id>, as in the
 *    channel's URL), an announcement channel where the bot may send messages and mention everyone; the post pings
 *    everyone and is published to the servers following the channel.
 *
 * X and Mastodon only get the latest major's minor and major releases, and its patch releases with user-facing
 * changes (a Features, Fixes or other section besides Internal changes and Docs), but no pre-releases; Telegram and
 * Discord get every release.
 *
 * --only announces on the given platforms alone (e.g. --only=x,telegram), to retry those that failed without posting
 * twice on the others, or to announce on X or Mastodon a release they skip.
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
$only = null;
$tags = [];
foreach ($args as $arg) {
    if (str_starts_with($arg, '--only=')) {
        $only = explode(',', strtolower(substr($arg, strlen('--only='))));
    } elseif ($arg !== '--dry-run' && $arg !== '--force') {
        $tags[] = $arg;
    }
}
if (count($tags) !== 1) {
    fwrite(STDERR, 'Usage: ' . $argv[0] . ' [--dry-run] [--force] [--only=<platform>,...] <tag>' . PHP_EOL);
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
 * POSTs $body as JSON, or GETs $url if $body is null.
 *
 * @param list<string> $headers
 * @return array<array-key, mixed>
 */
$request = static function (string $url, array $headers, array|object|null $body = null): array {
    $curl = curl_init($url);
    // + rather than a spread, which would renumber the CURLOPT_* keys
    curl_setopt_array($curl, ($body === null ? [] : [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($body, JSON_THROW_ON_ERROR),
    ]) + [
        CURLOPT_HTTPHEADER => [...$headers, 'Content-Type: application/json', 'User-Agent: psalm-release-bot'],
        CURLOPT_RETURNTRANSFER => true,
        // psalm-post.daniil.it loads x.com before posting
        CURLOPT_TIMEOUT => 60,
    ]);
    $response = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    // only the host: Telegram's URL contains the bot token
    $host = (string) parse_url($url, PHP_URL_HOST);
    if (!is_string($response)) {
        throw new RuntimeException("Cannot reach $host: " . curl_error($curl));
    }
    if ($status < 200 || $status >= 300) {
        throw new RuntimeException("Request to $host failed with HTTP $status: $response");
    }

    return json_decode($response, true, 512, JSON_THROW_ON_ERROR);
};

$release = json_decode(
    $run([
        'gh', 'release', 'view', $tag, '--repo', $repository,
        '--json', 'tagName,url,isDraft,isPrerelease,publishedAt,body',
    ]),
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

// public feeds only get releases worth a post: one a day for every branch buries the ones that matter, and posts
// nobody engages with make X show the account's other posts less
$feed_skip_reason = (static function () use ($release, $run, $repository): ?string {
    if ($release['isPrerelease']) {
        return 'it is a pre-release';
    }

    // without a tag, gh views the release marked as latest
    $latest = json_decode(
        $run(['gh', 'release', 'view', '--repo', $repository, '--json', 'tagName']),
        true,
        512,
        JSON_THROW_ON_ERROR,
    )['tagName'];
    if ((int) $release['tagName'] < (int) $latest) {
        return "it is older than the latest major ($latest)";
    }

    // a minor or major release always has news, and its notes may be written by hand without these sections
    if (str_ends_with($release['tagName'], '.0')) {
        return null;
    }

    preg_match_all('/^### (.+?)\s*$/m', $release['body'], $sections);
    if (array_diff($sections[1], ['Internal changes', 'Docs']) === []) {
        return 'it is a patch release whose notes have no Features, Fixes or other user-facing section';
    }

    return null;
})();

$text = "Psalm {$release['tagName']} has been released: {$release['url']} #psalm";
echo $text . PHP_EOL;

$platforms = [
    'Mastodon' => [
        ['MASTODON_URL', 'MASTODON_TOKEN'],
        static fn(string $instance, string $token): string => $request(
            rtrim($instance, '/') . '/api/v1/statuses',
            ["Authorization: Bearer $token", "Idempotency-Key: $repository-release-$tag"],
            ['status' => $text, 'visibility' => 'public'],
        )['url'],
    ],
    'X' => [
        ['PSALM_POST_TOKEN'],
        static fn(string $token): string => $request(
            'https://psalm-post.daniil.it/announce',
            ["Authorization: Bearer $token"],
            ['tag' => $tag, 'force' => $force],
        )['url'],
    ],
    'Telegram' => [
        ['TELEGRAM_BOT_TOKEN', 'TELEGRAM_CHAT_ID'],
        static function (string $token, string $chat_id) use ($request, $text): string {
            $message = $request(
                "https://api.telegram.org/bot$token/sendMessage",
                [],
                ['chat_id' => $chat_id, 'text' => $text],
            )['result'];

            return "https://t.me/{$message['chat']['username']}/{$message['message_id']}";
        },
    ],
    'Discord' => [
        ['DISCORD_BOT_TOKEN', 'DISCORD_CHANNEL'],
        static function (string $token, string $channel) use ($request, $text): string {
            [$server_id, $channel_id] = explode('/', $channel, 2);
            $api = "https://discord.com/api/v10/channels/$channel_id/messages";
            $headers = ["Authorization: Bot $token"];
            // a rerun (e.g. after publishing failed) finds the post instead of pinging everyone again
            $message = null;
            foreach ($request("$api?limit=50", $headers) as $recent) {
                if (str_contains($recent['content'], $text)) {
                    $message = $recent;
                    break;
                }
            }
            $message ??= $request(
                $api,
                $headers,
                ['content' => "@everyone $text", 'allowed_mentions' => ['parse' => ['everyone']]],
            );
            // the CROSSPOSTED flag: already published to the servers following the channel
            if (($message['flags'] & 1) === 0) {
                $request("$api/{$message['id']}/crosspost", $headers, (object) []);
            }

            return "https://discord.com/channels/$server_id/$channel_id/{$message['id']}";
        },
    ],
];

$unknown = array_diff($only ?? [], array_map(strtolower(...), array_keys($platforms)));
if ($unknown !== []) {
    fwrite(STDERR, 'Unknown platforms: ' . implode(', ', $unknown) . PHP_EOL);
    exit(1);
}

$failed = false;
foreach ($platforms as $platform => [$secrets, $announce]) {
    if ($only !== null && !in_array(strtolower($platform), $only, true)) {
        continue;
    }

    // naming the platform in --only announces there anyway
    if (in_array($platform, ['X', 'Mastodon'], true) && $feed_skip_reason !== null && $only === null) {
        echo "Not announcing $tag on $platform: $feed_skip_reason" . PHP_EOL;
        continue;
    }

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
