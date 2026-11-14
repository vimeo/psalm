<?php

declare(strict_types=1);

namespace Psalm\Overlay;

use JsonSerializable;
use Override;

use function array_filter;
use function array_map;
use function array_values;
use function is_array;
use function is_int;
use function is_string;

/**
 * The answer to a request: its JSON form is the envelope `psalm --overlay --output-format=json` prints
 *
 * @psalm-immutable
 */
final class OverlayResult implements JsonSerializable
{
    /**
     * @param list<OverlayIssue> $new_issues
     * @param list<string> $analyzed_files relative to the project root
     * @param list<string> $not_checked the passes over the whole project this request skipped
     */
    public function __construct(
        public readonly int $protocol,
        public readonly array $new_issues,
        public readonly array $analyzed_files,
        public readonly array $not_checked,
    ) {
    }

    /**
     * Keys this version does not know are left out: the protocol grows by adding keys
     *
     * @param array<array-key, mixed> $envelope
     * @psalm-pure
     */
    public static function fromEnvelope(array $envelope): self
    {
        $lists = array_filter($envelope, is_array(...));
        $ints = array_filter($envelope, is_int(...));

        return new self(
            $ints['protocol'] ?? 1,
            array_map(
                OverlayIssue::fromArray(...),
                array_values(array_filter($lists['new_issues'] ?? [], is_array(...))),
            ),
            array_values(array_filter($lists['analyzed_files'] ?? [], is_string(...))),
            array_values(array_filter($lists['not_checked'] ?? [], is_string(...))),
        );
    }

    /** Whether an introduced issue has the severity "error": what `psalm --overlay` exits with 2 for */
    public function hasNewErrors(): bool
    {
        foreach ($this->new_issues as $issue) {
            if ($issue->severity === 'error') {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, mixed> */
    #[Override]
    public function jsonSerialize(): array
    {
        return [
            'protocol' => $this->protocol,
            'new_issues' => $this->new_issues,
            'analyzed_files' => $this->analyzed_files,
            'not_checked' => $this->not_checked,
        ];
    }
}
