<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use LogicException;
use Override;
use Psalm\Codebase;
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

    private readonly bool $collect_locations;
    public function __construct(Codebase $codebase)
    {
        $this->collect_locations = $codebase->collect_locations;
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
        ?CodeLocation $location = null,
    ): DataFlowNode {
        $id = 'class '.$class_id;
        $key = $this->collect_locations && $location !== null ? $id . '@' . $location->getHash() : $id;
        if (array_key_exists($key, $this->nodes)) {
            return $this->nodes[$key];
        }
        $this->nodes[$key] = $node = DataFlowNode::make(
            $key,
            $id,
            $location,
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
        ?CodeLocation $location = null,
    ): DataFlowNode {
        $id = 'property '.$class_id.'::'.$property_name;
        $key = $this->collect_locations && $location !== null ? $id . '@' . $location->getHash() : $id;
        if (array_key_exists($key, $this->nodes)) {
            return $this->nodes[$key];
        }
        $this->nodes[$key] = $node = DataFlowNode::make(
            $key,
            $id,
            $location,
        );
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
        ?CodeLocation $location = null,
    ): DataFlowNode {
        $id = 'const ' . $class_id . '::' . $const_name;
        $key = $this->collect_locations && $location !== null ? $id . '@' . $location->getHash() : $id;
        if (array_key_exists($key, $this->nodes)) {
            return $this->nodes[$key];
        }

        $this->nodes[$key] = $node = DataFlowNode::make(
            $key,
            $id,
            $location,
        );

        return $node;
    }

    /**
     * @param lowercase-string $func
     * @psalm-external-mutation-free
     */
    public function getNodeForFunctionLike(
        string $func,
        ?CodeLocation $location = null,
    ): DataFlowNode {
        $id = 'func '.$func;
        $key = $this->collect_locations && $location !== null ? $id . '@' . $location->getHash() : $id;
        if (array_key_exists($key, $this->nodes)) {
            return $this->nodes[$key];
        }
        $this->nodes[$key] = $node = DataFlowNode::make(
            $key,
            $id,
            $location,
        );
        return $node;
    }

    /**
     * @param lowercase-string $func
     */
    public function getNodeForFunctionLikeReturn(
        string $func,
        ?CodeLocation $location = null,
    ): DataFlowNode {
        $id = 'return ' . $func;
        $key = $this->collect_locations && $location !== null ? $id . '@' . $location->getHash() : $id;
        if (array_key_exists($key, $this->nodes)) {
            return $this->nodes[$key];
        }

        $this->nodes[$key] = $node = DataFlowNode::make(
            $key,
            $id,
            $location,
        );

        return $node;
    }

    /**
     * @param lowercase-string $method_id
     * @psalm-external-mutation-free
     */
    public function getNodeForMissingMethod(
        string $method_id,
        ?CodeLocation $location = null,
    ): DataFlowNode {
        $id = 'missing-method ' . $method_id;
        $key = $this->collect_locations && $location !== null ? $id . '@' . $location->getHash() : $id;
        if (array_key_exists($key, $this->nodes)) {
            return $this->nodes[$key];
        }

        $this->nodes[$key] = $node = DataFlowNode::make(
            $key,
            $id,
            $location,
        );

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
        ?CodeLocation $location = null,
    ): DataFlowNode {
        $id = 'missing-property ' . $class_id . '::' . $property_name;
        $key = $this->collect_locations && $location !== null ? $id . '@' . $location->getHash() : $id;
        if (array_key_exists($key, $this->nodes)) {
            return $this->nodes[$key];
        }

        $this->nodes[$key] = $node = DataFlowNode::make(
            $key,
            $id,
            $location,
        );

        return $node;
    }
    
    /**
     * @psalm-external-mutation-free
     */
    public function getForGenericUse(?CodeLocation $location = null): DataFlowNode
    {
        $id = 'generic-use';
        $k = $this->collect_locations && $location !== null ? $id . '@' . $location->getHash() : $id;
        if (array_key_exists($k, $this->nodes)) {
            return $this->nodes[$k];
        }
        $this->nodes[$k] = $node = DataFlowNode::make(
            $k,
            $id,
            $location,
        );
        return $node;
    }

    public function addReferenceToNode(
        DataFlowNode $node,
        ?Context $context,
        ?CodeLocation $location = null,
    ): void {
        $caller = null;
        if ($context?->calling_method_id !== null) {
            $caller = $this->getNodeForFunctionLike($context->calling_method_id, $location);
        } elseif ($context?->calling_function_id !== null) {
            $caller = $this->getNodeForFunctionLike($context->calling_function_id, $location);
        } elseif ($context?->self) {
            $caller = $this->getNodeForClass($context->self, $location);
        } elseif ($this->collect_locations && $location) {
            // Source is not a method or function, so we assume it's a file-level use,
            // so used
            $caller = $this->getForGenericUse($location);
        }
        if ($caller === null) {
            return;
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
