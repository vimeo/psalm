<?php

declare(strict_types=1);

namespace Psalm\Plugin;

use BadMethodCallException;
use Override;
use Psalm\Codebase;
use Psalm\Config;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\VersionUtils;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\AfterAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterAnalysisEvent;
use Psalm\Progress\DebugProgress;
use Psalm\Progress\Progress;
use stdClass;

use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_merge;
use function array_values;
use function assert;
use function curl_error;
use function curl_exec;
use function curl_getinfo;
use function curl_init;
use function curl_setopt;
use function error_get_last;
use function explode;
use function file_put_contents;
use function function_exists;
use function in_array;
use function is_array;
use function is_string;
use function json_encode;
use function ksort;
use function parse_url;
use function preg_match;
use function preg_replace;
use function rawurldecode;
use function sprintf;
use function str_replace;
use function strip_tags;
use function strlen;
use function strtolower;
use function usort;
use function var_export;

use const CURLINFO_HEADER_OUT;
use const CURLOPT_CONNECTTIMEOUT;
use const CURLOPT_FOLLOWLOCATION;
use const CURLOPT_HTTPHEADER;
use const CURLOPT_POST;
use const CURLOPT_POSTFIELDS;
use const CURLOPT_PROTOCOLS;
use const CURLOPT_REDIR_PROTOCOLS;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_TIMEOUT;
use const CURLPROTO_HTTP;
use const CURLPROTO_HTTPS;
use const JSON_THROW_ON_ERROR;
use const PHP_EOL;
use const PHP_URL_HOST;
use const PHP_URL_SCHEME;
use const PHP_VERSION;

/**
 * @psalm-type ShepherdPayload = array{
 *     build: array{CI_NAME: mixed, ...<array-key, mixed>},
 *     git: array<array-key, mixed>,
 *     issues: list<IssueData>,
 *     issue_counts: array{baselined: array<string, int<1, max>>, info: array<string, int<1, max>>},
 *     analysis: array{
 *         full: bool,
 *         file_count: int<0, max>,
 *         target_php_version: string,
 *         find_unused_code: bool,
 *         taint_analysis: bool
 *     },
 *     coverage: array{int, int},
 *     level: int<1, 8>,
 *     versions: array{psalm: string, parser: string, php: string}
 * }
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
        $endpoint = $event->getCodebase()->config->shepherd_endpoint;
        $writes_a_file = strtolower((string) parse_url($endpoint, PHP_URL_SCHEME)) === 'file';

        if (!$writes_a_file && !function_exists('curl_init')) {
            $progress->warning('Results not sent to Shepherd: ext-curl is missing');

            return;
        }

        $rawPayload = self::collectPayloadToSend($event);

        if ($rawPayload === null) {
            $progress->warning(
                'Results not sent to Shepherd: no CI service with a GitHub repository detected',
            );

            return;
        }

        if ($writes_a_file) {
            self::writePayload($endpoint, $rawPayload, $progress);

            return;
        }

        self::sendPayload($endpoint, $rawPayload, $progress);
    }

    /**
     * build and git are as loosely typed as AfterAnalysisEvent and SourceControlInfo::toArray() give them. coverage
     * is the mixed and the non-mixed expression counts.
     *
     * @return ShepherdPayload|null
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

        // A pull request is checked out as a merge commit that is not part of it: its review is made on its head
        /** @var array{head?: array{id?: string|null}}|null $event_git */
        $event_git = $build_info['git'] ?? null;
        $pull_request_head = $event_git['head']['id'] ?? null;
        if (isset($build_info['CI_PR_NUMBER'])
            && $pull_request_head !== null
            && isset($source_control_data['head'])
            && is_array($source_control_data['head'])
        ) {
            $source_control_data['head']['id'] = $pull_request_head;
        }

        unset($build_info['git']);

        $issues_grouped_by_filename = $event->getIssues();
        $normalized_data = $issues_grouped_by_filename === [] ? [] : array_values(array_filter(
            array_merge(...array_values($issues_grouped_by_filename)), // flatten an array
            static fn(IssueData $i): bool => $i->severity === IssueData::SEVERITY_ERROR,
        ));

        $codebase = $event->getCodebase();

        $baselined_issue_counts = IssueBuffer::getBaselinedIssueCounts();

        $info_issue_counts = [];
        foreach ($issues_grouped_by_filename as $file_issues) {
            foreach ($file_issues as $issue) {
                if ($issue->severity === IssueData::SEVERITY_INFO) {
                    $info_issue_counts[$issue->type] = ($info_issue_counts[$issue->type] ?? 0) + 1;
                }
            }
        }

        // exact, as every issue the baseline covers reaches the event with severity info
        foreach ($baselined_issue_counts as $type => $count) {
            $remaining = ($info_issue_counts[$type] ?? 0) - $count;
            if ($remaining > 0) {
                $info_issue_counts[$type] = $remaining;
            } else {
                unset($info_issue_counts[$type]);
            }
        }

        ksort($info_issue_counts);

        return [
            'build' => $build_info,
            'git' => $source_control_data,
            'issues' => $normalized_data,
            'issue_counts' => ['baselined' => $baselined_issue_counts, 'info' => $info_issue_counts],
            'analysis' => [
                'full' => ProjectAnalyzer::getInstance()->full_run,
                'file_count' => $codebase->analyzer->getFileCountWithAnalysisResults(),
                'target_php_version' => Codebase::getPhpVersionString($codebase->analysis_php_version_id),
                'find_unused_code' => $codebase->find_unused_code !== null,
                // set exactly when the config or a CLI flag turns taint analysis on
                'taint_analysis' => $codebase->taint_flow_graph !== null,
            ],
            'coverage' => $codebase->analyzer->getTotalTypeCoverage($codebase),
            'level' => Config::getInstance()->level,
            'versions' => [
                'psalm' => VersionUtils::getPsalmVersion(),
                'parser' => VersionUtils::getPhpParserVersion(),
                'php' => PHP_VERSION,
            ],
        ];
    }

    /**
     * @param ShepherdPayload $rawPayload
     */
    private static function writePayload(string $endpoint, array $rawPayload, Progress $progress): void
    {
        $path = (string) preg_replace('#^file://#i', '', $endpoint);
        // file://name/... names a host, and /C:name is relative to the current directory of drive C on Windows
        if (preg_match('#^(?:/?[A-Za-z]:[/\\\\]|/(?![A-Za-z]:))#', $path) !== 1) {
            $progress->warning('Results not written: use an absolute path, as in file:///path/to/report.json');

            return;
        }

        // PHP opens file:///C:/... as C:/... on Windows
        if (@file_put_contents($endpoint, self::encodePayload($rawPayload)) === false) {
            $error = error_get_last()['message'] ?? 'unknown error';
            $progress->warning(
                "Results not written to $path: " . str_replace("file_put_contents($endpoint): ", '', $error),
            );

            return;
        }

        $progress->write("Results written to $path" . PHP_EOL);
    }

    /**
     * @param ShepherdPayload $rawPayload
     * @psalm-pure
     */
    private static function encodePayload(array $rawPayload): string
    {
        // an empty group must encode as {}, not []
        $rawPayload['issue_counts'] = array_map(
            static fn(array $counts): stdClass => (object) $counts,
            $rawPayload['issue_counts'],
        );

        return json_encode($rawPayload, JSON_THROW_ON_ERROR);
    }

    /**
     * @param ShepherdPayload $rawPayload
     */
    private static function sendPayload(string $endpoint, array $rawPayload, Progress $progress): void
    {
        $payload = self::encodePayload($rawPayload);

        // curl also reads local files (file://) and would show them as the response
        if (!in_array(strtolower((string) parse_url($endpoint, PHP_URL_SCHEME)), ['http', 'https'], true)) {
            $progress->warning('Results not sent to Shepherd: the URL must use http or https');

            return;
        }

        // Prepare new cURL resource
        $ch = curl_init($endpoint);
        assert($ch !== false);
        // Reporting is best-effort and must not stall the analysis result.
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
        curl_setopt($ch, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

        // No Content-Length: curl sets it for the POST, but a header set here would also go out with
        // the GET that follows a redirect, and Google Apps Script answers that GET with HTTP 400.
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        // Submit the POST request
        $curl_result = curl_exec($ch);

        /** @var array{http_code: int, ssl_verify_result: int, ...<string, mixed>} $curl_info */
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
        // each value is masked before it's formatted: var_export() would escape quotes inside a secret
        $secrets = self::getEndpointSecrets($endpoint);
        $progress->debug(sprintf(
            "Shepherd endpoint: %s\nShepherd response: %s\ncURL info:\n%s\n",
            self::redact($endpoint, $secrets),
            is_string($curl_result) ? self::redact(strip_tags($curl_result), $secrets) : 'n/a',
            var_export(array_map(
                static fn(mixed $value): mixed => is_string($value) ? self::redact($value, $secrets) : $value,
                $curl_info,
            ), true),
        ));
    }

    /**
     * Masks credentials (in the user info of URLs and in the authentication and cookie headers curl sends, see
     * CURLINFO_HEADER_OUT), query values, and the given secrets wherever they appear (e.g. echoed in a response),
     * as debug output often ends up in CI logs
     *
     * @param list<non-empty-string> $secrets
     * @psalm-pure
     */
    private static function redact(string $text, array $secrets): string
    {
        $text = str_replace($secrets, '***', $text);

        return (string) preg_replace(
            [
                '#(://)[^/\s]*@#',
                '#([?&][^=&\s\#]+)=[^&\s\#]*#',
                '#^((?:proxy-)?authorization|cookie)\s*:.*$#mi',
            ],
            ['$1***@', '$1=***', '$1: ***'],
            $text,
        );
    }

    /**
     * The user, password and query values of the endpoint, as given and decoded, longest first (so that a value
     * isn't masked only in part, through a shorter one it contains)
     *
     * @return list<non-empty-string>
     * @psalm-pure
     */
    private static function getEndpointSecrets(string $endpoint): array
    {
        $parts = parse_url($endpoint);
        $values = [$parts['user'] ?? '', $parts['pass'] ?? ''];

        foreach (explode('&', $parts['query'] ?? '') as $pair) {
            $values[] = explode('=', $pair, 2)[1] ?? '';
        }

        $secrets = [];
        foreach ($values as $value) {
            // too short to be a secret, and masking it would mangle the rest of the output
            if (strlen($value) >= 4) {
                $secrets[$value] = true;
                $secrets[rawurldecode($value)] = true;
            }
        }

        $secrets = array_keys($secrets);
        usort($secrets, static fn(string $a, string $b): int => strlen($b) <=> strlen($a));

        return $secrets;
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
