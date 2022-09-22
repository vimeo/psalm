<?php

namespace Psalm\Type;

abstract class TypeVisitor
{
    public const STOP_TRAVERSAL = 1;
    public const DONT_TRAVERSE_CHILDREN = 2;

    /**
     * @return self::STOP_TRAVERSAL|self::DONT_TRAVERSE_CHILDREN|null
     */
    abstract protected function enterNode(TypeNode &$type): ?int;

    /**
     * @return bool - true if we want to continue traversal, false otherwise
     */
    public function traverse(TypeNode &$node): bool
    {
        $visitor_result = $this->enterNode($node);

        if ($visitor_result === self::DONT_TRAVERSE_CHILDREN) {
            return true;
        }

        if ($visitor_result === self::STOP_TRAVERSAL) {
            return false;
        }

        $cloned = false;
        foreach ($node->getChildNodeKeys() as $key) {
            if ($node instanceof Union || $node instanceof MutableUnion) {
                $child_node = $node->getAtomicTypes();
            } else {
                $child_node = $node->{$key};
            }
            if ($child_node === null) {
                continue;
            }
            $orig = $child_node;
            if (is_array($child_node)) {
                $visitor_result = $this->traverseArray($child_node);
            } else {
                $visitor_result = $this->traverse($child_node);
            }
            if ($child_node !== $orig) {
                if ($node instanceof Union) {
                    $node = $node->getBuilder()->setTypes($child_node)->freeze();
                } elseif ($node instanceof MutableUnion) {
                    // This mutates in-place
                    $node->setTypes($child_node);
                } else {
                    if (!$cloned) {
                        $cloned = true;
                        $node = clone $node;
                    }
                    $node->{$key} = $child_node;
                }
            }
            if ($visitor_result === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<TypeNode> $nodes
     * @param-out array<TypeNode> $nodes
     */
    public function traverseArray(array &$nodes): bool
    {
        foreach ($nodes as &$node) {
            if ($this->traverse($node) === false) {
                return false;
            }
        }
        return true;
    }
}
