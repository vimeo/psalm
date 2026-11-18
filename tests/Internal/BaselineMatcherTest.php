<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal;

use PHPUnit\Framework\TestCase;
use Psalm\Internal\BaselineMatcher;
use Psalm\Tests\TestConfig;

final class BaselineMatcherTest extends TestCase
{
    public function testTheSnippetPicksTheEntry(): void
    {
        $baseline = ['src/A.php' => ['UndefinedFunction' => ['o' => 2, 's' => ['a()', 'b()']]]];
        $config = new TestConfig();

        self::assertTrue(BaselineMatcher::consume($config, $baseline, 'src/A.php', 'UndefinedFunction', " b()\r\n"));
        self::assertFalse(BaselineMatcher::consume($config, $baseline, 'src/A.php', 'UndefinedFunction', 'c()'));
        self::assertSame(['src/A.php' => ['UndefinedFunction' => ['o' => 1, 's' => ['a()']]]], $baseline);
    }

    public function testEachEntryCoversOneIssue(): void
    {
        $baseline = ['src/A.php' => ['UndefinedFunction' => ['o' => 1, 's' => ['a()']]]];
        $config = new TestConfig();

        self::assertTrue(BaselineMatcher::consume($config, $baseline, 'src/A.php', 'UndefinedFunction', 'a()'));
        self::assertFalse(BaselineMatcher::consume($config, $baseline, 'src/A.php', 'UndefinedFunction', 'a()'));
    }

    public function testAnEntryWithoutAllItsSnippetsCoversAnyIssueOfItsTypeUntilItsCountIsUsedUp(): void
    {
        $baseline = ['src/A.php' => ['UndefinedFunction' => ['o' => 2, 's' => ['a()']]]];
        $config = new TestConfig();

        self::assertTrue(BaselineMatcher::consume($config, $baseline, 'src/A.php', 'UndefinedFunction', 'x()'));
        self::assertTrue(BaselineMatcher::consume($config, $baseline, 'src/A.php', 'UndefinedFunction', 'y()'));
        self::assertFalse(BaselineMatcher::consume($config, $baseline, 'src/A.php', 'UndefinedFunction', 'a()'));
        self::assertSame(['src/A.php' => ['UndefinedFunction' => ['o' => 0, 's' => []]]], $baseline);
    }

    public function testAFileNameWithBackslashesMatchesTheBaseline(): void
    {
        $baseline = ['src/Sub/A.php' => ['UndefinedFunction' => ['o' => 1, 's' => ['a()']]]];

        self::assertTrue(
            BaselineMatcher::consume(new TestConfig(), $baseline, 'src\\Sub\\A.php', 'UndefinedFunction', 'a()'),
        );
    }

    public function testAnUnsuppressibleTypeIsNeverConsumed(): void
    {
        $baseline = ['src/A.php' => ['UndefinedFunction' => ['o' => 1, 's' => ['a()']]]];
        $config = new TestConfig();
        $config->addUnsuppressibleIssue('UndefinedFunction');

        self::assertFalse(BaselineMatcher::consume($config, $baseline, 'src/A.php', 'UndefinedFunction', 'a()'));
        self::assertSame(['src/A.php' => ['UndefinedFunction' => ['o' => 1, 's' => ['a()']]]], $baseline);
    }
}
