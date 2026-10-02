<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use Psalm\CodeLocation;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\DataFlow\Path;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Union;

use function abs;
use function array_keys;
use function array_sum;
use function count;
use function str_starts_with;
use function strlen;
use function substr;

/**
 * @internal
 * @psalm-capabilities read-props|write-this-props|write-props|write-refs
 */
abstract class DataFlowGraph
{
    /** @var array<string, array<string, Path>> */
    protected array $forward_edges = [];

    abstract public function addNode(DataFlowNode $node): void;

    /**
     * @psalm-capabilities read-props|write-this-props|write-props|write-refs
     */
    public function addPath(
        DataFlowNode $from,
        DataFlowNode $to,
        string $path_type,
        int $added_taints = 0,
        int $removed_taints = 0,
    ): void {
        $from_id = $from->id;
        $to_id = $to->id;

        if ($from_id === $to_id) {
            return;
        }

        $length = 0;

        if ($from->code_location
            && $to->code_location
            && $from->code_location->file_path === $to->code_location->file_path
        ) {
            $to_line = $to->code_location->raw_line_number;
            $from_line = $from->code_location->raw_line_number;
            $length = abs($to_line - $from_line);
        }

        $this->forward_edges[$from_id][$to_id] = new Path($path_type, $length, $added_taints, $removed_taints);
    }

    /**
     * Adds paths into $node from the parent nodes found in the array keys and values of $type,
     * at any depth, as the array assignments that put them there. Returns whether it added any.
     *
     * A value without parent nodes of its own carries its taint in those of its array keys and
     * values: an array fetch from it takes the parent nodes of the fetched value. Once $node is
     * made a parent node of such a value, the fetch goes through $node instead, which these
     * paths keep leading to the same taint.
     *
     * @psalm-capabilities read-props|write-this-props|write-props|write-refs
     */
    public function addPathsFromNestedParentNodes(DataFlowNode $node, Union $type, CodeLocation $location): bool
    {
        $added = false;

        foreach ($type->getAtomicTypes() as $atomic_type) {
            if ($atomic_type instanceof TKeyedArray) {
                foreach ($atomic_type->properties as $key => $property_type) {
                    $added = $this->addPathsFromParentNodes(
                        $node,
                        $property_type,
                        'arrayvalue-assignment-\'' . $key . '\'',
                        $location,
                    ) || $added;
                }

                $type_params = $atomic_type->fallback_params;
            } elseif ($atomic_type instanceof TArray) {
                $type_params = $atomic_type->type_params;
            } else {
                continue;
            }

            if ($type_params !== null) {
                $added = $this->addPathsFromParentNodes($node, $type_params[0], 'arraykey-assignment', $location)
                    || $added;
                $added = $this->addPathsFromParentNodes($node, $type_params[1], 'arrayvalue-assignment', $location)
                    || $added;
            }
        }

        return $added;
    }

    /**
     * Adds paths of type $path_type into $node from the parent nodes of $type, or else from those
     * nested in it (see addPathsFromNestedParentNodes()). Returns whether it added any.
     *
     * @psalm-capabilities read-props|write-this-props|write-props|write-refs
     */
    private function addPathsFromParentNodes(
        DataFlowNode $node,
        Union $type,
        string $path_type,
        CodeLocation $location,
    ): bool {
        if ($type->parent_nodes) {
            foreach ($type->parent_nodes as $parent_node) {
                $this->addPath($parent_node, $node, $path_type);
            }

            return true;
        }

        $nested_node = DataFlowNode::getForAssignment($node->label . ' ' . $path_type, $location);

        if (!$this->addPathsFromNestedParentNodes($nested_node, $type, $location)) {
            return false;
        }

        $this->addNode($nested_node);
        $this->addPath($nested_node, $node, $path_type);

        return true;
    }

    /**
     * @param array<string> $previous_path_types
     * @psalm-pure
     */
    protected static function shouldIgnoreFetch(
        string $path_type,
        string $expression_type,
        array $previous_path_types,
    ): bool {
        $el = strlen($expression_type);

        // arraykey-fetch requires a matching arraykey-assignment at the same level
        // otherwise the tainting is not valid
        if (str_starts_with($path_type, $expression_type . '-fetch-')
            || ($path_type === 'arraykey-fetch' && $expression_type === 'arrayvalue')
        ) {
            $fetch_nesting = 0;

            for ($x = count($previous_path_types)-1; $x >= 0; $x--) {
                $previous_path_type = $previous_path_types[$x];
                if ($previous_path_type === $expression_type . '-assignment') {
                    if ($fetch_nesting === 0) {
                        return false;
                    }

                    $fetch_nesting--;
                }

                if (str_starts_with($previous_path_type, $expression_type . '-fetch')) {
                    $fetch_nesting++;
                }

                if (str_starts_with($previous_path_type, $expression_type . '-assignment-')) {
                    if ($fetch_nesting > 0) {
                        $fetch_nesting--;
                        continue;
                    }

                    if (substr($previous_path_type, $el + 12) === substr($path_type, $el + 7)) {
                        return false;
                    }

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return array{int, int, int, float}
     * @psalm-mutation-free
     */
    public function getEdgeStats(): array
    {
        $lengths = 0;

        $destination_counts = [];
        $origin_counts = [];

        foreach ($this->forward_edges as $from_id => $destinations) {
            foreach ($destinations as $to_id => $path) {
                if ($path->length === 0) {
                    continue;
                }

                $lengths += $path->length;

                if (!isset($destination_counts[$to_id])) {
                    $destination_counts[$to_id] = 0;
                }

                $destination_counts[$to_id]++;

                $origin_counts[$from_id] = true;
            }
        }

        $count = array_sum($destination_counts);

        if (!$count) {
            return [0, 0, 0, 0.0];
        }

        $mean = $lengths / $count;

        return [$count, count($origin_counts), count($destination_counts), $mean];
    }

    /**
     * @psalm-return list<list<string>>
     * @psalm-mutation-free
     */
    public function summarizeEdges(): array
    {
        $edges = [];

        foreach ($this->forward_edges as $source => $destinations) {
            $edges[] = [$source, ...array_keys($destinations)];
        }

        return $edges;
    }
}
