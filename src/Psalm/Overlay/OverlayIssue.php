<?php

declare(strict_types=1);

namespace Psalm\Overlay;

use JsonSerializable;
use Override;

use function array_filter;
use function is_array;
use function is_int;
use function is_string;

/**
 * An issue the overlays introduce, as `psalm --output-format=json` reports issues
 *
 * @psalm-immutable
 */
final class OverlayIssue implements JsonSerializable
{
    /**
     * @param 'error'|'info' $severity what the baseline and the config's levels leave of it
     * @param string $file_name relative to the project root
     * @param array<array-key, mixed>|null $taint_trace
     * @param array<array-key, mixed>|null $other_references
     */
    public function __construct(
        public readonly string $link,
        public readonly string $severity,
        public readonly int $line_from,
        public readonly int $line_to,
        public readonly string $type,
        public readonly string $message,
        public readonly string $file_name,
        public readonly string $file_path,
        public readonly string $snippet,
        public readonly string $selected_text,
        public readonly int $from,
        public readonly int $to,
        public readonly int $snippet_from,
        public readonly int $snippet_to,
        public readonly int $column_from,
        public readonly int $column_to,
        public readonly int $shortcode,
        public readonly int $error_level,
        public readonly ?array $taint_trace,
        public readonly ?array $other_references,
        public readonly bool $is_security,
    ) {
    }

    /**
     * Keys this version does not know are left out: the protocol grows by adding keys
     *
     * @param array<array-key, mixed> $issue an issue of the envelope
     * @psalm-pure
     */
    public static function fromArray(array $issue): self
    {
        $strings = array_filter($issue, is_string(...));
        $ints = array_filter($issue, is_int(...));
        $arrays = array_filter($issue, is_array(...));
        $string = static fn(string $key): string => $strings[$key] ?? '';
        $int = static fn(string $key): int => $ints[$key] ?? 0;
        $array = static fn(string $key): ?array => $arrays[$key] ?? null;

        return new self(
            $string('link'),
            $string('severity') === 'info' ? 'info' : 'error',
            $int('line_from'),
            $int('line_to'),
            $string('type'),
            $string('message'),
            $string('file_name'),
            $string('file_path'),
            $string('snippet'),
            $string('selected_text'),
            $int('from'),
            $int('to'),
            $int('snippet_from'),
            $int('snippet_to'),
            $int('column_from'),
            $int('column_to'),
            $int('shortcode'),
            $int('error_level'),
            $array('taint_trace'),
            $array('other_references'),
            ($issue['is_security'] ?? false) === true,
        );
    }

    /** @return array<string, mixed> */
    #[Override]
    public function jsonSerialize(): array
    {
        return [
            'link' => $this->link,
            'severity' => $this->severity,
            'line_from' => $this->line_from,
            'line_to' => $this->line_to,
            'type' => $this->type,
            'message' => $this->message,
            'file_name' => $this->file_name,
            'file_path' => $this->file_path,
            'snippet' => $this->snippet,
            'selected_text' => $this->selected_text,
            'from' => $this->from,
            'to' => $this->to,
            'snippet_from' => $this->snippet_from,
            'snippet_to' => $this->snippet_to,
            'column_from' => $this->column_from,
            'column_to' => $this->column_to,
            'shortcode' => $this->shortcode,
            'error_level' => $this->error_level,
            'taint_trace' => $this->taint_trace,
            'other_references' => $this->other_references,
            'is_security' => $this->is_security,
        ];
    }
}
