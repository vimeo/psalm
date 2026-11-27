<?php

declare(strict_types=1);

namespace Psalm\Internal\Cli;

use Psalm\Config\IssueHandler;

use function class_exists;
use function constant;
use function dirname;
use function file_get_contents;
use function is_file;
use function preg_match;
use function strcasecmp;
use function trim;

/**
 * Finds the documentation of an issue type in the docs bundled with the Psalm package.
 *
 * @internal
 */
final class IssueDocs
{
    /**
     * Turns an issue type (PossiblyNullReference), a shortcode (78, 078) or a link (https://psalm.dev/078,
     * https://psalm.dev/docs/running_psalm/issues/PossiblyNullReference/) into the name of an issue type.
     */
    public static function findIssueType(string $query): ?string
    {
        $query = trim($query);
        $issue_types = IssueHandler::getAllIssueTypes();

        if (preg_match('~^(?:(?:https?://)?psalm\.dev/)?0*(\d+)/?$~i', $query, $matches) === 1
            && isset($matches[1])
        ) {
            $shortcode = (int) $matches[1];
            foreach ($issue_types as $issue_type) {
                $class = 'Psalm\\Issue\\' . $issue_type;
                if ($shortcode > 0 && class_exists($class) && constant($class . '::SHORTCODE') === $shortcode) {
                    return $issue_type;
                }
            }

            return null;
        }

        $docs_url = '~^(?:https?://)?psalm\.dev/docs/running_psalm/issues/(\w+)(?:/|\.md)?$~i';
        if (preg_match($docs_url, $query, $matches) === 1 && isset($matches[1])) {
            $query = $matches[1];
        }

        // Only names from the list of issue types reach the file system, so no path can leave the issues directory.
        foreach ($issue_types as $issue_type) {
            if (strcasecmp($issue_type, $query) === 0) {
                return $issue_type;
            }
        }

        return null;
    }

    /**
     * The markdown documentation of the issue, or null when $query is no known issue or has no page.
     */
    public static function read(string $query): ?string
    {
        $issue_type = self::findIssueType($query);
        if ($issue_type === null) {
            return null;
        }

        $path = dirname(__DIR__, 4) . '/docs/running_psalm/issues/' . $issue_type . '.md';
        if (!is_file($path)) {
            return null;
        }

        $contents = file_get_contents($path);

        return $contents === false ? null : $contents;
    }
}
