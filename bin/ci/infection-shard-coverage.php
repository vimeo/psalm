<?php

declare(strict_types=1);

// Writes the list of the source files of one shard of the mutation testing job, and their coverage in the XML format
// Infection reads, from the coverage of every chunk of the test suite: the XML of the whole source tree is too big for
// a runner's disk. The source files are those of infection.json5, given to the shards by size.

use SebastianBergmann\CodeCoverage\Report\Facade;
use SebastianBergmann\CodeCoverage\Serialization\Unserializer;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

if ($argc !== 6) {
    fwrite(STDERR, "Usage: $argv[0] <shard> <shard count> <coverage-php dir> <coverage-xml dir> <file list>\n");
    exit(1);
}

[, $shard, $shard_count, $coverage_dir, $xml_dir, $file_list] = $argv;
$shard = (int) $shard;
$shard_count = (int) $shard_count;
$root = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR;

$config = json_decode(
    preg_replace('~^\s*//.*$~m', '', file_get_contents($root . 'infection.json5')),
    true,
    flags: JSON_THROW_ON_ERROR,
);

$sizes = [];
foreach ($config['source']['directories'] as $directory) {
    $excludes = array_map(
        static fn(string $exclude): string => $root . $directory . DIRECTORY_SEPARATOR . $exclude,
        $config['source']['excludes'] ?? [],
    );

    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . $directory));
    foreach ($files as $file) {
        $path = $file->getPathname();
        if ($file->getExtension() !== 'php') {
            continue;
        }

        foreach ($excludes as $exclude) {
            if ($path === $exclude || str_starts_with($path, $exclude . DIRECTORY_SEPARATOR)) {
                continue 2;
            }
        }

        $sizes[$path] = $file->getSize();
    }
}

ksort($sizes);
arsort($sizes);
$loads = array_fill(1, $shard_count, 0);
$shard_files = [];
foreach ($sizes as $path => $size) {
    $least_loaded = array_keys($loads, min($loads), true)[0];
    $loads[$least_loaded] += $size;
    if ($least_loaded === $shard) {
        $shard_files[$path] = true;
    }
}

$paths = glob($coverage_dir . '/*.cov');
if (!$paths) {
    fwrite(STDERR, "No coverage in $coverage_dir\n");
    exit(1);
}
sort($paths);

$unserializer = new Unserializer();
$merged = null;

foreach ($paths as $path) {
    $coverage = $unserializer->unserialize($path);
    $data = $coverage['codeCoverage'];
    $base_path = rtrim($coverage['basePath'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

    // the paths are relative to a base path of each chunk's own
    $shard_coverage = static function (array $coverage) use ($base_path, $shard_files): array {
        $kept = [];
        foreach ($coverage as $file => $file_coverage) {
            if (isset($shard_files[$base_path . $file])) {
                $kept[$base_path . $file] = $file_coverage;
            }
        }

        return $kept;
    };
    $data->setLineCoverage($shard_coverage($data->lineCoverage()));
    $data->setFunctionCoverage($shard_coverage($data->functionCoverage()));

    if ($merged === null) {
        $coverage['basePath'] = '';
        $merged = $coverage;
    } else {
        $merged['codeCoverage']->merge($data);
        $merged['testResults'] += $coverage['testResults'];
    }

    unset($coverage, $data);
}

Facade::fromSerializedData($merged)->renderXml($xml_dir, false);

$relative_paths = array_map(
    static fn(string $path): string => substr($path, strlen($root)),
    array_keys($shard_files),
);
sort($relative_paths);
file_put_contents($file_list, implode(',', $relative_paths));
