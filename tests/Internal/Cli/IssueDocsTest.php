<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Cli;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psalm\Internal\Cli\IssueDocs;

use function file_get_contents;

final class IssueDocsTest extends TestCase
{
    /**
     * @return iterable<string, array{string, ?string}>
     * @psalm-pure
     */
    public static function queries(): iterable
    {
        yield 'type' => ['PossiblyNullReference', 'PossiblyNullReference'];
        yield 'zero padded shortcode' => ['083', 'PossiblyNullReference'];
        yield 'link' => ['https://psalm.dev/083', 'PossiblyNullReference'];
        yield 'docs link' => [
            'https://psalm.dev/docs/running_psalm/issues/PossiblyNullReference/',
            'PossiblyNullReference',
        ];
        yield 'unknown type' => ['NotAnIssue', null];
        yield 'unknown shortcode' => ['99999', null];
        yield 'path' => ['../common_issues', null];
    }

    #[DataProvider('queries')]
    public function testFindIssueType(string $query, ?string $expected): void
    {
        $this->assertSame($expected, IssueDocs::findIssueType($query));
    }

    public function testReadReturnsTheBundledDoc(): void
    {
        $this->assertSame(
            file_get_contents(__DIR__ . '/../../../docs/running_psalm/issues/PossiblyNullReference.md'),
            IssueDocs::read('83'),
        );
    }
}
