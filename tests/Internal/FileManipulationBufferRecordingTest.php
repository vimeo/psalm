<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal;

use Override;
use PHPUnit\Framework\TestCase;
use Psalm\FileManipulation;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;
use Psalm\IssueBuffer;

use function array_keys;
use function array_map;
use function array_values;

/**
 * A file manipulation follows the IssueBuffer recording level it was added in
 */
final class FileManipulationBufferRecordingTest extends TestCase
{
    /**
     * @psalm-capabilities read-props|write-this-props|write-refs|read-globals|write-globals
     */
    #[Override]
    protected function setUp(): void
    {
        FileManipulationBuffer::clearCache();
        IssueBuffer::clearCache();
    }

    /**
     * @psalm-capabilities read-props|write-this-props|write-refs|read-globals|write-globals
     */
    #[Override]
    protected function tearDown(): void
    {
        FileManipulationBuffer::clearCache();
        IssueBuffer::clearCache();
    }

    public function testManipulationAddedWithoutRecordingIsKept(): void
    {
        FileManipulationBuffer::add('a.php', [new FileManipulation(1, 2, 'x')]);

        $this->assertSame(['1:2:x'], self::describe('a.php'));
    }

    public function testManipulationOfAStoppedLevelIsDropped(): void
    {
        IssueBuffer::startRecording();
        FileManipulationBuffer::add('a.php', [new FileManipulation(1, 2, 'x')]);

        $this->assertSame([], self::describe('a.php'));

        IssueBuffer::clearRecordingLevel();
        IssueBuffer::stopRecording();

        $this->assertSame([], self::describe('a.php'));
        $this->assertSame([], FileManipulationBuffer::getAll());
    }

    public function testManipulationOfACommittedLevelIsKept(): void
    {
        IssueBuffer::startRecording();
        FileManipulationBuffer::add('a.php', [new FileManipulation(1, 2, 'x')]);
        IssueBuffer::clearRecordingLevel();
        $recorded = FileManipulationBuffer::clearRecordingLevel();
        IssueBuffer::stopRecording();

        $this->assertSame([], self::describe('a.php'));

        FileManipulationBuffer::bubbleUp($recorded);

        $this->assertSame(['1:2:x'], self::describe('a.php'));
    }

    public function testOnlyTheLastOfSeveralPassesIsCommitted(): void
    {
        $pass = static function (string $text): array {
            IssueBuffer::startRecording();
            FileManipulationBuffer::add('a.php', [new FileManipulation(1, 2, $text)]);
            $recorded = FileManipulationBuffer::clearRecordingLevel();
            IssueBuffer::stopRecording();

            return $recorded;
        };

        $pass('first');
        FileManipulationBuffer::bubbleUp($pass('last'));

        $this->assertSame(['1:2:last'], self::describe('a.php'));
    }

    public function testCommittingInANestedLevelWaitsForTheOuterLevel(): void
    {
        IssueBuffer::startRecording();
        FileManipulationBuffer::add('a.php', [new FileManipulation(1, 2, 'outer')]);

        IssueBuffer::startRecording();
        FileManipulationBuffer::add('a.php', [new FileManipulation(3, 4, 'inner')]);
        $inner = FileManipulationBuffer::clearRecordingLevel();
        IssueBuffer::stopRecording();

        FileManipulationBuffer::bubbleUp($inner);

        $this->assertSame([], self::describe('a.php'));

        // the outer level is dropped as a whole, the committed inner manipulation with it
        IssueBuffer::stopRecording();

        $this->assertSame([], self::describe('a.php'));

        IssueBuffer::startRecording();
        FileManipulationBuffer::bubbleUp($inner);
        $outer = FileManipulationBuffer::clearRecordingLevel();
        IssueBuffer::stopRecording();
        FileManipulationBuffer::bubbleUp($outer);

        $this->assertSame(['3:4:inner'], self::describe('a.php'));
    }

    public function testClearingALevelLeavesTheOtherLevelsAlone(): void
    {
        IssueBuffer::startRecording();
        FileManipulationBuffer::add('a.php', [new FileManipulation(1, 2, 'outer')]);

        IssueBuffer::startRecording();
        FileManipulationBuffer::add('b.php', [new FileManipulation(3, 4, 'inner')]);

        $this->assertSame(['b.php'], array_keys(FileManipulationBuffer::clearRecordingLevel()));
        $this->assertSame([], FileManipulationBuffer::clearRecordingLevel());
        IssueBuffer::stopRecording();

        $this->assertSame(['a.php'], array_keys(FileManipulationBuffer::clearRecordingLevel()));
        IssueBuffer::stopRecording();
    }

    public function testClearingTheCacheDropsTheRecordedManipulations(): void
    {
        IssueBuffer::startRecording();
        FileManipulationBuffer::add('a.php', [new FileManipulation(1, 2, 'x')]);

        FileManipulationBuffer::clearCache();

        $this->assertSame([], FileManipulationBuffer::clearRecordingLevel());
    }

    /**
     * @return list<string>
     * @psalm-external-mutation-free
     */
    private static function describe(string $file_path): array
    {
        return array_map(
            static fn(FileManipulation $manipulation): string
                => $manipulation->start . ':' . $manipulation->end . ':' . $manipulation->insertion_text,
            array_values(FileManipulationBuffer::getManipulationsForFile($file_path)),
        );
    }
}
