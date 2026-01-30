<?php

declare(strict_types=1);

namespace Psalm\Tests;

use DOMAttr;
use DOMDocument;
use DOMXPath;
use Override;
use PHPUnit\Framework\Constraint\Constraint;
use Psalm\Config;
use Psalm\Config\IssueHandler;
use Psalm\Context;
use Psalm\DocComment;
use Psalm\Exception\CodeException;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Provider\FakeFileProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Internal\RuntimeCaches;
use Psalm\Issue\UnusedBaselineEntry;
use Psalm\Issue\UnusedIssueHandlerSuppression;
use Psalm\Tests\Internal\Provider\FakeParserCacheProvider;
use UnexpectedValueException;

use function array_diff;
use function array_filter;
use function array_keys;
use function array_map;
use function array_shift;
use function assert;
use function count;
use function dirname;
use function explode;
use function file;
use function file_exists;
use function file_get_contents;
use function glob;
use function implode;
use function in_array;
use function preg_match;
use function preg_quote;
use function scandir;
use function sort;
use function str_replace;
use function str_starts_with;
use function strlen;
use function strpos;
use function substr;
use function trim;
use function usort;
use function var_export;

use const DIRECTORY_SEPARATOR;
use const FILE_IGNORE_NEW_LINES;
use const FILE_SKIP_EMPTY_LINES;
use const LIBXML_NONET;

final class DocumentationTest extends TestCase
{
    /**
     * a list of all files containing annotation documentation
     */
    private const ANNOTATION_DOCS = [
        'docs/annotating_code/supported_annotations.md',
        'docs/annotating_code/templated_annotations.md',
        'docs/annotating_code/adding_assertions.md',
        'docs/security_analysis/annotations.md',
    ];

    /**
     * annotations that we don’t want documented
     */
    private const INTENTIONALLY_UNDOCUMENTED_ANNOTATIONS = [
        '@psalm-self-out', // Not documented as it's a legacy alias of @psalm-this-out
        '@psalm-variadic',
    ];

    /**
     * These should be documented
     */
    private const WALL_OF_SHAME = [
        '@psalm-assert-untainted',
        '@psalm-flow',
        '@psalm-generator-return',
        '@psalm-override-method-visibility',
        '@psalm-override-property-visibility',
        '@psalm-scope-this',
        '@psalm-seal-methods',
        '@psalm-stub-override',
    ];

    protected ProjectAnalyzer $project_analyzer;

    private static string $docContents = '';

    /**
     * @return array<string, array<int, string>>
     */
    private static function getCodeBlocksFromDocs(): array
    {
        $issues_dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'running_psalm' . DIRECTORY_SEPARATOR . 'issues';

        if (!file_exists($issues_dir)) {
            throw new UnexpectedValueException('docs not found');
        }

        $issue_code = [];
        $files = glob($issues_dir . '/*.md');
        assert($files !== false);

        foreach ($files as $file_path) {
            $file_contents = file_get_contents($file_path);
            assert($file_contents !== false);

            $file_lines = explode("\n", $file_contents);

            $current_issue = str_replace('# ', '', array_shift($file_lines));

            for ($i = 0, $j = count($file_lines); $i < $j; ++$i) {
                $current_line = $file_lines[$i];

                if (substr($current_line, 0, 6) === '```php' && $current_issue) {
                    $current_block = '';
                    ++$i;

                    do {
                        $current_block .= $file_lines[$i] . "\n";
                        ++$i;
                    } while (substr($file_lines[$i], 0, 3) !== '```' && $i < $j);

                    $issue_code[$current_issue][] = trim($current_block);

                    continue 2;
                }
            }
        }

        return $issue_code;
    }

    #[Override]
    public function setUp(): void
    {
        RuntimeCaches::clearAll();

        $this->file_provider = new FakeFileProvider();

        $this->project_analyzer = new ProjectAnalyzer(
            new TestConfig(),
            new Providers(
                $this->file_provider,
                new FakeParserCacheProvider(),
            ),
        );

        $this->project_analyzer->setPhpVersion('8.0', 'tests');
    }

    public function testAllIssuesCoveredInConfigSchema(): void
    {
        $all_issues = IssueHandler::getAllIssueTypes();
        $all_issues[] = 'PluginIssue'; // not an ordinary issue
        sort($all_issues);

        $schema = new DOMDocument();
        $schema->load(__DIR__ . '/../config.xsd', LIBXML_NONET);

        $xpath = new DOMXPath($schema);
        $xpath->registerNamespace('xs', 'http://www.w3.org/2001/XMLSchema');

        /** @var iterable<mixed, DOMAttr> $handlers */
        $handlers = $xpath->query('//xs:complexType[@name="IssueHandlersType"]/xs:choice/xs:element/@name');
        $handler_types = [];
        foreach ($handlers as $handler) {
            $handler_types[] = $handler->value;
        }
        sort($handler_types);

        $this->assertSame(implode("\n", $all_issues), implode("\n", $handler_types));
    }

    public function testAllIssuesCovered(): void
    {
        $all_issues = IssueHandler::getAllIssueTypes();
        $all_issues[] = 'ParseError';
        $all_issues[] = 'PluginIssue';

        sort($all_issues);

        $code_blocks = self::getCodeBlocksFromDocs();

        // these cannot have code
        $code_blocks['UnrecognizedExpression'] = true;
        $code_blocks['UnrecognizedStatement'] = true;
        $code_blocks['PluginIssue'] = true;
        $code_blocks['TaintedInput'] = true;
        $code_blocks['TaintedCustom'] = true;
        $code_blocks['ComplexFunction'] = true;
        $code_blocks['ComplexMethod'] = true;
        $code_blocks['ConfigIssue'] = true;

        $documented_issues = array_keys($code_blocks);
        sort($documented_issues);

        $this->assertSame(implode("\n", $all_issues), implode("\n", $documented_issues));
    }

    /**
     * @dataProvider providerInvalidCodeParse
     * @small
     * @param array<string> $ignored_issues
     */
    public function testInvalidCode(string $code, string $error_message, array $ignored_issues = [], bool $check_references = false, string $php_version = '8.0'): void
    {
        if (strpos($this->getTestName(), 'SKIPPED-') !== false) {
            $this->markTestSkipped();
        }

        $this->project_analyzer->setPhpVersion($php_version, 'tests');

        if ($check_references) {
            $this->project_analyzer->getCodebase()->reportUnusedCode();
            $this->project_analyzer->trackUnusedSuppressions();
        }

        $is_taint_test = strpos($error_message, 'Tainted') !== false;

        $is_array_offset_test = strpos($error_message, 'ArrayOffset') && strpos($error_message, 'PossiblyUndefined') !== false;

        $this->project_analyzer->getConfig()->ensure_array_string_offsets_exist = $is_array_offset_test;
        $this->project_analyzer->getConfig()->ensure_array_int_offsets_exist = $is_array_offset_test;

        $this->project_analyzer->getConfig()->ensure_override_attribute = $error_message === 'MissingOverrideAttribute';

        $this->project_analyzer->getCodebase()->literal_array_key_check = $error_message === 'LiteralKeyUnshapedArray';

        foreach ($ignored_issues as $error_level) {
            $this->project_analyzer->getCodebase()->config->setCustomErrorLevel($error_level, Config::REPORT_SUPPRESS);
        }

        $this->expectException(CodeException::class);
        $this->expectExceptionMessageMatches('/\b' . preg_quote($error_message, '/') . '\b/');

        $codebase = $this->project_analyzer->getCodebase();
        $codebase->config->visitPreloadedStubFiles($codebase);

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile($file_path, $code);

        if ($is_taint_test) {
            $this->project_analyzer->trackTaintedInputs();
        }

        $this->analyzeFile($file_path, new Context());

        if ($check_references) {
            $this->project_analyzer->consolidateAnalyzedData();
        }
    }

    /**
     * @return array<string,array{string,string,string[],bool,string}>
     */
    public function providerInvalidCodeParse(): array
    {
        $invalid_code_data = [];

        foreach (self::getCodeBlocksFromDocs() as $issue_name => $blocks) {
            $php_version = '8.0';
            $ignored_issues = [];
            switch ($issue_name) {
                case 'InvalidStringClass':
                case 'MissingThrowsDocblock':
                case 'PluginClass':
                case 'RedundantIdentityWithTrue':
                case 'TraitMethodSignatureMismatch':
                case 'UncaughtThrowInGlobalScope':
                case UnusedBaselineEntry::getIssueType():
                case UnusedIssueHandlerSuppression::getIssueType():
                    continue 2;

                /** @todo reinstate this test when the issue is restored */
                case 'MethodSignatureMustProvideReturnType':
                    continue 2;

                case 'InvalidFalsableReturnType':
                    $ignored_issues = ['FalsableReturnStatement'];
                    break;

                case 'InvalidNullableReturnType':
                    $ignored_issues = ['NullableReturnStatement'];
                    break;

                case 'InvalidReturnType':
                    $ignored_issues = ['InvalidReturnStatement'];
                    break;

                case 'MixedStringOffsetAssignment':
                    $ignored_issues = ['MixedAssignment'];
                    break;

                case 'ParadoxicalCondition':
                    $ignored_issues = ['MissingParamType'];
                    break;

                case 'UnusedClass':
                case 'UnusedMethod':
                    $ignored_issues = ['UnusedVariable'];
                    break;


                case 'ClassMustBeFinal':
                    $ignored_issues = ['UnusedClass'];
                    break;

                case 'AmbiguousConstantInheritance':
                case 'DeprecatedConstant':
                case 'DuplicateEnumCase':
                case 'DuplicateEnumCaseValue':
                case 'InvalidEnumBackingType':
                case 'InvalidEnumCaseValue':
                case 'InvalidEnumMethod':
                case 'NoEnumProperties':
                case 'OverriddenFinalConstant':
                case 'InvalidInterfaceImplementation':
                    $php_version = '8.1';
                    break;

                case 'InvalidOverride':
                case 'MissingOverrideAttribute':
                case 'MissingClassConstType':
                    $php_version = '8.3';
                    break;
            }
            if (str_starts_with($issue_name, 'Taint')) {
                $ignored_issues = TaintTest::IGNORE;
            }

            $invalid_code_data[$issue_name] = [
                $blocks[0],
                $issue_name,
                $ignored_issues,
                $issue_name === 'ClassMustBeFinal'
                    || strpos($issue_name, 'Unused') !== false
                    || strpos($issue_name, 'Unevaluated') !== false
                    || strpos($issue_name, 'Unnecessary') !== false,
                $php_version,
            ];
        }

        return $invalid_code_data;
    }

    public function testShortcodesAreUnique(): void
    {
        $all_issues = IssueHandler::getAllIssueTypes();
        $all_shortcodes = [];

        foreach ($all_issues as $issue_type) {
            /** @var class-string $issue_class */
            $issue_class = '\\Psalm\\Issue\\' . $issue_type;
            /** @var int $shortcode */
            $shortcode = $issue_class::SHORTCODE;
            $all_shortcodes[$shortcode][] = $issue_type;
        }

        $duplicate_shortcodes = array_filter(
            $all_shortcodes,
            static fn($issues): bool => count($issues) > 1,
        );

        $this->assertEquals(
            [],
            $duplicate_shortcodes,
            "Duplicate shortcodes found: \n" . var_export($duplicate_shortcodes, true),
        );
    }

    /** @dataProvider knownAnnotations */
    public function testAllAnnotationsAreDocumented(string $annotation): void
    {
        if ('' === self::$docContents) {
            foreach (self::ANNOTATION_DOCS as $file) {
                $file_contents = file_get_contents(__DIR__ . '/../' . $file);
                assert($file_contents !== false);
                self::$docContents .= $file_contents;
            }
        }

        $this->assertThat(
            self::$docContents,
            $this->conciseExpected($this->stringContains('@psalm-' . $annotation)),
            "'@psalm-$annotation' is not present in the docs",
        );
    }

    /** @return iterable<string, array{string}> */
    public function knownAnnotations(): iterable
    {
        foreach (DocComment::PSALM_ANNOTATIONS as $annotation) {
            if (in_array('@psalm-' . $annotation, self::INTENTIONALLY_UNDOCUMENTED_ANNOTATIONS, true)) {
                continue;
            }

            if (in_array('@psalm-' . $annotation, self::WALL_OF_SHAME, true)) {
                continue;
            }

            yield $annotation => [$annotation];
        }
    }

    /**
     * Creates a constraint wrapper that displays the expected value in a concise form
     */
    public function conciseExpected(Constraint $inner): Constraint
    {
        return new class ($inner) extends Constraint
        {
            private Constraint $inner;

            public function __construct(Constraint $inner)
            {
                $this->inner = $inner;
            }

            #[Override]
            public function toString(): string
            {
                return $this->inner->toString();
            }

            #[Override]
            protected function matches(mixed $other): bool
            {
                return $this->inner->matches($other);
            }

            #[Override]
            protected function failureDescription(mixed $other): string
            {
                return $this->exporter()->shortenedExport($other) . ' ' . $this->toString();
            }
        };
    }

    /**
     * Tests that issues.md contains the expected links to issue documentation.
     * issues.md can be generated automatically with bin/docs/generate_documentation_issues_list.php.
     */
    public function testIssuesIndex(): void
    {
        $docs_dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . "docs" . DIRECTORY_SEPARATOR . "running_psalm" . DIRECTORY_SEPARATOR;
        $issues_index = "{$docs_dir}issues.md";
        $issues_dir = "{$docs_dir}issues";

        if (!file_exists($issues_dir)) {
            throw new UnexpectedValueException("Issues documentation not found");
        }

        if (!file_exists($issues_index)) {
            throw new UnexpectedValueException("Issues index not found");
        }

        $issues_index_contents = file($issues_index, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($issues_index_contents === false) {
            throw new UnexpectedValueException("Issues index returned false");
        }
        array_shift($issues_index_contents); // Remove title

        $issues_index_list = array_map(function (string $issues_line) {
            preg_match('/^ - \[([^\]]*)\]\(issues\/\1\.md\)$/', $issues_line, $matches);
            $this->assertCount(2, $matches, "Invalid format in issues index: $issues_line");
            return $matches[1];
        }, $issues_index_contents);

        $dir_contents = scandir($issues_dir);
        assert($dir_contents !== false);
        $issue_files = array_filter(array_map(function (string $issue_file) {
            if ($issue_file === "." || $issue_file === "..") {
                return false;
            }
            $this->assertStringEndsWith(".md", $issue_file, "Invalid file in issues documentation: $issue_file");
            return substr($issue_file, 0, strlen($issue_file) - 3);
        }, $dir_contents));

        $unlisted_issues = array_diff($issue_files, $issues_index_list);
        $this->assertEmpty($unlisted_issues, "Issue documentation missing from issues.md: " . implode(", ", $unlisted_issues));

        $missing_documentation = array_diff($issues_index_list, $issue_files);
        $this->assertEmpty($missing_documentation, "issues.md has link to non-existent documentation for: " . implode(", ", $missing_documentation));

        $sorted = $issues_index_list;
        usort($sorted, "strcasecmp");
        for ($i = 0; $i < count($sorted); ++$i) {
            $this->assertEquals($sorted[$i], $issues_index_list[$i], "issues.md out of order, expected {$sorted[$i]} before {$issues_index_list[$i]}");
        }
    }
}
