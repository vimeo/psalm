<?php

namespace Psalm\Report;

use Psalm\Internal\Analyzer\IssueData;
use Psalm\Internal\Json\Json;
use Psalm\Report;

use function array_map;
use function array_values;

final class JsonReport extends Report
{
    public function create(): string
    {
        $options = $this->pretty ? Json::PRETTY : Json::DEFAULT;

        $issues_data = array_map(
            static function (IssueData $issue_data): array {
                $issue_data = (array) $issue_data;
                unset($issue_data['dupe_key']);
                return $issue_data;
            },
            $this->issues_data
        );

        return Json::encode(array_values($issues_data), $options) . "\n";
    }
}
