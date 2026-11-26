<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Cli;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psalm\Internal\Cli\IssueDocs;
use Psalm\Issue\PossiblyNullReference;

use function file_get_contents;

final class IssueDocsTest extends TestCase
{
    /**
     * @return iterable<string, array{string}>
     * @psalm-pure
     */
    public static function possiblyNullReferenceQueries(): iterable
    {
        yield 'type' => ['PossiblyNullReference'];
        yield 'shortcode' => ['83'];
        yield 'zero padded shortcode' => ['083'];
        yield 'link' => ['https://psalm.dev/083'];
        yield 'link without scheme' => ['psalm.dev/83'];
        yield 'link with trailing slash' => ['https://psalm.dev/83/'];
        yield 'docs link' => ['https://psalm.dev/docs/running_psalm/issues/PossiblyNullReference/'];
        yield 'docs markdown link' => ['https://psalm.dev/docs/running_psalm/issues/PossiblyNullReference.md'];
        yield 'surrounding whitespace' => [" PossiblyNullReference\n"];
    }

    #[DataProvider('possiblyNullReferenceQueries')]
    public function testFindsTheIssueType(string $query): void
    {
        $this->assertSame('PossiblyNullReference', IssueDocs::findIssueType($query));
    }

    public function testTheShortcodeIsTheOneOfTheIssueClass(): void
    {
        $this->assertSame(83, PossiblyNullReference::SHORTCODE);
    }

    /**
     * @return iterable<string, array{string}>
     * @psalm-pure
     */
    public static function unknownQueries(): iterable
    {
        yield 'unknown type' => ['NotAnIssue'];
        yield 'unknown shortcode' => ['99999'];
        yield 'shortcode zero' => ['0'];
        yield 'empty' => [''];
        yield 'parent directory' => ['../../README'];
        yield 'parent directory of the issues' => ['../common_issues'];
        yield 'absolute path' => ['/etc/passwd'];
        yield 'type with extension' => ['PossiblyNullReference.md'];
        yield 'type with a path' => ['issues/PossiblyNullReference'];
        yield 'base class that is no issue' => ['CodeIssue'];
        yield 'docs link to a path' => ['https://psalm.dev/docs/running_psalm/issues/../common_issues'];
    }

    #[DataProvider('unknownQueries')]
    public function testUnknownQueriesHaveNoDocs(string $query): void
    {
        $this->assertNull(IssueDocs::findIssueType($query));
        $this->assertNull(IssueDocs::read($query));
    }

    public function testReadReturnsTheDocFileAsIs(): void
    {
        $expected = file_get_contents(__DIR__ . '/../../../docs/running_psalm/issues/PossiblyNullReference.md');

        $this->assertNotFalse($expected);
        $this->assertStringStartsWith('# PossiblyNullReference', $expected);
        $this->assertSame($expected, IssueDocs::read('PossiblyNullReference'));
        $this->assertSame($expected, IssueDocs::read('83'));
    }
}
