<?php

declare(strict_types=1);

namespace Psalm\Tests\FileManipulation;

use Override;
use Psalm\FileManipulation;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;
use Psalm\Internal\Provider\Providers;
use Psalm\Progress\Phase;
use Psalm\Progress\Progress;
use Psalm\Tests\DeferredAnalyzerTestCase;
use Psalm\Tests\Internal\Provider\FakeParserCacheProvider;
use Psalm\Tests\TestConfig;

final class UnchangedFileTest extends DeferredAnalyzerTestCase
{
    protected ProjectAnalyzer $project_analyzer;

    public function testFileWhoseManipulationsLeaveItUnchangedIsNeitherReportedNorRewritten(): void
    {
        $progress = new class extends Progress {
            /** @var list<string> */
            public array $altered_files = [];

            /**
             * @psalm-mutation-free
             */
            #[Override]
            public function debug(string $message): void
            {
            }

            /**
             * @psalm-mutation-free
             */
            #[Override]
            public function startPhase(Phase $phase, int $threads = 1): void
            {
            }

            /**
             * @psalm-mutation-free
             */
            #[Override]
            public function expand(int $number_of_tasks): void
            {
            }

            /**
             * @psalm-mutation-free
             */
            #[Override]
            public function taskDone(int $level): void
            {
            }

            /**
             * @psalm-mutation-free
             */
            #[Override]
            public function finish(): void
            {
            }

            /**
             * @psalm-capabilities read-props|write-this-props|write-refs
             */
            #[Override]
            public function alterFileDone(string $file_name): void
            {
                $this->altered_files[] = $file_name;
            }
        };

        $this->project_analyzer = new ProjectAnalyzer(
            new TestConfig(),
            new Providers($this->file_provider, new FakeParserCacheProvider()),
            null,
            [],
            1,
            1,
            $progress,
        );
        $codebase = $this->project_analyzer->getCodebase();
        $this->project_analyzer->alterCodeAfterCompletion();

        $file_path = self::$src_dir_path . 'somefile.php';
        $contents = '<?php echo 1;';
        $this->addFile($file_path, $contents);

        // replaces "<?php" with itself
        FileManipulationBuffer::add($file_path, [new FileManipulation(0, 5, '<?php')]);

        $codebase->analyzer->updateFile($file_path, false);

        $this->assertSame([], $progress->altered_files);
        $this->assertSame(0, $codebase->analyzer->getChangedFilesCount());
        $this->assertSame($contents, $codebase->getFileContents($file_path));
    }
}
