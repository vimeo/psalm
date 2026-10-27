<?php // phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols, Generic.Files.LineLength.TooLong


declare(strict_types=1);

use Composer\Semver\Semver;
use Composer\Semver\VersionParser;

/**
 * Generates the stub Dockerfile for a single PHP version on the fly and builds &
 * pushes it, reusing a persistent buildx registry cache.
 *
 * The cache is invalidated (via the CACHEBUST build arg) whenever PHP itself or
 * the resolved version of ANY installed extension changes. To do that exactly,
 * the cache key folds in, per extension, the version from that extension's real
 * source (see SOURCES): the PHP base image for bundled extensions, Packagist for
 * extensions installed with PIE, PECL for the other PECL extensions, and the
 * upstream git commit for extensions installed from a branch.
 *
 * Usage:
 *   php build_docker.php <version>   build & push ghcr.io/$ACTOR/psalm:internal_stubs_<version>
 *   php build_docker.php --versions  print the supported versions as a JSON array
 */

/** PHP versions we build stub images for. Single source of truth (also drives the CI matrix). */
const VERSIONS = ['7.0', '7.1', '7.2', '7.3', '7.4', '8.0', '8.1', '8.2', '8.3', '8.4', '8.5', '8.6'];

/** The tag of the PHP base image of versions with no release yet (php:<version>-alpine otherwise). */
const BASE_IMAGE_TAGS = [
    '8.6' => '8.6-rc-alpine',
];

/** Alpine packages installed first, for extensions the base image of a version lacks the libraries of. */
const BASE_IMAGE_PACKAGES = [
    // the 8.6 release candidate images are built without mbstring
    '8.6' => ['oniguruma-dev'],
];

/** Extensions installed in every image. */
const EXTENSIONS = [
    'memcached', 'grpc', 'soap', 'swoole', 'zookeeper',
    'amqp', 'apcu', 'zmq', 'ds', 'event', 'ev', 'redis', 'mongodb', 'imagick', 'pcntl',
    'pgsql', 'intl', 'gmp', 'mbstring', 'pdo_mysql', 'xml', 'dom', 'iconv', 'zip', 'igbinary', 'gd', 'bcmath',
];

/** Extensions added on top of EXTENSIONS for specific versions. */
const INCLUDES = [
    '7.4' => ['ffi'],
    '8.0' => ['uv-beta', 'ffi'],
    '8.1' => ['uv-beta', 'ffi'],
    '8.2' => ['uv-beta', 'ffi'],
    '8.3' => ['uv-beta', 'ffi'],
    '8.4' => ['uv-beta', 'ffi'],
    '8.5' => ['uv-beta', 'ffi'],
    '8.6' => ['uv-beta', 'ffi'],
];

/** Extensions removed for specific versions. */
const EXCLUDES = [
    // php-zmq does not build on PHP 8.5 (it calls zend_exception_get_default(), removed in 8.5).
    '8.5' => ['zmq'],
    // these don't build on the PHP 8.6 release candidates yet (8.6 removed XtOffsetOf, zval_dtor, INI_STR, ...)
    '8.6' => ['zmq', 'amqp', 'apcu', 'ds', 'ev', 'grpc', 'igbinary', 'memcached', 'redis', 'swoole', 'zookeeper'],
];

/** Extension versions pinned for specific versions, by base extension name. */
const PINS = [
    // grpc 1.85.0 needs GCC 10, newer than that of the Alpine 3.10 base image
    '7.1' => ['grpc' => '1.84.0'],
];

const PIE_VERSION = '1.5.3';

/**
 * PECL extensions installed with PIE, from Packagist, wherever their newest stable
 * PIE release supports the PHP version (install-php-extensions installs them
 * elsewhere): the PIE package, the Alpine packages it builds against, the
 * configure options install-php-extensions would pass, and whether it is built in
 * a layer of its own.
 */
const PIE_PACKAGES = [
    'memcached' => [
        'package' => 'php-memcached/php-memcached',
        'apk' => ['libmemcached-dev', 'zlib-dev', 'cyrus-sasl-dev'],
        'options' => ['--enable-memcached-igbinary', '--enable-memcached-json', '--enable-memcached-sasl', '--enable-memcached-session'],
    ],
    'grpc' => [
        'package' => 'grpc/grpc-php-ext',
        'apk' => ['zlib-dev', 'linux-headers'],
        'options' => [],
        'own_layer' => true,
    ],
    'swoole' => [
        'package' => 'swoole/swoole',
        'apk' => ['openssl-dev', 'curl-dev', 'postgresql-dev', 'linux-headers', 'c-ares-dev', 'brotli-dev', 'sqlite-dev', 'zstd-dev', 'ncurses-dev', 'libssh2-dev', 'liburing-dev'],
        'options' => [
            '--with-openssl-dir=yes', '--enable-mysqlnd', '--enable-swoole-curl', '--enable-cares', '--enable-brotli', '--enable-zstd',
            '--enable-swoole-pgsql', '--enable-swoole-sqlite', '--enable-iouring', '--enable-uring-socket', '--with-swoole-ssh2=yes',
        ],
    ],
    'amqp' => [
        'package' => 'php-amqp/php-amqp',
        'apk' => ['rabbitmq-c-dev'],
        'options' => [],
    ],
    'apcu' => [
        'package' => 'apcu/apcu',
        'apk' => [],
        'options' => [],
    ],
    'ds' => [
        'package' => 'php-ds/ext-ds',
        'apk' => [],
        'options' => [],
    ],
    'ev' => [
        'package' => 'osmanov/pecl-ev',
        'apk' => [],
        'options' => [],
    ],
    'redis' => [
        'package' => 'phpredis/phpredis',
        // the git sources lack the bundled liblzf of the PECL tarballs
        'apk' => ['zstd-dev', 'lz4-dev', 'liblzf-dev'],
        'options' => ['--enable-redis-igbinary', '--enable-redis-lzf', '--with-liblzf=yes', '--enable-redis-zstd', '--enable-redis-lz4', '--with-liblz4=yes'],
    ],
    'mongodb' => [
        'package' => 'mongodb/mongodb-extension',
        'apk' => ['openssl-dev', 'icu-dev', 'cyrus-sasl-dev', 'snappy-dev', 'zlib-dev', 'zstd-dev'],
        'options' => [
            '--with-mongodb-system-libs=no', '--with-mongodb-client-side-encryption=yes', '--with-mongodb-snappy=yes', '--with-mongodb-zlib=yes',
            '--with-mongodb-zstd=yes', '--with-mongodb-sasl=yes', '--with-mongodb-ssl=yes', '--enable-mongodb-crypto-system-profile=yes',
        ],
    ],
    'imagick' => [
        'package' => 'imagick/imagick',
        'apk' => ['imagemagick-dev'],
        'options' => [],
    ],
    'uv' => [
        'package' => 'amphp/uv',
        'apk' => ['libuv-dev'],
        'options' => [],
    ],
];

/**
 * Where install-php-extensions gets each extension's version from, keyed by the
 * base extension name (without any "-<stability>" suffix). Used to build a cache
 * key that changes exactly when the installed version would change:
 *   'php'                  bundled with PHP itself -> tracked by the base image digest
 *   'pecl'                 resolved from PECL       -> newest release on PECL of the wanted stability
 *   'git:<owner>/<repo>@<ref>'  installed from a git branch/tag with no version -> upstream commit
 */
const SOURCES = [
    'memcached' => 'pecl',
    'grpc' => 'pecl',
    'swoole' => 'pecl',
    'zookeeper' => 'pecl',
    'amqp' => 'pecl',
    'apcu' => 'pecl',
    'ds' => 'pecl',
    'event' => 'pecl',
    'ev' => 'pecl',
    'redis' => 'pecl',
    'mongodb' => 'pecl',
    'imagick' => 'pecl',
    'igbinary' => 'pecl',
    'uv' => 'pecl',
    'zmq' => 'git:zeromq/php-zmq@master',
    'soap' => 'php',
    'pcntl' => 'php',
    'pgsql' => 'php',
    'intl' => 'php',
    'gmp' => 'php',
    'mbstring' => 'php',
    'pdo_mysql' => 'php',
    'xml' => 'php',
    'dom' => 'php',
    'iconv' => 'php',
    'zip' => 'php',
    'gd' => 'php',
    'bcmath' => 'php',
    'ffi' => 'php',
];

const INSTALLER_URL = 'https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions';

function fail(string $message): never
{
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
}

/** Run a command, streaming output; abort on failure. */
function run(string $cmd): void
{
    echo "> $cmd\n";
    passthru($cmd, $exit);
    if ($exit !== 0) {
        exit($exit);
    }
}

/** Run a command capturing stdout; abort on failure. */
function capture(string $cmd): string
{
    $out = [];
    exec($cmd . ' 2>/dev/null', $out, $exit);
    if ($exit !== 0) {
        fail("Command failed ($exit): $cmd");
    }
    return implode("\n", $out);
}

/** Fetch a URL as a string (with retries), aborting on persistent failure. */
function fetch(string $url, array $headers = []): string
{
    $cmd = 'curl --fail --silent --show-error --location --max-time 30 --retry 5 --retry-all-errors --retry-delay 2';
    foreach ($headers as $header) {
        $cmd .= ' -H ' . escapeshellarg($header);
    }
    $cmd .= ' ' . escapeshellarg($url);
    return capture($cmd);
}

/** The list of extensions installed for a given version, after includes/excludes. */
function extensionsFor(string $version): array
{
    $extensions = array_merge(EXTENSIONS, INCLUDES[$version] ?? []);
    $excluded = EXCLUDES[$version] ?? [];
    $extensions = array_values(array_filter($extensions, static fn(string $e): bool => !in_array($e, $excluded, true)));
    $pins = PINS[$version] ?? [];
    $extensions = array_map(
        static fn(string $e): string => isset($pins[$e]) ? "$e-{$pins[$e]}" : $e,
        $extensions,
    );

    foreach ($extensions as $extension) {
        if (!preg_match('/^[A-Za-z0-9._-]+$/', $extension)) {
            fail("Invalid extension name: $extension");
        }
        $base = explode('-', $extension, 2)[0];
        if (!isset(SOURCES[$base])) {
            fail("No source defined for extension '$extension' (base '$base'); add it to SOURCES.");
        }
    }
    return $extensions;
}

/** Resolve the version identifier for one extension from its real source. */
function extensionVersion(string $extension, string $phpBaseDigest): string
{
    $base = explode('-', $extension, 2)[0];
    $source = SOURCES[$base];

    if ($source === 'php') {
        // Bundled with PHP: its version is the PHP version, already covered by the base image digest.
        return 'bundled@' . $phpBaseDigest;
    }
    if ($source === 'pecl') {
        $suffix = explode('-', $extension, 2)[1] ?? '';
        if (preg_match('/^\d/', $suffix)) {
            // pinned to a version
            return 'pecl@' . $suffix;
        }
        // The query string gets past the PECL CDN, which can serve these files days out of date.
        $stability = $suffix === '' ? 'stable' : $suffix;
        $resolved = trim(fetch("https://pecl.php.net/rest/r/$base/$stability.txt?" . time()));
        if (!preg_match('/^\d[\w.]*$/', $resolved)) {
            fail("Could not parse the $stability version of PECL extension '$base'.");
        }
        return 'pecl@' . $resolved;
    }
    if (preg_match('#^git:([^@]+)@(.+)$#', $source, $m)) {
        [, $repo, $ref] = $m;
        $headers = ['Accept: application/vnd.github.sha'];
        if (($token = getenv('GITHUB_TOKEN')) !== false && $token !== '') {
            $headers[] = 'Authorization: Bearer ' . $token;
        }
        $sha = trim(fetch("https://api.github.com/repos/$repo/commits/$ref", $headers));
        if (!preg_match('/^[0-9a-f]{40}$/', $sha)) {
            fail("Could not resolve commit for git source '$source' (got '$sha').");
        }
        return "git@$sha";
    }
    fail("Unknown source '$source' for extension '$base'.");
}

/** The PHP base image of a version. */
function phpBaseImage(string $version): string
{
    return 'php:' . (BASE_IMAGE_TAGS[$version] ?? "$version-alpine");
}

/** The manifest digest of the PHP base image for a version. */
function phpBaseDigest(string $version): string
{
    $image = phpBaseImage($version);
    $inspect = capture('docker buildx imagetools inspect ' . escapeshellarg($image));
    if (!preg_match('/^Digest:\s*(\S+)/m', $inspect, $m)) {
        fail("Could not determine the digest of $image.");
    }
    return $m[1];
}

/** The PHP version of the PHP base image for a version, e.g. 8.4.14. */
function phpBaseVersion(string $version): string
{
    $config = imageConfig(phpBaseImage($version)) ?? [];
    foreach ($config['Env'] ?? $config['env'] ?? [] as $env) {
        if (is_string($env) && str_starts_with($env, 'PHP_VERSION=')) {
            return substr($env, strlen('PHP_VERSION='));
        }
    }
    fail('Could not determine the PHP version of ' . phpBaseImage($version) . '.');
}

/** Composer's stabilities, most stable first, under their Composer and PECL names. */
const PIE_STABILITIES = ['stable' => 0, 'RC' => 1, 'beta' => 2, 'alpha' => 3, 'dev' => 4, 'devel' => 4];

/**
 * The extensions installed with PIE for a version, mapped to their Packagist
 * version: those whose newest PIE release (stable, unless the extension asks for
 * a less stable one) supports the PHP version. Older releases are left to
 * install-php-extensions, which knows which of them build on the older base
 * images (their Packagist constraints can be too loose).
 *
 * @return array<string, string>
 */
function pieInstalls(array $extensions, string $phpVersion): array
{
    require_once __DIR__ . '/../../vendor/autoload.php';

    $installs = [];
    foreach ($extensions as $extension) {
        [$base, $suffix] = array_pad(explode('-', $extension, 2), 2, '');
        if (!isset(PIE_PACKAGES[$base]) || preg_match('/^\d/', $suffix)) {
            continue;
        }
        // the "-<stability>" suffix is the least stable release install-php-extensions would take
        $minimumStability = PIE_STABILITIES[$suffix === '' ? 'stable' : $suffix] ?? PIE_STABILITIES['stable'];
        $package = PIE_PACKAGES[$base]['package'];
        $metadata = json_decode(fetch("https://repo.packagist.org/p2/$package.json"), true);
        // newest first, minified: each release only lists the fields that differ from the previous one
        $release = [];
        foreach ($metadata['packages'][$package] ?? [] as $diff) {
            $release = array_filter($diff + $release, static fn($value): bool => $value !== '__unset');
            if (($release['type'] ?? '') !== 'php-ext'
                || PIE_STABILITIES[VersionParser::parseStability($release['version'])] > $minimumStability
            ) {
                continue;
            }
            if (Semver::satisfies($phpVersion, $release['require']['php'] ?? '*')) {
                $installs[$extension] = $release['version'];
            }
            break;
        }
    }
    return $installs;
}

/**
 * Resolve the cache-busting components for a version: the PHP base image digest,
 * the installer, and every extension's real version, as an ordered list of
 * "name=version" strings. The CACHEBUST key is a hash of exactly this list, so
 * the list also explains *why* the key (and thus the image) changed.
 *
 * @param array<string, string> $pieInstalls
 * @return list<string>
 */
function resolveComponents(string $version, array $extensions, array $pieInstalls, string $phpBaseDigest): array
{
    $installerSha = hash('sha256', fetch(INSTALLER_URL));

    $components = [
        'php=' . $phpBaseDigest,
        'install-php-extensions=' . $installerSha,
        'pie=' . PIE_VERSION,
    ];
    $versions = [];
    foreach ($extensions as $extension) {
        $versions[$extension] = isset($pieInstalls[$extension])
            ? 'pie@' . PIE_PACKAGES[explode('-', $extension, 2)[0]]['package'] . ':' . $pieInstalls[$extension]
            : extensionVersion($extension, $phpBaseDigest);
    }
    ksort($versions);
    echo "Resolved versions for PHP $version:\n";
    echo "  php = $phpBaseDigest\n";
    echo "  install-php-extensions = $installerSha\n";
    foreach ($versions as $extension => $resolved) {
        echo "  $extension = $resolved\n";
        $components[] = "$extension=$resolved";
    }
    return $components;
}

/** @param list<string> $components */
function cacheKeyFromComponents(array $components): string
{
    return hash('sha256', implode("\n", $components));
}

/**
 * Build the cache-busting key for a version: PHP base image + every extension's real version.
 *
 * @param array<string, string> $pieInstalls
 */
function cacheKey(string $version, array $extensions, array $pieInstalls, string $phpBaseDigest): string
{
    return cacheKeyFromComponents(resolveComponents($version, $extensions, $pieInstalls, $phpBaseDigest));
}

const CACHE_KEY_LABEL = 'dev.psalm.stubs.cache-key';
const COMPONENTS_LABEL = 'dev.psalm.stubs.components';

/**
 * Read the config of an image in a registry, without pulling its layers.
 * Returns null when the image does not exist (or cannot be inspected).
 */
function imageConfig(string $image): ?array
{
    $out = [];
    $exit = 0;
    exec(
        'docker buildx imagetools inspect ' . escapeshellarg($image)
            . ' --format ' . escapeshellarg('{{json .Image}}') . ' 2>/dev/null',
        $out,
        $exit,
    );
    if ($exit !== 0) {
        return null;
    }

    $data = json_decode(implode("\n", $out), true);
    if (!is_array($data)) {
        return null;
    }

    // Single-platform images expose the config directly; multi-platform images
    // (like the PHP base images) expose a map of platform => config.
    $config = $data['config'] ?? null;
    if (!is_array($config)) {
        $entry = $data['linux/amd64'] ?? (reset($data) ?: null);
        $config = is_array($entry) ? ($entry['config'] ?? null) : null;
    }
    return is_array($config) ? $config : [];
}

/**
 * Read the labels of an image already in the registry, without pulling its
 * layers. Returns null when the image does not exist (or cannot be inspected).
 *
 * @return array<string, string>|null
 */
function imageLabels(string $image): ?array
{
    $config = imageConfig($image);
    if ($config === null) {
        return null;
    }

    $labels = $config['Labels'] ?? $config['labels'] ?? [];
    if (!is_array($labels)) {
        return [];
    }

    $result = [];
    foreach ($labels as $key => $value) {
        if (is_string($key) && is_string($value)) {
            $result[$key] = $value;
        }
    }
    return $result;
}

/**
 * Parse a "name=version" component list into a map.
 *
 * @param list<string> $components
 * @return array<string, string>
 */
function componentMap(array $components): array
{
    $map = [];
    foreach ($components as $component) {
        [$name, $version] = array_pad(explode('=', $component, 2), 2, '');
        $map[$name] = $version;
    }
    return $map;
}

/**
 * Log, before building, whether this run rebuilds the image and — when it does —
 * exactly which resolved component changed the CACHEBUST key. Prints GitHub
 * Actions `::notice::` annotations so the reason is visible in the run summary,
 * not just buried in the buildx layer log.
 *
 * @param list<string> $components
 */
function explainRebuild(string $image, string $cacheKey, array $components): void
{
    $labels = imageLabels($image);

    if ($labels === null) {
        echo "::notice title=Stub image::Building $image from scratch: no image with this tag exists in the registry yet.\n";
        return;
    }

    $oldKey = $labels[CACHE_KEY_LABEL] ?? null;

    if ($oldKey === $cacheKey) {
        echo "::notice title=Stub image::$image is up to date (cache key $cacheKey unchanged); "
            . "the extension layer is reused and nothing is rebuilt.\n";
        return;
    }

    echo "::notice title=Stub image::Rebuilding $image because the cache key changed"
        . ($oldKey === null ? ' (the existing image predates cache-key labelling)' : '') . ".\n";
    echo "  old cache key: " . ($oldKey ?? '(none)') . "\n";
    echo "  new cache key: $cacheKey\n";

    $encodedOld = $labels[COMPONENTS_LABEL] ?? '';
    $decodedOld = $encodedOld === '' ? false : base64_decode($encodedOld, true);
    if ($decodedOld === false) {
        echo "  (the existing image has no component breakdown, so the individual change cannot be shown)\n";
        return;
    }

    $oldMap = componentMap(explode("\n", $decodedOld));
    $newMap = componentMap($components);

    echo "  changed components (this is why the image is rebuilt):\n";
    $any = false;
    foreach ($newMap as $name => $version) {
        if (!array_key_exists($name, $oldMap)) {
            echo "    + $name = $version (added)\n";
            $any = true;
        } elseif ($oldMap[$name] !== $version) {
            echo "    ~ $name: {$oldMap[$name]} -> $version\n";
            $any = true;
        }
    }
    foreach ($oldMap as $name => $version) {
        if (!array_key_exists($name, $newMap)) {
            echo "    - $name = $version (removed)\n";
            $any = true;
        }
    }
    if (!$any) {
        echo "    (no component version differs; the key changed for another reason, e.g. the key format itself)\n";
    }
}

/**
 * A RUN instruction installing extensions with PIE, or nothing.
 *
 * @param array<string, string> $pieInstalls
 */
function pieStep(array $pieInstalls, string $cflags): string
{
    if ($pieInstalls === []) {
        return '';
    }
    $packages = [];
    $commands = [];
    foreach ($pieInstalls as $extension => $version) {
        $pie = PIE_PACKAGES[explode('-', $extension, 2)[0]];
        $packages = array_merge($packages, $pie['apk']);
        $commands[] = "{$cflags}pie install --with-php-config=/usr/local/bin/php-config "
            . implode(' ', [$pie['package'] . ':' . $version, ...$pie['options']]);
    }
    $pieVersion = PIE_VERSION;
    return "\nRUN case \"\$(uname -m)\" in aarch64) arch=ARM64 ;; *) arch=X64 ;; esac && \\\n"
        . "    curl -fsSL -o /usr/local/bin/pie https://github.com/php/pie/releases/download/$pieVersion/pie-Linux-\$arch && \\\n"
        . "    chmod +x /usr/local/bin/pie && \\\n"
        . '    apk add --no-cache $PHPIZE_DEPS libtool ' . implode(' ', array_unique($packages)) . " && \\\n    "
        . implode(" && \\\n    ", $commands) . "\n";
}

/** @param array<string, string> $pieInstalls */
function generateDockerfile(string $version, array $extensions, array $pieInstalls): string
{
    $installerUrl = INSTALLER_URL;
    $baseImage = phpBaseImage($version);

    $extensionList = implode(' ', array_diff($extensions, array_keys($pieInstalls)));
    // The base images of a version with no release come without pecl, and install-php-extensions
    // refuses a version it doesn't know yet: install PEAR, and let it treat the version like the
    // ones it supports (else it falls back to pickle, which doesn't run on the new version either).
    $supportStep = '';
    if (isset(BASE_IMAGE_TAGS[$version])) {
        $majorMinor = str_replace('.', '0', $version);
        $supportStep = " && \\\n    sed -i -E '/^[[:space:]]*505 \\| 506 \\|/ s/\\) ;;\$/ | $majorMinor) ;;/'"
            . ' /usr/local/bin/install-php-extensions'
            . " && \\\n    curl -fsSL https://pear.php.net/install-pear-nozlib.phar -o /tmp/pear.phar"
            . " && \\\n    php /tmp/pear.phar && rm /tmp/pear.phar";
    }
    // 8.6 dropped XtOffsetOf, an alias of offsetof, which imagick still uses
    $cflags = isset(BASE_IMAGE_TAGS[$version]) ? "CFLAGS='-DXtOffsetOf=offsetof' " : '';
    $packages = BASE_IMAGE_PACKAGES[$version] ?? [];
    $packageStep = $packages === []
        ? ''
        : 'apk add --no-cache ' . implode(' ', $packages) . " && \\\n    ";

    // grpc takes the longest to build by far: it gets a layer of its own, before everything
    // else, which stays cached until the base image or the grpc version changes
    $ownLayerInstalls = array_filter(
        $pieInstalls,
        static fn(string $extension): bool => PIE_PACKAGES[explode('-', $extension, 2)[0]]['own_layer'] ?? false,
        ARRAY_FILTER_USE_KEY,
    );
    $ownLayerStep = pieStep($ownLayerInstalls, $cflags);
    // after install-php-extensions, which installs igbinary for the memcached and redis serializers
    $pieStep = pieStep(array_diff_key($pieInstalls, $ownLayerInstalls), $cflags);

    return <<<DOCKERFILE
        # Generated on the fly by bin/stubs/build_docker.php - do not commit.
        FROM $baseImage
        {$ownLayerStep}
        ADD $installerUrl /usr/local/bin/

        RUN chmod +x /usr/local/bin/install-php-extensions{$supportStep}

        # CACHEBUST is a hash of the PHP base image digest and every installed
        # extension's version, resolved from each extension's real source. It changes
        # exactly when PHP or any extension version changes, rebuilding the layer below.
        ARG CACHEBUST
        RUN echo "cache key: \${CACHEBUST:-none}" && \\
            {$packageStep}{$cflags}install-php-extensions $extensionList
        {$pieStep}
        RUN echo 'zend_extension=opcache' > /usr/local/etc/php/php.ini
        DOCKERFILE;
}

// --- Entry point -------------------------------------------------------------

$arg = $argv[1] ?? null;

if ($arg === null) {
    fail('Usage: build_docker.php <version>|--versions');
}

if ($arg === '--versions') {
    echo json_encode(VERSIONS) . PHP_EOL;
    exit(0);
}

if ($arg === '--excludes') {
    // the extensions each version's image lacks, by their base name: gen_callmap.php keeps their overrides
    echo json_encode(array_map(
        static fn(array $extensions): array => array_map(
            static fn(string $extension): string => explode('-', $extension, 2)[0],
            $extensions,
        ),
        EXCLUDES,
    )) . PHP_EOL;
    exit(0);
}

if ($arg === '--versions-plain') {
    echo implode(' ', VERSIONS) . PHP_EOL;
    exit(0);
}

if ($arg === '--dockerfile') {
    $version = $argv[2] ?? fail('Usage: build_docker.php --dockerfile <version>');
    if (!in_array($version, VERSIONS, true)) {
        fail("Unsupported version '$version'.");
    }
    $extensions = extensionsFor($version);
    echo generateDockerfile($version, $extensions, pieInstalls($extensions, phpBaseVersion($version))) . PHP_EOL;
    exit(0);
}

if ($arg === '--cache-key') {
    $version = $argv[2] ?? fail('Usage: build_docker.php --cache-key <version>');
    if (!in_array($version, VERSIONS, true)) {
        fail("Unsupported version '$version'.");
    }
    $extensions = extensionsFor($version);
    $pieInstalls = pieInstalls($extensions, phpBaseVersion($version));
    echo 'CACHEBUST = ' . cacheKey($version, $extensions, $pieInstalls, phpBaseDigest($version)) . PHP_EOL;
    exit(0);
}

$version = $arg;
if (!in_array($version, VERSIONS, true)) {
    fail("Unsupported version '$version'. Supported: " . implode(', ', VERSIONS));
}

$actor = getenv('ACTOR');
if ($actor === false || $actor === '') {
    fail('The ACTOR environment variable (ghcr.io namespace) must be set.');
}

$extensions = extensionsFor($version);
$phpBaseDigest = phpBaseDigest($version);
$pieInstalls = pieInstalls($extensions, phpBaseVersion($version));
$components = resolveComponents($version, $extensions, $pieInstalls, $phpBaseDigest);
$cacheKey = cacheKeyFromComponents($components);
echo "CACHEBUST = $cacheKey\n";

// Non-canonical builds (feature branches) push a branch-scoped tag so they never
// clobber the shared canonical internal_stubs_<version> image that gen_callmap.sh
// and other consumers pull. TAG_SUFFIX is empty for the canonical build.
$tag = "internal_stubs_$version";
$suffix = getenv('TAG_SUFFIX');
if ($suffix !== false && $suffix !== '') {
    $tag .= '-' . preg_replace('/[^A-Za-z0-9._-]+/', '-', $suffix);
}
$image = "ghcr.io/$actor/psalm:$tag";

// The layer cache is intentionally shared across branches (it is content-addressed
// via CACHEBUST), so branches reuse each other's compiled extension layers.
$cacheRef = "ghcr.io/$actor/psalm:internal_stubs_{$version}_buildcache";

// The Dockerfile is generated on the fly into a throwaway temp directory (never
// inside the repo), which also serves as the - otherwise empty - build context.
$context = sys_get_temp_dir() . '/psalm-stub-' . $version . '-' . bin2hex(random_bytes(6));
if (!mkdir($context, 0o700, true) && !is_dir($context)) {
    fail("Could not create build context directory: $context");
}
file_put_contents("$context/Dockerfile", generateDockerfile($version, $extensions, $pieInstalls));

// Explain, before building, whether this run rebuilds the image and why, by
// diffing the resolved components against those labelled on the pushed image.
explainRebuild($image, $cacheKey, $components);

// Label the image with the cache key and the component breakdown so the next
// build can report exactly what changed (base64 keeps the newline-joined list
// safe as a single label value).
$componentsLabel = base64_encode(implode("\n", $components));

$command = sprintf(
    'docker buildx build --push --pull %s -f %s -t %s --build-arg CACHEBUST=%s --label %s --label %s --cache-from %s --cache-to %s',
    escapeshellarg($context),
    escapeshellarg("$context/Dockerfile"),
    escapeshellarg($image),
    escapeshellarg($cacheKey),
    escapeshellarg(CACHE_KEY_LABEL . '=' . $cacheKey),
    escapeshellarg(COMPONENTS_LABEL . '=' . $componentsLabel),
    escapeshellarg("type=registry,ref=$cacheRef"),
    escapeshellarg("type=registry,ref=$cacheRef,mode=max,image-manifest=true,oci-mediatypes=true,ignore-error=true"),
);
run($command);

unlink("$context/Dockerfile");
rmdir($context);
