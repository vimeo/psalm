<?php
namespace Psalm\Report;

use Psalm\Config;
use Psalm\Report;
use ReflectionClass;
use function htmlspecialchars;
use function sprintf;

class CheckstyleReport extends Report
{
    public function create(): string
    {
        $output = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";

        $output .= '<checkstyle>' . "\n";

        foreach ($this->issues_data as $issue_data) {
            $message = sprintf(
                '%s: %s',
                $issue_data->type,
                $issue_data->message
            );

            /** @var class-string $parent_issue */
            $parent_issue = '\Psalm\\Issue\\' . $issue_data->type;
            $parent_issue_classname = (new ReflectionClass($parent_issue))
                ->getParentClass()
                ->getShortName();
            $parent_issue_type = Config::getParentIssueType($issue_data->type) ? : $parent_issue_classname;
            $issue_source = 'Psalm.' . $parent_issue_type . '.' . $issue_data->type;

            $output .= '<file name="' . htmlspecialchars($issue_data->file_name) . '">' . "\n";
            $output .= ' ';
            $output .= '<error';
            $output .= ' line="' . $issue_data->line_from . '"';
            $output .= ' column="' . $issue_data->column_from . '"';
            $output .= ' severity="' . $issue_data->severity . '"';
            $output .= ' source="' . $issue_source . '"';
            $output .= ' message="' . htmlspecialchars($message) . '"';
            $output .= '/>' . "\n";
            $output .= '</file>' . "\n";
        }

        $output .= '</checkstyle>' . "\n";

        return $output;
    }
}
