<?php
namespace Psalm\Report;

use DOMDocument;
use DOMElement;
use Psalm\Config;
use Psalm\Report;
use function count;
use function sprintf;
use function trim;
use Doctrine\Instantiator\Exception\UnexpectedValueException;

/**
 * based on https://github.com/m50/psalm-json-to-junit
 * Copyright (c) Marisa Clardy marisa@clardy.eu
 *
 * with a few modifications
 */
class JunitReport extends Report
{
    /**
     * {@inheritdoc}
     */
    public function create(): string
    {
        $errors = 0;
        $warnings = 0;
        $tests = 0;

        $ndata = [];

        foreach ($this->issues_data as $error) {
            $is_error = $error->severity === Config::REPORT_ERROR;
            $is_warning = $error->severity === Config::REPORT_INFO;

            if ($is_error) {
                $errors++;
            } elseif ($is_warning) {
                $warnings++;
            } else {
                // currently this never happens
                continue;
            }

            $tests++;

            $fname = $error->file_name;

            if (!isset($ndata[$fname])) {
                $ndata[$fname] = [
                    'errors'   => $is_error ? 1 : 0,
                    'warnings' => $is_warning ? 1 : 0,
                    'failures' => [
                        $this->createFailure($error),
                    ],
                ];
            } else {
                if ($is_error) {
                    $ndata[$fname]['errors']++;
                } else {
                    $ndata[$fname]['warnings']++;
                }

                $ndata[$fname]['failures'][] = $this->createFailure($error);
            }
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $schema = 'https://raw.githubusercontent.com/junit-team/'.
            'junit5/r5.5.1/platform-tests/src/test/resources/jenkins-junit.xsd';

        $suites = $dom->createElement('testsuites');
        $testsuite = $dom->createElement('testsuite');

        if ($testsuite === false) {
            throw new \UnexpectedValueException('Bad falsy value');
        }

        $testsuite->setAttribute('failures', (string) $errors);
        $testsuite->setAttribute('warnings', (string) $warnings);
        $testsuite->setAttribute('name', 'psalm');
        $testsuite->setAttribute('tests', (string) $tests);
        $testsuite->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $testsuite->setAttribute('xsi:noNamespaceSchemaLocation', $schema);
        $suites->appendChild($testsuite);
        $dom->appendChild($suites);

        if (!count($ndata)) {
            $testcase = $dom->createElement('testcase');
            $testcase->setAttribute('name', 'psalm');
            $testsuite->appendChild($testcase);
        } else {
            foreach ($ndata as $file => $report) {
                $this->createTestSuite($dom, $testsuite, $file, $report);
            }
        }



        return $dom->saveXML();
    }

    /**
     * @param  array{
     *         errors: int,
     *         warnings: int,
     *         failures: list<array{
     *             data: array{
     *                 column_from: int,
     *                 column_to: int,
     *                 line: int,
     *                 message: string,
     *                 selected_text: string,
     *                 snippet: string,
     *                 type: string},
     *                 type: string
     *             }>
     *         } $report
     */
    private function createTestSuite(DOMDocument $dom, DOMElement $parent, string $file, array $report): void
    {
        $totalTests = $report['errors'] + $report['warnings'];
        if ($totalTests < 1) {
            $totalTests = 1;
        }

        $testsuite = $dom->createElement('testsuite');
        $testsuite->setAttribute('name', $file);
        $testsuite->setAttribute('file', $file);
        $testsuite->setAttribute('assertions', (string) $totalTests);
        $testsuite->setAttribute('failures', (string) $report['errors']);
        $testsuite->setAttribute('warnings', (string) $report['warnings']);

        $failuresByType = $this->groupByType($report['failures']);
        $testsuite->setAttribute('tests', (string) count($failuresByType));

        $iterator = 0;
        foreach ($failuresByType as $type => $data) {
            foreach ($data as $d) {
                $testcase = $dom->createElement('testcase');
                $testcase->setAttribute('name', "{$file}:{$d['line']}");
                $testcase->setAttribute('file', $file);
                $testcase->setAttribute('class', $type);
                $testcase->setAttribute('classname', $type);
                $testcase->setAttribute('line', (string) $d['line']);
                $testcase->setAttribute('assertions', (string) count($data));

                $failure = $dom->createElement('failure');
                $failure->setAttribute('type', $type);
                $failure->nodeValue = $this->dataToOutput($d);

                $testcase->appendChild($failure);
                $testsuite->appendChild($testcase);
            }
            $iterator++;
        }
        $parent->appendChild($testsuite);
    }

    /**
     * @return array{
     *     data: array{
     *         column_from: int,
     *         column_to: int,
     *         line: int,
     *         message: string,
     *         selected_text: string,
     *         snippet: string,
     *         type: string
     *     },
     *     type: string
     * }
     */
    private function createFailure(\Psalm\Internal\Analyzer\IssueData $issue_data) : array
    {
        return [
            'type' => $issue_data->type,
            'data' => [
                'message'       => $issue_data->message,
                'type'          => $issue_data->type,
                'snippet'       => $issue_data->snippet,
                'selected_text' => $issue_data->selected_text,
                'line'          => $issue_data->line_from,
                'column_from'   => $issue_data->column_from,
                'column_to'     => $issue_data->column_to,
            ],
        ];
    }

    /**
     * @param  array<array{
     *     data: array{
     *         column_from: int,
     *         column_to: int,
     *         line: int,
     *         message: string,
     *         selected_text: string,
     *         snippet: string,
     *         type: string
     *     },
     *     type: string
     * }>  $failures
     *
     * @return array<string, non-empty-list<array{
     *         column_from: int,
     *         column_to: int,
     *         line: int,
     *         message: string,
     *         selected_text: string,
     *         snippet: string,
     *         type: string
     *  }>>
     */
    private function groupByType(array $failures)
    {
        $nfailures = [];

        foreach ($failures as $failure) {
            $nfailures[$failure['type']][] = $failure['data'];
        }

        return $nfailures;
    }

    /**
     * @param  array<string, int|string>  $data
     */
    private function dataToOutput(array $data): string
    {
        $ret = '';

        foreach ($data as $key => $value) {
            $value = trim((string) $value);
            $ret .= "{$key}: {$value}\n";
        }

        return $ret;
    }
}
