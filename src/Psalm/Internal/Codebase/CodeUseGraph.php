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
     * @psalm-external-mutation-free
     */
    public function getNodeForClass(
        string $class_id,
    ): DataFlowNode {
        $class_id = strtolower($class_id);
        $id = 'class '.$class_id;
        if (array_key_exists($id, $this->nodes)) {
            return $this->nodes[$id];
        }
        $this->nodes[$id] = $node = DataFlowNode::make($id, $id, null);
        return $node;
    }

    /**
     * @param lowercase-string $class_id
     * @param string $property_name
     * @psalm-external-mutation-free
     */
    public function getNodeForProperty(
        string $class_id,
        string $property_name,
        bool $reading,
    ): DataFlowNode {
        $class_id = strtolower($class_id);
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
     * @psalm-external-mutation-free
     */
    public function getNodeForClassConstant(
        string $class_id,
        string $const_name,
    ): DataFlowNode {
        $class_id = strtolower($class_id);
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
        $func = strtolower($func);
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
        $func = strtolower($func);
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
        $method_id = strtolower($method_id);
        $id = 'missing-method ' . $method_id;
        if (array_key_exists($id, $this->nodes)) {
            return $this->nodes[$id];
        }

        $this->nodes[$id] = $node = DataFlowNode::make($id, $id, null);
        return $node;
    }

    /**
     * @param lowercase-string $class_id
     * @psalm-external-mutation-free
     */
    public function getNodeForMissingProperty(
        string $class_id,
        string $property_name,
    ): DataFlowNode {
        $class_id = strtolower($class_id);
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

    // --- is*Used ---

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
     */
    public function isClassUsed(string $class_id): bool
    {
        $id = 'class '.$class_id;
        if (!array_key_exists($id, $this->nodes)) {
            return false;
        }

        return $this->hasIncomingUse($id);
    }

    /**
     * @param lowercase-string $class_id
     * @param lowercase-string $const_name
     */
    public function isClassConstantUsed(string $class_id, string $const_name): bool
    {
        $id = 'const ' . $class_id . '::' . $const_name;
        if (!array_key_exists($id, $this->nodes)) {
            return false;
        }

        return $this->hasIncomingUse($id);
    }

    // --- get*References ---

    /**
     * @param lowercase-string $func
     * @return array<string, bool>
     */
    public function getFunctionlikeReferences(string $func): array
    {
        return $this->getIncomingUseSources('func ' . $func);
    }

    /**
     * @param lowercase-string $func
     * @return array<string, bool>
     */
    public function getFunctionlikeReturnReferences(string $func): array
    {
        return $this->getIncomingUseSources('return ' . $func);
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
     * @param lowercase-string $method_id
     * @return array<string, bool>
     */
    public function getMissingMethodReferences(string $method_id): array
    {
        return $this->getIncomingUseSources('missing-method ' . $method_id);
    }

    // --- get*ReferenceLocations ---

    /**
     * @param lowercase-string $func
     * @return array<string, CodeLocation>
     */
    public function getFunctionlikeReferenceLocations(string $func): array
    {
        return $this->getIncomingUseSourceLocations('func ' . $func);
    }

    /**
     * @param lowercase-string $func
     * @return array<string, CodeLocation>
     */
    public function getFunctionlikeReturnReferenceLocations(string $func): array
    {
        return $this->getIncomingUseSourceLocations('return ' . $func);
    }

    /**
     * @param lowercase-string $class_id
     * @param lowercase-string $property_name
     * @return array<string, CodeLocation>
     */
    public function getPropertyReferenceLocations(string $class_id, string $property_name): array
    {
        return $this->getIncomingUseSourceLocations('property '.$class_id.'::'.$property_name);
    }

    /**
     * @param lowercase-string $class_id
     * @param lowercase-string $property_name
     * @return array<string, CodeLocation>
     */
    public function getMissingPropertyReferenceLocations(string $class_id, string $property_name): array
    {
        return $this->getIncomingUseSourceLocations('missing-property ' . $class_id . '::' . $property_name);
    }

    /**
     * @param lowercase-string $class_id
     * @return array<string, CodeLocation>
     */
    public function getClassReferenceLocations(string $class_id): array
    {
        return $this->getIncomingUseSourceLocations('class ' . $class_id);
    }

    /**
     * @param lowercase-string $class_id
     * @param lowercase-string $const_name
     * @return array<string, CodeLocation>
     */
    public function getClassConstantReferenceLocations(string $class_id, string $const_name): array
    {
        return $this->getIncomingUseSourceLocations('const ' . $class_id . '::' . $const_name);
    }

    /**
     * @param lowercase-string $method_id
     * @return array<string, CodeLocation>
     */
    public function getMissingMethodReferenceLocations(string $method_id): array
    {
        return $this->getIncomingUseSourceLocations('missing-method ' . $method_id);
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
    public function getIncomingUseSources(string $node_id): array
    {
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
    private function getIncomingUseSourceLocations(string $node_id): array
    {
        return $this->locations[$node_id] ?? [];
    }

    public function getAllIncomingUseSources(): array
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
    public function getAllIncomingUseSourceLocations(): array
    {
        return $this->locations;
    }
}
