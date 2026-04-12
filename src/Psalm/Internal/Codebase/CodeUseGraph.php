<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use LogicException;
use Override;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\DataFlow\Path;

use function abs;
use function array_key_exists;

/**
 * @internal
 */
final class CodeUseGraph extends DataFlowGraph
{
    /** @var array<string, DataFlowNode> */
    private array $nodes = [];

    /**
     * @var array<string, array<string, Location>> maps from node id to location hash to location
     */
    private array $locations = [];

    public function __construct(public bool $collect_locations)
    {
    }

    /**
     * @psalm-external-mutation-free
     */
    #[Override]
    public function addNode(DataFlowNode $node): void
    {
        throw new LogicException('Use getNodeForClass or getNodeForFunctionLike instead');
    }

    /**
     * @param lowercase-string $class_id
     * @param bool $maybe
     * @psalm-external-mutation-free
     * 
     * @return $maybe ? DataFlowNode|null : DataFlowNode
     */
    public function getNodeForClass(
        string $class_id,
        bool $maybe = false,
    ): ?DataFlowNode {
        $class_id = strtolower($class_id);
        $id = 'class '.$class_id;
        if (array_key_exists($id, $this->nodes)) {
            return $this->nodes[$id];
        } else if ($maybe) {
            return null;
        }
        $this->nodes[$id] = $node = DataFlowNode::make($id, $id, null);
        return $node;
    }

    /**
     * @param lowercase-string $class_id
     * @param string $property_name
     * @psalm-external-mutation-free
     * 
     * @return $maybe ? DataFlowNode|null : DataFlowNode
     */
    public function getNodeForProperty(
        string $class_id,
        string $property_name,
        bool $reading,
        bool $maybe = false,
    ): ?DataFlowNode {
        $class_id = strtolower($class_id);
        if (!$reading) {
            return $this->getNodeForClass($class_id, $maybe);
        }

        $id = 'property '.$class_id.'::'.$property_name;
        if (array_key_exists($id, $this->nodes)) {
            return $this->nodes[$id];
        } else if ($maybe) {
            return null;
        }
        $this->nodes[$id] = $node = DataFlowNode::make($id, $id, null);
        return $node;
    }

    /**
     * @param lowercase-string $class_id
     * @psalm-external-mutation-free
     * 
     * @return $maybe ? DataFlowNode|null : DataFlowNode
     */
    public function getNodeForClassConstant(
        string $class_id,
        string $const_name,
        bool $maybe = false,
    ): ?DataFlowNode {
        $class_id = strtolower($class_id);
        $id = 'const ' . $class_id . '::' . $const_name;
        if (array_key_exists($id, $this->nodes)) {
            return $this->nodes[$id];
        } else if ($maybe) {
            return null;
        }

        $this->nodes[$id] = $node = DataFlowNode::make($id, $id, null);
        return $node;
    }

    /**
     * @param lowercase-string $func
     * @psalm-external-mutation-free
     */
    public function getNodeForFunctionLike(
        string $func,
        bool $maybe = false,
    ): ?DataFlowNode {
        $func = strtolower($func);
        $key = 'func '.$func;
        if (array_key_exists($key, $this->nodes)) {
            return $this->nodes[$key];
        } else if ($maybe) {
            return null;
        }
        $this->nodes[$key] = $node = DataFlowNode::make($key, $key, null);
        return $node;
    }

    /**
     * @param lowercase-string $func
     * @psalm-external-mutation-free
     * @return $maybe ? DataFlowNode|null : DataFlowNode
     */
    public function getNodeForFunctionLikeReturn(
        string $func,
        bool $maybe = false,
    ): ?DataFlowNode {
        $func = strtolower($func);
        $id = 'return ' . $func;
        if (array_key_exists($id, $this->nodes)) {
            return $this->nodes[$id];
        } else if ($maybe) {
            return null;
        }

        $this->nodes[$id] = $node = DataFlowNode::make($id, $id, null);
        return $node;
    }

    /**
     * @param lowercase-string $method_id
     * @psalm-external-mutation-free
     * @return $maybe ? DataFlowNode|null : DataFlowNode
     */
    public function getNodeForMissingMethod(
        string $method_id,
        bool $maybe = false,
    ): ?DataFlowNode {
        $method_id = strtolower($method_id);
        $id = 'missing-method ' . $method_id;
        if (array_key_exists($id, $this->nodes)) {
            return $this->nodes[$id];
        } else if ($maybe) {
            return null;
        }

        $this->nodes[$id] = $node = DataFlowNode::make($id, $id, null);
        return $node;
    }

    /**
     * @param lowercase-string $class_id
     * @psalm-external-mutation-free
     * @return $maybe ? DataFlowNode|null : DataFlowNode
     */
    public function getNodeForMissingProperty(
        string $class_id,
        string $property_name,
        bool $maybe = false,
    ): ?DataFlowNode {
        $class_id = strtolower($class_id);
        $id = 'missing-property ' . $class_id . '::' . $property_name;
        if (array_key_exists($id, $this->nodes)) {
            return $this->nodes[$id];
        } else if ($maybe) {
            return null;
        }

        $this->nodes[$id] = $node = DataFlowNode::make($id, $id, null);
        return $node;
    }

    /**
     * @psalm-external-mutation-free
     */
    private function getForGenericUse(): DataFlowNode
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

    /**
     * @psalm-external-mutation-free
     */
    private function getForPublicApi(): DataFlowNode
    {
        if (array_key_exists('public-api', $this->nodes)) {
            return $this->nodes['public-api'];
        }
        $this->nodes['public-api'] = $node = DataFlowNode::make(
            'public-api',
            'public-api',
            null,
        );
        return $node;
    }

    public function markAsPublicApi(DataFlowNode $node): void
    {
        $this->addPath(
            $this->getForPublicApi(),
            $node,
            'use',
        );
    }

    public function addReferenceToNode(
        DataFlowNode $node,
        ?Context $context,
        ?CodeLocation $location = null,
    ): void {
        if ($context?->calling_method_id !== null) {
            $caller = $this->getNodeForFunctionLike($context->calling_method_id);
        } elseif ($context?->calling_function_id !== null) {
            $caller = $this->getNodeForFunctionLike($context->calling_function_id);
        } elseif ($context?->self) {
            $caller = $this->getNodeForClass($context->self);
        } elseif ($location !== null) {
            $caller = $this->getForGenericUse();
        } else {
            return;
        }

        $this->addPath(
            $caller,
            $node,
            'use',
        );

        if ($this->collect_locations && $location !== null) {
            $id = $location->getHash();
            $this->locations[$node->id][$id] = $location;
        }
    }

    /**
     * @psalm-external-mutation-free
     */
    public function addGraph(self $other): void
    {
        $this->nodes += $other->nodes;

        foreach ($other->forward_edges as $key => $map) {
            if (!isset($this->forward_edges[$key])) {
                $this->forward_edges[$key] = $map;
            } else {
                $this->forward_edges[$key] += $map;
            }
        }

        foreach ($other->locations as $node_id => $locations) {
            if (!isset($this->locations[$node_id])) {
                $this->locations[$node_id] = $locations;
            } else {
                $this->locations[$node_id] += $locations;
            }
        }
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

    // General

    public function isUsed(?DataFlowNode $node): bool
    {
        if ($node === null) {
            return false;
        }
        $node_id = $node->id;

        foreach ($this->forward_edges as $from_id => $to_nodes) {
            if ($from_id === $node_id) {
                continue;
            }

            if (isset($to_nodes[$node_id])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, bool>
     */
    public function getReferences(?DataFlowNode $node): array
    {
        if ($node === null) {
            return [];
        }
        $node_id = $node->id;

        $references = [];

        foreach ($this->forward_edges as $from_id => $to_nodes) {
            if (isset($to_nodes[$node_id])) {
                $references[$from_id] = true;
            }
        }

        return $references;
    }

    /**
     * @return array<string, CodeLocation>
     */
    public function getReferenceLocations(?DataFlowNode $node): array
    {
        if ($node === null) {
            return [];
        }
        $node_id = $node->id;
        return $this->locations[$node_id] ?? [];
    }

    // All

    public function getAllReferences(): array
    {
        $result = [];

        foreach ($this->forward_edges as $from_id => $to_nodes) {
            foreach ($to_nodes as $to_id => $_) {
                if (!isset($result[$to_id])) {
                    $result[$to_id] = [];
                }
                $result[$to_id][$from_id] = true;
            }
        }

        return $result;
    }
    public function getAllReferenceLocations(): array
    {
        return $this->locations;
    }
}
