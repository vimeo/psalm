<?php
namespace Psalm\Plugin;

use Psalm\Codebase;
use Psalm\SourceControl\SourceControlInfo;
use function function_exists;
use function fwrite;
use const STDERR;
use const PHP_EOL;
use function array_filter;
use function json_encode;
use function parse_url;
use const PHP_URL_SCHEME;
use function curl_init;
use function curl_setopt;
use const CURLOPT_RETURNTRANSFER;
use const CURLINFO_HEADER_OUT;
use const CURLOPT_POST;
use const CURLOPT_POSTFIELDS;
use const CURLOPT_HTTPHEADER;
use function strlen;
use function curl_exec;
use function curl_getinfo;
use function var_export;
use function curl_close;

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
            fwrite(STDERR, 'No curl found, cannot send data to ' . $codebase->config->shepherd_host . PHP_EOL);
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
                fwrite(STDERR, 'Error with Psalm Shepherd:' . PHP_EOL);

                if ($return === false) {
                    /** @var array */
                    $curl_info = curl_getinfo($ch);

                    if (($curl_info['ssl_verify_result'] ?? 0) !== 0) {
                        fwrite(STDERR, 'Curl SSL error: ');

                        switch ($curl_info['ssl_verify_result']) {
                            case 2:
                                fwrite(STDERR, 'unable to get issuer certificate');
                                break;
                            case 3:
                                fwrite(STDERR, 'unable to get certificate CRL');
                                break;
                            case 4:
                                fwrite(STDERR, 'unable to decrypt certificate’s signature');
                                break;
                            case 5:
                                fwrite(STDERR, 'unable to decrypt CRL’s signature');
                                break;
                            case 6:
                                fwrite(STDERR, 'unable to decode issuer public key');
                                break;
                            case 7:
                                fwrite(STDERR, 'certificate signature failure');
                                break;
                            case 8:
                                fwrite(STDERR, 'CRL signature failure');
                                break;
                            case 9:
                                fwrite(STDERR, 'certificate is not yet valid');
                                break;
                            case 10:
                                fwrite(STDERR, 'certificate has expired');
                                break;
                            case 11:
                                fwrite(STDERR, 'CRL is not yet valid');
                                break;
                            case 12:
                                fwrite(STDERR, 'CRL has expired');
                                break;
                            case 13:
                                fwrite(STDERR, 'format error in certificate’s notBefore field');
                                break;
                            case 14:
                                fwrite(STDERR, 'format error in certificate’s notAfter field');
                                break;
                            case 15:
                                fwrite(STDERR, 'format error in CRL’s lastUpdate field');
                                break;
                            case 16:
                                fwrite(STDERR, 'format error in CRL’s nextUpdate field');
                                break;
                            case 17:
                                fwrite(STDERR, 'out of memory');
                                break;
                            case 18:
                                fwrite(STDERR, 'self signed certificate');
                                break;
                            case 19:
                                fwrite(STDERR, 'self signed certificate in certificate chain');
                                break;
                            case 20:
                                fwrite(STDERR, 'unable to get local issuer certificate');
                                break;
                            case 21:
                                fwrite(STDERR, 'unable to verify the first certificate');
                                break;
                            case 22:
                                fwrite(STDERR, 'certificate chain too long');
                                break;
                            case 23:
                                fwrite(STDERR, 'certificate revoked');
                                break;
                            case 24:
                                fwrite(STDERR, 'invalid CA certificate');
                                break;
                            case 25:
                                fwrite(STDERR, 'path length constraint exceeded');
                                break;
                            case 26:
                                fwrite(STDERR, 'unsupported certificate purpose');
                                break;
                            case 27:
                                fwrite(STDERR, 'certificate not trusted');
                                break;
                            case 28:
                                fwrite(STDERR, 'certificate rejected');
                                break;
                            case 29:
                                fwrite(STDERR, 'subject issuer mismatch');
                                break;
                            case 30:
                                fwrite(STDERR, 'authority and subject key identifier mismatch');
                                break;
                            case 31:
                                fwrite(STDERR, 'authority and issuer serial number mismatch');
                                break;
                            case 32:
                                fwrite(STDERR, 'key usage does not include certificate signing');
                                break;
                            case 50:
                                fwrite(STDERR, 'application verification failure');
                                break;
                        }

                        fwrite(STDERR, PHP_EOL);
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
