<?php
namespace Psalm\Plugin;

use Psalm\Codebase;
use Psalm\SourceControl\SourceControlInfo;

class SpiritGuide implements \Psalm\Plugin\Hook\AfterAnalysisInterface
{
    /**
     * Called after analysis is complete
     * @param array<int, array{severity: string, line_from: int, line_to: int, type: string, message: string,
     * file_name: string, file_path: string, snippet: string, from: int, to: int,
     * snippet_from: int, snippet_to: int, column_from: int, column_to: int, selected_text: string}> $issues
     *
     * @return void
     */
    public static function afterAnalysis(
        Codebase $codebase,
        array $issues,
        array $build_info,
        SourceControlInfo $source_control_info = null
    ) {
        if ($source_control_info instanceof \Psalm\SourceControl\Git\GitInfo && $build_info) {
            $data = [
                'build' => $build_info,
                'git' => $source_control_info->toArray(),
                'issues' => array_filter(
                    $issues,
                    function (array $i) : bool {
                        return $i['severity'] === 'error';
                    }
                ),
                'coverage_map' => $codebase->analyzer->getTypeCoverageMap($codebase),
                'coverage' => $codebase->analyzer->getTotalTypeCoverage($codebase)
            ];

            $payload = json_encode($data);

            // Prepare new cURL resource
            $ch = curl_init('https://' . $codebase->config->spirit_host . '/telemetry');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

            // Set HTTP Header for POST request
            curl_setopt(
                $ch,
                CURLOPT_HTTPHEADER,
                [
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($payload)
                ]
            );

            // Submit the POST request
            curl_exec($ch);

            // Close cURL session handle
            curl_close($ch);
        }
    }
}
