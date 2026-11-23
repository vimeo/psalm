<?php

declare(strict_types=1);

namespace Psalm\Internal\Overlay;

use Psalm\Internal\CliUtils;
use Psalm\Internal\Json\Json;
use Symfony\Component\Filesystem\Path;

use function array_key_exists;
use function file_exists;
use function file_get_contents;
use function is_array;
use function is_bool;
use function is_file;
use function is_int;
use function is_readable;
use function is_string;
use function realpath;

use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * The request and the answer as every transport spells them: the `analyze` params, the envelope, the error object
 *
 * @psalm-import-type IssueArray from IssueDiff
 * @internal
 */
final class Protocol
{
    public const VERSION = 1;

    /**
     * @param mixed $params the params of `analyze`, which `--overlays-from` reads too
     * @param string $cwd what relative paths are relative to
     * @throws OverlayFailure
     */
    public static function parseAnalyzeParams(mixed $params, string $cwd): Request
    {
        if (!is_array($params) || !isset($params['overlays']) || !is_array($params['overlays'])
            || $params['overlays'] === []
        ) {
            throw new OverlayFailure(OverlayFailure::INVALID_REQUEST, 'Expected a non-empty list of "overlays"');
        }

        $whole_project = $params['whole_project'] ?? false;
        if (!is_bool($whole_project)) {
            throw new OverlayFailure(OverlayFailure::INVALID_REQUEST, '"whole_project" must be a boolean');
        }

        $timeout_ms = $params['timeout_ms'] ?? null;
        if ($timeout_ms !== null && (!is_int($timeout_ms) || $timeout_ms < 1)) {
            throw new OverlayFailure(OverlayFailure::INVALID_REQUEST, '"timeout_ms" must be a positive integer');
        }

        $overlays = [];
        foreach ($params['overlays'] as $overlay) {
            if (!is_array($overlay) || !isset($overlay['path']) || !is_string($overlay['path'])) {
                throw new OverlayFailure(OverlayFailure::INVALID_REQUEST, 'Every overlay needs a "path"');
            }

            $path = self::resolvePath($overlay['path'], $cwd);

            if (isset($overlays[$path])) {
                throw new OverlayFailure(OverlayFailure::INVALID_REQUEST, 'Two overlays of ' . $overlay['path']);
            }

            $overlays[$path] = self::getContents($overlay, $overlay['path'], $cwd);
        }

        return new Request($overlays, $whole_project, $timeout_ms);
    }

    /**
     * The path of a file that exists: overlays of new files are reserved for a later version of the protocol
     *
     * @throws OverlayFailure
     */
    public static function resolvePath(string $path, string $cwd): string
    {
        $absolute = Path::makeAbsolute($path, $cwd);

        if (!file_exists($absolute)) {
            throw new OverlayFailure(
                OverlayFailure::UNSUPPORTED,
                'Overlays of files that do not exist are not supported yet: ' . $path,
            );
        }

        $real_path = realpath($absolute);

        if ($real_path === false || !is_file($real_path)) {
            throw new OverlayFailure(OverlayFailure::INVALID_OVERLAY, $path . ' is not a file');
        }

        return $real_path;
    }

    /**
     * @param array<array-key, mixed> $overlay
     * @throws OverlayFailure
     */
    private static function getContents(array $overlay, string $path, string $cwd): string
    {
        $kinds = (int) array_key_exists('replacement', $overlay)
            + (int) array_key_exists('contents', $overlay)
            + (int) array_key_exists('deleted', $overlay);

        if ($kinds !== 1) {
            throw new OverlayFailure(
                OverlayFailure::INVALID_REQUEST,
                'The overlay of ' . $path . ' needs exactly one of "replacement", "contents" and "deleted"',
            );
        }

        if (array_key_exists('deleted', $overlay)) {
            if ($overlay['deleted'] !== true) {
                throw new OverlayFailure(OverlayFailure::INVALID_REQUEST, '"deleted" must be true');
            }

            throw new OverlayFailure(
                OverlayFailure::UNSUPPORTED,
                'Overlays that delete a file are not supported yet: ' . $path,
            );
        }

        if (array_key_exists('contents', $overlay)) {
            if (!is_string($overlay['contents'])) {
                throw new OverlayFailure(OverlayFailure::INVALID_REQUEST, '"contents" must be a string');
            }

            return $overlay['contents'];
        }

        $replacement = $overlay['replacement'] ?? null;
        if (!is_string($replacement)) {
            throw new OverlayFailure(OverlayFailure::INVALID_REQUEST, '"replacement" must be a string');
        }

        $replacement_path = Path::makeAbsolute($replacement, $cwd);
        $contents = is_file($replacement_path) && is_readable($replacement_path)
            ? file_get_contents($replacement_path)
            : false;

        if ($contents === false) {
            throw new OverlayFailure(OverlayFailure::INVALID_OVERLAY, 'Cannot read the replacement ' . $replacement);
        }

        return CliUtils::sentByOperator($contents);
    }

    /**
     * @param list<IssueArray> $new_issues
     * @param list<string> $analyzed_files
     * @param list<string> $not_checked
     * @return array{protocol: int, new_issues: list<IssueArray>, analyzed_files: list<string>,
     *     not_checked: list<string>}
     * @psalm-pure
     */
    public static function envelope(array $new_issues, array $analyzed_files, array $not_checked): array
    {
        return [
            'protocol' => self::VERSION,
            'new_issues' => $new_issues,
            'analyzed_files' => $analyzed_files,
            'not_checked' => $not_checked,
        ];
    }

    /**
     * One line of JSON
     *
     * @param array<array-key, mixed> $data
     * @psalm-pure
     */
    public static function encode(array $data): string
    {
        return Json::encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
