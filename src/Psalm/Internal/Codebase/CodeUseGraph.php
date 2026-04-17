<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use LogicException;
use Override;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\DataFlow\UseFlowNode;
use Psalm\Internal\DataFlow\Path;
use Psalm\Storage\Mutations;
use SplQueue;
use SplStack;

use function abs;
use function array_key_exists;

/**
 * @internal
 */
final class CodeUseGraph
{
    /** @var array<string, UseFlowNode> */
    private array $nodes = [];

    /** @var array<string, array<string, Path>> */
    private array $forward_edges = [];

    /**
     * @var array<string, array<string, Location>> maps from node id to location hash to location
     */
    private array $locations = [];

    public function __construct(
        private readonly Codebase $codebase,
    )
    {
    }

    /**
     * @param lowercase-string $class_id
     * @param bool $maybe
     * @psalm-external-mutation-free
     * 
     * @return $maybe ? UseFlowNode|null : UseFlowNode
     */
    public function getNodeForClass(
        string $class_id,
        bool $maybe = false,
    ): ?UseFlowNode {
        $class_id = strtolower($class_id);
        $id = 'class '.$class_id;
        if (array_key_exists($id, $this->nodes)) {
            return $this->nodes[$id];
        } else if ($maybe) {
            return null;
        }
        $this->nodes[$id] = $node = new UseFlowNode($id);
        return $node;
    }

    /**
     * @param lowercase-string $class_id
     * @param string $property_name
     * @psalm-external-mutation-free
     * 
     * @return $maybe ? UseFlowNode|null : UseFlowNode
     */
    public function getNodeForProperty(
        string $class_id,
        string $property_name,
        bool $reading,
        bool $maybe = false,
    ): ?UseFlowNode {
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
        $this->nodes[$id] = $node = new UseFlowNode($id);
        return $node;
    }

    /**
     * @param lowercase-string $class_id
     * @psalm-external-mutation-free
     * 
     * @return $maybe ? UseFlowNode|null : UseFlowNode
     */
    public function getNodeForClassConstant(
        string $class_id,
        string $const_name,
        bool $maybe = false,
    ): ?UseFlowNode {
        $class_id = strtolower($class_id);
        $id = 'const ' . $class_id . '::' . $const_name;
        if (array_key_exists($id, $this->nodes)) {
            return $this->nodes[$id];
        } else if ($maybe) {
            return null;
        }

        $this->nodes[$id] = $node = new UseFlowNode($id);
        return $node;
    }

    /**
     * @param lowercase-string $func
     * @psalm-external-mutation-free
     */
    public function getNodeForFunctionLike(
        string $func,
        bool $maybe = false,
    ): ?UseFlowNode {
        $func = strtolower($func);
        $key = 'func '.$func;
        if (array_key_exists($key, $this->nodes)) {
            return $this->nodes[$key];
        } else if ($maybe) {
            return null;
        }
        $this->nodes[$key] = $node = new UseFlowNode($key);
        return $node;
    }

    /**
     * @param lowercase-string $func
     * @psalm-external-mutation-free
     * @return $maybe ? UseFlowNode|null : UseFlowNode
     */
    public function getNodeForFunctionLikeReturn(
        string $func,
        bool $maybe = false,
    ): ?UseFlowNode {
        $func = strtolower($func);
        $id = 'return ' . $func;
        if (array_key_exists($id, $this->nodes)) {
            return $this->nodes[$id];
        } else if ($maybe) {
            return null;
        }

        $this->nodes[$id] = $node = new UseFlowNode($id);
        return $node;
    }

    /**
     * @param lowercase-string $method_id
     * @psalm-external-mutation-free
     * @return $maybe ? UseFlowNode|null : UseFlowNode
     */
    public function getNodeForMissingMethod(
        string $method_id,
        bool $maybe = false,
    ): ?UseFlowNode {
        $method_id = strtolower($method_id);
        $id = 'missing-method ' . $method_id;
        if (array_key_exists($id, $this->nodes)) {
            return $this->nodes[$id];
        } else if ($maybe) {
            return null;
        }

        $this->nodes[$id] = $node = new UseFlowNode($id);
        return $node;
    }

    /**
     * @param lowercase-string $class_id
     * @psalm-external-mutation-free
     * @return $maybe ? UseFlowNode|null : UseFlowNode
     */
    public function getNodeForMissingProperty(
        string $class_id,
        string $property_name,
        bool $maybe = false,
    ): ?UseFlowNode {
        $class_id = strtolower($class_id);
        $id = 'missing-property ' . $class_id . '::' . $property_name;
        if (array_key_exists($id, $this->nodes)) {
            return $this->nodes[$id];
        } else if ($maybe) {
            return null;
        }

        $this->nodes[$id] = $node = new UseFlowNode($id);
        return $node;
    }

    /**
     * @psalm-external-mutation-free
     */
    private function getForGenericUse(): UseFlowNode
    {
        if (array_key_exists('generic-use', $this->nodes)) {
            return $this->nodes['generic-use'];
        }
        $this->nodes['generic-use'] = $node = new UseFlowNode('generic-use');
        return $node;
    }

    /**
     * @psalm-external-mutation-free
     */
    private function getForPublicApi(): UseFlowNode
    {
        if (array_key_exists('public-api', $this->nodes)) {
            return $this->nodes['public-api'];
        }
        $this->nodes['public-api'] = $node = new UseFlowNode('public-api');
        return $node;
    }

    public function markAsPublicApi(UseFlowNode $node): void
    {
        $this->addPath(
            $this->getForPublicApi(),
            $node,
            'use',
        );
    }

    public function addReferenceToNode(
        UseFlowNode $node,
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

        if ($this->codebase->collect_locations && $location !== null) {
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
    public function addPath(
        UseFlowNode $from,
        UseFlowNode $to,
        string $path_type,
    ): void {
        $from_id = $from->id;
        $to_id = $to->id;

        if ($from_id === $to_id) {
            return;
        }

        $this->forward_edges[$from_id][$to_id] = new Path($path_type, 0);
    }

    public function resolve(): void {
        if ($this->getForPublicApi()->used) {
            return;
        }
        $this->getForGenericUse();

        $this->resolveInner($this->getForPublicApi()->id, Mutations::LEVEL_NONE);
        $this->resolveInner($this->getForGenericUse()->id, Mutations::LEVEL_NONE);

        foreach ($this->nodes as $node) {
            if (!$node->used) {
                $this->resolveInnerMutations($node->id, $node->mutation_level);
            }
        }
    }

    private function resolveInner(string $id, int $level): void {
        foreach ($this->forward_edges[$id] as $to_id => $_) {
            $node = $this->nodes[$to_id];
            if (!$node->used || $node->mutation_level < $level) {
                $node->used = true;
                $this->resolveInner($to_id, max($level, $node->mutation_level));
            }
        }
    }

    private function resolveInnerMutations(string $id, int $level): void {
        foreach ($this->forward_edges[$id] as $to_id => $_) {
            $node = $this->nodes[$to_id];
            if ($node->mutation_level < $level) {
                $this->resolveInnerMutations($to_id, max($level, $node->mutation_level));
            }
        }
    }

    // General

    public function isUsed(?UseFlowNode $node): bool
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
    public function getReferences(?UseFlowNode $node): array
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
    public function getReferenceLocations(?UseFlowNode $node): array
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
