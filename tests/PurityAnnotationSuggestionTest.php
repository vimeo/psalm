<?php

declare(strict_types=1);

namespace Psalm\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use Psalm\Context;
use Psalm\IssueBuffer;

use function array_column;
use function array_filter;
use function array_unique;
use function array_values;
use function implode;
use function in_array;
use function sort;
use function str_contains;

final class PurityAnnotationSuggestionTest extends TestCase
{
    private const SUGGESTIONS = [
        'MissingAbstractPureAnnotation',
        'MissingImmutableAnnotation',
        'MissingInterfaceImmutableAnnotation',
        'MissingPureAnnotation',
    ];

    /**
     * @return array<string, array{bool, bool, list<string>, bool}>
     * @psalm-pure
     */
    public static function providerSuggestions(): array
    {
        return [
            'offByDefault' => [false, false, [], false],
            'offByDefaultWithTaintAnalysis' => [false, true, [], false],
            'onWithoutTaintAnalysis' => [true, false, self::SUGGESTIONS, false],
            'onWithTaintAnalysis' => [true, true, self::SUGGESTIONS, true],
        ];
    }

    /**
     * @param list<string> $expected_types
     */
    #[DataProvider('providerSuggestions')]
    public function testSuggestions(
        bool $suggest_purity_annotations,
        bool $taint_analysis,
        array $expected_types,
        bool $mentions_security_analysis,
    ): void {
        $this->testConfig->throw_exception = false;
        $this->testConfig->suggest_purity_annotations = $suggest_purity_annotations;

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                function slug(string $text): string {
                    return strtolower(trim($text));
                }

                final class Cart {
                    private int $items = 0;

                    public function add(): void {
                        $this->items++;
                    }

                    public function count(): int {
                        return $this->items;
                    }
                }

                final class Money {
                    public function __construct(public readonly int $cents) {}
                }

                interface Shape {
                    public function area(): float;
                }

                abstract class Base {
                    abstract public function run(): void;
                }',
        );

        $this->project_analyzer->setPhpVersion('8.1', 'tests');
        $this->analyzeFile($file_path, new Context(), false, $taint_analysis);
        $this->project_analyzer->consolidateAnalyzedData();

        $issues = array_values(array_filter(
            IssueBuffer::getIssuesDataForFile($file_path),
            static fn($issue): bool => in_array($issue->type, self::SUGGESTIONS, true),
        ));

        $types = array_values(array_unique(array_column($issues, 'type')));
        sort($types);
        self::assertSame($expected_types, $types);

        $mentions = false;
        foreach ($issues as $issue) {
            $mentions = $mentions || str_contains($issue->message, 'security analysis');
        }
        self::assertSame(
            $mentions_security_analysis,
            $mentions,
            implode("\n", array_column($issues, 'message')),
        );
    }
}
