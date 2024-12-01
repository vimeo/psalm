<?php

declare(strict_types=1);

require 'vendor/autoload.php';

require __DIR__ . '/gen_callmap_utils.php';

use DG\BypassFinals;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Provider\FileProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Tests\TestConfig;
use Psalm\Type;

BypassFinals::enable();

new ProjectAnalyzer(new TestConfig, new Providers(new FileProvider));

$codebase = ProjectAnalyzer::getInstance()->getCodebase();

chdir(__DIR__.'/../');

foreach (glob("dictionaries/CallMap*.php") as $file) {
    $callMap = require $file;
    $callMap = normalizeCallMap($callMap);

    file_put_contents($file, '<?php // phpcs:ignoreFile

return '.var_export($callMap, true).';');
}
