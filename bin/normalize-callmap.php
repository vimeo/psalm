<?php

declare(strict_types=1);

require 'vendor/autoload.php';

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

    array_walk_recursive($callMap, function (string &$type): void {
        $type = Type::parseString($type === '' ? 'mixed' : $type)->getId(true);
    });

    file_put_contents($file, '<?php // phpcs:ignoreFile

return '.var_export($callMap, true).';');
}
