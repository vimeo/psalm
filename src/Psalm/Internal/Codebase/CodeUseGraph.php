<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use Override;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\DataFlow\Path;

use function abs;
use function count;

/**
 * @internal
 */
final class CodeUseGraph extends DataFlowGraph
{
    /** @var array<string, DataFlowNode> */
    private array $nodes = [];

    /**
     * @psalm-external-mutation-free
     */
    #[Override]
    public function addNode(DataFlowNode $node): void
    {
        throw new \LogicException('Use getNodeForClass or getNodeForFunctionLike instead');
    }

    /**
     * @param lowercase-string $class_id
     * @psalm-external-mutation-free
     */
    public function getNodeForClass(
        string $class_id,
    ): DataFlowNode {
        $id = 'class '.$class_id;
        if (array_key_exists($id, $this->nodes)) {
            return $this->nodes[$id];
        }
        $this->nodes[$id] = $node = DataFlowNode::make(
            $id,
            $id,
            null,
        );
        return $node;
    }

    /**
     * @param lowercase-string $class_id
     * @param lowercase-string $property_name
     * @psalm-external-mutation-free
     */
    public function getNodeForProperty(
        string $class_id,
        string $property_name,
    ): DataFlowNode {
        $id = 'property '.$class_id.'::'.$property_name;
        if (array_key_exists($id, $this->nodes)) {
            return $this->nodes[$id];
        }
        $this->nodes[$id] = $node = DataFlowNode::make(
            $id,
            $id,
            null,
        );
        return $node;
    }

    /**
     * @param lowercase-string $func
     * @psalm-external-mutation-free
     */
    public function getNodeForFunctionLike(
        string $func,
    ): DataFlowNode {
        if (array_key_exists($func, $this->nodes)) {
            return $this->nodes[$func];
        }
        $this->nodes[$func] = $node = DataFlowNode::make(
            $func,
            $func,
            null,
        );
        return $node;
    }

    /*
    public function getNodeForFile(
        string $file_path,
    ): DataFlowNode {
        $file_path = 'file '.$file_path;
        if (array_key_exists($file_path, $this->nodes)) {
            return $this->nodes[$file_path];
        }
        $this->nodes[$file_path] = $node = DataFlowNode::make(
            $file_path,
            $file_path,
            null,
        );
        return $node;
    }*/
    
    /**
     * @psalm-external-mutation-free
     */
    public function getForPsalmApi(): DataFlowNode
    {
        if (array_key_exists('psalm-api', $this->nodes)) {
            return $this->nodes['psalm-api'];
        }
        $this->nodes['psalm-api'] = $node = DataFlowNode::make(
            'psalm-api',
            'psalm-api',
            null,
        );
        return $node;
    }

    
    /**
     * @psalm-external-mutation-free
     */
    public function getForGenericUse(): DataFlowNode
    {
        if (array_key_exists('generic-use', $this->nodes)) {
            return $this->nodes['generic-use'];
        }
        $this->nodes['generic-use'] = $node = DataFlowNode::make(
            'generic-use',
            'generic-use',
            null,
        );
        return $node;
    }

    public function addReferenceToNode(
        DataFlowNode $node,
        Context $context,
    ): void {
        if ($context->calling_method_id !== null) {
            $caller = $this->getNodeForFunctionLike($context->calling_method_id);
        } elseif ($context->calling_function_id !== null) {
            $caller = $this->getNodeForFunctionLike($context->calling_function_id);
        } else {
            // Source is not a method or function, so we assume it's a file-level use,
            // so used 
            $caller = $this->getForGenericUse();
        }

        $this->addPath(
            $caller,
            $node,
            'use',
        );
    }

    /**
     * @psalm-external-mutation-free
     */
    #[Override]
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

        $this->forward_edges[$from_id][$to_id] = new Path($path_type, $length);
    }

    /**
     * @param lowercase-string $func
     */
    public function isFunctionlikeUsed(string $func): bool
    {
        if (!array_key_exists($func, $this->nodes)) {
            return false;
        }

        return $this->isCodeUsed($this->nodes[$func]);
    }
    /**
     * @param lowercase-string $class_id
     */
    public function isClassUsed(string $class_id): bool
    {
        $id = 'class '.$class_id;
        if (!array_key_exists($id, $this->nodes)) {
            return false;
        }

        return $this->isCodeUsed($this->nodes[$id]);
    }

    private function isCodeUsed(DataFlowNode $assignment_node): bool
    {
        $visited_source_ids = [];

        $sources = [$assignment_node];

        for ($i = 0; count($sources) && $i < 200; $i++) {
            $new_child_nodes = [];

            foreach ($sources as $source) {
                $visited_source_ids[$source->id] = true;

                if ($this->getChildNodes(
                    $new_child_nodes,
                    $source,
                    $visited_source_ids,
                )) {
                    return true;
                }
            }

            $sources = $new_child_nodes;
        }

        return false;
    }

    /**
     * @param array<string, bool> $visited_source_ids
     * @param array<string, DataFlowNode> $child_nodes
     * @param-out array<string, DataFlowNode> $child_nodes
     */
    private function getChildNodes(
        array &$child_nodes,
        DataFlowNode $generated_source,
        array $visited_source_ids,
    ): bool {
        if (!isset($this->forward_edges[$generated_source->id])) {
            return false;
        }

        foreach ($this->forward_edges[$generated_source->id] as $to_id => $path) {
            $path_type = $path->type;

            if ($path_type === 'psalm-api') {
                return true;
            }

            if (isset($visited_source_ids[$to_id])) {
                continue;
            }

            $path_types = $generated_source->path_types;
            $path_types []= $path_type;
            $new_destination = new DataFlowNode(
                $to_id,
                null,
                null,
                $to_id,
                null,
                0,
                null,
                $path_types,
            );

            $child_nodes[$to_id] = $new_destination;
        }

        return false;
    }
}
