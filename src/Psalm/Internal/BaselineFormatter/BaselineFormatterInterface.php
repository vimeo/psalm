<?php

declare(strict_types=1);

namespace Psalm\Internal\BaselineFormatter;

use Psalm\ErrorBaseline;

/**
 * @psalm-import-type psalmFormattedBaseline from ErrorBaseline
 * @internal
 */
interface BaselineFormatterInterface
{
    public static function getKey(): string;

    /**
     * @param psalmFormattedBaseline $grouped_issues
     */
    public function format(
        array $grouped_issues,
        bool $include_php_versions
    ): string;

    /**
     * @return psalmFormattedBaseline
     */
    public function read(string $content): array;
}
