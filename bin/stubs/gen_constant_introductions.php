<?php // phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols


declare(strict_types=1);

/**
 * Writes dictionaries/ConstantIntroductions.php: the PHP version (a php_version_id, e.g. 80500) that
 * introduced each global constant, dated by the official php:<version>-cli-<debian> images.
 *
 * Extensions like curl, openssl and posix only define the constants their system library has, so
 * each version is compared with the previous one on the same Debian release. A constant counts
 * as introduced only if its extension is loaded in the previous image too.
 *
 * Usage: php bin/stubs/gen_constant_introductions.php [--repository=mirror.gcr.io/library/php]
 */

/** each version and the previous one, with image tags of the same Debian release */
const PAIRS = [
    ['8.0', '8.0-cli-bullseye', '7.4-cli-bullseye'],
    ['8.1', '8.1-cli-bullseye', '8.0-cli-bullseye'],
    ['8.2', '8.2-cli-bullseye', '8.1-cli-bullseye'],
    ['8.3', '8.3-cli-bookworm', '8.2-cli-bookworm'],
    ['8.4', '8.4-cli-bookworm', '8.3-cli-bookworm'],
    ['8.5', '8.5-cli-bookworm', '8.4-cli-bookworm'],
    // 8.6 has no -trixie tag yet; 8.6-cli is trixie
    ['8.6', '8.6-cli', '8.5-cli-trixie'],
];

/**
 * nl_langinfo() items PHP 8.1 and older only registered where <langinfo.h> defines them as macros:
 * they predate 8.2 on other platforms, though glibc builds lack them
 */
const PLATFORM_DEPENDENT = [
    'CURRENCY_SYMBOL', 'DECIMAL_POINT', 'ERA_YEAR', 'FRAC_DIGITS', 'GROUPING', 'INT_CURR_SYMBOL',
    'INT_FRAC_DIGITS', 'MON_DECIMAL_POINT', 'MON_GROUPING', 'MON_THOUSANDS_SEP', 'NEGATIVE_SIGN', 'NOSTR',
    'N_CS_PRECEDES', 'N_SEP_BY_SPACE', 'N_SIGN_POSN', 'POSITIVE_SIGN', 'P_CS_PRECEDES', 'P_SEP_BY_SPACE',
    'P_SIGN_POSN', 'THOUSANDS_SEP', 'YESSTR',
];

$repository = 'php';
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--repository=')) {
        $repository = substr($arg, strlen('--repository='));
    }
}

/**
 * @return array<string, list<string>> the constants of each loaded extension
 */
function dumpConstants(string $repository, string $tag): array
{
    $json = shell_exec(
        'docker run --rm ' . escapeshellarg($repository . ':' . $tag) . ' php -r '
        . escapeshellarg('echo json_encode(array_map(fn ($c) => array_keys($c), get_defined_constants(true)));'),
    );

    if (!is_string($json) || $json === '') {
        throw new RuntimeException('No constants dumped by ' . $repository . ':' . $tag);
    }

    /** @var array<string, list<string>> */
    $constants = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    unset($constants['user']);

    return $constants;
}

$introductions = [];

foreach (PAIRS as [$version, $tag, $previous_tag]) {
    $constants = dumpConstants($repository, $tag);
    $previous_constants = dumpConstants($repository, $previous_tag);

    $previous_names = [];
    foreach ($previous_constants as $names) {
        $previous_names += array_fill_keys($names, true);
    }

    [$major, $minor] = array_map('intval', explode('.', $version));

    foreach ($constants as $extension => $names) {
        if (!isset($previous_constants[$extension])) {
            continue;
        }

        foreach ($names as $name) {
            if (!isset($previous_names[$name]) && !in_array($name, PLATFORM_DEPENDENT, true)) {
                $introductions[$name] ??= $major * 10_000 + $minor * 100;
            }
        }
    }
}

ksort($introductions);

file_put_contents(
    __DIR__ . '/../../dictionaries/ConstantIntroductions.php',
    "<?php // phpcs:ignoreFile\n\nreturn " . var_export($introductions, true) . ";\n",
);
