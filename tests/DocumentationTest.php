<?php

namespace Psalm\Tests;

use DOMAttr;
use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\Constraint\Constraint;
use Psalm\Config;
use Psalm\Context;
use Psalm\DocComment;
use Psalm\Internal\RuntimeCaches;
use Psalm\Tests\Internal\Provider;

use function array_filter;
use function array_keys;
use function array_shift;
use function count;
use function dirname;
use function explode;
use function file_exists;
use function file_get_contents;
use function glob;
use function implode;
use function in_array;
use function preg_quote;
use function sort;
use function strpos;
use function str_replace;
use function substr;
use function trim;
use function var_export;

use const DIRECTORY_SEPARATOR;
use const LIBXML_NONET;

class DocumentationTest extends TestCase
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
        '@psalm-self-out', // I'm fairly sure it's intentionally undocumented, but can't find the reference
        '@psalm-variadic',
    ];
    
    /**
     * These should be documented
     */
    private const WALL_OF_SHAME = [
        '@psalm-assert-untainted',
        '@psalm-consistent-constructor',
        '@psalm-flow',
        '@psalm-generator-return',
        '@psalm-ignore-variable-method',
        '@psalm-ignore-variable-property',
        '@psalm-override-method-visibility',
        '@psalm-override-property-visibility',
        '@psalm-scope-this',
        '@psalm-seal-methods',
        '@psalm-stub-override',
        '@psalm-taint-unescape',
        '@psalm-yield',
    ];

    /** @var \Psalm\Internal\Analyzer\ProjectAnalyzer */
    protected $project_analyzer;

    /** @var string */
    private static $docContents = '';

    /**
     * @return array<string, array<int, string>>
     */
    private static function getCodeBlocksFromDocs(): array
    {
        $issues_dir = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'running_psalm' . DIRECTORY_SEPARATOR . 'issues';

        if (!file_exists($issues_dir)) {
            throw new \UnexpectedValueException('docs not found');
        }

        $issue_code = [];

        foreach (glob($issues_dir . '/*.md') as $file_path) {
            $file_contents = file_get_contents($file_path);

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

    public function setUp() : void
    {
        RuntimeCaches::clearAll();

        $this->file_provider = new Provider\FakeFileProvider();

        $this->project_analyzer = new \Psalm\Internal\Analyzer\ProjectAnalyzer(
            new TestConfig(),
            new \Psalm\Internal\Provider\Providers(
                $this->file_provider,
                new Provider\FakeParserCacheProvider()
            )
        );

        $this->project_analyzer->setPhpVersion('8.0');
    }

    public function testAllIssuesCoveredInConfigSchema(): void
    {
        $all_issues = \Psalm\Config\IssueHandler::getAllIssueTypes();
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
        $all_issues = \Psalm\Config\IssueHandler::getAllIssueTypes();
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

        $documented_issues = array_keys($code_blocks);
        sort($documented_issues);

        $this->assertSame(implode("\n", $all_issues), implode("\n", $documented_issues));
    }

    /**
     * @dataProvider providerInvalidCodeParse
     * @small
     *
     * @param string $code
     * @param string $error_message
     * @param array<string> $error_levels
     * @param bool $check_references
     *
     */
    public function testInvalidCode($code, $error_message, $error_levels = [], $check_references = false): void
    {
        if (strpos($this->getTestName(), 'SKIPPED-') !== false) {
            $this->markTestSkipped();
        }

        if ($check_references) {
            $this->project_analyzer->getCodebase()->reportUnusedCode();
            $this->project_analyzer->trackUnusedSuppressions();
        }

        $is_taint_test = strpos($error_message, 'Tainted') !== false;

        $is_array_offset_test = strpos($error_message, 'ArrayOffset') && strpos($error_message, 'PossiblyUndefined') !== false;

        $this->project_analyzer->getConfig()->ensure_array_string_offsets_exist = $is_array_offset_test;
        $this->project_analyzer->getConfig()->ensure_array_int_offsets_exist = $is_array_offset_test;

        foreach ($error_levels as $error_level) {
            $this->project_analyzer->getCodebase()->config->setCustomErrorLevel($error_level, Config::REPORT_SUPPRESS);
        }

        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessageRegExp('/\b' . preg_quote($error_message, '/') . '\b/');

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
     * @return array<string,array{string,string,string[],bool}>
     */
    public function providerInvalidCodeParse(): array
    {
        $invalid_code_data = [];

        foreach (self::getCodeBlocksFromDocs() as $issue_name => $blocks) {
            switch ($issue_name) {
                case 'MissingThrowsDocblock':
                    continue 2;

                case 'UncaughtThrowInGlobalScope':
                    continue 2;

                case 'InvalidStringClass':
                    continue 2;

                case 'ForbiddenEcho':
                    continue 2;

                case 'PluginClass':
                    continue 2;

                case 'RedundantIdentityWithTrue':
                    continue 2;

                case 'TraitMethodSignatureMismatch':
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

                case 'MixedInferredReturnType':
                    $ignored_issues = ['MixedReturnStatement'];
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

                default:
                    $ignored_issues = [];
            }

            $invalid_code_data[$issue_name] = [
                $blocks[0],
                $issue_name,
                $ignored_issues,
                strpos($issue_name, 'Unused') !== false
                    || strpos($issue_name, 'Unevaluated') !== false
                    || strpos($issue_name, 'Unnecessary') !== false,
            ];
        }

        return $invalid_code_data;
    }

    public function testShortcodesAreUnique(): void
    {
        $all_issues = \Psalm\Config\IssueHandler::getAllIssueTypes();
        $all_shortcodes = [];

        foreach ($all_issues as $issue_type) {
            $issue_class = '\\Psalm\\Issue\\' . $issue_type;
            /** @var int $shortcode */
            $shortcode = $issue_class::SHORTCODE;
            $all_shortcodes[$shortcode][] = $issue_type;
        }

        $duplicate_shortcodes = array_filter(
            $all_shortcodes,
            function ($issues): bool {
                return count($issues) > 1;
            }
        );

        $this->assertEquals(
            [],
            $duplicate_shortcodes,
            "Duplicate shortcodes found: \n" . var_export($duplicate_shortcodes, true)
        );
    }

    /** @dataProvider knownAnnotations */
    public function testAllAnnotationsAreDocumented(string $annotation): void
    {
        if ('' === self::$docContents) {
            foreach (self::ANNOTATION_DOCS as $file) {
                self::$docContents .= file_get_contents(__DIR__ . '/../' . $file);
            }
        }

        $this->assertThat(
            self::$docContents,
            $this->conciseExpected($this->stringContains('@psalm-' . $annotation)),
            "'@psalm-$annotation' is not present in the docs"
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
            /** @var Constraint */
            private $inner;

            public function __construct(Constraint $inner)
            {
                $this->inner = $inner;
            }

            public function toString(): string
            {
                return $this->inner->toString();
            }

            protected function matches($other): bool
            {
                return $this->inner->matches($other);
            }

            protected function failureDescription($other): string
            {
                return $this->exporter()->shortenedExport($other) . ' ' . $this->toString();
            }
        };
    }
}
