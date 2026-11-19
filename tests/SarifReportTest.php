<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\Internal\VersionUtils;
use Psalm\Report\ReportOptions;
use Psalm\Report\SarifReport;

use function define;
use function defined;
use function json_decode;

final class SarifReportTest extends TestCase
{
    #[Override]
    public static function setUpBeforeClass(): void
    {
        if (!defined('PSALM_VERSION')) {
            define('PSALM_VERSION', VersionUtils::getPsalmVersion());
        }

        parent::setUpBeforeClass();
    }

    public function testClampsRegionToSchemaMinimum(): void
    {
        // An issue reported at line 0 (e.g. UnusedBaselineEntry from a stale baseline)
        // must not emit startLine 0, which the SARIF schema rejects (minimum 1).
        $issue_data = new IssueData(
            IssueData::SEVERITY_ERROR,
            0,
            0,
            'UnusedBaselineEntry',
            'message',
            'file.php',
            '/',
            '',
            '',
            0,
            0,
            0,
            0,
            0,
            0,
        );

        $report = new SarifReport([$issue_data], [], new ReportOptions());

        $this->assertStringContainsString(
            '"region":{"startLine":1,"endLine":1,"startColumn":1,"endColumn":1}',
            $report->create(),
        );
    }

    /**
     * @return iterable<string, array{string, bool, list<positive-int>, array<string, mixed>}>
     * @psalm-pure
     */
    public static function ruleProperties(): iterable
    {
        yield 'a critical sink' => [
            'TaintedShell',
            true,
            [78],
            ['tags' => ['security', 'external/cwe/cwe-078'], 'security-severity' => '9.8'],
        ];
        yield 'a high one, with two weaknesses' => [
            'TaintedFile',
            true,
            [22, 73],
            ['tags' => ['security', 'external/cwe/cwe-022', 'external/cwe/cwe-073'], 'security-severity' => '7.5'],
        ];
        yield 'a medium one' => [
            'TaintedHeader',
            true,
            [601, 644],
            ['tags' => ['security', 'external/cwe/cwe-601', 'external/cwe/cwe-644'], 'security-severity' => '6.1'],
        ];
        yield 'a security issue without a score or a weakness of its own' => [
            'TaintedCustom',
            true,
            [],
            ['tags' => ['security'], 'security-severity' => '7.0'],
        ];
        yield 'an issue that is not about security, with a weakness' => [
            'PossiblyNullReference',
            false,
            [476],
            ['tags' => ['maintainability', 'external/cwe/cwe-476']],
        ];
        yield 'an issue that is not about security' => [
            'MissingReturnType',
            false,
            [],
            ['tags' => ['maintainability']],
        ];
    }

    /**
     * @param list<positive-int> $cwe_ids
     * @param array<string, mixed> $expected_properties
     */
    #[DataProvider('ruleProperties')]
    public function testTheRulesCarryTheTagsAndTheSecuritySeverity(
        string $type,
        bool $is_security,
        array $cwe_ids,
        array $expected_properties,
    ): void {
        $issue_data = new IssueData(
            IssueData::SEVERITY_ERROR,
            1,
            1,
            $type,
            'message',
            'file.php',
            '/',
            '',
            '',
            0,
            0,
            0,
            0,
            1,
            1,
            100,
            is_security: $is_security,
            cwe_ids: $cwe_ids,
        );

        /** @var array{runs: list<array{tool: array{driver: array{rules: list<array{id: string, properties: array<string, mixed>}>}}, results: list<array{ruleId: string}>}>} $report */
        $report = json_decode((new SarifReport([$issue_data], [], new ReportOptions()))->create(), true);

        $rule = $report['runs'][0]['tool']['driver']['rules'][0];

        // the issue type rather than the shortcode, which SARIF consumers can take for a CWE id
        self::assertSame($type, $rule['id']);
        self::assertSame($type, $report['runs'][0]['results'][0]['ruleId']);
        self::assertSame($expected_properties, $rule['properties']);
    }
}
