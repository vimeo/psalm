<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Codebase;

use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Psalm\Internal\Codebase\AnalysisOrder;
use Psalm\Plugin\EventHandler\BeforeFileAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\BeforeFileAnalysisEvent;
use Psalm\Tests\TestCase;

use function array_keys;
use function getcwd;

use const DIRECTORY_SEPARATOR;

final class AnalysisOrderTest extends TestCase
{
    private static function path(string $name): string
    {
        return (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'analysis_order'
            . DIRECTORY_SEPARATOR . $name . '.php';
    }

    /**
     * @param array<string, string> $sources contents by file name
     */
    private function deepScan(array $sources): void
    {
        $this->project_analyzer->setPhpVersion('8.0', 'tests');
        $codebase = $this->project_analyzer->getCodebase();

        foreach ($sources as $name => $contents) {
            $this->file_provider->registerFile(self::path($name), $contents);
            $codebase->addFilesToAnalyze([self::path($name) => self::path($name)]);
        }

        $codebase->scanFiles();
    }

    #[DataProvider('provideBranches')]
    public function testDeepScanCountsTheBranchesOfTheMostComplexFunctionLike(string $contents, int $expected): void
    {
        $this->deepScan(['a' => $contents]);

        self::assertSame(
            $expected,
            $this->project_analyzer->getCodebase()->file_storage_provider->get(self::path('a'))->max_function_branches,
        );
    }

    /**
     * @return iterable<string, array{string, int}>
     * @psalm-pure
     */
    public static function provideBranches(): iterable
    {
        yield 'every kind of branching node' => [
            '<?php
            function f(array $a, ?int $b, bool $c): void {
                if ($c) {} elseif ($b) {}
                switch ($b) { case 1: break; }
                try {} catch (Exception $e) {}
                for (;;) { break; }
                foreach ($a as $_) {}
                while ($c) { break; }
                do {} while (false);
                $x = match ($b) { 1 => 1, default => 2 };
                $y = $c ? 1 : 2;
                $z = $c && $c || $c and $c or $c;
                $b ??= $b ?? 1;
            }',
            17,
        ];

        yield 'the function-like with the most branches' => [
            '<?php
            final class A {
                public function one(bool $a): void { if ($a) {} }
                public function two(bool $a): void { if ($a) {} if ($a) {} }
            }
            function one(bool $a): void { if ($a) {} }',
            2,
        ];

        yield 'global code' => [
            '<?php
            if (PHP_OS) {} if (PHP_OS) {} if (PHP_OS) {}
            function one(bool $a): void { if ($a) {} }',
            3,
        ];

        yield 'closures count apart from the function they are in' => [
            '<?php
            function f(array $a): array {
                if ($a) {} if ($a) {}
                $g = function (bool $b): void { if ($b) {} };
                return array_map(fn(int $c): int => $c ?: 1, $a);
            }',
            2,
        ];

        yield 'no branches' => ['<?php function f(): void {}', 0];
    }

    public function testShallowScanCountsNothing(): void
    {
        $this->addFile(self::path('a'), '<?php if (PHP_OS) {} function f(bool $a): void { if ($a) {} }');
        $this->project_analyzer->getCodebase()->scanFiles();

        self::assertSame(
            0,
            $this->project_analyzer->getCodebase()->file_storage_provider->get(self::path('a'))->max_function_branches,
        );
    }

    public function testPoolReceivesTheMostComplexFilesFirstThenTheRestInPathOrder(): void
    {
        // in path order, as doAnalysis() sorts the files before handing them to the pool
        $this->deepScan([
            'a' => '<?php function a(): void {}',
            'b' => '<?php function b(bool $a): void { if ($a) {} if ($a) {} if ($a) {} }',
            'c' => '<?php function c(bool $a): void { if ($a) {} }',
            'd' => '<?php function d(bool $a): void { if ($a) {} if ($a) {} if ($a) {} }',
            'e' => '<?php function e(bool $a): void { if ($a) {} if ($a) {} }',
        ]);

        $analyzer = $this->project_analyzer->getCodebase()->analyzer;

        // 2 × 1 worker: b and d tie and go first in path order, e is more complex than c but stays after it
        self::assertSame(
            [self::path('b'), self::path('d'), self::path('a'), self::path('c'), self::path('e')],
            array_keys($analyzer->getFilesInPoolOrder(1)),
        );
    }

    public function testAnalysisWithoutPoolKeepsPathOrder(): void
    {
        $recorder = new class implements BeforeFileAnalysisInterface {
            /** @var list<string> */
            public static array $paths = [];

            #[Override]
            public static function beforeAnalyzeFile(BeforeFileAnalysisEvent $event): void
            {
                self::$paths[] = $event->getStatementsSource()->getFilePath();
            }
        };
        $codebase = $this->project_analyzer->getCodebase();
        $codebase->config->eventDispatcher->registerClass($recorder::class);

        $this->deepScan([
            'c' => '<?php function c(bool $a): void { if ($a) {} if ($a) {} }',
            'a' => '<?php function a(): void {}',
            'b' => '<?php function b(bool $a): void { if ($a) {} if ($a) {} if ($a) {} }',
        ]);

        $codebase->analyzer->analyzeFiles($this->project_analyzer, 1, false);

        self::assertSame([self::path('a'), self::path('b'), self::path('c')], $recorder::$paths);
    }

    /**
     * @param array<string, int> $costs
     * @param list<string> $expected
     */
    #[DataProvider('provideOrders')]
    public function testMostExpensiveFirst(array $costs, int $count, array $expected): void
    {
        $files = [];
        foreach ($costs as $path => $_) {
            $files[$path] = $path;
        }

        self::assertSame(
            $expected,
            array_keys(AnalysisOrder::mostExpensiveFirst($files, $count, static fn(string $path): int => $costs[$path])),
        );
    }

    /**
     * @return iterable<string, array{array<string, int>, int, list<string>}>
     * @psalm-pure
     */
    public static function provideOrders(): iterable
    {
        yield 'files without cost keep their place' => [['/a' => 0, '/b' => 0, '/c' => 4], 2, ['/c', '/a', '/b']];
        yield 'fewer files than moved' => [['/a' => 1, '/b' => 2], 4, ['/b', '/a']];
        yield 'nothing moved' => [['/a' => 1, '/b' => 2], 0, ['/a', '/b']];
    }
}
