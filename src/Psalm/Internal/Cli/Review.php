<?php

declare(strict_types=1);

namespace Psalm\Internal\Cli;

use AssertionError;
use Psalm\Internal\CliUtils;
use Psalm\Internal\ErrorHandler;
use RuntimeException;

use function array_filter;
use function array_reverse;
use function array_shift;
use function array_slice;
use function array_values;
use function assert;
use function count;
use function escapeshellarg;
use function file_exists;
use function file_get_contents;
use function fputs;
use function gc_collect_cycles;
use function gc_disable;
use function getenv;
use function ini_set;
use function json_decode;
use function ltrim;
use function passthru;
use function printf;
use function readline;
use function str_repeat;
use function strlen;
use function strpos;
use function substr;
use function trim;

use const JSON_THROW_ON_ERROR;
use const PHP_EOL;
use const PHP_OS_FAMILY;
use const STDERR;

// phpcs:disable PSR1.Files.SideEffects

require_once __DIR__ . '/../ErrorHandler.php';
require_once __DIR__ . '/../CliUtils.php';
require_once __DIR__ . '/../Composer.php';
require_once __DIR__ . '/../IncludeCollector.php';
require_once __DIR__ . '/../../IssueBuffer.php';

/**
 * @internal
 */
final class Review
{
    private static function r(string $cmd): void
    {
        passthru($cmd, $result);
        if ($result) {
            exit("Return code {$result}\n");
        }
    }
    
    /** @param list<string> $argv */
    public static function run(array $argv): void
    {
        CliUtils::checkRuntimeRequirements();
        ini_set('memory_limit', '8192M');

        gc_collect_cycles();
        gc_disable();

        ErrorHandler::install($argv);

        $args = array_slice($argv, 1);
        if (count($args) === 0) {
            self::help();
        }

        $issues = array_shift($args);
        $mode = array_shift($args);

        /** @psalm-suppress RiskyTruthyFalsyComparison */
        $mode = match ($mode) {
            'code-server' => static fn(string $file, int $line, int $column) => 'code-server -r ' .
                    escapeshellarg($file) . ':' .
                    escapeshellarg((string)$line) . ':' .
                    escapeshellarg((string)$column),

            'phpstorm' => static fn(string $file, int $line, int $column) => (PHP_OS_FAMILY === 'Darwin'
                ? 'open -na \'/Applications/PhpStorm.app\' --args'
                : escapeshellarg(getenv('PHPSTORM') ?: 'phpstorm')
                ). ' --line ' . escapeshellarg((string) $line) . " --column {$column} " . escapeshellarg($file),

            'code' => static fn(string $file, int $line, int $column)
                 => 'code --goto ' . escapeshellarg($file) . ':' .
                 escapeshellarg((string) $line) . ':' .
                 escapeshellarg((string) $column),
            
            null => throw new AssertionError("No IDE was specified as second parameter!"),
            default => throw new AssertionError("The only allowed IDEs are vscode, phpstorm, code-server, got $mode")
        };

        if (!file_exists($issues)) {
            throw new RuntimeException("$issues does not exist!");
        }
        
        $issues = file_get_contents($issues);
        if ($issues === false) {
            throw new AssertionError("Could not read issues");
        }

        /** @var list<array{type: string, snippet: string, selected_text: string, line_from: int, column_from: int, file_name: string}> */
        $issues = json_decode($issues, true, flags: JSON_THROW_ON_ERROR);
        foreach ($args as $issue) {
            if ($issue[0] === '~' || $issue[0] === '-') {
                $issue = substr($issue, 1);
                $issues = array_filter($issues, static fn(array $i) => $i['type'] !== $issue);
            } elseif ($issue === 'inv' || $issue === 'rev') {
                $issues = array_reverse($issues);
            } else {
                $issues = array_filter($issues, static fn(array $i) => $i['type'] === $issue);
            }
        }
        
        $allCount = count($issues);
        $issues = array_values($issues);
        foreach ($issues as $k => [
            'line_from' => $line,
            'column_from' => $column,
            'type' => $type,
            'message' => $message,
            'file_name' => $file,
            'snippet' => $snippet,
            'selected_text' => $selected,
        ]) {
            self::r('clear');
            echo "{$type}: {$message}" . PHP_EOL . PHP_EOL;
            echo $snippet . PHP_EOL;
        
            $pos = strpos($snippet, $selected);
            assert($pos !== false);
            $snippetTrimmed = ltrim($snippet);
            $lenTab = strlen($snippet) - strlen($snippetTrimmed);
        
            echo substr($snippet, 0, $lenTab);
            echo str_repeat(' ', $pos - $lenTab);
            echo str_repeat('^', strlen($selected));
            echo PHP_EOL . PHP_EOL;
        
            printf('%d%% (%d/%d)', (int)($k * 100 / $allCount), $k, $allCount);
            echo PHP_EOL;
        
            self::r($mode($file, $line, $column));

            /** @psalm-suppress RiskyTruthyFalsyComparison */
            if (trim(readline("Press enter to continue, q to quit: ") ?: '') === 'q') {
                break;
            }
        }
    }

    private static function help(): never
    {
        fputs(
            STDERR,
            PHP_EOL . "Usage:" . PHP_EOL . PHP_EOL .
                "psalm-review report.json code|phpstorm|code-server " .
                "[ inv|rev|[~-]IssueType1 ] [ [~-]IssueType2 ] ... " . PHP_EOL .
                "psalm --review report.json code|phpstorm|code-server " .
                "[ inv|rev|[~-]IssueType1 ] [ [~-]IssueType2 ] ... " . PHP_EOL . PHP_EOL .
                "Will parse the Psalm JSON report in report.json ".
                "and open the specified IDE at the line and column of the issue, ".
                "one by one for all issues.".PHP_EOL.
                "Press enter to go to the next issue, q to quit.".PHP_EOL.PHP_EOL.
                "The extra arguments may be used to filter only for issues of the specified types, ".
                "or for all issues except the specified types (with the ~ or - inversion);".PHP_EOL.
                "rev|inv keywords may be used to start from the end of the report.".PHP_EOL.PHP_EOL,
        );
        exit(1);
    }
}
