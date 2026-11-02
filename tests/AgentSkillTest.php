<?php

declare(strict_types=1);

namespace Psalm\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psalm\Internal\Cli\Psalm;
use Psalm\Internal\Cli\Psalter;
use ReflectionClassConstant;

use function array_map;
use function basename;
use function dirname;
use function file_exists;
use function file_get_contents;
use function glob;
use function preg_match_all;
use function preg_replace;
use function rtrim;
use function str_contains;
use function str_ends_with;

/**
 * Keeps the user-facing agent skill in resources/skills in sync with the CLI and the docs it names.
 */
final class AgentSkillTest extends BaseTestCase
{
    private const SKILL_DIR = __DIR__ . '/../resources/skills/psalm';

    private const ISSUE_PATTERN = '/`(?:@psalm-suppress )?((?:Possibly|Mixed|Missing|Invalid|Unused|Tainted|Undefined'
        . '|Less|More|Redundant|Internal|Check|Trace|Unnecessary|Impure|ClassMust)[A-Za-z]*\*?)/';

    /**
     * @return iterable<string, array{string}>
     */
    public static function providerSkillFiles(): iterable
    {
        $files = [...self::glob(self::SKILL_DIR . '/*.md'), ...self::glob(self::SKILL_DIR . '/references/*.md')];
        foreach ($files as $file) {
            yield basename(dirname($file)) . '/' . basename($file) => [$file];
        }
    }

    #[DataProvider('providerSkillFiles')]
    public function testIssueTypesExist(string $file): void
    {
        $issues_dir = __DIR__ . '/../docs/running_psalm/issues/';
        preg_match_all(self::ISSUE_PATTERN, (string) file_get_contents($file), $matches);
        foreach ($matches[1] as $issue) {
            if (str_ends_with($issue, '*')) {
                $this->assertNotEmpty(self::glob($issues_dir . $issue . '.md'), "No issue type matches $issue");
                continue;
            }
            $this->assertFileExists($issues_dir . $issue . '.md', "Unknown issue type $issue");
        }
    }

    #[DataProvider('providerSkillFiles')]
    public function testCliOptionsExist(string $file): void
    {
        $options = [
            ...self::longOptions(Psalm::class),
            ...self::longOptions(Psalter::class),
            ...self::scriptOptions(),
        ];
        $contents = (string) preg_replace('/`(?:gh|git|composer) [^`]*`/', '', (string) file_get_contents($file));
        preg_match_all('/(?<![\w-])--([a-z][a-z-]+)/', $contents, $matches);
        foreach ($matches[1] as $option) {
            $this->assertContains($option, $options, "Unknown option --$option");
        }
    }

    #[DataProvider('providerSkillFiles')]
    public function testReferencedPathsExist(string $file): void
    {
        $contents = (string) file_get_contents($file);
        $repo = __DIR__ . '/..';

        preg_match_all('~`(?:vendor/vimeo/psalm/)?((?:docs|stubs)/[\w/.-]+|config\.xsd)`~', $contents, $matches);
        foreach ($matches[1] as $path) {
            $this->assertTrue(file_exists($repo . '/' . rtrim($path, '/')), "Missing $path");
        }

        preg_match_all('~\]\(((?:references/|scripts/)?[\w-]+\.(?:md|php))(?:#[\w-]+)?\)~', $contents, $matches);
        foreach ($matches[1] as $link) {
            $this->assertFileExists(dirname($file) . '/' . $link, "Broken link $link");
        }

        preg_match_all('~`php (scripts/[\w-]+\.php)~', $contents, $matches);
        foreach ($matches[1] as $script) {
            $this->assertFileExists(self::SKILL_DIR . '/' . $script, "Missing script $script");
        }
    }

    public function testSkillShipsWithThePackage(): void
    {
        $gitattributes = (string) file_get_contents(__DIR__ . '/../.gitattributes');
        $this->assertFalse(str_contains($gitattributes, '/resources '), 'resources/ must not be export-ignored');
        $composer = (string) file_get_contents(__DIR__ . '/../composer.json');
        $this->assertStringContainsString('"source": "resources/skills"', $composer);
    }

    /**
     * @psalm-pure
     * @param class-string $class
     * @return list<string>
     */
    private static function longOptions(string $class): array
    {
        /** @var list<string> $options */
        $options = (new ReflectionClassConstant($class, 'LONG_OPTIONS'))->getValue();

        return array_map(static fn(string $option): string => rtrim($option, ':'), $options);
    }

    /**
     * @return list<string>
     */
    private static function scriptOptions(): array
    {
        $options = [];
        foreach (self::glob(self::SKILL_DIR . '/scripts/*.php') as $script) {
            preg_match_all('/^\s+--([a-z][a-z-]+)/m', (string) file_get_contents($script), $matches);
            foreach ($matches[1] as $option) {
                $options[] = $option;
            }
        }

        return $options;
    }

    /**
     * @return list<string>
     */
    private static function glob(string $pattern): array
    {
        $files = glob($pattern);

        return $files === false ? [] : $files;
    }
}
