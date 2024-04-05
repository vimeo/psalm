<?php

namespace Psalm\Plugin;

use BadMethodCallException;
use Psalm\Config;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\Internal\VersionUtils;
use Psalm\Plugin\EventHandler\AfterAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterAnalysisEvent;

use function array_filter;
use function array_key_exists;
use function array_merge;
use function array_values;
use function curl_close;
use function curl_exec;
use function curl_getinfo;
use function curl_init;
use function curl_setopt;
use function function_exists;
use function fwrite;
use function is_array;
use function is_int;
use function is_string;
use function json_encode;
use function parse_url;
use function sprintf;
use function strip_tags;
use function strlen;
use function substr_compare;
use function var_export;

use const CURLINFO_HEADER_OUT;
use const CURLOPT_FOLLOWLOCATION;
use const CURLOPT_HTTPHEADER;
use const CURLOPT_POST;
use const CURLOPT_POSTFIELDS;
use const CURLOPT_RETURNTRANSFER;
use const JSON_THROW_ON_ERROR;
use const PHP_EOL;
use const PHP_URL_HOST;
use const PHP_URL_SCHEME;
use const STDERR;

final class Shepherd implements AfterAnalysisInterface
{
    /**
     * Called after analysis is complete
     */
    public static function afterAnalysis(
        AfterAnalysisEvent $event
    ): void {
        if (!function_exists('curl_init')) {
            fwrite(STDERR, "No curl found, cannot send data to shepherd server.\n");

            return;
        }

        $rawPayload = self::collectPayloadToSend($event);

        if ($rawPayload === null) {
            return;
        }

        $config = $event->getCodebase()->config;

        /**
         * Deprecated logic, in Psalm 6 just use $config->shepherd_endpoint
         * '#' here is just a hack/marker to use a custom endpoint instead just a custom domain
         * case 1: empty option                                         (use https://shepherd.dev/hooks/psalm/)
         * case 2: custom domain (/hooks/psalm should be appended)      (use https://custom.domain/hooks/psalm)
         * case 3: custom endpoint (/hooks/psalm should be appended)    (use custom endpoint)
         */
        if (substr_compare($config->shepherd_endpoint, '#', -1) === 0) {
            $shepherd_endpoint = $config->shepherd_endpoint;
        } else {
            /** @psalm-suppress DeprecatedProperty, DeprecatedMethod */
            $shepherd_endpoint = self::buildShepherdUrlFromHost($config->shepherd_host);
        }

        self::sendPayload($shepherd_endpoint, $rawPayload);
    }

    /**
     * @psalm-pure
     * @deprecated Will be removed in Psalm 6
     */
    private static function buildShepherdUrlFromHost(string $host): string
    {
        if (parse_url($host, PHP_URL_SCHEME) === null) {
            $host = 'https://' . $host;
        }

        return $host . '/hooks/psalm';
    }

    /**
     * @return array{
     *     build: array,
     *     git: array,
     *     issues: array,
     *     coverage: list<int>,
     *     level: int<1, 8>,
     *     versions: array<string, string>
     * }|null
     */
    private static function collectPayloadToSend(AfterAnalysisEvent $event): ?array
    {
        /** @see \Psalm\Internal\ExecutionEnvironment\BuildInfoCollector::collect */
        $build_info = $event->getBuildInfo();

        $is_ci_env = array_key_exists('CI_NAME', $build_info); // 'git' key always presents
        if (! $is_ci_env) {
            return null;
        }

        $source_control_info = $event->getSourceControlInfo();
        $source_control_data = $source_control_info ? $source_control_info->toArray() : [];

        if ($source_control_data === [] && isset($build_info['git']) && is_array($build_info['git'])) {
            $source_control_data = $build_info['git'];
        }

        unset($build_info['git']);

        if ($build_info === []) {
            return null;
        }

        $issues_grouped_by_filename = $event->getIssues();
        $normalized_data = $issues_grouped_by_filename === [] ? [] : array_values(array_filter(
            array_merge(...array_values($issues_grouped_by_filename)), // flatten an array
            static fn(IssueData $i): bool => $i->severity === IssueData::SEVERITY_ERROR,
        ));

        $codebase = $event->getCodebase();

        return [
            'build' => $build_info,
            'git' => $source_control_data,
            'issues' => $normalized_data,
            'coverage' => $codebase->analyzer->getTotalTypeCoverage($codebase),
            'level' => Config::getInstance()->level,
            'versions' => [
                'psalm' => VersionUtils::getPsalmVersion(),
                'parser' => VersionUtils::getPhpParserVersion(),
            ],
        ];
    }

    private static function sendPayload(string $endpoint, array $rawPayload): void
    {
        $payload = json_encode($rawPayload, JSON_THROW_ON_ERROR);

        // Prepare new cURL resource
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

        // Set HTTP Header for POST request
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($payload),
            ],
        );

        // Submit the POST request
        $curl_result = curl_exec($ch);

        /** @var array{http_code: int, ssl_verify_result: int} $curl_info */
        $curl_info = curl_getinfo($ch);

        // Close cURL session handle
        curl_close($ch);

        $response_status_code = $curl_info['http_code'];
        if ($response_status_code >= 200 && $response_status_code < 300) {
            $shepherd_host = parse_url($endpoint, PHP_URL_HOST);

            fwrite(STDERR, "🐑 results sent to $shepherd_host 🐑" . PHP_EOL);
            return;
        }

        $is_ssl_error = $curl_info['ssl_verify_result'] > 1;
        if ($is_ssl_error) {
            fwrite(STDERR, self::getCurlSslErrorMessage($curl_info['ssl_verify_result']) . PHP_EOL);
            return;
        }

        $output = "Shepherd error: $endpoint endpoint responded with $response_status_code HTTP status code.\n";
        $response_content = is_string($curl_result) ? strip_tags($curl_result) : 'n/a';
        $output .= "Shepherd response: $response_content\n";
        if ($response_status_code === 0) {
            $output .= "Please check shepherd endpoint — it should be a valid URL.\n";
        }

        $output .= sprintf("cURL Debug info:\n%s\n", var_export($curl_info, true));
        fwrite(STDERR, $output);
    }

    /**
     * @param mixed $ch
     * @psalm-pure
     * @deprecated Will be removed in Psalm 6
     */
    public static function getCurlErrorMessage($ch): string
    {
        /**
         * @psalm-suppress MixedArgument
         * @var array
         */
        $curl_info = curl_getinfo($ch);

        /** @psalm-suppress MixedAssignment */
        $ssl_verify_result = $curl_info['ssl_verify_result'] ?? null;
        if (is_int($ssl_verify_result) && $ssl_verify_result > 1) {
            return self::getCurlSslErrorMessage($ssl_verify_result);
        }

        return '';
    }

    /**
     * @psalm-pure
     */
    private static function getCurlSslErrorMessage(int $ssl_verify_result): string
    {
        switch ($ssl_verify_result) {
            case 1:
                throw new BadMethodCallException('code 1 means a successful SSL response, there is no error to parse');
            case 2:
                return 'unable to get issuer certificate';
            case 3:
                return 'unable to get certificate CRL';
            case 4:
                return 'unable to decrypt certificate’s signature';
            case 5:
                return 'unable to decrypt CRL’s signature';
            case 6:
                return 'unable to decode issuer public key';
            case 7:
                return 'certificate signature failure';
            case 8:
                return 'CRL signature failure';
            case 9:
                return 'certificate is not yet valid';
            case 10:
                return 'certificate has expired';
            case 11:
                return 'CRL is not yet valid';
            case 12:
                return 'CRL has expired';
            case 13:
                return 'format error in certificate’s notBefore field';
            case 14:
                return 'format error in certificate’s notAfter field';
            case 15:
                return 'format error in CRL’s lastUpdate field';
            case 16:
                return 'format error in CRL’s nextUpdate field';
            case 17:
                return 'out of memory';
            case 18:
                return 'self signed certificate';
            case 19:
                return 'self signed certificate in certificate chain';
            case 20:
                return 'unable to get local issuer certificate';
            case 21:
                return 'unable to verify the first certificate';
            case 22:
                return 'certificate chain too long';
            case 23:
                return 'certificate revoked';
            case 24:
                return 'invalid CA certificate';
            case 25:
                return 'path length constraint exceeded';
            case 26:
                return 'unsupported certificate purpose';
            case 27:
                return 'certificate not trusted';
            case 28:
                return 'certificate rejected';
            case 29:
                return 'subject issuer mismatch';
            case 30:
                return 'authority and subject key identifier mismatch';
            case 31:
                return 'authority and issuer serial number mismatch';
            case 32:
                return 'key usage does not include certificate signing';
            case 50:
                return 'application verification failure';
            default:
                return "unknown cURL SSL error $ssl_verify_result";
        }
    }
}
