<?php
namespace Psalm\Plugin;

use Psalm\Codebase;
use Psalm\SourceControl\SourceControlInfo;

class Shepherd implements \Psalm\Plugin\Hook\AfterAnalysisInterface
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
        if (!function_exists('curl_init')) {
            echo 'No curl found, cannot send data to ' . $codebase->config->shepherd_host . PHP_EOL;
            return;
        }

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
                'coverage' => $codebase->analyzer->getTotalTypeCoverage($codebase)
            ];

            $payload = json_encode($data);

            $base_address = $codebase->config->shepherd_host;

            if (parse_url($base_address, PHP_URL_SCHEME) === null) {
                $base_address = 'https://' . $base_address;
            }

            // Prepare new cURL resource
            $ch = curl_init($base_address . '/hooks/psalm');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

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
            $return = curl_exec($ch);

            if ($return !== '') {
                echo 'Error with Psalm Shepherd:' . PHP_EOL;

                if ($return === false) {
                    /** @var array */
                    $curl_info = curl_getinfo($ch);

                    if (($curl_info['ssl_verify_result'] ?? 0) !== 0) {
                        echo 'Curl SSL error: ';

                        switch ($curl_info['ssl_verify_result']) {
                            case 2:
                                echo 'unable to get issuer certificate';
                                break;
                            case 3:
                                echo 'unable to get certificate CRL';
                                break;
                            case 4:
                                echo 'unable to decrypt certificate’s signature';
                                break;
                            case 5:
                                echo 'unable to decrypt CRL’s signature';
                                break;
                            case 6:
                                echo 'unable to decode issuer public key';
                                break;
                            case 7:
                                echo 'certificate signature failure';
                                break;
                            case 8:
                                echo 'CRL signature failure';
                                break;
                            case 9:
                                echo 'certificate is not yet valid';
                                break;
                            case 10:
                                echo 'certificate has expired';
                                break;
                            case 11:
                                echo 'CRL is not yet valid';
                                break;
                            case 12:
                                echo 'CRL has expired';
                                break;
                            case 13:
                                echo 'format error in certificate’s notBefore field';
                                break;
                            case 14:
                                echo 'format error in certificate’s notAfter field';
                                break;
                            case 15:
                                echo 'format error in CRL’s lastUpdate field';
                                break;
                            case 16:
                                echo 'format error in CRL’s nextUpdate field';
                                break;
                            case 17:
                                echo 'out of memory';
                                break;
                            case 18:
                                echo 'self signed certificate';
                                break;
                            case 19:
                                echo 'self signed certificate in certificate chain';
                                break;
                            case 20:
                                echo 'unable to get local issuer certificate';
                                break;
                            case 21:
                                echo 'unable to verify the first certificate';
                                break;
                            case 22:
                                echo 'certificate chain too long';
                                break;
                            case 23:
                                echo 'certificate revoked';
                                break;
                            case 24:
                                echo 'invalid CA certificate';
                                break;
                            case 25:
                                echo 'path length constraint exceeded';
                                break;
                            case 26:
                                echo 'unsupported certificate purpose';
                                break;
                            case 27:
                                echo 'certificate not trusted';
                                break;
                            case 28:
                                echo 'certificate rejected';
                                break;
                            case 29:
                                echo 'subject issuer mismatch';
                                break;
                            case 30:
                                echo 'authority and subject key identifier mismatch';
                                break;
                            case 31:
                                echo 'authority and issuer serial number mismatch';
                                break;
                            case 32:
                                echo 'key usage does not include certificate signing';
                                break;
                            case 50:
                                echo 'application verification failure';
                                break;
                        }

                        echo PHP_EOL;
                    } else {
                        echo var_export(curl_getinfo($ch), true) . PHP_EOL;
                    }
                } else {
                    echo $return . PHP_EOL;
                    echo 'Git args: ' . var_export($source_control_info->toArray(), true) . PHP_EOL;
                    echo 'CI args: ' . var_export($build_info, true) . PHP_EOL;
                }
            }

            // Close cURL session handle
            curl_close($ch);
        }
    }
}
