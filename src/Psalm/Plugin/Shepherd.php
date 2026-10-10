<?php

declare(strict_types=1);

namespace Psalm\Plugin;

use BadMethodCallException;
use Override;
use Psalm\Config;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\Internal\VersionUtils;
use Psalm\Plugin\EventHandler\AfterAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterAnalysisEvent;
use Psalm\Progress\DebugProgress;
use Psalm\Progress\Progress;

use function array_filter;
use function array_key_exists;
use function array_merge;
use function array_values;
use function assert;
use function curl_error;
use function curl_exec;
use function curl_getinfo;
use function curl_init;
use function curl_setopt;
use function function_exists;
use function is_array;
use function is_string;
use function json_encode;
use function parse_url;
use function preg_replace;
use function sprintf;
use function strip_tags;
use function strlen;
use function var_export;

use const CURLINFO_HEADER_OUT;
use const CURLOPT_CONNECTTIMEOUT;
use const CURLOPT_FOLLOWLOCATION;
use const CURLOPT_HTTPHEADER;
use const CURLOPT_POST;
use const CURLOPT_POSTFIELDS;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_TIMEOUT;
use const JSON_THROW_ON_ERROR;
use const PHP_EOL;
use const PHP_URL_HOST;

/**
 * @api
 */
final class Shepherd implements AfterAnalysisInterface
{
    /**
     * Called after analysis is complete
     */
    #[Override]
    public static function afterAnalysis(
        AfterAnalysisEvent $event,
    ): void {
        $progress = $event->getCodebase()->progress;

        if (!function_exists('curl_init')) {
            $progress->warning('Results not sent to Shepherd: ext-curl is missing');

            return;
        }

        $rawPayload = self::collectPayloadToSend($event);

        if ($rawPayload === null) {
            return;
        }

        $config = $event->getCodebase()->config;

        self::sendPayload($config->shepherd_endpoint, $rawPayload, $progress);
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

    private static function sendPayload(string $endpoint, array $rawPayload, Progress $progress): void
    {
        $payload = json_encode($rawPayload, JSON_THROW_ON_ERROR);

        // Prepare new cURL resource
        $ch = curl_init($endpoint);
        assert($ch !== false);
        // Reporting is best-effort and must not stall the analysis result.
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
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

        // The endpoint may hold a secret (e.g. a token in its query): only its host is shown
        $shepherd_host = (string) parse_url($endpoint, PHP_URL_HOST);

        $response_status_code = $curl_info['http_code'];
        if ($response_status_code >= 200 && $response_status_code < 300) {
            $progress->write("Results sent to Shepherd ($shepherd_host)" . PHP_EOL);
            return;
        }

        if ($curl_info['ssl_verify_result'] > 1) {
            $problem = 'SSL error: ' . self::getCurlSslErrorMessage($curl_info['ssl_verify_result']);
        } elseif ($response_status_code === 0) {
            $problem = curl_error($ch) ?: 'no response';
        } elseif ($response_status_code >= 300 && $response_status_code < 400) {
            $problem = "HTTP $response_status_code redirect";
        } else {
            $problem = "HTTP $response_status_code";
        }

        $progress->warning("Results not sent to Shepherd ($shepherd_host): $problem"
            . ($progress instanceof DebugProgress ? '' : '. Run with --debug for details'));
        $progress->debug(self::redact(sprintf(
            "Shepherd endpoint: %s\nShepherd response: %s\ncURL info:\n%s\n",
            $endpoint,
            is_string($curl_result) ? strip_tags($curl_result) : 'n/a',
            var_export($curl_info, true),
        )));
    }

    /**
     * Masks credentials and query values in URLs (the endpoint, the URL it redirected to, the request line),
     * as debug output often ends up in CI logs
     *
     * @psalm-pure
     */
    private static function redact(string $text): string
    {
        return (string) preg_replace(
            ['#(://)[^/@\s\']+@#', '#([?&][^=&\s\'\#]+)=[^&\s\'\#]*#'],
            ['$1***@', '$1=***'],
            $text,
        );
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
