<?php

declare(strict_types=1);

namespace Psalm\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use Psalm\Config;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\Codebase\MutationLevelResolver;
use Psalm\IssueBuffer;

use function sort;

use const DIRECTORY_SEPARATOR;

final class MutationInfoAnalysisOrderTest extends TestCase
{
    /**
     * @return iterable<string, array{list<string>}>
     * @psalm-pure
     */
    public static function providerAnalysisOrders(): iterable
    {
        yield 'declaring file before the file calling it' => [['Base.php', 'Child.php', 'Caller.php']];
        yield 'declaring file after the file calling it' => [['Caller.php', 'Child.php', 'Base.php']];
        yield 'declaring file between the files' => [['Child.php', 'Base.php', 'Caller.php']];
        yield 'child after the declaring file' => [['Caller.php', 'Base.php', 'Child.php']];
    }

    /**
     * `Base::run()` is analysed on its own, where it calls the overridable `Base::value()`, and again for each class
     * inheriting it that a call is made on (with taint tracking), where it calls `Child::value()`. Which of them
     * says what `run()` may be annotated with must not depend on which file is analysed first.
     *
     * @param list<string> $order
     */
    #[DataProvider('providerAnalysisOrders')]
    public function testMissingPureAnnotationsDoNotDependOnTheAnalysisOrder(array $order): void
    {
        $this->project_analyzer->trackTaintedInputs();
        $codebase = $this->project_analyzer->getCodebase();
        $config = $codebase->config;
        $config->setCustomErrorLevel('MissingPureAnnotation', Config::REPORT_INFO);

        $files = [
            'Base.php' => '<?php
                class Base {
                    /** @psalm-taint-specialize */
                    public function run(): int {
                        return $this->value();
                    }

                    public function value(): int {
                        return 0;
                    }
                }',
            'Child.php' => '<?php
                final class Child extends Base {
                    public function value(): int {
                        return 1;
                    }
                }',
            'Caller.php' => '<?php
                $child = new Child();
                $child->run();',
        ];

        foreach ($files as $file_name => $contents) {
            $file_path = self::$src_dir_path . $file_name;
            $this->addFile($file_path, $contents);
            $codebase->addFilesToAnalyze([$file_path => $file_path]);
        }

        $codebase->scanFiles();

        foreach ($order as $file_name) {
            $file_path = self::$src_dir_path . $file_name;
            (new FileAnalyzer($this->project_analyzer, $file_path, $config->shortenFileName($file_path)))->analyze();
        }

        MutationLevelResolver::resolve($this->project_analyzer);

        $messages = [];

        foreach (IssueBuffer::getIssuesData() as $file_issues) {
            foreach ($file_issues as $issue) {
                $messages[] = $issue->type . ' - ' . $issue->file_name . ' - ' . $issue->message;
            }
        }

        sort($messages);

        // Base::run() and Base::value() call, or are called by, what any child may override: nothing to annotate
        self::assertSame(
            [
                'MissingPureAnnotation - src' . DIRECTORY_SEPARATOR . 'Child.php - value must be marked @psalm-pure'
                    . ' to aid security analysis, run with --alter --issues=MissingPureAnnotation to fix this',
            ],
            $messages,
        );
    }
}
