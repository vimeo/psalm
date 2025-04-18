<?php

declare(strict_types=1);

if (count($argv) < 2) {
    fwrite(STDERR, 'Usage: ' . $argv[0] . ' <number_of_chunks>' . PHP_EOL);
    exit(1);
}

$number_of_chunks = (int) $argv[1];
if ($number_of_chunks === 0) {
    fwrite(STDERR, 'Usage: ' . $argv[0] . ' <number_of_chunks>' . PHP_EOL);
    exit(1);
}

// find tests -name '*Test.php'
$files = iterator_to_array(
    new RegexIterator(
        new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                'tests',
                FilesystemIterator::CURRENT_AS_PATHNAME|FilesystemIterator::SKIP_DOTS,
            ),
            RecursiveIteratorIterator::LEAVES_ONLY,
        ),
        '/.*Test.php$/',
    ),
);

mt_srand(4); // chosen by fair dice roll.
             // guaranteed to be random.
             // -- xkcd:221

$order = array_map(
    fn(): int => mt_rand(),
    $files,
);
array_multisort($order, $files);

$chunks = array_chunk($files, (int) ceil(count($files) / $number_of_chunks));

$phpunit_config = new DOMDocument('1.0', 'UTF-8');
$phpunit_config->preserveWhiteSpace = false;
$phpunit_config->load('phpunit.xml.dist');
$suites_container = $phpunit_config->getElementsByTagName('testsuites')->item(0);

while ($suites_container->firstChild) {
    $suites_container->removeChild($suites_container->firstChild);
}

foreach ($chunks as $chunk_id => $chunk) {
    $suite = $phpunit_config->createElement('testsuite');
    $suite->setAttribute('name', 'chunk_' . ($chunk_id + 1));
    foreach ($chunk as $file) {
         $suite->appendChild($phpunit_config->createElement('file', $file));
    }
    $suites_container->appendChild($suite);
}

$phpunit_config->formatOutput = true;
$phpunit_config->save('phpunit.xml');
