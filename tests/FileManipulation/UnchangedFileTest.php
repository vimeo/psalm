<?php

declare(strict_types=1);

namespace Psalm\Tests\FileManipulation;

use Psalm\FileManipulation;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;
use Psalm\Internal\Provider\Providers;
use Psalm\Progress\Progress;
use Psalm\Tests\DeferredAnalyzerTestCase;
use Psalm\Tests\Internal\Provider\FakeParserCacheProvider;
use Psalm\Tests\TestConfig;

final class UnchangedFileTest extends DeferredAnalyzerTestCase
{
    protected ProjectAnalyzer $project_analyzer;

    public function testFileWhoseManipulationsLeaveItUnchangedIsNeitherReportedNorRewritten(): void
    {
        $progress = $this->createMock(Progress::class);
        $progress->expects($this->never())->method('alterFileDone');

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

        $this->assertSame(0, $codebase->analyzer->getChangedFilesCount());
        $this->assertSame($contents, $codebase->getFileContents($file_path));
    }
}
