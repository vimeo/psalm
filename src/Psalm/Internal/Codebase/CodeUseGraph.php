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
     * @psalm-external-mutation-free
     */
    public function getNodeForClass(
        string $class_id,
    ): DataFlowNode {
        $id = 'class '.$class_id;
        if (array_key_exists($id, $this->nodes)) {
            return $this->nodes[$id];
        }
        $this->nodes[$id] = $node = DataFlowNode::make($id, $id, null);
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
        bool $reading,
    ): DataFlowNode {
        if (!$reading) {
            return $this->getNodeForClass($class_id);
        }
        $id = 'property '.$class_id.'::'.$property_name;
        if (array_key_exists($id, $this->nodes)) {
            return $this->nodes[$id];
        }
        $this->nodes[$id] = $node = DataFlowNode::make($id, $id, null);
        return $node;
    }

    /**
     * @param lowercase-string $class_id
     * @param lowercase-string $const_name
     * @psalm-external-mutation-free
     */
    public function getNodeForClassConstant(
        string $class_id,
        string $const_name,
    ): DataFlowNode {
        $id = 'const ' . $class_id . '::' . $const_name;
        if (array_key_exists($id, $this->nodes)) {
            return $this->nodes[$id];
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
    ): DataFlowNode {
        $key = 'func '.$func;
        if (array_key_exists($key, $this->nodes)) {
            return $this->nodes[$key];
        }
        $this->nodes[$key] = $node = DataFlowNode::make($key, $key, null);
        return $node;
    }

    /**
     * @param lowercase-string $func
     */
    public function getNodeForFunctionLikeReturn(
        string $func,
    ): DataFlowNode {
        $id = 'return ' . $func;
        if (array_key_exists($id, $this->nodes)) {
            return $this->nodes[$id];
        }

        $this->nodes[$id] = $node = DataFlowNode::make($id, $id, null);
        return $node;
    }

    /**
     * @param lowercase-string $method_id
     * @psalm-external-mutation-free
     */
    public function getNodeForMissingMethod(
        string $method_id,
    ): DataFlowNode {
        $id = 'missing-method ' . $method_id;
        if (array_key_exists($id, $this->nodes)) {
            return $this->nodes[$id];
        }

        $this->nodes[$id] = $node = DataFlowNode::make($id, $id, null);
        return $node;
    }

    /**
     * @param lowercase-string $class_id
     * @param lowercase-string $property_name
     * @psalm-external-mutation-free
     */
    public function getNodeForMissingProperty(
        string $class_id,
        string $property_name,
    ): DataFlowNode {
        $id = 'missing-property ' . $class_id . '::' . $property_name;
        if (array_key_exists($id, $this->nodes)) {
            return $this->nodes[$id];
        }

        $this->nodes[$id] = $node = DataFlowNode::make($id, $id, null);
        return $node;
    }

    /**
     * @psalm-external-mutation-free
     */
    public function getForGenericUse(?CodeLocation $location = null): DataFlowNode
    {
        $k = $this->collect_locations && $location !== null ? $location->file_path : 'generic-use';
        if (array_key_exists($k, $this->nodes)) {
            return $this->nodes[$k];
        }
        $this->nodes[$k] = $node = DataFlowNode::make(
            $k,
            'generic-use',
            $location,
        );
        return $node;
    }

    public function addReferenceToNode(
        DataFlowNode $node,
        ?Context $context,
        ?CodeLocation $location = null,
    ): void {
        if (!$this->collect_locations) {
            $location = null;
        }
        $location_caller = $this->getForGenericUse($location);

        $caller = null;
        if ($context?->calling_method_id !== null) {
            $caller = $this->getNodeForFunctionLike($context->calling_method_id);
        } elseif ($context?->calling_function_id !== null) {
            $caller = $this->getNodeForFunctionLike($context->calling_function_id);
        } elseif ($context?->self) {
            $caller = $this->getNodeForClass($context->self);
        } else {
            $caller = $location_caller;
        }

        $this->addPath(
            $caller,
            $node,
            'use',
        );

        if ($location !== null) {
            $this->addPath(
                $location_caller,
                $node,
                'use',
            );
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
        $id = 'func ' . $func;
        if (!array_key_exists($id, $this->nodes)) {
            return false;
        }

        return $this->hasIncomingUse($id);
    }

    /**
     * @param lowercase-string $func
     */
    public function isFunctionlikeReturnUsed(string $func): bool
    {
        $id = 'return ' . $func;
        if (!array_key_exists($id, $this->nodes)) {
            return false;
        }

        return $this->hasIncomingUse($id);
    }

    /**
     * @param lowercase-string $func
     * @return array<string, bool>
     */
    public function getFunctionlikeReferences(string $func): array
    {
        return $this->getIncomingUseSources('func ' . $func);
    }

    /**
     * @param lowercase-string $method_id
     * @return array<string, bool>
     */
    public function getMissingMethodReferences(string $method_id): array
    {
        return $this->getIncomingUseSources('missing-method ' . $method_id);
    }

    /**
     * @param lowercase-string $class_id
     * @param lowercase-string $property_name
     */
    public function isPropertyUsed(string $class_id, string $property_name): bool
    {
        $id = 'property '.$class_id.'::'.$property_name;
        if (!array_key_exists($id, $this->nodes)) {
            return false;
        }

        return $this->hasIncomingUse($id);
    }

    /**
     * @param lowercase-string $class_id
     * @param lowercase-string $property_name
     * @return array<string, bool>
     */
    public function getPropertyReferences(string $class_id, string $property_name): array
    {
        return $this->getIncomingUseSources('property '.$class_id.'::'.$property_name);
    }

    /**
     * @param lowercase-string $class_id
     * @param lowercase-string $property_name
     * @return array<string, bool>
     */
    public function getMissingPropertyReferences(string $class_id, string $property_name): array
    {
        return $this->getIncomingUseSources('missing-property ' . $class_id . '::' . $property_name);
    }

    /**
     * @param lowercase-string $class_id
     * @return array<string, bool>
     */
    public function getClassReferences(string $class_id): array
    {
        return $this->getIncomingUseSources('class ' . $class_id);
    }

    /**
     * @param lowercase-string $class_id
     * @param lowercase-string $const_name
     * @return array<string, bool>
     */
    public function getClassConstantReferences(string $class_id, string $const_name): array
    {
        return $this->getIncomingUseSources('const ' . $class_id . '::' . $const_name);
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

        return $this->hasIncomingUse($id);
    }

    private function hasIncomingUse(string $node_id): bool
    {
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
    private function getIncomingUseSources(string $node_id): array
    {
        $references = [];

        foreach ($this->forward_edges as $from_id => $to_nodes) {
            if (isset($to_nodes[$node_id])) {
                $references[$from_id] = true;
            }
        }

        return $references;
    }
}
