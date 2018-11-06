<?php

ini_set('display_startup_errors', '1');
ini_set('html_errors', '1');
ini_set('memory_limit', '-1');
error_reporting(E_ALL);

require __DIR__ . '../../../vendor/autoload.php';

$a = file_get_contents(__DIR__ . '/a.test');
$b = file_get_contents(__DIR__ . '/b.test');

$a_stmts = Psalm\Internal\Provider\StatementsProvider::parseStatements($a);

$time = microtime(true);

$file_changes = Psalm\Internal\Diff\FileDiffer::getDiff($a, $b);
$dlt = microtime(true);
$line_diff_time = $dlt - $time;

echo 'Partial parsing: diffing lines: ' . number_format($line_diff_time, 4) . "\n";

$traverser = new PhpParser\NodeTraverser;
$traverser->addVisitor(new Psalm\Internal\Visitor\CloningVisitor);
// performs a deep clone
/** @var array<int, PhpParser\Node\Stmt> */
$a_stmts_copy = $traverser->traverse($a_stmts);
$dlt2 = microtime(true);

echo 'Partial parsing: cloning: ' . number_format($dlt2 - $dlt, 4) . "\n";

Psalm\Internal\Provider\StatementsProvider::parseStatements($b, null, $a, $a_stmts_copy, $file_changes);

$diff_1 = microtime(true) - $time;

echo 'Partial parsing: ' . number_format($diff_1, 4) . "\n";

$time = microtime(true);

Psalm\Internal\Provider\StatementsProvider::parseStatements($b);

$diff_2 = microtime(true) - $time;

echo 'Full parsing: ' . number_format($diff_2, 4) . "\n";
