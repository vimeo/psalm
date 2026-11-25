<?php

declare(strict_types=1);

namespace Psalm\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psalm\Internal\Cli\Psalm;
use Psalm\Internal\Cli\Psalter;
use ReflectionClassConstant;

use function array_intersect_key;
use function array_map;
use function basename;
use function dirname;
use function escapeshellarg;
use function exec;
use function file_exists;
use function file_get_contents;
use function getmypid;
use function glob;
use function implode;
use function json_decode;
use function preg_match;
use function preg_match_all;
use function preg_replace;
use function rtrim;
use function str_contains;
use function str_ends_with;
use function sys_get_temp_dir;

use const PHP_BINARY;

/**
 * Keeps the user-facing agent skill in skills/ in sync with the CLI and the docs it names.
 */
final class AgentSkillTest extends BaseTestCase
{
    private const SKILL_DIR = __DIR__ . '/../skills/psalm';

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
        $this->addToAssertionCount(1);
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
        $this->addToAssertionCount(1);
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
        $this->addToAssertionCount(1);
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
        $this->assertFalse(str_contains($gitattributes, '/skills '), 'skills/ must not be export-ignored');
        $composer = (string) file_get_contents(__DIR__ . '/../composer.json');
        $this->assertStringContainsString('"source": "skills"', $composer);
    }

    public function testThePluginsStartTheServersThroughTheLauncher(): void
    {
        $plugin = json_decode((string) file_get_contents(self::SKILL_DIR . '/.claude-plugin/plugin.json'), true);
        $this->assertIsArray($plugin);
        $this->assertSame(['psalm' => [
            'command' => 'php',
            'args' => ['${CLAUDE_PLUGIN_ROOT}/scripts/server.php', 'mcp', '${CLAUDE_PROJECT_DIR}'],
        ]], $plugin['mcpServers'] ?? null);
        $this->assertSame(['psalm' => [
            'command' => 'php',
            'args' => ['${CLAUDE_PLUGIN_ROOT}/scripts/server.php', 'lsp', '${CLAUDE_PROJECT_DIR}'],
            'extensionToLanguage' => ['.php' => 'php'],
            'startupTimeout' => 300_000,
        ]], $plugin['lspServers'] ?? null);

        $opencode = (string) file_get_contents(self::SKILL_DIR . '/opencode/index.js');
        $this->assertStringContainsString('join(skillDir, "scripts", "server.php")', $opencode);
        $this->assertFileExists(self::SKILL_DIR . '/scripts/server.php');
    }

    public function testTheAgentPluginsBranchHasTheMarketplaceAndThePlugins(): void
    {
        $dir = sys_get_temp_dir() . '/psalm-agent-plugins-test-' . (int) getmypid();
        exec(
            escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/../bin/ci/publish-agent-plugins.php')
                . ' --build 7.99.1 ' . escapeshellarg($dir),
            $output,
            $exit_code,
        );
        $this->assertSame(0, $exit_code, implode("\n", $output));

        try {
            $marketplace = json_decode((string) file_get_contents($dir . '/.claude-plugin/marketplace.json'), true);
            $this->assertIsArray($marketplace);
            $this->assertSame('psalm', $marketplace['name'] ?? null);
            $plugins = $marketplace['plugins'] ?? null;
            $this->assertIsArray($plugins);
            $this->assertSame(
                ['name' => 'psalm', 'source' => './psalm', 'version' => '7.99.1'],
                array_intersect_key((array) ($plugins[0] ?? []), ['name' => true, 'source' => true, 'version' => true]),
            );

            $plugin = json_decode((string) file_get_contents($dir . '/psalm/.claude-plugin/plugin.json'), true);
            $this->assertIsArray($plugin);
            $this->assertSame('7.99.1', $plugin['version'] ?? null);
            $this->assertFileExists($dir . '/psalm/SKILL.md');
            $this->assertFileExists($dir . '/psalm/scripts/server.php');

            $package = json_decode((string) file_get_contents($dir . '/psalm/package.json'), true);
            $this->assertIsArray($package);
            $this->assertSame(['opencode-psalm', '7.99.1', 'opencode/index.js'], [
                $package['name'] ?? null,
                $package['version'] ?? null,
                $package['main'] ?? null,
            ]);
            $this->assertArrayNotHasKey('private', $package);
            $this->assertFileExists($dir . '/psalm/opencode/index.js');
        } finally {
            exec('rm -rf ' . escapeshellarg($dir));
        }
    }

    /**
     * skills.sh installs every skill it finds in the repository, .agents/skills included, except internal ones.
     */
    public function testContributorSkillsAreInternal(): void
    {
        $files = self::glob(__DIR__ . '/../.agents/skills/*/SKILL.md');
        $this->assertNotEmpty($files);
        foreach ($files as $file) {
            $this->assertTrue(self::isInternal($file), basename(dirname($file)) . ' must be internal');
        }
        $this->assertFalse(self::isInternal(self::SKILL_DIR . '/SKILL.md'), 'skills/psalm must not be internal');
    }

    private static function isInternal(string $skill_file): bool
    {
        if (preg_match('/\A---\R(.*?)\R---\R/s', (string) file_get_contents($skill_file), $matches) !== 1) {
            return false;
        }

        return preg_match('/^metadata:\R  internal: "true"$/m', $matches[1]) === 1;
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
