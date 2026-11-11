<?php // phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols, Generic.Files.LineLength.TooLong


declare(strict_types=1);

use Composer\Semver\Semver;
use Composer\Semver\VersionParser;

/**
 * Generates the stub image Dockerfile of a PHP version from the committed lock (image_lock.php), and builds and
 * pushes the image, reusing a persistent buildx registry cache.
 *
 * The lock pins every input of an image: the PHP base image digest, the install-php-extensions release and its hash,
 * and the version (or commit) of every separately released extension. The last layer of an image fails the build
 * unless PHP and those extensions have their locked versions. Images are tagged with a hash of their Dockerfile, so
 * each is built once per namespace.
 *
 * New versions are only resolved (from the base image registry, Packagist, GitHub, and a probe build for the
 * versions install-php-extensions picks itself) for the inputs the lock lacks, or for all of them with --refresh.
 *
 * Usage:
 *   php build_docker.php [--refresh] <version>    resolve what the lock lacks, then build & push ghcr.io/$ACTOR/psalm:<tag>
 *   php build_docker.php [--refresh] --globals    resolve the installer release and the phpstorm-stubs commit
 *   php build_docker.php --dockerfile <version>   print the Dockerfile generated from the lock
 *   php build_docker.php --tag <version>          print the image tag
 *   php build_docker.php --phpstorm-stubs         print the locked phpstorm-stubs commit
 *   php build_docker.php --stale                  print the versions whose reflected callmaps don't match the lock (JSON)
 *   php build_docker.php --stale-plain            the same, space separated
 *   php build_docker.php --record <version>...    stamp the reflected callmaps of versions as matching the lock
 *   php build_docker.php --lock-part [<version>]  print the installer, phpstorm-stubs and version entries of the lock (JSON)
 *   php build_docker.php --merge-lock <dir>       merge the JSON lock parts in a directory into the lock
 *   php build_docker.php --versions               print the supported versions as a JSON array
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
    'pgsql', 'intl', 'gmp', 'mbstring', 'mysqli', 'pdo_mysql', 'pdo_pgsql',
    'snmp', 'tidy', 'xsl', 'xml', 'dom', 'iconv', 'zip', 'igbinary', 'gd', 'bcmath',
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
 * Where each extension comes from, keyed by the base extension name (without any "-<stability>" suffix):
 *   'php'                       bundled with PHP itself: pinned by the base image digest
 *   'pecl'                      a PECL release: the lock pins the PIE release, or the one install-php-extensions picks
 *   'git:<owner>/<repo>@<ref>'  installed from git: the lock pins the commit <ref> pointed to
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
    'mysqli' => 'php',
    'pdo_mysql' => 'php',
    'pdo_pgsql' => 'php',
    'snmp' => 'php',
    'tidy' => 'php',
    'xsl' => 'php',
    'xml' => 'php',
    'dom' => 'php',
    'iconv' => 'php',
    'zip' => 'php',
    'gd' => 'php',
    'bcmath' => 'php',
    'ffi' => 'php',
];

const INSTALLER_REPOSITORY = 'mlocati/docker-php-extension-installer';
const PHPSTORM_STUBS_REPOSITORY = 'JetBrains/phpstorm-stubs';

/** The exact inputs of every image. Committed; only this script changes it. */
const IMAGE_LOCK = __DIR__ . '/image_lock.php';

/** Per version, the stamp (see stamp()) of the committed reflected callmaps. */
const CALLMAP_INPUTS = __DIR__ . '/../../dictionaries/autogen/CallMapInputs.php';

/**
 * The lock's values end up in Dockerfiles and shell commands, so anything else is rejected. Extension specs are
 * "pecl@<version>", "pie@<package>:<version>" or "git@<commit>:<version>".
 */
const LOCK_PATTERNS = [
    'installer' => '~^\d+\.\d+\.\d+@sha256:[0-9a-f]{64}$~D',
    'phpstorm-stubs' => '~^[0-9a-f]{40}$~D',
    'php' => '~^\d+\.\d+\.\d+[A-Za-z0-9.]*@sha256:[0-9a-f]{64}$~D',
    'extension' => '~^(pecl@\d[A-Za-z0-9.]*|pie@[a-z0-9_.-]+/[a-z0-9_.-]+:v?\d[A-Za-z0-9.-]*|git@[0-9a-f]{40}:\d[A-Za-z0-9.]*)$~D',
];

/** A version an extension reports that install-php-extensions takes back (<extension>-<version>). */
const REPORTED_VERSION = '~^\d[A-Za-z0-9.]*$~D';

// Both run on the PHP of the images, 7.0 included. The first fails the build unless PHP and every separately
// released extension have their locked versions.
const VERIFY_SCRIPT = '$failed = PHP_VERSION !== $argv[1];'
    . ' if ($failed) { fwrite(STDERR, "php: locked " . $argv[1] . ", installed " . PHP_VERSION . "\n"); }'
    . ' foreach (array_slice($argv, 2) as $locked) {'
    . ' list($extension, $version) = explode("=", $locked, 2);'
    . ' $installed = phpversion($extension);'
    . ' if ($installed !== $version) {'
    . ' $failed = true;'
    . ' fwrite(STDERR, $extension . ": locked " . $version . ", installed " . var_export($installed, true) . "\n");'
    . ' }'
    . ' }'
    . ' exit($failed ? 1 : 0);';
const PROBE_SCRIPT = 'foreach (array_slice($argv, 1) as $extension) {'
    . ' $version = phpversion($extension);'
    . ' echo $extension, "=", $version === false ? "" : $version, "\n";'
    . ' }';

function fail(string $message): never
{
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
}

/** Run a command, streaming output; retry transient failures (registry and download 5xx), abort if all fail. */
function run(string $cmd, int $attempts = 3): void
{
    for ($attempt = 1;; $attempt++) {
        echo "> $cmd\n";
        passthru($cmd, $exit);
        if ($exit === 0) {
            return;
        }
        if ($attempt === $attempts) {
            exit($exit);
        }
        echo "Attempt $attempt of $attempts failed with exit code $exit, retrying in 30 s\n";
        sleep(30);
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

/** A curl command fetching a URL (with retries). */
function curl(string $url, array $headers = []): string
{
    $cmd = 'curl --fail --silent --show-error --location --max-time 30 --retry 5 --retry-all-errors --retry-delay 2';
    foreach ($headers as $header) {
        $cmd .= ' -H ' . escapeshellarg($header);
    }
    return $cmd . ' ' . escapeshellarg($url);
}

/** Fetch a URL as a string (with retries), aborting on persistent failure. */
function fetch(string $url, array $headers = []): string
{
    return capture(curl($url, $headers));
}

/** The sha256 of the file at a URL (fetch() would drop its trailing whitespace). */
function fetchHash(string $url): string
{
    $file = tempnam(sys_get_temp_dir(), 'psalm-stub-');
    capture(curl($url) . ' --output ' . escapeshellarg($file));
    $hash = hash_file('sha256', $file);
    unlink($file);
    return $hash;
}

/** Headers for the GitHub API, authenticated when GITHUB_TOKEN is set. */
function githubHeaders(string $accept): array
{
    $headers = ['Accept: ' . $accept];
    if (($token = getenv('GITHUB_TOKEN')) !== false && $token !== '') {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    return $headers;
}

/** The commit a branch of a GitHub repository points to. */
function gitCommit(string $repository, string $ref): string
{
    $sha = trim(fetch("https://api.github.com/repos/$repository/commits/$ref", githubHeaders('application/vnd.github.sha')));
    if (!preg_match('/^[0-9a-f]{40}$/D', $sha)) {
        fail("Could not resolve the commit of $repository@$ref (got '$sha').");
    }
    return $sha;
}

function installerUrl(string $release): string
{
    return 'https://github.com/' . INSTALLER_REPOSITORY . "/releases/download/$release/install-php-extensions";
}

function supportedVersion(?string $version, string $usage): string
{
    if ($version === null) {
        fail("Usage: build_docker.php $usage");
    }
    if (!in_array($version, VERSIONS, true)) {
        fail("Unsupported version '$version'. Supported: " . implode(', ', VERSIONS));
    }
    return $version;
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
        if (!preg_match('/^[A-Za-z0-9._-]+$/D', $extension)) {
            fail("Invalid extension name: $extension");
        }
        $base = explode('-', $extension, 2)[0];
        if (!isset(SOURCES[$base])) {
            fail("No source defined for extension '$extension' (base '$base'); add it to SOURCES.");
        }
    }
    return $extensions;
}

/**
 * The separately released extensions of a version, whose versions the lock pins.
 *
 * @return array<string, string> base name => name in extensionsFor()
 */
function lockedExtensions(string $version): array
{
    $extensions = [];
    foreach (extensionsFor($version) as $extension) {
        $base = explode('-', $extension, 2)[0];
        if (SOURCES[$base] !== 'php') {
            $extensions[$base] = $extension;
        }
    }
    ksort($extensions);
    return $extensions;
}

/** @return array{string, string}|null the repository and ref of an extension installed from git */
function gitSource(string $base): ?array
{
    return preg_match('#^git:([^@]+)@(.+)$#', SOURCES[$base], $m) ? [$m[1], $m[2]] : null;
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

/** The PHP version of a PHP base image, e.g. 8.4.14. */
function phpBaseVersion(string $image): string
{
    $config = imageConfig($image) ?? [];
    foreach ($config['Env'] ?? $config['env'] ?? [] as $env) {
        if (is_string($env) && str_starts_with($env, 'PHP_VERSION=')) {
            return substr($env, strlen('PHP_VERSION='));
        }
    }
    fail("Could not determine the PHP version of $image.");
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
                // it ends up in the Dockerfile's shell commands
                if (!preg_match('/^v?\d[\w.-]*$/D', $release['version'])) {
                    fail("Unexpected version '{$release['version']}' of PIE package '$package'.");
                }
                $installs[$extension] = $release['version'];
            }
            break;
        }
    }
    return $installs;
}

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

// --- The lock and the stamps ---------------------------------------------------

function loadPhpData(string $path): array
{
    $data = require $path;
    if (!is_array($data)) {
        fail("$path does not return an array.");
    }
    return $data;
}

function writePhpData(string $path, array $data): void
{
    if (file_put_contents($path, "<?php // phpcs:ignoreFile\n\nreturn " . var_export($data, true) . ";\n") === false) {
        fail("Could not write $path.");
    }
}

/**
 * Validate lock data, in the order it is committed in. Entries of versions no longer built are dropped.
 *
 * @return array{installer?: string, phpstorm-stubs?: string, versions: array<string, array{php: string, extensions: array<string, string>}>}
 */
function validLock(array $data, string $source): array
{
    $lock = [];
    foreach (['installer', 'phpstorm-stubs'] as $key) {
        if (!isset($data[$key])) {
            continue;
        }
        if (!is_string($data[$key]) || !preg_match(LOCK_PATTERNS[$key], $data[$key])) {
            fail("$source: invalid $key " . var_export($data[$key], true) . '.');
        }
        $lock[$key] = $data[$key];
    }
    $versions = $data['versions'] ?? [];
    if (!is_array($versions)) {
        fail("$source: invalid versions.");
    }
    $lock['versions'] = [];
    foreach (VERSIONS as $version) {
        if (!isset($versions[$version])) {
            continue;
        }
        $entry = $versions[$version];
        if (!is_array($entry)
            || !is_string($entry['php'] ?? null)
            || !preg_match(LOCK_PATTERNS['php'], $entry['php'])
            || !str_starts_with($entry['php'], "$version.")
            || !is_array($entry['extensions'] ?? null)
        ) {
            fail("$source: invalid entry for PHP $version.");
        }
        $extensions = $entry['extensions'];
        foreach ($extensions as $extension => $spec) {
            if (!preg_match('/^[a-z0-9_]+$/D', (string) $extension)
                || !is_string($spec)
                || !preg_match(LOCK_PATTERNS['extension'], $spec)
            ) {
                fail("$source: invalid spec " . var_export($spec, true) . " of $extension for PHP $version.");
            }
        }
        ksort($extensions);
        $lock['versions'][$version] = ['php' => $entry['php'], 'extensions' => $extensions];
    }
    return $lock;
}

function loadLock(): array
{
    return validLock(loadPhpData(IMAGE_LOCK), IMAGE_LOCK);
}

function writeLock(array $lock): void
{
    writePhpData(IMAGE_LOCK, validLock($lock, 'the resolved lock'));
}

/** Whether a locked spec still installs an extension the way the constants above ask for. */
function specMatches(string $extension, string $spec): bool
{
    [$base, $suffix] = array_pad(explode('-', $extension, 2), 2, '');
    if (gitSource($base) !== null) {
        return str_starts_with($spec, 'git@');
    }
    if (preg_match('/^\d/', $suffix)) {
        return $spec === "pecl@$suffix";
    }
    if (str_starts_with($spec, 'pie@')) {
        return isset(PIE_PACKAGES[$base]) && str_starts_with($spec, 'pie@' . PIE_PACKAGES[$base]['package'] . ':');
    }
    return str_starts_with($spec, 'pecl@');
}

/**
 * The lock entry of a version, if the lock pins every input of its image.
 *
 * @return array{php: string, extensions: array<string, string>}|null
 */
function lockEntry(array $lock, string $version): ?array
{
    $entry = $lock['versions'][$version] ?? null;
    if ($entry === null || !isset($lock['installer'], $lock['phpstorm-stubs'])) {
        return null;
    }
    $extensions = lockedExtensions($version);
    if (array_keys($extensions) !== array_keys($entry['extensions'])) {
        return null;
    }
    foreach ($extensions as $base => $extension) {
        if (!specMatches($extension, $entry['extensions'][$base])) {
            return null;
        }
    }
    return $entry;
}

/** The version an extension installed from a locked spec reports. */
function specVersion(string $spec): string
{
    [$type, $value] = explode('@', $spec, 2);
    return match ($type) {
        'pecl' => $value,
        'pie' => preg_replace('/^v/', '', explode(':', $value, 2)[1]),
        'git' => explode(':', $value, 2)[1],
    };
}

/** Resolve the installer release and the phpstorm-stubs commit, if the lock lacks them or with $refresh. */
function resolveGlobals(array $lock, bool $refresh): array
{
    $resolved = $lock;
    if ($refresh || !isset($lock['installer'])) {
        $release = json_decode(fetch(
            'https://api.github.com/repos/' . INSTALLER_REPOSITORY . '/releases/latest',
            githubHeaders('application/vnd.github+json'),
        ), true);
        $tag = is_array($release) ? ($release['tag_name'] ?? null) : null;
        if (!is_string($tag) || !preg_match('/^\d+\.\d+\.\d+$/D', $tag)) {
            fail('Could not resolve the latest release of ' . INSTALLER_REPOSITORY . '.');
        }
        $resolved['installer'] = "$tag@sha256:" . fetchHash(installerUrl($tag));
    }
    if ($refresh || !isset($lock['phpstorm-stubs'])) {
        $resolved['phpstorm-stubs'] = gitCommit(PHPSTORM_STUBS_REPOSITORY, 'master');
    }
    foreach (['installer', 'phpstorm-stubs'] as $key) {
        if (($lock[$key] ?? null) !== $resolved[$key]) {
            echo "::notice title=Stub image lock::$key: " . ($lock[$key] ?? '(none)') . " -> $resolved[$key]\n";
        }
    }
    return $resolved;
}

/**
 * Resolve the inputs of a version's image the lock lacks or the constants above changed (all of them with
 * $refresh): the base image, the PIE release of the extensions installed with PIE, the commit of the ones installed
 * from git, and the versions install-php-extensions picks for the others. It pins old versions for old PHP, so those
 * are read from a probe build, never predicted.
 */
function resolveVersion(array $lock, string $version, bool $refresh): array
{
    $old = $lock['versions'][$version] ?? ['php' => null, 'extensions' => []];
    if ($refresh || $old['php'] === null) {
        $digest = phpBaseDigest($version);
        $php = phpBaseVersion(phpBaseImage($version) . "@$digest") . "@$digest";
        if (!preg_match(LOCK_PATTERNS['php'], $php) || !str_starts_with($php, "$version.")) {
            fail("Unexpected base image of PHP $version: '$php'.");
        }
    } else {
        $php = $old['php'];
    }

    $specs = [];
    $probes = [];
    foreach (lockedExtensions($version) as $base => $extension) {
        $locked = $refresh ? null : ($old['extensions'][$base] ?? null);
        if ($locked !== null && specMatches($extension, $locked)) {
            $specs[$base] = $locked;
            continue;
        }
        $suffix = explode('-', $extension, 2)[1] ?? '';
        $git = gitSource($base);
        if ($git !== null) {
            $commit = gitCommit($git[0], $git[1]);
            $probes[$base] = ["$git[0]@$commit", "git@$commit:"];
        } elseif (preg_match('/^\d/', $suffix)) {
            $specs[$base] = "pecl@$suffix";
        } elseif (($pie = pieInstalls([$extension], explode('@', $php, 2)[0])) !== []) {
            $specs[$base] = 'pie@' . PIE_PACKAGES[$base]['package'] . ':' . $pie[$extension];
        } else {
            $probes[$base] = [$extension, 'pecl@'];
        }
    }
    if ($probes !== []) {
        $reported = probe($version, $lock['installer'], $php, array_map(static fn(array $probe): string => $probe[0], $probes));
        foreach ($probes as $base => [, $prefix]) {
            if (!preg_match(REPORTED_VERSION, $reported[$base] ?? '')) {
                fail("PHP $version: $base reports the version " . var_export($reported[$base] ?? null, true) . ', which cannot be pinned.');
            }
            $specs[$base] = $prefix . $reported[$base];
        }
    }
    ksort($specs);

    if ($php !== $old['php']) {
        echo "::notice title=Stub image lock::PHP $version: php " . ($old['php'] ?? '(none)') . " -> $php\n";
    }
    foreach (array_keys($old['extensions'] + $specs) as $base) {
        if (($old['extensions'][$base] ?? null) !== ($specs[$base] ?? null)) {
            echo "::notice title=Stub image lock::PHP $version: $base " . ($old['extensions'][$base] ?? '(none)')
                . ' -> ' . ($specs[$base] ?? '(none)') . "\n";
        }
    }

    $lock['versions'][$version] = ['php' => $php, 'extensions' => $specs];
    return validLock($lock, "the lock resolved for PHP $version");
}

/**
 * Build extensions with install-php-extensions alone, on the locked base image and installer, and return the version
 * each reports.
 *
 * @param array<string, string> $installs base name => install-php-extensions argument
 * @return array<string, string>
 */
function probe(string $version, string $installer, string $php, array $installs): array
{
    $dockerfile = fromStep($version, $php) . " AS probe\n"
        . installerStep($version, $installer) . "\n"
        . installStep($version, array_values($installs)) . "\n"
        . 'RUN php -r ' . escapeshellarg(PROBE_SCRIPT) . ' -- ' . implode(' ', array_keys($installs)) . " > /versions.txt\n\n"
        . "FROM scratch\nCOPY --from=probe /versions.txt /\n";
    $context = buildContext($version, $dockerfile);
    // the instructions name no versions, so a cached layer would hold the versions of an older build
    run(sprintf(
        'docker buildx build --no-cache --output %s -f %s %s',
        escapeshellarg("type=local,dest=$context/out"),
        escapeshellarg("$context/Dockerfile"),
        escapeshellarg($context),
    ));
    $reported = [];
    foreach (file("$context/out/versions.txt", FILE_IGNORE_NEW_LINES) ?: [] as $line) {
        [$base, $installed] = array_pad(explode('=', $line, 2), 2, '');
        $reported[$base] = $installed;
    }
    unlink("$context/out/versions.txt");
    rmdir("$context/out");
    removeContext($context);
    return $reported;
}

/**
 * The stamp of a version's reflected callmaps: a hash of every input of the reflection (the image, the extraction
 * script and the downloaded stubs), and hashes of the files it wrote. Null if the lock doesn't pin the image or a
 * file is missing.
 *
 * @return array{inputs: string, files: array<string, string>}|null
 */
function stamp(string $version, array $lock): ?array
{
    if (lockEntry($lock, $version) === null) {
        return null;
    }
    $suffix = str_replace('.', '', $version);
    $files = [];
    foreach (["CallMap_$suffix.php", "CallMapExtensions_$suffix.php"] as $file) {
        $path = dirname(CALLMAP_INPUTS) . "/$file";
        if (!is_file($path)) {
            return null;
        }
        $files[$file] = normalizedHash($path);
    }
    return [
        'inputs' => hash('sha256', implode("\n", [
            generateDockerfile($version, $lock),
            normalizedHash(__DIR__ . '/gen_base_callmap.php'),
            $lock['phpstorm-stubs'],
        ])),
        'files' => $files,
    ];
}

/** A hash of a file that doesn't depend on the line endings of the checkout. */
function normalizedHash(string $path): string
{
    $contents = file_get_contents($path);
    if ($contents === false) {
        fail("Could not read $path.");
    }
    return hash('sha256', str_replace("\r\n", "\n", $contents));
}

/** @return list<string> the versions whose reflected callmaps weren't generated from the locked inputs */
function staleVersions(array $lock): array
{
    $stamps = loadPhpData(CALLMAP_INPUTS);
    return array_values(array_filter(
        VERSIONS,
        static fn(string $version): bool => ($stamps[$version] ?? null) !== (stamp($version, $lock) ?? false),
    ));
}

/** @param list<string> $versions */
function record(array $versions, array $lock): void
{
    $stamps = loadPhpData(CALLMAP_INPUTS);
    foreach ($versions as $version) {
        $stamps[$version] = stamp($version, $lock)
            ?? fail("Cannot stamp PHP $version: the lock doesn't pin its image, or a reflected callmap is missing.");
    }
    $ordered = [];
    foreach (VERSIONS as $version) {
        if (isset($stamps[$version])) {
            $ordered[$version] = $stamps[$version];
        }
    }
    writePhpData(CALLMAP_INPUTS, $ordered);
}

/** Merge the lock parts (see --lock-part) in a directory into the lock; parts must not contradict each other. */
function mergeLock(array $lock, string $directory): array
{
    $files = glob("$directory/*.json") ?: [];
    if ($files === []) {
        fail("No lock parts in $directory.");
    }
    $merged = ['versions' => []];
    foreach ($files as $file) {
        $data = json_decode((string) file_get_contents($file), true);
        $part = validLock(is_array($data) ? $data : fail("$file is not a lock part."), $file);
        foreach ($part['versions'] as $version => $entry) {
            if (isset($merged['versions'][$version]) && $merged['versions'][$version] !== $entry) {
                fail("$file: the entry for PHP $version differs from that of another part.");
            }
            $merged['versions'][$version] = $entry;
        }
        unset($part['versions']);
        foreach ($part as $key => $value) {
            if (isset($merged[$key]) && $merged[$key] !== $value) {
                fail("$file: its $key differs from that of another part.");
            }
            $merged[$key] = $value;
        }
    }
    $merged['versions'] += $lock['versions'];
    return validLock($merged + $lock, 'the merged lock');
}

// --- The Dockerfile ------------------------------------------------------------

/** The FROM instruction of a version's image: the base image, by digest. */
function fromStep(string $version, string $php): string
{
    return 'FROM ' . phpBaseImage($version) . '@' . explode('@', $php, 2)[1];
}

/** The locked install-php-extensions release, checked against its hash, and what it needs to run on the version. */
function installerStep(string $version, string $installer): string
{
    [$release, $checksum] = explode('@', $installer, 2);
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
    return "ADD --checksum=$checksum " . installerUrl($release) . " /usr/local/bin/\n\n"
        . "RUN chmod +x /usr/local/bin/install-php-extensions{$supportStep}";
}

/** 8.6 dropped XtOffsetOf, an alias of offsetof, which imagick still uses */
function cflags(string $version): string
{
    return isset(BASE_IMAGE_TAGS[$version]) ? "CFLAGS='-DXtOffsetOf=offsetof' " : '';
}

/** @param list<string> $arguments */
function installStep(string $version, array $arguments): string
{
    $packages = BASE_IMAGE_PACKAGES[$version] ?? [];
    $packageStep = $packages === []
        ? ''
        : 'apk add --no-cache ' . implode(' ', $packages) . " && \\\n    ";
    return "RUN {$packageStep}" . cflags($version) . 'install-php-extensions ' . implode(' ', $arguments);
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
        . "    curl -fsSL --retry 5 --retry-delay 2 -o /usr/local/bin/pie https://github.com/php/pie/releases/download/$pieVersion/pie-Linux-\$arch && \\\n"
        . "    chmod +x /usr/local/bin/pie && \\\n"
        . '    apk add --no-cache $PHPIZE_DEPS libtool ' . implode(' ', array_unique($packages)) . " && \\\n    "
        . implode(" && \\\n    ", $commands) . "\n";
}

function generateDockerfile(string $version, array $lock): string
{
    $entry = lockEntry($lock, $version)
        ?? fail("The lock doesn't pin every input of the PHP $version image: run php bin/stubs/build_docker.php $version.");

    $arguments = [];
    $pieInstalls = [];
    $locked = [explode('@', $entry['php'], 2)[0]];
    foreach (extensionsFor($version) as $extension) {
        $base = explode('-', $extension, 2)[0];
        $spec = $entry['extensions'][$base] ?? null;
        if ($spec === null) {
            // bundled with PHP
            $arguments[] = $extension;
            continue;
        }
        $locked[] = "$base=" . specVersion($spec);
        [$type, $value] = explode('@', $spec, 2);
        if ($type === 'pie') {
            $pieInstalls[$extension] = explode(':', $value, 2)[1];
        } elseif ($type === 'git') {
            $arguments[] = gitSource($base)[0] . '@' . explode(':', $value, 2)[0];
        } else {
            $arguments[] = "$base-$value";
        }
    }

    $cflags = cflags($version);
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
    $fromStep = fromStep($version, $entry['php']);
    $installerStep = installerStep($version, $lock['installer']);
    $installStep = installStep($version, $arguments);
    $verifyStep = 'RUN php -r ' . escapeshellarg(VERIFY_SCRIPT) . ' -- ' . implode(' ', $locked);

    return <<<DOCKERFILE
        # Generated by bin/stubs/build_docker.php from bin/stubs/image_lock.php - do not commit.
        {$fromStep}
        {$ownLayerStep}
        {$installerStep}

        {$installStep}
        {$pieStep}
        RUN echo 'zend_extension=opcache' > /usr/local/etc/php/php.ini

        {$verifyStep}
        DOCKERFILE;
}

/** The tag of an image: a hash of its Dockerfile, which pins all its inputs. */
function imageTag(string $version, string $dockerfile): string
{
    return "internal_stubs_$version-" . substr(hash('sha256', $dockerfile), 0, 16);
}

/** A throwaway build context (never inside the repo) holding just the Dockerfile. */
function buildContext(string $version, string $dockerfile): string
{
    $context = sys_get_temp_dir() . '/psalm-stub-' . $version . '-' . bin2hex(random_bytes(6));
    if (!mkdir($context, 0o700, true) && !is_dir($context)) {
        fail("Could not create build context directory: $context");
    }
    file_put_contents("$context/Dockerfile", $dockerfile);
    return $context;
}

function removeContext(string $context): void
{
    unlink("$context/Dockerfile");
    rmdir($context);
}

// --- Entry point -------------------------------------------------------------

$args = array_slice($argv, 1);
$refresh = ($args[0] ?? null) === '--refresh';
if ($refresh) {
    array_shift($args);
}
$arg = $args[0] ?? fail('Usage: build_docker.php [--refresh] <version>|--globals, or see the header of build_docker.php');
if ($refresh && $arg !== '--globals' && str_starts_with($arg, '--')) {
    fail('--refresh only applies to <version> and --globals.');
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

if ($arg === '--extensions-plain') {
    $version = supportedVersion($args[1] ?? null, '--extensions-plain <version>');
    echo implode(' ', array_map(
        static fn(string $extension): string => explode('-', $extension, 2)[0],
        extensionsFor($version),
    )) . PHP_EOL;
    exit(0);
}

if ($arg === '--external-extensions') {
    // Only bundled extensions follow PHP's version history; separately released extensions need their overrides kept.
    echo json_encode(array_keys(array_filter(SOURCES, static fn(string $source): bool => $source !== 'php'))) . PHP_EOL;
    exit(0);
}

if ($arg === '--dockerfile') {
    echo generateDockerfile(supportedVersion($args[1] ?? null, '--dockerfile <version>'), loadLock()) . PHP_EOL;
    exit(0);
}

if ($arg === '--tag') {
    $version = supportedVersion($args[1] ?? null, '--tag <version>');
    echo imageTag($version, generateDockerfile($version, loadLock())) . PHP_EOL;
    exit(0);
}

if ($arg === '--phpstorm-stubs') {
    echo (loadLock()['phpstorm-stubs'] ?? fail('The lock pins no phpstorm-stubs commit: run php bin/stubs/build_docker.php --globals.'))
        . PHP_EOL;
    exit(0);
}

if ($arg === '--stale') {
    echo json_encode(staleVersions(loadLock())) . PHP_EOL;
    exit(0);
}

if ($arg === '--stale-plain') {
    echo implode(' ', staleVersions(loadLock())) . PHP_EOL;
    exit(0);
}

if ($arg === '--record') {
    $versions = array_slice($args, 1);
    if ($versions === []) {
        fail('Usage: build_docker.php --record <version>...');
    }
    record(array_map(static fn(string $version): string => supportedVersion($version, '--record <version>...'), $versions), loadLock());
    exit(0);
}

if ($arg === '--lock-part') {
    $part = loadLock();
    $version = isset($args[1]) ? supportedVersion($args[1], '--lock-part [<version>]') : null;
    $part['versions'] = $version === null
        ? []
        : [$version => $part['versions'][$version] ?? fail("The lock has no entry for PHP $version.")];
    echo json_encode($part, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
    exit(0);
}

if ($arg === '--merge-lock') {
    writeLock(mergeLock(loadLock(), $args[1] ?? fail('Usage: build_docker.php --merge-lock <dir>')));
    exit(0);
}

if ($arg === '--globals') {
    writeLock(resolveGlobals(loadLock(), $refresh));
    exit(0);
}

$version = supportedVersion($arg, '[--refresh] <version>');

$actor = getenv('ACTOR');
if ($actor === false || $actor === '') {
    fail('The ACTOR environment variable (ghcr.io namespace) must be set.');
}

$lock = resolveVersion(resolveGlobals(loadLock(), false), $version, $refresh);
writeLock($lock);

$dockerfile = generateDockerfile($version, $lock);
$image = "ghcr.io/$actor/psalm:" . imageTag($version, $dockerfile);
if (imageConfig($image) !== null) {
    echo "::notice title=Stub image::$image exists already, nothing to build.\n";
    exit(0);
}

// The layer cache is shared by every image of a version, so a changed input only rebuilds the layers after it.
$cacheRef = "ghcr.io/$actor/psalm:internal_stubs_{$version}_buildcache";

$context = buildContext($version, $dockerfile);
run(sprintf(
    'docker buildx build --push %s -f %s -t %s --cache-from %s --cache-to %s',
    escapeshellarg($context),
    escapeshellarg("$context/Dockerfile"),
    escapeshellarg($image),
    escapeshellarg("type=registry,ref=$cacheRef"),
    escapeshellarg("type=registry,ref=$cacheRef,mode=max,image-manifest=true,oci-mediatypes=true,ignore-error=true"),
));
removeContext($context);
