<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Analyzer;

use PHPUnit\Framework\TestCase;
use Psalm\Internal\Analyzer\IssueData;

final class IssueDataTest extends TestCase
{
    public function testLinkUsesDocumentationUrlWhenProvided(): void
    {
        $issue = new IssueData(
            IssueData::SEVERITY_ERROR,
            1,
            1,
            'CustomIssue',
            'message',
            'file.php',
            '/path/file.php',
            'snippet',
            'selected',
            0,
            0,
            0,
            0,
            0,
            0,
            documentation_url: 'https://example.com/docs/CustomIssue',
        );

        self::assertSame('https://example.com/docs/CustomIssue', $issue->link);
    }

    public function testLinkUsesPsalmDevWhenShortcodeProvidedWithoutDocumentationUrl(): void
    {
        $issue = new IssueData(
            IssueData::SEVERITY_ERROR,
            1,
            1,
            'SomeIssue',
            'message',
            'file.php',
            '/path/file.php',
            'snippet',
            'selected',
            0,
            0,
            0,
            0,
            0,
            0,
            42,
        );

        self::assertSame('https://psalm.dev/042', $issue->link);
    }

    public function testDocumentationUrlTakesPriorityOverShortcode(): void
    {
        $issue = new IssueData(
            IssueData::SEVERITY_ERROR,
            1,
            1,
            'CustomIssue',
            'message',
            'file.php',
            '/path/file.php',
            'snippet',
            'selected',
            0,
            0,
            0,
            0,
            0,
            0,
            42,
            documentation_url: 'https://example.com/docs',
        );

        self::assertSame('https://example.com/docs', $issue->link);
    }

    public function testLinkIsEmptyWhenNoShortcodeOrDocumentationUrl(): void
    {
        $issue = new IssueData(
            IssueData::SEVERITY_ERROR,
            1,
            1,
            'CustomIssue',
            'message',
            'file.php',
            '/path/file.php',
            'snippet',
            'selected',
            0,
            0,
            0,
            0,
            0,
            0,
        );

        self::assertSame('', $issue->link);
    }
}
