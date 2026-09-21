<?php // phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols, Generic.Files.LineLength.TooLong


declare(strict_types=1);

/**
 * Generates the stub Dockerfile for a single PHP version on the fly and builds &
 * pushes it, reusing a persistent buildx registry cache.
 *
 * The cache is invalidated (via the CACHEBUST build arg) whenever PHP itself or
 * the resolved version of ANY installed extension changes. To do that exactly,
 * the cache key folds in, per extension, the version from that extension's real
 * source (see SOURCES): the PHP base image for bundled extensions, PECL for PECL
 * extensions, and the upstream git commit for extensions installed from a branch.
 *
 * Usage:
 *   php build_docker.php <version>   build & push ghcr.io/$ACTOR/psalm:internal_stubs_<version>
 *   php build_docker.php --versions  print the supported versions as a JSON array
 */

/** PHP versions we build stub images for. Single source of truth (also drives the CI matrix). */
const VERSIONS = ['7.0', '7.1', '7.2', '7.3', '7.4', '8.0', '8.1', '8.2', '8.3', '8.4', '8.5'];

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
];

/** Extensions removed for specific versions. */
const EXCLUDES = [
    // php-zmq does not build on PHP 8.5 (it calls zend_exception_get_default(), removed in 8.5).
    '8.5' => ['zmq'],
];

/**
 * Where install-php-extensions gets each extension's version from, keyed by the
 * base extension name (without any "-<stability>" suffix). Used to build a cache
 * key that changes exactly when the installed version would change:
 *   'php'                  bundled with PHP itself -> tracked by the base image digest
 *   'pecl'                 resolved from PECL       -> newest release on PECL
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
        $xml = fetch("https://pecl.php.net/rest/r/$base/allreleases.xml");
        if (!preg_match('#<v>([^<]+)</v>#', $xml, $m)) {
            fail("Could not parse a version for PECL extension '$base'.");
        }
        return 'pecl@' . $m[1];
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

/** The manifest digest of the PHP base image for a version. */
function phpBaseDigest(string $version): string
{
    $inspect = capture('docker buildx imagetools inspect ' . escapeshellarg("php:$version-alpine"));
    if (!preg_match('/^Digest:\s*(\S+)/m', $inspect, $m)) {
        fail("Could not determine the digest of php:$version-alpine.");
    }
    return $m[1];
}

/**
 * Resolve the cache-busting components for a version: the PHP base image digest,
 * the installer, and every extension's real version, as an ordered list of
 * "name=version" strings. The CACHEBUST key is a hash of exactly this list, so
 * the list also explains *why* the key (and thus the image) changed.
 *
 * @return list<string>
 */
function resolveComponents(string $version, array $extensions, string $phpBaseDigest): array
{
    $installerSha = hash('sha256', fetch(INSTALLER_URL));

    $components = [
        'php=' . $phpBaseDigest,
        'install-php-extensions=' . $installerSha,
    ];
    $versions = [];
    foreach ($extensions as $extension) {
        $versions[$extension] = extensionVersion($extension, $phpBaseDigest);
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

/** Build the cache-busting key for a version: PHP base image + every extension's real version. */
function cacheKey(string $version, array $extensions, string $phpBaseDigest): string
{
    return cacheKeyFromComponents(resolveComponents($version, $extensions, $phpBaseDigest));
}

const CACHE_KEY_LABEL = 'dev.psalm.stubs.cache-key';
const COMPONENTS_LABEL = 'dev.psalm.stubs.components';

/**
 * Read the labels of an image already in the registry, without pulling its
 * layers. Returns null when the image does not exist (or cannot be inspected).
 *
 * @return array<string, string>|null
 */
function imageLabels(string $image): ?array
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
    // expose a map of platform => config. Our stub images are single-platform,
    // but handle both so the check never crashes.
    $config = $data['config'] ?? null;
    if (!is_array($config)) {
        $entry = $data['linux/amd64'] ?? (reset($data) ?: null);
        $config = is_array($entry) ? ($entry['config'] ?? null) : null;
    }

    $labels = is_array($config) ? ($config['Labels'] ?? $config['labels'] ?? []) : [];
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

function generateDockerfile(string $version, array $extensions): string
{
    $extensionList = implode(' ', $extensions);
    $installerUrl = INSTALLER_URL;

    return <<<DOCKERFILE
        # Generated on the fly by bin/stubs/build_docker.php - do not commit.
        FROM php:$version-alpine

        ADD $installerUrl /usr/local/bin/

        RUN chmod +x /usr/local/bin/install-php-extensions

        # CACHEBUST is a hash of the PHP base image digest and every installed
        # extension's version, resolved from each extension's real source. It changes
        # exactly when PHP or any extension version changes, rebuilding the layer below.
        ARG CACHEBUST
        RUN echo "cache key: \${CACHEBUST:-none}" && \\
            install-php-extensions $extensionList

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

if ($arg === '--versions-plain') {
    echo implode(' ', VERSIONS) . PHP_EOL;
    exit(0);
}

if ($arg === '--dockerfile') {
    $version = $argv[2] ?? fail('Usage: build_docker.php --dockerfile <version>');
    if (!in_array($version, VERSIONS, true)) {
        fail("Unsupported version '$version'.");
    }
    echo generateDockerfile($version, extensionsFor($version)) . PHP_EOL;
    exit(0);
}

if ($arg === '--cache-key') {
    $version = $argv[2] ?? fail('Usage: build_docker.php --cache-key <version>');
    if (!in_array($version, VERSIONS, true)) {
        fail("Unsupported version '$version'.");
    }
    echo 'CACHEBUST = ' . cacheKey($version, extensionsFor($version), phpBaseDigest($version)) . PHP_EOL;
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
$components = resolveComponents($version, $extensions, $phpBaseDigest);
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
file_put_contents("$context/Dockerfile", generateDockerfile($version, $extensions));

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
