<?php

declare(strict_types=1);

namespace Psalm\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use Psalm\Config;
use Psalm\Context;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\IssueBuffer;

use function array_map;
use function basename;
use function ksort;
use function sort;

/**
 * Every declaration of a class declared more than once is analysed, against its own statements.
 */
final class DuplicateClassTest extends TestCase
{
    /**
     * @param array<string, string> $files the contents of the files, by name
     * @param array<string, list<string>> $expected the issues, by file: type and line
     */
    #[DataProvider('providerDeclarations')]
    public function testEveryDeclarationIsAnalyzed(array $files, array $expected): void
    {
        $codebase = $this->project_analyzer->getCodebase();
        $codebase->config->throw_exception = false;
        $codebase->config->setCustomErrorLevel('DuplicateClass', Config::REPORT_SUPPRESS);
        $codebase->config->setCustomErrorLevel('PossiblyInvalidCast', Config::REPORT_SUPPRESS);
        $codebase->config->setCustomErrorLevel('TaintedTextWithQuotes', Config::REPORT_SUPPRESS);
        $this->project_analyzer->trackTaintedInputs();

        $file_paths = [];
        foreach ($files as $name => $contents) {
            $file_path = self::$src_dir_path . $name;
            $this->addFile($file_path, $contents);
            $file_paths[$file_path] = $file_path;
        }

        $codebase->addFilesToAnalyze($file_paths);
        $codebase->scanFiles();

        foreach ($file_paths as $file_path) {
            (new FileAnalyzer($this->project_analyzer, $file_path, $codebase->config->shortenFileName($file_path)))
                ->analyze(new Context());
        }

        $codebase->taint_flow_graph?->connectSinksAndSources($codebase->progress);

        $issues = [];
        foreach (IssueBuffer::getIssuesData() as $file_path => $file_issues) {
            $file_issues = array_map(
                static fn(IssueData $issue): string => $issue->type . ':' . $issue->line_from,
                $file_issues,
            );
            sort($file_issues);
            $issues[basename($file_path)] = $file_issues;
        }
        ksort($issues);

        self::assertSame($expected, $issues);
    }

    /**
     * @return iterable<string, array{array<string, string>, array<string, list<string>>}>
     * @psalm-pure
     */
    public static function providerDeclarations(): iterable
    {
        $first = '<?php
namespace Lib;

final class C {
    public function m(): void {
        echo (string) $_GET["a"];
    }
}';
        $second = '<?php
namespace Lib;

final class C {
    public function n(string $s): int {
        echo (string) $_GET["b"];
        return $s;
    }
}';

        yield 'in two files' => [
            ['a.php' => $first, 'b.php' => $second],
            [
                'a.php' => ['TaintedHtml:6'],
                'b.php' => ['InvalidReturnStatement:7', 'InvalidReturnType:5', 'TaintedHtml:6'],
            ],
        ];

        yield 'in one file' => [
            ['a.php' => '<?php
if (rand(0, 1)) {
    final class C {
        public function m(): void {
            echo (string) $_GET["a"];
        }
    }
} else {
    final class C {
        public function n(string $s): int {
            echo (string) $_GET["b"];
            return $s;
        }
    }
}'],
            [
                'a.php' => [
                    'InvalidReturnStatement:12',
                    'InvalidReturnType:10',
                    'TaintedHtml:11',
                    'TaintedHtml:5',
                ],
            ],
        ];
    }
}
