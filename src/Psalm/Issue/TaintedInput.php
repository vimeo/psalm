<?php

declare(strict_types=1);

namespace Psalm\Issue;

use Psalm\CodeLocation;
use Psalm\Internal\Analyzer\DataFlowNodeData;

abstract class TaintedInput extends CodeIssue
{
    public const ERROR_LEVEL = -2;
    /** @var int<0, max> */
    public const SHORTCODE = 205;

    /**
     * @param list<array{location: ?CodeLocation, label: string, entry_path_type: string}> $journey
     */
    public function __construct(
        string $message,
        CodeLocation $code_location,
        public readonly array $journey,
        public readonly string $journey_text,
    ) {
        parent::__construct($message, $code_location);
    }

    /**
     * @return list<DataFlowNodeData|array{label: string, entry_path_type: string}>
     */
    public function getTaintTrace(): array
    {
        $nodes = [];

        foreach ($this->journey as ['location' => $location, 'label' => $label, 'entry_path_type' => $path_type]) {
            if ($location) {
                $nodes[] = self::nodeToDataFlowNodeData($location, $label);
            } else {
                $nodes[] = ['label' => $label, 'entry_path_type' => $path_type];
            }
        }

        return $nodes;
    }

    public static function nodeToDataFlowNodeData(
        CodeLocation $location,
        string $label,
    ): DataFlowNodeData {
        $selection_bounds = $location->getSelectionBounds();
        $snippet_bounds = $location->getSnippetBounds();

        return new DataFlowNodeData(
            $label,
            $location->getLineNumber(),
            $location->getEndLineNumber(),
            $location->file_name,
            $location->file_path,
            $location->getSnippet(),
            $selection_bounds[0],
            $selection_bounds[1],
            $snippet_bounds[0],
            $location->getColumn(),
            $location->getEndColumn(),
        );
    }

    public function getJourneyMessage(): string
    {
        return $this->message . ' in path: ' . $this->journey_text;
    }
}
